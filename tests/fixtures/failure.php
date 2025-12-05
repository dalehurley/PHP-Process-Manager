<?php

declare(strict_types=1);

/**
 * Test fixture: exits with error code.
 */
fwrite(STDERR, "Error output\n");
exit(1);

