<?php
/**
 * Created by PhpStorm.
 * User: aly
 * Date: 3/4/14
 * Time: 6:38 PM
 */

namespace Rephp\Socket;


use React\Socket\ConnectionInterface;

interface StreamSocketInterface extends ConnectionInterface
{
    public function getRemoteName();

    /**
     * @return string
     */
    public function getLocalName();

    /**
     * @param bool $block
     */
    public function block($block = false);

    /**
     * @return static
     */
    public function accept();

    public function getRaw();

    public function getId();
}