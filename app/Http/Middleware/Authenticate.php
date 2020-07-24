<?php

namespace App\Http\Middleware;

use App\Models\ApiLog;
use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Supports\MessageClass;
use Exception;

class Authenticate
{
    use MessageClass;

    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if ($this->auth->guard($guard)->guest()) {
            return $this->sendBadRequest(null, __('validation.common.unauthorized_user'), RESPONSE_UNAUTHORIZED_REQUEST);
            // return response("Unauthorized.", 401);
        }
        //        $request->toArray();
        //        $request['user_id'] = $request['user_id'] ?? \Auth::id();


        try {
            $log = [
                'user_id' => \Auth::id() ?? null,
                'api_url' => $request->path(), // api url
                'method' => $request->method(), // api method
                'request' => $request->toArray(), // api request
                // 'response' =>   // api response
            ];
            if (in_array($request->method(), ['GET', 'DELETE'])) {
                unset($log['request']);
            }
            ApiLog::create($log);
        } catch (Exception $e) {
            \Log::error($e->getMessage());
        }
        return $next($request);
    }
}
