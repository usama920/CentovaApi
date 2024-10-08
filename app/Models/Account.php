<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;
    protected $table = 'accounts';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'appwrite_id',
        'forgot_password_code',
        'forgot_password_status',
        'forgot_password_time'
    ];
}
