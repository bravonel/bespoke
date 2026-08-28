<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Client;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim($request->string('q')->toString()),
            'client_id' => $request->integer('client_id') ?: '',
            'status' => $request->string('status')->toString(),
        ];

        if (! in_array($filters['status'], Brand::statusOptions(), true)) {
            $filters['status'] = '';
        }

        $query = Brand::query()
            ->with(['client'])
            ->withCount('projects');

        if ($filters['q'] !== '') {
            $search = $filters['q'];

            $query->where(function ($subquery) use ($search) {
                $subquery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('therapeutic_area', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($clientQuery) => $clientQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($filters['client_id'] !== '') {
            $query->where('client_id', $filters['client_id']);
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', '!=', 'archived');
        }

        return view('brands.index', [
            'brands' => $query
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'clients' => Client::query()->orderBy('name')->get(),
            'statuses' => Brand::statusOptions(),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(Brand::statusOptions())],
            'therapeutic_area' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        Brand::create($validated);

        return to_route('brands.index')->with('status', 'Marca creada.');
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(Brand::statusOptions())],
            'therapeutic_area' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $brand->update($validated);

        return to_route('brands.index')->with('status', 'Marca actualizada.');
    }

    public function deactivate(Brand $brand): RedirectResponse
    {
        if ($brand->status !== 'archived') {
            $brand->update([
                'status_before_archive' => $brand->status,
                'status' => 'archived',
            ]);
        }

        return to_route('brands.index')->with('status', 'Marca desactivada. Sus proyectos e historial se conservaron.');
    }

    public function activate(Brand $brand): RedirectResponse
    {
        if ($brand->status === 'archived') {
            $brand->update([
                'status' => $brand->status_before_archive ?: 'active',
                'status_before_archive' => null,
            ]);
        }

        return to_route('brands.index', ['status' => 'archived'])->with('status', 'Marca reactivada.');
    }
}
