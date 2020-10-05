<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'api',
    'prefix' => 'v1/auth'
], function ($router) {
    Route::post('login', 'AuthController@login');
    Route::post('register', 'AuthController@register');
    Route::post('confirm', 'AuthController@confirm');
    Route::post('forgot', 'AuthController@forgot');
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'v1'
], function ($router) {

    Route::get('user/get', 'UsersController@get');
    Route::post('user/post', 'UsersController@post');
    Route::put('user/put', 'UsersController@put');
    Route::post('user/put', 'UsersController@put');
    Route::put('user/delete', 'UsersController@delete');
    Route::get('user/statistics', 'UsersController@statistics');

    Route::get('product/get', 'ProductsController@get');
    Route::post('product/post', 'ProductsController@post');
    Route::put('product/put', 'ProductsController@put');
    Route::delete('product/delete', 'ProductsController@delete');
    Route::get('product/filter', 'ProductsController@filter_');

    Route::get('category/get', 'CategoriesController@get');
    Route::post('category/post', 'CategoriesController@post');
    Route::put('category/put', 'CategoriesController@put');
    Route::delete('category/delete', 'CategoriesController@delete');

    Route::get('sub_category/get', 'SubCategoriesController@get');
    Route::post('sub_category/post', 'SubCategoriesController@post');
    Route::put('sub_category/put', 'SubCategoriesController@put');
    Route::delete('sub_category/delete', 'SubCategoriesController@delete');

    Route::get('order/get', 'OrdersController@get');
    Route::post('order/post', 'OrdersController@post');
    Route::put('order/put', 'OrdersController@put');
    Route::delete('order/delete', 'OrdersController@delete');

    Route::get('order_detail/get', 'OrderDetailsController@get');
    Route::post('order_detail/post', 'OrderDetailsController@post');
    Route::put('order_detail/put', 'OrderDetailsController@put');
    Route::delete('order_detail/delete', 'OrderDetailsController@delete');
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'v1/admin'
], function ($router) {

    Route::get('user/get', 'UsersController@get');
    Route::post('user/post', 'UsersController@post');
    Route::put('user/put', 'UsersController@put');
    Route::delete('user/delete', 'UsersController@delete');

    Route::get('product/get', 'ProductsController@get');
    Route::post('product/post', 'ProductsController@post');
    Route::put('product/put', 'ProductsController@put');
    Route::delete('product/delete', 'ProductsController@delete');
    Route::get('product/filter', 'ProductsController@filter_');

    Route::get('category/get', 'CategoriesController@get');
    Route::post('category/post', 'CategoriesController@post');
    Route::put('category/put', 'CategoriesController@put');
    Route::delete('category/delete', 'CategoriesController@delete');
    Route::get('category/lookup', 'CategoriesController@lookup');

    Route::get('subcategory/get', 'SubCategoriesController@get');
    Route::post('subcategory/post', 'SubCategoriesController@post');
    Route::put('subcategory/put', 'SubCategoriesController@put');
    Route::delete('subcategory/delete', 'SubCategoriesController@delete');
    Route::get('subcategory/lookup', 'SubCategoriesController@lookup');

    Route::get('order/get', 'OrdersController@get');
    Route::post('order/post', 'OrdersController@post');
    Route::put('order/put', 'OrdersController@put');
    Route::delete('order/delete', 'OrdersController@delete');

    Route::get('order_detail/get', 'OrderDetailsController@get');
    Route::post('order_detail/post', 'OrderDetailsController@post');
    Route::put('order_detail/put', 'OrderDetailsController@put');
    Route::delete('order_detail/delete', 'OrderDetailsController@delete');

    Route::get('user/statistics', 'UsersController@statistics');

});
