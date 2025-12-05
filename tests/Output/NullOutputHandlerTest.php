<?php

declare(strict_types=1);

namespace DaleHurley\ProcessManager\Tests\Output;

use DaleHurley\ProcessManager\Output\NullOutputHandler;
use DaleHurley\ProcessManager\Output\OutputHandlerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NullOutputHandler::class)]
final class NullOutputHandlerTest extends TestCase
{
    #[Test]
    public function it_implements_output_handler_interface(): void
    {
        $handler = new NullOutputHandler();
        $this->assertInstanceOf(OutputHandlerInterface::class, $handler);
    }

    #[Test]
    public function it_produces_no_output(): void
    {
        $handler = new NullOutputHandler();

        ob_start();
        $handler->scriptAdded('test.php');
        $handler->scriptCompleted('test.php');
        $handler->scriptKilled('test.php');
        $handler->info('message');
        $handler->error('error');
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }
}

