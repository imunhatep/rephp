<?php
namespace Rephp\Command;

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Rephp\Controller\WsController;
use Rephp\LoopEvent\SchedulerLoop;
use Rephp\Service\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WsServerCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('rephp:wsserver')
            ->setDescription('PHP Websocket server based on coroutineIO')
            ->addArgument(
                'host',
                InputArgument::OPTIONAL,
                'Websocket server hostname'
            )
            ->addArgument(
                'port',
                InputArgument::OPTIONAL,
                'Websocket server port'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $host = $input->getArgument('host');
        $port = $input->getArgument('port');
        if (!$port) {
            $port = '8089';
        }

        if (!$host) {
            $host = '127.0.0.1';
        }

        $eventLoop = new SchedulerLoop();

        $socket = new Server($eventLoop);
        $socket->listen($port, $host);

        $server = new IoServer(new HttpServer(new WsServer(new WsController())), $socket, $eventLoop);
        //$server = new IoServer(new ChatController(), $socket, $eventLoop);
        $output->writeln('<info>Websocket server starting on: '.$host.':'.$port.'</info>');
        $server->run();
    }
}