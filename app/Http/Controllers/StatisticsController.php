<?php

namespace App\Http\Controllers;

use App\Models\PlaybackstatsTracks;
use App\Models\Playlists;
use App\Models\Track;
use App\Models\TrackHistory;
use App\Models\VisitorStatsSessions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Throwable;

class StatisticsController extends Controller
{
    public function Report()
    {
        $uname = "shouttest2";
        $pword = "RSRnet2018";
        $url = "http://cad.casthost.ca:2199/api.php?xm=server.managedj&f=json&a[username]=" . $uname . "&a[password]=" . $pword . "&a[action]=list";;
        $json = file_get_contents($url);
        $result = json_decode($json, true);
        $account = DB::table('accounts')->first();
        dd($result);
    }

    public function Download(Request $request)
    {
        set_time_limit(600);

        $request->validate([
            'account_id' => 'required'
        ]);
        $account_id = $request->account_id ? $request->account_id : null;

        $startDate = null;
        $endDate = null;
        if (isset($request->from_date) && $request->from_date != null && isset($request->to_date) && $request->to_date != null) {
            $startDate = Carbon::createFromFormat('Y-m-d', $request->from_date)->startOfDay();
            $endDate = Carbon::createFromFormat('Y-m-d', $request->to_date)->endOfDay();
        }

        $user_tracks = [];
        $user_tracks_query = DB::table('tracks')
            ->leftJoin('track_albums', 'tracks.albumid', '=', 'track_albums.id')
            ->leftJoin('track_artists', 'tracks.artistid', '=', 'track_artists.id')
            ->where([
                'tracks.accountid' => $request->account_id
            ])
            ->select('tracks.title', 'tracks.comments', 'tracks.albumid', 'tracks.artistid', 'track_albums.name as album_name', 'track_artists.name as artist_name')->get();
        foreach ($user_tracks_query as $track) {
            array_push($user_tracks, $track);
        }

        // prx($user_tracks);


        // $totalCount = PlaybackstatsTracks::whereBetween('starttime', [$startDate, $endDate])->where(['accountid' => $account_id])->count();
        // prx($user_tracks);
        $skip = 0;
        $playlists = [];

        while ($skip < 1000) {
            $stats = PlaybackstatsTracks::whereBetween('starttime', [$startDate, $endDate])->where(['accountid' => $account_id])->orderBy('starttime', 'ASC')->skip($skip)->limit(500)->get();
            $skip += 500;

            foreach ($stats as $key => $stat) {
                $title = null;
                $raw_name = explode("-", $stat->name);

                if (isset($raw_name[1])) {
                    $title = trim($raw_name[1]);
                }

                $found = false;
                $album_data = (object)[];

                $album_data->duration = $stat->duration;
                $album_data->starttime = $stat->starttime;
                $album_data->endtime = $stat->endtime;
                $album_data->listeners = $stat->listeners;
                $album_data->comments = null;

                foreach ($user_tracks as $track) {
                    if ($track->title == $title && !$found) {
                        $album_data->raw_meta = $stat->name;
                        $album_data->comments = $track->comments;
                        array_push($playlists, $album_data);
                    }
                }
                if (!$found) {
                    $album_data->artist_name = $stat->name;
                    $album_data->raw_meta = $stat->name;
                    $album_data->album_name = null;
                    $album_data->title = $stat->name;
                }















                // $album_data = DB::table('tracks')
                //     ->leftJoin('track_albums', 'tracks.albumid', '=', 'track_albums.id')
                //     ->leftJoin('track_artists', 'tracks.artistid', '=', 'track_artists.id')
                //     ->where([
                //         'tracks.title' => $title,
                //         'tracks.accountid' => $request->account_id
                //     ])
                //     ->select('tracks.title', 'tracks.comments', 'tracks.albumid', 'tracks.artistid', 'track_albums.name as album_name', 'track_artists.name as artist_name')->first();
                // if (!$album_data) {
                //     $album_data = (object)[];
                //     $album_data->artist_name = $stat->name;
                //     $album_data->album_name = null;
                //     $album_data->title = $stat->name;
                // }
                // $album_data->raw_meta = $stat->name;
                // $album_data->duration = $stat->duration;
                // $album_data->starttime = $stat->starttime;
                // $album_data->endtime = $stat->endtime;
                // $album_data->listeners = $stat->listeners;

                array_push($playlists, $album_data);
            }
        }
        // prx($skip);

        // $stats = DB::table('playbackstats_tracks')
        //     ->where(['playbackstats_tracks.accountid' => $request->account_id])
        //     ->whereBetween('playbackstats_tracks.starttime', [$startDate, $endDate])
        //     ->join('tracks', 'tracks.title', 'LIKE', 'playbackstats_tracks.name')
        //     ->orderBy('playbackstats_tracks.starttime', 'ASC')
        //     ->limit(2)
        //     ->get();


        // $stats = DB::table('playbackstats_tracks')
        //     ->whereBetween('playbackstats_tracks.starttime', [$startDate, $endDate])
        //     ->where(['playbackstats_tracks.accountid' => $account_id])
        //     ->join('tracks', function ($join) {
        //         $raw_name = explode("-", 'playbackstats_tracks.name');
        //         prx($join);
        //         $title = null;
        //         if (isset($raw_name[1])) {
        //             $title = trim($raw_name[1]);
        //         }
        //         $join->on(DB::raw(), '=', 'tracks.accountid')->where('tracks.title', '=', $title);
        //         $join->on('playbackstats_tracks.accountid', '=', 'tracks.accountid')->where('tracks.title', '=', $title);
        //     })
        //     ->select('tracks.title', 'tracks.comments', 'tracks.albumid', 'tracks.artistid', 'playbackstats_tracks.name')
        //     ->orderBy('starttime', 'ASC')
        //     ->limit(2)->get();

        // $stats = DB::table('playbackstats_tracks')
        //     ->where(['playbackstats_tracks.accountid' => $account_id])
        //     ->whereBetween('playbackstats_tracks.starttime', [$startDate, $endDate])
        //     ->orderBy('starttime', 'ASC')
        //     ->join('tracks', 'tracks.title', '=', explode("-", 'playbackstats_tracks.name')[0])
        //     ->limit(2)->get();

        // prx($stats);




        return response()->json(['stats' => $playlists]);
    }

