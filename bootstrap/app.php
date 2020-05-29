<?php

require_once __DIR__ . '/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

/** main sending configurations */
// $app->configure('mail');
$app->configure('api-debugger');
// $app->configure('cors');


$app->alias('mailer', Illuminate\Contracts\Mail\Mailer::class);

$app->withFacades(true, [
    Tymon\JWTAuth\Facades\JWTAuth::class => 'JWTAuth',
    Tymon\JWTAuth\Facades\JWTFactory::class => 'JWTFactory',
    'Illuminate\Support\Facades\Notification' => 'Notification',
]);

$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
    // App\Http\Middleware\ExampleMiddleware::class
    App\Http\Middleware\CorsMiddleware::class,
]);

$app->routeMiddleware([
    'auth' => App\Http\Middleware\Authenticate::class,
    'check_account' => App\Http\Middleware\CheckUserAccount::class,
    'cors' => App\Http\Middleware\CorsMiddleware::class,
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/
$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
$app->register(Tymon\JWTAuth\Providers\LumenServiceProvider::class);
// $app->register(Prettus\Repository\Providers\LumenRepositoryServiceProvider::class);
/** Mail */
// $app->register(Illuminate\Mail\MailServiceProvider::class);
/** Notification Register */
$app->register(\Illuminate\Notifications\NotificationServiceProvider::class); // Send Notifications
$app->register(Flipbox\LumenGenerator\LumenGeneratorServiceProvider::class); // File Generator
$app->register(Sayeed\CustomMigrate\CustomMigrateServiceProvider::class); // Custom Migration
// $app->register(Barryvdh\Cors\ServiceProvider::class); //CORS
$app->register(Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);

/* Request */
// $app->register(Ghostff\FormRequest\RequestServiceProvider::class);
/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

/** route file for web panel */
$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__ . '/../routes/web.php';
});


/** connect api file for device apis */
$app->router->group([
    'namespace' => 'App\Http\Controllers\API\v1',
    'prefix' => 'api'
], function ($router) {
    require __DIR__ . '/../routes/api.php';
});

/**
 * Admin Route Register
 */
$app->router->group([
    'namespace' => 'App\Http\Controllers\Admin',
    'prefix' => 'admin',
], function ($router) {
    require __DIR__ . '/../routes/admin.php';
});
return $app;
