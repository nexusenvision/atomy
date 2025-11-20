<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Purchase Order Tolerance
    |--------------------------------------------------------------------------
    |
    | Maximum percentage that a PO total can exceed the requisition total.
    | BUS-PRO-0101: Prevents excessive budget overruns.
    |
    */
    'po_tolerance_percent' => env('PROCUREMENT_PO_TOLERANCE_PERCENT', 10.0),

    /*
    |--------------------------------------------------------------------------
    | Three-Way Matching Tolerances
    |--------------------------------------------------------------------------
    |
    | Tolerances for quantity and price variance during 3-way matching.
    | Used by the MatchingEngine when comparing PO, GRN, and Invoice.
    |
    */
    'quantity_tolerance_percent' => env('PROCUREMENT_QUANTITY_TOLERANCE_PERCENT', 5.0),
    'price_tolerance_percent' => env('PROCUREMENT_PRICE_TOLERANCE_PERCENT', 5.0),

    /*
    |--------------------------------------------------------------------------
    | Auto-Numbering Patterns
    |--------------------------------------------------------------------------
    |
    | Patterns for auto-generating document numbers.
    | Integrates with Nexus\Sequencing package.
    |
    */
    'requisition_number_pattern' => env('PROCUREMENT_REQ_PATTERN', 'REQ-{YYYY}-{####}'),
    'po_number_pattern' => env('PROCUREMENT_PO_PATTERN', 'PO-{YYYY}-{####}'),
    'grn_number_pattern' => env('PROCUREMENT_GRN_PATTERN', 'GRN-{YYYY}-{####}'),
    'rfq_number_pattern' => env('PROCUREMENT_RFQ_PATTERN', 'RFQ-{YYYY}-{####}'),
];
