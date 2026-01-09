# PHP Jackson for Symfony

Symfony integration for [tcds-io/php-jackson](https://github.com/tcds-io/php-jackson), a type-safe object mapper inspired by Jackson (Java).

This package lets you:

- Inject **typed objects** (and collections) directly into controllers and route callables
- Deserialize from JSON body, query params, form data, and route params
- Automatically serialize your return values back to JSON using PHP-Jackson

---

## 🚀 Installation

```bash
composer require tcds-io/php-jackson-symfony
```

Then create the configuration file:
```bash
bin/console jackson:configure # config/jackson.php
```

---

## ⚙️ How it works

1. The plugin inspects your **method parameter types** and **PHPDoc generics**.
2. It builds those objects from:
    - Route params (`{id}`)
    - Query / form data
    - JSON body
3. Your return value is serialized automatically using PHP‑Jackson.

---

## 🧩 Controller-based injection & response

```php
// Your controller
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

// config/jackson.php
/**
 * @returns array{
 *     mappers: array<class-string, array{
 *         reader?: callable(mixed $value, string $type, ObjectMapper $mapper, array $path): mixed,
 *         writer?: callable(mixed $data, string $type, ObjectMapper $mapper, array $path): mixed,
 *     }>,
 *     params?: callable(Container $container, ObjectMapper $mapper): array
 * }
 */
return [
    'mappers' => [
        App\Domain\Foo::class => [],
        App\Queries\InvoiceQuery::class => [],
    ],
    'params' => function (Container $container) {
        $authService = $container->get(AuthTokenService::class);

        return $authService->getClaims();
    },
];

```
---

## 🛠 Configuring Serializable Objects

To enable automatic request → object → response mapping, register your serializable classes in:

```
config/jackson.php
```

### Example configuration

```php
return [
    'mappers' => [
        // Simple automatic serialization
        Address::class => [],
    
        // Custom readers and writers
        Foo::class => [
            'reader' => fn(array $data) => new Foo($data['a'], $data['b']),
            'writer' => fn(Foo $foo) => ['a' => $foo->a, 'b' => $foo->b],
        ],
    
        // Use Laravel's Auth system to inject the authenticated user
        User::class => [
            // Any controller that includes `User $user` will automatically receive `Auth::user()`.
            'reader' => fn () => Auth::user(),
    
            // Optional: control what is exposed in API responses.
            // Responses containing a `User` instance will use your custom `writer` output.
            'writer' => fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                // 'email' => $user->email, // exclude sensitive fields
            ],
        ],
        
        // Other classes:
        // - Unregistered classes cannot be serialized or deserialized (security-by-default).
    ],
    'params' => function (Container $container, ObjectMapper $mapper) {
        return [
            // Any custom data you want to be injected into serializable classes
        ];
    },
];
```
---

## 🧪 Error handling

If parsing fails, php-jackson-symfony converts php-jackson `UnableToParseValue`  into `400 Bad Request` HTTP error responses, ex:
```json
{
  "message": "Unable to parse value at .type",
  "expected": ["AAA", "BBB"],
  "given": "string"
}
```
---

## 📦 Related packages

- Core mapper: https://github.com/tcds-io/php-jackson
- Laravel integration: https://github.com/tcds-io/php-jackson-laravel
- Guzzle integration: https://github.com/tcds-io/php-jackson-guzzle  
