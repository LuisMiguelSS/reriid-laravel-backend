<?php
namespace App\Console\Commands;

require 'vendor\autoload.php';

use Illuminate\Console\Command;
use Ratchet\Server\IoServer;
use \Ratchet\Http\HttpServer;
use \Ratchet\WebSocket\WsServer;
use App\Http\Controllers\WebSocketController;

class WebSocketServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:init';

    /**
     * The port the websocket will be listening on.
     * 
     * @var int
     */
    protected static $port = 50100;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command starts the websocket server that is used for realtime chatting.';

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
     * @return mixed
     */
    public function handle()
    {
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(new WebSocketController())
            ),
            WebSocketServer::$port 
       );
       $server->run();
    }
}
