<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrdersController extends API
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth:api');
    }

    public function get(Request $request)
    {
        $id = $request->get('id');
        $user_id = $request->get('user_id');
        $farmer_id = $request->get('farmer_id');

        if (!empty($user_id) && !empty($farmer_id)) {
            $query = DB::table('view_orders')->where(['user_id' => $user_id, 'farmer_id' => $farmer_id, 'is_deleted' => 0])->select('*')->get()->first();
            return response()->json(self::make_response(true, 'OK', null, $query));
        }

        if (!empty($user_id)) {
            $query = DB::table('view_orders')->where(['user_id' => $user_id, 'is_deleted' => 0])->select('*')->get()->first();
            return response()->json(self::make_response(true, 'OK', null, $query));
        }

        if (!empty($farmer_id)) {
            $query = DB::table('view_orders')->where(['farmer_id' => $farmer_id, 'is_deleted' => 0])->select('*')->get()->first();
            return response()->json(self::make_response(true, 'OK', null, $query));
        }

        $is_delivered = $request->get('is_delivered');
        $search = $request->get('search');
        $data = DB::table('view_orders')->where('is_deleted', 0)->get();

        if (is_numeric($is_delivered)) {
            $query = DB::table('view_orders')->where(['is_delivered' => $is_delivered, 'is_deleted' => 0])->select('*')->get();
            return response()->json(self::make_response(true, 'OK', null, $query));
        }

        if (!empty($id)) {
            $query = DB::table('view_orders')->where(['id' => $id, 'is_deleted' => 0])->select('*')->get()->first();
            return response()->json(self::make_response(true, 'OK', null, $query));
        }
        if (!empty($search)) {
            $query = DB::table('view_orders')
                ->where('code', 'like', "%$search%")
                ->orWhere('user_id', 'like', "%$search%")
                ->orWhere('id', 'like', "%$search%")
                ->orWhere('farmer_id', 'like', "%$search%")
                ->orWhere('farmer_name', 'like', "%$search%")
                ->orWhere('user_name', 'like', "%$search%")
                ->orWhere('user_phone', 'like', "%$search%")
                ->orWhere('user_email', 'like', "%$search%")
                ->orWhere('farmer_email', 'like', "%$search%")
                ->orWhere('farmer_phone', 'like', "%$search%")
                ->get();
            return response()->json(self::make_response(true, 'OK', null, $query));
        }
        return response()->json(self::make_response(true, 'OK', null, $data));
    }

    public function post(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string|exists:dim_user,id',
            'farmer_id' => 'required|string|exists:dim_user,id',
            'payment_method' => 'required|string',
            'delivery_location' => 'required|string',
            'items' => 'required|string'
        ]);

        if ($validator->fails()) {
            $data = self::make_response(false, 'Validation Error', [$validator->errors()->first()]);
            return response()->json($data);
        }

        $items = json_decode($request->post('items'), true);

        $item_errors = [];

        if (!is_array($items) || count($items) == 0) {
            return response()->json(self::make_response(false, 'Validation Error', ['Hello order details must be in a string array[]']));
        }

        foreach ($items as $item) {
            $validated_item = Validator::make($item, [
                'product_id' => 'required|string|exists:user_product,id',
                'quantity' => 'required',
                'unit_price' => 'required|string',
                'discount' => 'required|numeric|between:0,100',
            ]);

            if ($validated_item->fails()) {
                $item_errors[] = $validated_item->errors()->first();
            }
        }

        if (count($item_errors) > 0) {
            return response()->json(self::make_response(false, 'Validation Error', [$item_errors[0]]));
        }


        $new_order = $request->post();

        if ($new_order['user_id'] == $new_order['farmer_id']) {
            return response()->json(self::make_response(false, 'Farmer-User-Validation', ['Hello,it seems the user and farmer identities are the same']));
        }

        $new_order['id'] = self::UUID();
        $new_order['code'] = 'REF-' . Date('Y') . '-' . self::RANDOM_KEY(6, true);
        $new_order['created_at'] = self::NOW();
        $new_order['is_deleted'] = 0;
        $new_order['is_delivered'] = 0;
        $new_order['date_created'] = self::NOW('d');

        unset($new_order['items']);
        DB::table('dim_order')->updateOrInsert($new_order);

        foreach ($items as $item) {
            $new_order_detail = $item;
            $new_order_detail['id'] = self::UUID();
            $new_order_detail['is_deleted'] = 0;
            $new_order_detail['order_id'] = $new_order['id'];
            $new_order_detail['sub_total'] = $new_order_detail['quantity'] * $new_order_detail['unit_price'];
            $new_order_detail['created_at'] = $new_order['created_at'];

            DB::table('order_detail')->updateOrInsert($new_order_detail);
        }

        return response()->json(self::make_response(true, 'OK', null, 'Order created successfully'));
    }

    public function put(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|exists:dim_order,id',
            'values' => 'required|string'
        ]);

        if ($validator->fails()) {
            $data = self::make_response(false, 'Oops', [$validator->errors()->first()]);
            return response()->json($data);
        }

        $key = $request->get('key');
        $new_data = json_decode($request->get('values'), true);

        DB::table('dim_order')->where('id', $key)->update($new_data);

        return response()->json(self::make_response(true, 'OK', null, 'Order updated successfully'));
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required'
        ]);

        if ($validator->fails()) {
            $data = self::make_response(false, 'Oops', [$validator->errors()->first()]);
            return response()->json($data);
        }

        $key = $request->get('key');
        DB::table('dim_order')->where('id', $key)->delete();

        return response()->json(self::make_response(true, 'OK', null, 'Order deleted successfully'));
    }
}
