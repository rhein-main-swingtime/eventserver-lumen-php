<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class LoggingMiddleware
{

    private function isLocalhost(): bool
    {
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
            Log::info('URI requested: ' . $request->getUri());
        }

        return $next($request);
    }
}
