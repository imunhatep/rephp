<?php
/**
 * Created by PhpStorm.
 * User: aly
 * Date: 3/5/14
 * Time: 11:45 AM
 */

namespace Rephp\LoopEvent;


use React\EventLoop\LoopInterface;
use Rephp\Scheduler\Task;
use Rephp\Socket\SocketSchedulerInterface;
use Rephp\Socket\StreamSocketInterface;

interface SchedulerLoopInterface extends SocketSchedulerInterface, LoopInterface
{
    /**
     * {@inheritdoc}
     */
    function add(\Generator $coroutine, $name = '');

    /**
     * {@inheritdoc}
     */
    function addReadStream($stream, callable $listener);

    /**
     * {@inheritdoc}
     */
    function addWriteStream($stream, callable $listener);

    /**
     * @param StreamSocketInterface       $socket
     * @param \Rephp\Scheduler\Task $task
     */
    public function addReader(StreamSocketInterface $socket, Task $task);

    /**
     * @param StreamSocketInterface       $socket
     * @param \Rephp\Scheduler\Task $task
     */
    public function addWriter(StreamSocketInterface $socket, Task $task);

    /**
     * {@inheritdoc}
     */
    function removeReadStream($stream);

    /**
     * {@inheritdoc}
     */
    function removeWriteStream($stream);

    /**
     * {@inheritdoc}
     */
    function removeStream($stream);
}