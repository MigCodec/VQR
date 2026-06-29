<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

abstract class AdminController extends Controller
{
    protected function denyUnlessAdmin()
    {
        if (! Auth::check()) {
            return redirect()->route('auth.google.redirect');
        }

        abort_unless(Auth::user()->isAdmin(), 403);

        return null;
    }
}
