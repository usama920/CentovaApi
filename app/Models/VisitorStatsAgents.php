<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorStatsAgents extends Model
{
    use HasFactory;
    protected $table = 'visitorstats_agents';

    protected $fillable = [
        'name',
    ];
}
