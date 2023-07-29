<?php

namespace App\Http\Controllers;

use App\Models\Track;
use App\Models\VisitorStatsSessions;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        set_time_limit(60000);
        $request->validate([
            'days' => 'required',
            'account_id' => 'required'
        ]);
        try {
            $subDays = $request->days ? $request->days : 14;
            $account_id = $request->account_id ? $request->account_id : 163;
            $subDaysTime = Carbon::today()->subDays($subDays);

            $topVisitorsBySessions = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->with('userAgents')->select('ipaddress', 'country', DB::raw('count(*) as totalSessions'), DB::raw('sum(bandwidth) as totalBandwidth'))->orderBy('totalSessions', 'DESC')->groupBy('ipaddress')->limit(10)->get();
            foreach ($topVisitorsBySessions as $key => $session) {
                $topVisitorsBySessions[$key]['ip'] = long2ip($session->ipaddress);
                $topVisitorsBySessions[$key]['totalFormattedbandwidth'] = format_size($session->totalBandwidth);
            }

            $topVisitorsByMinutes = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->with('userAgents')->select('ipaddress', 'country', DB::raw('sum(bandwidth) as totalBandwidth'),  DB::raw('sum(duration) as totalDuration'))->orderBy('totalDuration', 'DESC')->groupBy('ipaddress')->limit(10)->get();
            foreach ($topVisitorsByMinutes as $key => $session) {
                $topVisitorsByMinutes[$key]['ip'] = long2ip($session->ipaddress);
                $topVisitorsByMinutes[$key]['totalDurationInMinutes'] = round($session->totalDuration / 60);
                $topVisitorsByMinutes[$key]['totalFormattedbandwidth'] = format_size($session->totalBandwidth);
            }

            $visitorSessions = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->get();
            $total_seconds = 0;
            $total_data = 0;

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
            $uniqueIpSessions = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->groupBy('ipaddress')->get();
            $uniqueCountrySessions = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->groupBy('country')->get();


            return response()->json(['total_minutes' => $total_minutes, 'total_hours' => $total_hours, 'total_sessions' => $total_sessions, 'average_session_time' => $average_session_time, 'uniqueIpSessions' => count($uniqueIpSessions), 'uniqueCountrySessions' => count($uniqueCountrySessions), 'total_data_transfer' => format_size($total_data), 'average_data_transfer' => $average_data_transfer, 'session_length_intervals' => $session_length_intervals, 'topVisitorsBySessions' => $topVisitorsBySessions, 'topVisitorsByMinutes' => $topVisitorsByMinutes]);
        } catch (Throwable $th) {
            return response()->json($th->getMessage());
        }
    }

    public function StatisticsCountries(Request $request)
    {
        $subDays = $request->days ? $request->days : 14;
        $account_id = $request->account_id ? $request->account_id : 163;
        $subDaysTime = Carbon::today()->subDays($subDays);
        $countriesStatsBySession = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->groupBy('country')->select('country', DB::raw('count(*) as total'))->orderBy('total', 'DESC')->get();
        $countriesStatsByMinutes = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->groupBy('country')->select('country', DB::raw('sum(duration) as total'))->orderBy('total', 'DESC')->get();
        return response()->json(['countriesStatsByMinutes' => $countriesStatsByMinutes, 'countriesStatsBySession' => $countriesStatsBySession]);
    }

    public function StatisticsTracks(Request $request)
    {
        $request->validate([
            'days' => 'required',
            'account_id' => 'required'
        ]);
        $subDays = $request->days ? $request->days : 14;
        $account_id = $request->account_id ? $request->account_id : 163;
        $subDaysTime = Carbon::today()->subDays($subDays);

        $tracks = Track::where(['accountid' => $account_id])->count();
        return response()->json(['unique_tracks' => $tracks]);
    }

    public function  StatisticsUserAgents(Request $request)
    {
        $request->validate([
            'days' => 'required',
            'account_id' => 'required'
        ]);
        $subDays = $request->days ? $request->days : 14;
        $account_id = $request->account_id ? $request->account_id : 163;
        $subDaysTime = Carbon::today()->subDays($subDays);

        $userAgentsBySessions = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->groupBy('useragentid')->with('userAgents')->select('useragentid', DB::raw('count(*) as total'),  DB::raw('sum(bandwidth) as bandwidth'))->orderBy('total', 'DESC')->limit(10)->get();
        $userAgentsByMinutes = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->groupBy('useragentid')->with('userAgents')->select('useragentid', DB::raw('sum(duration) as seconds'),  DB::raw('sum(bandwidth) as bandwidth'))->orderBy('seconds', 'DESC')->limit(10)->get();
        return response()->json(['userAgentsBySessions' => $userAgentsBySessions, 'userAgentsByMinutes' => $userAgentsByMinutes]);
    }

    public function StatisticsHistorical(Request $request)
    {
        $request->validate([
            'days' => 'required',
            'account_id' => 'required'
        ]);
        $subDays = $request->days ? $request->days : 14;
        $account_id = $request->account_id ? $request->account_id : 163;
        $subDaysTime = Carbon::today()->subDays($subDays);

        $userAgentsBySessions = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->groupBy('useragentid')->with('userAgents')->select('useragentid', DB::raw('count(*) as total'),  DB::raw('sum(bandwidth) as bandwidth'))->orderBy('total', 'DESC')->limit(10)->get();
        $userAgentsByMinutes = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->groupBy('useragentid')->with('userAgents')->select('useragentid', DB::raw('sum(duration) as seconds'),  DB::raw('sum(bandwidth) as bandwidth'))->orderBy('seconds', 'DESC')->limit(10)->get();
        return response()->json(['userAgentsBySessions' => $userAgentsBySessions, 'userAgentsByMinutes' => $userAgentsByMinutes]);
    }
}
