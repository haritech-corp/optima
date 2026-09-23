<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['PR', 'Public Relation', 1],
            ['KOL', 'KOL & KOC Marketing', 2],
            ['OOH', 'OOH Advertising', 3],
            ['PMS', 'Programmatic & Social Media Ads', 4],
            ['WAD', 'Website & Apps Development', 5],
            ['CRP', 'Creative Production', 6],
        ];

        foreach ($services as [$id, $name, $sort]) {
            Service::query()->updateOrCreate(
                ['service_id' => $id],
                ['name' => $name, 'sort_order' => $sort, 'is_active' => true],
            );
        }

        $templates = [
            'PR' => ['Brief', 'Draft Rilis', 'Review Klien', 'Distribusi Media', 'Report Clipping'],
            'KOL' => ['Sourcing KOL', 'Nego Rate', 'Brief Konten', 'Approval', 'Posting', 'Report Engagement'],
            'OOH' => ['Cek Availability Inventory', 'Booking', 'Produksi Material', 'Pasang', 'Dokumentasi'],
            'PMS' => ['Setup Campaign', 'Approval Budget', 'Jalankan', 'Optimasi', 'Report Performance'],
            'WAD' => ['Requirement', 'Desain', 'Development', 'Testing', 'Deploy', 'Maintenance'],
            'CRP' => ['Brief Kreatif', 'Konsep', 'Produksi', 'Revisi', 'Final Delivery'],
        ];

        ServiceTemplate::query()->delete();
        foreach ($templates as $serviceId => $steps) {
            foreach ($steps as $i => $step) {
                ServiceTemplate::query()->create([
                    'template_id' => sprintf('%s-%02d', $serviceId, $i + 1),
                    'service_id' => $serviceId,
                    'step_name' => $step,
                    'sequence' => $i + 1,
                    'is_default' => true,
                ]);
            }
        }
    }
}