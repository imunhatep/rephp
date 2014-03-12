<?php

namespace Rephp\Scheduler;

/**
 * @author Kazuyuki Hayashi <hayashi@valnur.net>
 */
class Value
{

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @param $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->value;
    }

}