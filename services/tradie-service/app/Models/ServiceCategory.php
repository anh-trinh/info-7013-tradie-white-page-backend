<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    protected $fillable = ['name','description'];

    public function tradies()
    {
        return $this->belongsToMany(TradieProfile::class, 'tradie_services');
    }
}
