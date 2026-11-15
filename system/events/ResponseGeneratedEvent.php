<?php

declare(strict_types=1);

namespace herbie\events;

use herbie\AbstractEvent;
use Psr\Http\Message\ResponseInterface;

final class ResponseGeneratedEvent extends AbstractEvent
{
    public function __construct(private ResponseInterface $response)
    {
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }
}
