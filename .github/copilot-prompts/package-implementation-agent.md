# Package Implementation Agent Prompt

**Purpose**: Systematic implementation of a complete Nexus package following the monorepo architecture with proper tracking, decision points, and integration validation.

## 🎯 Your Mission

You are implementing **[PACKAGE_NAME]** for the Nexus ERP monorepo. Your goal is to create a **production-ready, fully integrated package** that:
- Follows the "Logic in Packages, Implementation in Applications" architecture
- Satisfies all requirements from REQUIREMENTS.csv
- Integrates properly with Atomy and dependent packages
- Is tested, documented, and ready for use

## 📋 Prerequisites - VERIFY FIRST

Before starting implementation, **STOP and verify** these prerequisites:

1. **Read the Architecture Document**
   - [ ] Read `/home/conrad/Dev/azaharizaman/atomy/ARCHITECTURE.md` completely
   - [ ] Read `/home/conrad/Dev/azaharizaman/atomy/.github/copilot-instructions.md` completely
   - [ ] Understand the "Logic in Packages, Implementation in Applications" principle

2. **Examine Requirements**
   - [ ] Read `/home/conrad/Dev/azaharizaman/atomy/REQUIREMENTS.csv`
   - [ ] Filter for package prefix (e.g., "FR-UOM-", "BUS-UOM-")
   - [ ] Count total requirements and categorize by type:
     * Architectural (ARC-*)
     * Business (BUS-*)
     * Functional (FR-*)
     * Performance (PER-*)
     * Security (SEC-*)
     * Reliability (REL-*)
     * User Stories (US-*)

3. **Identify Dependencies**
   - [ ] Check if package depends on other Nexus packages (e.g., Tenant, AuditLogger, Sequencing)
   - [ ] Verify dependent packages exist and are implemented
   - [ ] Note integration points with Atomy application

4. **Check for Existing Implementation**
   - [ ] Verify `packages/[PackageName]/` does NOT already exist
   - [ ] Check if any models/migrations exist in `apps/Atomy/` for this domain
   - [ ] Search for related code in workspace

**DECISION POINT 🛑**: If any prerequisite fails or is unclear, **STOP and ask the user** for clarification before proceeding.

## 🗂️ Phase 1: Planning & Todo List Creation

**Objective**: Create a comprehensive, trackable todo list breaking down the entire implementation.

### Step 1.1: Create Master Todo List

Use `manage_todo_list` to create a structured todo list with these phases:

```markdown
# [PACKAGE_NAME] Implementation

## Phase 1: Package Skeleton
- [ ] Create package directory structure
- [ ] Create composer.json with package metadata
- [ ] Create LICENSE and README.md
- [ ] Create folder structure (Contracts/, Exceptions/, Services/, ValueObjects/, etc.)

## Phase 2: Package Core - Contracts
- [ ] Define all interfaces based on requirements
- [ ] Add comprehensive docblocks with @param, @return, @throws
- [ ] Ensure all methods have strict type hints

## Phase 3: Package Core - Exceptions
- [ ] Create all domain-specific exception classes
- [ ] Add factory methods for common error scenarios
- [ ] Ensure clear, actionable error messages

## Phase 4: Package Core - Value Objects
- [ ] Identify all value objects from requirements
- [ ] Create readonly classes for immutability
- [ ] Implement serialization (toArray, fromArray, JsonSerializable)
- [ ] Add validation logic

## Phase 5: Package Core - Services
- [ ] Implement business logic services
- [ ] Use dependency injection via constructor
- [ ] Validate inputs and throw descriptive exceptions
- [ ] Add internal caching where appropriate

## Phase 6: Atomy - Database Design
- [ ] Design database schema (tables, columns, relationships)
- [ ] Create migration with ULID primary keys
- [ ] Add indexes, foreign keys, unique constraints
- [ ] Use DECIMAL for precision-critical fields

## Phase 7: Atomy - Models
- [ ] Create Eloquent models implementing package interfaces
- [ ] Add relationships, fillable, casts, scopes
- [ ] Apply traits (HasUlids, SoftDeletes, BelongsToTenant, Auditable)
- [ ] Add custom query scopes if needed

## Phase 8: Atomy - Repository
- [ ] Implement repository interface with Eloquent
- [ ] Handle all CRUD operations
- [ ] Add error handling and exception throwing
- [ ] Optimize queries with eager loading

## Phase 9: Atomy - Service Provider
- [ ] Create service provider with IoC bindings
- [ ] Register all singletons (repository, services, manager)
- [ ] Load migrations and publish config
- [ ] Add boot logic if needed

## Phase 10: Atomy - Configuration
- [ ] Create config file with all settings
- [ ] Add feature flags (tenant_isolation, audit_logging, caching)
- [ ] Add seed data configuration
- [ ] Document all config options

## Phase 11: Integration - Dependent Packages
- [ ] Identify packages that need this package
- [ ] Update their composer.json to require this package
- [ ] Integrate services where needed
- [ ] Test integration points

## Phase 12: Integration - Testing
- [ ] Create package unit tests (mocked repository)
- [ ] Create Atomy feature tests (with database)
- [ ] Test all happy paths
- [ ] Test all error paths
- [ ] Test integration with dependent packages

## Phase 13: Documentation
- [ ] Update REQUIREMENTS.csv with file/method mappings
- [ ] Create comprehensive [PACKAGE]_IMPLEMENTATION.md
- [ ] Add usage examples in documentation
- [ ] Document API endpoints (if applicable)

## Phase 14: Final Validation
- [ ] Run all tests and verify they pass
- [ ] Check for lint errors and fix
- [ ] Verify all requirements are satisfied
- [ ] Create final git commit
```

**ACTION**: Create this todo list now using `manage_todo_list` with operation="write".

### Step 1.2: Requirement Analysis

Create a summary of requirements:

```markdown
Total Requirements: [COUNT]
- Architectural: [COUNT]
- Business: [COUNT]
- Functional: [COUNT]
- Performance: [COUNT]
- Security: [COUNT]
- Reliability: [COUNT]
- User Stories: [COUNT]

Key Features to Implement:
1. [Feature 1 from requirements]
2. [Feature 2 from requirements]
3. [Feature 3 from requirements]
...

Critical Constraints:
1. [Constraint 1 from business requirements]
2. [Constraint 2 from business requirements]
...

Integration Points:
1. [Package dependency 1] - for [purpose]
2. [Package dependency 2] - for [purpose]
...
```

