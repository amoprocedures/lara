<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderDetailsController extends API
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

        $data = DB::table('view_order_details')->where('is_deleted', 0)->get();

        if (!empty($id)) {
            $query = DB::table('view_order_details')->where(['id' => $id, 'is_deleted' => 0])->select('*')->get()->first();
            return response()->json(self::make_response(true, 'OK', null, $query));
        }
        if (!empty($search)) {
            $query = DB::table('view_order_details')
                ->where('order_id', 'like', "%$search%")
                ->orWhere('code', 'like', "%$search%")
                ->get();
            return response()->json(self::make_response(true, 'OK', null, $query));
        }
        return response()->json(self::make_response(true, 'OK', null, $data));
    }

    public function put(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|exists:order_detail,id',
            'values' => 'required|string'
        ]);

        if ($validator->fails()) {
            $data = self::make_response(false, 'Oops', [$validator->errors()->first()]);
            return response()->json($data);
        }

        $key = $request->get('key');
        $new_data = json_decode($request->get('values'), true);

        DB::table('order_detail')->where('id', $key)->update($new_data);

        return response()->json(self::make_response(true, 'OK', null, 'Order detail updated successfully'));
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|exists:order_detail,id'
        ]);

        if ($validator->fails()) {
            $data = self::make_response(false, 'Oops', [$validator->errors()->first()]);
            return response()->json($data);
        }

        $key = $request->get('key');
        DB::table('order_detail')->where('id', $key)->delete();

        return response()->json(self::make_response(true, 'OK', null, 'Order detail deleted successfully'));
    }
}