    public function StatisticsListeners(Request $request)
    {
        $request->validate([
            'account_id' => 'required'
        ]);

        try {
            $subDays = $request->days ? $request->days : 14;
            $account_id = $request->account_id ? $request->account_id : null;
            $subDaysTime = Carbon::today()->subDays($subDays);

            $startDate = null;
            $endDate = null;
            if (isset($request->from_date) && $request->from_date != null && isset($request->to_date) && $request->to_date != null) {
                $startDate = Carbon::createFromFormat('Y-m-d', $request->from_date)->startOfDay();
                $endDate = Carbon::createFromFormat('Y-m-d', $request->to_date)->endOfDay();
            }

            if ($startDate && $endDate) {
                $topVisitorsBySessions = VisitorStatsSessions::where(['accountid' => $account_id])->whereBetween('starttime', [$startDate, $endDate])->with('userAgents')->select('ipaddress', 'country', DB::raw('count(*) as totalSessions'), DB::raw('sum(bandwidth) as totalBandwidth'))->orderBy('totalSessions', 'DESC')->groupBy('ipaddress')->limit(10)->get();

                $topVisitorsByMinutes = VisitorStatsSessions::where(['accountid' => $account_id])->whereBetween('starttime', [$startDate, $endDate])->with('userAgents')->select('ipaddress', 'country', DB::raw('sum(bandwidth) as totalBandwidth'),  DB::raw('sum(duration) as totalDuration'))->orderBy('totalDuration', 'DESC')->groupBy('ipaddress')->limit(10)->get();
            } else {
                $topVisitorsBySessions = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->with('userAgents')->select('ipaddress', 'country', DB::raw('count(*) as totalSessions'), DB::raw('sum(bandwidth) as totalBandwidth'))->orderBy('totalSessions', 'DESC')->groupBy('ipaddress')->limit(10)->get();

                $topVisitorsByMinutes = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->with('userAgents')->select('ipaddress', 'country', DB::raw('sum(bandwidth) as totalBandwidth'),  DB::raw('sum(duration) as totalDuration'))->orderBy('totalDuration', 'DESC')->groupBy('ipaddress')->limit(10)->get();
            }

            foreach ($topVisitorsBySessions as $key => $session) {
                $topVisitorsBySessions[$key]['ip'] = long2ip($session->ipaddress);
                $topVisitorsBySessions[$key]['totalFormattedbandwidth'] = format_size($session->totalBandwidth);
            }

            foreach ($topVisitorsByMinutes as $key => $session) {
                $topVisitorsByMinutes[$key]['ip'] = long2ip($session->ipaddress);
                $topVisitorsByMinutes[$key]['totalDurationInMinutes'] = round($session->totalDuration / 60);
                $topVisitorsByMinutes[$key]['totalFormattedbandwidth'] = format_size($session->totalBandwidth);
            }

            if ($startDate && $endDate) {
                $visitorSessions = VisitorStatsSessions::where(['accountid' => $account_id])->whereBetween('starttime', [$startDate, $endDate])->get();
                $total_seconds = 0;
                $total_data = 0;
            } else {
                $visitorSessions = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->get();
                $total_seconds = 0;
                $total_data = 0;
            }

            $session_length_intervals = ['zeroTo30Sec' => 0, 'ThirtySecToTwoMin' => 0, 'TwoMinToFiveMin' => 0, 'FiveMinToFifteenMin' => 0, 'FifteenMinTOThirtyMin' => 0, 'ThirtyMinToOneHour' => 0, 'OneHourToFourHour' => 0, 'AboveFourHour' => 0];

            foreach ($visitorSessions as $session) {
                $total_seconds += $session->duration;
                $total_data += $session->bandwidth;
                if ($session->duration <= 30) {
                    $session_length_intervals['zeroTo30Sec']++;
                } elseif ($session->duration <= 120) {
                    $session_length_intervals['ThirtySecToTwoMin']++;
                } elseif ($session->duration <= 300) {
                    $session_length_intervals['TwoMinToFiveMin']++;
                } elseif ($session->duration <= 900) {
                    $session_length_intervals['FiveMinToFifteenMin']++;
                } elseif ($session->duration <= 1800) {
                    $session_length_intervals['FifteenMinTOThirtyMin']++;
                } elseif ($session->duration <= 3600) {
                    $session_length_intervals['ThirtyMinToOneHour']++;
                } elseif ($session->duration <= 10800) {
                    $session_length_intervals['OneHourToFourHour']++;
                } elseif ($session->duration > 10800) {
                    $session_length_intervals['AboveFourHour']++;
                }
            }
            $total_minutes = round($total_seconds / 60);
            $total_hours = round($total_minutes / 60, 1);
            $total_sessions = count($visitorSessions);
            $average_session_time = $total_seconds > 0 ? format_time($total_seconds, $total_sessions) : 0;
            $average_data_transfer = $total_data > 0 && $total_sessions > 0 ? format_size($total_data / $total_sessions) : format_size($total_data);

            if ($startDate && $endDate) {
                $uniqueIpSessions = VisitorStatsSessions::where(['accountid' => $account_id])->whereBetween('starttime', [$startDate, $endDate])->groupBy('ipaddress')->get();
                $uniqueCountrySessions = VisitorStatsSessions::where(['accountid' => $account_id])->whereBetween('starttime', [$startDate, $endDate])->groupBy('country')->get();
            } else {
                $uniqueIpSessions = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->groupBy('ipaddress')->get();
                $uniqueCountrySessions = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->groupBy('country')->get();
            }


            return response()->json(['total_minutes' => $total_minutes, 'total_hours' => $total_hours, 'total_sessions' => $total_sessions, 'average_session_time' => $average_session_time, 'uniqueIpSessions' => count($uniqueIpSessions), 'uniqueCountrySessions' => count($uniqueCountrySessions), 'total_data_transfer' => format_size($total_data), 'average_data_transfer' => $average_data_transfer, 'session_length_intervals' => $session_length_intervals, 'topVisitorsBySessions' => $topVisitorsBySessions, 'topVisitorsByMinutes' => $topVisitorsByMinutes]);
        } catch (Throwable $th) {
            return response()->json($th->getMessage());
        }
    }