**DECISION POINT 🛑**: Present this summary to the user and ask:
- "Are there any requirements I'm missing or misunderstanding?"
- "Are there any additional constraints or considerations?"
- "Should I proceed with implementation?"

## 🏗️ Phase 2: Package Implementation

**Objective**: Implement the framework-agnostic package core.

### Step 2.1: Create Package Skeleton

1. Create directory structure:
   ```
   packages/[PackageName]/
   ├── composer.json
   ├── LICENSE
   ├── README.md
   └── src/
       ├── Contracts/
       ├── Exceptions/
       ├── Services/
       └── ValueObjects/
   ```

2. **composer.json requirements**:
   - Package name: `nexus/[package-name]` (kebab-case)
   - PHP version: `^8.2` or higher
   - PSR-4 autoloader: `"Nexus\\[PackageName]\\": "src/"`
   - **CRITICAL**: NO Laravel dependencies (framework-agnostic)
   - Only allow dependencies on other `nexus/*` packages or pure PHP libraries

3. **README.md must include**:
   - Package overview and features
   - Installation instructions
   - Basic usage examples (5+ examples)
   - Architecture explanation
   - Requirements summary

**VALIDATION CHECKPOINT**: After creating skeleton, verify:
- [ ] No Laravel dependencies in composer.json
- [ ] PSR-4 autoloader configured correctly
- [ ] README has comprehensive usage examples

**ACTION**: Mark todo items in Phase 1 as completed. Mark Phase 2 item as in-progress.

### Step 2.2: Implement Contracts (Interfaces)

For each interface:

1. **Naming convention**: `[Entity]Interface` (e.g., `TenantInterface`, `UomRepositoryInterface`)

2. **Must include**:
   - `declare(strict_types=1);` at top
   - Comprehensive docblock with description
   - All method signatures with strict type hints
   - Detailed `@param`, `@return`, `@throws` annotations

3. **Repository interface pattern**:
   - `find[Entity]By[Criteria]()` - retrieval methods
   - `save[Entity]()` - persistence methods
   - `delete[Entity]()` - deletion methods
   - `get[Entities]By[Criteria]()` - collection methods
   - `ensureUnique[Property]()` - validation methods

4. **Common methods to include**:
   - Getters for all properties (e.g., `getCode()`, `getName()`)
   - Status checks (e.g., `isActive()`, `isSystemDefined()`)
   - Relational accessors (e.g., `getDimension()`, `getParent()`)

**EXAMPLE**:
```php
<?php

declare(strict_types=1);

namespace Nexus\[PackageName]\Contracts;

/**
 * Interface for [Entity] entity.
 *
 * Defines the contract for accessing [entity] properties.
 */
interface [Entity]Interface
{
    /**
     * Get the unique code for this [entity].
     *
     * @return string The [entity] code (e.g., 'kg', 'active', 'monthly')
     */
    public function getCode(): string;

    /**
     * Get the human-readable name.
     *
     * @return string The [entity] name
     */
    public function getName(): string;

    // ... more methods
}
```

**VALIDATION CHECKPOINT**: After implementing all interfaces, verify:
- [ ] All interfaces have strict_types declaration
- [ ] All methods have complete type hints
- [ ] All docblocks are comprehensive
- [ ] No implementation details leaked (only contracts)

**ACTION**: Mark Phase 2 Contracts todo as completed after validation.

### Step 2.3: Implement Exceptions

For each exception:

1. **Naming convention**: `[DescriptiveError]Exception` (e.g., `TenantNotFoundException`, `InvalidConversionRatioException`)

2. **Must extend**: Base PHP exceptions (e.g., `Exception`, `InvalidArgumentException`, `RuntimeException`)

3. **Must include**:
   - `declare(strict_types=1);` at top
   - Static factory methods for common scenarios
   - Clear, actionable error messages
   - Contextual information in message

4. **Factory method pattern**:
   ```php
   public static function for[Scenario]([params]): self
   {
       return new self(sprintf(
           'Descriptive error message with %s context',
           $param
       ));
   }
   ```

**EXAMPLE**:
```php
<?php

declare(strict_types=1);

namespace Nexus\[PackageName]\Exceptions;

use InvalidArgumentException;

/**
 * Exception thrown when [scenario description].
 */
final class [DescriptiveError]Exception extends InvalidArgumentException
{
    /**
     * Create exception for [common scenario].
     *
     * @param string $code The [entity] code that caused the error
     * @return self
     */
    public static function forCode(string $code): self
    {
        return new self(sprintf(
            '[Entity] with code "%s" [error description].',
            $code
        ));
    }
}
```

**VALIDATION CHECKPOINT**: After implementing all exceptions, verify:
- [ ] All exceptions have factory methods
- [ ] Error messages are clear and actionable
- [ ] Exceptions extend appropriate base classes

**ACTION**: Mark Phase 2 Exceptions todo as completed.

### Step 2.4: Implement Value Objects

For each value object:

1. **Identify candidates**: Immutable data structures (e.g., Quantity, TenantStatus, AuditLevel)

2. **Use native enums when appropriate**:
   - **Use enum** if: Fixed set of values (statuses, levels, types, strategies)
   - **Use readonly class** if: Composite data (Quantity with value + unit, Address with multiple fields)

3. **For enum-based value objects**:
   ```php
   <?php

   declare(strict_types=1);

   namespace Nexus\[PackageName]\ValueObjects;

   /**
    * Enum representing [entity] with [backing type].
    */
   enum [Name]: [int|string]
   {
       case [CaseName1] = [value1];
       case [CaseName2] = [value2];

       /**
        * Get human-readable label.
        *
        * @return string
        */
       public function label(): string
       {
           return match ($this) {
               self::[CaseName1] => '[Label 1]',
               self::[CaseName2] => '[Label 2]',
           };
       }

       /**
        * Check if this is [case 1].
        *
        * @return bool
        */
       public function is[CaseName1](): bool
       {
           return $this === self::[CaseName1];
       }
   }
   ```

4. **For readonly class value objects**:
   ```php
   <?php

   declare(strict_types=1);

   namespace Nexus\[PackageName]\ValueObjects;

   use JsonSerializable;

   /**
    * Immutable value object representing [concept].
    */
   final readonly class [Name] implements JsonSerializable
   {
       /**
        * Create a new [entity] instance.
        *
        * @param float $property1 Description
        * @param string $property2 Description
        */
       public function __construct(
           public float $property1,
           public string $property2,
       ) {
           // Validation logic here
           if ($property1 <= 0) {
               throw new InvalidArgumentException('Property1 must be positive');
           }
       }

       /**
        * Create from array representation.
        *
        * @param array{property1: float, property2: string} $data
        * @return self
        */
       public static function fromArray(array $data): self
       {
           return new self($data['property1'], $data['property2']);
       }

       /**
        * Convert to array representation.
        *
        * @return array{property1: float, property2: string}
        */
       public function toArray(): array
       {
           return [
               'property1' => $this->property1,
               'property2' => $this->property2,
           ];
       }

       /**
        * @return array{property1: float, property2: string}
        */
       public function jsonSerialize(): array
       {
           return $this->toArray();
       }
   }
   ```

