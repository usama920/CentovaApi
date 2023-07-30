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

class APIController extends Controller
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

        if ($startDate && $endDate) {
            $playbackStats = DB::table('playbackstats_tracks')->whereBetween('starttime', [$startDate, $endDate])->where(['accountid' => $account_id])->orderBy('listeners', 'DESC')->orderBy('duration', 'DESC')->get();
        } else {
            $playbackStats = DB::table('playbackstats_tracks')->where('starttime', '>=', $subDaysTime)->where(['accountid' => $account_id])->orderBy('listeners', 'DESC')->orderBy('duration', 'DESC')->get();
        }

        $total_tracks = count($playbackStats);
        $total_duration = 0;
        $average_length = 0;
        $peak_listeners = 0;
        $peak_track = null;
        $peak_time = null;
        if ($total_tracks > 0) {
            $total_duration = $playbackStats->sum('duration');
            $average_length = round($total_duration / $total_tracks);
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


        // $test = DB::table('playbackstats_tracks')->where('starttime', '>=', $subDaysTime)->where(['accountid' => $account_id, 'name' => 'Rock Solid Radio - Promo VA RSR 4'])->get();
        // $stats['totalDuration'] = $test->sum('duration');
        // $stats['count'] = count($test);
        // $playlists = Playlists::where(['accountid' => $account_id])->with('playlistTracks')->get()->toArray();
        // $tracks = 0;
        // foreach ($playlists as $playlist) {
        //     if (isset($playlist['playlist_tracks']) && $playlist['playlist_tracks'] != null) {
        //         $tracks += count($playlist['playlist_tracks']);
        //     }
        // }
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
