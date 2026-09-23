<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\AccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $me = AccessService::employeeId();

        return view('notifications.index', [
            'notifications' => Notification::query()->where('employee_id', $me)->latest()->paginate(30),
        ]);
    }

    public function markRead(Request $request, Notification $notification): RedirectResponse
    {
        if ($notification->employee_id === AccessService::employeeId()) {
            $notification->update(['read_at' => now()]);
            if ($notification->link) {
                return redirect()->to($notification->link);
            }
        }

        return back();
    }

    public function markAll(Request $request): RedirectResponse
    {
        Notification::query()
            ->where('employee_id', AccessService::employeeId())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('status', 'Semua notifikasi ditandai dibaca.');
    }
}