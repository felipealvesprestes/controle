<?php

namespace App\Domain\Access;

/**
 * A per-user exception on top of the roles (RN-16).
 */
enum Effect: string
{
    case Allow = 'allow';
    case Deny = 'deny';
}
