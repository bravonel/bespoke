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
    public function index(): View
    {
        return view('brands.index', [
            'brands' => Brand::query()
                ->with(['client'])
                ->withCount('projects')
                ->latest()
                ->get(),
            'clients' => Client::query()->orderBy('name')->get(),
            'statuses' => Brand::statusOptions(),
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

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();

        return to_route('brands.index')->with('status', 'Marca eliminada.');
    }
}
