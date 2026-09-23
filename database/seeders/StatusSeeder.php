<?php

namespace Database\Seeders;

use App\Models\StatusMaster;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Controlled vocabulary (blueprint data governance): every status field
     * only accepts values registered here.
     */
    public function run(): void
    {
        $statuses = [
            ['deal_stage', 'Prospek', 'Prospek'],
            ['deal_stage', 'Pitching', 'Pitching'],
            ['deal_stage', 'Negosiasi', 'Negosiasi'],
            ['deal_stage', 'Deal', 'Deal'],
            ['deal_stage', 'Closing', 'Closing'],

            ['deal_status', 'open', 'Open'],
            ['deal_status', 'won', 'Won'],
            ['deal_status', 'lost', 'Lost'],
            ['deal_status', 'on_hold', 'On Hold'],

            ['project_status', 'Meeting', 'Meeting'],
            ['project_status', 'Planning', 'Planning'],
            ['project_status', 'Draft', 'Draft'],
            ['project_status', 'Proposal', 'Proposal'],
            ['project_status', 'Quotation', 'Quotation'],
            ['project_status', 'Active', 'Active'],
            ['project_status', 'On Hold', 'On Hold'],
            ['project_status', 'At Risk', 'At Risk'],
            ['project_status', 'Completed', 'Completed'],
            ['project_status', 'Closed', 'Closed'],
            ['project_status', 'Cancelled', 'Cancelled'],

            ['task_status', 'To Do', 'To Do'],
            ['task_status', 'In Progress', 'In Progress'],
            ['task_status', 'Review', 'Review'],
            ['task_status', 'Revision', 'Revision'],
            ['task_status', 'Approved', 'Approved'],
            ['task_status', 'Done', 'Done'],
            ['task_status', 'Blocked', 'Blocked'],

            ['asset_ooh_status', 'Available', 'Available'],
            ['asset_ooh_status', 'Booked', 'Booked'],
            ['asset_ooh_status', 'Maintenance', 'Maintenance'],

            ['asset_platform_status', 'Available', 'Available'],
            ['asset_platform_status', 'Booked', 'Booked'],
            ['asset_platform_status', 'Used', 'Used'],
            ['asset_platform_status', 'Expired', 'Expired'],

            ['asset_condition', 'Baik', 'Baik'],
            ['asset_condition', 'Perlu Perbaikan', 'Perlu Perbaikan'],
            ['asset_condition', 'Rusak', 'Rusak'],

            ['invoice_status', 'DP Diterima', 'DP Diterima'],
            ['invoice_status', 'Menunggu Pelunasan', 'Menunggu Pelunasan'],
            ['invoice_status', 'Lunas', 'Lunas'],
            ['invoice_status', 'Ditunda', 'Ditunda'],

            ['invoice_request_status', 'Diajukan', 'Diajukan'],
            ['invoice_request_status', 'Disetujui', 'Disetujui'],
            ['invoice_request_status', 'Ditolak', 'Ditolak'],

            ['payment_status', 'Diajukan', 'Diajukan'],
            ['payment_status', 'Diproses', 'Diproses'],
            ['payment_status', 'Pending', 'Pending'],
            ['payment_status', 'DP', 'DP'],
            ['payment_status', 'Sebagian', 'Sebagian'],
            ['payment_status', 'Lunas', 'Lunas'],
            ['payment_status', 'Tertunda', 'Tertunda'],

            ['reimbursement_status', 'Diajukan', 'Diajukan'],
            ['reimbursement_status', 'Disetujui Atasan', 'Disetujui Atasan'],
            ['reimbursement_status', 'Diproses Finance', 'Diproses Finance'],
            ['reimbursement_status', 'Dibayarkan', 'Dibayarkan'],

            ['leave_status', 'Diajukan', 'Diajukan'],
            ['leave_status', 'Disetujui Atasan', 'Disetujui Atasan'],
            ['leave_status', 'Disetujui HR', 'Disetujui HR'],
            ['leave_status', 'Selesai', 'Selesai'],
            ['leave_status', 'Ditolak', 'Ditolak'],

            ['recruitment_status', 'Department Request', 'Department Request'],
            ['recruitment_status', 'HR Opens Vacancy', 'HR Opens Vacancy'],
            ['recruitment_status', 'Screening', 'Screening'],
            ['recruitment_status', 'Interview', 'Interview'],
            ['recruitment_status', 'Offering', 'Offering'],
            ['recruitment_status', 'Closed', 'Closed'],
            ['recruitment_status', 'Onboarding', 'Onboarding / Create Employee Master'],

            ['lead_status', 'new', 'New'],
            ['lead_status', 'contacted', 'Contacted'],
            ['lead_status', 'qualified', 'Qualified'],
            ['lead_status', 'nurturing', 'Nurturing'],
            ['lead_status', 'converted', 'Converted'],
            ['lead_status', 'unqualified', 'Unqualified'],

            ['attendance_status', 'Hadir', 'Hadir'],
            ['attendance_status', 'Telat', 'Telat'],
            ['attendance_status', 'Izin', 'Izin'],
            ['attendance_status', 'Sakit', 'Sakit'],
            ['attendance_status', 'Cuti', 'Cuti'],
            ['attendance_status', 'Absent', 'Absent'],

            ['community_relationship', 'Active', 'Active'],
            ['community_relationship', 'Passive', 'Passive'],
            ['community_relationship', 'Cold', 'Cold'],

            ['approval_status', 'Pending', 'Pending'],
            ['approval_status', 'Approved', 'Approved'],
            ['approval_status', 'Rejected', 'Rejected'],
            ['approval_status', 'Revision', 'Revision'],
        ];

        foreach ($statuses as $i => [$category, $key, $label]) {
            StatusMaster::query()->updateOrCreate(
                ['category' => $category, 'status_key' => $key],
                ['label' => $label, 'sort_order' => $i],
            );
        }
    }
}