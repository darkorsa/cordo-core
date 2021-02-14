<?php

namespace Cordo\Core\UI\Http\Middleware;

use DI\Container;
use OAuth2\Scope;
use OAuth2\Server;
use OAuth2\Storage\Pdo;
use OAuth2\Storage\Memory;
use OAuth2\GrantType\RefreshToken;
use OAuth2\GrantType\UserCredentials;
use OAuth2\GrantType\ClientCredentials;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class OAuth2ServerMiddleware implements MiddlewareInterface
{
    private Container $container;
    
    private array $servers;

    private array $db;
    
    public function __construct(Container $container, array $servers, array $db)
    {
        $this->container = $container;
        $this->servers = $servers;
        $this->db = $db;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        foreach ($this->servers as $namespace => $serverConfig) {
            $this->registerServer($namespace, $serverConfig);
        }

        return $handler->handle($request);
    }

    private function registerServer(string $namespace, array $serverConfig)
    {
        $storage = new Pdo([
            'dsn' => 'mysql:dbname=' . $this->db['database'] . ';host=' . $this->db['host'],
            'username' => $this->db['user'],
            'password' => $this->db['password'],
        ]);

        $credentials = $this->container->get($serverConfig['credentials']);

        $server = new Server($storage, [
            'access_lifetime' => $serverConfig['token_expire'],
        ]);
        $server->addGrantType(new ClientCredentials($storage));
        $server->addGrantType(new UserCredentials($credentials));
        $server->addGrantType(new RefreshToken($storage, [
            'always_issue_new_refresh_token' => $serverConfig['always_issue_new_refresh_token'],
        ]));
        $server->setScopeUtil(new Scope(new Memory([
            'default_scope' => $serverConfig['default_scope'],
            'supported_scopes' => $serverConfig['scopes'],
        ])));

        $this->container->set("{$namespace}_oauth_server", $server);
    }
}
