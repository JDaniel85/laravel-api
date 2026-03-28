<?php

namespace App\Http\Controllers;

use App\Models\FcmToken;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FcmController extends Controller
{
    private $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Guardar o actualizar token FCM del usuario
     * POST /api/fcm-token
     */
    public function storeToken(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'fcm_token' => 'required|string',
                'device_name' => 'nullable|string|max:255'
            ]);

            $user = $request->user();
            
            // Buscar si el token ya existe
            $fcmToken = FcmToken::where('token', $validated['fcm_token'])->first();

            if ($fcmToken) {
                // Actualizar token existente
                $fcmToken->update([
                    'user_id' => $user?->id,
                    'device_name' => $validated['device_name'] ?? $fcmToken->device_name,
                    'last_used_at' => now()
                ]);
                $message = 'Token FCM actualizado correctamente';
            } else {
                // Crear nuevo token
                FcmToken::create([
                    'user_id' => $user?->id,
                    'token' => $validated['fcm_token'],
                    'device_name' => $validated['device_name'],
                    'last_used_at' => now()
                ]);
                $message = 'Token FCM guardado correctamente';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'user_id' => $user?->id
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error guardando token FCM: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar un mensaje de prueba a un usuario
     * POST /api/notifications/test
     */
    public function sendTestNotification(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'nullable|exists:users,id',
                'title' => 'required|string|max:255',
                'body' => 'required|string|max:500'
            ]);

            // Si no especifica user_id, usar el usuario autenticado
            $userId = $validated['user_id'] ?? $request->user()?->id;

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se requiere autenticación o user_id'
                ], 401);
            }

            // Obtener todos los tokens del usuario
            $tokens = FcmToken::where('user_id', $userId)->pluck('token')->toArray();

            if (empty($tokens)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario no tiene tokens FCM registrados'
                ], 404);
            }

            // Enviar el mensaje
            $result = $this->firebaseService->sendToMultiple(
                $tokens,
                $validated['title'],
                $validated['body'],
                ['timestamp' => now()->toIso8601String()]
            );

            return response()->json([
                'success' => true,
                'message' => 'Notificación enviada',
                'result' => $result
            ]);
        } catch (\Exception $e) {
            \Log::error('Error enviando notificación: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la notificación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar un mensaje a todos los usuarios
     * POST /api/notifications/broadcast
     */
    public function broadcastNotification(Request $request): JsonResponse
    {
        try {
            // Requiere admin o permiso especial
            if (!$request->user() || !$this->isAdmin($request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para enviar broadcasts'
                ], 403);
            }

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'body' => 'required|string|max:500',
                'topic' => 'nullable|string|max:255'
            ]);

            $topic = $validated['topic'] ?? 'all';

            // Enviar a todos usando un tema
            $success = $this->firebaseService->sendToTopic(
                $topic,
                $validated['title'],
                $validated['body'],
                ['timestamp' => now()->toIso8601String()]
            );

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Broadcast enviado correctamente' : 'Error al enviar broadcast',
                'topic' => $topic
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en broadcast: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el broadcast',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener los tokens FCM de un usuario
     * GET /api/fcm-tokens
     */
    public function getUserTokens(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }

            $tokens = FcmToken::where('user_id', $user->id)
                ->select('id', 'token', 'device_name', 'last_used_at', 'created_at')
                ->get();

            return response()->json([
                'success' => true,
                'tokens' => $tokens,
                'count' => $tokens->count()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error obteniendo tokens: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tokens'
            ], 500);
        }
    }

    /**
     * Eliminar un token FCM
     * DELETE /api/fcm-tokens/{tokenId}
     */
    public function deleteToken(Request $request, $tokenId): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }

            $token = FcmToken::where('id', $tokenId)
                ->where('user_id', $user->id)
                ->first();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token no encontrado'
                ], 404);
            }

            $token->delete();

            return response()->json([
                'success' => true,
                'message' => 'Token eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error eliminando token: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar token'
            ], 500);
        }
    }

    /**
     * Verificar si el usuario es admin (puedes adaptar esto a tu lógica)
     */
    private function isAdmin(User $user): bool
    {
        // Adapta esto según tu lógica de roles/permisos
        return $user->is_admin ?? false;
    }
}
