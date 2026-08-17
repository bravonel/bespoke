<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Client;
use App\Models\QrCode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QrCodeController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim($request->string('q')->toString()),
            'client_id' => $request->integer('client_id') ?: '',
            'status' => $request->string('status')->toString(),
        ];

        $query = QrCode::query()->with(['client', 'brand']);

        if ($filters['q'] !== '') {
            $search = $filters['q'];
            $query->where(fn ($builder) => $builder
                ->where('name', 'like', "%{$search}%")
                ->orWhere('destination_url', 'like', "%{$search}%")
                ->orWhereHas('client', fn ($client) => $client->where('name', 'like', "%{$search}%")));
        }

        if ($filters['client_id'] !== '') {
            $query->where('client_id', $filters['client_id']);
        }

        if (in_array($filters['status'], QrCode::statusOptions(), true)) {
            $query->where('status', $filters['status']);
        } else {
            $filters['status'] = '';
        }

        $recentScans = DB::table('qr_scans')->where('created_at', '>=', now()->subDays(30))->count();
        $previousScans = DB::table('qr_scans')
            ->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])
            ->count();

        return view('qr-codes.index', [
            'qrCodes' => $query->latest()->paginate(18)->withQueryString(),
            'clients' => Client::query()->orderBy('name')->get(),
            'filters' => $filters,
            'summary' => [
                'total' => QrCode::query()->count(),
                'active' => QrCode::query()->where('status', 'active')->count(),
                'scans' => QrCode::query()->sum('scans_count'),
                'recent_scans' => $recentScans,
                'trend' => $previousScans > 0
                    ? round((($recentScans - $previousScans) / $previousScans) * 100)
                    : ($recentScans > 0 ? 100 : 0),
            ],
        ]);
    }

    public function create(): View
    {
        return view('qr-codes.create', [
            'clients' => Client::query()->orderBy('name')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'defaults' => $this->defaultDesign(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateQrCode($request);
        $logoPath = $request->file('logo')?->store('qr-logos', 'public');

        $qrCode = QrCode::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'slug' => $this->uniqueSlug(),
            'design' => $this->designFrom($request),
            'logo_path' => $logoPath,
        ]);

        return to_route('qr-codes.show', $qrCode)->with('status', 'QR dinámico creado y listo para compartir.');
    }

    public function show(QrCode $qrCode): View
    {
        $qrCode->load(['client', 'brand', 'creator']);
        $range = collect(range(13, 0))->map(fn (int $days) => now()->subDays($days)->startOfDay());
        $dailyCounts = $qrCode->scans()
            ->where('created_at', '>=', $range->first())
            ->selectRaw('DATE(created_at) as scan_date, COUNT(*) as aggregate')
            ->groupBy('scan_date')
            ->pluck('aggregate', 'scan_date');

        $devices = $qrCode->scans()
            ->selectRaw('device, COUNT(*) as aggregate')
            ->groupBy('device')
            ->orderByDesc('aggregate')
            ->pluck('aggregate', 'device');

        $locations = $qrCode->scans()
            ->where(fn ($query) => $query->whereNotNull('city')->orWhereNotNull('country'))
            ->selectRaw('city, region, country, COUNT(*) as aggregate')
            ->groupBy('city', 'region', 'country')
            ->orderByDesc('aggregate')
            ->limit(6)
            ->get()
            ->mapWithKeys(fn ($scan) => [
                collect([$scan->city, $scan->region, $scan->country])->filter()->join(', ') => $scan->aggregate,
            ]);

        return view('qr-codes.show', [
            'qrCode' => $qrCode,
            'daily' => $range->map(fn ($date) => [
                'label' => $date->translatedFormat('d M'),
                'count' => (int) $dailyCounts->get($date->toDateString(), 0),
            ]),
            'devices' => $devices,
            'locations' => $locations,
            'uniqueScans' => $qrCode->scans()->whereNotNull('ip_hash')->distinct()->count('ip_hash'),
            'recentScans' => $qrCode->scans()->latest()->limit(20)->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'clients' => Client::query()->orderBy('name')->get(),
        ]);
    }

    public function print(QrCode $qrCode): View
    {
        $qrCode->load(['client', 'brand']);

        return view('qr-codes.print', ['qrCode' => $qrCode]);
    }

    public function update(Request $request, QrCode $qrCode): RedirectResponse
    {
        $validated = $this->validateQrCode($request, false);

        if ($request->boolean('remove_logo') && $qrCode->logo_path) {
            Storage::disk('public')->delete($qrCode->logo_path);
            $qrCode->logo_path = null;
        }

        if ($request->hasFile('logo')) {
            if ($qrCode->logo_path) {
                Storage::disk('public')->delete($qrCode->logo_path);
            }
            $qrCode->logo_path = $request->file('logo')->store('qr-logos', 'public');
        }

        $qrCode->fill([
            ...$validated,
            'design' => $this->designFrom($request),
        ])->save();

        return to_route('qr-codes.show', $qrCode)->with('status', 'QR actualizado sin cambiar el código impreso.');
    }

    public function destroy(QrCode $qrCode): RedirectResponse
    {
        if ($qrCode->logo_path) {
            Storage::disk('public')->delete($qrCode->logo_path);
        }

        $qrCode->delete();

        return to_route('qr-codes.index')->with('status', 'QR eliminado.');
    }

    public function export(QrCode $qrCode): StreamedResponse
    {
        return response()->streamDownload(function () use ($qrCode): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['fecha', 'dispositivo', 'navegador', 'ciudad', 'region', 'pais', 'referente']);
            $qrCode->scans()->latest()->chunk(500, function ($scans) use ($output): void {
                foreach ($scans as $scan) {
                    fputcsv($output, [
                        $scan->created_at?->toIso8601String(),
                        $scan->device,
                        $scan->browser,
                        $scan->city,
                        $scan->region,
                        $scan->country,
                        $scan->referrer,
                    ]);
                }
            });
            fclose($output);
        }, 'escaneos-'.$qrCode->slug.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validateQrCode(Request $request, bool $requireName = true): array
    {
        return $request->validate([
            'name' => [$requireName ? 'required' : 'sometimes', 'string', 'max:255'],
            'destination_url' => ['required', 'url:http,https', 'max:2048'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'status' => ['required', Rule::in(QrCode::statusOptions())],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);
    }

    private function designFrom(Request $request): array
    {
        return [
            'foreground' => $this->safeColor($request->string('foreground')->toString(), '#161616'),
            'background' => $this->safeColor($request->string('background')->toString(), '#FFFFFF'),
            'dots' => $this->safeOption($request->string('dots')->toString(), ['square', 'dots', 'rounded', 'extra-rounded', 'classy', 'classy-rounded'], 'rounded'),
            'corners' => $this->safeOption($request->string('corners')->toString(), ['square', 'dot', 'extra-rounded'], 'extra-rounded'),
            'frame' => $this->safeOption($request->string('frame')->toString(), ['none', 'soft', 'ticket'], 'soft'),
            'cta' => Str::limit(trim($request->string('cta')->toString()) ?: 'ESCANEA AQUÍ', 28, ''),
        ];
    }

    private function defaultDesign(): array
    {
        return [
            'foreground' => '#161616',
            'background' => '#FFFFFF',
            'dots' => 'rounded',
            'corners' => 'extra-rounded',
            'frame' => 'soft',
            'cta' => 'ESCANEA AQUÍ',
        ];
    }

    private function safeColor(string $value, string $fallback): string
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? strtoupper($value) : $fallback;
    }

    private function safeOption(string $value, array $options, string $fallback): string
    {
        return in_array($value, $options, true) ? $value : $fallback;
    }

    private function uniqueSlug(): string
    {
        do {
            $slug = Str::lower(Str::random(9));
        } while (QrCode::query()->where('slug', $slug)->exists());

        return $slug;
    }
}
