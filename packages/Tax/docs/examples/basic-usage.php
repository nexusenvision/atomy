<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Tax
 * 
 * This example demonstrates basic package usage.
 */

use Nexus\Tax\Contracts\ManagerInterface;

// Inject the manager
$manager = app(ManagerInterface::class);

// Use the package
$result = $manager->someMethod();

echo "Result: " . print_r($result, true) . "\n";
