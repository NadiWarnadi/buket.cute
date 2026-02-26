<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request, $token)
    {
        return view('auth.reset-password', ['request' => $request, 'token' => $token]);
    }

    /**
     * Handle an incoming new password request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        // Password reset logic here
        // This would typically call a password reset broker

        return redirect()->route('login')->with('status', __('auth.password'));
    }
}
