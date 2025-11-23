# Nexus\MFA Implementation Plan

**Package**: `Nexus\Identity` (MFA features integrated into existing Identity package)  
**Start Date**: November 23, 2025  
**Status**: In Progress  
**Approach**: Test-Driven Development (TDD)  
**Target Test Coverage**: 95%+ for package code

## Executive Summary

This plan implements enterprise-grade Multi-Factor Authentication (MFA) within the existing `Nexus\Identity` package, supporting:

- **TOTP** (Time-based One-Time Password) via RFC 6238
- **Passkey/WebAuthn** (Passwordless authentication) with FIDO2 compliance
- **Backup Codes** (One-time recovery codes with Argon2id hashing)
- **SMS/Email OTP** (Time-limited codes via Nexus\Notifier)
- **Device Trust** (30-day trusted device fingerprinting)
- **FIDO MDS Integration** (Authenticator trust validation)
- **Enforced Onboarding** (3-step wizard with middleware enforcement)
- **Admin Recovery** (Break-glass MFA reset with audit trail)
- **Lifecycle Management** (365-day rotation, 180-day inactivity detection)
- **Compliance Reporting** (SOX/PCI-DSS/NIST audit trails)

## Architecture Principles

### 1. Framework Agnosticism (Package Layer)
- No Laravel facades or global helpers in `packages/Identity`
- All dependencies injected as interfaces
- Pure PHP 8.3+ with strict types
- Constructor property promotion with `readonly` properties
- Native enums instead of class constants
- Use `match` expressions instead of `switch`

### 2. Integration with Existing Nexus Packages
- **Nexus\Crypto**: `KeyGeneratorInterface`, `HasherInterface` for secret generation and hashing
- **Nexus\AuditLogger**: `AuditLogManagerInterface` for all MFA events (7-year retention)
- **Nexus\Notifier**: `NotificationManagerInterface` for SMS/Email OTP and security alerts
- **Nexus\Monitoring**: `TelemetryTrackerInterface` for metrics and alerting
- **Nexus\Tenant**: `TenantContextInterface` for multi-tenant isolation
- **Nexus\Identity**: Existing `AuthorizationManagerInterface`, `UserRepositoryInterface`

### 3. Database Strategy
- All tables use ULID primary keys
- Multi-tenant with `TenantScope`
- Encrypted storage for TOTP secrets and Passkey credentials
- Generated column for credential_id indexing (performance optimization)
- Foreign keys and indexes deferred to `99999_add_mfa_constraints.php`

### 4. Testing Strategy
- **Package Layer**: Pure unit tests with mocked dependencies (no database)
- **Application Layer**: Feature tests with database (Laravel TestCase)
- **Security Tests**: Timing attack resistance, brute force protection, tenant isolation
- **Performance Tests**: P95 latency <100ms target

## Implementation Phases

### Phase 1: Foundation (Value Objects, Enums, Contracts)

**Goal**: Create immutable, well-tested building blocks

**Components**:
1. `MfaMethod` enum with business logic methods
2. Value Objects:
   - `TotpSecret` (base32 validation, algorithm support)
   - `BackupCode` (plaintext/hash separation)
   - `BackupCodeSet` (regeneration threshold logic)
   - `WebAuthnCredential` (credential data, sign count tracking)
   - `DeviceFingerprint` (HMAC-based fingerprinting)
3. Contracts:
   - `MfaEnrollmentInterface` (CRUD for MFA methods)
   - `MfaVerifierInterface` (verification logic)
   - `WebAuthnManagerInterface` (WebAuthn operations)
   - `MfaEnrollmentRepositoryInterface` (persistence)
   - `WebAuthnCredentialRepositoryInterface` (specialized queries)
   - `CacheRepositoryInterface` (rate limiting, OTP storage)

**Tests**: 50+ unit tests validating immutability, business rules, validation

**Milestone**: Commit "feat(mfa): Add MFA foundation (enums, value objects, contracts)"

---

### Phase 2: TOTP Engine

**Goal**: RFC 6238 compliant TOTP implementation

**Dependencies**:
- Add `spomky-labs/otphp: ^11.3` to composer.json
- Add `endroid/qr-code: ^5.0` for QR generation

**Components**:
1. `TotpEngine` wrapping otphp library
   - `generateSecret()`: 32-byte base32 secret
   - `generateQrCodeUri()`: otpauth:// URI format
   - `generateQrCodeImage()`: PNG base64
   - `verifyCode()`: ±30s time drift tolerance
   - `getCurrentCode()`: For testing

**Tests**: 
- RFC 6238 Appendix B test vectors
- QR URI format validation
- Time drift tolerance
- QR image generation

