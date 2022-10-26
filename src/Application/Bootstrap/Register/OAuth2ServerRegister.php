<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Bootstrap\Register;

use OAuth2\Scope;
use OAuth2\Server;
use OAuth2\Storage\Pdo;
use OAuth2\Storage\Memory;
use Cordo\Core\Application\App;
use OAuth2\GrantType\RefreshToken;
use OAuth2\GrantType\UserCredentials;
use OAuth2\GrantType\ClientCredentials;

class OAuth2ServerRegister
{
    public function __construct(private App $app)
    {
    }

    public function register(): void
    {
        foreach ($this->app->config->get('auth.servers') as $namespace => $serverConfig) {
            $this->registerServer($namespace, $serverConfig);
        }
    }

    private function registerServer(string $namespace, array $serverConfig): void
    {
        $dbDriver = $this->app->config->get('db.driver');
        $db = $this->app->db_config;

        $storage = new Pdo([
            'dsn' => $dbDriver . ':dbname=' . $db['dbname'] . ';host=' . $db['host'],
            'username' => $db['user'],
            'password' => $db['password'],
        ]);

        $credentials = $this->app->container->get($serverConfig['credentials']);

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

        $this->app->container->set("{$namespace}_oauth_server", $server);
    }
}
