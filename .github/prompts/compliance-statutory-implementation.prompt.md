# Systematic Implementation Prompt: Nexus\Compliance and Nexus\Statutory

**Target Audience**: Coding Agent (GitHub Copilot Agent)  
**Purpose**: Systematic implementation of Nexus\Compliance and Nexus\Statutory packages with all related country-specific and scheme-specific adapters  
**Prerequisites**: Read `/home/runner/work/atomy/atomy/docs/COMPLIANCE_STATUTORY_READINESS_ANALYSIS.md` first

---

## Mission Statement

You are tasked with implementing two critical packages in the Nexus ERP monorepo:

1. **Nexus\Compliance**: Operational compliance engine for enforcing business rules, SOD (Segregation of Duties), and compliance scheme requirements (ISO 14001, SOX, etc.)
2. **Nexus\Statutory**: Statutory reporting engine for generating tax and regulatory reports in country-specific formats (XBRL, JSON, CSV)

Both packages must be **framework-agnostic**, follow the **Nexus architecture principles**, and integrate seamlessly with existing packages.

---

## 🎯 Implementation Objectives

### Primary Goals
1. ✅ Implement fully functional Nexus\Compliance package (publishable to Packagist)
2. ✅ Implement fully functional Nexus\Statutory package (publishable to Packagist)
3. ✅ Satisfy all 64 Compliance requirements from REQUIREMENTS_PART2.csv
4. ✅ Satisfy all 120 Statutory requirements from REQUIREMENTS_PART2.csv
5. ✅ Create Atomy integration (Eloquent models, migrations, repositories, API endpoints)
6. ✅ Implement at least 2 compliance scheme adapters (ISO 14001, SOX)
7. ✅ Implement at least 2 statutory reporting adapters (Payroll MYS, Accounting MYS)
8. ✅ Achieve 80%+ test coverage for core engine components
9. ✅ Update REQUIREMENTS_PART2.csv with file mappings for all requirements
10. ✅ Create comprehensive implementation documentation

### Success Criteria
- [ ] All packages pass `composer validate`
- [ ] All packages have zero Laravel dependencies in `composer.json`
- [ ] All unit tests passing (PHPUnit)
- [ ] All integration tests passing
- [ ] All API endpoints operational (tested via Postman/Insomnia)
- [ ] CodeQL security scan passes with zero high-severity issues
- [ ] Documentation is complete (README, implementation guides, API docs)
- [ ] REQUIREMENTS_PART2.csv is fully updated with file mappings

---

## 📋 Phase-by-Phase Implementation Guide

Execute the implementation in **6 phases**, following the roadmap in the readiness analysis. After each phase, commit progress and run tests to validate.

---

## Phase 1: Foundation (Weeks 1-2)

**Goal**: Establish core packages with contracts and default implementations

### Step 1.1: Create Nexus\Compliance Package Skeleton

**Location**: `packages/Compliance/`

**Tasks**:
1. Create directory structure with all required folders
2. Implement composer.json (NO Laravel dependencies)
3. Create all core contracts with comprehensive docblocks
4. Implement immutable Value Objects
5. Create ComplianceManager skeleton service
6. Write README.md

**Critical Requirements**:
- Framework-agnostic (no Laravel in composer.json)
- All interfaces fully documented
- Constructor property promotion with readonly
- Use native PHP enums for Value Objects

**Validation**:
- `composer validate` passes
- No Laravel dependencies found
- All docblocks complete

**Commit**: "Phase 1.1: Create Nexus\Compliance package skeleton"

---

### Step 1.2: Create Nexus\Statutory Package Skeleton

**Location**: `packages/Statutory/`

**Tasks**:
1. Create directory structure
2. Implement composer.json (depends on nexus/finance, nexus/period)
3. Create all core contracts (TaxonomyReportGeneratorInterface, PayrollStatutoryInterface, ReportMetadataInterface)
4. Implement DefaultAccountingAdapter (basic P&L, Balance Sheet)
5. Implement DefaultPayrollStatutoryAdapter (zero deductions)
6. Create immutable Value Objects
7. Write README.md

**Validation**:
- `composer validate` passes
- Default adapters functional (smoke test)

**Commit**: "Phase 1.2: Create Nexus\Statutory package skeleton with default adapters"

---

### Step 1.3-1.5: Create Atomy Migrations and Run

**Tasks**:
1. Create Compliance migrations (4 tables)
2. Create Statutory migrations (3 tables)
3. Run migrations and validate

**Tables**:
- compliance_schemes, sod_rules, sod_violations, configuration_audit_checkpoints
- statutory_reports, statutory_report_instances, statutory_rate_tables

