<?php

namespace App\Http\Middleware;

use App\Conference;
use App\Helpers\General;
use Closure;
use Illuminate\Support\Facades\Auth;

class DelegateRedirectIfNotAuthenticated
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param  string|null $guard
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null) {

        if (!Auth::guard('delegate')->check()) {
            return redirect(General::conferenceRoute('frontend.login'));
        } else {
            $currentConference = $request->segment(1);
            $conference = Conference::where('url_slug', $currentConference)->first();
            $user = Auth::guard('delegate')->user();
            if ($user->conference_id !== $conference->id) {
                return redirect(General::conferenceRoute('frontend.login'));
            }
        }

        return $next($request);
    }
}
