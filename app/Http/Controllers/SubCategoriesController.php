<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SubCategoriesController extends API
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

        $data = DB::table('sub_category')->where('is_deleted', 0)->get();

        if (!empty($id)) {
            $query = DB::table('sub_category')->where('id', $id)->select('*')->get()->first();
            return response()->json(self::make_response(true, 'OK', null, $query));
        }
        if (!empty($search)) {
            $query = DB::table('sub_category')
                ->where('name', 'like', "%$search%")
                ->get();
            return response()->json(self::make_response(true, 'OK', null, $query));
        }
        return response()->json(self::make_response(true, 'OK', null, $data));
    }

    public function post(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:3,45|unique:sub_category',
            'description' => 'required|string|between:3,100',
            'category_id' => 'required|string|exists:dim_category,id'
        ]);

        if ($validator->fails()) {
            $data = self::make_response(false, $validator->errors()->first(), [$validator->errors()->first()]);
            return response()->json($data)->setStatusCode(500);
        }

        $new_product = $request->post();
        $new_product['id'] = self::UUID();
        $new_product['created_at'] = self::NOW();
        $new_product['is_deleted'] = 0;

        DB::table('sub_category')->insert($new_product);

        return response()->json(self::make_response(true, 'OK', null, 'sub-category created successfully'));
    }

    public function put(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|exists:sub_category,id',
            'values' => 'required|string',
        ]);

        if ($validator->fails()) {
            $data = self::make_response(false, 'Oops', [$validator->errors()->first()]);
            return response()->json($data);
        }

        $key = $request->get('key');
        $new_data = json_decode($request->get('values'), true);

        DB::table('sub_category')->where('id', $key)->update($new_data);

        return response()->json(self::make_response(true, 'OK', null, 'sub-category updated successfully'));
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|exists:sub_category,id'
        ]);

        if ($validator->fails()) {
            $data = self::make_response(false, 'Oops', [$validator->errors()->first()]);
            return response()->json($data);
        }

        $key = $request->get('key');
        try {
            DB::table('sub_category')->where('id', $key)->delete();
            return response()->json(self::make_response(true, 'OK', null, 'sub-category deleted successfully'));
        } catch (QueryException $ex) {
            return response()->json(self::make_response(false, 'NB: Hello this record has linked fields so you cannot delete it, Thanks', ['Hello this record has linked fields']))->setStatusCode(500);
        }
    }
}
