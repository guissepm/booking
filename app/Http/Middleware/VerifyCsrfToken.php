<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'stripe/webhook',
    ];

    /**
     * Stateless JWT/mobile requests (ajax without the web app's own
     * "fromWebApp" marker, see Controller::setMiddleware()) authenticate via
     * a bearer token instead of the session cookie, so they carry no CSRF
     * token and are not forgeable through a session-based CSRF attack.
     */
    public function handle($request, \Closure $next)
    {
        if ($request->ajax() && !$request->has('fromWebApp'))
        {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
