<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        // $permissions = [
        //     'can_see_dashboard_page',
        //     'can_see_users_page',
        //     'can_add_users',
        //     'can_edit_users',
        //     'can_change_users_status',
        //     'can_see_users',
        //     'can_see_requests_page',
        //     'can_see_requests',
        //     'can_edit_requests',
        //     'can_assign_requests',
        //     'can_reassign_requests',
        //     'can_see_clients',
        //     'can_see_client_history',
        //     'can_see_client_profile',
        //     'can_add_clients',
        //     'can_edit_clients',

        //     'can_see_appointments_page',
        //     'can_see_appointments',
        //     'can_add_appointments',
        //     'can_edit_appointments',
        //     'can_see_appointments_history',
        //     'can_assign_technician',
        //     'can_reassign_technician',
        //     'can_add_products',
        //     'can_edit_products',
        //     'can_change_products_status',
        //     'can_see_products',

        //     'can_see_reports_page',
        //     'can_make_reports',

        //     'can_see_employees',
        //     'can_edit_employees',
        //     'can_see_employee_profile',
        //     'can_see_employee_history',

        //     'can_see_appointment_types_page',
        //     'can_edit_appointment_types',
        //     'can_add_appointment_types',
        //     'can_see_appointment_types',

        //     'can_see_services_page',
        //     'can_edit_services',
        //     'can_add_services',
        //     'can_see_services',

        //     'can_see_roles_page',
        //     'can_edit_roles',
        //     'can_add_roles',
        //     'can_see_roles',
        //     'can_see_skills_page',
        //     'can_edit_skills',
        //     'can_add_skills',
        //     'can_see_skills',
        //     'can_add_skills_sector',
        //     'can_see_area_page',
        //     'can_edit_area',
        //     'can_add_area',
        //     'can_see_area',
        //     'can_add_work_schedule',
        //     'can_edit_work_schedule',
        //     'can_see_work_schedule',
        //     'can_add_events',
        //     'can_edit_events',
        //     'can_see_events',
        //     'can_see_events_page',
        // ];
        $permissions = [
            'clients_module',
            'cars_module',
            'users_module',
            'employees_module',
            'products_module',
            'services_module',
            'complaints_module',
            'area_manager_module',
            'appointments_module',
        ];
        foreach ($permissions as $permission) {
            Permission::create(['guard_name' => 'sanctum','name' => $permission]);
        }
        // create roles and assign created permissions
        $role = Role::create(['guard_name' => 'sanctum', 'name' => 'مسؤل النظام']);
        $role->givePermissionTo(Permission::all());
        User::find(1)->assignRole($role);
        $tech_role = Role::create(['guard_name' => 'sanctum', 'name' => 'فني']);
    }

}
