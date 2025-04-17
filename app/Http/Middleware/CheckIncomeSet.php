<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckIncomeSet
{
    public function handle(Request $request, Closure $next): Response
    {
//        if (!Auth::user()->income || Auth::user()->income == 0) {
        $user = Auth::user();
        if (!$user->incomes()) {
            return redirect()->route('register.income')->with('error', 'Please set your income first.');
        }

        return $next($request);
    }
}
