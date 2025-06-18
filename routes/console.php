<?php

Schedule::command('invoices:generate-recurring')
    ->daily()
    ->at('00:00')
    ->appendOutputTo(storage_path('logs/recurring-invoices.log'));

Schedule::command('invoices:update-invoice-status')
    ->daily()
    ->at('00:00')
    ->appendOutputTo(storage_path('logs/update-invoice-status.log'));

//Schedule::command('app:test-mail')
//    ->everyFiveMinutes()
//    ->appendOutputTo(storage_path('logs/test-mail.log'));
