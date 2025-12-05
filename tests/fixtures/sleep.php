<?php

declare(strict_types=1);

/**
 * Test fixture: sleeps for specified duration.
 * Usage: php sleep.php [seconds]
 */
$seconds = (int) ($argv[1] ?? 2);
sleep($seconds);
echo "Slept for {$seconds} seconds\n";
exit(0);

