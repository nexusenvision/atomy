<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Nexus\Manufacturing\Contracts\BomManagerInterface;
use Nexus\Manufacturing\Contracts\BomRepositoryInterface;
use Nexus\Manufacturing\Contracts\BomInterface;
use Nexus\Manufacturing\Enums\BomType;
use Nexus\Manufacturing\Exceptions\BomNotFoundException;
use Nexus\Manufacturing\Exceptions\CircularBomException;
use Nexus\Manufacturing\Exceptions\InvalidBomVersionException;
use Nexus\Manufacturing\ValueObjects\BomLine;

/**
 * BOM Manager implementation.
 *
 * Manages Bill of Materials lifecycle, versioning, and explosion.
 */
final readonly class BomManager implements BomManagerInterface
{
    public function __construct(
        private BomRepositoryInterface $repository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function create(
        string $productId,
        BomType $type,
        array $lines,
        string $version = '1.0',
        ?\DateTimeImmutable $effectiveFrom = null
    ): BomInterface {
        // Validate circular dependencies
        $productIds = array_map(fn (BomLine $line) => $line->productId, $lines);
        $this->validateNoCircularDependency($productId, $productIds);

        return $this->repository->create([
            'productId' => $productId,
            'type' => $type->value,
            'version' => $version,
            'lines' => array_map(fn (BomLine $line) => $line->toArray(), $lines),
            'effectiveFrom' => $effectiveFrom?->format('Y-m-d'),
            'status' => 'draft',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(string $id): BomInterface
    {
        $bom = $this->repository->findById($id);

        if ($bom === null) {
            throw BomNotFoundException::withId($id);
        }

        return $bom;
    }

    /**
     * {@inheritdoc}
     */
    public function findByProductId(string $productId, ?\DateTimeImmutable $effectiveDate = null): BomInterface
    {
        $bom = $this->repository->findByProductId($productId, $effectiveDate);

        if ($bom === null) {
            if ($effectiveDate !== null) {
                throw BomNotFoundException::noEffectiveBom($productId, $effectiveDate->format('Y-m-d'));
            }
            throw BomNotFoundException::withProductId($productId);
        }

        return $bom;
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $id, array $data): BomInterface
    {
        $bom = $this->findById($id);

        if ($bom->getStatus() === 'released') {
            throw InvalidBomVersionException::cannotModify($id, 'released');
        }

        // If updating lines, validate circular dependencies
        if (isset($data['lines'])) {
            $productId = $bom->getProductId();
            $productIds = array_map(fn (array $line) => $line['productId'], $data['lines']);
            $this->validateNoCircularDependency($productId, $productIds);
        }

        return $this->repository->update($id, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function addLine(string $bomId, BomLine $line): BomInterface
    {
        $bom = $this->findById($bomId);

        if ($bom->getStatus() === 'released') {
            throw InvalidBomVersionException::cannotModify($bomId, 'released');
        }

        // Check for circular dependency with new component
        $this->validateNoCircularDependency($bom->getProductId(), [$line->productId]);

        $lines = $bom->getLines();
        $lines[] = $line;

        return $this->repository->update($bomId, [
            'lines' => array_map(fn (BomLine $l) => $l->toArray(), $lines),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function removeLine(string $bomId, int $lineNumber): BomInterface
    {
        $bom = $this->findById($bomId);

        if ($bom->getStatus() === 'released') {
            throw InvalidBomVersionException::cannotModify($bomId, 'released');
        }

        $lines = array_filter(
            $bom->getLines(),
            fn (BomLine $line) => $line->lineNumber !== $lineNumber
        );

        return $this->repository->update($bomId, [
            'lines' => array_map(fn (BomLine $l) => $l->toArray(), array_values($lines)),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function createVersion(string $bomId, string $newVersion, ?\DateTimeImmutable $effectiveFrom = null): BomInterface
    {
        $sourceBom = $this->findById($bomId);
        $productId = $sourceBom->getProductId();

        // Check if version already exists
        $existingVersions = $this->repository->findAllVersions($productId);
        foreach ($existingVersions as $existing) {
            if ($existing->getVersion() === $newVersion) {
                throw InvalidBomVersionException::versionExists($productId, $newVersion);
            }
        }

        return $this->repository->create([
            'productId' => $productId,
            'type' => $sourceBom->getType()->value,
            'version' => $newVersion,
            'lines' => array_map(fn (BomLine $line) => $line->toArray(), $sourceBom->getLines()),
            'effectiveFrom' => $effectiveFrom?->format('Y-m-d'),
            'status' => 'draft',
            'previousVersionId' => $bomId,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function release(string $bomId, \DateTimeImmutable $effectiveFrom): BomInterface
    {
        $bom = $this->findById($bomId);

        if (count($bom->getLines()) === 0) {
            throw InvalidBomVersionException::cannotRelease($bomId, 'BOM has no lines');
        }

        return $this->repository->update($bomId, [
            'status' => 'released',
            'effectiveFrom' => $effectiveFrom->format('Y-m-d'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function obsolete(string $bomId, \DateTimeImmutable $effectiveTo): BomInterface
    {
        $this->findById($bomId);

        return $this->repository->update($bomId, [
            'status' => 'obsolete',
            'effectiveTo' => $effectiveTo->format('Y-m-d'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function explode(
        string $productId,
        float $quantity = 1.0,
        ?\DateTimeImmutable $effectiveDate = null,
        int $maxLevels = 99
    ): array {
        $bom = $this->findByProductId($productId, $effectiveDate);

        return $this->explodeBom($bom, $quantity, $effectiveDate, 0, $maxLevels, []);
    }

    /**
     * {@inheritdoc}
     */
    public function getEffectiveLines(string $bomId, ?\DateTimeImmutable $effectiveDate = null): array
    {
        $bom = $this->findById($bomId);
        $effectiveDate ??= new \DateTimeImmutable();

        return array_filter(
            $bom->getLines(),
            fn (BomLine $line) => $line->isEffectiveAt($effectiveDate)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function compare(string $bomId1, string $bomId2): array
    {
        $bom1 = $this->findById($bomId1);
        $bom2 = $this->findById($bomId2);

        $lines1 = $this->indexLinesByProduct($bom1->getLines());
        $lines2 = $this->indexLinesByProduct($bom2->getLines());

        $allProducts = array_unique(array_merge(
            array_keys($lines1),
            array_keys($lines2)
        ));

        $differences = [
            'added' => [],
            'removed' => [],
            'changed' => [],
        ];

        foreach ($allProducts as $productId) {
            $in1 = isset($lines1[$productId]);
            $in2 = isset($lines2[$productId]);

            if ($in1 && !$in2) {
                $differences['removed'][] = [
                    'productId' => $productId,
                    'quantity' => $lines1[$productId]->quantity,
                ];
            } elseif (!$in1 && $in2) {
                $differences['added'][] = [
                    'productId' => $productId,
                    'quantity' => $lines2[$productId]->quantity,
                ];
            } elseif ($in1 && $in2) {
                $line1 = $lines1[$productId];
                $line2 = $lines2[$productId];

                if (abs($line1->quantity - $line2->quantity) > 0.0001) {
                    $differences['changed'][] = [
                        'productId' => $productId,
                        'oldQuantity' => $line1->quantity,
                        'newQuantity' => $line2->quantity,
                    ];
                }
            }
        }

        return $differences;
    }

    /**
     * {@inheritdoc}
     */
    public function whereUsed(string $productId): array
    {
        return $this->repository->findWhereUsed($productId);
    }

    /**
     * Recursively explode BOM.
     *
     * @param array<string> $visited Products already visited (for circular detection)
     * @return array<array{productId: string, quantity: float, level: int, bomLine: BomLine}>
     */
    private function explodeBom(
        BomInterface $bom,
        float $quantity,
        ?\DateTimeImmutable $effectiveDate,
        int $currentLevel,
        int $maxLevels,
        array $visited
    ): array {
        $result = [];
        $productId = $bom->getProductId();

        // Check for circular dependency
        if (in_array($productId, $visited, true)) {
            throw CircularBomException::withPath([...$visited, $productId]);
        }

        $visited[] = $productId;

        $effectiveLines = $this->getEffectiveLines($bom->getId(), $effectiveDate);

        foreach ($effectiveLines as $line) {
            $requiredQuantity = $line->getQuantityWithScrap() * $quantity;

            $result[] = [
                'productId' => $line->productId,
                'quantity' => $requiredQuantity,
                'level' => $currentLevel + 1,
                'bomLine' => $line,
            ];

            // Recursively explode if there's a BOM and not at max level
            if ($currentLevel < $maxLevels - 1) {
                try {
                    $childBom = $this->repository->findByProductId($line->productId, $effectiveDate);
                    if ($childBom !== null) {
                        $childResults = $this->explodeBom(
                            $childBom,
                            $requiredQuantity,
                            $effectiveDate,
                            $currentLevel + 1,
                            $maxLevels,
                            $visited
                        );
                        $result = [...$result, ...$childResults];
                    }
                } catch (BomNotFoundException) {
                    // No child BOM - this is a raw material
                }
            }
        }

        return $result;
    }

    /**
     * Validate no circular dependencies exist.
     *
     * @param array<string> $componentProductIds
     */
    private function validateNoCircularDependency(string $parentProductId, array $componentProductIds): void
    {
        foreach ($componentProductIds as $componentId) {
            if ($componentId === $parentProductId) {
                throw CircularBomException::withPath([$parentProductId, $componentId]);
            }

            // Check if component has parent as a component (deep check)
            try {
                $componentBom = $this->repository->findByProductId($componentId);
                if ($componentBom !== null) {
                    $this->checkCircularPath($parentProductId, $componentBom, [$parentProductId, $componentId]);
                }
            } catch (BomNotFoundException) {
                // No BOM for component - that's fine
            }
        }
    }

    /**
     * Check for circular path recursively.
     *
     * @param array<string> $path
     */
    private function checkCircularPath(string $targetProductId, BomInterface $bom, array $path): void
    {
        foreach ($bom->getLines() as $line) {
            if ($line->productId === $targetProductId) {
                throw CircularBomException::withPath([...$path, $line->productId]);
            }

            try {
                $childBom = $this->repository->findByProductId($line->productId);
                if ($childBom !== null) {
                    $this->checkCircularPath($targetProductId, $childBom, [...$path, $line->productId]);
                }
            } catch (BomNotFoundException) {
                // No child BOM
            }
        }
    }

    /**
     * Index BOM lines by product ID.
     *
     * @param array<BomLine> $lines
     * @return array<string, BomLine>
     */
    private function indexLinesByProduct(array $lines): array
    {
        $indexed = [];
        foreach ($lines as $line) {
            $indexed[$line->productId] = $line;
        }
        return $indexed;
    }
}
