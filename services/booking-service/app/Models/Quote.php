<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model {
  protected $table = 'quotes';
  protected $fillable = [
    'resident_account_id','tradie_account_id','service_address','service_postcode','job_description','status'
  ];
  public function messages() { return $this->hasMany(QuoteMessage::class, 'quote_id'); }
  public function booking() { return $this->hasOne(Booking::class, 'quote_id'); }
}