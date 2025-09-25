<?php

namespace App\Http\Controllers;

use App\Models\TradieProfile;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TradieController extends Controller
{
    private function mergeJsonBody(Request $request): void
    {
        $contentType = $request->headers->get('Content-Type', '');
        $raw = $request->getContent();
        $looksJson = is_string($raw) && strlen($raw) > 0 && (str_starts_with(trim($raw), '{') || str_starts_with(trim($raw), '['));
        if (stripos($contentType, 'application/json') !== false || $looksJson) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $request->merge($decoded);
            }
        }
    }
    public function search(Request $request)
    {
        $query = TradieProfile::query();
        if ($request->has('location')) {
            $query->where('postcode', 'like', '%'.$request->input('location').'%');
        }
        if ($request->has('service')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->input('service').'%');
            });
        }
        $tradies = $query->with('categories')->get();

        // Performance: bulk enrichment instead of per-item HTTP calls
        if ($tradies->isNotEmpty()) {
            $client = new Client([ 'timeout' => 3.0, 'connect_timeout' => 1.0 ]);

            // 1) Bulk ratings summary from Review Service by account IDs
            $accountIds = $tradies->pluck('account_id')->filter()->unique()->values()->all();
            if (!empty($accountIds)) {
                try {
                    $qs = implode('&', array_map(fn($id) => 'tradie_ids=' . urlencode($id), $accountIds));
                    $url = 'http://review-service:8000/api/internal/reviews/summary?' . $qs;
                    $resp = $client->get($url);
                    if ($resp->getStatusCode() === 200) {
                        $arr = json_decode((string)$resp->getBody(), true) ?: [];
                        $ratingsMap = collect($arr)->keyBy('tradie_account_id');
                        $tradies->each(function ($t) use ($ratingsMap) {
                            $aid = (int)($t->account_id ?? 0);
                            if ($aid && $ratingsMap->has($aid)) {
                                $row = $ratingsMap->get($aid);
                                $t->reviews_count = (int)($row['reviews_count'] ?? 0);
                                $t->average_rating = (float)($row['average_rating'] ?? 0);
                            }
                        });
                    }
                } catch (\Throwable $e) {
                    // fail-soft
                }
            }

            // 2) Bulk contact enrichment from Account Service
            if (!empty($accountIds)) {
                try {
                    $url = 'http://account-service:8000/api/internal/accounts?ids=' . implode(',', $accountIds);
                    $aresp = $client->get($url);
                    if ($aresp->getStatusCode() === 200) {
                        $accs = collect(json_decode((string)$aresp->getBody(), true) ?: [])->keyBy('id');
                        $tradies->each(function ($t) use ($accs) {
                            $aid = (int)($t->account_id ?? 0);
                            if ($aid && $accs->has($aid)) {
                                $a = $accs->get($aid);
                                if (!empty($a['email'] ?? null)) {
                                    $t->setAttribute('email', $a['email']);
                                }
                                if (!empty($a['phone_number'] ?? null)) {
                                    $t->setAttribute('phone_number', $a['phone_number']);
                                }
                            }
                        });
                    }
                } catch (\Throwable $e) {
                    // fail-soft
                }
            }
        }

        // Optional: apply rating filter and sort by query params
        $sortBy = $request->input('sort_by');
        $ratingFilter = $request->input('rating'); // e.g., "4+ Stars", "all"

        // Filter by threshold if provided and not 'all'
        if ($ratingFilter && strtolower($ratingFilter) !== 'all') {
            // Extract a leading number like 4 from "4+ Stars"
            if (preg_match('/^(\\d)/', $ratingFilter, $m)) {
                $threshold = (int)$m[1];
                $tradies = $tradies->filter(function ($t) use ($threshold) {
                    $avg = (float)($t->average_rating ?? 0);
                    return $avg >= $threshold;
                })->values();
            }
        }

        if ($sortBy && stripos($sortBy, 'highest') !== false) {
            // Sort by average_rating desc, then reviews_count desc
            $tradies = $tradies->sortByDesc(function ($t) { return (float)($t->average_rating ?? 0); })
                               ->sortByDesc(function ($t) { return (int)($t->reviews_count ?? 0); })
                               ->values();
        }

        return response()->json($tradies);
    }

    // PUT /api/tradies/profile
    public function updateProfile(Request $request)
    {
        $this->mergeJsonBody($request);

        // User context provided by API Gateway
        $accountId = $request->header('X-User-Id');
        if (!$accountId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $this->validate($request, [
            'business_name' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:10',
            'base_rate' => 'nullable|string|max:255',
            'about' => 'nullable|string',
            'email' => 'nullable|email',
            'phone_number' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'primary_service_id' => 'nullable|integer',
        ]);

        // Upsert tradie profile by account_id
        $profile = TradieProfile::updateOrCreate(
            ['account_id' => $accountId],
            $request->only(['business_name', 'postcode', 'base_rate', 'about', 'email', 'phone_number', 'contact_person'])
        );

        // Update categories link if provided
        if ($request->filled('primary_service_id')) {
            $profile->categories()->sync([$request->input('primary_service_id')]);
        }

        return response()->json($profile->load('categories'));
    }

    // GET /api/tradies/{id}
    public function getById(Request $request, $id)
    {
        // Try lookup by account_id first (cross-service consistency), then fallback to primary key id
        $tradieProfile = TradieProfile::with('categories')->where('account_id', $id)->first();
        if (!$tradieProfile) {
            $tradieProfile = TradieProfile::with('categories')->findOrFail($id);
        }

        // Keep ratings as stored (avoid circular calls to review-service here)

        // Always get latest contact info (email/phone_number) from Account Service for consistency
        $accountEmail = null;
        $accountPhone = null;
        try {
            if ($tradieProfile->account_id) {
                $client = new Client([ 'timeout' => 2.5, 'connect_timeout' => 1.0 ]);
                $resp = $client->get('http://account-service:8000/api/internal/accounts/' . $tradieProfile->account_id);
                if ($resp->getStatusCode() === 200) {
                    $acc = json_decode((string)$resp->getBody(), true) ?: [];
                    // Use account service data as source of truth for contact info
                    $accountEmail = $acc['email'] ?? null;
                    $accountPhone = $acc['phone_number'] ?? null;
                }
            }
        } catch (\Throwable $e) {
            // If account-service is not reachable, use tradie profile data
        }

        // Convert to array and override with account service data
        $response = $tradieProfile->toArray();
        if ($accountEmail) {
            $response['email'] = $accountEmail;
        }
        if ($accountPhone) {
            $response['phone_number'] = $accountPhone;
        }

        // Return the complete profile with account service contact information
        return response()->json($response);
    }
}
