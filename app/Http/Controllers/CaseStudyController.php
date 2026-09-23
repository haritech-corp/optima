<?php

namespace App\Http\Controllers;

use App\Models\CaseStudy;

class CaseStudyController extends DirectoryController
{
    protected function model(): string { return CaseStudy::class; }
    protected function routeName(): string { return 'case-studies'; }
    protected string $searchColumn = 'title';

    protected function entityKey(): string { return 'casestudy'; }

    protected function pageTitle(): string
    {
        return 'Case Study';
    }

    protected function columns(): array
    {
        return [
            'case_study_id' => 'ID',
            'title' => 'Judul',
            'client_id' => 'Client',
            'service' => 'Layanan',
            'result_metric' => 'Metrik Hasil',
        ];
    }

    protected function fields(): array
    {
        return [
            'title' => ['label' => 'Judul', 'type' => 'text', 'required' => true],
            'client_id' => ['label' => 'Client', 'type' => 'client_select', 'required' => false],
            'service' => ['label' => 'Layanan', 'type' => 'services', 'required' => false],
            'timeline' => ['label' => 'Timeline', 'type' => 'text', 'required' => false],
            'result_metric' => ['label' => 'Metrik Hasil', 'type' => 'text', 'required' => false],
            'proof_link' => ['label' => 'Link Bukti', 'type' => 'textarea', 'required' => false],
            'notes' => ['label' => 'Catatan', 'type' => 'textarea', 'required' => false],
        ];
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'client_id' => ['nullable', 'exists:clients,client_id'],
            'service' => ['nullable', 'array'],
            'service.*' => ['string'],
            'timeline' => ['nullable', 'string', 'max:100'],
            'result_metric' => ['nullable', 'string', 'max:200'],
            'proof_link' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}