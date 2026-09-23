<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\LeaveQuota;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // [employee_id suffix is auto-generated], name, email, dept, position, role, manager(email)
        $people = [
            ['Ari Wibowo', 'ari.wibowo@optima.co.id', 'OPT-MGT', 'CEO / Director', 'super_admin_global', null],
            ['Budi Santoso', 'budi.santoso@optima.co.id', 'OPT-DC', 'Ads Specialist', 'koordinator', 'ari.wibowo@optima.co.id'],
            ['Citra Dewi', 'citra.dewi@optima.co.id', 'OPT-DC', 'KOL & Activation Campaign Associate', 'staff', 'budi.santoso@optima.co.id'],
            ['Dewi Lestari', 'dewi.lestari@optima.co.id', 'OPT-BOF', 'Finance Associate', 'staff', 'ari.wibowo@optima.co.id'],
            ['Eko Prasetyo', 'eko.prasetyo@optima.co.id', 'OPT-BOH', 'HR & GA Lead', 'super_admin_department', 'ari.wibowo@optima.co.id'],
            ['Hendra Wijaya', 'hendra.wijaya@optima.co.id', 'OPT-COM', 'Kepala Business Development', 'koordinator', 'ari.wibowo@optima.co.id'],
            ['Fajar Putra', 'fajar.putra@optima.co.id', 'OPT-COM', 'Business Development Associate', 'staff', 'hendra.wijaya@optima.co.id'],
            ['Gita Ayu', 'gita.ayu@optima.co.id', 'OPT-COM', 'Client Partner Associate', 'staff', 'hendra.wijaya@optima.co.id'],
            ['Intan Permata', 'intan.permata@optima.co.id', 'OPT-CS', 'Customer Service', 'staff', 'ari.wibowo@optima.co.id'],
            ['Joko Susilo', 'joko.susilo@optima.co.id', 'OPT-CM', 'Creative Associate', 'staff', 'ari.wibowo@optima.co.id'],
            ['Karin Anjani', 'karin.anjani@optima.co.id', 'OPT-CN', 'Journalist Associate', 'staff', 'ari.wibowo@optima.co.id'],
            ['Lukman Hakim', 'lukman.hakim@optima.co.id', 'OPT-CN', 'Creative Associate', 'staff', 'karin.anjani@optima.co.id'],
            ['Maya Sari', 'maya.sari@optima.co.id', 'OPT-DP', 'Nutritionist', 'staff', 'maya.sari@optima.co.id'],
            ['Nanda Pratama', 'nanda.pratama@optima.co.id', 'OPT-DP', 'Creative Associate', 'staff', 'maya.sari@optima.co.id'],
            ['Olivia Rahma', 'olivia.rahma@optima.co.id', 'OPT-DP', 'Journalist Associate', 'staff', 'maya.sari@optima.co.id'],
        ];

        foreach ($people as [$name, $email, $departmentId, $position, $role, $managerEmail]) {
            $employee = Employee::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'department_id' => $departmentId,
                    'position' => $position,
                    'access_role' => $role,
                    'start_date' => now()->subMonths(6)->toDateString(),
                    'contract_status' => 'Contract',
                    'status' => 'active',
                    'is_active' => true,
                ],
            );

            if ($managerEmail) {
                $manager = Employee::query()->where('email', $managerEmail)->first();
                if ($manager) {
                    $employee->update(['manager_employee_id' => $manager->employee_id]);
                }
            }

            foreach (['Cuti Tahunan', 'Izin', 'Sakit'] as $type) {
                LeaveQuota::query()->updateOrCreate(
                    ['employee_id' => $employee->employee_id, 'year' => now()->year, 'type' => $type],
                    ['total_days' => $type === 'Cuti Tahunan' ? 12 : 6, 'used_days' => 0],
                );
            }
        }

        // Campusnet & DietPartner coordinators report to the CEO in the demo.
        $ceo = Employee::query()->where('email', 'ari.wibowo@optima.co.id')->value('employee_id');
        Employee::query()->whereIn('email', ['karin.anjani@optima.co.id', 'maya.sari@optima.co.id'])->update(['manager_employee_id' => $ceo]);

        Setting::set('notification_channels', json_encode(config('optima.notification_channels')));
        Setting::set('zoho_api_enabled', 'false');
        Setting::set('task_reminder_hours', json_encode(config('optima.automation.task_reminder_hours')));
    }
}