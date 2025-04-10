<?php

namespace Cordo\Core\Domain\Entity\OAuth;

class OAuthRefreshToken
{
    private string $refresh_token;
    private string $client_id;
    private ?string $user_id = null;
    private \DateTime $expires;
    private ?string $scope = null;
}
