<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{

    // ALLOW OPTIONS METHOD
    protected $headers = [
        'Allow-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Headers' => 'Origin, Content-Type, Accept, Authorization, X-Request-With, X-Requested-With, *',
        'Access-Control-Allow-Credentials' => 'true'
        // 'Access-Control-Allow-Headers' => 'Content-Type, Authorization, Accept, X-Requested-With, Origin, multipart/form-data, Application/Json, *',
        // multipart/form-data

        // 'Access-Control-Allow-Origin'      => '*',
        // 'Access-Control-Allow-Methods'     => 'GET, POST, PUT, DELETE, OPTIONS',
        // 'Access-Control-Allow-Credentials' => 'true',
        // 'Access-Control-Max-Age'           => '86400',
        // 'Access-Control-Allow-Headers'     => 'Origin, Content-Type, Accept, Authorization, X-Request-With,  *,'

    ];

    /**
     * setCorsHeaders
     *
     * @param  mixed $response
     *
     * @return void
     */
    public function setCorsHeaders($response)
    {
        foreach ($this->headers as $key => $value) {
            $response->header($key, $value);
        }
        return $response;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $headers = [
            'Access-Control-Allow-Origin'      => '*',
            'Access-Control-Allow-Methods'     => 'GET, PUT, POST, DELETE, OPTIONS',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'           => '86400',
            'Access-Control-Allow-Headers'     => 'Content-Type, Content-Range, Content-Disposition, Content-Description, Authorization, X-Requested-With'
        ];
        $response = $next($request);
        foreach ($headers as $key => $value) {
            $response->header($key, $value);
        }
        return $response;

        // header('Access-Control-Allow-Origin: *');
        // header("Access-Control-Allow-Credentials: true");
        // header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Content-Type, Content-Range, Content-Disposition, Content-Description');

        $response = $next($request);
        return $response;


        # 
        $response = $next($request);
        return $this->setCorsHeaders($response);

        if ($request->getMethod() == "OPTIONS") {
            return response()->make('OK', 200, $this->headers);
        }

        $response = $next($request);
        foreach ($this->headers as $key => $value)
            $response->header($key, $value);
        return $response;
    }
}
