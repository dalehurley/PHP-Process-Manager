<?php

declare(strict_types=1);

/**
 * Test fixture: echoes provided arguments.
 */
$args = array_slice($argv, 1);
echo implode(' ', $args) . "\n";
exit(0);