**Milestone**: Commit "feat(mfa): Implement TOTP engine with RFC 6238 compliance"

---

### Phase 3: WebAuthn Engine

**Goal**: Multi-platform WebAuthn/Passkey support

**Dependencies**:
- Add `web-auth/webauthn-lib: ^4.7`
- Add `web-auth/cose-lib: ^4.2`
- Add `web-auth/metadata-service: ^4.7`

**Components**:
1. `WebAuthnEngine`
   - `generateRegistrationOptions()`: PublicKeyCredentialCreationOptions
   - `verifyAttestationResponse()`: All formats (packed, fido-u2f, android-safetynet, apple, tpm)
   - `generateAuthenticationOptions()`: With/without allowCredentials (usernameless)
   - `verifyAssertionResponse()`: Sign count rollback detection

2. `SafetyNetAttestationValidator`
   - JWS parsing and Google signature verification
   - CTS match and basic integrity extraction
   - Nonce validation

**Tests**:
- W3C WebAuthn Appendix F test vectors
- All attestation format parsing
- SafetyNet validation with test JWS
- Sign count rollback detection
- Challenge replay rejection

**Milestone**: Commit "feat(mfa): Implement WebAuthn engine with multi-format attestation"

---

### Phase 4: FIDO MDS Service

**Goal**: Authenticator trust validation via FIDO Metadata Service

**Components**:
1. `FidoMdsService`
   - `updateMetadata()`: Download and parse FIDO MDS BLOB
   - `getAuthenticatorStatus()`: Check AAGUID status
   - `isAuthenticatorTrusted()`: Validate not revoked
   - `getAuthenticatorMetadata()`: For compliance reporting

2. `UpdateFidoMdsCommand`
   - Scheduled weekly
   - Tracks success/failure metric
   - Critical alert on failure

**Integration**:
- WebAuthn registration checks AAGUID trust if config enabled
- Performance target: <50ms overhead

**Tests**:
- BLOB parsing
- Status extraction
- Revoked authenticator rejection
- Cache TTL verification

**Milestone**: Commit "feat(mfa): Add FIDO MDS integration for authenticator validation"

---

### Phase 5: MFA Service Layer

**Goal**: Core business logic with full integration

**Components**:
1. `MfaEnrollmentService` implementing `MfaEnrollmentInterface`
   - TOTP enrollment with QR generation
   - Passkey enrollment delegation to WebAuthnService
   - Backup code generation (Argon2id hashing)
   - Passwordless conversion
   - Credential management (rename, revoke)
   - Admin reset with recovery token

2. `MfaVerifierService` implementing `MfaVerifierInterface`
   - TOTP verification with rate limiting
   - Backup code verification (constant-time comparison)
   - Passkey verification with device fingerprint tracking
   - Fallback chain with exponential backoff

**Security Features**:
- Argon2id: memory 64MB, time 4, threads 1
- Rate limiting: 5 attempts per 15 minutes
- Timing attack resistance: hash_equals() for backup codes
- Recovery mode for passwordless users

**Tests**: 100+ unit tests with mocked dependencies

**Milestone**: Commit "feat(mfa): Implement MFA service layer with security features"

---

### Phase 6: Device Trust and Lifecycle

**Goal**: Trusted device management and credential lifecycle

**Components**:
1. `TrustedDeviceManager`
   - Device fingerprint storage (30-day TTL)
   - Cross-device notification on new fingerprint
   - Platform migration detection
   - Admin revocation

2. `PasskeyLifecycleService`
   - Expiration detection (365-day default)
   - Inactivity detection (180-day threshold)
   - Scheduled notifications

3. Artisan Commands:
   - `NotifyExpiringPasskeysCommand` (daily)
   - `CleanupInactivePasskeysCommand` (monthly with --dry-run)

**Tests**:
- Trust lifecycle
- Migration detection
- Expiration/inactivity logic

**Milestone**: Commit "feat(mfa): Add device trust and lifecycle management"

---

### Phase 7: MFA Onboarding Service

**Goal**: Enforced 3-step onboarding wizard

**Components**:
1. `MfaOnboardingService`
   - Session state management in cache
   - Step validation (prerequisites)
   - Progress tracking metrics
   - Abandonment detection

2. Middleware: `EnsureMfaOnboardingComplete`
   - Feature flag support (phased rollout)
   - Role-based enforcement
   - Redirect to onboarding wizard

**Flow**:
- Step 1: Primary Passkey OR TOTP
- Step 2: Backup Passkey OR TOTP OR SMS
- Step 3: Backup codes download confirmation

**Tests**:
- Step validation
- Middleware blocking
- Metrics tracking

**Milestone**: Commit "feat(mfa): Implement enforced onboarding wizard"

