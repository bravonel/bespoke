<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Access\OperationalAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ProfileController extends Controller
{
    public function __invoke(Request $request, OperationalAccess $access): View
    {
        $user = $request->user();

        if (! Schema::hasTable('temporary_coverages')) {
            return view('profile', [
                'coverageCandidates' => collect(),
                'createdCoverages' => collect(),
                'receivedCoverages' => collect(),
                'coverageClients' => collect(),
                'coverageProjects' => collect(),
            ]);
        }

        $superAdminEmails = collect(config('bespoke.super_admin_emails', []))
            ->reject(fn (string $email) => $email === '*')
            ->all();

        $delegableProjects = $access->delegableProjects($user)
            ->with('client')
            ->where('status', '!=', 'archived')
            ->orderBy('name')
            ->get();
        $coverageRelations = Schema::hasTable('temporary_coverage_scopes')
            ? ['delegate', 'scopes.client', 'scopes.project']
            : ['delegate'];
        $receivedRelations = Schema::hasTable('temporary_coverage_scopes')
            ? ['owner', 'scopes.client', 'scopes.project']
            : ['owner'];

        return view('profile', [
            'coverageCandidates' => User::query()
                ->active()
                ->whereKeyNot($user->id)
                ->where(fn ($query) => $query->whereNull('role')->orWhere('role', '!=', User::ROLE_ADMIN))
                ->when($superAdminEmails, fn ($query) => $query->whereNotIn('email', $superAdminEmails))
                ->orderBy('name')
                ->get(),
            'createdCoverages' => $user->coveragesCreated()
                ->with($coverageRelations)
                ->latest('starts_on')
                ->limit(12)
                ->get(),
            'receivedCoverages' => $user->coveragesReceived()
                ->with($receivedRelations)
                ->whereNull('revoked_at')
                ->whereDate('ends_on', '>=', today())
                ->orderBy('starts_on')
                ->get(),
            'coverageClients' => $delegableProjects
                ->pluck('client')
                ->filter()
                ->unique('id')
                ->sortBy('name')
                ->values(),
            'coverageProjects' => $delegableProjects,
        ]);
    }
}
