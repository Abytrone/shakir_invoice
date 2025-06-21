<?php

Schedule::command('invoices:generate-recurring')
    ->daily()
    ->at('00:00')
    ->appendOutputTo(storage_path('logs/recurring-invoices.log'));

Schedule::command('invoices:update-invoice-status')
    ->daily()
    ->at('00:00')
    ->appendOutputTo(storage_path('logs/update-invoice-status.log'));

Schedule::command('invoice:recurring-invoice-reminder')
    ->daily()
    ->at('00:00')
    ->appendOutputTo(storage_path('logs/recurring-invoice-reminder.log'));


Schedule::command('invoice:update-invoice-over-due-status')
    ->daily()
    ->at('00:00')
    ->appendOutputTo(storage_path('logs/update-invoice-over-due-status.log'));

