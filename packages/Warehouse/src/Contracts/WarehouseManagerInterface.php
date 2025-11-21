<?php

declare(strict_types=1);

namespace Nexus\Warehouse\Contracts;

/**
 * Warehouse management interface
 */
interface WarehouseManagerInterface
{
    /**
     * Create warehouse
     */
    public function createWarehouse(string $code, string $name, array $metadata = []): string;
    
    /**
     * Get warehouse details
     */
    public function getWarehouse(string $warehouseId): array;
    
    /**
     * List warehouses for tenant
     */
    public function listWarehouses(): array;
}
