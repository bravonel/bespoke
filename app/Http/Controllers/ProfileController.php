<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ProfileController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if (! Schema::hasTable('temporary_coverages')) {
            return view('profile', [
                'coverageCandidates' => collect(),
                'createdCoverages' => collect(),
                'receivedCoverages' => collect(),
            ]);
        }

        $superAdminEmails = collect(config('bespoke.super_admin_emails', []))
            ->reject(fn (string $email) => $email === '*')
            ->all();

        return view('profile', [
            'coverageCandidates' => User::query()
                ->active()
                ->whereKeyNot($user->id)
                ->where(fn ($query) => $query->whereNull('role')->orWhere('role', '!=', User::ROLE_ADMIN))
                ->when($superAdminEmails, fn ($query) => $query->whereNotIn('email', $superAdminEmails))
                ->orderBy('name')
                ->get(),
            'createdCoverages' => $user->coveragesCreated()
                ->with('delegate')
                ->latest('starts_on')
                ->limit(12)
                ->get(),
            'receivedCoverages' => $user->coveragesReceived()
                ->with('owner')
                ->whereNull('revoked_at')
                ->whereDate('ends_on', '>=', today())
                ->orderBy('starts_on')
                ->get(),
        ]);
    }
}