**Validation**:
- All 7 tables created
- Foreign keys functional
- Migrations can rollback

**Commit**: "Phase 1.3-1.5: Create and apply Compliance and Statutory migrations"

---

## Phase 2: Core Engine (Weeks 3-5)

**Goal**: Implement internal processing engines

### Step 2.1: Implement Nexus\Compliance Core Engine

**Location**: `packages/Compliance/src/Core/`

**Components**:
- RuleEngine (rule evaluation)
- ValidationPipeline (multi-step validation)
- SodValidator (creator ≠ approver check)
- ConfigurationValidator (feature/setting checks)

**Unit Tests**:
- 80%+ coverage required
- Test all edge cases
- Mock dependencies

**Commit**: "Phase 2.1: Implement Compliance Core Engine with tests"

---

### Step 2.2: Implement Nexus\Statutory Core Engine

**Location**: `packages/Statutory/src/Core/`

**Components**:
- SchemaValidator (data validation)
- ReportGenerator (report creation)
- FormatConverter (JSON, CSV, XML, XBRL)
- FinanceDataExtractor (data extraction)

**Unit Tests**:
- 85%+ coverage required
- Test schema validation
- Test format conversion

**Commit**: "Phase 2.2: Implement Statutory Core Engine with tests"

---

### Step 2.3-2.4: Implement Full Service Layer

**Tasks**:
1. Complete ConfigurationAuditor
2. Complete StatutoryReportManager with full pipeline

**Validation**:
- All engines functional
- All tests passing
- Integration tests working

**Commit**: "Phase 2.3-2.4: Complete service layer implementation"

---

## Phase 3: Atomy Integration (Weeks 6-7)

**Goal**: Eloquent models, repositories, API endpoints

### Step 3.1-3.2: Implement Eloquent Models

**Tasks**:
1. Create all Compliance models (ComplianceScheme, SodRule, SodViolation, ConfigurationAuditCheckpoint)
2. Create all Statutory models (StatutoryReport, StatutoryReportInstance, StatutoryRateTable)
3. Implement all interface methods
4. Define relationships

**Commit**: "Phase 3.1-3.2: Implement Eloquent models with relationships"

---

### Step 3.3-3.4: Implement Repositories

**Tasks**:
1. Create all Compliance repositories
2. Create all Statutory repositories
3. Implement all interface methods

**Commit**: "Phase 3.3-3.4: Implement repositories for persistence"

---

### Step 3.5: Create Service Providers

**Tasks**:
1. Create ComplianceServiceProvider
2. Create StatutoryServiceProvider
3. Register in config/app.php
4. Bind all interfaces to implementations

**Validation**:
- `php artisan about` shows providers
- Interface resolution working

**Commit**: "Phase 3.5: Create service providers with IoC bindings"

---

### Step 3.6-3.7: Implement API Endpoints

**Tasks**:
1. Create ComplianceController
2. Create StatutoryController
3. Define routes
4. Write feature tests

**Endpoints**:
- POST /api/compliance/schemes (activate)
- GET /api/compliance/schemes (list)
- POST /api/compliance/sod-rules (create)
- GET /api/compliance/sod-violations (list)
- POST /api/statutory/reports/generate
- GET /api/statutory/reports
- GET /api/statutory/reports/{id}/download

**Validation**:
- All feature tests passing
- API endpoints operational

**Commit**: "Phase 3.6-3.7: Implement API endpoints with feature tests"

---

## Phase 4: Feature Composition & XBRL (Weeks 8-9)

**Goal**: Advanced features

### Step 4.1: Design Extension Point System

**Tasks**:
1. Document extension points in target packages
2. Create ExtensionPointRegistry

**Commit**: "Phase 4.1: Design extension point system"

---

### Step 4.2: Implement ISO 14001 Adapter

**Location**: Create new package `packages/ComplianceISO14001/`

**Tasks**:
1. Implement adapter with feature composition
2. Test with Asset package integration

**Commit**: "Phase 4.2: Implement ISO 14001 compliance adapter"

---

### Step 4.3: Implement XBRL Generator

**Tasks**:
1. Install php-xbrl library
2. Implement XBRLGenerator
3. Test XBRL generation

**Commit**: "Phase 4.3: Implement XBRL generator"

---

### Step 4.4: Implement Malaysia SSM BR Adapter

**Location**: Create new package `packages/StatutoryAccountingMYSProp/`

**Tasks**:
1. Implement SSMBRTaxonomyAdapter
2. Test with real SSM BR schema

**Commit**: "Phase 4.4: Implement Malaysia SSM BR adapter"

---

## Phase 5: Country-Specific Adapters (Weeks 10-12)

