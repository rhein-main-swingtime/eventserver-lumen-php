<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class CorsMiddleware
{

    private function isLocalhost(): bool {
        return (
            strpos($_SERVER['REMOTE_ADDR'], 'localhost')  !== false
            || strpos($_SERVER['REMOTE_ADDR'], '127.0.0.1')  !== false
        );
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

        if ($this->isLocalhost()) {
            $allowed_origin = '*';
        } else {
            $protocol = !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';
            $allowed_origin = $protocol . 'soontobe.rmswing.de';
        }

        $headers = [
            'Access-Control-Allow-Origin'      => $allowed_origin,
            'Access-Control-Allow-Methods'     => 'POST, GET, OPTIONS, PUT, DELETE',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'           => '86400',
            'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Requested-With'
        ];

        if ($request->isMethod('OPTIONS')) {
            return response()->json('{"method":"OPTIONS"}', 200, $headers);
        }

        $response = $next($request);

        foreach ($headers as $key => $value) {
            $response->header($key, $value);
        }

        return $response;
    }
}
