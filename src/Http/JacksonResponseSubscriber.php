<?php

namespace Tcds\Io\Jackson\Symfony\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Tcds\Io\Generic\Reflection\ReflectionClass;
use Tcds\Io\Jackson\JsonObjectMapper;
use Tcds\Io\Jackson\Symfony\Attributes\JacksonResponse;
use Tcds\Io\Jackson\Symfony\JacksonConfig;

final readonly class JacksonResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private JsonObjectMapper $mapper,
        private JacksonConfig $config,
    ) {}

    public static function getSubscribedEvents(): array
    {
        // Run early enough to win, but after other view listeners if you prefer.
        return [
            KernelEvents::VIEW => ['onKernelView', 0],
        ];
    }

    public function onKernelView(ViewEvent $event): void
    {
        $value = $event->getControllerResult();
        $returnType = $this->getReturnType($event->controllerArgumentsEvent);
        $jacksonResponse = $this->getJacksonResponse($event->controllerArgumentsEvent);

        if (!$this->config->writable($value, $returnType, $jacksonResponse)) {
            return;
        }

        $content = $this->mapper->writeValue($value);
        $response = new Response(
            content: $content,
            status: $jacksonResponse->status ?? Response::HTTP_OK,
            headers: array_merge(['Content-Type' => 'application/json'], $jacksonResponse->headers ?? []),
        );
        $event->setResponse($response);
    }

    private function getJacksonResponse(?ControllerArgumentsEvent $event): ?JacksonResponse
    {
        return ($event?->getAttributes(JacksonResponse::class)[0] ?? null);
    }

    private function getReturnType(?ControllerArgumentsEvent $event): string
    {
        $controllerData = $event?->getController();

        if (!is_array($controllerData)) {
            return 'mixed';
        }

        $controller = $controllerData[0] ?? null;
        $method = $controllerData[1] ?? null;

        if (!is_object($controller) || !is_string($method)) {
            return 'mixed';
        }

        $reflection = new ReflectionClass($controller::class);
        $method = $reflection->getMethod($method);

        return $method->getReturnType()->getName();
    }
}
