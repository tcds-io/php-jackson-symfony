<?php

namespace Tcds\Io\Jackson\Symfony\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Tcds\Io\Generic\BetterGenericException;
use Tcds\Io\Generic\Reflection\ReflectionMethod;
use Tcds\Io\Generic\Reflection\Type\ReflectionType;
use Tcds\Io\Jackson\Exception\JacksonException;
use Tcds\Io\Jackson\ObjectMapper;
use Tcds\Io\Jackson\Symfony\JacksonConfig;

final readonly class JacksonArgumentResolver implements ValueResolverInterface
{
    public function __construct(private ObjectMapper $mapper, private JacksonConfig $config)
    {
    }

    /**
     * @throws JacksonException
     * @throws BetterGenericException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($this->config->readable($argument->getType())) {
            return [
                $this->parseSerializableType(
                    type: $argument->getType(),
                    isList: false,
                    request: $request,
                ),
            ];
        }

        $type = $this->annotated($argument);

        if ($this->config->readable($type)) {
            return [
                $this->parseSerializableType(
                    type: $type,
                    isList: ReflectionType::isList($type),
                    request: $request,
                ),
            ];
        }

        return [];
    }

    /**
     * @throws BetterGenericException
     */
    private function annotated(ArgumentMetadata $argument): string
    {
        $method = ReflectionMethod::createFromMethodName($argument->getControllerName());
        $param = $method->getParameter($argument->getName());

        return $param->getType()->getName();
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @return T
     * @throws JacksonException
     */
    private function parseSerializableType(string $type, bool $isList, Request $request): mixed
    {
        return $this->mapper->readValue(
            type: $type,
            value: $this->getRequestData($isList, $request),
        );
    }

    /**
     * @return ($isList is true ? list<mixed> : array<string, mixed>)
     */
    private function getRequestData(bool $isList, Request $request): array
    {
        return $isList
            // when desired type is list, then grab only payload because
            // query and path params will mess up with the list payload
            ? $request->request->all()
            // return the whole request merged into a single array
            : array_merge(
                $this->config->customParams(),
                $request->query->all(),
                $request->request->all(),
                $request->attributes->get('_route_params', []),
            );
    }
}
