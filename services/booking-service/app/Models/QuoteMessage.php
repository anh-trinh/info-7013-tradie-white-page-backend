<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class QuoteMessage extends Model
{
    protected $fillable = ['quote_id','sender_account_id','message','offered_price'];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }
}
