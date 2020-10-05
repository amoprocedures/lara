<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UsersController extends API
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth:api');
    }

    public function get(Request $request)
    {
        $id = $request->get('id');
        $search = $request->get('search');

        $data = DB::table('dim_user')->select('*')->get();
        if (!empty($id)) {
            $query = DB::table('dim_user')->where('id', $id)->select('*')->get()->first();
            $data = json_decode(json_encode($query), true);
            if (!empty($data)) {
                unset($data['password']);
            }
            return response()->json(self::make_response(true, 'OK', null, $data));
        }
        if (!empty($search)) {
            $query = DB::table('dim_user')
                ->where('id', 'like', "%$search%")
                ->orWhere('first_name', 'like', "%$search%")
                ->orWhere('last_name', 'like', "%$search%")
                ->orWhere('user_name', 'like', "%$search%")
                ->orWhere('role', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")->get();

            return response()->json(self::make_response(true, 'OK', null, $query));
        }

        return response()->json(self::make_response(true, 'OK', null, $data));
    }

    public function put(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required',
            'values' => 'required',
        ]);

        if ($validator->fails()) {
            $data = self::make_response(false, 'Oops', [$validator->errors()->first()]);
            return response()->json($data);
        }

        $key = $request->get('key');
        $new_data = json_decode($request->get('values'), true);

        DB::table('dim_user')->where('id', $key)->update($new_data);
        $query = DB::table('dim_user')->where('id', $key)->select('*')->get()->first();
        $user = json_decode(json_encode($query), true);
        unset($user['password']);

        if ($request->hasFile('photo_url')) {
            $allowedFileExtensions = ['jpeg', 'png', 'jpg'];
            $file = $request->file('photo_url');

            $extension = $file->getClientOriginalExtension();
            $validated = in_array($extension, $allowedFileExtensions);

            if (!$validated) {
                return response()->json(self::make_response(false, 'Image Extension', ['Allowed images are [.jpg, .jpeg, .png]']));
            } else {
                $file_name = $user['id'] . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('images/profiles'), $file_name);
                $profile_data = ['photo_url' => 'images/profiles/' . $file_name];
                DB::table('dim_user')->where('id', $key)->update($profile_data);

                $query = DB::table('dim_user')->where('id', $key)->select('*')->get()->first();
                $user = json_decode(json_encode($query), true);
                unset($user['password']);
            }
        }

        $response = self::make_response(true, 'OK', null, $user);
        return response()->json($response);

    }

    public function statistics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_type' => 'required|in:farmer,user,admin,super',
            'user_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            $data = self::make_response(false, 'Oops', [$validator->errors()->first()]);
            return response()->json($data);
        }

        $output = [];

        $user_type = $request->get('user_type');
        $user_id = $request->get('user_id');

        $status = DB::select("select if(is_active=0,'inactive_users','active_users') as name ,count(*) as value  from dim_user group by is_active;");

        $products = DB::select("select if(is_verified=0,'not_verified','verified') as name ,count(*) as value  from user_product where user_id='$user_id' group by is_verified;");

        $roles = DB::select("select role as name ,count(*) as value  from dim_user group by role;");

        if (in_array($user_type, ['admin', 'super'])) {
            $products = DB::select("select if(is_verified=0,'not_verified','verified') as name ,count(*) as value  from user_product group by is_verified;");
            $orders = DB::select("select if(is_delivered=0,'not_delivered','delivered') as name ,count(*) as value  from dim_order  group by is_delivered;");
            $statistics = DB::select("select date_format(date_created,'%b') as label , count(*) as data,month(date_created) as month_ from dim_order group by month_ order by date_created asc;");


            $output['status'] = $status;
            $output['roles'] = $roles;

            $order_data = ['labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'], 'data' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]];

            foreach ($statistics as $stat) {
                $position = array_search($stat->label, $order_data['labels']);
                $order_data['data'][$position] = (int)$stat->data;
            }
            $output['order_statistics'] = $order_data;
            $output['orders'] = $orders;

        }

        $output['products'] = $products;
        return response()->json(self::make_response(true, 'OK', null, $output));
    }
}
