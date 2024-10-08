<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function Test()
    {
        prx('Running');
    }
    public function TryLogin(Request $request)
    {
        $request->validate([
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
                    'status' => 'success',
                    'username' => $account->username,
                    'account_id' => $account->id,
                    'email' => $account->email,
                    'version' => ($account->version == 'advanced' ? 'advanced' : 'basic'),
                    'account_type' => 'userAccount',
                    'servertype' => $account->servertype
                ]);
            } else {
                return response()->json([
                    'status' => 'error', 'messsage' => 'Invalid Credentials!'
                ]);
            }
        } else {
            $dj = DB::table('djaccounts')->where(['username' => $username, 'status' => 'enabled'])->first();
            if ($dj) {
                $checkPassword = Hash::check($password, $dj->password);
                if ($checkPassword) {
                    $login_weekdays = explode(',', $dj->login_weekdays);
                    $todayDay = strtolower(Carbon::now()->format('D'));
                    if (in_array($todayDay, $login_weekdays)) {
                        $startTime = Carbon::createFromTimeString($dj->login_starttime);
                        $endTime = Carbon::createFromTimeString($dj->login_endtime);
                        $endTime->subHours(12);
                        $endTime = $endTime->format('H:i');
                        $currentTime = Carbon::now();
                        $startTime->subHours(12);
                        $startTime = $startTime->format('H:i');

                        if ($currentTime->between($startTime, $endTime) || ($dj->login_starttime == '00:00:00' && $dj->login_endtime == '00:00:00')) {
                            // return response()->json([$dj]);
                            $account = DB::table('accounts')->where(['id' => $dj->accountid])->first();
                            return response()->json([
                                'status' => 'success',
                                'username' => $account->username,
                                'account_id' => $account->id,
                                'email' => $account->email,
                                'version' => 'advanced',
                                'account_type' => 'djAccount',
                                'permissions' => $dj->permissions,
                                'servertype' => $account->servertype
                            ]);
                        } else {
                            return response()->json([
                                'status' => 'error', 'messsage' => 'Access Denied!'
                            ]);
                        }
                    } else {
                        return response()->json([
                            'status' => 'error', 'messsage' => 'Access Denied!'
                        ]);
                    }
                } else {
                    return response()->json([
                        'status' => 'error', 'messsage' => 'Invalid Credentials!'
                    ]);
                }
            }
            return response()->json([
                'status' => 'error', 'messsage' => 'Invalid Credentials!'
            ]);
        }
    }

    public function GetAccount(Request $request)
    {
        if (isset($request->type) && $request->type == "fromAppwriteId") {
            $account = DB::table('accounts')->where(['appwrite_id' => $request->appwrite_id])->first();
            if ($account) {
                return response()->json([
                    'status' => 'success', 'appwrite_id' => $account->appwrite_id, 'hostname' => $account->hostname, 'username' => $account->username
                ]);
            } else {
                return response()->json([
                    'status' => 'error'
                ]);
            }
        }
        $request->validate([
            'account_id' => 'required',
            'username' => 'required',
            'password' => 'required'
        ]);
        $username = $request->username;
        $password = $request->password;
        $account = DB::table('accounts')->where(['username' => $username])->first();
        if ($account) {
            if (isset($request->type) && $request->type == "fromAppwriteId") {
                return response()->json([
                    'status' => 'success', 'appwrite_id' => $account->appwrite_id, 'hostname' => $account->hostname, 'port' => $account->port, 'title' => $account->title
                ]);
            }
            $checkPassword = Hash::check($password, $account->password);
            if ($checkPassword) {
                return response()->json([
                    'status' => 'success', 'appwrite_id' => $account->appwrite_id, 'hostname' => $account->hostname, 'port' => $account->port, 'title' => $account->title
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

    public function AllAppUsers()
    {
        $accounts = DB::table('accounts')->where('appwrite_id', '!=', null)->get();
        $users = [];
        foreach ($accounts as $key => $account) {
            array_push($users, ['username' => $account->username, 'appwrite_id' => $account->appwrite_id]);
        }
        return response()->json($users);
    }

    public function ForgotPasswordCode(Request $request)
    {
        $request->validate([
            'email' => 'required',
        ]);
        $account = Account::where(['email' => $request->email])->first();
        if ($account) {
            $code = rand(111111, 999999);
            $account->forgot_password_code = $code;
            $account->forgot_password_status = 0;
            $account->forgot_password_time = date('Y-m-d H:i:s');
            $account->save();
            return response()->json([
                'status' => 'success', 'username' => $account->username, 'code' => $code, 'message' => 'success'
            ]);
        } else {
            return response()->json([
                'status' => 'error', 'messsage' => 'Email does not exist!'
            ]);
        }
    }

    public function ForgotPasswordVerifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'code' => 'required'
        ]);
        $account = Account::where(['email' => $request->email, 'forgot_password_code' => $request->code, 'forgot_password_status' => 0])->where("forgot_password_time", ">", Carbon::now()->subDay())->first();
        if ($account) {
            return response()->json([
                'status' => 'success', 'message' => 'success'
            ]);
        } else {
            return response()->json([
                'status' => 'error', 'messsage' => 'Wrong verification code!'
            ]);
        }
    }

    public function ForgotPasswordSave(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
            'code' => 'required'
        ]);
        $account = Account::where(['email' => $request->email, 'forgot_password_code' => $request->code, 'forgot_password_status' => 0])->where("forgot_password_time", ">", Carbon::now()->subDay())->first();
        if ($account) {
            $password = Hash::make($request->password);
            $account->password = $password;
            $account->forgot_password_status = 1;
            $account->save();
            return response()->json([
                'status' => 'success', 'message' => 'success'
            ]);
        } else {
            return response()->json([
                'status' => 'error', 'messsage' => 'Something went wrong!'
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
                DB::table('accounts')->where(['username' => $username])->update([
                    'appwrite_id' => $request->appwrite_id
                ]);
                return response()->json([
                    'status' => 'success'
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

    public function UpdateBannedCountries(Request $request)
    {
        $request->validate([
            'account_id' => 'required',
            'username' => 'required',
            'password' => 'required',
            'bannedCountries' => 'required',
            'server' => 'required'
        ]);

        $username = $request->username;
        $password = $request->password;

        $account = DB::table('accounts')->where(['username' => $username])->first();
        if ($account) {
            $checkPassword = Hash::check($password, $account->password);
            if ($checkPassword) {
                DB::table('accounts')->where(['username' => $username])->update([
                    'bannedCountries' => $request->bannedCountries
                ]);
                return response()->json([
                    'status' => 'success'
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

    public function GetBannedCountries($username)
    {
        $account = DB::table('accounts')->where(['username' => $username])->first();
        if ($account) {
            return response()->json([
                'bannedCountries' => $account->bannedCountries
            ]);
        } else {
            return response()->json([
                'status' => 'error', 'messsage' => 'Invalid Username!'
            ]);
        }
    }

    public function ChangePassword(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $username = $request->username;
        $password = $request->password;
        $account = DB::table('accounts')->where(['username' => $username])->first();
        if ($account) {
            $checkPassword = Hash::check($password, $account->password);
            if ($checkPassword) {
                $newPassword = Hash::make($request->password);
                DB::table('accounts')->where(['username' => $username])->update([
                    'password' => $newPassword
                ]);
                return response()->json([
                    'status' => 'success'
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

    public function UpdateVersion(Request $request)
    {
        $request->validate([
            'account_id' => 'required',
            'username' => 'required',
            'password' => 'required',
            'type' => 'required'
        ]);
        $username = $request->username;
        $password = $request->password;
        $account = DB::table('accounts')->where(['username' => $username])->first();
        if ($account) {
            $checkPassword = Hash::check($password, $account->password);
            if ($checkPassword) {
                DB::table('accounts')->where(['username' => $username])->update([
                    'version' => $request->type
                ]);
                return response()->json([
                    'status' => 'success'
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
