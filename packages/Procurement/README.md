# Nexus\Procurement

Framework-agnostic procurement management package providing comprehensive purchase requisition, purchase order, goods receipt, and 3-way matching capabilities for ERP systems.

## Overview

The `Nexus\Procurement` package provides:

- **Purchase Requisition Management**: Complete requisition workflow from draft to approval to PO conversion
- **Purchase Order Processing**: Create POs from requisitions or directly with budget validation
- **Goods Receipt Notes (GRN)**: Record and validate received goods against purchase orders
- **3-Way Matching Engine**: Validate Invoice-PO-GRN alignment for accounts payable
- **Vendor Quote Management**: RFQ process and quote comparison
- **Approval Workflows**: Multi-level requisition approval with authorization checks

## Key Features

### Enterprise-Grade Procurement
- **Requisition-to-PO Conversion**: Seamless conversion with budget validation
- **Direct PO Creation**: Bypass requisition for urgent purchases
- **Segregation of Duties**: Enforces approval rules (requester cannot approve own requisition)
- **Budget Controls**: PO cannot exceed requisition by >10% without re-approval
- **Multi-Currency Support**: Full currency conversion via `Nexus\Currency`

### 3-Way Matching Integration
The package provides concrete implementations for contracts defined in `Nexus\Payable`:
1. **Purchase Order Repository** - Supplies PO line data for matching
2. **Goods Receipt Repository** - Supplies GRN line data for matching
3. **Matching Engine** - Validates Invoice-PO-GRN alignment with configurable tolerances

### Business Rule Enforcement
- Requisition must have ≥1 line item
- Approved requisitions are immutable
- GRN quantity cannot exceed PO quantity
- PO creator cannot create GRN for same PO (segregation of duties)
- Requester cannot approve own requisition

## Installation

```bash
composer require nexus/procurement
```

## Architecture

### Framework-Agnostic Core
- All business logic in `src/Services/`
- All data structures defined via interfaces in `src/Contracts/`
- Zero Laravel dependencies in package layer
- Persistence via repository interfaces

### Integration Points
- **Nexus\Payable**: Provides PO and GRN data for 3-way matching
- **Nexus\Uom**: Unit of measurement validation
- **Nexus\Currency**: Multi-currency support
- **Nexus\Workflow**: Requisition approval workflows
- **Nexus\AuditLogger**: Comprehensive change tracking

## Usage

See `docs/PROCUREMENT_IMPLEMENTATION.md` for complete implementation guide and usage examples.

## License

MIT License - see LICENSE file for details.
