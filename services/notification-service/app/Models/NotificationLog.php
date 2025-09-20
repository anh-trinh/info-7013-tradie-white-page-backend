<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $fillable = ['recipient_email', 'template_name', 'status', 'sent_at'];
    public $timestamps = false;
}
