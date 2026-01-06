<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LogToFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:log-to-file';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info('this is a log message');
//        throw new \Exception('This is a runtime exception');
    }
}