    public function comparator($object1, $object2)
    {
        return $object1->count > $object2->count;
    }

    public function StatisticsLiveListeners(Request $request)
    {
        $request->validate([
            'account_id' => 'required',
            'username' => 'required',
            'password' => 'required'
        ]);
        $account_id = $request->account_id ? $request->account_id : null;

        $url  = "http://admin:$request->password@51.81.208.185:8800/admin.cgi?sid=1&mode=viewxml&page=3";

        $obj  = json_decode(json_encode(simplexml_load_file($url)));
        $tunedListeners = [];
        $listenersCountryWise = [];
        $listenersUserAgentWise = [];

        foreach ($obj->LISTENERS->LISTENER as $listener) {
            $location = json_decode(file_get_contents("http://ipinfo.io/{$listener->HOSTNAME}/json"));

            if ($location) {
                $newArray = [
                    'ip' => $listener->HOSTNAME,
                    'userAgent' => $listener->USERAGENT,
                    'totalDuration' => $listener->CONNECTTIME,
                    'country' => $location->country
                ];
                array_push($tunedListeners, $newArray);

                $included = false;
                foreach ($listenersCountryWise as $key => $country) {
                    if ($country['location'] == $location->country && $included == false) {
                        $listenersCountryWise[$key]['count'] = $country['count'] + 1;
                        $included = true;
                    }
                }
                if ($included == false) {
                    $newArray = [
                        'location' => $location->country,
                        'count' => 1
                    ];
                    array_push($listenersCountryWise, $newArray);
                }

                $included = false;
                $agent = explode(" ", $listener->USERAGENT);
                foreach ($listenersUserAgentWise as $key => $userAgent) {
                    if ($userAgent['userAgent'] == $agent[0] && $included == false) {
                        $listenersUserAgentWise[$key]['count'] = $userAgent['count'] + 1;
                        $included = true;
                    }
                }
                if ($included == false) {
                    $newArray = [
                        'userAgent' => $agent[0],
                        'count' => 1
                    ];
                    array_push($listenersUserAgentWise, $newArray);
                }
            }
        }

        usort($listenersCountryWise, function ($object1, $object2) {
            return $object1['count'] < $object2['count'];
        });
        usort($listenersUserAgentWise, function ($object1, $object2) {
            return $object1['count'] < $object2['count'];
        });


        return response()->json([
            'listenersUserAgentWise' => $listenersUserAgentWise,
            'listenersCountryWise' => $listenersCountryWise,
            'tunedListeners' => $tunedListeners
        ]);
    }

