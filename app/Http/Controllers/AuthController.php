<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;


class AuthController extends API
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth:api', ['except' => ['login', 'register', 'confirm', 'forgot']]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation Error', 'error' => [$validator->errors()->first()]]);
        }

        $token = auth()->attempt($validator->validated());

        if (!$token) {
            $email = $request->post('email');
            $password = $request->post('password');
            $token = auth()->attempt(['phone' => $email, 'password' => $password]);

            if (!$token) {
                $token = auth()->attempt(['user_name' => $email, 'password' => $password]);
                if (!$token) {
                    return response()->json(['status' => false, 'message' => 'Hello, you have provided wrong credentials', 'error' => ['Wrong credentials while attempting to login'], 'response' => null]);
                } else {
                    return $this->createNewToken($token);
                }
            } else {
                return $this->createNewToken($token);
            }
        }
        return $this->createNewToken($token);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|unique:dim_user',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone' => 'required|between:9,16|unique:dim_user',
            'user_name' => 'required|string|unique:dim_user',
            'role' => 'required|string|in:farmer,user,admin,super',
            'password' => 'required|string|between:8,16',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation Error', 'error' => [$validator->errors()->first()]], 422);
        }

        try {
            $uuid = self::UUID();
            $data = $validator->validated();
            $data['created_at'] = self::NOW();
            $data['id'] = $uuid;
            $data['password'] = Hash::make($data['password']);
            $data['is_active'] = 1;
            $data['photo_url'] = 'assets/images/profiles/no_image.png';

            $query = DB::table('dim_user')->insert($data);
            if ($query) {
//                if (!empty($data['email'])) {
//                    $secret_data = [
//                        'secret_code' => self::RANDOM_KEY(4, true),
//                        'secret_code_sent_at' => self::NOW()
//                    ];
//                    DB::table('dim_user')->where('id', $uuid)->update($secret_data);
//                    self::SEND_MAIL(['email' => $data['email'], 'title' => $data['first_name'] . ' ' . $data['last_name']], 'Account Activation', '<p>Your account activation code is : <span style="font-size: 20px;">' . $secret_data['secret_code'] . '</span></p>');
//                    return response()->json($this->make_response(true, 'OK', null, 'Registration Successful, activation code has been sent to the email!'));
//                }
                return response()->json($this->make_response(true, 'OK', null, 'Account has been created successfully!'));
            }
            return response()->json($this->make_response(false, 'Oops', 'Something went wrong!', null));
        } catch (ValidationException $e) {
            return response()->json($this->make_response(false, 'Oops', 'Something went wrong!', null));
        }
    }

    public function confirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'secret_code' => 'required|digits:4'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation Error', 'error' => [$validator->errors()->first()]]);
        }

        $data = $validator->validated();

        $found_user = DB::table('dim_user')->where('email', $data['email'])->get()->first();

        if (empty($found_user)) {
            return response()->json(['status' => false, 'message' => 'This account is not registered on the system', 'error' => ["Hello, this account is not registered on the system !"]]);
        }

        if ($found_user->secret_code != $data['secret_code']) {
            return response()->json($this->make_response(false, 'Invalid code has been detected!', ['Invalid code has been detected, try again !']));
        }

        $new_data = [
            'secret_code' => null,
            'secret_code_sent_at' => null,
            'email_verified' => true,
            'email_verified_at' => self::NOW(),
            'is_active' => true,
            'updated_at' => self::NOW()
        ];

        if (array_key_exists('password', $data)) {
            $new_data['password'] = Hash::make($data['password']);
        }

        $query = DB::table('dim_user')->where('id', $found_user->id)->update($new_data);
        if ($query) {
            $user = DB::table('dim_user')
                ->where('id', $found_user->id)
                ->select('id', 'first_name', 'last_name', 'user_name', 'phone', 'email', 'email_verified', 'phone_verified', 'email_verified_at', 'phone_verified_at', 'secret_code', 'secret_code_sent_at', 'created_at', 'updated_at', 'role', 'photo_url', 'is_active')
                ->get()->first();
            return response()->json($this->make_response(true, 'Successful, Your account is now active', null, $user));
        }
        return response()->json($this->make_response(false, 'Oops, Something went wrong !', ['Oops, Something went wrong!']));
    }

    public function forgot(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_type' => 'required|string|in:find_account,update_password,send_code'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation Error', 'error' => [$validator->errors()->first()]]);
        }

        $type = $request->post('request_type');

        if ($type == 'find_account') {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => 'Validation Error', 'error' => [$validator->errors()->first()]]);
            }

            $identity = $request->post('email');

            $found_user = DB::table('dim_user')
                ->where('email', $identity)
                ->orWhere('phone', $identity)
                ->orWhere('user_name', $identity)
                ->get()
                ->first();

            if (empty($found_user)) {
                return response()->json($this->make_response(false, 'System search result', ['Account not found for this identity, try again!']));
            }

            return response()->json($this->make_response(true, 'OK', null, $found_user));
        }

        if ($type == 'update_password') {
            $validator = Validator::make($request->all(), [
                'id' => 'required|string|exists:dim_user,id',
                'password' => 'required|string|between:8,16',
                'secret_code' => 'required|digits:4'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => 'Validation Error', 'error' => [$validator->errors()->first()]]);
            }

            $data = $validator->validated();

            $new_credentials = [
                'password' => Hash::make($data['password']),
                'secret_code' => null,
                'secret_code_sent_at' => null,
                'updated_at' => self::NOW(),
                'email_verified' => true
            ];

            $credentials = [
                'id' => $data['id'],
                'secret_code' => $data['secret_code']
            ];

            $found_user = DB::table('dim_user')->where($credentials)->get()->first();

            if (empty($found_user)) {
                return response()->json(['status' => false, 'message' => 'Invalid Credentials', 'error' => ['Invalid code has been detected!']]);
            }

            $user_updated = DB::table('dim_user')->where('id', $found_user->id)->update($new_credentials);

            if ($user_updated) {
                $user = DB::table('dim_user')->where('id', $found_user->id)->get()->first();
                return response()->json(['status' => true, 'message' => 'Account recovered successfully!', null, $user]);
            }
            return response()->json(['status' => false, 'message' => 'Oops', 'error' => ['Something went wrong while updating please try again !']]);
        }

        if ($type == 'send_code') {
            $validator = Validator::make($request->all(), [
                'id' => 'required|string|exists:dim_user,id',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => 'Validation Error', 'error' => [$validator->errors()->first()]]);
            }

            $found_user = DB::table('dim_user')->where('id')->get()->first();
            if (empty($found_user)) {
                return response()->json(['status' => false, 'message' => 'Account not found!', 'error' => ['This account is not existing on the system!']]);
            }
            if (!empty($found_user->email)) {
                $secret_data = [
                    'secret_code' => self::RANDOM_KEY(4, true),
                    'secret_code_sent_at' => self::NOW()
                ];
                DB::table('dim_user')->where('id', $found_user->id)->update($secret_data);
                self::SEND_MAIL(['email' => $found_user->email, 'title' => $found_user->first_name . ' ' . $found_user->last_name], 'Account Recovery', '<p>Your account recovery code is : <span style="font-size: 20px;">' . $secret_data['secret_code'] . '</span></p>');
                return response()->json($this->make_response(true, 'OK', null, 'Successful, activation code has been sent to the email inbox!'));
            }
            return response()->json($this->make_response(false, 'Email not found', ['Email address is not present for account']));
        }

        return response()->json(['status' => false, 'message' => 'Oops', 'error' => ['this request seems to be malicious']]);
    }

    public function refresh()
    {
        return $this->createNewToken(auth()->refresh());
    }

    protected function createNewToken($token)
    {
        return response()->json([
            'status' => true,
            'message' => 'Successful, you are now logged in !',
            'error' => null,
            'response' => ['token' => $token, 'user' => auth()->user(), 'headers' => 'Authorization: Bearer _place_your_stored_token_here_']
        ]);
    }


}
