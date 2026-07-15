<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CollaboratorController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim($request->string('q')->toString()),
            'area' => $request->string('area')->toString(),
            'status' => $request->string('status')->toString(),
        ];

        if (! in_array($filters['status'], ['active', 'inactive'], true)) {
            $filters['status'] = '';
        }

        $query = User::query()
            ->withCount(['ownedProjects', 'assignedTasks', 'projectWorkloads']);

        if ($filters['q'] !== '') {
            $search = $filters['q'];

            $query->where(function ($subquery) use ($search) {
                $subquery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('area', 'like', "%{$search}%")
                    ->orWhere('puesto', 'like', "%{$search}%");
            });
        }

        if ($filters['area'] !== '') {
            $query->where('area', $filters['area']);
        }

        if ($filters['status'] === 'active') {
            $query->active();
        }

        if ($filters['status'] === 'inactive') {
            $query->inactive();
        }

        return view('collaborators.index', [
            'collaborators' => $query
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'areas' => User::query()
                ->whereNotNull('area')
                ->distinct()
                ->orderBy('area')
                ->pluck('area'),
            'positions' => User::query()
                ->whereNotNull('puesto')
                ->distinct()
                ->orderBy('puesto')
                ->pluck('puesto'),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedAttributes($request, new User);
        $validated['email_verified_at'] = now();

        User::create($validated);

        return to_route('collaborators.index')->with('status', 'Colaborador creado.');
    }

    public function update(Request $request, User $collaborator): RedirectResponse
    {
        $validated = $this->validatedAttributes($request, $collaborator, isUpdate: true);

        $collaborator->update($validated);

        return to_route('collaborators.index')->with('status', 'Colaborador actualizado.');
    }

    public function deactivate(Request $request, User $collaborator): RedirectResponse
    {
        if ($collaborator->is($request->user())) {
            return back()->with('status', 'No puedes dar de baja tu propio usuario.');
        }

        $collaborator->update(['is_active' => false]);

        return back()->with('status', 'Colaborador dado de baja.');
    }

    public function activate(User $collaborator): RedirectResponse
    {
        $collaborator->update(['is_active' => true]);

        return back()->with('status', 'Colaborador reactivado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedAttributes(Request $request, User $collaborator, bool $isUpdate = false): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($collaborator),
            ],
            'area' => ['nullable', 'string', 'max:255'],
            'puesto' => ['nullable', 'string', 'max:255'],
            'daily_capacity_hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'password' => [
                $isUpdate ? 'nullable' : 'required',
                'string',
                'confirmed',
                Password::defaults(),
            ],
        ]);

        $attributes = collect($validated)
            ->except(['daily_capacity_hours', 'password'])
            ->all();

        $attributes['daily_capacity_minutes'] = (int) round(((float) $validated['daily_capacity_hours']) * 60);

        if (! $isUpdate) {
            $attributes['is_active'] = true;
        }

        if (filled($validated['password'] ?? null)) {
            $attributes['password'] = $validated['password'];
        }

        return $attributes;
    }
}
