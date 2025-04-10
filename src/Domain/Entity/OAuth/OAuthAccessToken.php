<?php

namespace Cordo\Core\Domain\Entity\OAuth;

class OAuthAccessToken
{
    private string $access_token;
    private string $client_id;
    private ?string $user_id = null;
    private \DateTime $expires;
    private ?string $scope = null;
}
