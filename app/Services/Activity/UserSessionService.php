<?php

namespace App\Services\Activity;

use App\Models\User;
use App\Models\UserSession;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserSessionService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function start(User $user, Request $request): UserSession
    {
        $session = UserSession::query()->firstOrCreate(
            ['session_key_hash' => $this->sessionHash($request)],
            [
                'user_id' => $user->id,
                'channel' => 'web',
                'ip_hash' => $this->ipHash($request->ip()),
                ...$this->device($request->userAgent()),
                'last_page' => Str::limit($request->path(), 255, ''),
                'started_at' => now(),
                'last_activity_at' => now(),
                'last_heartbeat_at' => now(),
            ]
        );

        $request->session()->put('activity_session_id', $session->id);

        if ($session->wasRecentlyCreated) {
            $this->audit->recordSystem('auth.login_succeeded', $user, [
                'session_id' => $session->id,
                'device_type' => $session->device_type,
                'browser' => $session->browser,
            ]);
        }

        return $session;
    }

    public function current(Request $request, ?User $user = null): ?UserSession
    {
        $user ??= $request->user();

        if (! $user) {
            return null;
        }

        $session = UserSession::query()
            ->where('user_id', $user->id)
            ->where('session_key_hash', $this->sessionHash($request))
            ->whereNull('ended_at')
            ->first();

        return $session ?: $this->start($user, $request);
    }

    public function heartbeat(Request $request, bool $active, ?string $page = null): ?UserSession
    {
        $session = $this->current($request);

        if (! $session) {
            return null;
        }

        $now = now();
        $elapsed = min(120, max(0, $session->last_heartbeat_at?->diffInSeconds($now) ?? 0));
        $session->increment($active ? 'active_seconds' : 'idle_seconds', $elapsed);
        $session->forceFill([
            'last_activity_at' => $active ? $now : $session->last_activity_at,
            'last_heartbeat_at' => $now,
            'last_page' => Str::limit((string) $page, 255, ''),
        ])->save();

        return $session->refresh();
    }

    public function end(Request $request, string $reason = 'logout', ?User $user = null): void
    {
        $user ??= $request->user();
        $session = $user ? $this->currentWithoutCreating($request, $user) : null;

        if (! $session) {
            return;
        }

        $session->update([
            'ended_at' => now(),
            'end_reason' => $reason,
            'last_activity_at' => now(),
        ]);

        $this->audit->recordSystem(
            $reason === 'logout' ? 'auth.logout' : 'auth.session_'.$reason,
            $user,
            ['session_id' => $session->id]
        );
    }

    public function expireStale(): int
    {
        $cutoff = now()->subMinutes(config('activity.session_idle_minutes', 30));
        $sessions = UserSession::query()
            ->with('user')
            ->whereNull('ended_at')
            ->where('last_heartbeat_at', '<', $cutoff)
            ->get();

        foreach ($sessions as $session) {
            $session->update(['ended_at' => now(), 'end_reason' => 'expired']);
            $this->audit->recordSystem('auth.session_expired', $session->user, [
                'session_id' => $session->id,
            ], channel: 'system', userSessionId: $session->id);
        }

        return $sessions->count();
    }

    private function currentWithoutCreating(Request $request, User $user): ?UserSession
    {
        return UserSession::query()
            ->where('user_id', $user->id)
            ->where('session_key_hash', $this->sessionHash($request))
            ->whereNull('ended_at')
            ->first();
    }

    private function sessionHash(Request $request): string
    {
        return hash_hmac('sha256', $request->session()->getId(), (string) config('app.key'));
    }

    private function ipHash(?string $ip): ?string
    {
        return $ip ? hash_hmac('sha256', $ip, (string) config('app.key')) : null;
    }

    private function device(?string $agent): array
    {
        $agent = (string) $agent;
        $device = preg_match('/Mobile|Android|iPhone/i', $agent) ? 'mobile' : 'desktop';
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Otro',
        };
        $platform = match (true) {
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS') => 'macOS',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone'), str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'Otro',
        };

        return [
            'device_type' => $device,
            'browser' => $browser,
            'platform' => $platform,
        ];
    }
}
