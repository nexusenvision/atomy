<?php

declare(strict_types=1);

namespace Nexus\Warehouse\Contracts;

/**
 * Bin location repository contract
 */
interface BinLocationRepositoryInterface
{
    /**
     * Find bin location by ID
     * 
     * @return BinLocationInterface|null
     */
    public function findById(string $id): ?BinLocationInterface;
    
    /**
     * Find bin location by code within warehouse
     * 
     * @return BinLocationInterface|null
     */
    public function findByCode(string $warehouseId, string $code): ?BinLocationInterface;
    
    /**
     * Find all active bin locations in warehouse
     * 
     * @return array<BinLocationInterface>
     */
    public function findByWarehouse(string $warehouseId): array;
    
    /**
     * Save bin location entity
     */
    public function save(BinLocationInterface $binLocation): void;
    
    /**
     * Delete bin location by ID
     */
    public function delete(string $id): void;
}
