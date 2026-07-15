<?php

namespace App\Services\AI;

interface AiSpeechProvider
{
    /**
     * @return array{body: string, mime: string}
     */
    public function synthesize(string $text): array;
}
