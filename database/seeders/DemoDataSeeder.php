<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Community;
use App\Models\Deal;
use App\Models\Employee;
use App\Models\Interaction;
use App\Models\KolKoc;
use App\Models\Lead;
use App\Models\MediaOutlet;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $pic = Employee::query()->where('email', 'fajar.putra@optima.co.id')->first();
        $picId = $pic?->employee_id;
        $cs = Employee::query()->where('email', 'gita.ayu@optima.co.id')->first();

        if (! $picId) {
            return;
        }

        $client = Client::query()->firstOrCreate(
            ['name' => 'PT Nusantara Sejahtera'],
            [
                'legal_name' => 'PT Nusantara Sejahtera',
                'sector' => 'FMCG',
                'contact_email' => 'hello@nusantarasejahtera.co.id',
                'owner_employee_id' => $picId,
                'service_history' => ['KOL & KOC Marketing', 'OOH Advertising'],
                'total_contract' => 250_000_000,
                'status' => 'active',
            ],
        );

        $client2 = Client::query()->firstOrCreate(
            ['name' => 'PT Kopi Nusantara'],
            [
                'legal_name' => 'PT Kopi Nusantara',
                'sector' => 'F&B',
                'contact_email' => 'info@kopinusantara.co.id',
                'owner_employee_id' => $cs?->employee_id ?? $picId,
                'total_contract' => 120_000_000,
                'status' => 'active',
            ],
        );

        foreach ([
            ['Lead Industri Kreatif', 'Indonesia Creative Network', 'Creative', 'Website', 'qualified', $picId, $client2->client_id],
            ['Lead F&B Jakarta', 'Cafe Senja', 'F&B', 'Referral', 'contacted', $picId, null],
            ['Lead Edukasi', 'Edukasi Kita', 'Education', 'Instagram', 'nurturing', $picId, null],
        ] as [$name, $org, $sector, $source, $status, $picEmp, $converted]) {
            Lead::query()->firstOrCreate(
                ['name' => $name],
                [
                    'company_or_community' => $org,
                    'sector' => $sector,
                    'source' => $source,
                    'status' => $status,
                    'pic_employee_id' => $picEmp,
                    'client_id' => $converted,
                    'converted_at' => $converted ? now() : null,
                ],
            );
        }

        Community::query()->firstOrCreate(
            ['community_name' => 'Kampus Merdeka Jakarta'],
            ['platform' => 'Community', 'segment' => 'Education', 'relationship_status' => 'Active', 'pic_employee_id' => $picId],
        );
        Community::query()->firstOrCreate(
            ['community_name' => 'Komunitas UMKM Digital'],
            ['platform' => 'WhatsApp', 'segment' => 'UMKM', 'relationship_status' => 'Active', 'pic_employee_id' => $picId],
        );

        KolKoc::query()->firstOrCreate(
            ['name' => 'Raka Nugraha'],
            ['category' => 'KOL', 'platform' => 'Instagram', 'rate_card' => 15_000_000],
        );

        MediaOutlet::query()->firstOrCreate(
            ['media_name' => 'Daily News Indonesia'],
            ['category' => 'Online', 'platform' => 'Portal'],
        );

        Vendor::query()->firstOrCreate(
            ['vendor_name' => 'Vendor Cetak Utama'],
            ['category' => 'Production', 'status' => 'active'],
        );

        Deal::query()->firstOrCreate(
            ['client_id' => $client->client_id, 'stage' => 'Negosiasi'],
            [
                'pic_employee_id' => $picId,
                'service' => ['KOL', 'OOH'],
                'deal_value' => 350_000_000,
                'target_date' => now()->addMonths(2)->toDateString(),
                'probability' => 60,
                'next_action' => 'Finalisasi scope kreatif dan media',
                'next_action_date' => now()->addWeek()->toDateString(),
                'status' => 'open',
            ],
        );

        $lead = Lead::query()->where('name', 'Lead Industri Kreatif')->first();
        if ($lead) {
            Interaction::query()->firstOrCreate(
                ['entity_type' => 'lead', 'entity_id' => $lead->lead_id, 'interaction_date' => now()],
                [
                    'pic_employee_id' => $picId,
                    'activity' => 'Kick-off meeting scope kampanye',
                    'result' => 'Tertarik dengan paket KOL + OOH',
                    'next_action' => 'Kirim proposal formal',
                    'follow_up_date' => now()->addWeek()->toDateString(),
                    'status' => 'done',
                ],
            );
        }
    }
}