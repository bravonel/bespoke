<?php

namespace App\Livewire\Actions;

use App\Services\Activity\UserSessionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Logout
{
    public function __construct(private readonly UserSessionService $sessions) {}

    /**
     * Log the current user out of the application.
     */
    public function __invoke(): void
    {
        if (request()->hasSession()) {
            $this->sessions->end(request(), 'logout');
        }
        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();
    }
}
