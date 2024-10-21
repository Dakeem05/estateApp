<?php

namespace App\Console\Commands\Api\V1;

use App\Models\Apartment;
use Illuminate\Console\Command;

class RestartDailyViews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:restart-daily-views';

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
        $apartments = Apartment::all();

        foreach($apartments as $apartment){
            $apartment->update(['daily_views' => 0]);
        }
    }
}
