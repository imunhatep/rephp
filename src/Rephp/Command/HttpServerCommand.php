<?php
namespace Rephp\Command;

use Rephp\Controller\HttpController;
use Rephp\LoopEvent\SchedulerLoop;
use Rephp\Server\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HttpServerCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('rephp:httpserver')
            ->setDescription('PHP Http server based on React and Coroutine loop')
            ->addArgument(
                'host',
                InputArgument::OPTIONAL,
                'Http server hostname'
            )
            ->addArgument(
                'port',
                InputArgument::OPTIONAL,
                'Http server port'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $host = $input->getArgument('host');
        $port = $input->getArgument('port');
        if (!$port) {
            $port = '8080';
        }

        if (!$host) {
            $host = '127.0.0.1';
        }

        $eventLoop = new SchedulerLoop();

        $socket = new Server($eventLoop);

        $http = new \React\Http\Server($socket);
        $http->on('request', array(new HttpController(), 'onRequest'));

        $socket->listen($port, $host);
        $eventLoop->run();
    }
}