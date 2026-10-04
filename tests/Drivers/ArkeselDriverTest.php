<?php

declare(strict_types=1);

namespace GhanaSms\Tests\Drivers;

use GhanaSms\Drivers\ArkeselDriver;
use GhanaSms\DTO\Message;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class ArkeselDriverTest extends TestCase
{
    private array $history = [];

    private function driver(Response ...$responses): ArkeselDriver
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        return new ArkeselDriver(new Client(['handler' => $stack]), 'test-key', 'MyApp');
    }

    public function test_send_success(): void
    {
        $driver = $this->driver(new Response(200, [], json_encode([
            'status' => 'success',
            'data'   => [['recipient' => '233241234567', 'id' => 'abc123']],
        ])));

        $result = $driver->send(new Message('0241234567', 'Your code is 4821'));

        $this->assertTrue($result->success);
        $this->assertSame('abc123', $result->messageId);

        $body = json_decode((string) $this->history[0]['request']->getBody(), true);
        $this->assertSame(['233241234567'], $body['recipients']);
        $this->assertSame('MyApp', $body['sender']);
    }

    public function test_send_failure_returns_error(): void
    {
        $driver = $this->driver(new Response(401, [], json_encode([
            'status'  => 'error',
            'message' => 'Invalid API key',
        ])));

        $result = $driver->send(new Message('0241234567', 'Hi'));

        $this->assertFalse($result->success);
        $this->assertSame('Invalid API key', $result->error);
    }
}
