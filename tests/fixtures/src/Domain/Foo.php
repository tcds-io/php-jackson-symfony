<?php

namespace App\Domain;

readonly class Foo
{
    public function __construct(
        public ?int $id,
        public string $a,
        public string $b,
        public Type $type,
    ) {}
}
