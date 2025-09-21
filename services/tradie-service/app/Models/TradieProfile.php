<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradieProfile extends Model
{
    protected $fillable = ['account_id','business_name','about','postcode','base_rate'];

    public function categories()
    {
        return $this->belongsToMany(ServiceCategory::class, 'tradie_services');
    }
}
