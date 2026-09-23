<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DepartmentModuleAccess;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Single master department records — Commercial Dept and Business
     * Development & Client Partner share ONE record (blueprint rule).
     */
    public function run(): void
    {
        $departments = [
            ['OPT-MGT', 'Management', 'Management', null],
            ['OPT-OPS', 'Business Operation Dept.', 'Business Operation', null],
            ['OPT-BOF', 'Business Operation - Finance', 'Finance', 'OPT-OPS'],
            ['OPT-BOH', 'Business Operation - HR & GA', 'HR & GA', 'OPT-OPS'],
            ['OPT-COM', 'Business Development & Client Partner / Commercial', 'Commercial', null],
            ['OPT-DC', 'Digital Campaign Dept.', 'Digital Campaign', null],
            ['OPT-CM', 'Creative & Media Dept.', 'Creative & Media', null],
            ['OPT-CN', 'CampusNet Coordinator', 'CampusNet', null],
            ['OPT-DP', 'DietPartner Coordinator', 'DietPartner', null],
            ['OPT-CS', 'Customer Service', 'Customer Service', null],
        ];

        foreach ($departments as [$id, $name, $code, $parent]) {
            Department::query()->updateOrCreate(
                ['department_id' => $id],
                ['name' => $name, 'code' => $code, 'parent_id' => $parent, 'is_active' => true],
            );
        }

        // Department ownership matrix (blueprint department_ownership).
        $matrix = [
            'OPT-MGT' => [['dashboard', 'full'], ['admin', 'full'], ['crm', 'full'], ['sales', 'full'], ['project', 'full'], ['inventory', 'full'], ['finance', 'full'], ['hr', 'full']],
            'OPT-COM' => [['sales', 'full'], ['crm', 'full'], ['project', 'related'], ['finance', 'read']],
            'OPT-CM' => [['project', 'full'], ['inventory', 'read']],
            'OPT-CN' => [['project', 'full'], ['crm', 'read'], ['inventory', 'read']],
            'OPT-DP' => [['project', 'full'], ['crm', 'read'], ['inventory', 'read']],
            'OPT-DC' => [['project', 'full'], ['inventory', 'read']],
            'OPT-BOF' => [['finance', 'full'], ['project', 'read'], ['inventory', 'read']],
            'OPT-BOH' => [['hr', 'full'], ['inventory', 'full'], ['project', 'read']],
            'OPT-CS' => [['crm', 'full'], ['sales', 'related']],
        ];

        foreach ($matrix as $departmentId => $modules) {
            foreach ($modules as [$module, $level]) {
                DepartmentModuleAccess::query()->updateOrCreate(
                    ['department_id' => $departmentId, 'module' => $module],
                    ['access_level' => $level],
                );
            }
        }
    }
}