<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradieService extends Model
{
    protected $table = 'tradie_services';
    public $timestamps = false;
    protected $fillable = [
        'tradie_profile_id', 'service_category_id'
    ];
}
