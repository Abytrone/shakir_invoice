<?php

namespace App\Constants;

class InvoiceStatus
{
    public const DRAFT = 'draft';
    const SENT = 'sent';
    public const UNPAID = 'unpaid';
    public const PARTIAL = 'partial';
    public const PAID = 'paid';
    public const OVERDUE = 'overdue';
    public const CANCELLED = 'cancelled';

}
