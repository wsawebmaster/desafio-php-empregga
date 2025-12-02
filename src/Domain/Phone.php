<?php
declare(strict_types=1);

namespace App\Domain;

class Phone
{
    public function __construct(
        public readonly int $id,
        public readonly int $contactId,
        public readonly string $number,
        public readonly ?string $label = null,
    ) {
    }
}
