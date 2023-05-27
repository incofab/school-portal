<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;

class EnsureUserIsAnAgent
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        if (!$user){
            return $request->expectsJson()
                ? abort(403, 'You are not looged in.')
                : Redirect::guest(URL::route('login'));
        }
        
        $role = $user->role()->first();
        
        if(!$role || ($role->name !== 'agent' && $role->name !== 'admin')){
            return $request->expectsJson()
                ? abort(403, 'Access denied.')
                : Redirect::guest(URL::route('user-dashboard'));
        }
        
        return $next($request);
    }
    
}
