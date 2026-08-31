<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\ModuleActivationRequest;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = $request->user()->notifications()->latest()->paginate();
        $requestIds = $notifications->getCollection()
            ->where('type', 'module_activation_request')
            ->pluck('data')
            ->pluck('request_id')
            ->filter()
            ->map(fn ($id) => (int) $id);
        $requestStatuses = ModuleActivationRequest::withoutGlobalScopes()
            ->whereIn('id', $requestIds)
            ->pluck('status', 'id');

        $notifications->getCollection()->each(function (Notification $notification) use ($requestStatuses): void {
            $requestId = (int) data_get($notification->data, 'request_id', 0);
            $notification->request_status = $requestStatuses->get($requestId);
        });

        return Inertia::render('Notifications/Index', [
            'notifications' => NotificationResource::collection($notifications),
        ]);
    }

    public function update(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->update(['read_at' => now()]);
        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);
        return back()->with('success', 'Notifications marked as read.');
    }
}
