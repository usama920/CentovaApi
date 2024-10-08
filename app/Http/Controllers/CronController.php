<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Playlists;
use Illuminate\Http\Request;

class CronController extends Controller
{
    public function UpdatePlaylist(Request $request)
    {
        try {
            $playlist = Playlists::where([
                'id' => $request->playlist_id,
                'accountid' => $request->account_id
            ])->first();

            if ($playlist) {
                if ($request->playlist_type == "general") {
                    $playlist->general_starttime = $request->general_starttime;
                    $playlist->general_endtime = $request->general_endtime;
                    $playlist->general_weight = $request->general_weight;
                    $playlist->save();
                } else {
                    $playlist->scheduled_datetime = $request->scheduled_datetime;
                    $playlist->scheduled_repeat = $request->scheduled_repeat;

                    if ($request->scheduled_repeat == "weekly") {
                        $playlist->scheduled_weekdays = $request->repeat_on;
                    } else if ($request->scheduled_repeat == "montly") {
                        $playlist->scheduled_monthdays = $request->repeat_on;
                    }
                    $playlist->save();
                }
                return response()->json(['status' => 'success']);
            } else {
                return response()->json(['status' => 'Playlist Not Found']);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => $th]);
        }
    }
}
