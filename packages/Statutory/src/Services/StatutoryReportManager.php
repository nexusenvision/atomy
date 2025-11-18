<?php

declare(strict_types=1);

namespace Nexus\Statutory\Services;

use Nexus\Statutory\Contracts\StatutoryReportInterface;
use Nexus\Statutory\Contracts\StatutoryReportRepositoryInterface;
use Nexus\Statutory\Contracts\TaxonomyReportGeneratorInterface;
use Nexus\Statutory\Exceptions\InvalidReportTypeException;
use Nexus\Statutory\Exceptions\ReportNotFoundException;
use Nexus\Statutory\ValueObjects\ReportFormat;
use Psr\Log\LoggerInterface;

/**
 * Service for managing statutory reports.
 */
final class StatutoryReportManager
{
    public function __construct(
        private readonly StatutoryReportRepositoryInterface $repository,
        private readonly TaxonomyReportGeneratorInterface $generator,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Generate a new statutory report.
     *
     * @param string $tenantId The tenant identifier
     * @param string $reportType The report type identifier
     * @param \DateTimeImmutable $startDate Report period start date
     * @param \DateTimeImmutable $endDate Report period end date
     * @param ReportFormat $format Desired output format
     * @param array<string, mixed> $options Additional generation options
     * @return string The report ID
     */
    public function generateReport(
        string $tenantId,
        string $reportType,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ReportFormat $format,
        array $options = []
    ): string {
        $this->logger->info("Generating statutory report", [
            'tenant_id' => $tenantId,
            'report_type' => $reportType,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'format' => $format->value,
        ]);

        return $this->generator->generateReport(
            $tenantId,
            $reportType,
            $startDate,
            $endDate,
            $format,
            $options
        );
    }

    /**
     * Get a statutory report by ID.
     *
     * @param string $reportId The report ID
     * @return StatutoryReportInterface
     * @throws ReportNotFoundException
     */
    public function getReport(string $reportId): StatutoryReportInterface
    {
        $report = $this->repository->findById($reportId);
        
        if ($report === null) {
            throw new ReportNotFoundException($reportId);
        }

        return $report;
    }

    /**
     * Get all reports for a tenant.
     *
     * @param string $tenantId The tenant identifier
     * @param string|null $reportType Optional filter by report type
     * @param \DateTimeImmutable|null $from Optional start date filter
     * @param \DateTimeImmutable|null $to Optional end date filter
     * @return array<StatutoryReportInterface>
     */
    public function getReports(
        string $tenantId,
        ?string $reportType = null,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null
    ): array {
        return $this->repository->getReports($tenantId, $reportType, $from, $to);
    }

    /**
     * Get available report types.
     *
     * @return array<string>
     */
    public function getAvailableReportTypes(): array
    {
        return $this->generator->getSupportedReportTypes();
    }

    /**
     * Validate report data.
     *
     * @param string $reportType The report type
     * @param array<string, mixed> $data The data to validate
     * @return array<string> Validation errors (empty if valid)
     */
    public function validateReportData(string $reportType, array $data): array
    {
        return $this->generator->validateReportData($reportType, $data);
    }
}
