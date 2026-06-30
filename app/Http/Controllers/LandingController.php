<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class LandingController extends Controller
{
    public function __invoke()
    {
        if (Auth::check()) {
            return redirect()->route('account.show');
        }

        return view('landing');
    }
}
