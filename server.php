<?php

/**
 * WebSocket Chat Server
 * 
 * Clean implementation following SOLID principles and design patterns
 */

declare(strict_types=1);

// Configuration
const HOST = '0.0.0.0';
const PORT = 8080;
const WEBSOCKET_GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
const BUFFER_SIZE = 4096;

set_time_limit(0);
ob_implicit_flush();

/**
 * WebSocket Frame Encoder/Decoder
 * Single Responsibility: Handle WebSocket protocol encoding/decoding
 */
class WebSocketFrame
{
    private const OPCODE_TEXT = 0x01;
    private const FIN_BIT = 0x80;
    
    public function decode($data)
    {
        if (strlen($data) < 2) {
            return '';
        }
        
        $payloadLength = ord($data[1]) & 127;
        $maskStart = $this->getMaskStartPosition($payloadLength);
        $masks = substr($data, $maskStart, 4);
        $payload = substr($data, $maskStart + 4);
        
        return $this->unmaskPayload($payload, $masks);
    }
    
    public function encode($payload)
    {
        $frame = chr(self::FIN_BIT | self::OPCODE_TEXT);
        $length = strlen($payload);
        
        $frame .= $this->encodeLength($length);
        $frame .= $payload;
        
        return $frame;
    }
    
    private function getMaskStartPosition($payloadLength)
    {
        if ($payloadLength === 126) {
            return 4;
        } elseif ($payloadLength === 127) {
            return 10;
        }
        return 2;
    }
    
    private function unmaskPayload($payload, $masks)
    {
        $text = '';
        for ($i = 0; $i < strlen($payload); $i++) {
            $text .= $payload[$i] ^ $masks[$i % 4];
        }
        return $text;
    }
    
    private function encodeLength($length)
    {
        if ($length <= 125) {
            return chr($length);
        } elseif ($length <= 65535) {
            return chr(126) . pack('n', $length);
        }
        return chr(127) . pack('NN', 0, $length);
    }
}

/**
 * WebSocket Handshake Handler
 * Single Responsibility: Handle WebSocket handshake protocol
 */
class WebSocketHandshake
{
    public function isHandshakeRequest($data)
    {
        return (bool) preg_match("/Sec-WebSocket-Key: (.*)\\r\\n/", $data);
    }
    
    public function createHandshakeResponse($data)
    {
        if (!preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $data, $matches)) {
            return null;
        }
        
        $key = trim($matches[1]);
        $acceptKey = base64_encode(sha1($key . WEBSOCKET_GUID, true));
        
        return "HTTP/1.1 101 Switching Protocols\r\n" .
               "Upgrade: websocket\r\n" .
               "Connection: Upgrade\r\n" .
               "Sec-WebSocket-Accept: {$acceptKey}\r\n\r\n";
    }
}

/**
 * Chat Message Value Object
 * Encapsulates message data with validation
 */
class ChatMessage
{
    private $username;
    private $message;
    private $time;
    
    public function __construct($username, $message)
    {
        $this->username = $username;
        $this->message = $message;
        $this->time = date('H:i:s');
    }
    
    public static function fromJson($json, $fallbackUsername)
    {
        $decoded = json_decode($json, true);
        
        if ($decoded && isset($decoded['username'], $decoded['message'])) {
            return new self($decoded['username'], $decoded['message']);
        }
        
        return new self($fallbackUsername, $json);
    }
    
    public function toJson()
    {
        return json_encode([
            'from' => $this->username,
            'message' => $this->message,
            'time' => $this->time
        ]);
    }
    
    public function getUsername()
    {
        return $this->username;
    }
    
    public function getMessage()
    {
        return $this->message;
    }
}

/**
 * Client Connection
 * Represents a single WebSocket client connection
 */
class ClientConnection
{
    /** @var resource */
    private $socket;
    private $handshakeCompleted = false;
    private $address;
    private $username = null;
    private $selection = null;
    private $id = null;
    private $role = null;
    
    public function __construct($socket)
    {
        $this->socket = $socket;
        $this->address = stream_socket_get_name($socket, true) ?: 'unknown';
        stream_set_blocking($socket, false);
    }
    
    public function getSocket()
    {
        return $this->socket;
    }
    
    public function getAddress()
    {
        return $this->address;
    }

