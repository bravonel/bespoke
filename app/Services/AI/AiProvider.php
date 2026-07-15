<?php

namespace App\Services\AI;

interface AiProvider
{
    public function respond(string $instructions, string $input): string;
}
