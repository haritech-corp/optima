<?php

namespace App\Http\Controllers;

use App\Models\MarketingCampaign;

class CampaignController extends DirectoryController
{
    protected function model(): string { return MarketingCampaign::class; }
    protected function routeName(): string { return 'campaigns'; }
    protected string $searchColumn = 'campaign_name';

    protected function entityKey(): string { return 'campaign'; }

    protected function pageTitle(): string
    {
        return 'Marketing / Blasting';
    }

    protected function columns(): array
    {
        return [
            'campaign_id' => 'ID',
            'campaign_name' => 'Campaign',
            'target_segment' => 'Target',
            'date' => 'Tanggal',
            'status' => 'Status',
            'result' => 'Hasil',
        ];
    }

    protected function fields(): array
    {
        return [
            'campaign_name' => ['label' => 'Nama Campaign', 'type' => 'text', 'required' => true],
            'target_segment' => ['label' => 'Target Segment', 'type' => 'text', 'required' => false],
            'channel' => ['label' => 'Channel (blasting)', 'type' => 'channels', 'required' => false],
            'date' => ['label' => 'Tanggal', 'type' => 'date', 'required' => false],
            'material' => ['label' => 'Material (link)', 'type' => 'textarea', 'required' => false],
            'pic_employee_id' => $this->employeeField(),
            'status' => ['label' => 'Status', 'type' => 'select', 'required' => false, 'options' => ['Planned', 'Running', 'Done', 'Paused', 'Cancelled']],
            'result' => ['label' => 'Hasil', 'type' => 'textarea', 'required' => false],
            'notes' => ['label' => 'Catatan', 'type' => 'textarea', 'required' => false],
        ];
    }

    protected function rules(): array
    {
        return [
            'campaign_name' => ['required', 'string', 'max:200'],
            'target_segment' => ['nullable', 'string', 'max:200'],
            'channel' => ['nullable', 'array'],
            'channel.*' => ['string'],
            'date' => ['nullable', 'date'],
            'material' => ['nullable', 'string'],
            'pic_employee_id' => ['nullable', 'exists:employees,employee_id'],
            'status' => ['nullable', 'string', 'max:40'],
            'result' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}