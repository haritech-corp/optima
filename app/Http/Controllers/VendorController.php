<?php

namespace App\Http\Controllers;

use App\Models\Vendor;

class VendorController extends DirectoryController
{
    protected function model(): string { return Vendor::class; }
    protected function routeName(): string { return 'vendors'; }
    protected string $searchColumn = 'vendor_name';

    protected function entityKey(): string { return 'vendor'; }

    protected function pageTitle(): string
    {
        return 'Vendor';
    }

    protected function columns(): array
    {
        return [
            'vendor_id' => 'ID',
            'vendor_name' => 'Vendor',
            'category' => 'Kategori',
            'contact_name' => 'Kontak',
            'payment_terms' => 'Term Pembayaran',
            'status' => 'Status',
        ];
    }

    protected function fields(): array
    {
        return [
            'vendor_name' => ['label' => 'Nama Vendor', 'type' => 'text', 'required' => true],
            'category' => ['label' => 'Kategori', 'type' => 'text', 'required' => false],
            'contact_name' => ['label' => 'Nama Kontak', 'type' => 'text', 'required' => false],
            'contact_email' => ['label' => 'Email', 'type' => 'email', 'required' => false],
            'contact_phone' => ['label' => 'Telepon', 'type' => 'text', 'required' => false],
            'payment_terms' => ['label' => 'Term Pembayaran', 'type' => 'text', 'required' => false],
            'status' => ['label' => 'Status', 'type' => 'select', 'required' => false, 'options' => ['active', 'inactive', 'blacklist']],
            'notes' => ['label' => 'Catatan', 'type' => 'textarea', 'required' => false],
        ];
    }

    protected function rules(): array
    {
        return [
            'vendor_name' => ['required', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:100'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'contact_email' => ['nullable', 'email', 'max:200'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string'],
        ];
    }
}