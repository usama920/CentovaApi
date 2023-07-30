<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Playlists extends Model
{
    use HasFactory;
    protected $table = 'playlists';

    protected $fillable = [
        'id',
    ];

    public function playlistTracks()
    {
        return $this->hasMany(PlaylistTracks::class, 'playlistid', 'id');
    }
}