---

### Phase 8: Atomy Application Layer

**Goal**: Database, models, repositories, monitoring

**Components**:
1. Migration: `2025_11_23_create_mfa_tables.php`
   - `mfa_enrollments` table with encrypted secret column
   - Generated column for credential_id (performance)
   - Indexes on user_id, method, tenant_id, enrolled_at, last_used_at

2. Migration: `99999_add_mfa_constraints.php`
   - Foreign keys to users table
   - Unique constraints
   - Additional indexes

3. `MfaEnrollment` Model
   - `HasUlids`, `TenantScope`
   - Encrypted casts for secret
   - JSON casts for metadata

4. Repositories:
   - `DbMfaEnrollmentRepository` with tenant scoping
   - `DbWebAuthnCredentialRepository` with 15-min caching
   - `LaravelCacheRepository` implementing `CacheRepositoryInterface`

5. `MonitoredMfaVerifierService` Decorator
   - 15+ metrics tracked
   - Integration with Nexus\Monitoring

6. `IdentityServiceProvider` Updates
   - Bind all interfaces to implementations
   - Wrap verifier with monitoring decorator
   - Register scheduled commands

**Tests**: Feature tests with database

**Milestone**: Commit "feat(mfa): Add Atomy integration layer with monitoring"

---

### Phase 9: Admin Features

**Goal**: Recovery, analytics, compliance reporting

**Components**:
1. Admin Recovery:
   - Recovery token generation (6-hour TTL)
   - Token redemption endpoint
   - Authorization check with audit logging

2. Analytics Endpoint:
   - Adoption rates by method
   - AAGUID/platform distribution
   - Onboarding funnel metrics
   - CSV export via Nexus\Export

3. Compliance Reports:
   - Coverage by role/department/tenant
   - Attestation details (AAGUID, manufacturer, certification)
   - 7-year audit log confirmation
   - PDF export via Nexus\Export

**Tests**:
- Authorization enforcement
- Token expiration
- Report accuracy

**Milestone**: Commit "feat(mfa): Add admin recovery and compliance reporting"

---

### Phase 10: API Controllers and Routes

**Goal**: RESTful API for MFA operations

**Components**:
1. `MfaController`:
   - TOTP enrollment/verification
   - Passkey list/rename/revoke
   - Backup code generation
   - Passwordless conversion

2. `MfaOnboardingController`:
   - Onboarding flow endpoints
   - Progress status
   - Step completion

3. `MfaAnalyticsController` (admin):
   - Statistics endpoint
   - Funnel metrics

4. `MfaComplianceReportController` (admin):
   - Compliance report generation
   - Attestation report

5. Middleware:
   - `RequireHttps` for all WebAuthn endpoints
   - `EnsureMfaOnboardingComplete`
   - `EnsureNotInRecoveryMode`

**Routes**:
- `/api/v1/mfa/*` (user endpoints)
- `/api/v1/mfa/onboarding/*` (onboarding flow)
- `/api/v1/admin/analytics/mfa` (admin analytics)
- `/api/v1/admin/reports/mfa/*` (admin reports)

**Tests**: API feature tests

**Milestone**: Commit "feat(mfa): Add API controllers and routes"

---

### Phase 11: Feature Tests

**Goal**: End-to-end flow validation

**Test Scenarios**:
1. TOTP enrollment → verification → disable
2. Passkey enrollment → usernameless auth → rename → revoke
3. Multi-credential onboarding (Passkey → TOTP → backup codes)
4. Passwordless conversion → backup code recovery → re-enrollment
5. Admin reset → recovery token → re-enrollment
6. Device trust (new device → notification → trusted → revoke)
7. Lifecycle (expiration → inactive detection → notifications)
8. Phased rollout (phase1 optional → phase2 role-based → phase3 global)

**Milestone**: Commit "test(mfa): Add comprehensive feature tests"

---

### Phase 12: Security Testing

**Goal**: Validate security guarantees

**Test Scenarios**:
1. **Timing Attack Resistance**:
   - Backup code verification execution time variance <5%
   - Measure with microtime(true)

2. **Brute Force Protection**:
   - 6th TOTP attempt throws RateLimitExceededException
   - Cache-based rate limiting works

3. **Sign Count Rollback**:
   - Throw SignCountRollbackException on cloning attempt
   - Track critical metric

4. **AAGUID Enforcement**:
   - Unlisted authenticator rejected
   - FIDO MDS integration works

5. **Tenant Isolation**:
   - User A (tenant 1) cannot verify User B (tenant 2) credentials
   - Cross-tenant access throws exception

6. **Challenge Replay**:
   - Reused challenge rejected
   - Cache expiration works

