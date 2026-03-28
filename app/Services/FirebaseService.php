<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService
{
    private $messaging;

    public function __construct()
    {
        try {
            $this->messaging = (new Factory())
                ->withServiceAccount(storage_path('productos-5138d-firebase-adminsdk-fbsvc-cd8fc12ba4.json'))
                ->createMessaging();
        } catch (\Exception $e) {
            \Log::error('Error inicializando Firebase: ' . $e->getMessage());
        }
    }

    /**
     * Enviar un mensaje push a un dispositivo específico
     * 
     * @param string $deviceToken Token FCM del dispositivo
     * @param string $title Título del mensaje
     * @param string $body Cuerpo del mensaje
     * @param array $data Datos adicionales (opcional)
     * @return bool
     */
    public function sendToDevice(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        try {
            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $result = $this->messaging->send($message);
            
            \Log::info('Mensaje FCM enviado correctamente', [
                'deviceToken' => substr($deviceToken, 0, 20) . '...',
                'messageId' => $result
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Error enviando mensaje FCM: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar un mensaje push a múltiples dispositivos
     * 
     * @param array $deviceTokens Array de tokens FCM
     * @param string $title Título del mensaje
     * @param string $body Cuerpo del mensaje
     * @param array $data Datos adicionales (opcional)
     * @return array
     */
    public function sendToMultiple(array $deviceTokens, string $title, string $body, array $data = []): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($deviceTokens as $token) {
            if ($this->sendToDevice($token, $title, $body, $data)) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $token;
            }
        }

        return $results;
    }

    /**
     * Enviar un mensaje a un tema (topic)
     * Útil para broadcasts a múltiples usuarios
     * 
     * @param string $topic Nombre del tema
     * @param string $title Título del mensaje
     * @param string $body Cuerpo del mensaje
     * @param array $data Datos adicionales (opcional)
     * @return bool
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        try {
            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $result = $this->messaging->send($message);
            
            \Log::info('Mensaje FCM enviado al tema', [
                'topic' => $topic,
                'messageId' => $result
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Error enviando mensaje a tema: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Suscribir un dispositivo a un tema
     * 
     * @param string $topic Nombre del tema
     * @param array $deviceTokens Array de tokens FCM
     * @return bool
     */
    public function subscribeToTopic(string $topic, array $deviceTokens): bool
    {
        try {
            $this->messaging->subscribeToTopic($topic, $deviceTokens);
            \Log::info('Dispositivos suscritos al tema', [
                'topic' => $topic,
                'count' => count($deviceTokens)
            ]);
            return true;
        } catch (\Exception $e) {
            \Log::error('Error suscribiendo a tema: ' . $e->getMessage());
            return false;
        }
    }
}
