<?php

namespace Tcds\Io\Jackson\Symfony;

use Closure;
use Psr\Container\ContainerInterface as Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Tcds\Io\Generic\Reflection\ReflectionFunction;
use Tcds\Io\Generic\Reflection\Type\Parser\DocBlockTypeResolver;
use Tcds\Io\Generic\Reflection\Type\ReflectionType;
use Tcds\Io\Jackson\Exception\UnableToParseValue;
use Tcds\Io\Jackson\ObjectMapper;
use Tcds\Io\Jackson\Symfony\Attributes\JacksonResponse;

/**
 * @phpstan-type Mapper array{
 *     reader?: callable|null,
 *     writer?: callable|null,
 * }
 * @phpstan-type Mappers array<string, Mapper>
 * @phpstan-type CustomParams Closure(): array<string, mixed>
 * @phpstan-type RequestErrorHandler Closure(UnableToParseValue $e): Response
 */
readonly class JacksonConfig
{
    /** @var Mappers */
    private array $mappers;

    /** @var CustomParams */
    private Closure $customParams;

    /** @var ?RequestErrorHandler */
    private ?Closure $requestErrorHandler;

    public function __construct(
        string $configFilePath,
        private ObjectMapper $mapper,
        private Container $container,
    ) {
        /** @var array{ errors?: array{ request?: RequestErrorHandler }, mappers?: Mappers, params?: CustomParams } $config */
        $config = file_exists($configFilePath)
            ? require $configFilePath
            : [];

        $errors = $config['errors'] ?? [];

        $this->mappers = $config['mappers'] ?? [];
        $this->customParams = $config['params'] ?? fn() => [];
        $this->requestErrorHandler = $errors['request'] ?? null;
    }

    public function readable(string $type): bool
    {
        [$main, $generics] = DocBlockTypeResolver::instance()->genericTypeParts($type);
        $isList = ReflectionType::isList($main);

        if ($isList) {
            $main = $generics[0] ?? 'mixed';
        }

        $config = $this->mappers[$main] ?? null;

        if (null === $config) {
            /**
             * the type was not configured to be read
             */
            return false;
        }

        if (array_key_exists('reader', $config) && $config['reader'] === null) {
            /**
             * the type was configured but the reader was set to null, meaning the type should not be read
             */
            return false;
        }

        return true;
    }

    public function writable(mixed $value, string $returnType, ?JacksonResponse $jacksonResponse = null): bool
    {
        if ($jacksonResponse !== null) {
            return true;
        }

        [$type, $generics] = DocBlockTypeResolver::instance()->genericTypeParts($returnType);
        $type = $type === 'mixed' && is_object($value) ? $value::class : $type;
        $isList = ReflectionType::isList($type);
        $listType = $isList ? $generics[0] ?? 'mixed' : 'mixed';

        return isset($this->mappers[$type]) || ($isList && isset($this->mappers[$listType]));
    }

    /**
     * @return array<string, mixed>
     */
    public function customParams(): array
    {
        $params = ReflectionFunction::call($this->customParams, [
            'container' => $this->container,
            'mapper' => $this->mapper,
        ]);

        if (!is_array($params)) {
            return [];
        }

        $customParams = [];
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $customParams[$key] = $value;
            }
        }

        return $customParams;
    }

    public function handleRequestError(UnableToParseValue $e): Response
    {
        $handler = $this->requestErrorHandler ?? fn(UnableToParseValue $e) => new JsonResponse([
            'message' => $e->getMessage(),
            'expected' => $e->expected,
            'given' => $e->given,
        ], Response::HTTP_BAD_REQUEST);

        return $handler($e);
    }
}
