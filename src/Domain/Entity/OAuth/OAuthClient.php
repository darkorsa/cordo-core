<?php

namespace Cordo\Core\Domain\Entity\OAuth;

class OAuthClient
{
    private string $client_id;
    private ?string $client_secret = null;
    private string $redirect_uri;
    private ?string $grant_types = null;
    private ?string $scope = null;
    private ?string $user_id = null;
}
