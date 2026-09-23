<?php

namespace App\Http\Controllers;

use App\Models\Community;

class CommunityController extends DirectoryController
{
    protected function model(): string { return Community::class; }
    protected function routeName(): string { return 'communities'; }
    protected string $searchColumn = 'community_name';

    protected function entityKey(): string { return 'community'; }

    protected function columns(): array
    {
        return [
            'community_id' => 'ID',
            'community_name' => 'Community',
            'platform' => 'Platform',
            'segment' => 'Segment',
            'contact_email' => 'Email',
            'relationship_status' => 'Status Relasi',
            'pic_employee_id' => 'PIC',
        ];
    }

    protected function fields(): array
    {
        return [
            'community_name' => ['label' => 'Nama Community', 'type' => 'text', 'required' => true],
            'platform' => ['label' => 'Platform', 'type' => 'text', 'required' => true],
            'segment' => ['label' => 'Segment', 'type' => 'text', 'required' => false],
            'contact_email' => ['label' => 'Email Kontak', 'type' => 'email', 'required' => false],
            'contact_phone' => ['label' => 'Telepon', 'type' => 'text', 'required' => false],
            'relationship_status' => ['label' => 'Status Relasi', 'type' => 'select', 'required' => false, 'options' => ['Active', 'Passive', 'Cold']],
            'pic_employee_id' => $this->employeeField(),
            'notes' => ['label' => 'Catatan', 'type' => 'textarea', 'required' => false],
        ];
    }

    protected function rules(): array
    {
        return [
            'community_name' => ['required', 'string', 'max:200'],
            'platform' => ['required', 'string', 'max:100'],
            'segment' => ['nullable', 'string', 'max:100'],
            'contact_email' => ['nullable', 'email', 'max:200'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'relationship_status' => ['nullable', 'string', 'max:40'],
            'pic_employee_id' => ['nullable', 'exists:employees,employee_id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}