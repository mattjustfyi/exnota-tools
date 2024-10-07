<?php

namespace App\DTOs;

use Carbon\Carbon;

readonly class ConsentGrant
{
    public function __construct(
        public Carbon $timestamp,
        public string $terms_version,
        public string $privacy_version,
        public string $consent_url,
        public string $consent_language
    ) {}
}
