<?php

namespace App\Console\Commands;

use App\Admin\Controllers\ApiController;
use App\Models\Machine;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\Response;

class WebSocketListener extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ws:listen';

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

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $machines = Machine::whereNotNull('device_id')->get();
        $client = new Client();
        $token = "eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJtZXNzeXN0ZW1AZ21haWwuY29tIiwidXNlcklkIjoiNGQxYzg5NTAtODVkOC0xMWVlLTgzOTItYTUxMzg5MTI2ZGM2Iiwic2NvcGVzIjpbIlRFTkFOVF9BRE1JTiJdLCJzZXNzaW9uSWQiOiJiOGFjODk2Yy1lNjc5LTQ1NzAtOWVkMi1iZDk3YzI5MDVhY2YiLCJpc3MiOiJ0aGluZ3Nib2FyZC5pbyIsImlhdCI6MTcwMTA3NTU1MywiZXhwIjoxNzAxMDg0NTUzLCJlbmFibGVkIjp0cnVlLCJpc1B1YmxpYyI6ZmFsc2UsInRlbmFudElkIjoiMzYwY2MyMjAtODVkOC0xMWVlLTgzOTItYTUxMzg5MTI2ZGM2IiwiY3VzdG9tZXJJZCI6IjEzODE0MDAwLTFkZDItMTFiMi04MDgwLTgwODA4MDgwODA4MCJ9.UPFpes-J9Y-pc04inh1Lueh6N4Rkl-Vx8MXDKPRFiZlOnH-kdCKjJVrVPSLtV0Xr2UsEMGoZmRGHAPq6P93g8g";
        foreach($machines as $key=>$machine){
            $response = $client->get('http://113.176.95.167:3030/api/plugins/telemetry/DEVICE/'.$machine->device_id.'/values/timeseries',[
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]);
            $res = (array)json_decode($response->getBody());
            $data = [];
            foreach($res as $key=>$value){
                $data[$key] = $value[0]->value;
            }
            $data = json_encode($data);
            if($machine->id === "So01"){
                $this->info("Received message: $data");
            }
        }
    }
}
