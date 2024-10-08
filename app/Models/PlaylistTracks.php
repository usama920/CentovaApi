<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaylistTracks extends Model
{
    use HasFactory;
    protected $table = 'playlist_tracks';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'playlistid',
        'trackid'
    ];

    public function sectionsCountRelation()
    {
        return $this->hasOne(Playlists::class, 'id', 'playlistid')->selectRaw('id, count(*) as count')->groupBy('playlist_id');
    }

    public function tracks()
    {
        return $this->hasMany(Track::class, 'id', 'trackid');
    }
}