    public function StatisticsCountries(Request $request)
    {
        $request->validate([
            'account_id' => 'required'
        ]);
        $subDays = $request->days ? $request->days : 14;
        $account_id = $request->account_id ? $request->account_id : null;
        $subDaysTime = Carbon::today()->subDays($subDays);

        $startDate = null;
        $endDate = null;
        if (isset($request->from_date) && $request->from_date != null && isset($request->to_date) && $request->to_date != null) {
            $startDate = Carbon::createFromFormat('Y-m-d', $request->from_date)->startOfDay();
            $endDate = Carbon::createFromFormat('Y-m-d', $request->to_date)->endOfDay();
        }

        if ($startDate && $endDate) {
            $countriesStatsBySession = VisitorStatsSessions::where(['accountid' => $account_id])->whereBetween('starttime', [$startDate, $endDate])->groupBy('country')->select('country', DB::raw('count(*) as total'))->orderBy('total', 'DESC')->get();
            $countriesStatsByMinutes = VisitorStatsSessions::where(['accountid' => $account_id])->whereBetween('starttime', [$startDate, $endDate])->groupBy('country')->select('country', DB::raw('sum(duration) as total'))->orderBy('total', 'DESC')->get();
        } else {
            $countriesStatsBySession = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->groupBy('country')->select('country', DB::raw('count(*) as total'))->orderBy('total', 'DESC')->get();
            $countriesStatsByMinutes = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->groupBy('country')->select('country', DB::raw('sum(duration) as total'))->orderBy('total', 'DESC')->get();
        }
        return response()->json(['countriesStatsByMinutes' => $countriesStatsByMinutes, 'countriesStatsBySession' => $countriesStatsBySession]);
    }

