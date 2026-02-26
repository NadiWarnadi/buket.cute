<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password view.
     */
    public function show(Request $request)
    {
        return view('auth.confirm-password');
    }

    /**
     * Confirm the user's password.
     */
    public function store(Request $request)
    {
        if (! hash_equals((string) $request->session()->get('auth.password_confirmed_at'), (string) now()->timestamp)) {
            if (! password_verify($request->password, $request->user()->password)) {
                return back()->withErrors([
                    'password' => __('auth.password'),
                ]);
            }

            $request->session()->put('auth.password_confirmed_at', now()->timestamp);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
