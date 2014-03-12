<?php

namespace Rephp\Server;

/**
 * @author Kazuyuki Hayashi <hayashi@valnur.net>
 */
interface ServerInterface
{

    /**
     * @param string string $address
     */
    public function run($address = '127.0.0.1:8000');

    /**
     * @param string $address
     */
    public function listen($address);

    /**
     * @param string $address
     * @throws \Rephp\Exception\Exception
     * @return resource
     */
    public function createSocket($address);

}