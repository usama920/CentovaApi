<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaylistTracks extends Model
{
    use HasFactory;
    protected $table = 'playlist_tracks';

    protected $fillable = [
        'id',
        'playlistid',
        'trackid'
    ];

    public function sectionsCountRelation()
    {
        return $this->hasOne(Playlists::class, 'id', 'playlistid')->selectRaw('id, count(*) as count')->groupBy('playlist_id');
        // replace module_id with appropriate foreign key if needed
    }
}
