<?php

namespace Rephp\Socket;

/**
 * @author Kazuyuki Hayashi <hayashi@valnur.net>
 */
class ProtectedStreamSocket extends ProtectedSocket
{

    /**
     * @var StreamSocket
     */
    protected $socket;

    /**
     * @param StreamSocket $socket
     */
    public function __construct(StreamSocket $socket)
    {
        $this->socket = $socket;
    }

    /**
     * @return string
     */
    public function getRemoteName()
    {
        return $this->socket->getRemoteName();
    }

    /**
     * @return string
     */
    public function getLocalName()
    {
        return $this->socket->getLocalName();
    }

}