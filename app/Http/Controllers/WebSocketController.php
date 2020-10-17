<?php

namespace App\Http\Controllers;

use DateTime;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class WebSocketController extends Controller implements MessageComponentInterface
{
    private $connections = [];

    /**
     * Called when a new connection is opened.
     * @param  ConnectionInterface $conn The socket that just connected
     * @throws \Exception
     */
    function onOpen(ConnectionInterface $conn){
        $this->connections[$conn->resourceId] = compact('conn') + ['username' => null];
    }
    
    /**
     * Called when a client sends data through the socket
     * @param  \Ratchet\ConnectionInterface $conn The socket
     * @param  string $msg The message received
     * @throws \Exception
     */
    function onMessage(ConnectionInterface $from, $msg){

        $message = json_decode($msg, true);

        if (!isset($message['action']) || !isset($message['data'])) {
            $this->sendMalformedJSONNotification($from);
            return;

        } else {

            // Has action
            switch ($message['action']) {
                case 'set':
                    // Set Username
                    if (!isset($message['data']['username'])) {
                        $this->sendMalformedJSONNotification($from);
                        return;
                    }

                    // Check if it already exists
                    foreach($this->connections as $resourceId => $connection) {
                        if (isset($connection['username'])) {
                            if ($connection['username'] == $message['data']['username']) {
                                $from->send(json_encode(['error' => 'Username already in use']));
                                return;
                            }
                        }

                    }
                    
                    $this->connections[$from->resourceId]['username'] = $message['data']['username'];
                    $from->send(json_encode('Username successfully set.'));

                break;
                case 'send-message':

                    // Check user has username
                    if (!$this->hasUsername($from)) {
                        $from->send(json_encode(['error' => 'A username must be set.']));
                        return;
                    }

                    // Send message to user
                    if (!isset($message['data']['message']) || !isset($message['data']['to'])) {
                        $this->sendMalformedJSONNotification($from);
                        return;
                    }
                    
                    // Check target is not same as sender
                    if ($message['data']['to'] == $this->getUsername($from)) {
                        $from->send(json_encode(['error' => 'You cannot send a message to yourself.']));
                    }

                    // Search user
                    foreach($this->connections as $resourceId => &$connection) {

                        if (isset($connection['username'])) {
                            if ($connection['username'] == $message['data']['to']) {
                                $connection['conn']->send(json_encode([
                                    'realId' => $message['data']['realId'],
                                    'from' => $this->getUsername($from),
                                    'message' => $message['data']['message'],
                                    'timestamp' => (new DateTime())->format('c')
                                ]));
                                return;
                            }
                        }

                    }
                    $from->send(json_encode(['error' =>'User is not available.']));

                default:
                    $from->send(json_encode(['error' => 'Unsupported action type.']));
                    break;
            }
        }
    }
    
     /**
     * Called before or after a socket is closed (depends on how it's closed). 
     * SendMessage to $conn will not result in an error if it has already been closed.
     * 
     * @param  ConnectionInterface $conn The socket/connection that is closing/closed
     * @throws \Exception
     */
    function onClose(ConnectionInterface $conn){
        unset($this->connections[$conn->resourceId]);
    }
    
     /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
     * @param  ConnectionInterface $conn
     * @param  \Exception $e
     * @throws \Exception
     */
    function onError(ConnectionInterface $conn, \Exception $e){
        echo "An error occurred with connection $conn->resourceId: " .$e->getMessage()." at line ".$e->getLine();

        unset($this->connections[$conn->resourceId]);

        $conn->close();
    }

    function sendMalformedJSONNotification(ConnectionInterface $connection) {
        $connection->send(json_encode([
            'error' => 'Malformed JSON: No correct action or data indicated.'
        ]));
    }

    function hasUsername(ConnectionInterface $connection) {
        return isset($this->connections[$connection->resourceId]['username']);
    }

    function getUsername(ConnectionInterface $connection) {
        return $this->hasUsername($connection)
                ?
                $this->connections[$connection->resourceId]['username']
                :
                null;
    }
    
}
