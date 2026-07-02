<?php

namespace App\Constants;

class PaymentStatus
{
    const PENDING = 'pending';
    const COMPLETED = 'completed';
    const FAILED = 'failed';

    const MAX_ATTEMPTS = 3;
}
