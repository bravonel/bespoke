<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Access\UserRoleService;
use App\Services\Audit\AuditLogger;
use App\Services\WhatsApp\PhoneNumber;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CollaboratorController extends Controller
{
    public function __construct(
        private readonly UserRoleService $roles,
        private readonly AuditLogger $audit,
    ) {}

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
            'roleOptions' => User::roleOptions(),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedAttributes($request, new User);
        $validated['email_verified_at'] = now();

        $collaborator = User::create($validated);
        $this->audit->record('user.created', $collaborator, $request->user(), [
            'role' => $collaborator->role,
            'area' => $collaborator->area,
            'puesto' => $collaborator->puesto,
        ]);

        return to_route('collaborators.index')->with('status', 'Colaborador creado.');
    }

    public function update(Request $request, User $collaborator): RedirectResponse
    {
        $validated = $this->validatedAttributes($request, $collaborator, isUpdate: true);

        try {
            $this->roles->assertRoleChangeAllowed($collaborator, $validated['role'] ?? null);
        } catch (DomainException $exception) {
            return back()->with('status', $exception->getMessage());
        }

        $collaborator->update($validated);
        $this->audit->record('user.updated', $collaborator, $request->user(), [
            'changes' => $collaborator->getChanges(),
        ]);

        return to_route('collaborators.index')->with('status', 'Colaborador actualizado.');
    }

    public function deactivate(Request $request, User $collaborator): RedirectResponse
    {
        if ($collaborator->is($request->user())) {
            return back()->with('status', 'No puedes dar de baja tu propio usuario.');
        }

        try {
            $this->roles->assertDeactivationAllowed($collaborator);
        } catch (DomainException $exception) {
            return back()->with('status', $exception->getMessage());
        }

        $collaborator->update(['is_active' => false]);
        $this->audit->record('user.deactivated', $collaborator, $request->user());

        return back()->with('status', 'Colaborador dado de baja.');
    }

    public function activate(Request $request, User $collaborator): RedirectResponse
    {
        $collaborator->update(['is_active' => true]);
        $this->audit->record('user.activated', $collaborator, $request->user());

        return back()->with('status', 'Colaborador reactivado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedAttributes(Request $request, User $collaborator, bool $isUpdate = false): array
    {
        $request->merge([
            'whatsapp_phone' => PhoneNumber::normalize($request->input('whatsapp_phone')),
            'whatsapp_enabled' => $request->boolean('whatsapp_enabled'),
        ]);

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
            'role' => ['nullable', 'string', Rule::in(array_keys(User::roleOptions()))],
            'whatsapp_phone' => [
                'nullable',
                'string',
                'min:10',
                'max:24',
                Rule::unique(User::class)->ignore($collaborator),
            ],
            'whatsapp_enabled' => ['boolean'],
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
        $attributes['whatsapp_enabled'] = filled($validated['whatsapp_phone'] ?? null)
            && (bool) ($validated['whatsapp_enabled'] ?? false);

        if (! $isUpdate) {
            $attributes['is_active'] = true;
        }

        if (filled($validated['password'] ?? null)) {
            $attributes['password'] = $validated['password'];
        }

        return $attributes;
    }
}
