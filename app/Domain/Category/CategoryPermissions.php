<?php

namespace App\Domain\Category;

use App\Domain\Access\Permission;
use App\Domain\Access\PermissionProvider;
use App\Domain\Access\PermissionType;

/**
 * Permissions of the categories screen (T-09, F-04).
 */
final class CategoryPermissions implements PermissionProvider
{
    public function module(): string
    {
        return 'Categories';
    }

    public function permissions(): array
    {
        return [
            new Permission('category.menu', PermissionType::Menu, 'Categories item in the navigation', 'T-09'),
            new Permission('category.view', PermissionType::Page, 'Open the categories', 'T-09', ['category.menu']),
            new Permission('category.create', PermissionType::Action, 'Create a category', 'T-09', ['category.view']),
            new Permission('category.edit', PermissionType::Action, 'Change, reorder and mark categories as not billable', 'T-09', ['category.view']),
            new Permission('category.status.edit', PermissionType::Action, 'Deactivate and reactivate a category', 'T-09', ['category.view']),
            new Permission('category.delete', PermissionType::Action, 'Delete a category with no time entries', 'T-09', ['category.view']),
        ];
    }
}
