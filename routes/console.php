<?php

Schedule::command('invoices:generate-recurring')
    ->daily()
    ->appendOutputTo(storage_path('logs/recurring-invoices.log'));

Schedule::command('invoices:update-invoice-status')
    ->daily()
    ->appendOutputTo(storage_path('logs/update-invoice-status.log'));

Schedule::command('invoice:recurring-invoice-reminder')
    ->daily()
    ->appendOutputTo(storage_path('logs/recurring-invoice-reminder.log'));

Schedule::command('invoice:update-invoice-over-due-status')
    ->daily()
    ->appendOutputTo(storage_path('logs/update-invoice-over-due-status.log'));


Schedule::command('telescope:prune --hours=48')->daily();

Schedule::command('app:scheduler-is-still-running')
    ->twiceDaily(0, 12)
    ->appendOutputTo(storage_path('logs/scheduler-is-still-running.log'));


Schedule::command('app:auto-bill-client')
    ->daily()
    ->appendOutputTo(storage_path('logs/app:auto-bill-client.log'));
