<?php

namespace App\Domain\Client;

use App\Domain\Access\Permission;
use App\Domain\Access\PermissionProvider;
use App\Domain\Access\PermissionType;

/**
 * Permissions of the clients screens (T-08, F-03).
 */
final class ClientPermissions implements PermissionProvider
{
    public function module(): string
    {
        return 'Clients';
    }

    public function permissions(): array
    {
        return [
            new Permission('client.menu', PermissionType::Menu, 'Clients item in the navigation', 'T-08'),
            new Permission('client.view', PermissionType::Page, 'Open the clients list and details', 'T-08', ['client.menu']),
            new Permission('client.fiscal.view', PermissionType::Content, 'CPF or CNPJ, email and address of the clients', 'T-08', ['client.view']),
            new Permission('client.rate.view', PermissionType::Content, 'Hourly rates of the clients', 'T-08', ['client.view']),
            new Permission('client.create', PermissionType::Action, 'Create a client', 'T-08', ['client.view']),
            new Permission('client.edit', PermissionType::Action, 'Change client details and fiscal data', 'T-08', ['client.fiscal.view']),
            new Permission('client.rate.edit', PermissionType::Action, 'Schedule and remove rate changes', 'T-08', ['client.rate.view']),
            new Permission('client.status.edit', PermissionType::Action, 'Deactivate and reactivate a client', 'T-08', ['client.view']),
            new Permission('client.delete', PermissionType::Action, 'Delete a client with no time entries', 'T-08', ['client.view']),
        ];
    }
}
