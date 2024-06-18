<?php

namespace App\Http\Controllers;

use App\Events\ChatEvent;
use App\Http\Requests\ChatSendMessageRequest;
use App\Models\Chat;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    use ApiResponse;

    public function allChatUsers(){
        try {
            $userId = Auth::user()->id;
            
            $users = Chat::where('sender_id', $userId)
                ->orWhere('receiver_id', $userId)
                ->distinct()
                ->get(['sender_id', 'receiver_id']);
            
            $userIds = $users->map(function ($item) use ($userId) {
                return $item->sender_id == $userId ? $item->receiver_id : $item->sender_id;
            })->unique();

            $chatUsers = $userIds->map(function ($id) use ($userId) {
                $user = User::find($id);
                
                $lastMessage = Chat::where(function ($query) use ($id, $userId) {
                    $query->where('sender_id', $userId)->where('receiver_id', $id);
                })->orWhere(function ($query) use ($id, $userId) {
                    $query->where('sender_id', $id)->where('receiver_id', $userId);
                })->latest()->first();
    
                return [
                    'user' => $user,
                    'last_message' => [
                        'id' => $lastMessage->id,
                        'message' => $lastMessage->message,
                    ],
                ];
            });
            
            return $this->successResponse('All chat users fetched successfully', $chatUsers, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch all chat users', $this->formatException($e), 500);
        }
    }


    public function messages($receiver_id){
        try {
            $messages = Chat::where(function ($query) use ($receiver_id) {
                                $query->where('sender_id', Auth::user()->id)
                                      ->orWhere('sender_id', $receiver_id);
                            })
                            ->where(function ($query) use ($receiver_id) {
                                $query->where('receiver_id', $receiver_id)
                                      ->orWhere('receiver_id', Auth::user()->id);
                            })
                            ->orderBy('created_at', 'asc')
                            ->get();
            
            return $this->successResponse('messages fetched successfully', $messages, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to fetch messages', $this->formatException($e), 500);
        }
    }

    public function store(ChatSendMessageRequest $request){
        DB::beginTransaction();

        try {
            $request->validated();

            $message = Chat::create([
                'message' => $request->message,
                'sender_id' => Auth::user()->id,
                'receiver_id' => $request->receiver_id
            ]);

            $message->load(['sender', 'receiver']);

            ChatEvent::dispatch($message, 'created');
            
            DB::commit();
            return $this->successResponse('message send successfully', $message, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('failed to send message', $this->formatException($e), 500);
        }
    }

    public function update(Request $request,$id){
        DB::beginTransaction();

        try {
            $message = Chat::find($id);
            if(!$message) return $this->errorResponse('message not found', null, 404);

            $message->message = $request->filled('message') ? $request->message : $message->message;
            $message->save();

            ChatEvent::dispatch($message, 'updated');
            
            DB::commit();
            return $this->successResponse('message updated successfully', $message, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('failed to updated message', $this->formatException($e), 500);
        }
    }

    public function destroy($id){
        DB::beginTransaction();

        try {
            $message = Chat::find($id);
            if(!$message) return $this->errorResponse('message not found', null, 404);

            ChatEvent::dispatch($message, 'deleted');
            
            $message->delete();
            
            DB::commit();
            return $this->successResponse('message deleted successfully', $message, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('failed to deleted message', $this->formatException($e), 500);
        }
    }
}
