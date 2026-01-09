<?php

namespace Tcds\Io\Jackson\Symfony\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Tcds\Io\Jackson\Exception\UnableToParseValue;

class JacksonExceptionSubscriber implements EventSubscriberInterface
{
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

        $event->setResponse(
            new JsonResponse(
                data: [
                    'message' => $exception->getMessage(),
                    'expected' => $exception->expected,
                    'given' => $exception->given,
                ],
                status: Response::HTTP_BAD_REQUEST,
            ),
        );
    }
}