5. **Must include**:
   - `declare(strict_types=1);` at top
   - Immutability (readonly keyword for classes, enum for fixed sets)
   - Validation in constructor (for readonly classes)
   - Serialization methods: `toArray()`, `fromArray()`, `jsonSerialize()`
   - Convenience methods (e.g., `is*()`, `format()`, `equals()`)

**VALIDATION CHECKPOINT**: After implementing all value objects, verify:
- [ ] All value objects are immutable (enum or readonly class)
- [ ] Validation logic prevents invalid states
- [ ] Serialization methods are present
- [ ] No setters or mutable state

**ACTION**: Mark Phase 2 Value Objects todo as completed.

### Step 2.5: Implement Services

For each service:

1. **Naming convention**: `[Domain][Purpose]Service` or `[Domain]Manager` (e.g., `UomConversionEngine`, `TenantManager`)

2. **Use constructor property promotion with readonly**:
   ```php
   public function __construct(
       private readonly [Interface1] $dependency1,
       private readonly [Interface2] $dependency2,
   ) {}
   ```

3. **Service architecture**:
   - **Manager classes**: High-level façade for external consumers (e.g., `UomManager`, `TenantManager`)
   - **Engine classes**: Core business logic implementation (e.g., `UomConversionEngine`, `RetentionPolicyService`)
   - **Validation classes**: Business rule validation (e.g., `UomValidationService`, `TenantValidationService`)

4. **Must include**:
   - `declare(strict_types=1);` at top
   - Dependency injection via constructor (NO static methods except factories)
   - Input validation with descriptive exceptions
   - Comprehensive docblocks for all public methods
   - Business logic only - NO persistence (use repository interface)

5. **Method pattern**:
   ```php
   /**
    * [Action description].
    *
    * @param [Type] $param1 Description
    * @param [Type] $param2 Description
    * @return [Type] Description
    * @throws [Exception1] When [condition]
    * @throws [Exception2] When [condition]
    */
   public function [action]([Type] $param1, [Type] $param2): [Type]
   {
       // 1. Validate inputs
       $this->validateSomething($param1);

       // 2. Execute business logic
       $result = $this->doSomething($param1, $param2);

       // 3. Return result
       return $result;
   }
   ```

**VALIDATION CHECKPOINT**: After implementing all services, verify:
- [ ] All dependencies injected via constructor (readonly properties)
- [ ] No persistence logic (only repository interface calls)
- [ ] Comprehensive input validation
- [ ] Clear separation: Manager (public API) vs Engine (internal logic)

**ACTION**: Mark Phase 2 Services todo as completed.

### Step 2.6: Update Root Composer

Add package to monorepo's composer.json:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/[PackageName]"
        }
    ]
}
```

**ACTION**: Update `/home/conrad/Dev/azaharizaman/atomy/composer.json` to add the new package repository.

**COMMIT POINT 📝**: After Phase 2 completion:
```bash
git add packages/[PackageName]/
git add composer.json
git commit -m "feat: Add [PackageName] package skeleton with core business logic

- Create package structure with composer.json, LICENSE, README
- Implement [N] interfaces in Contracts/
- Implement [N] exceptions in Exceptions/
- Implement [N] value objects in ValueObjects/
- Implement [N] service classes in Services/
- All code is framework-agnostic with strict types
- Comprehensive docblocks and type hints

Ref: [Requirement codes, e.g., FR-UOM-101, BUS-UOM-201]"
```

**ACTION**: Execute the commit after user confirmation.

## 🚀 Phase 3: Atomy Implementation

**Objective**: Implement the Laravel-based application layer in Atomy.

### Step 3.1: Database Design

1. **Identify entities** from package interfaces and requirements

2. **For each table, design**:
   - **Primary key**: `id` ULID (NOT auto-increment)
   - **Natural key**: `code` VARCHAR(50) UNIQUE (for entities with codes)
   - **Relationships**: Foreign keys with proper constraints
   - **Indexes**: On frequently queried columns
   - **Precision**: DECIMAL(30,15) for financial/measurement values
   - **Versioning**: `version` INT for audit trail (if needed)
   - **Timestamps**: `created_at`, `updated_at` (Laravel standard)
   - **Soft deletes**: `deleted_at` (for most entities)

3. **Foreign key rules**:
   - Use `ON DELETE RESTRICT` for critical relationships (prevent orphans)
   - Use `ON DELETE CASCADE` for dependent relationships (clean up)
   - Use `ON DELETE SET NULL` for optional relationships

4. **Unique constraints**:
   - Composite unique: `(code, deleted_at)` for soft-deleted entities
   - Single unique: `code` for non-soft-deleted entities

**DECISION POINT 🛑**: Before creating migration, present the schema design to user:
```markdown
## Proposed Database Schema

### Table: [table_name]
Columns:
- id: ULID, primary key
- code: VARCHAR(50), unique, indexed
- name: VARCHAR(100), not null
- [foreign_key]: VARCHAR(50), foreign key to [other_table](code)
- is_[flag]: BOOLEAN, default false
- [precision_field]: DECIMAL(30,15), not null
- created_at, updated_at, deleted_at: TIMESTAMP

Indexes:
- PRIMARY KEY (id)
- UNIQUE (code)
- INDEX (foreign_key)
- INDEX (is_flag)

Foreign Keys:
- foreign_key REFERENCES [other_table](code) ON DELETE RESTRICT

### Table: [related_table]
...

