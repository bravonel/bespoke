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

            $html .= '<a href="'.e($href).'" target="_blank" rel="noopener noreferrer" class="inline break-all font-medium underline decoration-current/30 underline-offset-2 hover:decoration-current" style="color:var(--brand-amber)">'.e($url).'</a>';
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
