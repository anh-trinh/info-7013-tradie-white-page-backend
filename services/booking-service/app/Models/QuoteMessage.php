<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class QuoteMessage extends Model {
  protected $table = 'quote_messages';
  protected $fillable = ['quote_id','sender_account_id','message','offered_price'];
}