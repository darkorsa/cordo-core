<?php

namespace Cordo\Core\Domain\Entity\OAuth;

class OAuthScope
{
    private string $scope;
    private ?bool $is_default = null;
}
