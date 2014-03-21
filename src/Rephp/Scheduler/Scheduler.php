<?php

namespace Rephp\Scheduler;

/**
 * @author Kazuyuki Hayashi <hayashi@valnur.net>
 */
class Scheduler
{

    /**
     * @var int
     */
    protected $sequence = 0;

    /**
     * @var array
     */
    protected $tasks = [];

    /**
     * @var \SplQueue|Task[]
     */
    protected $queue;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->queue = new \SplQueue();
    }

    /**
     * @param \Generator $coroutine
     *
     * @return int
     */
    public function add(\Generator $coroutine, $name = 'Scheduler::add()')
    {
        $id = ++$this->sequence;
        $task = new Task($id, $this->generateStack($coroutine), $name);
        $this->tasks[$id] = $task;
        $this->schedule($task, $name);

        return $id;
    }

    /**
     * @param Task $task
     */
    public function schedule(Task $task)
    {
        //echo 'Schedule Task(' . $task->getName() . ')'."\n";
        $this->queue->enqueue($task);
    }

    /**
     * Starts scheduler
     */
    public function run()
    {
        while (!$this->queue->isEmpty()) {
            $task = $this->queue->dequeue();

            if(!$task->isKilled()){
                $return = $task->run();

                if ($return instanceof SystemCall) {
                    $return($task, $this);
                    continue;
                }
            }

            if ($task->isFinished()) {
                unset($this->tasks[$task->getId()]);
            } else {
                $this->schedule($task, $task->getName());
            }
        }
    }

    /**
     * @param $id
     *
     * @return bool
     */
    public function kill($id)
    {
        if (!isset($this->tasks[$id])) {
            return false;
        }

        foreach ($this->queue as $i => $task) {
            if ($task->getId() === $id) {
                unset($this->queue[$i]);
                break;
            }
        }

        return true;
    }

    /**
     *
     */
    public function shutdown()
    {
        foreach ($this->queue as $key => $value) {
            unset($this->queue[$key]);
        }
    }

    /**
     * @param \Generator $coroutine
     *
     * @return \Generator
     */
    public function generateStack(\Generator $coroutine)
    {
        $stack = new \SplStack();

        while (true) {
            if (($value = $coroutine->current()) instanceof \Generator) {
                $stack->push($coroutine);
                $coroutine = $value;
                continue;
            }

            $isValue = $value instanceof Value;
            if (!$coroutine->valid() || $isValue) {
                if ($stack->isEmpty()) {
                    return;
                }

                $coroutine = $stack->pop();
                $coroutine->send($isValue ? $value->get() : null);
                continue;
            }

            $coroutine->send(yield $coroutine->key() => $value);
        }
    }

}