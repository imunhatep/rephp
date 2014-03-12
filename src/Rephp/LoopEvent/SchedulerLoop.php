<?php
namespace Rephp\LoopEvent;


use React\EventLoop\Tick\FutureTickQueue;
use React\EventLoop\Tick\NextTickQueue;
use React\EventLoop\Timer\Timer;
use React\EventLoop\Timer\TimerInterface;
use React\EventLoop\Timer\Timers;
use Rephp\Scheduler\Scheduler;
use Rephp\Scheduler\SystemCall;
use Rephp\Scheduler\Task;
use Rephp\Socket\Socket;
use Rephp\Socket\StreamSocket;
use Rephp\Socket\StreamSocketInterface;

class SchedulerLoop extends Scheduler implements SchedulerLoopInterface
{
    const STREAM_SELECT_TIMEOUT = 100000;

    private $nextTickQueue;
    private $futureTickQueue;
    private $running;

    /**
     * @var array
     */
    protected $readeTasks;
    protected $readResources;

    /**
     * @var array
     */
    protected $writeTasks;
    protected $writeResources;

    protected $debugEnabled = false;

    function __construct()
    {
        parent::__construct();

        $this->nextTickQueue = new NextTickQueue($this);
        $this->futureTickQueue = new FutureTickQueue($this);
        $this->timers = new Timers();

        $this->readTasks = [];
        $this->readResources = [];

        $this->writeTasks = [];
        $this->writeResources = [];
    }

    /**
     * {@inheritdoc}
     */
    function addReadStream($stream, callable $listener)
    {
        $task = new Task(++$this->sequence, $this->callableToGenerator($stream, $listener), 'readstream');
        $socket = new Socket($stream, $this);
        $socket->block(false);

        $this->addReader($socket, $task);
    }

    /**
     * {@inheritdoc}
     */
    function addWriteStream($stream, callable $listener)
    {
        $task = new Task(++$this->sequence, $this->callableToGenerator($stream, $listener));
        $socket = new Socket($stream, $this);
        $socket->block(false);

        $this->addWriter($socket, $task);
    }

    /**
     * @param StreamSocketInterface         $socket
     * @param \Rephp\Scheduler\Task   $task
     */
    public function addReader(StreamSocketInterface $socket, Task $task)
    {
        $this->debug('====== READ: ' . (int)$socket->getRaw() . ' Task(' . $task->getName() . ')');

        $resourceId = $socket->getId();
        if (!isset($this->readResources[$resourceId])) {
            $this->readTasks[$resourceId] = new \SplStack();
            $this->readResources[$resourceId] = $socket->getRaw();
        }

        $this->readTasks[$resourceId]->push($task);
    }

    /**
     * @param StreamSocketInterface         $socket
     * @param \Rephp\Scheduler\Task   $task
     */
    public function addWriter(StreamSocketInterface $socket, Task $task)
    {
        $this->debug('====== WRITE: ' . (int)$socket->getRaw() . ' Task(' . $task->getName() . ')');

        $resourceId = $socket->getId();
        if (!isset($this->writeResources[$resourceId])) {
            $this->writeTasks[$resourceId] = new \SplStack($task);
            $this->writeResources[$resourceId] = $socket->getRaw();
        };

        $this->writeTasks[$resourceId]->push($task);
    }

    /**
     * {@inheritdoc}
     */
    function removeReadStream($stream)
    {
        $resourceId = (int)$stream;

        if (isset($this->readResources[$resourceId])) {
            unset($this->readTasks[$resourceId], $this->readResources[$resourceId]);
        }
    }

    /**
     * {@inheritdoc}
     */
    function removeWriteStream($stream)
    {
        $resourceId = (int)$stream;

        if (isset($this->writeResources[$resourceId])) {
            unset($this->writeTasks[$resourceId], $this->writeResources[$resourceId]);
        }
    }

    /**
     * {@inheritdoc}
     */
    function removeStream($stream)
    {
        $this->removeReadStream($stream);
        $this->removeWriteStream($stream);
    }

    /**
     * {@inheritdoc}
     */
    public function addTimer($interval, callable $callback)
    {
        $timer = new Timer($this, $interval, $callback, false);

        $this->timers->add($timer);

        return $timer;
    }

    /**
     * {@inheritdoc}
     */
    public function addPeriodicTimer($interval, callable $callback)
    {
        $timer = new Timer($this, $interval, $callback, true);

        $this->timers->add($timer);

        return $timer;
    }

    /**
     * {@inheritdoc}
     */
    public function cancelTimer(TimerInterface $timer)
    {
        $this->timers->cancel($timer);
    }

