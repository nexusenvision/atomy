<?php

declare(strict_types=1);

namespace Nexus\Warehouse\Contracts;

/**
 * Warehouse repository contract
 */
interface WarehouseRepositoryInterface
{
    /**
     * Find warehouse by ID
     * 
     * @return WarehouseInterface|null
     */
    public function findById(string $id): ?WarehouseInterface;
    
    /**
     * Find warehouse by code within tenant
     * 
     * @return WarehouseInterface|null
     */
    public function findByCode(string $tenantId, string $code): ?WarehouseInterface;
    
    /**
     * Find all active warehouses for a tenant
     * 
     * @return array<WarehouseInterface>
     */
    public function findByTenant(string $tenantId): array;
    
    /**
     * Save warehouse entity
     */
    public function save(WarehouseInterface $warehouse): void;
    
    /**
     * Delete warehouse by ID
     */
    public function delete(string $id): void;
}
