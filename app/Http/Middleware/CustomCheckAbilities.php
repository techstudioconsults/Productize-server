<?php

namespace App\Http\Middleware;

use App\Exceptions\ForbiddenException;
use App\Exceptions\UnAuthorizedException;

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 20-06-2024
 */
class CustomCheckAbilities
{
    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$abilities
     * @return \Illuminate\Http\Response
     *
     * @throws UnAuthorizedException|ForbiddenException
     */
    public function handle($request, $next, ...$abilities)
    {
        if (! $request->user() || ! $request->user()->currentAccessToken()) {
            throw new UnAuthorizedException('Unauthorized access: You must be logged in and possess a valid access token to perform this action.');
        }

        foreach ($abilities as $ability) {
            if (! $request->user()->tokenCan($ability)) {
                throw new ForbiddenException("Access denied: You do not have the required ability '{$ability}' to perform this action. Please contact support if you believe this is an error.");
            }
        }

        return $next($request);
    }
}
