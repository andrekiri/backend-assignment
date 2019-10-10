<?php

namespace tests;

use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\RequestBody;
use Slim\Http\Uri;

trait MockRequest
{
    /**
     * Mock an HTTP request.
     *
     * @param array $params
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function sendHTTP($method, $uri, $bodyParams, $content_type)
    {
        $environment = Environment::mock([
            'REQUEST_METHOD' => $method,
            'REQUEST_URI'    => $uri,
            'CONTENT_TYPE'   => $content_type
        ]);

        $uri = Uri::createFromEnvironment($environment);
        $headers = Headers::createFromEnvironment($environment);
        $request = new Request($method, $uri, $headers, [], $environment->all(), new RequestBody());
        if (!empty($bodyParams)) {
            $request = $request->withParsedBody($bodyParams);
        }
        $this->container['request'] = $request;
        return $this->app->run(true);
    }

 }