<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('settings', [
            'user' => Auth::user(),
        ]);
    }
}
