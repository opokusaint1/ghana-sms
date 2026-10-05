<?php

declare(strict_types=1);

namespace GhanaSms\Tests\Drivers;

use GhanaSms\Drivers\HubtelDriver;
use GhanaSms\DTO\Message;
use GhanaSms\Enums\ErrorType;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class HubtelDriverTest extends TestCase
{
    private array $history = [];

    private function driver(Response ...$responses): HubtelDriver
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        return new HubtelDriver(new Client(['handler' => $stack]), 'my-id', 'my-secret', 'MyApp');
    }

    private static function ok(array $rows): Response
    {
        return new Response(200, [], json_encode(['batchId' => 'b1', 'status' => 0, 'data' => $rows]));
    }

    public function test_send_success(): void
    {
        $driver = $this->driver(self::ok([
            ['recipient' => '233241234567', 'content' => 'Hi', 'messageId' => 'm1'],
        ]));

        $result = $driver->send(new Message('0241234567', 'Hi'));

        $this->assertTrue($result->success);
        $this->assertSame('m1', $result->messageId);

        $request = $this->history[0]['request'];
        $this->assertSame(
            'Basic ' . base64_encode('my-id:my-secret'),
            $request->getHeaderLine('Authorization')
        );
        $this->assertStringEndsWith('/v1/messages/batch/simple/send', (string) $request->getUri());

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('MyApp', $body['From']);
        $this->assertSame(['233241234567'], $body['Recipients']);
        $this->assertSame('Hi', $body['Content']);
    }

    public function test_bulk_same_message_uses_one_request(): void
    {
        $driver = $this->driver(self::ok([
            ['recipient' => '233241234567', 'content' => 'Hi', 'messageId' => 'm1'],
            ['recipient' => '233201234567', 'content' => 'Hi', 'messageId' => 'm2'],
        ]));

        $results = $driver->sendBulk([
            new Message('0241234567', 'Hi'),
            new Message('0201234567', 'Hi'),
        ]);

        $this->assertCount(1, $this->history);
        $this->assertSame('m1', $results[0]->messageId);
        $this->assertSame('m2', $results[1]->messageId);
    }

    public function test_unauthorized_with_empty_body_is_an_authentication_error(): void
    {
        $driver = $this->driver(new Response(401, [], ''));

        $result = $driver->send(new Message('0241234567', 'Hi'));

        $this->assertFalse($result->success);
        $this->assertSame(ErrorType::Authentication, $result->errorType);
    }

    public function test_error_status_with_message_is_classified(): void
    {
        // Assumed error shape; adjust once seen live.
        $driver = $this->driver(new Response(200, [], json_encode([
            'status'  => 1,
            'message' => 'Insufficient balance',
        ])));

        $result = $driver->send(new Message('0241234567', 'Hi'));

        $this->assertFalse($result->success);
        $this->assertSame('Insufficient balance', $result->error);
        $this->assertSame(ErrorType::InsufficientBalance, $result->errorType);
    }

    public function test_balance_is_not_supported(): void
    {
        $this->assertNull($this->driver()->balance());
    }
}
