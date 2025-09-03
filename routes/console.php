<?php

use App\Console\Commands\RecurringInvoiceReminder;
use App\Console\Commands\SchedulerIsStillRunning;
use App\Console\Commands\UpdateInvoiceOverDueStatus;
use App\Console\Commands\UpdateInvoiceStatus;

Schedule::command(\App\Console\Commands\GenerateRecurringInvoices::class)
    ->daily()
    ->appendOutputTo(storage_path('logs/recurring-invoices.log'));

Schedule::command(UpdateInvoiceStatus::class)
    ->daily()
    ->appendOutputTo(storage_path('logs/update-invoice-status.log'));

Schedule::command(RecurringInvoiceReminder::class)
    ->daily()
    ->appendOutputTo(storage_path('logs/recurring-invoice-reminder.log'));

Schedule::command( UpdateInvoiceOverDueStatus::class)
    ->daily()
    ->appendOutputTo(storage_path('logs/update-invoice-over-due-status.log'));


Schedule::command('telescope:prune --hours=48')->daily();

Schedule::command(SchedulerIsStillRunning::class)
    ->daily()
    ->appendOutputTo(storage_path('logs/scheduler-is-still-running.log'));


Schedule::command('app:auto-bill-client')
    ->daily()
    ->appendOutputTo(storage_path('logs/app:auto-bill-client.log'));
