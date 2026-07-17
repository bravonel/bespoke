<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

final class LinkedText
{
    public static function render(?string $text): HtmlString
    {
        if ($text === null || $text === '') {
            return new HtmlString('');
        }

        $html = '';
        $offset = 0;
        $pattern = '~(?:https?://|www\.)[^\s<>"\']+~iu';

        preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$rawUrl, $position]) {
            $html .= e(substr($text, $offset, $position - $offset));

            [$url, $trailing] = self::withoutTrailingPunctuation($rawUrl);
            $href = Str::startsWith(Str::lower($url), ['http://', 'https://'])
                ? $url
                : 'https://'.$url;
            $host = parse_url($href, PHP_URL_HOST) ?: 'enlace externo';
            $label = 'Abrir enlace a '.Str::replaceStart('www.', '', $host);

            $html .= '<a href="'.e($href).'" target="_blank" rel="noopener noreferrer" aria-label="'.e($label).'" title="'.e($label).'" class="mx-1 inline-flex h-7 w-7 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 align-middle text-amber-700 transition hover:-translate-y-0.5 hover:border-amber-300 hover:bg-amber-100 hover:text-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2">'
                .'<svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
                .'<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>'
                .'</svg><span class="sr-only">'.e($label).'</span></a>';
            $html .= e($trailing);

            $offset = $position + strlen($rawUrl);
        }

        $html .= e(substr($text, $offset));

        return new HtmlString($html);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function withoutTrailingPunctuation(string $url): array
    {
        $trailing = '';

        while ($url !== '' && preg_match('/[.,;:!?]$/u', $url)) {
            $trailing = substr($url, -1).$trailing;
            $url = substr($url, 0, -1);
        }

        return [$url, $trailing];
    }
}
