<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Playlists;
use App\Models\PlaylistTracks;
use App\Models\Track;
use App\Models\TrackHistory;
use App\Models\VisitorStatsSessions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class DjautoController extends Controller
{

    public function EnableAutodj(Request $request)
    {
        $request->validate([
            'account_id' => 'required',
            'username' => 'required',
            'password' => 'required',
        ]);
        $username = $request->username;
        $password = $request->password;
        $account = Account::where(['username' => $username])->first();
        if ($account) {
            $checkPassword = Hash::check($password, $account->password);
            if ($checkPassword) {
                $account->usesource = 1;
                $account->save();
                return response()->json(['status' => 'success']);
            } else {
                return response()->json([
                    'status' => 'error',
                    'messsage' => 'Invalid Credentials!'
                ]);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'messsage' => 'Invalid Credentials!'
            ]);
        }
        return response()->json(['status' => 'success']);
    }

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
            'username' => 'required',
            'password' => 'required',
            'usesource' => 'required',
            'royaltymode' => 'required',
            'shareplaylists' => 'required',
            'genre' => 'required',
            'crossfade' => 'required'
        ]);
        $username = $request->username;
        $password = $request->password;
        $account = Account::where(['username' => $username])->first();
        if ($account) {
            $checkPassword = Hash::check($password, $account->password);
            if ($checkPassword) {
                $account->usesource = $request->usesource;
                $account->royaltymode = $request->royaltymode;
                $account->shareplaylists = $request->shareplaylists;
                $account->genre = $request->genre;
                $account->crossfade = $request->crossfade;
                $account->save();
                return response()->json(['status' => 'success']);
            } else {
                return response()->json([
                    'status' => 'error',
                    'messsage' => 'Invalid Credentials!'
                ]);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'messsage' => 'Invalid Credentials!'
            ]);
        }
        return response()->json(['status' => 'success']);
    }

    public function CreateJingle(Request $request)
    {
        $request->validate([
            'account_id' => 'required',
            'username' => 'required',
            'password' => 'required',
            'title' => 'required',
            'interval_length' => 'required',
            'interval_type' => 'required',
            'interval_style' => 'required'
        ]);

        $username = $request->username;
        $password = $request->password;
        $account = DB::table('accounts')->where(['username' => $username])->first();
        if ($account) {
            $checkPassword = Hash::check($password, $account->password);
            if ($checkPassword) {
                $playlist = new Playlists();
                $playlist->title = $request->title;
                $playlist->type = 'interval';
                $playlist->scheduled_datetime = date('Y-m-d H:i:s');
                $playlist->scheduled_monthdays = 'date';
                $playlist->interval_type = $request->interval_type;
                $playlist->interval_length = $request->interval_length;
                $playlist->general_weight = 1;
                $playlist->status = 'enabled';
                $playlist->general_order = 'random';
                $playlist->interval_style = $request->interval_style;
                $playlist->scheduled_lastrun = '1000-01-01 00:00:00';
                $playlist->accountid = $account->id;
                $playlist->save();
                return response()->json(['status' => 'success']);
            } else {
                return response()->json([
                    'status' => 'error',
                    'messsage' => 'Invalid Credentials!'
                ]);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'messsage' => 'Invalid Credentials!'
            ]);
        }
    }

    public function CreatePlaylist(Request $request)
    {
        $request->validate([
            'account_id' => 'required',
            'username' => 'required',
            'password' => 'required',
            'title' => 'required',
            'playlist_type' => 'required',
        ]);

        $username = $request->username;
        $password = $request->password;
        $account = DB::table('accounts')->where(['username' => $username])->first();
        if ($account) {
            $checkPassword = Hash::check($password, $account->password);
            if ($checkPassword) {
                $playlist = new Playlists();
                $playlist->title = $request->title;
                if ($request->playlist_type == "general") {
                    $playlist->type = 'general';
                    $playlist->scheduled_datetime = date('Y') . '-01-01 01:00:00';
                    $playlist->general_weight = $request->addPlaylistWeight;
                    $playlist->general_order = $request->addPlaylistPlaybackOrder;
                    if (!isset($request->addPlaylistAllDay)) {
                        $playlist->general_starttime = $request->addPlaylistDateTimeStart;
                        $playlist->general_endtime = $request->addPlaylistDateTimeEnd;
                    }
                } else if ($request->playlist_type == "scheduled") {
                    $playlist->type = 'scheduled';
                    $playlist->scheduled_datetime = date('Y') . '-' . date('m-d H:i:s', strtotime($request->addPlaylistScheduledDateTimeStart));
                    $playlist->scheduled_style = $request->addPlaylistPlaybackStyle;
                    $playlist->scheduled_interruptible = $request->addPlaylistInterruptible;
                    $playlist->scheduled_repeat = $request->addPlaylistRepeatSchedule;
                    $playlist->scheduled_duration = $request->addPlaylistStopAfter;
                    if ($request->addPlaylistRepeatSchedule == "weekly") {
                        $playlist->scheduled_weekdays = $request->schedule_weekdays;
                    } else if ($request->addPlaylistRepeatSchedule == "monthly") {
                        $playlist->scheduled_monthdays = $request->addPlaylistMonthlyRepeatSchedule;
                    }
                }
                $playlist->interval_length = 20;
                $playlist->status = "enabled";
                $playlist->scheduled_lastrun = '1000-01-01 00:00:00';
                $playlist->accountid = $account->id;
                $playlist->save();
                return response()->json(['status' => 'success']);
            } else {
                return response()->json([
                    'status' => 'error',
                    'messsage' => 'Invalid Credentials!'
                ]);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'messsage' => 'Invalid Credentials!'
            ]);
        }
    }

    public function UpdateAllPlaylist(Request $request)
    {
        try {
            $request->validate([
                'account_id' => 'required',
                'username' => 'required',
                'password' => 'required',
                'title' => 'required',
                'playlist_type' => 'required',
                'playlist_id' => 'required',
            ]);

            Log::info($request->post());

            $username = $request->username;
            $password = $request->password;
            $account = DB::table('accounts')->where(['username' => $username])->first();
            if ($account) {
                $checkPassword = Hash::check($password, $account->password);
                if ($checkPassword) {
                    $playlist = Playlists::where(['id' => $request->playlist_id, 'accountid' => $account->id])->first();
                    if (!$playlist) {
                        return response()->json(['status' => 'Playlist does not exist.']);
                    }
                    $playlist->title = $request->title;
                    if ($request->playlist_type == "general") {
                        $playlist->type = 'general';
                        $playlist->scheduled_datetime = date('Y') . '-01-01 01:00:00';
                        $playlist->general_weight = $request->editPlaylistWeight;
                        $playlist->general_order = $request->editPlaylistPlaybackOrder;
                        if (!isset($request->editPlaylistAllDay)) {
                            $playlist->general_starttime = $request->editPlaylistDateTimeStart;
                            $playlist->general_endtime = $request->editPlaylistDateTimeEnd;
                        }
                    } else if ($request->playlist_type == "scheduled") {
                        $playlist->type = 'scheduled';
                        $playlist->scheduled_datetime = date('Y') . '-' . date('m-d H:i:s', strtotime($request->editPlaylistScheduledDateTimeStart));
                        $playlist->scheduled_style = $request->editPlaylistPlaybackStyle;
                        $playlist->scheduled_interruptible = $request->editPlaylistInterruptible;
                        $playlist->scheduled_repeat = $request->editPlaylistRepeatSchedule;
                        $playlist->scheduled_duration = $request->editPlaylistStopAfter;
                        if ($request->editPlaylistRepeatSchedule == "weekly") {
                            $playlist->scheduled_weekdays = $request->schedule_weekdays;
                        } else if ($request->editPlaylistRepeatSchedule == "monthly") {
                            $playlist->scheduled_monthdays = $request->editPlaylistMonthlyRepeatSchedule;
                        }
                    }
                    $playlist->save();

                    PlaylistTracks::where(['playlistid' => $request->playlist_id])->delete();

                    if (isset($request->playlistEditFiles)) {
                        foreach ($request->playlistEditFiles as $playlistFile) {
                            $selectedTrack = Track::where(['accountid' => $account->id])
                                ->where('title', 'like', '%' . $playlistFile . '%')
                                ->first();

                            if ($selectedTrack) {
                                $playlistTracks = new PlaylistTracks();
                                $playlistTracks->trackid = $selectedTrack->id;
                                $playlistTracks->playlistid = $request->playlist_id;
                                $playlistTracks->save();
                            }
                        }
                    }
                    return response()->json(['status' => 'success']);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'messsage' => 'Invalid Credentials!'
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'messsage' => 'Invalid Credentials!'
                ]);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());

            return response()->json([
                'status' => 'error',
                'messsage' => $th->getMessage()
            ]);
        }
    }

    public function PlaylistTracks(Request $request)
    {
        $request->validate([
            'account_id' => 'required',
            'username' => 'required',
            'password' => 'required',
            'playlist_id' => 'required'
        ]);

        $username = $request->username;
        $password = $request->password;
        $account = DB::table('accounts')->where(['username' => $username])->first();
        if ($account) {
            $checkPassword = Hash::check($password, $account->password);
            if ($checkPassword) {

                $playlistTracks = PlaylistTracks::where(['playlistid' => $request->playlist_id])->with('tracks')->get();
                $userTracks = Track::where(['accountid' => $request->account_id])
                    ->get();
                // if ($request->data == 'all') {
                //     $userTracks = Track::where(['accountid' => $request->account_id])
                //         ->where('pathname', 'not like', '%' . 'jingles/' . '%')
                //         ->get();
                // } else {
                //     $userTracks = Track::where(['accountid' => $request->account_id])
                //         ->where('pathname', 'like', '%' . 'jingles/' . '%')
                //         ->get();
                // }
                return response()->json(['status' => 'success', 'playlistTracks' => $playlistTracks, 'userTracks' => $userTracks]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'messsage' => 'Invalid Credentials!'
                ]);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'messsage' => 'Invalid Credentials!'
            ]);
        }
    }

    public function DeletePlaylist(Request $request)
    {
        $request->validate([
            'account_id' => 'required',
            'username' => 'required',
            'password' => 'required',
            'playlist_id' => 'required'
        ]);

        $username = $request->username;
        $password = $request->password;
        $account = DB::table('accounts')->where(['username' => $username])->first();
        if ($account) {
            $checkPassword = Hash::check($password, $account->password);
            if ($checkPassword) {
                Playlists::where(['id' => $request->playlist_id])->delete();
                PlaylistTracks::where(['playlistid' => $request->playlist_id])->delete();
                return response()->json(['status' => 'success']);
            } else {
                return response()->json([
                    'status' => 'error',
                    'messsage' => 'Invalid Credentials!'
                ]);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'messsage' => 'Invalid Credentials!'
            ]);
        }
    }

    public function UpdatePlaylist($type, Request $request)
    {
        $request->validate([
            'account_id' => 'required',
            'username' => 'required',
            'password' => 'required',
            'playlist_id' => 'required'
        ]);

        $username = $request->username;
        $password = $request->password;
        $account_id = $request->account_id;

        $account = DB::table('accounts')->where(['username' => $username])->first();
        if ($account) {
            $checkPassword = Hash::check($password, $account->password);
            if ($checkPassword) {
                if ($type == 'interval') {
                    $request->validate([
                        'interval_type' => 'required'
                    ]);
                    Playlists::where(['id' => $request->playlist_id])->update([
                        'interval_type' => $request->interval_type
                    ]);
                } elseif ($type == 'all') {
                    $request->validate([
                        'title' => 'required',
                        'interval_length' => 'required',
                        'interval_type' => 'required',
                        'interval_style' => 'required',
                    ]);
                    Playlists::where(['id' => $request->playlist_id])->update([
                        'title' => $request->title,
                        'interval_length' => $request->interval_length,
                        'interval_type' => $request->interval_type,
                        'interval_style' => $request->interval_style,
                    ]);

                    PlaylistTracks::where(['playlistid' => $request->playlist_id])->delete();


                    if (isset($request->jingleEditFiles)) {
                        foreach ($request->jingleEditFiles as $jingleFile) {
                            $selectedTrack = Track::where(['accountid' => $account_id])
                                ->where('title', 'like', '%' . $jingleFile . '%')
                                ->first();

                            if ($selectedTrack) {
                                $playlistTracks = new PlaylistTracks();
                                $playlistTracks->trackid = $selectedTrack->id;
                                $playlistTracks->playlistid = $request->playlist_id;
                                $playlistTracks->save();
                            }
                        }
                    }
                }
                return response()->json(['status' => 'success']);
            } else {
                return response()->json([
                    'status' => 'error',
                    'messsage' => 'Invalid Credentials!'
                ]);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'messsage' => 'Invalid Credentials!'
            ]);
        }
    }
}
