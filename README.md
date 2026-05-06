# PHP Jackson for Symfony

[![Integration Test](https://github.com/tcds-io/php-jackson-symfony/actions/workflows/integration.yml/badge.svg)](https://github.com/tcds-io/php-jackson-symfony/actions/workflows/integration.yml)

Symfony integration for [tcds-io/php-jackson](https://github.com/tcds-io/php-jackson), a type-safe object mapper inspired by Jackson (Java).

This package lets you:

- Inject **typed objects** and collections directly into Symfony controllers
- Deserialize from JSON body, query params, form data, and route params
- Automatically serialize your return values back to JSON using PHP-Jackson
- Opt into request and response mapping with PHP attributes

---

## Installation

```bash
composer require tcds-io/php-jackson-symfony
```

Enable the bundle if Symfony Flex did not add it automatically:

```php
// config/bundles.php
return [
    // ...
    Tcds\Io\Jackson\Symfony\JacksonBundle::class => ['all' => true],
];
```

No configuration file is needed for the attribute-based setup. Create one only when you need global mappers or custom params:

```bash
bin/console jackson:configure # creates config/jackson.php
```

---

## How it works

1. Mark request DTO parameters with `#[JacksonInject]`.
2. Mark return values that should be serialized with `#[JacksonResponse]`.
3. The bundle inspects your **method parameter types** and **PHPDoc generics**.
4. It builds those objects from:
    - Route params (`{id}`)
    - Query / form data
    - JSON body
5. Your return value is serialized using PHP-Jackson.

---

## Controller-based injection & response

```php
use Symfony\Component\Routing\Attribute\Route;
use Tcds\Io\Jackson\Symfony\Attributes\JacksonInject;
use Tcds\Io\Jackson\Symfony\Attributes\JacksonResponse;

class FooBarController
{
    /**
     * @param list<Foo> $items
     * @return list<Foo>
     */
    #[Route('/resource', methods: ['POST'])]
    #[JacksonResponse]
    public function list(#[JacksonInject] array $items): array
    {
        return $items;
    }

    #[Route('/resource/{id}', methods: ['POST'])]
    #[JacksonResponse]
    public function read(int $id, #[JacksonInject] Foo $foo): Foo
    {
        return new Foo(
            id: $id,
            a: $foo->a,
            b: $foo->b,
            type: $foo->type,
        );
    }
}
```

---

## Invokable controllers

Symfony routes usually point to controller services. For compact endpoints, use an invokable controller:

```php
use Symfony\Component\Routing\Attribute\Route;
use Tcds\Io\Jackson\Symfony\Attributes\JacksonInject;
use Tcds\Io\Jackson\Symfony\Attributes\JacksonResponse;

#[Route('/greet', methods: ['POST'])]
final readonly class GreetController
{
    #[JacksonResponse(status: 201, headers: ['X-Resource' => 'greeting'])]
    public function __invoke(#[JacksonInject] Greeting $greeting): Greeting
    {
        return $greeting;
    }
}
```

---

## Response status and headers

```php
use Symfony\Component\Routing\Attribute\Route;
use Tcds\Io\Jackson\Symfony\Attributes\JacksonInject;
use Tcds\Io\Jackson\Symfony\Attributes\JacksonResponse;

class GreetController
{
    #[Route('/greet', methods: ['POST'])]
    #[JacksonResponse(status: 201, headers: ['X-Resource' => 'greeting'])]
    public function store(#[JacksonInject] Greeting $greeting): Greeting
    {
        return $greeting;
    }
}
```

- **`#[JacksonInject]`** on a parameter forces php-jackson to deserialize the request payload into that type, even when the type is not registered in `mappers` or has been opted out via `reader: null`.
- **`#[JacksonResponse(status: 201)]`** on a method serializes the return value via php-jackson and wraps it in a Symfony `Response` with the given status and headers. `status` defaults to `200`.

---

## Extending Jackson configuration

The attribute-based API is the default path for request and response mapping, but the bundle also has a central configuration file for application-wide behavior. Use it to add global serialization rules and inject custom params into mapped objects.

### Create config

Create the configuration file when you need global mappers or custom params:

```bash
bin/console jackson:configure # creates config/jackson.php
```

### Add global mappers

Global mappers tell Jackson to always handle a type in requests and responses without adding attributes to every controller method. They are useful when a DTO is part of your app-wide API contract, or when you want to define custom read/write behavior once instead of repeating it at each endpoint.

```php
use App\Services\AuthTokenService;
use Psr\Container\ContainerInterface;
use Tcds\Io\Jackson\ObjectMapper;

return [
    'mappers' => [
        // Simple automatic request and response mapping
        Address::class => [],

        // Custom readers and writers
        Foo::class => [
            'reader' => fn(array $data) => new Foo($data['a'], $data['b']),
            'writer' => fn(Foo $foo) => ['a' => $foo->a, 'b' => $foo->b],
        ],

        // Control how sensitive objects are exposed in responses
        Account::class => [
            'writer' => fn(Account $account) => [
                'id' => $account->id,
                'name' => $account->name,
                // 'apiKey' => $account->apiKey, // exclude sensitive fields
            ],
        ],
    ],
    'params' => fn(ContainerInterface $container, ObjectMapper $mapper) => [
        'userId' => $container->get(AuthTokenService::class)->userId(),
    ],
];
```

With a configured mapper, Jackson can read and write that type without `#[JacksonInject]` or `#[JacksonResponse]`:

```php
use Symfony\Component\Routing\Attribute\Route;

class FooBarController
{
    #[Route('/resource/{id}', methods: ['POST'])]
    public function read(int $id, Foo $foo): Foo
    {
        return new Foo(
            id: $id,
            a: $foo->a,
            b: $foo->b,
            type: $foo->type,
        );
    }
}
```

Responses serialized this way use status `200` and do not support custom headers. Use `#[JacksonResponse]` when an endpoint needs a different status code or response headers.

### Inject custom params

Custom params let you add request-scoped values that do not come from the URL, query string, form data, or JSON body. This is useful for authenticated user IDs, tenant IDs, locale, feature flags, or any value you want available while Jackson builds a request object.

```php
use App\Services\AuthTokenService;
use Psr\Container\ContainerInterface;

return [
    'params' => fn(ContainerInterface $container) => [
        'userId' => $container->get(AuthTokenService::class)->userId(),
    ],
    // ...
];
```

Those values are merged into the data used to build Jackson objects, so a DTO can receive them like any other constructor field:

```php
readonly class InvoiceQuery
{
    public function __construct(
        public int $userId,
        public ?string $customer = null,
    ) {}
}
```

---

## Error handling

If parsing fails, php-jackson-symfony converts a php-jackson `UnableToParseValue` exception into a `400 Bad Request` response by default:

```json
{
  "message": "Unable to parse value at .type",
  "expected": ["AAA", "BBB"],
  "given": "string"
}
```

Set `errors.request` when your API needs a different response shape or status code. The handler receives an `UnableToParseValue` exception and must return a Symfony `Response`.

```php
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Tcds\Io\Jackson\Exception\UnableToParseValue;

return [
    'errors' => [
        'request' => fn(UnableToParseValue $e) => new JsonResponse([
            'error' => $e->getMessage(),
            'hint' => 'Check the request body format.',
        ], Response::HTTP_UNPROCESSABLE_ENTITY),
    ],
    // ...
];
```

The `UnableToParseValue` exception exposes:

- `$e->getMessage()` - human-readable description of the failure
- `$e->expected` - list of accepted values or types
- `$e->given` - the type or value that was received

---

## Development

```bash
composer install
composer tests       # runs cs:check + phpstan
composer cs:fix      # auto-fix code style
```

End-to-end integration tests run a real Symfony app:

```bash
tests/install.sh
cd tests/blog && vendor/bin/phpunit --testdox
```

---

## Related packages

- Core mapper: https://github.com/tcds-io/php-jackson
- Laravel integration: https://github.com/tcds-io/php-jackson-laravel
- Guzzle integration: https://github.com/tcds-io/php-jackson-guzzle
