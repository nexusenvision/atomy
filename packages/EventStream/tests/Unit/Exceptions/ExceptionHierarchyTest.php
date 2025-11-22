<?php

declare(strict_types=1);

namespace Nexus\EventStream\Tests\Unit\Exceptions;

use Nexus\EventStream\Exceptions\EventStreamException;
use Nexus\EventStream\Exceptions\StreamNotFoundException;
use Nexus\EventStream\Exceptions\SnapshotNotFoundException;
use Nexus\EventStream\Exceptions\InvalidSnapshotException;
use Nexus\EventStream\Exceptions\ProjectionException;
use Nexus\EventStream\Exceptions\EventSerializationException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;

#[Group('eventstream')]
#[Group('exceptions')]
final class ExceptionHierarchyTest extends TestCase
{
    #[Test]
    public function base_exception_extends_php_exception(): void
    {
        $exception = new EventStreamException('Test message');

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    #[Test]
    #[DataProvider('exceptionClassProvider')]
    public function all_exceptions_extend_base_exception(string $exceptionClass): void
    {
        $exception = new $exceptionClass('Test message');

        $this->assertInstanceOf(EventStreamException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public static function exceptionClassProvider(): array
    {
        return [
            'StreamNotFoundException' => [StreamNotFoundException::class],
            'SnapshotNotFoundException' => [SnapshotNotFoundException::class],
            'InvalidSnapshotException' => [InvalidSnapshotException::class],
            'ProjectionException' => [ProjectionException::class],
            'EventSerializationException' => [EventSerializationException::class],
        ];
    }

    #[Test]
    public function stream_not_found_exception_has_descriptive_message(): void
    {
        $exception = new StreamNotFoundException('stream-123');

        $this->assertStringContainsString('stream-123', $exception->getMessage());
        $this->assertStringContainsString('not found', strtolower($exception->getMessage()));
    }

    #[Test]
    public function snapshot_not_found_exception_has_descriptive_message(): void
    {
        $exception = new SnapshotNotFoundException('aggregate-456');

        $this->assertStringContainsString('aggregate-456', $exception->getMessage());
        $this->assertStringContainsString('snapshot', strtolower($exception->getMessage()));
    }

    #[Test]
    public function invalid_snapshot_exception_includes_checksum_info(): void
    {
        $exception = new InvalidSnapshotException('snapshot-789', 'expected-hash', 'actual-hash');

        $message = strtolower($exception->getMessage());
        $this->assertStringContainsString('snapshot-789', $exception->getMessage());
        $this->assertStringContainsString('expected-hash', $exception->getMessage());
        $this->assertStringContainsString('actual-hash', $exception->getMessage());
        $this->assertStringContainsString('checksum', $message);
    }

    #[Test]
    public function projection_exception_supports_previous_exception(): void
    {
        $previous = new \RuntimeException('Database error');
        $exception = new ProjectionException('Projection failed', $previous);

        $this->assertSame($previous, $exception->getPrevious());
        $this->assertStringContainsString('Projection failed', $exception->getMessage());
    }

    #[Test]
    public function event_serialization_exception_includes_event_type(): void
    {
        $exception = new EventSerializationException('AccountDebitedEvent', 'Invalid JSON');

        $this->assertStringContainsString('AccountDebitedEvent', $exception->getMessage());
        $this->assertStringContainsString('Invalid JSON', $exception->getMessage());
    }
}