    /**
     * {@inheritdoc}
     */
    public function isTimerActive(TimerInterface $timer)
    {
        return $this->timers->contains($timer);
    }

    /**
     * {@inheritdoc}
     */
    public function nextTick(callable $listener)
    {
        $this->nextTickQueue->add($listener);
    }

    /**
     * {@inheritdoc}
     */
    public function futureTick(callable $listener)
    {
        $this->futureTickQueue->add($listener);
    }

    /**
     * {@inheritdoc}
     */
    function tick()
    {
        $this->nextTickQueue->tick();
        $this->futureTickQueue->tick();
        $this->timers->tick();

        $this->doPoll(0);
    }

    /**
     * {@inheritdoc}
     */
    function run()
    {
        if(!$this->running){
            $this->running = true;
            $this->add($this->poll(), 'Poll');
        }

        parent::run();
    }

    /**
     * {@inheritdoc}
     */
    function stop()
    {
        $this->running = false;
    }

    /**
     * @return \Generator
     */
    protected function poll()
    {
        $timeout = $poll = 0;
        while ($this->running) {
            $this->debug("\n\n".'--== POLL (N:'.$poll++.' Q: '.count($this->queue).')==--');

            yield $this->nextTickQueue->tick();
            yield $this->futureTickQueue->tick();
            yield $this->timers->tick();

            // Next-tick or future-tick queues have pending callbacks ...
            if (!$this->running || !$this->nextTickQueue->isEmpty() || !$this->futureTickQueue->isEmpty()) {
                $timeout = 0;
            }
            // There is a pending timer, only block until it is due ...
            else if ($scheduledAt = $this->timers->getFirst()) {
                if (0 > $timeout = $scheduledAt - $this->timers->getTime()) {
                    $timeout = 0;
                }
            }
            // The only possible event is stream activity, so wait forever ...
            else if ($this->readResources or $this->writeResources) {

                if ($this->queue->isEmpty()) {
                    //sleep(2);
                    $this->debug(MYNAME.' - NOTHING TO DO');

                    $timeout = self::STREAM_SELECT_TIMEOUT;
                }
                else {
                    $timeout = 0;
                }
            }

            yield $this->doPoll($timeout);
        }
    }

    /**
     * @param $timeout
     */
    protected function doPoll($timeout)
    {
        /* @var StreamSocket[] $reader */
        /* @var StreamSocket[] $writer */

        if (empty($this->writeResources) and empty($this->readResources)) {
            return;
        }

        $this->debug('Streams Read : ' . count($this->readResources) . '   Write: ' . count($this->writeResources));

        //$r = $this->readResources; $w = $this->writeResources;
        //if(!stream_select($r, $w, $e = [], $timeout)){ return ; }

        list($r, $w) = $this->selectStreams($timeout);

        foreach ($r as $socket) {
            /** @var \SplStack $taskStack */
            $taskStack = $this->readTasks[(int) $socket];

            $taskStack->rewind();
            while($taskStack->valid()){
                $this->schedule($taskStack->current());
                $taskStack->next();
            }
        }

        foreach ($w as $socket) {
            /** @var \SplStack $taskStack */
            $taskStack = $this->writeTasks[(int) $socket];

            $taskStack->rewind();
            while($taskStack->valid()){
                $this->schedule($taskStack->current());
                $taskStack->next();
            }
        }

        $this->debug('--== END ==--'."\n");
    }

    function selectStreams($timeout)
    {
        $spr = 1000; // streams per round
        $streamCount = count($this->readResources);

        $offset = 0;
        $w = $this->writeResources;

        // timeout / rounds
        $timeoutPerRound = (int) $timeout / ceil($streamCount / $spr);
        do{
            $r = array_slice($this->readResources, $offset, $spr);
            stream_select($r, $w, $e = [], 0, $timeoutPerRound);
            //$this->debug("Offset: $offset  found: ".count($r));

            $offset+= $spr;
        }
        while(empty($r) and empty($w) and ($offset < $streamCount));

        return [$r,$w];
    }

    /**
     * @param $stream
     * @param callable $callable
     * @return \Generator
     */
    protected function callableToGenerator($stream, callable $callable)
    {
        $closure = $callable;
        if(is_array($callable)){
            $loop = $this;
            $closure = function() use ($callable, $stream, $loop) {
                call_user_func($callable, $stream, $loop);
            };
        }

        yield new SystemCall($closure);
    }

    protected function debug($msg)
    {
        $this->debugEnabled and error_log($msg);
    }
}