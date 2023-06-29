<?php

namespace App\Http\Controllers;

use App\Models\VisitorStatsSessions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function TryLogin()
    {
        $username = "";
        $password = "";
        $account = DB::table('account')->where(['username' => $username])->first();
        $checkPassword = Hash::check($password, $account->password);
        if ($checkPassword) {
            return response()->json([
                'status' => 'success', 'username' => $account->username
            ]);
        } else {
            return response()->json([
                'status' => 'error', 'messsage' => 'Invalid Credentials!'
            ]);
        }
    }
}
