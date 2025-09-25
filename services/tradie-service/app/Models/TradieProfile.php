<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradieProfile extends Model
{
    protected $fillable = [
        'account_id',
        'business_name',
        'about',
        'postcode',
        'base_rate',
        'email',
        'phone_number',
        'contact_person',
        'average_rating',
        'reviews_count'
    ];

    protected $casts = [
        'average_rating' => 'float',
        'reviews_count' => 'integer',
    ];

    public function categories()
    {
        return $this->belongsToMany(ServiceCategory::class, 'tradie_services');
    }
}
