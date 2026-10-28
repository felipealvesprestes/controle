<?php

namespace App\Domain\Access;

/**
 * Why a permission ended up allowed or denied, shown to the administrator (F-17).
 */
enum Reason: string
{
    case Administrator = 'administrator';
    case Inactive = 'inactive';
    case DeniedForUser = 'denied_for_user';
    case AllowedForUser = 'allowed_for_user';
    case Role = 'role';
    case NotGranted = 'not_granted';
    case MissingDependency = 'missing_dependency';
}
