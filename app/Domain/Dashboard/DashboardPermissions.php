<?php

namespace App\Domain\Dashboard;

use App\Domain\Access\Permission;
use App\Domain\Access\PermissionProvider;
use App\Domain\Access\PermissionType;

/**
 * Permissions of the dashboard (T-07).
 */
final class DashboardPermissions implements PermissionProvider
{
    public function module(): string
    {
        return 'Dashboard';
    }

    public function permissions(): array
    {
        return [
            new Permission('dashboard.menu', PermissionType::Menu, 'Dashboard item in the navigation', 'T-07'),
            new Permission('dashboard.page', PermissionType::Page, 'Open the dashboard', 'T-07', ['dashboard.menu']),
            new Permission('dashboard.setup.view', PermissionType::Content, 'Initial setup progress', 'T-07', ['dashboard.page']),
            new Permission('dashboard.billing_rule.view', PermissionType::Content, 'Billing rule and amounts', 'T-07', ['dashboard.page']),
        ];
    }
}
