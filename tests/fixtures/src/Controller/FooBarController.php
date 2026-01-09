<?php

namespace App\Controller;

use App\Domain\Foo;
use App\Queries\InvoiceQuery;
use Symfony\Component\Routing\Attribute\Route;

class FooBarController
{
    /**
     * @param list<Foo> $items
     * @return list<Foo>
     */
    #[Route('/controller', methods: ['POST'])]
    public function list(array $items): array
    {
        return $items;
    }

    #[Route('/controller/{id}', methods: ['POST'])]
    public function read(int $id, Foo $foo): Foo
    {
        return new Foo(
            id: $id,
            a: $foo->a,
            b: $foo->b,
            type: $foo->type,
        );
    }

    #[Route('/invoices/{id}', methods: ['POST'])]
    public function invoices(InvoiceQuery $query): InvoiceQuery
    {
        return $query;
    }
}