Questions:
1. Should we add tenant_id for multi-tenancy?
2. Should we add version tracking for audit trail?
3. Are there any missing columns or relationships?
```

**ACTION**: Wait for user approval before proceeding.

### Step 3.2: Create Migration

1. **Naming**: `YYYY_MM_DD_HHMMSS_create_[domain]_tables.php`
   - Use single migration for related tables
   - Example: `2025_11_17_200000_create_uom_tables.php`

2. **Migration structure**:
   ```php
   public function up(): void
   {
       // Create tables in dependency order (parent before child)
       Schema::create('[parent_table]', function (Blueprint $table) {
           $table->ulid('id')->primary();
           $table->string('code', 50)->unique();
           // ... columns
           $table->timestamps();
           $table->softDeletes();

           // Indexes
           $table->index('code');
       });

       Schema::create('[child_table]', function (Blueprint $table) {
           $table->ulid('id')->primary();
           $table->string('[parent]_code', 50);
           // ... columns
           $table->timestamps();
           $table->softDeletes();

           // Foreign keys
           $table->foreign('[parent]_code')
               ->references('code')
               ->on('[parent_table]')
               ->onDelete('restrict');

           // Indexes
           $table->index('[parent]_code');
       });
   }

   public function down(): void
   {
       // Drop in reverse order (child before parent)
       Schema::dropIfExists('[child_table]');
       Schema::dropIfExists('[parent_table]');
   }
   ```

**VALIDATION CHECKPOINT**: After creating migration, verify:
- [ ] All tables use ULID primary keys
- [ ] Foreign keys have proper ON DELETE actions
- [ ] Indexes on frequently queried columns
- [ ] Natural keys (code) are unique
- [ ] Tables created in dependency order
- [ ] Tables dropped in reverse order

**ACTION**: Mark Phase 6 todo as completed. Create migration file in `apps/Atomy/database/migrations/`.

### Step 3.3: Create Eloquent Models

For each entity:

1. **Naming**: PascalCase singular (e.g., `Unit`, `Tenant`, `AuditLog`)

2. **Must implement**: Corresponding package interface

3. **Standard traits**:
   - `HasUlids` - REQUIRED for ULID primary keys
   - `SoftDeletes` - for entities that need soft delete
   - `BelongsToTenant` - if tenant isolation enabled
   - `Auditable` - if audit logging enabled

4. **Model template**:
   ```php
   <?php

   declare(strict_types=1);

   namespace App\Models;

   use Illuminate\Database\Eloquent\Concerns\HasUlids;
   use Illuminate\Database\Eloquent\Model;
   use Illuminate\Database\Eloquent\Relations\BelongsTo;
   use Illuminate\Database\Eloquent\Relations\HasMany;
   use Illuminate\Database\Eloquent\SoftDeletes;
   use Nexus\[PackageName]\Contracts\[Entity]Interface;

   /**
    * Eloquent model for [Entity].
    *
    * Implements [Entity]Interface from package layer.
    *
    * @property string $id ULID primary key
    * @property string $code Unique [entity] code
    * @property string $name Human-readable name
    * @property [type] $[property] Description
    * @property \Carbon\Carbon $created_at
    * @property \Carbon\Carbon $updated_at
    * @property \Carbon\Carbon|null $deleted_at
    */
   class [Entity] extends Model implements [Entity]Interface
   {
       use HasUlids;
       use SoftDeletes;

       /**
        * The table associated with the model.
        *
        * @var string
        */
       protected $table = '[table_name]';

       /**
        * The attributes that are mass assignable.
        *
        * @var array<int, string>
        */
       protected $fillable = [
           'code',
           'name',
           // ... all non-computed columns
       ];

       /**
        * The attributes that should be cast.
        *
        * @var array<string, string>
        */
       protected $casts = [
           'is_[flag]' => 'boolean',
           'created_at' => 'datetime',
           'updated_at' => 'datetime',
           'deleted_at' => 'datetime',
       ];

       // Implement interface methods
       public function getCode(): string
       {
           return $this->code;
       }

       public function getName(): string
       {
           return $this->name;
       }

       // Relationships
       public function [parent](): BelongsTo
       {
           return $this->belongsTo([Parent]::class, '[parent]_code', 'code');
       }

       public function [children](): HasMany
       {
           return $this->hasMany([Child]::class, '[parent]_code', 'code');
       }
   }
   ```

5. **Relationship conventions**:
   - `BelongsTo`: Use for foreign key relationships
   - `HasMany`: Use for one-to-many relationships
   - `BelongsToMany`: Use for many-to-many relationships
   - Always specify foreign key and owner key explicitly

**VALIDATION CHECKPOINT**: After creating all models, verify:
- [ ] All models implement package interfaces
- [ ] HasUlids trait applied to all models
- [ ] All columns in fillable array
- [ ] Proper casts for boolean, datetime, decimal
- [ ] Relationships defined with explicit keys
- [ ] Interface methods implemented

**ACTION**: Mark Phase 7 todo as completed. Create models in `apps/Atomy/app/Models/`.

### Step 3.4: Create Repository Implementation

1. **Naming**: `Db[Domain]Repository` (e.g., `DbUomRepository`, `DbTenantRepository`)

2. **Must implement**: Package repository interface

3. **Repository template**:
   ```php
   <?php

   declare(strict_types=1);

   namespace App\Repositories;

   use App\Models\[Entity];
   use Nexus\[PackageName]\Contracts\[Repository]Interface;
   use Nexus\[PackageName]\Exceptions\[Entity]NotFoundException;
   use Nexus\[PackageName]\Exceptions\Duplicate[Entity]CodeException;

   /**
    * Eloquent implementation of [Repository]Interface.
    */
   final readonly class Db[Repository] implements [Repository]Interface
   {
       /**
        * Find [entity] by code.
        *
        * @param string $code The [entity] code
        * @return [Entity] The found [entity] model
        * @throws [Entity]NotFoundException If [entity] not found
        */
       public function find[Entity]ByCode(string $code): [Entity]
       {
           $entity = [Entity]::where('code', $code)->first();

           if (!$entity) {
               throw [Entity]NotFoundException::forCode($code);
           }

           return $entity;
       }

       /**
        * Save [entity] to database.
        *
        * @param array<string, mixed> $data The [entity] data
        * @return [Entity] The saved [entity]
        * @throws Duplicate[Entity]CodeException If code already exists
        */
       public function save[Entity](array $data): [Entity]
       {
           // Check for duplicates
           if (isset($data['code']) && [Entity]::where('code', $data['code'])->exists()) {
               throw Duplicate[Entity]CodeException::forCode($data['code']);
           }

           return [Entity]::create($data);
       }

       // Implement all other interface methods...
   }
   ```

4. **Query optimization**:
   - Use `with()` for eager loading relationships
   - Use `select()` to limit columns when not all needed
   - Use `exists()` for existence checks (faster than `count()`)

**VALIDATION CHECKPOINT**: After creating repository, verify:
- [ ] All interface methods implemented
- [ ] Proper exception throwing with descriptive messages
- [ ] Eager loading for relationships
- [ ] Existence checks before inserts

**ACTION**: Mark Phase 8 todo as completed. Create repository in `apps/Atomy/app/Repositories/`.

### Step 3.5: Create Service Provider

1. **Naming**: `[PackageName]ServiceProvider` (e.g., `UomServiceProvider`, `TenantServiceProvider`)

2. **Service provider template**:
   ```php
   <?php

   declare(strict_types=1);

   namespace App\Providers;

   use App\Repositories\Db[Repository];
   use Illuminate\Support\ServiceProvider;
   use Nexus\[PackageName]\Contracts\[Repository]Interface;
   use Nexus\[PackageName]\Services\[Service1];
   use Nexus\[PackageName]\Services\[Service2];
   use Nexus\[PackageName]\Services\[Manager];

   /**
    * Service provider for [PackageName] package.
    *
    * Registers IoC container bindings for package services.
    */
   final class [PackageName]ServiceProvider extends ServiceProvider
   {
       /**
        * Register services.
        *
        * @return void
        */
       public function register(): void
       {
           // Bind repository interface to Eloquent implementation
           $this->app->singleton(
               [Repository]Interface::class,
               Db[Repository]::class
           );

           // Bind services as singletons
           $this->app->singleton([Service1]::class, function ($app) {
               return new [Service1](
                   $app->make([Repository]Interface::class)
               );
           });

           $this->app->singleton([Service2]::class, function ($app) {
               return new [Service2](
                   $app->make([Repository]Interface::class)
               );
           });

           $this->app->singleton([Manager]::class, function ($app) {
               return new [Manager](
                   $app->make([Repository]Interface::class),
                   $app->make([Service1]::class),
                   $app->make([Service2]::class)
               );
           });

           // Merge config
           $this->mergeConfigFrom(
               __DIR__ . '/../../config/[package].php',
               '[package]'
           );
       }

       /**
        * Bootstrap services.
        *
        * @return void
        */
       public function boot(): void
       {
           // Load migrations
           $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

           // Publish config
           if ($this->app->runningInConsole()) {
               $this->publishes([
                   __DIR__ . '/../../config/[package].php' => config_path('[package].php'),
               ], '[package]-config');
           }
       }
   }
   ```

3. **Binding rules**:
   - **Singleton**: For stateless services, repositories, managers
   - **Transient**: For stateful services (rare)
   - Always use interface → implementation binding

**VALIDATION CHECKPOINT**: After creating service provider, verify:
- [ ] Repository interface bound to Eloquent implementation
- [ ] All services bound as singletons
- [ ] Manager has all dependencies injected
- [ ] Config merged and publishable
- [ ] Migrations loaded

**ACTION**: Mark Phase 9 todo as completed. Create service provider in `apps/Atomy/app/Providers/`.

### Step 3.6: Create Configuration File

1. **Naming**: `[package].php` (kebab-case, e.g., `uom.php`, `tenant.php`)

2. **Configuration template**:
   ```php
   <?php

   return [
       /*
       |--------------------------------------------------------------------------
       | [Feature] Configuration
       |--------------------------------------------------------------------------
       |
       | Description of what this config controls.
       |
       */

       // Feature flags
       'enabled' => env('[PACKAGE]_ENABLED', true),
       'tenant_isolation' => env('[PACKAGE]_TENANT_ISOLATION', false),
       'audit_logging' => env('[PACKAGE]_AUDIT_LOGGING', true),

       // Performance settings
       'cache_enabled' => env('[PACKAGE]_CACHE_ENABLED', true),
       'cache_duration' => env('[PACKAGE]_CACHE_DURATION', 3600),

       // Business settings
       'default_[setting]' => env('[PACKAGE]_DEFAULT_[SETTING]', 'value'),

       // Seed data (optional)
       'seed_[entities]' => [
           'entity1' => ['name' => 'Entity 1', 'code' => 'e1'],
           'entity2' => ['name' => 'Entity 2', 'code' => 'e2'],
       ],
   ];
   ```

3. **Configuration principles**:
   - All settings should have sensible defaults
   - Use `env()` for environment-specific values
   - Group related settings together
   - Document each setting with comments

**VALIDATION CHECKPOINT**: After creating config, verify:
- [ ] All settings have defaults
- [ ] Feature flags for optional integrations
- [ ] Environment variables used appropriately
- [ ] Comprehensive comments

**ACTION**: Mark Phase 10 todo as completed. Create config in `apps/Atomy/config/`.

### Step 3.7: Register Service Provider

Update `apps/Atomy/config/app.php`:

```php
'providers' => [
    // ... existing providers
    App\Providers\[PackageName]ServiceProvider::class,
],
```

**ACTION**: Add the service provider to the providers array.

**COMMIT POINT 📝**: After Phase 3 completion:
```bash
git add apps/Atomy/
git commit -m "feat: Add Atomy implementation for [PackageName] package

