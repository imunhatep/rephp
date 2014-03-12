<?php

namespace Rephp\Socket;

/**
 * @author Kazuyuki Hayashi <hayashi@valnur.net>
 */
class ProtectedSocket
{

    /**
     * @var Socket
     */
    protected $socket;

    /**
     * @param Socket $socket
     */
    public function __construct(Socket $socket)
    {
        $this->socket = $socket;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->socket->getId();
    }


}