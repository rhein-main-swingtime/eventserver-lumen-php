<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class CorsMiddleware
{

    private const ALLOWED_ORIGINS = [
        'soontobe.rmswing.de',
        'hugo.rmswing.de',
        'rmswing.de'
    ];

    private function isLocalhost(): bool
    {
        foreach (['REMOTE_ADDR', 'HTTP_ORIGIN'] as $header) {
            if (!array_key_exists($header, $_SERVER)) {
                continue;
            }

            if (strpos($_SERVER[$header], 'localhost')  !== false
                || strpos($_SERVER[$header], '127.0.0.1')  !== false
                || strpos($_SERVER[$header], '::1')  !== false
                || strpos($_SERVER[$header], '0:0:0:0:0:0:0:1')  !== false
            ) {
                return true;
            }
        }
        return false;
    }

    private function isAllowedOrigin()
    {

        foreach (['REMOTE_ADDR', 'HTTP_ORIGIN'] as $header) {
            if (!array_key_exists($header, $_SERVER)) {
                continue;
            }
            foreach (self::ALLOWED_ORIGINS as $val) {
                if (strpos($_SERVER[$header], $val)  !== false) {
                    return true;
                }
            }
        }
        return false;
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
        } elseif ($this->isAllowedOrigin()) {
            $allowed_origin = $_SERVER['ORIGIN'] ?? $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['REMOTE_ADDR'];
        } else {
            return $next($request);
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
