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

    public function UpdateSongRequests(Request $request)
    {
        $request->validate([
            'account_id' => 'required',
            'automatically_queue' => 'required',
            'request_delay' => 'required',
            'request_probability' => 'required',
            'email_unknown_requests' => 'required'
        ]);
        Account::where(['id' => $request->account_id])->update([
            'autoqueuerequests' => $request->automatically_queue,
            'requestdelay' => $request->request_delay,
            'requestprobability' => $request->request_probability,
            'emailunknownrequests' => $request->email_unknown_requests
        ]);

        return response()->json(['status' => 'success']);
    }
}
