<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorStatsSessions extends Model
{
    use HasFactory;
    protected $table = 'visitorstats_sessions';

    protected $fillable = [
        'accountid',
        'useragentid',
        'starttime',
        'endtime',
        'duration',
        'bandwidth',
        'ipaddress',
        'country',
        'resumedata'
    ];
}