- Create migration with [N] tables using ULID primary keys
- Implement [N] Eloquent models with relationships
- Implement Db[Repository] with all CRUD operations
- Create [PackageName]ServiceProvider with IoC bindings
- Create [package].php config with feature flags
- Register service provider in app config

All models implement package interfaces.
All services injected via dependency injection.

Ref: [Requirement codes, e.g., ARC-UOM-0029, ARC-UOM-0030]"
```

**ACTION**: Execute the commit after user confirmation.

## 🔗 Phase 4: Integration

**Objective**: Integrate the new package with dependent packages and Atomy.

### Step 4.1: Identify Integration Points

**DECISION POINT 🛑**: Ask the user:
```markdown
## Integration Analysis

This package may need to integrate with:

1. **Dependent Packages** (packages that will USE this package):
   - [ ] Nexus\Inventory - Uses [feature from this package]
   - [ ] Nexus\Manufacturing - Uses [feature from this package]
   - [ ] Nexus\Procurement - Uses [feature from this package]

2. **Dependency Packages** (packages this package USES):
   - [ ] Nexus\Tenant - For multi-tenancy context
   - [ ] Nexus\AuditLogger - For change tracking
   - [ ] Nexus\Sequencing - For auto-numbering

3. **Atomy Application**:
   - [ ] API endpoints needed? (Controllers, routes)
   - [ ] Middleware needed? (Tenant scoping, auth)
   - [ ] Seeders needed? (Default data)

