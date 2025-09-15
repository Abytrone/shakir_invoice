<?php

use App\Console\Commands\AutoBillClient;
use App\Console\Commands\GenerateRecurringInvoices;
use App\Console\Commands\LogToFile;
use App\Console\Commands\RecurringInvoiceReminder;
use App\Console\Commands\SchedulerIsStillRunning;
use App\Console\Commands\UpdateInvoiceOverDueStatus;
use App\Console\Commands\UpdateInvoiceStatus;

Schedule::command(GenerateRecurringInvoices::class)
    ->dailyAt('00:00')
    ->appendOutputTo(storage_path('logs/recurring-invoices.log'));

Schedule::command(UpdateInvoiceStatus::class)
    ->dailyAt('00:00')
    ->appendOutputTo(storage_path('logs/update-invoice-status.log'));

Schedule::command(RecurringInvoiceReminder::class)
    ->dailyAt('00:00')
    ->appendOutputTo(storage_path('logs/recurring-invoice-reminder.log'));

Schedule::command( UpdateInvoiceOverDueStatus::class)
    ->dailyAt('00:00')
    ->appendOutputTo(storage_path('logs/update-invoice-over-due-status.log'));


Schedule::command('telescope:prune --hours=48')->dailyAt('00:00');

Schedule::command(SchedulerIsStillRunning::class)
//    ->everyFiveSeconds()
    ->dailyAt(now()->addMinutes(5)->format('H:i'))
    ->appendOutputTo(storage_path('logs/scheduler-is-still-running.log'));


Schedule::command(AutoBillClient::class)
    ->dailyAt('00:00')
    ->appendOutputTo(storage_path('logs/auto-bill-client.log'));

//Schedule::command(LogToFile::class)
//    ->everyMinute();
