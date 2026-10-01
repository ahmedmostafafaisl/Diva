<?php

namespace App\Http\Controllers\Refactor\Notification;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Services\FirebaseNotificationService;
use App\Http\Requests\Notification\NotificationStoreRequest;
use App\Http\Requests\Notification\NotificationUpdateRequest;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use App\Http\Resources\Refactor\Notification\NotificationResource;


class NotificationController extends Controller
{
    protected $notificationRepository;
    protected $firebaseNotificationService;




    public function __construct(NotificationRepositoryInterface $notificationRepository, FirebaseNotificationService $firebaseNotificationService)
    {
        $this->notificationRepository = $notificationRepository;
        $this->firebaseNotificationService = $firebaseNotificationService;
    }

    public function index()
    {
        return NotificationResource::collection($this->notificationRepository->all());
    }

    public function store(NotificationStoreRequest $request)
    {
        $notification = $this->notificationRepository->create($request->validated());
        return new NotificationResource($notification);
    }

    public function show($id)
    {
        $notification = $this->notificationRepository->find($id);
        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }
        return new NotificationResource($notification);
    }

    public function update(NotificationUpdateRequest $request, $id)
    {
        $updated = $this->notificationRepository->update($id, $request->validated());
        if (!$updated) {
            return response()->json(['message' => 'Update failed'], 400);
        }
        return response()->json(['message' => 'Notification updated successfully']);
    }

    public function destroy($id)
    {
        $deleted = $this->notificationRepository->delete($id);
        if (!$deleted) {
            return response()->json(['message' => 'Delete failed'], 400);
        }
        return response()->json(['message' => 'Notification deleted successfully']);
    }

    public function sendPushNotification(NotificationStoreRequest $request)
    {
        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        if (!$user || !$user->fcm_token) {
            return response()->json(['success' => false, 'message' => 'User does not have a device token'], 400);
        }


        if (!empty($user->fcm_token)) {
            $title = $request->title ?? '  جديدة';
            $body =  $request->body ?? 'لديك إشعار جديد';
            $response = $this->firebaseNotificationService->sendNotification($user->fcm_token,  $user->id, $title, $body, $request->data ?? []);
        }

        return response()->json($response);
    }



    public function getUserNotifications()
    {
        return NotificationResource::collection($this->notificationRepository->getUserNotifications());
    }

    public function makeAsRead($id)
    {
        $notification = $this->notificationRepository->find($id);
        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }
        $notification->read = 1;
        $notification->save();
        return response()->json(['message' => 'Notification marked as read']);
    }
}
