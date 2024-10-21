<?php

namespace App\Console\Commands\Api\V1;

use App\Models\User;
use Illuminate\Console\Command;

class RestartDailyRejection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:restart-daily-rejection';

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
        $users = User::where('role', 'agent')->get();

        foreach($users as $user){
            $user->update(['offers_declined' => 0]);
        }
    }
}
