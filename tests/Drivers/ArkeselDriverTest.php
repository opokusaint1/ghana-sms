<?php

declare(strict_types=1);

namespace GhanaSms\Tests\Drivers;

use GhanaSms\Drivers\ArkeselDriver;
use GhanaSms\DTO\Message;
use GhanaSms\Enums\ErrorType;
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

    private static function ok(array $rows): Response
    {
        return new Response(200, [], json_encode(['status' => 'success', 'data' => $rows]));
    }

    public function test_send_success(): void
    {
        $driver = $this->driver(self::ok([['recipient' => '233241234567', 'id' => 'abc123']]));

        $result = $driver->send(new Message('0241234567', 'Your code is 4821'));

        $this->assertTrue($result->success);
        $this->assertSame('abc123', $result->messageId);

        $body = json_decode((string) $this->history[0]['request']->getBody(), true);
        $this->assertSame(['233241234567'], $body['recipients']);
        $this->assertSame('MyApp', $body['sender']);
    }

    public function test_send_failure_returns_typed_error(): void
    {
        $driver = $this->driver(new Response(401, [], json_encode([
            'status'  => 'error',
            'message' => 'Invalid API key',
        ])));

        $result = $driver->send(new Message('0241234567', 'Hi'));

        $this->assertFalse($result->success);
        $this->assertSame('Invalid API key', $result->error);
        $this->assertSame(ErrorType::Authentication, $result->errorType);
    }

    public function test_bulk_same_message_uses_one_request(): void
    {
        $driver = $this->driver(self::ok([
            ['recipient' => '233241234567', 'id' => 'a'],
            ['recipient' => '233201234567', 'id' => 'b'],
        ]));

        $results = $driver->sendBulk([
            new Message('0241234567', 'Hi'),
            new Message('0201234567', 'Hi'),
        ]);

        $this->assertCount(1, $this->history);
        $this->assertSame('a', $results[0]->messageId);
        $this->assertSame('b', $results[1]->messageId);

        $body = json_decode((string) $this->history[0]['request']->getBody(), true);
        $this->assertSame(['233241234567', '233201234567'], $body['recipients']);
    }

    public function test_bulk_different_messages_use_separate_requests(): void
    {
        $driver = $this->driver(
            self::ok([['recipient' => '233241234567', 'id' => 'a']]),
            self::ok([['recipient' => '233201234567', 'id' => 'b']]),
        );

        $results = $driver->sendBulk([
            new Message('0241234567', 'Hello Ama'),
            new Message('0201234567', 'Hello Kofi'),
        ]);

        $this->assertCount(2, $this->history);
        $this->assertSame('a', $results[0]->messageId);
        $this->assertSame('b', $results[1]->messageId);
    }

    public function test_bulk_marks_invalid_numbers_as_failed(): void
    {
        $driver = $this->driver(self::ok([['recipient' => '233241234567', 'id' => 'a']]));

        $results = $driver->sendBulk([
            new Message('0241234567', 'Hi'),
            new Message('123', 'Hi'),
        ]);

        $this->assertTrue($results[0]->success);
        $this->assertFalse($results[1]->success);
        $this->assertSame(ErrorType::InvalidRecipient, $results[1]->errorType);
    }
}
