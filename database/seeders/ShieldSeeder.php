<?php

namespace Database\Seeders;

use Filament\Facades\Filament;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * @var list<string>
     */
    private const EmployeePermissions = [
        'ViewAny:Customer',
        'View:Customer',
        'Create:Customer',
        'Update:Customer',
        'ViewAny:Invoice',
        'View:Invoice',
        'Create:Invoice',
        'Update:Invoice',
        'Delete:Invoice',
        'ViewAny:DiscountRequest',
        'View:DiscountRequest',
        'Create:DiscountRequest',
    ];

    /**
     * @var list<string>
     */
    private const ManagerExclusivePermissions = [
        'Delete:Customer',
        'Restore:Customer',
        'ForceDelete:Customer',
        'Restore:Invoice',
        'ForceDelete:Invoice',
        'Delete:DiscountRequest',
        'Restore:DiscountRequest',
        'ForceDelete:DiscountRequest',
        'Review:DiscountRequest',
        'ViewAny:User',
        'View:User',
        'Create:User',
        'Update:User',
        'Block:User',
        'Unblock:User',
        'ViewAny:Role',
        'View:Role',
        'Create:Role',
        'Update:Role',
        'Delete:Role',
        'ViewReports',
        'ViewManagerStatsOverviewWidget',
        'ViewInvoiceSalesChartWidget',
        'ViewPendingDiscountRequestsWidget',
        'ViewReportInvoicesTableWidget',
        'ViewEmployeePerformanceTableWidget',
        'ViewPaidDiscountsTableWidget',
        'ViewHorizon',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Artisan::call('shield:generate', [
            '--all' => true,
            '--option' => 'permissions',
            '--panel' => 'admin',
            '--ignore-existing-policies' => true,
        ]);

        foreach (collect([...self::EmployeePermissions, ...self::ManagerExclusivePermissions])->unique() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $employee = Role::findOrCreate('employee', 'web');
        $manager = Role::findOrCreate('manager', 'web');
        $admin = Role::findOrCreate('admin', 'web');

        $employee->syncPermissions(self::EmployeePermissions);
        $manager->syncPermissions([
            ...self::EmployeePermissions,
            ...self::ManagerExclusivePermissions,
        ]);
        $admin->syncPermissions([
            ...self::EmployeePermissions,
            'Delete:Customer',
            'Restore:Customer',
            'ForceDelete:Customer',
            'Restore:Invoice',
            'ForceDelete:Invoice',
            'Delete:DiscountRequest',
            'Restore:DiscountRequest',
            'ForceDelete:DiscountRequest',
            'Review:DiscountRequest',
            'ViewHorizon',
        ]);
    }
}
