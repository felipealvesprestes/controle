<?php

namespace App\Domain\Provider;

use App\Domain\Access\Permission;
use App\Domain\Access\PermissionProvider;
use App\Domain\Access\PermissionType;

/**
 * Permissions of the provider settings (T-10, F-02).
 */
final class ProviderPermissions implements PermissionProvider
{
    public function module(): string
    {
        return 'Settings';
    }

    public function permissions(): array
    {
        return [
            new Permission('settings.billing.menu', PermissionType::Menu, 'Billing defaults item in the navigation', 'T-10'),
            new Permission('settings.billing.page', PermissionType::Page, 'Open the billing defaults', 'T-10', ['settings.billing.menu']),
            new Permission('settings.billing.rate.view', PermissionType::Content, 'Default hourly rate and example amount', 'T-10', ['settings.billing.page']),
            new Permission('settings.billing.edit', PermissionType::Action, 'Change the default rate and rounding', 'T-10', ['settings.billing.rate.view']),
            new Permission('settings.company.menu', PermissionType::Menu, 'Company details item in the navigation', 'T-10'),
            new Permission('settings.company.page', PermissionType::Page, 'Open the company details', 'T-10', ['settings.company.menu']),
            new Permission('settings.company.edit', PermissionType::Action, 'Change the company details', 'T-10', ['settings.company.page']),
        ];
    }
}
