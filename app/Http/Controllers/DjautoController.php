<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Playlists;
use App\Models\Track;
use App\Models\TrackHistory;
use App\Models\VisitorStatsSessions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Throwable;

class DjautoController extends Controller
{
    public function Playlists(Request $request)
    {
        $request->validate([
            'account_id' => 'required'
        ]);

        $playlists = Playlists::where(['accountid' => $request->account_id])->get();

        return response()->json(['playlists' => $playlists]);
    }

    public function UpdateSettings(Request $request)
    {
        $request->validate([
            'account_id' => 'required',
            'genre' => 'required',
            'crossfade' => 'required',
            'ignoremeta' => 'required'
        ]);
        Account::where(['accountid' => $request->account_id])->update([
            'genre' => $request->genre,
            'crossfade' => $request->crossfdae,
            'ignoremeta' => $request->ignoremeta
        ]);
        return response()->json(['status' => 'success']);
    }
}
