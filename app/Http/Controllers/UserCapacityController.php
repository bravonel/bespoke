<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserCapacityController extends Controller
{
    public function update(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'daily_capacity_hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
        ]);

        $before = $user->only('daily_capacity_minutes');
        $user->update([
            'daily_capacity_minutes' => (int) round(((float) $validated['daily_capacity_hours']) * 60),
        ]);
        $audit->recordChange(
            'user.capacity_changed',
            $user,
            $before,
            $user->only('daily_capacity_minutes'),
            $request->user(),
        );

        return back()->with('status', 'Capacidad diaria actualizada.');
    }
}
