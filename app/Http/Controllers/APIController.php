<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class APIController extends Controller
{
    public function Report()
    {
        $account = DB::table('accounts')->first();
        dd($account);
    }
}
