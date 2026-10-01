<?php

namespace App\Http\Controllers\Refactor\Chat;

use App\Models\Message;
use App\Models\ChatRoom;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use App\Events\GroupRoomUpdated;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function createRoom(Request $request)
    {
        $room = ChatRoom::create([
            'type' => $request->type,
            'name' => $request->name,
        ]);

        $room->users()->sync($request->user_ids); // for private/group
        return $room;
    }
    public function getMessages($roomId)
    {
        return Message::where('chat_room_id', $roomId)
            ->with('user')
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values();
    }

    public function getPrivateMessagesWithUser(Request $request, $userId)
    {
        $authId = auth()->id();

        // 1. Find a private room with exactly the two users
        $room = ChatRoom::where('type', 'private')
            ->whereHas('users', fn($q) => $q->where('user_id', $authId))
            ->whereHas('users', fn($q) => $q->where('user_id', $userId))
            ->withCount('users')
            ->having('users_count', '=', 2)
            ->first();

        // 2. No room? return error
        if (!$room) {
            return response()->json(['message' => 'No room found'], 404);
        }

        // 3. Return last 50 messages (with sender info)
        $messages = Message::where('chat_room_id', $room->id)
            ->with('user:id,username') // or other fields you want
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'room_id' => $room->id,
            'messages' => $messages,
        ]);
    }

    //send message to a user in a private chat room
    public function sendMessage(Request $request)
    {
        $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);

        $fromUserId = auth()->id();
        $toUserId = $request->to_user_id;
        // ✅ Prevent sending message to self

        if ($fromUserId === $toUserId) {
            return response()->json([
                'message' => 'You cannot send a message to yourself.'
            ], 422);
        }


        // Get or create private room with exactly 2 users
        $room = $this->getOrCreatePrivateRoom($fromUserId, $toUserId);

        // Save message
        $message = Message::create([
            'chat_room_id' => $room->id,
            'user_id' => $fromUserId,
            'message' => $request->message,
        ]);

        // Broadcast it (optional)
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'message' => 'Message sent',
            'room_id' => $room->id,
            'data' => $message->load('user'),
        ]);
    }


    // get single chat messages
    protected function getOrCreatePrivateRoom($userId1, $userId2): ChatRoom
    {
        // Step 1: Try to find a private room that has exactly these two users
        $existingRoom = ChatRoom::where('type', 'private')
            ->whereHas('users', fn($q) => $q->where('user_id', $userId1))
            ->whereHas('users', fn($q) => $q->where('user_id', $userId2))
            ->withCount('users')
            ->get()
            ->firstWhere('users_count', 2); // must have exactly 2 users

        if ($existingRoom) {
            return $existingRoom;
        }

        // Step 2: Create the room if not found
        $room = ChatRoom::create([
            'type' => 'private'
        ]);

        $room->users()->attach([$userId1, $userId2]);

        return $room;
    }

    // create a new group chat room

    public function createRoomWithUsers(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'name' => 'nullable|string|max:255', // Optional group name
            'image' => 'nullable', // Optional image
        ]);

        $authUser = auth()->user();
        $participantIds = collect($request->user_ids)
            ->push($authUser->id)
            ->unique()
            ->values();

        $type = count($participantIds) === 2 ? 'private' : 'group';

        // ✅ Private: return existing room if found
        if ($type === 'private') {
            $room = ChatRoom::where('type', 'private')
                ->whereHas('users', fn($q) => $q->where('user_id', $authUser->id))
                ->whereHas('users', fn($q) => $q->whereIn('user_id', $request->user_ids))
                ->withCount('users')
                ->get()
                ->filter(fn($room) => $room->users_count === 2)
                ->first();

            if ($room) {
                return response()->json([
                    'message' => 'Private room already exists',
                    'room_id' => $room->id
                ]);
            }
        }

        // ✅ Create new room

        $room = ChatRoom::create([
            'type' => $type,
            'name' => $type === 'group' ? ($request->name ?? 'Group Chat') : null,

        ]);
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('chat_rooms'), $filename);
            $room->image = 'chat_rooms/' . $filename;
        }

        $room->users()->attach($participantIds);

        return response()->json([
            'message' => 'Room created successfully',
            'room_id' => $room->id,
            'name' => $room->name,
            'image_url' => $room->image,
            'type' => $type,
'users' => collect($participantIds)->map(fn($id) => (int) $id)->values(),

        ]);
    }

    // get group chat rooms

    public function getMyGroupChatRooms()
    {
        $user = auth()->user();

        $rooms = $user->chatRooms()
            // ->where('type', 'group')
            ->with(['users:id,username,email,phone', 'messages' => function ($q) {
                $q->latest()->limit(1); // latest message per room (optional)
            }])
            ->latest()
            ->get();

        return response()->json([
            'rooms' => $rooms
        ]);
    }

    // send message to a group chat room
    public function sendMessageToGroup(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:chat_rooms,id',
            'message' => 'required|string|max:1000',
        ]);

        $user = auth()->user();
        $room = ChatRoom::with('users')->findOrFail($request->room_id);

        // Ensure it's a group room and user is a member
        if ($room->type !== 'group') {
            return response()->json(['message' => 'Not a group room'], 403);
        }

        if (!$room->users->contains($user->id)) {
            return response()->json(['message' => 'You are not a member of this room'], 403);
        }

        // Create the message
        $message = Message::create([
            'chat_room_id' => $room->id,
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        // Broadcast event (optional)
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => $message->load('user')
        ]);
    }


    // update group chat room

    public function updateGroupRoom(Request $request, $roomId)
    {
        $request->validate([
            'name'  => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $authUser = auth()->user();

        $room = ChatRoom::where('id', $roomId)
            ->where('type', 'group')
            ->whereHas('users', fn($q) => $q->where('user_id', $authUser->id))
            ->firstOrFail();

        if ($request->has('name')) {
            $room->name = $request->name;
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('chat_rooms'), $filename);
            $room->image = 'chat_rooms/' . $filename;
        }
        $room->save();


        // Broadcast the updated room event
        broadcast(new GroupRoomUpdated($room))->toOthers();
        return response()->json([
            'message' => 'Chat room updated',
            'room' => [
                'id' => $room->id,
                'name' => $room->name,
                'image_url' => $room->image,
            ]
        ]);
    }


    // add users to group chat room
    public function addUsersToRoom(Request $request, $roomId)
    {
        $request->validate([
            'user_ids'   => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $authUser = auth()->user();

        $room = ChatRoom::where('id', $roomId)
            ->where('type', 'group')
            ->whereHas('users', fn($q) => $q->where('user_id', $authUser->id))
            ->firstOrFail();

        $room->users()->syncWithoutDetaching($request->user_ids);

        return response()->json(['message' => 'Users added to room']);
    }

    // remove user from group chat room
    public function removeUsersFromRoom(Request $request, $roomId)
    {
        $request->validate([
            'user_ids'   => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $authUser = auth()->user();

        $room = ChatRoom::where('id', $roomId)
            ->where('type', 'group')
            ->whereHas('users', fn($q) => $q->where('user_id', $authUser->id))
            ->firstOrFail();

        $room->users()->detach($request->user_ids);

        return response()->json(['message' => 'Users removed from room']);
    }
}