    public function StatisticsTracks(Request $request)
    {
        $request->validate([
            'account_id' => 'required'
        ]);
        $subDays = $request->days ? $request->days : 14;
        $account_id = $request->account_id ? $request->account_id : null;
        $subDaysTime = Carbon::today()->subDays($subDays);

        $startDate = null;
        $endDate = null;
        if (isset($request->from_date) && $request->from_date != null && isset($request->to_date) && $request->to_date != null) {
            $startDate = Carbon::createFromFormat('Y-m-d', $request->from_date)->startOfDay();
            $endDate = Carbon::createFromFormat('Y-m-d', $request->to_date)->endOfDay();
        }

        $playbackStats = array();

        if ($startDate && $endDate) {
            DB::table('playbackstats_tracks')->whereBetween('starttime', [$startDate, $endDate])->where(['accountid' => $account_id])->groupBy('accountid')->orderBy('listeners', 'DESC')->orderBy('duration', 'DESC')->select('name', 'starttime', 'listeners', DB::raw('count(*) as total_tracks'), DB::raw('sum(duration) as total_duration'))->get();
        } else {
            $playbackStats = DB::table('playbackstats_tracks')->where('starttime', '>=', $subDaysTime)->where(['accountid' => $account_id])->groupBy('accountid')->orderBy('listeners', 'DESC')->orderBy('duration', 'DESC')->select('name', 'starttime', 'listeners', DB::raw('count(*) as total_tracks'), DB::raw('sum(duration) as total_duration'))->get();
        }

        if (isset($playbackStats[0]->total_tracks)) {
            $total_tracks = $playbackStats[0]->total_tracks;
        } else {
            $total_tracks = 0;
        }
        $average_length = 0;
        $peak_listeners = 0;
        $peak_track = null;
        $peak_time = null;
        if ($total_tracks > 0) {
            $average_length = round($playbackStats[0]->total_duration / $total_tracks);
            $peak_listeners = $playbackStats[0]->listeners;
            $peak_track = $playbackStats[0]->name;
            $peak_time = $playbackStats[0]->starttime;
        }

        $user_Tracks = Track::where(['accountid' => $account_id])->get();
        $unique_tracks = count($user_Tracks);

        if ($startDate && $endDate) {
            $topTracksByPlayback = DB::table('playbackstats_tracks')->whereBetween('starttime', [$startDate, $endDate])->where(['accountid' => $account_id])->groupBy('name')->select('name', DB::raw('count(*) as playbacks'))->orderBy('playbacks', 'DESC')->limit(10)->get();
            $topTracksByAirTime = DB::table('playbackstats_tracks')->whereBetween('starttime', [$startDate, $endDate])->where(['accountid' => $account_id])->groupBy('name')->select('name', DB::raw('sum(duration) as totalDuration'))->orderBy('totalDuration', 'DESC')->limit(10)->get();
        } else {
            $topTracksByPlayback = DB::table('playbackstats_tracks')->where('starttime', '>=', $subDaysTime)->where(['accountid' => $account_id])->groupBy('name')->select('name', DB::raw('count(*) as playbacks'))->orderBy('playbacks', 'DESC')->limit(10)->get();
            $topTracksByAirTime = DB::table('playbackstats_tracks')->where('starttime', '>=', $subDaysTime)->where(['accountid' => $account_id])->groupBy('name')->select('name', DB::raw('sum(duration) as totalDuration'))->orderBy('totalDuration', 'DESC')->limit(10)->get();
        }
        return response()->json(['topTracksByAirTime' => $topTracksByAirTime, 'topTracksByPlayback' => $topTracksByPlayback, 'total_tracks' => $total_tracks, 'unique_tracks' => $unique_tracks, 'average_length' => $average_length, 'topTracksByAirTime' => $topTracksByAirTime, 'peak_listeners' => $peak_listeners, 'peak_track' => $peak_track, 'peak_time' => $peak_time]);
    }

