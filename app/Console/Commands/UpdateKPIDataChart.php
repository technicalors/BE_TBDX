<?php

namespace App\Console\Commands;

use App\Admin\Controllers\KPIController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateKPIDataChart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updatekpidata:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public $controller;
    public function __construct(KPIController $controller)
    {
        parent::__construct();
        $this->controller = $controller;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info('Command your:command-name is running at ' . now());
        $this->controller->cronjob();
        return 0;
    }
}
