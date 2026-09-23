<?php

namespace App\Http\Controllers;

use App\Models\Evidence;
use App\Models\Project;
use App\Models\Task;
use App\Services\AccessService;
use App\Services\ApprovalEngine;
use App\Services\AutomationLogger;
use App\Services\WorkloadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:project.view')->only(['edit']);
        $this->middleware('permission:project.edit')->only(['update', 'evidence', 'requestApproval']);
    }

    public function edit(Task $task): View
    {
        $task->load(['project.client', 'activity', 'pic', 'evidence.uploader']);

        return view('tasks.edit', [
            'task' => $task,
            'employees' => \App\Models\Employee::query()->where('status', 'active')->orderBy('name')->get(),
            'services' => config('optima.service_lines'),
            'taskStatusOptions' => \App\Models\StatusMaster::labels('task_status'),
        ]);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $data = $request->validate([
            'task_name' => ['required', 'string', 'max:200'],
            'brief' => ['nullable', 'string'],
            'stage' => ['nullable', 'string', 'max:80'],
            'pic_employee_id' => ['nullable', 'exists:employees,employee_id'],
            'start_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date'],
            'status' => ['required', 'string'],
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'publishing_information' => ['nullable', 'string'],
            'material_attachment' => ['nullable', 'string'],
            'evidence' => ['nullable', 'string'],
            'issue' => ['nullable', 'string'],
            'next_action' => ['nullable', 'string'],
        ]);

        if (($data['status'] ?? '') === Task::DONE) {
            $data['is_completed'] = true;
            $data['completed_at'] = now();
            $data['progress_percent'] = 100;
            $data['approval_status'] = 'Approved';
        }

        $oldPic = $task->pic_employee_id;
        $task->update($data);
        $task->project?->recalculateProgress();
        WorkloadService::syncFor($oldPic);
        WorkloadService::syncFor($task->pic_employee_id);

        return redirect()->route('projects.show', $task->project_id)->with('status', 'Task berhasil diperbarui.');
    }

    /** Upload evidence (photo/screenshot link) for a task. */
    public function evidence(Request $request, Task $task): RedirectResponse
    {
        $data = $request->validate([
            'photo_link_screenshot' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string'],
        ]);

        Evidence::create($data + [
            'task_id' => $task->task_id,
            'uploaded_by_employee_id' => AccessService::employeeId(),
        ]);

        return back()->with('status', 'Evidence berhasil diunggah.');
    }

    /** Route a task into the shared approval engine (content/client approval). */
    public function requestApproval(Task $task): RedirectResponse
    {
        $hasPending = $task->approvalRequest()->where('status', 'Pending')->exists();
        if ($hasPending) {
            return back()->withErrors(['approval' => 'Task ini masih memiliki approval yang belum diputuskan.']);
        }

        $approver = $task->project->main_pic_employee_id !== $task->pic_employee_id
            ? $task->project->main_pic_employee_id
            : \App\Models\Employee::query()->whereKey($task->pic_employee_id)->value('manager_employee_id');

        if (! $approver) {
            return back()->withErrors(['approval' => 'Tidak ada approver yang tersedia untuk task ini.']);
        }

        ApprovalEngine::submit('task', $task->task_id, (string) $task->pic_employee_id ?: AccessService::employeeId(), [
            ['approver_type' => 'content', 'approver_employee_id' => $approver],
        ]);

        $task->update(['status' => 'Review', 'approval_status' => 'Pending']);
        AutomationLogger::log('content_approval_submitted', 'task', $task->task_id, 'success');

        return back()->with('status', 'Task dikirim untuk approval konten.');
    }
}