<?php

namespace Rephp\Socket;

use Rephp\Scheduler\SystemCall;
use Rephp\Scheduler\Task;
use Rephp\Scheduler\Value;

/**
 * @author Kazuyuki Hayashi <hayashi@valnur.net>
 */
class StreamSocket extends Socket implements StreamSocketInterface
{

    /**
     * @return string
     */
    public function getRemoteName()
    {
        return stream_socket_get_name($this->socket, true);
    }

    /**
     * @return string
     */
    public function getLocalName()
    {
        return stream_socket_get_name($this->socket, false);
    }

    /**
     * @param bool $block
     */
    public function block($block = false)
    {
        socket_set_blocking($this->socket, $block);
    }

    /**
     * @return static
     */
    public function accept()
    {
        yield new SystemCall(function (Task $task, SocketSchedulerInterface $scheduler) {
            $scheduler->addReader($this, $task);
        }, 'ss::accept');
        yield new Value(new static(stream_socket_accept($this->socket, 0), $this->loop));
    }

    /**
     * @param $length
     *
     * @return string
     */
    public function read($length)
    {
        yield new SystemCall(function (Task $task, SocketSchedulerInterface $scheduler) {
            $scheduler->addReader($this, $task);
        });
        yield new Value(parent::read($length));
    }

    /**
     * @param $data
     *
     * @return int
     */
    public function write($data)
    {
        yield new SystemCall(function (Task $task, SocketSchedulerInterface $scheduler) {
            $scheduler->addWriter($this, $task);
        });
        yield new Value(parent::write($data));
    }

}