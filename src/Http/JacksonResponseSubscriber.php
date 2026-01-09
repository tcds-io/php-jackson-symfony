<?php

namespace Tcds\Io\Jackson\Symfony\Http;

use ReflectionException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Tcds\Io\Generic\Reflection\ReflectionClass;
use Tcds\Io\Jackson\JsonObjectMapper;
use Tcds\Io\Jackson\Symfony\JacksonConfig;

final readonly class JacksonResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private JsonObjectMapper $mapper,
        private JacksonConfig $config,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Run early enough to win, but after other view listeners if you prefer.
        return [
            KernelEvents::VIEW => ['onKernelView', 0],
        ];
    }

    /**
     * @throws ReflectionException
     */
    public function onKernelView(ViewEvent $event): void
    {
        $value = $event->getControllerResult();
        $returnType = $this->getReturnType($event->controllerArgumentsEvent);

        if (!$this->config->writable($value, $returnType)) {
            return;
        }

        $content = $this->mapper->writeValue($value);
        $response = new Response($content, Response::HTTP_OK, ['Content-Type' => 'application/json']);
        $event->setResponse($response);
    }

    /**
     * @throws ReflectionException
     */
    private function getReturnType(?ControllerArgumentsEvent $event): string
    {
        $controller = $event?->getController();

        if (!$controller || !is_array($controller)) {
            return 'mixed';
        }

        [$controller, $method] = $event->getController();
        $reflection = new ReflectionClass($controller::class);
        $method = $reflection->getMethod($method);

        return $method->getReturnType()->getName();
    }
}
