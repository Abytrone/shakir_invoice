<?php

namespace App\Constants;

class PayStackPayment
{
    public const EVENT_DIRECT_DEBIT_AUTHORIZATION_CREATED = 'direct_debit.authorization.created';
    public const EVENT_DIRECT_DEBIT_AUTHORIZATION_ACTIVE = 'direct_debit.authorization.active';
    public const EVENT_CHARGE_SUCCESS = 'charge.success';
}
