<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $table = 'accounts';
    protected $fillable = [
        'first_name', 'last_name', 'email', 'password', 'phone_number', 'role', 'status'
    ];
    protected $hidden = ['password'];
}
