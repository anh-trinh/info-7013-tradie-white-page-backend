<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradieProfile extends Model
{
    protected $table = 'tradie_profiles';
    protected $fillable = [
        'account_id', 'business_name', 'about', 'postcode', 'base_rate'
    ];
}
