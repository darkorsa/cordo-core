<?php

declare(strict_types=1);

namespace Cordo\Core\Application\Bootstrap\Init;

use Laminas\Permissions\Acl\Acl;
use Cordo\Core\SharedKernel\Enum\SystemRole;
use Laminas\Permissions\Acl\Role\GenericRole as Role;

class AclInit
{
    public static function init(): array
    {
        return self::getDefinitions();
    }

    private static function getDefinitions(): array
    {
        return [
            'acl' => \DI\factory(static function () {
                $acl = new Acl();
                // add system roles
                $acl
                    ->addRole(new Role(SystemRole::GUEST()))
                    ->addRole(new Role(SystemRole::LOGGED()));

                return $acl;
            }),
        ];
    }
}
