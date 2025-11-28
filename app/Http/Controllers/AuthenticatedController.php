<?php

namespace App\Http\Controllers;

/**
 * Base controller for authenticated & verified users.
 */
class AuthenticatedController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }
}