    public function getId()
    {
        return $this->id ?? $this->address;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setUsername($name)
    {
        $this->username = $name;
    }

    public function setRole($role)
    {
        $this->role = $role;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setSelection($value)
    {
        $this->selection = $value;
    }

    public function getSelection()
    {
        return $this->selection;
    }
    
    public function isHandshakeCompleted()
    {
        return $this->handshakeCompleted;
    }
    
    public function completeHandshake()
    {
        $this->handshakeCompleted = true;
    }
    
    public function send($data)
    {
        return @fwrite($this->socket, $data) !== false;
    }
    
    public function read()
    {
        return @fread($this->socket, BUFFER_SIZE) ?: '';
    }
    
    public function close()
    {
        @fclose($this->socket);
    }
}

/**
 * Client Manager
 * Manages all connected clients
 */
class ClientManager
{
    /** @var ClientConnection[] */
    private $clients = array();
    private $nextId = 1;
    private $revealed = false;
    
    public function add($client)
    {
        $client->setId((string)$this->nextId++);
        $this->clients[(int)$client->getSocket()] = $client;
    }

    public function findByUsername(string $username)
    {
        foreach ($this->clients as $client) {
            if ($client->getUsername() === $username) {
                return $client;
            }
        }
        return null;
    }

    /**
     * Merge state from an existing client into the incoming client (which keeps the new socket).
     * Close the old socket and remove the old client entry.
     */
    public function mergeClients(ClientConnection $incoming, ClientConnection $existing)
    {
        $oldSocket = $existing->getSocket();

        // remove old mapping first
        unset($this->clients[(int)$oldSocket]);

        // copy identity/state from existing to incoming
        $incoming->setId($existing->getId());
        $incoming->setUsername($existing->getUsername());
        $incoming->setRole($existing->getRole());
        $incoming->setSelection($existing->getSelection());

        // close the old socket resource
        @fclose($oldSocket);

        // ensure the incoming client is stored under its socket (it should already be),
        // but make sure the key points to the incoming object
        $this->clients[(int)$incoming->getSocket()] = $incoming;
    }
    
    public function remove($client)
    {
        unset($this->clients[(int)$client->getSocket()]);
    }
    
    public function findBySocket($socket)
    {
        return isset($this->clients[(int)$socket]) ? $this->clients[(int)$socket] : null;
    }

    public function findById(string $id)
    {
        foreach ($this->clients as $client) {
            if ((string)$client->getId() === (string)$id) return $client;
        }
        return null;
    }
    
    public function getAll()
    {
        return $this->clients;
    }
    
    public function getSockets()
    {
        return array_map(function($client) { return $client->getSocket(); }, $this->clients);
    }
    
    public function broadcast($data, $exclude = null)
    {
        foreach ($this->clients as $client) {
            if ($exclude && $client === $exclude) {
                continue;
            }
            
            if ($client->isHandshakeCompleted()) {
                $client->send($data);
            }
        }
    }

    public function broadcastUsers()
    {
        // prepare some state
        $usersForAll = [];
        // count developers (voters)
        $developerCount = 0;
        foreach ($this->clients as $c) {
            if (($c->getRole() ?? 'DEVELOPER') === 'DEVELOPER') $developerCount++;
        }

        // check if all developers selected
        $devsAllSelected = true;
        if ($developerCount === 0) {
            $devsAllSelected = false;
        } else {
            foreach ($this->clients as $c) {
                if (($c->getRole() ?? 'DEVELOPER') === 'DEVELOPER' && $c->getSelection() === null) {
                    $devsAllSelected = false;
                    break;
                }
            }
        }

        $frameHandler = new WebSocketFrame();

        // send personalized payload to each recipient so they always see their own selection
        foreach ($this->clients as $recipient) {
            $users = [];
            foreach ($this->clients as $client) {
                $showSelection = false;
                // reveal for everyone if round revealed or all devs selected
                if ($this->revealed || $devsAllSelected) {
                    $showSelection = true;
                }
                // always show the recipient's own selection to them
                if ($client === $recipient) {
                    $showSelection = true;
                }

                $users[] = [
                    'id' => $client->getId(),
                    'name' => $client->getUsername() ?? ('User ' . $client->getId()),
                    'role' => $client->getRole() ?? 'DEVELOPER',
                    'selection' => $client->getSelection()
                ];
            }

            $payload = json_encode(['type' => 'users', 'users' => $users, 'revealed' => $this->revealed, 'youId' => $recipient->getId()]);
            $frame = $frameHandler->encode($payload);
            if ($recipient->isHandshakeCompleted()) {
                $recipient->send($frame);
            }
        }
    }

    public function setRevealed($v)
    {
        $this->revealed = $v;
    }
    public function isRevealed()
    {
        return $this->revealed;
    }

    public function resetRound()
    {
        $this->revealed = false;
        foreach ($this->clients as $client) {
            $client->setSelection(null);
        }
    }
    
    public function allSelected()
    {
        // consider only Developers as voters
        $developerCount = 0;
        foreach ($this->clients as $c) {
            if (($c->getRole() ?? 'DEVELOPER') === 'DEVELOPER') $developerCount++;
        }

        if ($developerCount === 0) return false;

        foreach ($this->clients as $client) {
            if (($client->getRole() ?? 'DEVELOPER') === 'DEVELOPER' && $client->getSelection() === null) {
                return false;
            }
        }

        return true;
    }

    public function hasLeader()
    {
        foreach ($this->clients as $c) {
            if (($c->getRole() ?? 'DEVELOPER') === 'LEADER') return true;
        }
        return false;
    }
}

/**
 * Logger
 * Centralized logging
 */
class Logger
{
    public function info($message)
    {
        echo "[INFO] {$message}\n";
    }
    
    public function debug($message)
    {
        echo "[DEBUG] {$message}\n";
    }
    
    public function error($message)
    {
        echo "[ERROR] {$message}\n";
    }
}

/**
 * WebSocket Chat Server
 * Orchestrates all components following Single Responsibility Principle
 */
class WebSocketChatServer
{
    /** @var resource */
    private $serverSocket;
    private $clientManager;
    private $frameHandler;
    private $handshakeHandler;
    private $logger;
    
    public function __construct($host, $port)
    {
        $this->clientManager = new ClientManager();
        $this->frameHandler = new WebSocketFrame();
        $this->handshakeHandler = new WebSocketHandshake();
        $this->logger = new Logger();
        
        $this->createSocket($host, $port);
    }
    
    private function createSocket($host, $port)
    {
        $this->serverSocket = stream_socket_server(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr
        );
        
        if (!$this->serverSocket) {
            throw new RuntimeException("Failed to create socket: {$errstr} ({$errno})");
        }
        
        $this->logger->info("WebSocket server started on ws://{$host}:{$port}");
    }
    
    public function run()
    {
        while (true) {
            $this->processConnections();
        }
    }
    
    private function processConnections()
    {
        $read = $this->clientManager->getSockets();
        $read[] = $this->serverSocket;
        $write = $except = null;
        
        if (stream_select($read, $write, $except, null) === false) {
            return;
        }
        
        if (in_array($this->serverSocket, $read)) {
            $this->acceptNewConnection();
            unset($read[array_search($this->serverSocket, $read)]);
        }
        
        foreach ($read as $socket) {
            $this->handleClientData($socket);
        }
    }
    
    private function acceptNewConnection()
    {
        $socket = @stream_socket_accept($this->serverSocket, 0);
        if (!$socket) {
            return;
        }
        
        $client = new ClientConnection($socket);
        $this->clientManager->add($client);
        $this->logger->info("New connection from {$client->getAddress()}");
    }
    
    private function handleClientData($socket)
    {
        $client = $this->clientManager->findBySocket($socket);
        if (!$client) {
            return;
        }
        
        $data = $client->read();
        
        if ($data === '' || $data === false) {
            $this->disconnectClient($client);
            return;
        }
        
        if (!$client->isHandshakeCompleted()) {
            $this->performHandshake($client, $data);
            return;
        }
        
        $this->processMessage($client, $data);
    }
    
    private function performHandshake($client, $data)
    {
        $response = $this->handshakeHandler->createHandshakeResponse($data);
        
        if ($response) {
            $client->send($response);
            $client->completeHandshake();
            $this->logger->info("Handshake completed: {$client->getAddress()}");
        }
    }
    
    private function processMessage($client, $data)
    {
        $messageText = $this->frameHandler->decode($data);
        
        if ($messageText === '') {
            return;
        }
        // try to parse structured payloads (join/select)
        $decoded = json_decode($messageText, true);

        if (is_array($decoded) && isset($decoded['type'])) {
            $type = $decoded['type'];

            if ($type === 'join' && isset($decoded['username'])) {
                $username = (string)$decoded['username'];

                // if a user with same username already exists, reuse that user instead of creating a new one
                $existing = $this->clientManager->findByUsername($username);
                if ($existing && $existing !== $client) {
                    $this->clientManager->mergeClients($client, $existing);
                    $this->logger->info("Reused existing user: {$client->getUsername()} ({$client->getAddress()})");
                    $this->clientManager->broadcastUsers();
                    return;
                }

                // normal join for a new username
                $client->setUsername($username);
                // set role if provided and valid
                $role = $decoded['role'] ?? null;
                if (is_string($role) && in_array($role, ['DEVELOPER','LEADER','ANALYST'])) {
                    $client->setRole($role);
                } else {
                    $client->setRole('DEVELOPER');
                }

                $this->logger->info("User joined: {$client->getUsername()} ({$client->getAddress()})");
                // broadcast updated user list
                $this->clientManager->broadcastUsers();
                return;
            }

            if ($type === 'select' && array_key_exists('value', $decoded)) {
                // only Developers can vote
                if (($client->getRole() ?? 'DEVELOPER') !== 'DEVELOPER') {
                    $this->logger->info("Ignored selection (not allowed) from {$client->getUsername()} [role={$client->getRole()}]");
                    return;
                }

                // ignore changes if round already revealed
                if ($this->clientManager->isRevealed()) {
                    $this->logger->info("Ignored selection (already revealed) from {$client->getUsername()}");
                    return;
                }

                $client->setSelection($decoded['value']);
                $this->logger->info("User selection: {$client->getUsername()} => {$decoded['value']}");
                // if all developers selected, mark revealed
                if ($this->clientManager->allSelected()) {
                    $this->clientManager->setRevealed(true);
                }
                $this->clientManager->broadcastUsers();
                return;
            }

            if ($type === 'reveal') {
                $role = $client->getRole() ?? 'DEVELOPER';
                $canReveal = false;
                if ($role === 'LEADER') $canReveal = true;
                if ($role === 'ANALYST' && !$this->clientManager->hasLeader()) $canReveal = true;
                if ($canReveal) {
                    $this->clientManager->setRevealed(true);
                    $this->logger->info("Reveal triggered by {$client->getUsername()} [role={$role}]");
                    $this->clientManager->broadcastUsers();
                } else {
                    $this->logger->info("Reveal denied for {$client->getUsername()} [role={$role}]");
                }
                return;
            }

            if ($type === 'attention') {
                $target = $decoded['target'] ?? null;
                $fromId = $client->getId();
                $fromName = $client->getUsername() ?? ('User ' . $fromId);

                $payload = json_encode([
                    'type' => 'attention',
                    'from' => $fromId,
                    'fromName' => $fromName,
                    'target' => $target
                ]);

                $frame = $this->frameHandler->encode($payload);

                if ($target) {
                    $recipient = $this->clientManager->findById((string)$target);
                    if ($recipient && $recipient->isHandshakeCompleted()) {
                        $recipient->send($frame);
                        $this->logger->info("Attention from {$fromName} to {$target}");
                    } else {
                        $this->logger->info("Attention target not found or not ready: {$target}");
                    }
                } else {
                    // broadcast if no specific target provided
                    $this->clientManager->broadcast($frame);
                    $this->logger->info("Attention broadcast from {$fromName}");
                }

                return;
            }

            if ($type === 'reset') {
                $role = $client->getRole() ?? 'DEVELOPER';
                $canReset = false;
                if ($role === 'LEADER') $canReset = true;
                if ($role === 'ANALYST' && !$this->clientManager->hasLeader()) $canReset = true;
                if ($canReset) {
                    $this->clientManager->resetRound();
                    $this->logger->info("Reset triggered by {$client->getUsername()} [role={$role}]");
                    $this->clientManager->broadcastUsers();
                } else {
                    $this->logger->info("Reset denied for {$client->getUsername()} [role={$role}]");
                }
                return;
            }
        }

        // fallback to chat message
        $message = ChatMessage::fromJson($messageText, $client->getAddress());
        $this->logger->debug("Message from {$message->getUsername()}: {$message->getMessage()}");
        
        try {
            $frame = $this->frameHandler->encode($message->toJson());
            $this->clientManager->broadcast($frame, $client);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
    
    private function disconnectClient($client)
    {
        $this->logger->info("Client disconnected: {$client->getAddress()}");
        $client->close();
        $this->clientManager->remove($client);
        // broadcast updated users list when someone disconnects
        $this->clientManager->broadcastUsers();
    }
}

// Bootstrap
try {
    $server = new WebSocketChatServer(HOST, PORT);
    $server->run();
} catch (Exception $e) {
    echo "Server error: {$e->getMessage()}\n";
    exit(1);
}
