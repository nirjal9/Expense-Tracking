<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;


class CheckInitialRegistration
{
//    public function handle(Request $request, Closure $next): Response
//    {
////        if (!Auth::user()) {
////            return redirect()->route('register');
////        }
//        if (!Auth::check()) {
//            return $next($request);
//        }
//
////        if (Auth::check() && Auth::user()->is_completed) {
////            return redirect()->route('dashboard');
////        }
////        if (!Auth::user()->is_completed) {
////            return redirect()->route('register.income');
////        }
//        if (!Auth::user()->is_completed) {
//            if ($request->routeIs('register.income') || $request->routeIs('register.income.store')) {
//                return $next($request);
//            }
//            return redirect()->route('register.income');
//        }
//
//
//        return $next($request);
//    }

//    public function handle(Request $request, Closure $next): Response
//    {
//        if (!Auth::check()) {
//            return $next($request);
//        }
//
//        if (!Auth::user()->is_completed) {
//            if ($request->routeIs('register')) {
//                return redirect()->route('register.income');
//            }
//            if ($request->routeIs('register.income*') || $request->routeIs('register.categories*') || $request->routeIs('register.budget*')) {
//                return $next($request);
//            }
//            return redirect()->route('register.income');
//        }
//
//        return $next($request);
//    }
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            if ($request->routeIs('register.income*') ||
                $request->routeIs('register.categories*') ||
                $request->routeIs('register.budget*')) {
                return redirect()->route('register');
            }
            return $next($request);
        }

        if (!Auth::user()->is_completed) {
            if ($request->routeIs('register')) {
                return redirect()->route('register.income');
            }

            if ($request->routeIs('register.income*') ||
                $request->routeIs('register.categories*') ||
                $request->routeIs('register.budget*')) {
                return $next($request);
            }

            return redirect()->route('register.income');
        }

        return $next($request);
    }}
