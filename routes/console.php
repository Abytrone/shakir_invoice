<?php

Schedule::command('invoices:generate-recurring')
    ->daily()
    ->at('00:00')
    ->appendOutputTo(storage_path('logs/recurring-invoices.log'));

Schedule::command('invoices:update-invoice-status')
    ->EveryFiveMinutes()
    ->appendOutputTo(storage_path('logs/update-invoice-status.log'));
