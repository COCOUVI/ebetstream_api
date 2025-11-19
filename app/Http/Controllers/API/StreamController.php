<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Stream;
use App\Models\StreamSession;
use App\Models\StreamChatMessage;
use App\Models\StreamFollower;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StreamController extends Controller
{
    /**
     * Liste tous les streams (live et offline)
     */
    public function index(Request $request)
    {
        $query = Stream::with(['user', 'sessions'])
            ->orderBy('is_live', 'desc')
            ->orderBy('viewer_count', 'desc');

        // Filtres
        if ($request->has('live_only') && $request->live_only) {
            $query->live();
        }

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->has('game')) {
            $query->byGame($request->game);
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $streams = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $streams
        ]);
    }

    /**
     * Créer un nouveau stream
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'game' => 'nullable|string|max:100',
            'thumbnail' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier si l'utilisateur a déjà un stream
        $existingStream = Stream::where('user_id', $user->id)->first();

        if ($existingStream) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà un stream. Utilisez la mise à jour pour le modifier.'
            ], 400);
        }

        $stream = Stream::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'game' => $request->game,
            'thumbnail' => $request->thumbnail,
            'rtmp_url' => 'rtmp://localhost:1935/live/' . Str::random(32),
            'hls_url' => 'http://localhost:8080/hls/' . Str::random(32) . '.m3u8',
            'is_live' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stream créé avec succès',
            'data' => $stream->load('user')
        ], 201);
    }

    /**
     * Afficher un stream spécifique
     */
    public function show($id)
    {
        $stream = Stream::with(['user', 'sessions', 'followers'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $stream
        ]);
    }

    /**
     * Mettre à jour un stream
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $stream = Stream::where('user_id', $user->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'game' => 'nullable|string|max:100',
            'thumbnail' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $stream->update($request->only([
            'title', 'description', 'category', 'game', 'thumbnail'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Stream mis à jour avec succès',
            'data' => $stream->load('user')
        ]);
    }

    /**
     * Démarrer un stream (go live)
     */
    public function start(Request $request, $id)
    {
        $user = $request->user();
        $stream = Stream::where('user_id', $user->id)->findOrFail($id);

        if ($stream->is_live) {
            return response()->json([
                'success' => false,
                'message' => 'Le stream est déjà en direct'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $stream->update([
                'is_live' => true,
                'started_at' => now(),
            ]);

            $session = StreamSession::create([
                'stream_id' => $stream->id,
                'status' => 'live',
                'started_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stream démarré avec succès',
                'data' => [
                    'stream' => $stream->load('user'),
                    'session' => $session
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du démarrage du stream: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Arrêter un stream
     */
    public function stop(Request $request, $id)
    {
        $user = $request->user();
        $stream = Stream::where('user_id', $user->id)->findOrFail($id);

        if (!$stream->is_live) {
            return response()->json([
                'success' => false,
                'message' => 'Le stream n\'est pas en direct'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $activeSession = $stream->sessions()
                ->where('status', 'live')
                ->latest()
                ->first();

            if ($activeSession) {
                $activeSession->update([
                    'status' => 'ended',
                    'ended_at' => now(),
                ]);
            }

            $stream->update([
                'is_live' => false,
                'ended_at' => now(),
                'viewer_count' => 0,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stream arrêté avec succès',
                'data' => $stream->load('user')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'arrêt du stream: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le stream key pour le streamer
     */
    public function getStreamKey(Request $request)
    {
        $user = $request->user();
        $stream = Stream::where('user_id', $user->id)->first();

        if (!$stream) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun stream trouvé. Créez d\'abord un stream.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'stream_id' => $stream->id,
                'stream_key' => $stream->stream_key,
                'rtmp_url' => $stream->rtmp_url,
                'hls_url' => $stream->hls_url,
            ]
        ]);
    }

    /**
     * Mettre à jour le nombre de viewers
     */
    public function updateViewers(Request $request, $id)
    {
        $stream = Stream::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'viewer_count' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $stream->update([
            'viewer_count' => $request->viewer_count
        ]);

        return response()->json([
            'success' => true,
            'data' => $stream
        ]);
    }

    /**
     * Suivre/Ne plus suivre un stream
     */
    public function toggleFollow(Request $request, $id)
    {
        $user = $request->user();
        $stream = Stream::findOrFail($id);

        $follower = StreamFollower::where('stream_id', $stream->id)
            ->where('user_id', $user->id)
            ->first();

        if ($follower) {
            $follower->delete();
            $stream->decrement('follower_count');
            $isFollowing = false;
        } else {
            StreamFollower::create([
                'stream_id' => $stream->id,
                'user_id' => $user->id,
            ]);
            $stream->increment('follower_count');
            $isFollowing = true;
        }

        return response()->json([
            'success' => true,
            'message' => $isFollowing ? 'Vous suivez maintenant ce stream' : 'Vous ne suivez plus ce stream',
            'data' => [
                'is_following' => $isFollowing,
                'follower_count' => $stream->fresh()->follower_count
            ]
        ]);
    }

    /**
     * Obtenir les messages du chat
     */
    public function getChatMessages(Request $request, $id)
    {
        $stream = Stream::findOrFail($id);

        $messages = StreamChatMessage::where('stream_id', $stream->id)
            ->notDeleted()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($request->get('limit', 50))
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * Envoyer un message dans le chat
     */
    public function sendChatMessage(Request $request, $id)
    {
        $user = $request->user();
        $stream = Stream::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:500',
            'reply_to' => 'nullable|exists:stream_chat_messages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $message = StreamChatMessage::create([
            'stream_id' => $stream->id,
            'user_id' => $user->id,
            'message' => $request->message,
            'reply_to' => $request->reply_to,
            'is_moderator' => false, // TODO: Vérifier les permissions
            'is_subscriber' => false, // TODO: Vérifier l'abonnement
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message envoyé',
            'data' => $message->load('user')
        ], 201);
    }

    /**
     * Supprimer un message du chat (modération)
     */
    public function deleteChatMessage(Request $request, $id, $messageId)
    {
        $user = $request->user();
        $stream = Stream::findOrFail($id);

        // Vérifier que l'utilisateur est le propriétaire du stream ou un modérateur
        if ($stream->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        $chatMessage = StreamChatMessage::where('stream_id', $stream->id)
            ->findOrFail($messageId);

        $chatMessage->update(['is_deleted' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Message supprimé'
        ]);
    }
}
