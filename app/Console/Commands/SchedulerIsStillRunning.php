<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mail;

class SchedulerIsStillRunning extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scheduler-is-still-running';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    public function handle(): void
    {
        try {
            $this->info(now()->toString() . 'Sending email to notify that the scheduler is still running...');
            Mail::to('mahmudsheikh25@gmail.com')
                ->send(new \App\Mail\SchedulerIsStillRunning());
        } catch (\Exception $e) {
            $this->error(now()->toString() .'Failed to send email: ' . $e->getMessage());
        }

    }
}
