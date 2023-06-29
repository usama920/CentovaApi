<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function TryLogin(Request $request)
    {
        return response()->json([
            'status' => 'error', 'messsage' => 'Invalid Credentials!'
        ]);
        $username = $request->username;
        $password = $request->password;
        $account = DB::table('account')->where(['username' => $username])->first();
        if ($account) {
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
        } else {
            return response()->json([
                'status' => 'error', 'messsage' => 'Invalid Credentials!'
            ]);
        }
    }
}
