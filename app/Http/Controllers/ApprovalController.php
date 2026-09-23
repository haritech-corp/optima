<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Services\AccessService;
use App\Services\ApprovalEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:approval.view');
    }

    public function index(Request $request): View
    {
        $me = AccessService::employeeId();
        $query = ApprovalRequest::query()
            ->with(['requester', 'currentApprover', 'steps.approver']);

        if (! AccessService::can('approval.decide') || $request->string('filter')->toString() === 'mine') {
            $query->where('current_approver_employee_id', $me);
        } elseif ($request->string('filter')->toString() === 'submitted') {
            $query->where('requester_employee_id', $me);
        }

        return view('approvals.index', [
            'requests' => $query->latest()->paginate(20)->withQueryString(),
            'filter' => $request->string('filter')->toString(),
        ]);
    }

    public function decide(Request $request, ApprovalRequest $approvalRequest): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:Approve,Reject,Revision'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            ApprovalEngine::decide($approvalRequest, AccessService::employeeId(), $data['decision'], $data['note']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['decision' => $e->getMessage()]);
        }

        return redirect()->route('approvals.index')->with('status', 'Keputusan approval berhasil disimpan.');
    }

    public function show(ApprovalRequest $approvalRequest): View
    {
        $approvalRequest->load(['requester', 'currentApprover', 'steps.approver']);

        return view('approvals.show', ['request' => $approvalRequest]);
    }
}