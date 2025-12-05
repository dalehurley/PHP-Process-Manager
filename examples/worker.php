<?php

declare(strict_types=1);

/**
 * Example worker script that simulates work by sleeping for a random duration.
 */

$sleepTime = random_int(1, 6);
sleep($sleepTime);

// Exit successfully
exit(0);
