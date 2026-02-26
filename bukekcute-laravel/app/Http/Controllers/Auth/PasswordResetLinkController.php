<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create()
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Password reset logic here

        return back()->with('status', __('passwords.sent'));
    }
}
