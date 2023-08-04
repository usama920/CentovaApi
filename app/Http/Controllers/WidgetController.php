<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class WidgetController extends Controller
{
    public function UpdateRecentTracks(Request $request)
    {
        $request->validate([
            'account_id' => 'required',
            'recent_tracks' => 'required'
        ]);
        Account::where(['id' => $request->account_id])->update([
            'recenttracks' => $request->recent_tracks
        ]);

        return response()->json(['status' => 'success']);
    }
}
