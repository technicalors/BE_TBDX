<?php

namespace App\Console\Commands;

use App\Admin\Controllers\MESUsageRateController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DailyDataUsageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dailydatausage:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lấy dữ liệu sử dụng phần mềm mỗi ngày';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public $controller;
    public function __construct(MESUsageRateController $controller)
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
        $this->controller->cronjob();
        return 0;
    }
}
