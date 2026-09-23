<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['OPT-MGT', 'Management'], ['OPT-CRM', 'CRM'], ['OPT-OPS', 'Operations']] as [$code, $name]) {
            Department::query()->firstOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
        }
    }
}
