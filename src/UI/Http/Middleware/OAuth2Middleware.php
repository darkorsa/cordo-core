<?php

namespace Cordo\Core\UI\Http\Middleware;

use OAuth2\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Container\ContainerInterface;
use OAuth2\Response as OAuth2Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class OAuth2Middleware implements MiddlewareInterface
{
    private $container;

    private $namespace;

    private $scope;

    public function __construct(ContainerInterface $container, string $namespace, $scope = null)
    {
        $this->container = $container;
        $this->namespace = $namespace;
        $this->scope = $scope;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $server = $this->container->get("{$this->namespace}_oauth_server");
        $response = new OAuth2Response();

        if (!$server->verifyResourceRequest(
            Request::createFromGlobals(),
            $response,
            $this->scope,
        )) {
            return new Response(401, [], json_encode($response->getParameters()));
        }

        $tokenData = (array) $server->getAccessTokenData(Request::createFromGlobals());

        if (!array_key_exists('user_id', $tokenData)) {
            return new Response(500, [], 'Invalid user id');
        }

        $request = $request->withAttribute('user_id', $tokenData['user_id']);

        return $handler->handle($request);
    }
}
