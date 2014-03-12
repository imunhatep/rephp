<?php

namespace Rephp\Scheduler;

/**
 * @author Kazuyuki Hayashi <hayashi@valnur.net>
 */
class Task
{

    /**
     * @var int
     */
    private $id;

    /**
     * @var \Generator
     */
    private $coroutine;

    /**
     * @var bool
     */
    private $firstRun = true;

    /**
     * @var mixed
     */
    private $value;

    public $name;

    /**
     * @param            $id
     * @param \Generator $coroutine
     */
    public function __construct($id, \Generator $coroutine, $name = '')
    {
        //echo 'NEW task('.$id.'): '.$name."\n";

        $this->name = $name;
        $this->id = $id;
        $this->coroutine = $coroutine;
    }

    /**
     * @param mixed $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function run()
    {
        if ($this->firstRun) {
            $this->firstRun = false;

            return $this->coroutine->current();
        }

        $return = $this->coroutine->send($this->value);
        $this->value = null;

        return $return;
    }

    /**
     * @return bool
     */
    public function isFinished()
    {
        return !$this->coroutine->valid();
    }

}