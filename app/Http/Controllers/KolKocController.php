<?php

namespace App\Http\Controllers;

use App\Models\KolKoc;

class KolKocController extends DirectoryController
{
    protected function model(): string { return KolKoc::class; }
    protected function routeName(): string { return 'kol-kocs'; }

    protected function entityKey(): string { return 'kol'; }

    protected function pageTitle(): string
    {
        return 'KOL / KOC';
    }

    protected function columns(): array
    {
        return [
            'kol_id' => 'ID',
            'name' => 'Nama',
            'category' => 'Kategori',
            'platform' => 'Platform',
            'contact_email' => 'Email',
            'rate_card' => 'Rate Card',
        ];
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Nama', 'type' => 'text', 'required' => true],
            'category' => ['label' => 'Kategori', 'type' => 'select', 'required' => false, 'options' => ['KOL', 'KOC', 'Micro', 'Nano', 'Nano Community']],
            'platform' => ['label' => 'Platform', 'type' => 'select', 'required' => false, 'options' => ['Instagram', 'TikTok', 'YouTube', 'Twitter/X', 'Facebook', 'LinkedIn']],
            'contact_email' => ['label' => 'Email', 'type' => 'email', 'required' => false],
            'contact_phone' => ['label' => 'Telepon', 'type' => 'text', 'required' => false],
            'rate_card' => ['label' => 'Rate Card (Rp)', 'type' => 'number', 'required' => false],
            'collaboration_history' => ['label' => 'Riwayat Kolaborasi', 'type' => 'textarea', 'required' => false],
            'notes' => ['label' => 'Catatan', 'type' => 'textarea', 'required' => false],
        ];
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'string', 'max:100'],
            'contact_email' => ['nullable', 'email', 'max:200'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'rate_card' => ['nullable', 'numeric', 'min:0'],
            'collaboration_history' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}