<?php

namespace Tcds\Io\Jackson\Symfony\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Tcds\Io\Jackson\Exception\UnableToParseValue;
use Tcds\Io\Jackson\Symfony\JacksonConfig;

class JacksonExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly JacksonConfig $config) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onException'];
    }

    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof UnableToParseValue) {
            return;
        }

        $event->setResponse($this->config->handleRequestError($exception));
    }
}
