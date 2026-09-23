<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * RBAC matrix from the blueprint. scope: all | department | own
     */
    public function run(): void
    {
        $permissions = [
            // dashboard
            ['dashboard.view', 'dashboard', 'Lihat dashboard'],
            // admin (global)
            ['admin.user.manage', 'admin', 'Kelola user'],
            ['admin.department.manage', 'admin', 'Kelola departemen'],
            ['admin.role.manage', 'admin', 'Kelola role & permission'],
            ['admin.audit.view', 'admin', 'Lihat audit log'],
            ['admin.automation.view', 'admin', 'Lihat automation log'],
            ['admin.setting.manage', 'admin', 'Kelola setting'],
            // crm
            ['crm.lead.view', 'crm', 'Lihat lead'],
            ['crm.lead.create', 'crm', 'Buat lead'],
            ['crm.lead.edit', 'crm', 'Ubah lead'],
            ['crm.lead.archive', 'crm', 'Arsipkan lead'],
            ['crm.client.view', 'crm', 'Lihat client'],
            ['crm.client.create', 'crm', 'Buat client'],
            ['crm.client.edit', 'crm', 'Ubah client'],
            ['crm.community.view', 'crm', 'Lihat community'],
            ['crm.community.create', 'crm', 'Buat community'],
            ['crm.community.edit', 'crm', 'Ubah community'],
            ['crm.community.archive', 'crm', 'Arsipkan community'],
            ['crm.kol.view', 'crm', 'Lihat KOL/KOC'],
            ['crm.kol.create', 'crm', 'Buat KOL/KOC'],
            ['crm.kol.edit', 'crm', 'Ubah KOL/KOC'],
            ['crm.kol.archive', 'crm', 'Arsipkan KOL/KOC'],
            ['crm.media.view', 'crm', 'Lihat media'],
            ['crm.media.create', 'crm', 'Buat media'],
            ['crm.media.edit', 'crm', 'Ubah media'],
            ['crm.media.archive', 'crm', 'Arsipkan media'],
            ['crm.vendor.view', 'crm', 'Lihat vendor'],
            ['crm.vendor.create', 'crm', 'Buat vendor'],
            ['crm.vendor.edit', 'crm', 'Ubah vendor'],
            ['crm.vendor.archive', 'crm', 'Arsipkan vendor'],
            ['crm.interaction.view', 'crm', 'Lihat interaksi'],
            ['crm.interaction.create', 'crm', 'Catat interaksi'],
            ['crm.campaign.view', 'crm', 'Lihat marketing/blasting'],
            ['crm.campaign.create', 'crm', 'Buat campaign'],
            ['crm.campaign.edit', 'crm', 'Ubah campaign'],
            ['crm.campaign.archive', 'crm', 'Arsipkan campaign'],
            ['crm.casestudy.view', 'crm', 'Lihat case study'],
            ['crm.casestudy.create', 'crm', 'Buat case study'],
            ['crm.casestudy.edit', 'crm', 'Ubah case study'],
            ['crm.casestudy.archive', 'crm', 'Arsipkan case study'],
            // sales
            ['sales.pipeline.view', 'sales', 'Lihat pipeline'],
            ['sales.pipeline.create', 'sales', 'Buat deal'],
            ['sales.pipeline.edit', 'sales', 'Ubah deal'],
            ['sales.pipeline.close', 'sales', 'Closing deal'],
            // project
            ['project.view', 'project', 'Lihat proyek'],
            ['project.create', 'project', 'Buat proyek'],
            ['project.edit', 'project', 'Ubah proyek'],
            ['project.approve', 'project', 'Setujui task/evidence'],
            ['project.evidence', 'project', 'Unggah evidence'],
            // inventory
            ['inventory.view', 'inventory', 'Lihat inventory'],
            ['inventory.create', 'inventory', 'Buat aset'],
            ['inventory.edit', 'inventory', 'Ubah aset'],
            ['inventory.book', 'inventory', 'Booking aset'],
            ['inventory.opname', 'inventory', 'Stock opname'],
            // finance
            ['finance.view', 'finance', 'Lihat status finance'],
            ['finance.value.view', 'finance', 'Lihat nominal finance'],
            ['finance.invoice.edit', 'finance', 'Kelola invoice'],
            ['finance.payment.edit', 'finance', 'Kelola payment'],
            ['finance.reimbursement.edit', 'finance', 'Kelola reimbursement'],
            ['finance.budget.view', 'finance', 'Lihat budget'],
            // hr
            ['hr.view', 'hr', 'Lihat data SDM'],
            ['hr.employee.manage', 'hr', 'Kelola employee master'],
            ['hr.attendance.edit', 'hr', 'Kelola attendance'],
            ['hr.leave.approve', 'hr', 'Setujui cuti/izin'],
            ['hr.recruitment.manage', 'hr', 'Kelola rekrutmen'],
            ['hr.workload.view', 'hr', 'Lihat workload'],
            // approval & report
            ['approval.view', 'approval', 'Lihat approval queue'],
            ['approval.decide', 'approval', 'Memutuskan approval'],
            ['report.view', 'report', 'Lihat laporan'],
        ];

        foreach ($permissions as [$id, $module, $description]) {
            Permission::query()->updateOrCreate(
                ['permission_id' => $id],
                ['module' => $module, 'name' => ucwords(str_replace('_', ' ', str_replace('.', ' - ', $id))), 'description' => $description],
            );
        }

        $roles = [
            ['super_admin_global', 'Super Admin Global', 10],
            ['super_admin_department', 'Super Admin Department', 20],
            ['koordinator', 'Kepala/Koordinator', 30],
            ['staff', 'Staff/Associate', 40],
            ['viewer', 'Viewer', 50],
        ];

        foreach ($roles as [$id, $name, $level]) {
            Role::query()->updateOrCreate(
                ['role_id' => $id],
                ['name' => $name, 'level' => $level, 'is_active' => true],
            );
        }

        $all = array_column($permissions, 0);
        $viewerOnly = array_values(array_filter($all, fn (string $p) => str_ends_with($p, '.view') || $p === 'report.view' || $p === 'dashboard.view'));
        $noAdmin = array_values(array_filter($all, fn (string $p) => ! str_starts_with($p, 'admin.') && $p !== 'report.view' && $p !== 'dashboard.view'));

        $matrix = [
            'super_admin_global' => array_fill_keys($all, 'all'),
            'super_admin_department' => array_fill_keys($noAdmin, 'department'),
            'koordinator' => array_fill_keys(array_merge(
                array_values(array_filter($all, fn ($p) => str_ends_with($p, '.view'))),
                ['crm.lead.create', 'crm.lead.edit', 'crm.client.create', 'crm.client.edit', 'crm.interaction.create', 'crm.campaign.create', 'crm.campaign.edit', 'crm.casestudy.create', 'crm.casestudy.edit', 'sales.pipeline.create', 'sales.pipeline.edit', 'project.create', 'project.edit', 'project.approve', 'project.evidence', 'inventory.create', 'inventory.edit', 'inventory.book', 'inventory.opname', 'approval.view', 'approval.decide', 'hr.attendance.edit', 'hr.workload.view'],
            ), 'department'),
            'staff' => array_fill_keys(array_merge(
                ['dashboard.view', 'crm.lead.view', 'crm.lead.create', 'crm.lead.edit', 'crm.client.view', 'crm.client.create', 'crm.interaction.view', 'crm.interaction.create', 'crm.community.view', 'crm.kol.view', 'crm.media.view', 'crm.vendor.view', 'crm.campaign.view', 'crm.casestudy.view', 'sales.pipeline.view', 'sales.pipeline.create', 'sales.pipeline.edit', 'project.view', 'project.create', 'project.edit', 'project.evidence', 'inventory.view', 'inventory.create', 'inventory.edit', 'inventory.book', 'finance.view', 'hr.attendance.edit', 'hr.workload.view'],
            ), 'own'),
            'viewer' => array_fill_keys($viewerOnly, 'all'),
        ];

        RolePermission::query()->delete();
        foreach ($matrix as $roleId => $map) {
            foreach ($map as $permissionId => $scope) {
                if (in_array($permissionId, $all, true)) {
                    RolePermission::query()->create(['role_id' => $roleId, 'permission_id' => $permissionId, 'scope' => $scope]);
                }
            }
        }
    }
}