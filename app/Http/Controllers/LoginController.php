<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function TryLogin(Request $request)
    {
        $username = $request->username;
        $password = $request->password;
        $account = DB::table('accounts')->where(['username' => $username])->first();
        if ($account) {
            $checkPassword = Hash::check($password, $account->password);
            if ($checkPassword) {
                return response()->json([
                    'status' => 'success', 'username' => $account->username, 'account_id' => $account->id
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

    public function GetAccount(Request $request)
    {
        $request->validate([
            'account_id' => 'required',
            'username' => 'required',
            'password' => 'required'
        ]);
        $username = $request->username;
        $password = $request->password;
        $account = DB::table('accounts')->where(['username' => $username])->first();
        if ($account) {
            $checkPassword = Hash::check($password, $account->password);
            if ($checkPassword) {
                return response()->json([
                    'status' => 'success', 'appwrite_id' => $account->appwrite_id, 'hostname' => $account->hostname, 'port' => $account->port
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

    public function UpdateAccount(Request $request)
    {
        $request->validate([
            'account_id' => 'required',
            'username' => 'required',
            'password' => 'required',
            'type' => 'required'
        ]);
        if ($request->type == 'appwrite') {
            $request->validate([
                'appwrite_id' => 'required'
            ]);
        }
        $username = $request->username;
        $password = $request->password;
        $account = DB::table('accounts')->where(['username' => $username])->first();
        if ($account) {
            $checkPassword = Hash::check($password, $account->password);
            if ($checkPassword) {
                $account->appwrite_id = $request->appwrite_id;
                $account->save();
                return response()->json([
                    'status' => 'success', 'appwrite_id' => $account->appwrite_id, 'hostname' => $account->hostname, 'port' => $account->port
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
