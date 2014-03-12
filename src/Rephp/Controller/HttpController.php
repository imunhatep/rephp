<?php
/**
 * Created by PhpStorm.
 * User: aly
 * Date: 3/12/14
 * Time: 12:48 PM
 */

namespace Rephp\Controller;


use React\Http\Request;
use React\Http\Response;

class HttpController
{

    public function onRequest(Request $request, Response $response)
    {
        $response->writeHead('200');
        $response->end('Hello world from ('.$request->getPath().').');
    }

}