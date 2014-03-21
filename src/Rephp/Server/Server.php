<?php

namespace Rephp\Server;

use Evenement\EventEmitter;
use React\Socket\ConnectionException;
use React\Socket\ServerInterface;
use Rephp\Exception\Exception;
use Rephp\LoopEvent\SchedulerLoopInterface;
use Rephp\Scheduler\SystemCall;
use Rephp\Scheduler\Task;
use Rephp\Socket\Socket;

/**
 * Simple HTTP Server Implementation
 *
 * @author Artem <Aly> Suahrev
 *
 * @codeCoverageIgnore
 */
class Server extends EventEmitter implements ServerInterface
{
    private $loop;
    private $server;
    private $isClosed;

    function __construct(SchedulerLoopInterface $loop)
    {
        $this->isClosed = false;
        $this->loop = $loop;
    }

    /**
     * @param $port
     * @param string $host
     * @return resource
     * @throws \React\Socket\ConnectionException
     */
    function listen($port, $host = '127.0.0.1')
    {
        $this->server = @stream_socket_server('tcp://' . $host . ':' . $port, $no, $str);
        if (!$this->server) {
            throw new ConnectionException("$str ($no)");
        }

        $this->loop->add($this->createServer(), 'Listen server');
    }

    /**
     * @return \Generator
     * @throws \Rephp\Exception\Exception
     */
    protected function createServer()
    {
        $server = new Socket($this->server, $this->loop);
        $server->block(false);

        yield $this->accept($server);
    }


    /**
     * @param Socket $server
     * @return \Generator
     */
    protected function accept(Socket $server)
    {
        yield new SystemCall(function (Task $task, SchedulerLoopInterface $scheduler) use ($server){
            $scheduler->addReader($server, $task);
        }, 's:accept');

        while (!$this->isClosed) {
            yield new SystemCall(function (Task $task, SchedulerLoopInterface $scheduler) use ($server){
                $this->handleClient(new Socket(stream_socket_accept($server->getRaw(), 0), $this->loop));
            });
        }

        $server->close();
    }

    function handleClient(Socket $client)
    {
        $client->block(false);

        /** @var Socket $client */
        $this->emit('connection', array($client));
        $client->resume();
    }

    function getPort()
    {
        $name = stream_socket_get_name($this->server, false);

        return (int)substr(strrchr($name, ':'), 1);
    }

    function shutdown()
    {
        $this->isClosed = true;
        $this->loop->removeStream($this->server);
        $this->removeAllListeners();
    }
}