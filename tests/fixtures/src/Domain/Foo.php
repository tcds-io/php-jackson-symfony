<?php

namespace App\Domain;

readonly class Foo
{
    public function __construct(
        public ?int $id = null,
        public string $a,
        public string $b,
        public Type $type,
    ) {
    }
}
