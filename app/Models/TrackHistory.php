<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackHistory extends Model
{
    use HasFactory;
    protected $table = 'track_history';

    protected $fillable = [
        'trackid',
    ];
}
