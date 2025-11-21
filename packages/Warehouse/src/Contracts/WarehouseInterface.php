<?php

declare(strict_types=1);

namespace Nexus\Warehouse\Contracts;

/**
 * Warehouse entity interface
 */
interface WarehouseInterface
{
    public function getId(): string;
    public function getCode(): string;
    public function getName(): string;
    public function getAddress(): ?string;
    public function isActive(): bool;
    public function getMetadata(): array;
}
