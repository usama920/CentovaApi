<?php

use App\Http\Controllers\DjautoController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\WidgetController;
use App\Models\Track;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::post('/statistics/tracks', function (Request $request) {
    set_time_limit(60000);

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
    $total_duration = 0;

    if ($startDate && $endDate) {
        DB::table('playbackstats_tracks')->whereBetween('starttime', [$startDate, $endDate])->where(['accountid' => $account_id])->orderBy('listeners', 'DESC')->orderBy('duration', 'DESC')->get();
    } else {
        // DB::table('playbackstats_tracks')->where('starttime', '>=', $subDaysTime)->where(['accountid' => $account_id])->orderBy('listeners', 'DESC')->orderBy('duration', 'DESC')->chunk(1000, function ($stats) use (&$playbackStats, &$total_duration) {
        //     // $playbackStats = [];
        //     foreach ($stats as $stat) {
        //         $total_duration += $stat->duration;
        //         $playbackStats[] = $stat;
        //     }
        // });

        $playbackStats = DB::table('playbackstats_tracks')->where('starttime', '>=', $subDaysTime)->where(['accountid' => $account_id])->groupBy('accountid')->orderBy('listeners', 'DESC')->orderBy('duration', 'DESC')->select('name', 'starttime', 'listeners', DB::raw('count(*) as total_tracks'), DB::raw('sum(duration) as total_duration'))->get();
    }

    // return response()->json(['test' => $test]);


    $total_tracks = $playbackStats[0]->total_tracks;
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
});

Route::post('/trylogin', [LoginController::class, 'TryLogin']);

Route::get('/reports', [StatisticsController::class, 'Report']);
Route::post('/statistics/listeners', [StatisticsController::class, 'StatisticsListeners']);
Route::post('/statistics/liveListeners', [StatisticsController::class, 'StatisticsLiveListeners']);
Route::post('/statistics/countries', [StatisticsController::class, 'StatisticsCountries']);
// Route::post('/statistics/tracks', [StatisticsController::class, 'StatisticsTracks']);
Route::post('/statistics/userAgents', [StatisticsController::class, 'StatisticsUserAgents']);
Route::post('/statistics/historical', [StatisticsController::class, 'StatisticsHistorical']);

Route::post('/djauto/playlists', [DjautoController::class, 'Playlists']);


Route::post('/widgets/update/recent_tracks', [WidgetController::class, 'UpdateRecentTracks']);
Route::post('/widgets/update/song_requests', [WidgetController::class, 'UpdateSongRequests']);
