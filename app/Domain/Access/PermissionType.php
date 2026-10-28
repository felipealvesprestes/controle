<?php

namespace App\Domain\Access;

/**
 * What a permission unlocks on the interface (F-17).
 */
enum PermissionType: string
{
    case Menu = 'menu';
    case Page = 'page';
    case Action = 'action';
    case Field = 'field';
    case Content = 'content';
    case Scope = 'scope';
}
