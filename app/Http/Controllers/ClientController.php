<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim($request->string('q')->toString()),
            'status' => $request->string('status')->toString(),
        ];

        if (! in_array($filters['status'], Client::statusOptions(), true)) {
            $filters['status'] = '';
        }

        $query = Client::query()
            ->withCount(['brands', 'projects']);

        if ($filters['q'] !== '') {
            $search = $filters['q'];

            $query->where(function ($subquery) use ($search) {
                $subquery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('primary_contact_name', 'like', "%{$search}%")
                    ->orWhere('primary_contact_email', 'like', "%{$search}%")
                    ->orWhere('primary_contact_phone', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', '!=', 'archived');
        }

        return view('clients.index', [
            'clients' => $query
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'statuses' => Client::statusOptions(),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(Client::statusOptions())],
            'primary_contact_name' => ['nullable', 'string', 'max:255'],
            'primary_contact_email' => ['nullable', 'email', 'max:255'],
            'primary_contact_phone' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        Client::create($validated);

        return to_route('clients.index')->with('status', 'Cliente creado.');
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(Client::statusOptions())],
            'primary_contact_name' => ['nullable', 'string', 'max:255'],
            'primary_contact_email' => ['nullable', 'email', 'max:255'],
            'primary_contact_phone' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $client->update($validated);

        return to_route('clients.index')->with('status', 'Cliente actualizado.');
    }

    public function deactivate(Client $client): RedirectResponse
    {
        if ($client->status !== 'archived') {
            $client->update([
                'status_before_archive' => $client->status,
                'status' => 'archived',
            ]);
        }

        return to_route('clients.index')->with('status', 'Cliente desactivado. Sus proyectos e historial se conservaron.');
    }

    public function activate(Client $client): RedirectResponse
    {
        if ($client->status === 'archived') {
            $client->update([
                'status' => $client->status_before_archive ?: 'active',
                'status_before_archive' => null,
            ]);
        }

        return to_route('clients.index', ['status' => 'archived'])->with('status', 'Cliente reactivado.');
    }
}
