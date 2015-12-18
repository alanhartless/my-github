<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

use Illuminate\Support\Facades\Request;

Route::filter('auth.basic', function() {
    $user     = Request::getUser();
    $password = Request::getPassword();

    $systemUser     = env('AUTH_USER', false);
    $systemPassword = env('AUTH_PASSWORD', false);

    if ((!empty($systemUser) || !empty($systemPassword)) && ($user != $systemUser || $password != $systemPassword)) {
        $response = Response::make('Not authorized', 401);

        $response->header('WWW-Authenticate', 'Basic realm="Login to My Github"');

        return $response;
    }
});

Route::group(['before' => 'auth.basic'], function() {
    Route::get('/', ['as' => 'default', 'uses' => 'Repositories\IndexController@showIndex']);

    // Repository list
    Route::get('repo/{login}/{repo}', ['as' => 'branches', 'uses' => 'Branches\IndexController@showIndex']);

    // Activity
    Route::get('activity/live', ['as' => 'get_live_activity', 'uses' => 'Activity\LiveController@getActivity']);
    Route::get('activity/{page?}', ['as' => 'activity', 'uses' => 'Activity\IndexController@showIndex']);
    Route::post('comment/reply/{login}/{repo}/{issue}', ['as' => 'comment_reply', 'uses' => 'Activity\ReplyController@reply']);

    // Notifications
    Route::get('notifications/live', ['as' => 'get_live_notifications', 'uses' => 'Notifications\LiveController@getActivity']);
    Route::get('notifications/{page?}', ['as' => 'notifications', 'uses' => 'Notifications\IndexController@showIndex']);

    // Branch management
    Route::post('delete/{login}/{repo}/{branch}', ['as' => 'delete_branch', 'uses' => 'Branches\deleteController@deleteBranch']);
});

// Logout
Route::get('logout', function() {
    $response = Response::make('<a href="'.URL::route('default').'">Not authorized - click to login</a>', 401);

    $response->header('WWW-Authenticate', 'Basic realm="Login to My Github"');

    return $response;
});