**Goal**: Additional adapters

### Tasks:
1. Validate/enhance Nexus\PayrollMysStatutory
2. Implement SOX compliance adapter
3. Test all adapters with real data

**Commit**: "Phase 5: Complete country-specific and scheme-specific adapters"

---

## Phase 6: Documentation & Deployment (Week 13)

**Goal**: Production readiness

### Step 6.1: Update REQUIREMENTS_PART2.csv

**Tasks**:
1. Map all 184 requirements to files
2. Use semicolon separators
3. Include methods where relevant

**Commit**: "Phase 6.1: Update REQUIREMENTS_PART2.csv with file mappings"

---

### Step 6.2: Create Implementation Documentation

**Tasks**:
1. Create COMPLIANCE_IMPLEMENTATION.md
2. Create STATUTORY_IMPLEMENTATION.md
3. Include structure, requirements, examples, schema

**Commit**: "Phase 6.2: Create comprehensive documentation"

---

### Step 6.3: Update ARCHITECTURE.md

**Tasks**:
1. Add Compliance/Statutory patterns
2. Document extension points
3. Update package list

**Commit**: "Phase 6.3: Update ARCHITECTURE.md"

---

### Step 6.4: Security Audit

**Tasks**:
1. Run CodeQL
2. Run PHPStan level 8
3. Fix all issues

**Commit**: "Phase 6.4: Pass security audit"

---

### Step 6.5: Performance Testing

**Tasks**:
1. Test report generation (100K entries)
2. Test SOD evaluation (1000 rules)
3. Document benchmarks

**Commit**: "Phase 6.5: Complete performance testing"

---

## Final Validation Checklist

Before completion, verify ALL:

### Package Quality
- [ ] `composer validate` passes for all packages
- [ ] Zero Laravel dependencies in packages
- [ ] All interfaces documented
- [ ] All Value Objects immutable

### Testing
- [ ] All tests passing
- [ ] Coverage ≥ 80% (Compliance) / 85% (Statutory)

### Atomy Integration
- [ ] All migrations applied
- [ ] All models functional
- [ ] All repositories functional
- [ ] All API endpoints operational

### Documentation
- [ ] REQUIREMENTS_PART2.csv updated (184 requirements)
- [ ] Implementation docs complete
- [ ] ARCHITECTURE.md updated

### Security & Performance
- [ ] CodeQL passed
- [ ] PHPStan level 8 passed
- [ ] Performance benchmarks documented

### Functional Requirements
- [ ] All 64 Compliance requirements satisfied
- [ ] All 120 Statutory requirements satisfied
- [ ] 2+ compliance schemes functional
- [ ] 2+ statutory adapters functional

---

## Execution Guidelines

1. **Read readiness analysis first**
2. **Follow phases sequentially** (no skipping)
3. **Commit frequently** with descriptive messages
4. **Test constantly** (don't accumulate failures)
5. **Validate early** (composer, migrations, bindings)
6. **Document as you go**
7. **Security first** (never commit secrets)
8. **Follow Nexus architecture** (framework-agnostic)

---

## Common Pitfalls to Avoid

1. ❌ Laravel dependencies in package composer.json
2. ❌ Skipping unit tests
3. ❌ Business logic in controllers
4. ❌ Forgetting REQUIREMENTS_PART2.csv updates
5. ❌ Not validating migrations
6. ❌ Hardcoded configuration
7. ❌ Mutable Value Objects
8. ❌ Missing extension point docs
9. ❌ Skipping performance tests
10. ❌ No security scans

---

## Support Resources

### Key Files
- `/home/runner/work/atomy/atomy/ARCHITECTURE.md`
- `/home/runner/work/atomy/atomy/docs/COMPLIANCE_STATUTORY_READINESS_ANALYSIS.md`
- `/home/runner/work/atomy/atomy/REQUIREMENTS_PART2.csv`
- `/home/runner/work/atomy/atomy/.github/prompts/package-implementation-workflow.prompt.md`

### Package Examples
- `packages/Tenant/` - Simple package
- `packages/Finance/` - Complex with Core/
- `packages/PayrollMysStatutory/` - Country adapter

---

## Success Metrics

At completion:
- ✅ 2 framework-agnostic packages
- ✅ 4 adapter packages
- ✅ 184 requirements satisfied
- ✅ 7 database tables
- ✅ 8+ API endpoints
- ✅ 80%+ test coverage
- ✅ Zero high-severity issues
- ✅ Complete documentation

---

**Prompt Version**: 1.0  
**Date**: November 18, 2025  
**Estimated Duration**: 13 weeks  
**Status**: Ready for Execution

Good luck, Coding Agent! 🚀
