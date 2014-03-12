<?php

namespace Rephp\Server;

use Rephp\Socket\StreamSocket;

/**
 * @author Kazuyuki Hayashi <hayashi@valnur.net>
 */
interface HandlerInterface
{

    /**
     * @param StreamSocket $socket
     * @return \Generator
     */
    public function handleClient(StreamSocket $socket);


}