**Milestone**: Commit "test(mfa): Add security test suite"

---

### Phase 13: Documentation

**Goal**: Complete documentation for deployment

**Documents**:
1. `docs/MFA_IMPLEMENTATION_SUMMARY.md`:
   - Features implemented
   - Architecture overview
   - Security design (Argon2id, encryption, timing resistance)
   - Compliance mapping (NIST/FIDO/PCI/SOX/GDPR)
   - Performance optimizations
   - Monitoring metrics (15+ metrics)
   - Phased rollout strategy
   - Browser compatibility matrix
   - Troubleshooting guide

2. `packages/Identity/README.md` updates:
   - MFA section with code examples
   - JavaScript integration guide (@simplewebauthn/browser)
   - Configuration reference
   - Security best practices

3. Deployment Guide:
   - Pre-deployment checklist
   - Feature flag configuration
   - Monitoring alert setup
   - Communication templates
   - Rollback procedures

**Milestone**: Commit "docs(mfa): Complete implementation documentation"

---

## Configuration Reference

### Environment Variables
```env
# WebAuthn Configuration
WEBAUTHN_RP_NAME="Atomy ERP"
WEBAUTHN_RP_ID="atomy.local"  # Must match domain

# MFA Features
MFA_ENABLE_USERNAMELESS=false
MFA_ENABLE_CONDITIONAL_UI=false
MFA_ANDROID_REQUIRE_CTS_MATCH=false
MFA_VALIDATE_AGAINST_FIDO_MDS=false

# Lifecycle Policies
MFA_PASSKEY_MAX_AGE_DAYS=365
MFA_PASSKEY_INACTIVITY_DAYS=180

# Phased Rollout
MFA_PHASE1_OPTIONAL=true
MFA_PHASE2_ROLE_BASED=false
MFA_PHASE3_GLOBAL=false
```

### Config Files
- `config/identity.php`: MFA settings
- `config/monitoring.php`: Alert thresholds

## Monitoring Metrics

### Critical Alerts
- `mfa.passkey_sign_count_rollback_total > 0`: Credential cloning detected
- `mfa.fido_mds_update_failed`: Compliance risk

### Warnings
- `mfa.verification_duration_ms P95 > 100ms`: Performance degradation
- `mfa.onboarding_abandoned_total > 30%`: UX issue

### Tracking
- `mfa.users_with_mfa_enabled_total`: Adoption rate
- `mfa.passkey_auth_prompt_duration_ms`: UX metric
- `mfa.enforcement_blocked_logins_total{role}`: Rollout impact

## Compliance Mapping

| Requirement | MFA Feature | Implementation |
|-------------|-------------|----------------|
| **NIST SP 800-63B AAL2** | Multi-factor authentication | Passkey (possession + inherence) |
| **FIDO2 Level 2** | Certified authenticators | FIDO MDS validation |
| **PCI-DSS 8.3.1** | MFA for privileged access | Role-based enforcement |
| **SOX AU-2** | Audit logging | 7-year retention via Nexus\AuditLogger |
| **GDPR Article 32** | Security measures | Encrypted storage, timing attack resistance |

## Testing Coverage Target

- **Package Code**: 95%+ (unit tests only)
- **Application Code**: 80%+ (feature tests)
- **Security Tests**: 100% of critical paths

## Rollout Timeline

### Week 1: Phase 1 (Optional Enrollment)
- Feature flag: `MFA_PHASE1_OPTIONAL=true`
- Measure adoption via metrics
- Support team training

### Week 3: Phase 2 (Role-Based Mandatory)
- Feature flag: `MFA_PHASE2_ROLE_BASED=true`
- Enforce for admin and finance roles
- Monitor blocked login metrics

### Week 5: Phase 3 (Global Mandatory)
- 1-week notice to all users
- Feature flag: `MFA_PHASE3_GLOBAL=true`
- Extended helpdesk hours

## Rollback Procedure

1. Disable enforcement feature flag
2. Clear `mfa_onboarding_completed` flags if needed
3. Communicate to users
4. Investigate root cause
5. Document lessons learned

## Success Criteria

- [ ] 95%+ test coverage on package code
- [ ] All security tests passing
- [ ] P95 verification latency <100ms
- [ ] Zero sign count rollback detections in production
- [ ] <5% onboarding abandonment rate
- [ ] Documentation complete and reviewed
- [ ] Monitoring alerts configured and tested
- [ ] Admin recovery procedure tested
- [ ] Compliance report generated successfully

---

**Implementation Status**: Phase 1 - Foundation (In Progress)  
**Last Updated**: November 23, 2025  
**Next Milestone**: Commit MFA foundation (enums, value objects, contracts)
