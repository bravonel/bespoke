<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class QrRedirectController extends Controller
{
    public function __invoke(Request $request, string $slug): RedirectResponse|Response
    {
        $qrCode = QrCode::query()->where('slug', $slug)->firstOrFail();

        if ($qrCode->status !== 'active') {
            return response()->view('qr-codes.inactive', ['qrCode' => $qrCode], 410);
        }

        $agent = Str::limit((string) $request->userAgent(), 1000, '');

        DB::transaction(function () use ($request, $qrCode, $agent): void {
            $qrCode->scans()->create([
                'ip_hash' => $request->ip() ? hash_hmac('sha256', $request->ip(), (string) config('app.key')) : null,
                'device' => $this->device($agent),
                'browser' => $this->browser($agent),
                'country' => $request->header('CF-IPCountry') ?: $request->header('X-Vercel-IP-Country'),
                'region' => $request->header('X-Vercel-IP-Country-Region'),
                'city' => $request->header('X-Vercel-IP-City'),
                'referrer' => Str::limit((string) $request->headers->get('referer'), 2000, ''),
                'user_agent' => $agent,
                'created_at' => now(),
            ]);

            $qrCode->increment('scans_count');
            $qrCode->forceFill(['last_scanned_at' => now()])->save();
        });

        return redirect()->away($qrCode->destination_url, 302, [
            'Cache-Control' => 'no-store, private',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    private function device(string $agent): string
    {
        if (preg_match('/ipad|tablet|kindle/i', $agent)) {
            return 'tablet';
        }

        if (preg_match('/mobile|iphone|ipod|android/i', $agent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function browser(string $agent): string
    {
        return match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') => 'Opera',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') && ! str_contains($agent, 'Chrome/') => 'Safari',
            str_contains($agent, 'Firefox/') => 'Firefox',
            default => 'Otro',
        };
    }
}
