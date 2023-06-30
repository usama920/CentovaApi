<?php

namespace App\Http\Controllers;

use App\Models\VisitorStatsSessions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $subDays = $request->days ? $request->days : 14;
        $account_id = $request->account_id ? $request->account_id : null;
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


        $uniqueIpSessions = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->groupBy('ipaddress')->count();

        $uniqueCountrySessions = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->groupBy('country')->count();

        return response()->json(['total_minutes' => $total_minutes, 'total_hours' => $total_hours, 'total_sessions' => $total_sessions, 'uniqueIpSessions' => $uniqueIpSessions, 'uniqueCountrySessions' => $uniqueCountrySessions, 'total_data_transfer' => format_size($total_data), 'average_data_transfer' => format_size($total_data / $total_sessions)]);
    }
}
