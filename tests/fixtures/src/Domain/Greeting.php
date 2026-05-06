<?php

namespace App\Domain;

readonly class Greeting
{
    public function __construct(
        public string $message,
    ) {}
}