Questions:
1. Which packages need to integrate with this package?
2. Should I create API endpoints for external access?
3. Should I create seeders for default data?
4. Are there any other integration requirements?
```

**ACTION**: Wait for user response before proceeding.

### Step 4.2: Update Dependent Package Composer Files

For each dependent package:

1. Add package requirement:
   ```json
   {
       "require": {
           "nexus/[package-name]": "*@dev"
       }
   }
   ```

2. Update package's service to use new package:
   ```php
   use Nexus\[NewPackage]\Services\[Service];

   public function __construct(
       private readonly [Service] $[service],
   ) {}
   ```

**ACTION**: Update composer.json files for all dependent packages.

### Step 4.3: Create API Endpoints (If Needed)

If API access is required:

1. **Create controller**:
   ```php
   <?php

   namespace App\Http\Controllers\Api;

   use App\Http\Controllers\Controller;
   use Illuminate\Http\JsonResponse;
   use Illuminate\Http\Request;
   use Nexus\[PackageName]\Services\[Manager];

   final class [Entity]Controller extends Controller
   {
       public function __construct(
           private readonly [Manager] $manager
       ) {}

       public function index(): JsonResponse
       {
           $entities = $this->manager->getAll[Entities]();
           return response()->json($entities);
       }

       public function show(string $code): JsonResponse
       {
           $entity = $this->manager->get[Entity]($code);
           return response()->json($entity);
       }

       public function store(Request $request): JsonResponse
       {
           $validated = $request->validate([
               'code' => 'required|string|max:50',
               'name' => 'required|string|max:100',
               // ... validation rules
           ]);

           $entity = $this->manager->create[Entity]($validated);
           return response()->json($entity, 201);
       }
   }
   ```

2. **Create routes** in `routes/api_[package].php`:
   ```php
   use App\Http\Controllers\Api\[Entity]Controller;
   use Illuminate\Support\Facades\Route;

   Route::prefix('[package]')->group(function () {
       Route::apiResource('[entities]', [Entity]Controller::class);
   });
   ```

3. **Register routes** in `apps/Atomy/app/Providers/RouteServiceProvider.php` or `bootstrap/app.php`.

**ACTION**: Create controllers and routes if API access is needed.

### Step 4.4: Create Seeders (If Needed)

If default data is required:

1. **Create seeder**:
   ```php
   <?php

   namespace Database\Seeders;

   use Illuminate\Database\Seeder;
   use Nexus\[PackageName]\Services\[Manager];

   final class [Package]Seeder extends Seeder
   {
       public function __construct(
           private readonly [Manager] $manager
       ) {}

       public function run(): void
       {
           // Seed default entities
           $entities = config('[package].seed_[entities]', []);

           foreach ($entities as $code => $data) {
               $this->manager->create[Entity]([
                   'code' => $code,
                   'name' => $data['name'],
                   // ... other fields
               ]);
           }
       }
   }
   ```

2. **Register seeder** in `DatabaseSeeder.php`:
   ```php
   public function run(): void
   {
       $this->call([
           [Package]Seeder::class,
       ]);
   }
   ```

**ACTION**: Create seeders if default data is needed.

**COMMIT POINT 📝**: After Phase 4 completion:
```bash
git add .
git commit -m "feat: Add integration points for [PackageName] package

- Update dependent package composer.json files
- Create API endpoints ([Entity]Controller, routes)
- Create [Package]Seeder for default data
- Integrate with [dependent packages]

Integration complete and tested.

Ref: [Integration requirement codes]"
```

**ACTION**: Execute the commit after user confirmation.

## 🧪 Phase 5: Testing

**Objective**: Ensure the package works correctly with comprehensive tests.

### Step 5.1: Create Package Unit Tests

Create tests in `packages/[PackageName]/tests/`:

1. **Test structure**:
   ```
   tests/
   ├── Unit/
   │   ├── ValueObjects/
   │   │   └── [ValueObject]Test.php
   │   └── Services/
   │       └── [Service]Test.php
   └── TestCase.php
   ```

2. **Mock repository in tests**:
   ```php
   <?php

   namespace Nexus\[PackageName]\Tests\Unit\Services;

   use Nexus\[PackageName]\Contracts\[Repository]Interface;
   use Nexus\[PackageName]\Services\[Service];
   use PHPUnit\Framework\TestCase;

   final class [Service]Test extends TestCase
   {
       private [Repository]Interface $repository;
       private [Service] $service;

       protected function setUp(): void
       {
           $this->repository = $this->createMock([Repository]Interface::class);
           $this->service = new [Service]($this->repository);
       }

       #[Test]
       public function it_[does_something](): void
       {
           // Arrange
           $this->repository->expects($this->once())
               ->method('find[Entity]ByCode')
               ->with('test-code')
               ->willReturn($mockEntity);

           // Act
           $result = $this->service->[method]('test-code');

           // Assert
           $this->assertEquals($expected, $result);
       }
   }
   ```

3. **Test coverage goals**:
   - Value objects: 100% (they're simple)
   - Services: 80%+ (test all public methods and edge cases)
   - Validation: 100% (test all validation rules)

**ACTION**: Create unit tests for all package classes.

### Step 5.2: Create Atomy Feature Tests

Create tests in `apps/Atomy/tests/Feature/[PackageName]/`:

1. **Test structure**:
   ```
   tests/Feature/[PackageName]/
   ├── [Entity]ManagementTest.php
   ├── [Entity]ConversionTest.php
   └── [Entity]IntegrationTest.php
   ```

2. **Use database in feature tests**:
   ```php
   <?php

   namespace Tests\Feature\[PackageName];

   use App\Models\[Entity];
   use Illuminate\Foundation\Testing\RefreshDatabase;
   use Nexus\[PackageName]\Services\[Manager];
   use Tests\TestCase;

   final class [Entity]ManagementTest extends TestCase
   {
       use RefreshDatabase;

       #[Test]
       public function it_creates_[entity]_successfully(): void
       {
           // Arrange
           $manager = app([Manager]::class);

           // Act
           $entity = $manager->create[Entity]([
               'code' => 'test',
               'name' => 'Test Entity',
           ]);

           // Assert
           $this->assertInstanceOf([Entity]::class, $entity);
           $this->assertDatabaseHas('[table]', [
               'code' => 'test',
               'name' => 'Test Entity',
           ]);
       }

       #[Test]
       public function it_throws_exception_for_duplicate_code(): void
       {
           // Arrange
           $manager = app([Manager]::class);
           $manager->create[Entity](['code' => 'test', 'name' => 'Test']);

           // Act & Assert
           $this->expectException(Duplicate[Entity]CodeException::class);
           $manager->create[Entity](['code' => 'test', 'name' => 'Test 2']);
       }
   }
   ```

3. **Test API endpoints**:
   ```php
   #[Test]
   public function it_returns_[entities]_via_api(): void
   {
       // Arrange
       [Entity]::factory()->count(3)->create();

       // Act
       $response = $this->getJson('/api/[package]/[entities]');

       // Assert
       $response->assertStatus(200)
           ->assertJsonCount(3)
           ->assertJsonStructure([
               '*' => ['code', 'name', 'created_at'],
           ]);
   }
   ```

**ACTION**: Create feature tests for all functionality.

### Step 5.3: Run Tests and Fix Issues

```bash
# Run package tests
cd packages/[PackageName]
composer test

