# RePHP

Requirements
------------

* PHP5.5+

## Install

The recommended way to install react is [through composer](http://getcomposer.org).

```JSON
{
    "repositories": [
        { "type": "vcs", "url": "http://github.com/Imunhatep/rephp" }
    ],
    "require": {
        "imunhatep/rephp": "@dev"
    }
}
```

## What is it?

Generally this is project is a try to REview how we use PHP. It's based on PHP Coroutine server, mostly written by nikic,  [Cooperative-multitasking-using-coroutines-in-PHP](http://nikic.github.io/2012/12/22/Cooperative-multitasking-using-coroutines-in-PHP.html).
The goal is to make HTTP server like nginx to connect directly to PHP Http server based on [ReactPHP](http://reactphp.org/). This approach is described by Marc, [php-high-performance](http://marcjschmidt.de/blog/2014/02/08/php-high-performance.html).

But, react works by eventloop principle either that libevent or simply socket_select loop. What's uniq in this project is that I try to implement coroutine event loop from nikic coroutine implementation to React core. And make this work as Http server in the way Marc described it.

This will allow to use ReactPHP packages like [Ratchet](http://socketo.me/) websockets server.
For Web application framework [Symfony2](http://symfony.com) will be used.

## How to start?

### Http server:
```
./bin/rephp rephp:httpserver localhost 8080
```


### WebSocket server:
```
./bin/rephp rephp:wsserver localhost 8089
```

For web sockets you will need manually install [Ratchet](http://socketo.me/). I will implement Ratchet a little bit later.