    public function  StatisticsUserAgents(Request $request)
    {
        $request->validate([
            'account_id' => 'required'
        ]);
        $subDays = $request->days ? $request->days : 14;
        $account_id = $request->account_id ? $request->account_id : null;
        $subDaysTime = Carbon::today()->subDays($subDays);

        $startDate = null;
        $endDate = null;
        if (isset($request->from_date) && $request->from_date != null && isset($request->to_date) && $request->to_date != null) {
            $startDate = Carbon::createFromFormat('Y-m-d', $request->from_date)->startOfDay();
            $endDate = Carbon::createFromFormat('Y-m-d', $request->to_date)->endOfDay();
        }

        if ($startDate && $endDate) {
            $userAgentsBySessions = VisitorStatsSessions::where(['accountid' => $account_id])->whereBetween('starttime', [$startDate, $endDate])->groupBy('useragentid')->with('userAgents')->select('useragentid', DB::raw('count(*) as total'),  DB::raw('sum(bandwidth) as bandwidth'))->orderBy('total', 'DESC')->limit(10)->get();
            $userAgentsByMinutes = VisitorStatsSessions::where(['accountid' => $account_id])->whereBetween('starttime', [$startDate, $endDate])->groupBy('useragentid')->with('userAgents')->select('useragentid', DB::raw('sum(duration) as seconds'),  DB::raw('sum(bandwidth) as bandwidth'))->orderBy('seconds', 'DESC')->limit(10)->get();
        } else {
            $userAgentsBySessions = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->groupBy('useragentid')->with('userAgents')->select('useragentid', DB::raw('count(*) as total'),  DB::raw('sum(bandwidth) as bandwidth'))->orderBy('total', 'DESC')->limit(10)->get();
            $userAgentsByMinutes = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->groupBy('useragentid')->with('userAgents')->select('useragentid', DB::raw('sum(duration) as seconds'),  DB::raw('sum(bandwidth) as bandwidth'))->orderBy('seconds', 'DESC')->limit(10)->get();
        }

        return response()->json(['userAgentsBySessions' => $userAgentsBySessions, 'userAgentsByMinutes' => $userAgentsByMinutes]);
    }

    public function StatisticsHistorical(Request $request)
    {
        $request->validate([
            'account_id' => 'required'
        ]);

        $subDays = $request->days ? $request->days : 14;
        $account_id = $request->account_id ? $request->account_id : null;
        $period_from = $subDaysTime = Carbon::today()->subDays($subDays);
        $period_to = Carbon::today();

        $startDate = null;
        $endDate = null;
        if (isset($request->from_date) && $request->from_date != null && isset($request->to_date) && $request->to_date != null) {
            $period_from = $startDate = Carbon::createFromFormat('Y-m-d', $request->from_date)->startOfDay();
            $period_to = $endDate = Carbon::createFromFormat('Y-m-d', $request->to_date)->endOfDay();
        }

        if ($startDate && $endDate) {
            $peakListeners = VisitorStatsSessions::where(['accountid' => $account_id])->whereBetween('starttime', [$startDate, $endDate])->groupBy(DB::raw('Date(starttime)'))->select('starttime', DB::raw('count(*) as totalSessions'), DB::raw('sum(duration) as totalDuration'), DB::raw('sum(bandwidth) as totalBandwidth'))->orderBy('starttime', 'ASC')->get()->map(function ($expense) {
                return [
                    'created_at' => date("d/m", strtotime($expense->starttime)),
                    'totalDuration' => $expense->totalDuration,
                    'totalSessions' => $expense->totalSessions,
                    'totalBandwidth' => $expense->totalBandwidth,
                    'starttime' => $expense->starttime
                ];
            })->toArray();
        } else {
            $peakListeners = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->groupBy(DB::raw('Date(starttime)'))->select('starttime', DB::raw('count(*) as totalSessions'), DB::raw('sum(duration) as totalDuration'), DB::raw('sum(bandwidth) as totalBandwidth'))->orderBy('starttime', 'ASC')->get()->map(function ($expense) {
                return [
                    'created_at' => date("d/m", strtotime($expense->starttime)),
                    'totalDuration' => $expense->totalDuration,
                    'totalSessions' => $expense->totalSessions,
                    'totalBandwidth' => $expense->totalBandwidth,
                    'starttime' => $expense->starttime
                ];
            })->toArray();
        }

        $unique = array_unique($peakListeners, SORT_REGULAR);
        return response()->json(['peakListeners' => $peakListeners, 'unique' => $unique, 'subDaysTime' => $subDaysTime, 'period_from' => $period_from, 'period_to' => $period_to]);
    }
}
