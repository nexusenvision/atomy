<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nexus\Statutory\Services\StatutoryReportManager;
use Nexus\Statutory\ValueObjects\ReportFormat;
use Psr\Log\LoggerInterface;

/**
 * API Controller for Statutory Reporting features.
 */
final class StatutoryReportController extends Controller
{
    public function __construct(
        private readonly StatutoryReportManager $reportManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Get all statutory reports for a tenant.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|string',
            'report_type' => 'nullable|string',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $reports = $this->reportManager->getReports(
            $validated['tenant_id'],
            $validated['report_type'] ?? null,
            isset($validated['from']) ? new \DateTimeImmutable($validated['from']) : null,
            isset($validated['to']) ? new \DateTimeImmutable($validated['to']) : null
        );

        return response()->json([
            'data' => array_map(fn($report) => [
                'id' => $report->getId(),
                'report_type' => $report->getReportType(),
                'start_date' => $report->getStartDate()->format('Y-m-d'),
                'end_date' => $report->getEndDate()->format('Y-m-d'),
                'format' => $report->getFormat()->value,
                'status' => $report->getStatus(),
                'generated_at' => $report->getGeneratedAt()?->format(\DateTimeInterface::ATOM),
            ], $reports),
        ]);
    }

    /**
     * Generate a new statutory report.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|string',
            'report_type' => 'required|string|in:profit_loss,balance_sheet,trial_balance',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'format' => 'required|string|in:JSON,XML,CSV,XBRL',
            'account_data' => 'required|array',
            'options' => 'array',
        ]);

        try {
            $reportId = $this->reportManager->generateReport(
                $validated['tenant_id'],
                $validated['report_type'],
                new \DateTimeImmutable($validated['start_date']),
                new \DateTimeImmutable($validated['end_date']),
                ReportFormat::from($validated['format']),
                $validated['account_data'],
                $validated['options'] ?? []
            );

            return response()->json([
                'message' => 'Report generated successfully',
                'data' => [
                    'report_id' => $reportId,
                ],
            ], 201);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate report', [
                'error' => $e->getMessage(),
                'report_type' => $validated['report_type'],
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Generate a report with full metadata.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateWithMetadata(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|string',
            'report_type' => 'required|string|in:profit_loss,balance_sheet,trial_balance',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'format' => 'required|string|in:JSON,XML,CSV,XBRL',
            'account_data' => 'required|array',
            'options' => 'array',
        ]);

        try {
            $result = $this->reportManager->generateReportWithMetadata(
                $validated['tenant_id'],
                $validated['report_type'],
                new \DateTimeImmutable($validated['start_date']),
                new \DateTimeImmutable($validated['end_date']),
                ReportFormat::from($validated['format']),
                $validated['account_data'],
                $validated['options'] ?? []
            );

            return response()->json([
                'message' => 'Report with metadata generated successfully',
                'data' => $result,
            ], 201);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate report with metadata', [
                'error' => $e->getMessage(),
                'report_type' => $validated['report_type'],
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get a specific report by ID.
     *
     * @param string $reportId
     * @return JsonResponse
     */
    public function show(string $reportId): JsonResponse
    {
        try {
            $report = $this->reportManager->getReport($reportId);

            return response()->json([
                'data' => [
                    'id' => $report->getId(),
                    'tenant_id' => $report->getTenantId(),
                    'report_type' => $report->getReportType(),
                    'start_date' => $report->getStartDate()->format('Y-m-d'),
                    'end_date' => $report->getEndDate()->format('Y-m-d'),
                    'format' => $report->getFormat()->value,
                    'status' => $report->getStatus(),
                    'file_path' => $report->getFilePath(),
                    'generated_by' => $report->getGeneratedBy(),
                    'generated_at' => $report->getGeneratedAt()?->format(\DateTimeInterface::ATOM),
                ],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve report', [
                'error' => $e->getMessage(),
                'report_id' => $reportId,
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get available report types.
     *
     * @return JsonResponse
     */
    public function getReportTypes(): JsonResponse
    {
        $reportTypes = $this->reportManager->getAvailableReportTypes();

        return response()->json([
            'data' => $reportTypes,
        ]);
    }
}
