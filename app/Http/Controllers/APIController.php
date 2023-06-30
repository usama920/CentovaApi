<?php

namespace App\Http\Controllers;

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

            $average_data_transfer = $total_data > 0 && $total_sessions > 0 ? format_size($total_data / $total_sessions) : format_size($total_data);
            $uniqueIpSessions = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->groupBy('ipaddress')->get();
            $uniqueCountrySessions = VisitorStatsSessions::where(['accountid' => $account_id])->where('starttime', '>=', $subDaysTime)->groupBy('country')->get();
            // return response()->json(count($uniqueCountrySessions));

            return response()->json(['total_minutes' => $total_minutes, 'total_hours' => $total_hours, 'total_sessions' => $total_sessions, 'uniqueIpSessions' => count($uniqueIpSessions), 'uniqueCountrySessions' => count($uniqueCountrySessions), 'total_data_transfer' => format_size($total_data), 'average_data_transfer' => $average_data_transfer]);
        } catch (Throwable $th) {
            return response()->json($th->getMessage());
        }
    }
}
