<?php

namespace Rephp\Server;

use Rephp\Socket\ProtectedStreamSocket;
use Rephp\Socket\StreamSocket;

/**
 * @author Kazuyuki Hayashi <hayashi@valnur.net>
 */
abstract class AbstractHandler implements HandlerInterface
{

    /**
     * @param StreamSocket $socket
     *
     * @return \Generator
     */
    public function handleClient(StreamSocket $socket)
    {
        $data = (yield $socket->read(8048));

        $response = $this->handleRequest($data, new ProtectedStreamSocket($socket));

        yield $socket->write($response);
        yield $socket->close();
    }

    /**
     * @param string                $input
     * @param ProtectedStreamSocket $socket
     *
     * @return string
     */
    abstract public function handleRequest($input, ProtectedStreamSocket $socket);

}