# Run Atomy tests
cd apps/Atomy
php artisan test --filter=[PackageName]

# Run all tests
php artisan test
```

**VALIDATION CHECKPOINT**: Ensure:
- [ ] All package unit tests pass
- [ ] All Atomy feature tests pass
- [ ] No lint errors or warnings
- [ ] Test coverage meets targets (80%+)

**DECISION POINT 🛑**: If tests fail, analyze failures:
- Are there bugs in the implementation?
- Are there missing validations?
- Are there edge cases not handled?
- Are the tests incorrect?

Fix issues and re-run tests until all pass.

**COMMIT POINT 📝**: After Phase 5 completion:
```bash
git add .
git commit -m "test: Add comprehensive tests for [PackageName] package

Package Unit Tests:
- [N] value object tests (100% coverage)
- [N] service tests ([X]% coverage)
- [N] validation tests (100% coverage)

Atomy Feature Tests:
- [N] management tests (CRUD operations)
- [N] integration tests (with dependent packages)
- [N] API endpoint tests

All tests passing. Ready for use.

Ref: [Test requirement codes]"
```

**ACTION**: Execute the commit after tests pass.

## 📚 Phase 6: Documentation

**Objective**: Create comprehensive documentation for package users and developers.

### Step 6.1: Update REQUIREMENTS.csv

For each requirement:

1. **Add implementation details**:
   - File path where requirement is implemented
   - Method name if applicable
   - Line range if specific

2. **Format**: `[File path]::[Method name] - [Additional context]`

3. **Examples**:
   - `packages/Uom/src/Contracts/UomRepositoryInterface.php::findUnitByCode() - Defines persistence contract`
   - `packages/Uom/src/Services/UomConversionEngine.php::convert() - Implements conversion logic with caching`
   - `apps/Atomy/database/migrations/2025_11_17_200000_create_uom_tables.php - Creates 4 tables with ULID PKs`

**ACTION**: Use `multi_replace_string_in_file` to update all requirements efficiently.

### Step 6.2: Create Implementation Documentation

Create `docs/[PACKAGE]_IMPLEMENTATION.md`:

**Template structure**:

```markdown
# [Package] Implementation

Complete implementation guide for the Nexus [Package] package.

## 📦 Package Structure (packages/[Package]/)

[Show complete file tree with requirement annotations]

## 🚀 Atomy Implementation Structure (apps/Atomy/)

[Show Atomy file tree with requirement annotations]

## ✅ Requirements Satisfied

### Architectural Requirements ([N] total)

- **[REQ-CODE]**: ✅ [Requirement text]
  - [Implementation details]
  - Files: [file paths]
  - Methods: [method names]

[Repeat for all requirements, grouped by category]

## 📝 Usage Examples

### 1. Install Package in Atomy
[Installation instructions]

### 2. Basic Operations
[Code examples]

### 3. Advanced Features
[Code examples]

[Include 8-10 practical, copy-paste examples]

## 🔧 Configuration

[Complete config file with explanations]

## 📊 Database Schema

[Table descriptions with all columns, indexes, foreign keys]

## 🧪 Testing

[Testing strategy and commands]

## 📚 Next Steps

[What to do after implementation]

## 🔒 Security Considerations

[Security features and best practices]

## 📖 Documentation

[Links to related docs]
```

**ACTION**: Create comprehensive implementation documentation.

**COMMIT POINT 📝**: After Phase 6 completion:
```bash
git add docs/ REQUIREMENTS.csv
git commit -m "docs: Add comprehensive documentation for [PackageName] package

- Update REQUIREMENTS.csv with file/method mappings ([N] requirements)
- Create [PACKAGE]_IMPLEMENTATION.md with complete guide
- Add [N] usage examples
- Document database schema
- Document configuration options
- Document testing approach
- Document security considerations

Documentation complete and ready for developers.

Ref: All [PackageName] requirements"
```

**ACTION**: Execute the commit after user confirmation.

## ✅ Phase 7: Final Validation

**Objective**: Ensure everything is complete and production-ready.

### Step 7.1: Completion Checklist

Go through this checklist with the user:

```markdown
## Implementation Completion Checklist

### Package Layer (packages/[PackageName]/)
- [ ] composer.json has no Laravel dependencies
- [ ] All interfaces defined in Contracts/
- [ ] All exceptions defined in Exceptions/
- [ ] All value objects defined in ValueObjects/
- [ ] All services defined in Services/
- [ ] README.md has comprehensive examples
- [ ] All code has strict_types declaration
- [ ] All code has comprehensive docblocks

### Application Layer (apps/Atomy/)
- [ ] Migration creates all necessary tables with ULID PKs
- [ ] All models implement package interfaces
- [ ] Repository implements all interface methods
- [ ] Service provider registers all bindings
- [ ] Config file has all necessary settings
- [ ] Service provider registered in app config

### Integration
- [ ] Dependent packages updated (if any)
- [ ] API endpoints created (if needed)
- [ ] Seeders created (if needed)
- [ ] Integration points tested

### Testing
- [ ] Package unit tests created and passing
- [ ] Atomy feature tests created and passing
- [ ] Test coverage meets targets (80%+)
- [ ] No lint errors or warnings

### Documentation
- [ ] REQUIREMENTS.csv updated with file mappings
- [ ] [PACKAGE]_IMPLEMENTATION.md created
- [ ] Usage examples provided (8-10 examples)
- [ ] Database schema documented
- [ ] Configuration documented
- [ ] Security considerations documented

### Version Control
- [ ] Package skeleton committed
- [ ] Atomy implementation committed
- [ ] Integration committed
- [ ] Tests committed
- [ ] Documentation committed

