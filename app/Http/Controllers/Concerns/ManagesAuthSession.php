<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait ManagesAuthSession
{
    protected function regenerateSession(Request $request): void
    {
        $request->session()->regenerate();
    }

    protected function invalidateSession(Request $request): void
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
