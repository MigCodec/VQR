<?php

namespace App\Http\Controllers\Admin;

use App\Models\Card;
use App\Models\User;

class AdminDashboardController extends AdminController
{
    public function __invoke()
    {
        if ($response = $this->denyUnlessAdmin()) {
            return $response;
        }

        return view('admin.dashboard', [
            'cards' => Card::query()
                ->with('user')
                ->latest()
                ->get(),
            'cardTypes' => Card::TYPES,
            'users' => User::query()
                ->withCount(['cards', 'activeVehicles'])
                ->orderBy('name')
                ->get(),
        ]);
    }
}