### Quality
- [ ] Code follows PHP 8.x modern standards
- [ ] Code follows monorepo architecture principles
- [ ] All business logic in package, implementation in Atomy
- [ ] No framework coupling in package layer
```

**DECISION POINT 🛑**: Review checklist with user:
- Are all items checked?
- Are there any missing pieces?
- Are there any improvements needed?

### Step 7.2: Final Demonstration

Show the user a practical example:

```markdown
## [PackageName] Implementation Complete! 🎉

### What's Been Implemented

**Package Layer** ([N] files):
- [N] interfaces in Contracts/
- [N] exceptions in Exceptions/
- [N] value objects in ValueObjects/
- [N] services in Services/

**Application Layer** ([N] files):
- [N] tables in migration
- [N] models in Models/
- 1 repository in Repositories/
- 1 service provider in Providers/
- 1 config file

**Tests** ([N] tests):
- [N] package unit tests (passing)
- [N] Atomy feature tests (passing)

**Documentation**:
- REQUIREMENTS.csv updated ([N] requirements)
- [PACKAGE]_IMPLEMENTATION.md created

### Quick Start Example

```php
// [Show a practical, working example]
```

### Next Steps

1. Run migrations: `php artisan migrate`
2. Seed data (optional): `php artisan db:seed --class=[Package]Seeder`
3. Use in other packages: `composer require nexus/[package-name]`
4. Start building features!

### Questions?

- Need API endpoints? I can create them.
- Need additional features? Let me know.
- Need integration with other packages? I can help.
- Ready to start using it? Here are more examples...
```

**ACTION**: Present the completion summary to the user.

## 🎯 Success Criteria

Implementation is considered **COMPLETE** when:

1. ✅ All requirements from REQUIREMENTS.csv are satisfied
2. ✅ Package layer is framework-agnostic (no Laravel dependencies)
3. ✅ Application layer implements all package interfaces
4. ✅ All integration points are functional
5. ✅ All tests pass (package unit tests + Atomy feature tests)
6. ✅ Documentation is comprehensive and accurate
7. ✅ All code is committed with clear commit messages
8. ✅ User confirms implementation meets their needs

## 🚨 Roadblock Protocol

When you encounter a roadblock:

### 1. Identify the Roadblock Type

- **Missing Information**: Requirement unclear, schema ambiguous
- **User Decision Required**: Architecture choice, naming convention
- **Technical Blocker**: Dependency not implemented, API not available
- **Scope Question**: Feature in/out of scope, complexity concern

### 2. Stop and Document

**ROADBLOCK 🛑**: [Type]

**Context**: [What were you trying to do?]

**Issue**: [What's blocking progress?]

**Options**: [If applicable, list possible solutions]

**Decision Needed**: [What does the user need to decide?]

### 3. Present to User

Use clear, structured format:

```markdown
## 🛑 Decision Required

I've reached a point where I need your input:

**Situation**: [Brief description]

**Question**: [Specific question]

**Options**:
1. [Option 1] - [Pros/Cons]
2. [Option 2] - [Pros/Cons]
3. [Option 3] - [Pros/Cons]

**Recommendation**: [Your suggestion with reasoning]

**Impact**: [How this affects implementation]

What would you like me to do?
```

### 4. Wait for User Response

**DO NOT**:
- Make assumptions about missing information
- Proceed with ambiguous requirements
- Skip features because they're unclear
- Create placeholder implementations

**DO**:
- Wait for explicit user confirmation
- Ask clarifying questions
- Suggest alternatives with trade-offs
- Document the decision for future reference

## 📝 Commit Message Standards

Follow this format for all commits:

```
<type>: <summary>

<detailed description>

<changes made>

Ref: <requirement codes>
```

**Types**:
- `feat`: New feature or package implementation
- `fix`: Bug fix
- `refactor`: Code refactoring without behavior change
- `test`: Adding or updating tests
- `docs`: Documentation changes
- `chore`: Build, config, or dependency updates

**Examples**:

```
feat: Add Inventory package skeleton with core business logic

Implemented framework-agnostic business logic layer for inventory management.

Changes:
- Create package structure with composer.json, LICENSE, README
- Implement 8 interfaces in Contracts/
- Implement 12 exceptions in Exceptions/
- Implement 6 value objects in ValueObjects/
- Implement 5 service classes in Services/
- All code uses PHP 8.2+ features (readonly, enums, match)
- Comprehensive docblocks and strict type hints

Ref: FR-INV-A01, FR-INV-A02, FR-INV-A03, ARC-INV-0027, ARC-INV-0028
```

```
feat: Add Atomy implementation for Inventory package

Implemented Laravel-based application layer for inventory management.

Changes:
- Create migration with 6 tables using ULID primary keys
- Implement 6 Eloquent models with relationships
- Implement DbInventoryRepository with all CRUD operations
- Create InventoryServiceProvider with IoC bindings
- Create inventory.php config with feature flags
- Register service provider in app config

All models implement package interfaces.
All services use dependency injection.

Ref: ARC-INV-0029, ARC-INV-0030, ARC-INV-0031, ARC-INV-0032
```

## 🎓 Agent Self-Check Questions

Before moving to next phase, ask yourself:

1. **Architecture Compliance**
   - Is business logic in package layer?
   - Is implementation in application layer?
   - Is package framework-agnostic?

2. **Requirement Satisfaction**
   - Have I addressed all requirements for this phase?
   - Are there edge cases I'm missing?
   - Do I need user clarification?

3. **Code Quality**
   - Am I using PHP 8.x modern features?
   - Are all dependencies readonly and injected?
   - Are docblocks comprehensive?
   - Are type hints strict?

4. **Integration Completeness**
   - Have I identified all dependent packages?
   - Have I updated their composer.json?
   - Have I tested integration points?

5. **Testing Coverage**
   - Have I tested happy paths?
   - Have I tested error paths?
   - Have I tested edge cases?
   - Have I tested integrations?

6. **Documentation Quality**
   - Is REQUIREMENTS.csv updated accurately?
   - Does implementation doc have enough examples?
   - Is configuration documented?
   - Are security considerations documented?

7. **User Communication**
   - Have I explained what I'm doing?
   - Have I identified decision points?
   - Have I waited for user confirmation?
   - Have I committed appropriately?

## 🎯 Final Notes

- **Be systematic**: Follow the phases in order
- **Be thorough**: Don't skip validation checkpoints
- **Be communicative**: Keep user informed at every decision point
- **Be quality-focused**: Don't compromise on code quality for speed
- **Be integrative**: Remember this package needs to work with others
- **Be user-centric**: This is their codebase, their decisions matter

**Your goal is not just to implement a package, but to implement it RIGHT.**

Good luck! 🚀
