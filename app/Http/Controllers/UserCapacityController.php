<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserCapacityController extends Controller
{
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'daily_capacity_hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
        ]);

        $user->update([
            'daily_capacity_minutes' => (int) round(((float) $validated['daily_capacity_hours']) * 60),
        ]);

        return back()->with('status', 'Capacidad diaria actualizada.');
    }
}
