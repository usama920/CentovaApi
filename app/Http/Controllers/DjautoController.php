<?php

namespace App\Http\Controllers;

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
}
