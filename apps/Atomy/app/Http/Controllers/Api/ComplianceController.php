<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nexus\Compliance\Services\ComplianceManager;
use Nexus\Compliance\Services\ConfigurationAuditor;
use Nexus\Compliance\Services\SodManager;
use Nexus\Compliance\ValueObjects\SeverityLevel;
use Psr\Log\LoggerInterface;

/**
 * API Controller for Compliance features.
 */
final class ComplianceController extends Controller
{
    public function __construct(
        private readonly ComplianceManager $complianceManager,
        private readonly SodManager $sodManager,
        private readonly ConfigurationAuditor $configurationAuditor,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Get all active compliance schemes for a tenant.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getActiveSchemes(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');

        $schemes = $this->complianceManager->getActiveSchemes($tenantId);

        return response()->json([
            'data' => $schemes,
        ]);
    }

    /**
     * Activate a compliance scheme.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function activateScheme(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|string',
            'scheme_name' => 'required|string|in:ISO14001,SOX,GDPR,HIPAA,PCI_DSS',
            'configuration' => 'array',
        ]);

        try {
            $this->complianceManager->activateScheme(
                $validated['tenant_id'],
                $validated['scheme_name'],
                $validated['configuration'] ?? []
            );

            return response()->json([
                'message' => "Scheme {$validated['scheme_name']} activated successfully",
            ], 201);
        } catch (\Exception $e) {
            $this->logger->error('Failed to activate scheme', [
                'error' => $e->getMessage(),
                'scheme_name' => $validated['scheme_name'],
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Deactivate a compliance scheme.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deactivateScheme(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|string',
            'scheme_name' => 'required|string',
        ]);

        try {
            $this->complianceManager->deactivateScheme(
                $validated['tenant_id'],
                $validated['scheme_name']
            );

            return response()->json([
                'message' => "Scheme {$validated['scheme_name']} deactivated successfully",
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to deactivate scheme', [
                'error' => $e->getMessage(),
                'scheme_name' => $validated['scheme_name'],
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create a new SOD rule.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createSodRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|string',
            'rule_name' => 'required|string',
            'transaction_type' => 'required|string',
            'severity_level' => 'required|string|in:INFO,LOW,MEDIUM,HIGH,CRITICAL',
            'creator_role' => 'nullable|string',
            'approver_role' => 'nullable|string',
            'constraints' => 'array',
        ]);

        try {
            $rule = $this->sodManager->createRule(
                $validated['tenant_id'],
                $validated['rule_name'],
                $validated['transaction_type'],
                SeverityLevel::from($validated['severity_level']),
                $validated['creator_role'] ?? null,
                $validated['approver_role'] ?? null,
                $validated['constraints'] ?? []
            );

            return response()->json([
                'message' => 'SOD rule created successfully',
                'data' => [
                    'rule_id' => $rule->getId(),
                    'rule_name' => $rule->getRuleName(),
                ],
            ], 201);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create SOD rule', [
                'error' => $e->getMessage(),
                'rule_name' => $validated['rule_name'],
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Validate a transaction against SOD rules.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateTransaction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|string',
            'transaction_type' => 'required|string',
            'creator_id' => 'required|string',
            'approver_id' => 'required|string',
            'transaction_data' => 'array',
        ]);

        try {
            $result = $this->sodManager->validateTransaction(
                $validated['tenant_id'],
                $validated['transaction_type'],
                $validated['creator_id'],
                $validated['approver_id'],
                $validated['transaction_data'] ?? []
            );

            return response()->json([
                'is_valid' => $result['is_valid'],
                'violations' => $result['violations'],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to validate transaction', [
                'error' => $e->getMessage(),
                'transaction_type' => $validated['transaction_type'],
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Audit configuration against a compliance scheme.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function auditConfiguration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|string',
            'scheme_name' => 'required|string',
            'system_configuration' => 'array',
        ]);

        try {
            // Get the scheme
            $scheme = $this->complianceManager->getScheme(
                $validated['tenant_id'],
                $validated['scheme_name']
            );

            if ($scheme === null) {
                return response()->json([
                    'error' => 'Scheme not found',
                ], 404);
            }

            // Perform audit
            $auditResults = $this->configurationAuditor->auditConfiguration(
                $scheme,
                $validated['system_configuration'] ?? []
            );

            // Generate report
            $report = $this->configurationAuditor->generateAuditReport(
                $scheme,
                $validated['system_configuration'] ?? [],
                $auditResults
            );

            return response()->json([
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to audit configuration', [
                'error' => $e->getMessage(),
                'scheme_name' => $validated['scheme_name'],
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
