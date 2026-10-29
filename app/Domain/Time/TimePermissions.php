<?php

namespace App\Domain\Time;

use App\Domain\Access\Permission;
use App\Domain\Access\PermissionProvider;
use App\Domain\Access\PermissionType;

/**
 * Permissions of the today and time entries screens (T-02, T-03, T-04, F-05, F-06).
 */
final class TimePermissions implements PermissionProvider
{
    public function module(): string
    {
        return 'Time entries';
    }

    public function permissions(): array
    {
        return [
            new Permission('time.entry.menu', PermissionType::Menu, 'Today and Time entries items in the navigation', 'T-02'),
            new Permission('time.entry.view', PermissionType::Page, 'Open today and the time entries', 'T-03', ['time.entry.menu']),
            new Permission('time.entry.amount.view', PermissionType::Content, 'Amounts of the time entries', 'T-02', ['time.entry.view']),
            new Permission('time.entry.create', PermissionType::Action, 'Log time and use the timer', 'T-04', ['time.entry.view']),
            new Permission('time.entry.edit', PermissionType::Action, 'Change a time entry of an open period', 'T-04', ['time.entry.view']),
            new Permission('time.entry.delete', PermissionType::Action, 'Delete a time entry of an open period', 'T-03', ['time.entry.view']),
            new Permission('time.entry.scope.all', PermissionType::Scope, 'See and change the time entries of every user', 'T-03', ['time.entry.view']),
        ];
    }
}
