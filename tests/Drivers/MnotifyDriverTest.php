<?php

declare(strict_types=1);

namespace GhanaSms\Tests\Drivers;

use GhanaSms\Drivers\MnotifyDriver;
use GhanaSms\DTO\Message;
use GhanaSms\Enums\ErrorType;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class MnotifyDriverTest extends TestCase
{
    private array $history = [];

    private function driver(Response ...$responses): MnotifyDriver
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        return new MnotifyDriver(new Client(['handler' => $stack]), 'test-key', 'MyApp');
    }

    public function test_send_success(): void
    {
        $driver = $this->driver(new Response(200, [], json_encode([
            'status'  => 'success',
            'code'    => '2000',
            'message' => 'Message sent successfully',
            'summary' => ['message_id' => 'msg-1', 'total_sent' => 1],
        ])));

        $result = $driver->send(new Message('0241234567', 'Hello'));

        $this->assertTrue($result->success);
        $this->assertSame('msg-1', $result->messageId);

        $request = $this->history[0]['request'];
        $this->assertStringContainsString('key=test-key', $request->getUri()->getQuery());

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame(['233241234567'], $body['recipient']);
        $this->assertSame('MyApp', $body['sender']);
    }

    public function test_real_invalid_key_response_is_parsed(): void
    {
        // This is the actual error shape mNotify returned during live testing.
        $driver = $this->driver(new Response(401, [], json_encode([
            'error' => 'invalid api key. please make sure your api key is valid and enabled',
        ])));

        $result = $driver->send(new Message('0241234567', 'Hi'));

        $this->assertFalse($result->success);
        $this->assertStringContainsString('invalid api key', (string) $result->error);
        $this->assertSame(ErrorType::Authentication, $result->errorType);
    }

    public function test_bulk_same_message_uses_one_request(): void
    {
        $driver = $this->driver(new Response(200, [], json_encode([
            'status'  => 'success',
            'summary' => ['message_id' => 'batch-1'],
        ])));

        $results = $driver->sendBulk([
            new Message('0241234567', 'Hi'),
            new Message('0201234567', 'Hi'),
        ]);

        $this->assertCount(1, $this->history);
        $this->assertSame('batch-1', $results[0]->messageId);
        $this->assertSame('batch-1', $results[1]->messageId);

        $body = json_decode((string) $this->history[0]['request']->getBody(), true);
        $this->assertSame(['233241234567', '233201234567'], $body['recipient']);
    }
}
