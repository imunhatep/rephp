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
<<<<<<< HEAD
    const STREAM_SELECT_TIMEOUT = 250000;
=======
    const STREAM_SELECT_TIMEOUT = 100000;
>>>>>>> 50ed71d966622dd433c444f6d504107a7e1b927d

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

<<<<<<< HEAD
    protected $debugEnabled;

    function __construct($debug = 0)
=======
    protected $debugEnabled = false;

    function __construct()
>>>>>>> 50ed71d966622dd433c444f6d504107a7e1b927d
    {
        parent::__construct();

        $this->debugEnabled = $debug;

        $this->nextTickQueue = new NextTickQueue($this);
        $this->futureTickQueue = new FutureTickQueue($this);
        $this->timers = new Timers();

        $this->readTasks = [];
        $this->readResources = [];

        $this->writeTasks = [];
        $this->writeResources = [];

        $this->lastTimeout = self::STREAM_SELECT_TIMEOUT;

        $this->addPeriodicTimer(
            10,
            function () {
                $this->debug("\n" .
                    "Read count: " . count($this->readResources) . "\n" .
                    "Write count: " . count($this->writeResources) . "\n" .
                    "Queue count: " . count($this->queue) . "\n" .
                    "Tasks count: " . count($this->tasks) . "\n" .
                    "Last timeout: " . $this->lastTimeout . "\n" .
                    "====================",
                    3
                );


                foreach ($this->readResources as $res) {
                    if (!is_resource($res) or feof($res)) {
                        $this->removeStream($res);
                        fclose($res);
                    }
                }

                foreach ($this->writeResources as $res) {
                    if (!is_resource($res) or feof($res)) {
                        $this->removeStream($res);
                        fclose($res);
                    }
                }

            }
        );
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
     * @param StreamSocketInterface $socket
     * @param \Rephp\Scheduler\Task $task
     */
    function addReader(StreamSocketInterface $socket, Task $task)
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
     * @param StreamSocketInterface $socket
     * @param \Rephp\Scheduler\Task $task
     */
    function addWriter(StreamSocketInterface $socket, Task $task)
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

        $this->debug('====== REM RE: ' . (int)$resourceId);

        unset($this->readResources[$resourceId]);
        if (isset($this->readTasks[$resourceId])) {
            $this->killTaskStack($this->readTasks[$resourceId]);
            unset($this->readTasks[$resourceId]);
        }
    }

    /**
     * {@inheritdoc}
     */
    function removeWriteStream($stream)
    {
        $resourceId = (int)$stream;
        $this->debug('====== REM WR: ' . (int)$resourceId);

        unset($this->writeResources[$resourceId]);
        if (isset($this->writeTasks[$resourceId])) {
            $this->killTaskStack($this->writeTasks[$resourceId]);
            unset($this->writeTasks[$resourceId]);
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

    protected function killTaskStack(\SplStack $taskStack)
    {
        $taskStack->rewind();
        while ($taskStack->valid()) {
            $taskStack->current()->kill();
            $taskStack->next();
        }
    }

    /**
     * {@inheritdoc}
     */
    function addTimer($interval, callable $callback)
    {
        $timer = new Timer($this, $interval, $callback, false);

        $this->timers->add($timer);

        return $timer;
    }

    /**
     * {@inheritdoc}
     */
    function addPeriodicTimer($interval, callable $callback)
    {
        $timer = new Timer($this, $interval, $callback, true);

        $this->timers->add($timer);

        return $timer;
    }

    /**
     * {@inheritdoc}
     */
    function cancelTimer(TimerInterface $timer)
    {
        $this->timers->cancel($timer);
    }

    /**
     * {@inheritdoc}
     */
    function isTimerActive(TimerInterface $timer)
    {
        return $this->timers->contains($timer);
    }

    /**
     * {@inheritdoc}
     */
    function nextTick(callable $listener)
    {
        $this->nextTickQueue->add($listener);
    }

    /**
     * {@inheritdoc}
     */
    function futureTick(callable $listener)
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
<<<<<<< HEAD
            $this->debug("\n\n" . '--== POLL (N:' . $poll++ . ' Q: ' . count($this->queue) . ')==--');
=======
            $this->debug("\n\n".'--== POLL (N:'.$poll++.' Q: '.count($this->queue).')==--');
>>>>>>> 50ed71d966622dd433c444f6d504107a7e1b927d

            yield $this->nextTickQueue->tick();
            yield $this->futureTickQueue->tick();
            yield $this->timers->tick();

            // Queue or Next-tick or future-tick queues have pending callbacks ...
            if (!$this->queue->isEmpty() || !$this->nextTickQueue->isEmpty() || !$this->futureTickQueue->isEmpty()) {
                $timeout = 0;
            }
            else if ($this->readResources or $this->writeResources) {
                $timeout = self::STREAM_SELECT_TIMEOUT;
            }
            else if ($scheduledAt = $this->timers->getFirst()) {
                //$timeout = ($scheduledAt - $this->timers->getTime()) * 1000000;
                $timeout = 1000000;
            }
<<<<<<< HEAD
            else {
                //($this->debugEnabled === 1) and sleep(2);
                $this->debug('NOTHING TO DO');
                $timeout = 1000000;
            }


            //$this->debug($timeout, 3);

            yield $this->doPoll($timeout);

//
//            $this->debug("Read count: ".count($this->readResources) . "\n" .
//                "Write count: ".count($this->writeResources) . "\n" .
//                "Queue count: ".count($this->queue) . "\n" .
//                "Tasks count: ".count($this->tasks) . "\n" .
//                "Last timeout: ".$this->lastTimeout . "\n" .
//                "====================", 3);
//
//
//            foreach($this->readResources as $rid => $res){
//                if(!is_resource($res)){
//                    $this->debug("Dead resource: $rid", 3);
//                }
//            }
=======
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
>>>>>>> 50ed71d966622dd433c444f6d504107a7e1b927d
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

<<<<<<< HEAD
        $this->debug('Streams Read : ' . count($this->readResources) . '   Write: ' . count($this->writeResources), 2);
=======
        $this->debug('Streams Read : ' . count($this->readResources) . '   Write: ' . count($this->writeResources));
>>>>>>> 50ed71d966622dd433c444f6d504107a7e1b927d

        //$r = $this->readResources; $w = $this->writeResources;
        //if(!stream_select($r, $w, $e = [], $timeout)){ return ; }

        list($r, $w) = $this->selectStreams($timeout);

        foreach ($r as $socket) {
            /** @var \SplStack $taskStack */
            $taskStack = $this->readTasks[(int)$socket];

            $taskStack->rewind();
            while ($taskStack->valid()) {
                //$this->debug('Schedule Read Task(' . $taskStack->current()->getName() . ')');
                $this->schedule($taskStack->current());
                $taskStack->next();
            }
        }

        foreach ($w as $socket) {
            /** @var \SplStack $taskStack */
            $taskStack = $this->writeTasks[(int)$socket];

            $taskStack->rewind();
            while ($taskStack->valid()) {
                //$this->debug('Schedule Write Task(' . $taskStack->current()->getName() . ')');
                $this->schedule($taskStack->current());
                $taskStack->next();
            }
        }

<<<<<<< HEAD
        $this->debug('--== END ==--' . "\n");
=======
        $this->debug('--== END ==--'."\n");
>>>>>>> 50ed71d966622dd433c444f6d504107a7e1b927d
    }

    function selectStreams($timeout)
    {
        $spr = 1000; // streams per round
        $streamCount = count($this->readResources);

        $offset = 0;
        $w = $this->writeResources;

        // timeout / rounds
<<<<<<< HEAD
        $this->lastTimeout = (int)($timeout / ceil($streamCount / $spr));
        do {
            $r = array_slice($this->readResources, $offset, $spr);
            stream_select($r, $w, $e = [], 0, $this->lastTimeout);
            $this->debug("Offset: $offset Timeout: $this->lastTimeout Found: " . count($r), 2);
=======
        $timeoutPerRound = (int) $timeout / ceil($streamCount / $spr);
        do{
            $r = array_slice($this->readResources, $offset, $spr);
            stream_select($r, $w, $e = [], 0, $timeoutPerRound);
            //$this->debug("Offset: $offset  found: ".count($r));
>>>>>>> 50ed71d966622dd433c444f6d504107a7e1b927d

            $offset += $spr;
        } while (empty($r) and empty($w) and ($offset < $streamCount));

        return [$r, $w];
    }

    /**
     * @param $stream
     * @param callable $callable
     * @return \Generator
     */
    protected function callableToGenerator($stream, callable $callable)
    {
        $closure = $callable;
<<<<<<< HEAD
        if (is_array($callable)) {
            $loop = $this;
            $closure = function () use ($callable, $stream, $loop) {
=======
        if(is_array($callable)){
            $loop = $this;
            $closure = function() use ($callable, $stream, $loop) {
>>>>>>> 50ed71d966622dd433c444f6d504107a7e1b927d
                call_user_func($callable, $stream, $loop);
            };
        }

        yield new SystemCall($closure);
    }

<<<<<<< HEAD
    protected function debug($msg, $type = 1)
    {
        if ($this->debugEnabled and ($type >= $this->debugEnabled)) {
            error_log($msg);
        }
=======
    protected function debug($msg)
    {
        $this->debugEnabled and error_log($msg);
>>>>>>> 50ed71d966622dd433c444f6d504107a7e1b927d
    }
}