<?php

declare(strict_types=1);

namespace App\Models\Reporting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Nexus\Reporting\Contracts\ReportDefinitionInterface;
use Nexus\Reporting\ValueObjects\ReportFormat;
use Nexus\Reporting\ValueObjects\ReportSchedule;

/**
 * Report Definition Eloquent Model
 *
 * Implements ReportDefinitionInterface from the Reporting package.
 */
class ReportDefinition extends Model implements ReportDefinitionInterface
{
    use HasUuids;

    protected $table = 'reports_definitions';

    protected $fillable = [
        'name',
        'description',
        'query_id',
        'owner_id',
        'format',
        'schedule_type',
        'schedule_config',
        'recipients',
        'parameters',
        'template_config',
        'is_active',
        'tenant_id',
    ];

    protected $casts = [
        'schedule_config' => 'array',
        'recipients' => 'array',
        'parameters' => 'array',
        'template_config' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get generated reports for this definition.
     */
    public function generatedReports()
    {
        return $this->hasMany(ReportGenerated::class, 'report_definition_id');
    }

    // ========================================
    // ReportDefinitionInterface Implementation
    // ========================================

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getQueryId(): string
    {
        return $this->query_id;
    }

    public function getOwnerId(): string
    {
        return $this->owner_id;
    }

    public function getFormat(): ReportFormat
    {
        return ReportFormat::from($this->format);
    }

    public function getSchedule(): ?ReportSchedule
    {
        if ($this->schedule_type === null || $this->schedule_config === null) {
            return null;
        }

        return ReportSchedule::fromArray($this->schedule_config);
    }

    public function getRecipients(): array
    {
        // In a full implementation, this would resolve recipient IDs to NotifiableInterface instances
        // For now, return the raw array
        return $this->recipients ?? [];
    }

    public function getParameters(): array
    {
        return $this->parameters ?? [];
    }

    public function getTemplateConfig(): ?array
    {
        return $this->template_config;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getTenantId(): ?string
    {
        return $this->tenant_id;
    }
}
