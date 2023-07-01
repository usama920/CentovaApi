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
            $visitorSessions = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->get();
            $total_seconds = 0;
            $total_data = 0;

            foreach ($visitorSessions as $session) {
                $total_seconds += $session->duration;
                $total_data += $session->bandwidth;
            }
            $total_minutes = round($total_seconds / 60);
            $total_hours = round($total_minutes / 60, 1);
            $total_sessions = count($visitorSessions);
            $average_session_time = $total_seconds > 0 ? format_time($total_seconds, $total_sessions) : 0;
            $average_data_transfer = $total_data > 0 && $total_sessions > 0 ? format_size($total_data / $total_sessions) : format_size($total_data);
            $uniqueIpSessions = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->groupBy('ipaddress')->get();
            $uniqueCountrySessions = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->groupBy('country')->get();

            return response()->json(['total_minutes' => $total_minutes, 'total_hours' => $total_hours, 'total_sessions' => $total_sessions, 'average_session_time' => $average_session_time, 'uniqueIpSessions' => count($uniqueIpSessions), 'uniqueCountrySessions' => count($uniqueCountrySessions), 'total_data_transfer' => format_size($total_data), 'average_data_transfer' => $average_data_transfer]);
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
}
