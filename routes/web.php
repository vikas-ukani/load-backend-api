<?php

/** @noinspection ALL */

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/test', function (Request $request) {
    dd('testing route', User::first()->toArray());
    // $input = $request->all();

    // $encoded = json_encode($input['amenities_available'] ) ;

    // dd('check request', $request->all());
});

$router->get('/verify-email/{id}',  "Controller@userEmailVerify");
/** for vue routes */
$router->get('/admin', function () {
    return view('admin');
});

// $router->get('/admin/{route}/', function () {
//     return view('admin');
// });


/** For Image Security display user to show image */
$router->get(UPLOADED_FOLDER_NAME . '{folderName}/{moduleName}/{fileName}', function ($folderName, $moduleName, $fileName) {
    $storagePath = storage_path(UPLOADED_FOLDER_NAME . $folderName . '/' . $moduleName . '/' . $fileName . PNG_EXTENSION);

    if (file_exists($storagePath)) {
        $file = File::get($storagePath);
        $type = File::mimeType($storagePath);
        $response = response()->make($file, 200);
        $response->header("Content-Type", $type);
        return $response;
    }
    $response = [
        'success' => false,
        'status' => 400,
        'data' => null,
        'message' => ucfirst(strtolower(__('validation.common.image_not_found')))
    ];
    return response()->json($response);
});
