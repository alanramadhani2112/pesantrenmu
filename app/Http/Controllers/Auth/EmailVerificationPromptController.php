<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Show the email verification prompt.
     */
    public function __invoke(): View
    {
        return view('auth.verify-email');
    }
}
