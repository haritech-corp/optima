<?php

namespace App\Services;

use App\Models\IntegrationRun;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

/**
 * Zoho Invoice integration. When Zoho is not configured (development/test) the
 * service records an integration run with status "skipped" and returns null so
 * the workflow can still be exercised end-to-end.
 */
class ZohoService
{
    public function isConfigured(): bool
    {
        return (bool) Setting::get('zoho_api_enabled', false)
            && (bool) Setting::get('zoho_access_token');
    }

    /** ERP → Zoho: create the invoice, keep zoho_invoice_id + pdf link. */
    public function createInvoice(Invoice $invoice): ?string
    {
        if (! $this->isConfigured()) {
            IntegrationRun::create([
                'integration' => 'zoho',
                'direction' => 'out',
                'record_type' => 'invoice',
                'record_id' => $invoice->invoice_id,
                'status' => 'skipped',
                'error' => 'Zoho belum dikonfigurasi (zoho_api_enabled=false).',
                'executed_at' => now(),
            ]);

            return null;
        }

        try {
            $token = (string) Setting::get('zoho_access_token');
            $organizationId = (string) Setting::get('zoho_organization_id');
            $response = Http::withToken($token)
                ->baseUrl('https://www.zohoapis.com/books/v3')
                ->asJson()
                ->post("/organizations/{$organizationId}/invoices", [
                    'customer_id' => $invoice->client_id,
                    'invoice_number' => $invoice->invoice_id,
                    'date' => $invoice->created_at?->toDateString() ?? today()->toDateString(),
                    'due_date' => $invoice->due_date?->toDateString(),
                    'line_items' => [[
                        'name' => 'Layanan OPTIMA — '.($invoice->project_id ?? ''),
                        'rate' => $invoice->nominal,
                        'quantity' => 1,
                    ]],
                ]);

            if ($response->failed()) {
                IntegrationRun::create([
                    'integration' => 'zoho',
                    'direction' => 'out',
                    'record_type' => 'invoice',
                    'record_id' => $invoice->invoice_id,
                    'status' => 'failed',
                    'response_payload' => $response->json(),
                    'error' => "Zoho HTTP {$response->status()}",
                    'executed_at' => now(),
                ]);

                return null;
            }

            $data = $response->json('invoice', []);
            $invoice->update([
                'zoho_invoice_id' => $data['invoice_id'] ?? null,
                'pdf_link' => $data['pdf_url'] ?? null,
                'synced_at' => now(),
            ]);
            IntegrationRun::create([
                'integration' => 'zoho',
                'direction' => 'out',
                'record_type' => 'invoice',
                'record_id' => $invoice->invoice_id,
                'external_id' => $data['invoice_id'] ?? null,
                'status' => 'success',
                'request_payload' => ['invoice_number' => $invoice->invoice_id],
                'response_payload' => $data,
                'executed_at' => now(),
            ]);

            return $invoice->zoho_invoice_id;
        } catch (\Throwable $e) {
            IntegrationRun::create([
                'integration' => 'zoho',
                'direction' => 'out',
                'record_type' => 'invoice',
                'record_id' => $invoice->invoice_id,
                'status' => 'failed',
                'error' => $e->getMessage(),
                'executed_at' => now(),
            ]);

            return null;
        }
    }

    /** Zoho → ERP: synchronize payment/status back to the operational DB. */
    public function syncInvoiceStatus(Invoice $invoice, ?string $zohoStatus = null): ?array
    {
        $externalId = $invoice->zoho_invoice_id;
        if (! $this->isConfigured() && $zohoStatus === null) {
            IntegrationRun::create([
                'integration' => 'zoho',
                'direction' => 'in',
                'record_type' => 'invoice',
                'record_id' => $invoice->invoice_id,
                'external_id' => $externalId,
                'status' => 'skipped',
                'error' => 'Zoho belum dikonfigurasi; status diberikan manual.',
                'executed_at' => now(),
            ]);

            return null;
        }

        $status = $zohoStatus;
        if ($status === null && $externalId) {
            $token = (string) Setting::get('zoho_access_token');
            $organizationId = (string) Setting::get('zoho_organization_id');
            $response = Http::withToken($token)->baseUrl('https://www.zohoapis.com/books/v3')
                ->get("/organizations/{$organizationId}/invoices/{$externalId}");
            $status = $response->ok() ? data_get($response->json('invoice'), 'status') : null;
        }

        $mapped = match ($status) {
            'paid' => 'Lunas',
            'partially_paid' => 'DP Diterima',
            'overdue' => 'Ditunda',
            default => $invoice->status,
        };

        $invoice->update(['status' => $mapped, 'synced_at' => now()]);
        IntegrationRun::create([
            'integration' => 'zoho',
            'direction' => 'in',
            'record_type' => 'invoice',
            'record_id' => $invoice->invoice_id,
            'external_id' => $externalId,
            'status' => 'success',
            'response_payload' => ['zoho_status' => $status, 'mapped' => $mapped],
            'executed_at' => now(),
        ]);

        return ['zoho_status' => $status, 'mapped' => $mapped];
    }
}