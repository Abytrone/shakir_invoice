<?php

namespace App\Console\Commands;

use Illuminate\Bus\Queueable;
use Illuminate\Console\Command;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;
use Illuminate\Mail\Mailable;
class TestMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-mail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    public function handle(): void
    {
        Mail::to('mahmudsheikh25@gmail.com')
            ->send(new \App\Mail\TestMail());
    }
}
