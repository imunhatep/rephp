<?php
namespace Rephp\Socket;


use Evenement\EventEmitterTrait;
use React\Stream\Buffer;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;
use Rephp\LoopEvent\SchedulerLoopInterface;
use Rephp\Scheduler\SystemCall;
use Rephp\Scheduler\Task;
use Rephp\Scheduler\Value;

/**
 * Socket Implementation based on react connection class
 *
 * @author Artem <Aly> Suharev
 */
class Socket implements StreamSocketInterface
{
    use EventEmitterTrait;

    public $bufferSize;
    protected $socket;
    protected $readable;
    protected $writable;
    protected $closing;


    protected $loop;
    protected $buffer;

    function __construct($socket, SchedulerLoopInterface $loop)
    {
        if(!is_resource($socket)){
            throw new \InvalidArgumentException('Connection constructor expect a valid PHP resource as first arg');
        }

        $this->socket = $socket;
        $this->loop = $loop;

        $this->bufferSize = 1500; //typical MTU size
        $this->readable = true;
        $this->writable = true;
        $this->closing = false;

        $this->buffer = new Buffer($this->socket, $this->loop);
        $this->buffer->on(
            'error',
            function ($error) {
                $this->emit('error', array($error, $this));
                $this->close();
            }
        );

        $this->buffer->on(
            'drain',
            function () {
                $this->emit('drain', array($this));
            }
        );
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int) $this->socket;
    }

    /**
     * @return mixed
     */
    public function getRaw()
    {
        return $this->socket;
    }

    /**
     * @param bool $block
     */
    function block($block = false)
    {
        stream_set_blocking($this->socket, $block);
    }

    function isReadable()
    {
        return $this->readable;
    }

    function isWritable()
    {
        return $this->writable;
    }

    /**
     * @return static
     */
    function accept()
    {
        yield new SystemCall(function (Task $task, SchedulerLoopInterface $scheduler) {
            $scheduler->addReader($this, $task);
        }, 's:accept');

        while($this->readable){
            yield new Value(new static(stream_socket_accept($this->socket, 0), $this->loop));
        }
    }

    function pause()
    {
        yield new SystemCall(function (Task $task, SchedulerLoopInterface $scheduler) {
            $scheduler->removeReadStream($this->socket);
        }, 's:pause');
    }

    function resume()
    {
        $task = new Task(0, $this->handleData(), 'Resume::handleData');
        $this->loop->addReader($this, $task);
    }

    function getBuffer()
    {
        return $this->buffer;
    }

    function handleData()
    {
        while ($this->readable) {
            yield new SystemCall(function (Task $task, SchedulerLoopInterface $scheduler) {
                $data = stream_socket_recvfrom($this->socket, $this->bufferSize);

                if (feof($this->socket)) {
                    $this->end();
                }
                else {
                    $this->emit('data', array($data, $this));
                }
            }, 's:handleData');

        }
    }

    function setTimeout($seconds)
    {
        stream_set_timeout($this->socket, $seconds);
    }


    function handleClose()
    {
        if (is_resource($this->socket)) {
            stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
            fclose($this->socket);
        }
    }

    /**
     * @param $data
     *
     * @return int
     */
    function write($data)
    {
        if ($this->writable) {
            $this->buffer->write($data);
        }
    }

    function close()
    {
        if (!$this->writable && !$this->closing) {
            return;
        }

        $this->closing = false;

        $this->readable = false;
        $this->writable = false;

        $this->emit('end', array($this));
        $this->emit('close', array($this));
        $this->loop->removeStream($this->socket);
        $this->buffer->removeAllListeners();
        $this->removeAllListeners();

        $this->handleClose();
    }

    function end($data = null)
    {
        $this->closing = true;

        $this->readable = false;
        $this->writable = false;

        $this->buffer->on( 'close', function () { $this->close(); } );
        $this->buffer->end($data);
    }

    function pipe(WritableStreamInterface $dest, array $options = [])
    {
        Util::pipe($this, $dest, $options);
        return $dest;
    }

    function getRemoteAddress()
    {
        return $this->parseAddress($this->getRemoteName());
    }

    /**
     * @return string
     */
    function getRemoteName()
    {
        return stream_socket_get_name($this->socket, true);
    }

    /**
     * @return string
     */
    function getLocalName()
    {
        return stream_socket_get_name($this->socket, false);
    }

    protected function parseAddress($address)
    {
        return trim(substr($address, 0, strrpos($address, ':')), '[]');
    }

}