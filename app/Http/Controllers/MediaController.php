<?php

namespace App\Http\Controllers;

use App\Models\MediaOutlet;

class MediaController extends DirectoryController
{
    protected function model(): string { return MediaOutlet::class; }
    protected function routeName(): string { return 'media'; }
    protected string $searchColumn = 'media_name';

    protected function entityKey(): string { return 'media'; }

    protected function pageTitle(): string
    {
        return 'Media';
    }

    protected function columns(): array
    {
        return [
            'media_id' => 'ID',
            'media_name' => 'Media',
            'category' => 'Kategori',
            'platform' => 'Platform',
            'contact_email' => 'Email',
        ];
    }

    protected function fields(): array
    {
        return [
            'media_name' => ['label' => 'Nama Media', 'type' => 'text', 'required' => true],
            'category' => ['label' => 'Kategori', 'type' => 'select', 'required' => false, 'options' => ['Online', 'Cetak', 'TV', 'Radio', 'Community']],
            'platform' => ['label' => 'Platform', 'type' => 'text', 'required' => false],
            'contact_email' => ['label' => 'Email', 'type' => 'email', 'required' => false],
            'contact_phone' => ['label' => 'Telepon', 'type' => 'text', 'required' => false],
            'history' => ['label' => 'Riwayat', 'type' => 'textarea', 'required' => false],
            'notes' => ['label' => 'Catatan', 'type' => 'textarea', 'required' => false],
        ];
    }

    protected function rules(): array
    {
        return [
            'media_name' => ['required', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'string', 'max:100'],
            'contact_email' => ['nullable', 'email', 'max:200'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'history' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}