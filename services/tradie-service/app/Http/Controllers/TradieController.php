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

        // Enrich ratings from review-service for immediate correctness.
        // Aggregate from account_id and fallback to primary id for compatibility with seeded review IDs.
        $client = new Client([ 'timeout' => 2.5, 'connect_timeout' => 1.0 ]);
        foreach ($tradies as $t) {
            try {
                $sum = 0; $cnt = 0;
                // 1) By account_id
                if ($t->account_id) {
                    $resp = $client->get('http://review-service:8000/api/reviews/tradie/' . $t->account_id);
                    if ($resp->getStatusCode() === 200) {
                        $body = (string) $resp->getBody();
                        $data = json_decode($body, true) ?: [];
                        if (is_array($data)) {
                            foreach ($data as $r) { if (isset($r['rating'])) { $sum += (int)$r['rating']; $cnt++; } }
                        }
                    }
                }
                // 2) Fallback by primary id (helps when reviews are seeded against small IDs)
                if ($cnt === 0) {
                    $resp2 = $client->get('http://review-service:8000/api/reviews/tradie/' . $t->id);
                    if ($resp2->getStatusCode() === 200) {
                        $body2 = (string) $resp2->getBody();
                        $data2 = json_decode($body2, true) ?: [];
                        if (is_array($data2)) {
                            foreach ($data2 as $r) { if (isset($r['rating'])) { $sum += (int)$r['rating']; $cnt++; } }
                        }
                    }
                }
                if ($cnt > 0) {
                    $t->reviews_count = $cnt;
                    $t->average_rating = round($sum / $cnt, 1);
                }
            } catch (\Throwable $e) {
                // Ignore and keep defaults
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

        // If ratings are not yet populated (e.g., worker not processed), compute on the fly from review-service
        if ((int)($tradieProfile->reviews_count ?? 0) === 0) {
            try {
                $client = new Client([ 'timeout' => 3, 'connect_timeout' => 1.5 ]);
                // account_id is the identifier used by review-service as tradie_account_id
                $accountId = $tradieProfile->account_id;
                $sum = 0; $cnt = 0;
                if ($accountId) {
                    $resp = $client->get('http://review-service:8000/api/reviews/tradie/' . $accountId);
                    if ($resp->getStatusCode() === 200) {
                        $data = json_decode($resp->getBody()->getContents(), true) ?: [];
                        if (is_array($data)) {
                            foreach ($data as $r) { if (isset($r['rating'])) { $sum += (int)$r['rating']; $cnt++; } }
                        }
                    }
                }
                // Fallback to primary id if nothing found by account
                if ($cnt === 0) {
                    $resp2 = $client->get('http://review-service:8000/api/reviews/tradie/' . $tradieProfile->id);
                    if ($resp2->getStatusCode() === 200) {
                        $data2 = json_decode($resp2->getBody()->getContents(), true) ?: [];
                        if (is_array($data2)) {
                            foreach ($data2 as $r) { if (isset($r['rating'])) { $sum += (int)$r['rating']; $cnt++; } }
                        }
                    }
                }
                if ($cnt > 0) {
                    $tradieProfile->reviews_count = $cnt;
                    $tradieProfile->average_rating = round($sum / $cnt, 1);
                }
            } catch (\Throwable $e) {
                // Ignore network errors and continue returning stored values
                // Log::warning('Rating enrichment failed: '.$e->getMessage());
            }
        }

        // Return the complete profile including contact information
        return response()->json($tradieProfile);
    }
}
