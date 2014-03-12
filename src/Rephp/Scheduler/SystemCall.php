<?php

namespace Rephp\Scheduler;

/**
 * @author Kazuyuki Hayashi <hayashi@valnur.net>
 */
class SystemCall
{

    /**
     * @var callable
     */
    protected $callback;

    /**
     * @param \Generator $coroutine
     *
     * @return static
     */
    public static function create(\Generator $coroutine, $name = 'Syscall::create')
    {
        return new static(function (Task $task, Scheduler $scheduler) use ($coroutine, $name) {
            $task->setValue($scheduler->add($coroutine, $name));
            $scheduler->schedule($task);
        });
    }

    /**
     * @param $taskId
     *
     * @return static
     */
    public static function kill($taskId)
    {
        return new static(function (Task $task, Scheduler $scheduler) use ($taskId) {
            $task->setValue($scheduler->kill($taskId));
            $scheduler->schedule($task);
        });
    }

    /**
     * @return static
     */
    public static function shutdown()
    {
        return new static(function (Task $task, Scheduler $scheduler) {
            $scheduler->shutdown();
        });
    }

    /**
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @param Task      $task
     * @param Scheduler $scheduler
     *
     * @return mixed
     */
    public function __invoke(Task $task, Scheduler $scheduler)
    {
        $callback = $this->callback;

        return $callback($task, $scheduler);
    }

}