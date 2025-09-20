<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model {
  protected $table = 'bookings';
  protected $fillable = ['quote_id','final_price','scheduled_at','status'];
  public function quote() { return $this->belongsTo(Quote::class,'quote_id'); }
}