<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductsController extends API
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

        $is_verified = $request->get('is_verified');

        $search = $request->get('search');

        $data = DB::table('view_user_product')->select('*')->get();

        $strong_query = DB::table('view_user_product');
        if (is_numeric($is_verified) || !empty($is_verified)) {
            $strong_query->where('is_verified', $is_verified);
        }


        if (is_numeric($is_verified) || !empty($is_verified)) {
            $query = DB::table('view_user_product')->where(['is_verified' => $is_verified])->get();
            return response()->json(self::make_response(true, 'OK', null, $query));
        }

        if (!empty($search)) {
            $query = DB::table('view_user_product')
                ->where('id', 'like', "%$search%")
                ->orWhere('user_id', 'like', "%$search%")
                ->orWhere('name', 'like', "%$search%")
                ->orWhere('high_lights', 'like', "%$search%")
                ->orWhere('category', 'like', "%$search%")
                ->orWhere('sub_category', 'like', "%$search%")
                ->orWhere('uploader_name', 'like', "%$search%")->get();

            return response()->json(self::make_response(true, 'OK', null, $query));
        }

        if (!empty($user_id) && !empty($id)) {
            $data = DB::table('view_user_product')->select('*')->where('user_id', $user_id)->where('id', $id)->get();
            return response()->json(self::make_response(true, 'OK', null, $data));
        }

        if (!empty($user_id)) {
            $data = DB::table('view_user_product')->select('*')->where('user_id', $user_id)->get();
            return response()->json(self::make_response(true, 'OK', null, $data));
        }

        if (!empty($id)) {
            $data = DB::table('view_user_product')->select('*')->where('id', $id)->get()->first();
            return response()->json(self::make_response(true, 'OK', null, $data));
        }
        return response()->json(self::make_response(true, 'OK', null, $data));
    }

    public function put(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'key' => 'required|string',
            'values' => 'required',
        ]);

        if ($validator->fails()) {
            $data = self::make_response(false, 'Oops', [$validator->errors()->first()]);
            return response()->json($data);
        }

        $key = $request->get('key');
        $new_data = json_decode($request->get('values'), true);

        if (array_key_exists('is_verified', $new_data)) {
            $new_data['verified_at'] = ($new_data['is_verified'] == '1') ? self::NOW() : null;
        }

        DB::table('user_product')->where('id', $key)->update($new_data);

        return response()->json(self::make_response(true, 'OK', null, $new_data));
    }

    public function post(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string|exists:dim_user,id',
            'sub_category_id' => 'required|exists:sub_category,id',
            'name' => 'required|between:3,45',
            'price' => 'required|numeric',
            'description' => 'required|string|between:5,200',
            'high_lights' => 'required|string|between:5,200',
            'sale_start_date' => 'required|date|date_format:Y-m-d',
            'sale_end_date' => 'required|date|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            $data = self::make_response(false, 'Validation Error', [$validator->errors()->first()]);
            return response()->json($data);
        }

        if (!$request->hasFile('images')) {
            return response()->json(self::make_response(true, 'OK', null, ['NB: This request has no images']));
        } else {
            $allowedFileExtensions = ['jpeg', 'png', 'jpg'];
            $files = $request->file('images');

            $file_errors = [];
            foreach ($files as $file) {
                $extension = $file->getClientOriginalExtension();
                $validated = in_array($extension, $allowedFileExtensions);

                if (!$validated) {
                    $file_errors[] = 'error';
                }
            }

            if (!empty($file_errors)) {
                return response()->json(self::make_response(true, 'OK', null, ['NB: This request has some invalid files, allowed are [jpeg, png, jpg]']));
            } else {
                $new_product = $request->post();
                $new_product['id'] = self::UUID();
                $new_product['created_at'] = self::NOW();
                $new_product['is_verified'] = 0;
                $new_product['sale_price'] = $new_product['price'];

                $new_product['category_id'] = DB::table('sub_category')->where('id', $new_product['sub_category_id'])->get()->first()->category_id;

                DB::table('user_product')->insert($new_product);

                foreach ($files as $file) {
                    $file_name = $name = str_replace('-', '_', self::UUID()) . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('images/products'), $file_name);

                    $new_product_image = [
                        'id' => $this->UUID(),
                        'user_product_id' => $new_product['id'],
                        'url' => 'images/products/' . $file_name,
                        'created_at' => self::NOW(),
                        'is_deleted' => 0
                    ];
                    DB::table('user_product_image')->insert($new_product_image);
                }
                $output = DB::table('view_user_product')->select('*')->where('id', $new_product['id'])->get()->first();
                return response()->json(self::make_response(true, 'Product uploaded successfully', null, $output));
            }
        }
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|string|exists:user_product,id',
        ]);

        if ($validator->fails()) {
            $data = self::make_response(false, 'Validation Error', [$validator->errors()->first()]);
            return response()->json($data);
        }
        $id = $request->get('id');
        $query = DB::table('user_product')->where('id', $id)->delete();
        if (!$query) {
            return response()->json(self::make_response(true, 'OK', 'Something went wrong!'));
        }
        return response()->json(self::make_response(true, 'OK', null, ['Product Deleted successfully!']));
    }

    public function filter_(Request $request)
    {
        $is_verified = $request->get('is_verified');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');


        if (!empty($start_date) && !empty($end_date) && is_numeric($is_verified)) {
            $query = DB::table('view_user_product')
                ->where('is_verified', $is_verified)
                ->whereBetween('sale_end_date', [$start_date, $end_date])
                ->get();
            return response()->json(self::make_response(true, 'OK', null, $query));
        }

        if (is_numeric($is_verified)) {
            $query = DB::table('view_user_product')->where('is_verified', $is_verified)->get();
            return response()->json(self::make_response(true, 'OK', null, $query));
        }
    }

}
