# Firebase Cloud Messaging (FCM) - Guía de Implementación

## 📋 Índice
1. [Configuración Inicial](#configuración-inicial)
2. [Cómo Funciona](#cómo-funciona)
3. [Uso en el Frontend](#uso-en-el-frontend)
4. [Uso en el Backend](#uso-en-el-backend)
5. [Pruebas](#pruebas)
6. [Troubleshooting](#troubleshooting)

---

## Configuración Inicial

### Credenciales almacenadas  
- **Clave Pública (VAPID)**: `BNl_WE6MA5t2GwDNx3lcYwJ0vo8LtVxTAnwEVI4PTDCv3qjxPKGHEz9joMNSTj1nyfGnu44kbMcj3FTDnj97Dr4`
- **Project ID**: `productos-5138d`
- **Sender ID**: `675160803547`
- **API Key**: `AIzaSyBdzET4RSfQZZeS4jmvtVclr-05slJ9Bks`

### Archivos creados

#### Frontend (Vue/Vuetify)
```
src/
├── config/firebase.ts              # Configuración de Firebase
├── components/InstallPWA.vue       # Componente mejorado con FCM
└── pages/FCMDashboard.vue          # Panel de control para enviar notificaciones

public/
└── firebase-messaging-sw.js        # Service Worker para mensajes en background
```

#### Backend (Laravel)
```
app/
├── Services/FirebaseService.php    # Servicio para enviar messages
├── Http/Controllers/FcmController.php  # Endpoints API
└── Models/FcmToken.php             # Modelo para guardar tokens

database/migrations/
└── 2026_03_25_000000_create_fcm_tokens_table.php  # Tabla de tokens
```

---

## Cómo Funciona

### Flujo de Notificaciones Push

```
1. USUARIO INSTALA LA APP
   ↓
2. SE LE SOLICITA PERMISO PARA NOTIFICACIONES
   ↓
3. USUARIO AUTORIZA (o rechaza)
   ↓
4. SE GENERA UN TOKEN FCM ÚNICO
   ↓
5. EL TOKEN SE GUARDA EN EL SERVIDOR (tabla fcm_tokens)
   ↓
6. SERVIDOR PUEDE ENVIAR MENSAJES A ESE TOKEN
   ↓
7. EL USUARIO RECIBE LA NOTIFICACIÓN (incluso offline)
```

---

## Uso en el Frontend

### 1. Instalar la PWA

Cuando el usuario accede a la app:
- Aparece un banner: "Instalar Control de Almacen PWA"
- Luego aparece otro: "Activa Notificaciones"

### 2. Solicitar Permisos Manualmente

```typescript
import { requestNotificationPermission } from '@/config/firebase'

// Solicitar permiso
const token = await requestNotificationPermission()
if (token) {
  console.log('Token obtenido:', token)
}
```

### 3. El Token se Guarda en el Servidor

Se envía automáticamente a **POST `/api/fcm-token`**:
```json
{
  "fcm_token": "eyJhbGc...",
  "device_name": "Mi iPhone"
}
```

### 4. Recibir NotificacionesAutomáticamente

#### En PRIMER PLANO (app abierta)
Manejado por el listener en `firebase.ts` - muestra una notificación personalizada

#### En SEGUNDO PLANO (app cerrada)
Manejado por el Service Worker en `firebase-messaging-sw.js`

---

## Uso en el Backend

### 1. Enviar una Notificación a UN Usuario

```php
use App\Services\FirebaseService;

$firebaseService = new FirebaseService();

$success = $firebaseService->sendToDevice(
    deviceToken: 'TOKEN_DEL_USUARIO',
    title: '¡Hola!',
    body: 'Este es un mensaje de prueba',
    data: ['url' => '/products/123', 'action' => 'view']
);
```

### 2. Enviar a Múltiples Usuarios

```php
$users = User::all();
$tokens = $users->flatMap(fn($u) => $u->fcmTokens->pluck('token'))->toArray();

$result = $firebaseService->sendToMultiple(
    deviceTokens: $tokens,
    title: 'Alerta General',
    body: 'Mensaje para todos',
    data: []
);

// Result:
// [
//   'success' => 5,
//   'failed' => 1,
//   'errors' => ['token_invalido']
// ]
```

### 3. Enviar a un Tema (Broadcast)

```php
// Suscribir usuarios a un tema
$firebaseService->subscribeToTopic('products', $deviceTokens);

// Enviar a todos en el tema
$firebaseService->sendToTopic(
    topic: 'products',
    title: 'Nuevo producto',
    body: 'Se agregó un nuevo producto',
    data: ['product_id' => '123']
);
```

---

## Pruebas

### Opción 1: Usar el Panel de Control FCM

1. Ve a `http://tuapp.com/fcm-dashboard` (ruta protegida)
2. Verás:
   - Tu token FCM registrado
   - Estado de notificaciones
   - Formulario para enviar notificación de prueba
   - Formulario para broadcast

### Opción 2: API Directa

#### Registrar un Token
```bash
curl -X POST http://localhost:8000/api/fcm-token \
  -H "Content-Type: application/json" \
  -d '{"fcm_token":"TOKEN_AQUI","device_name":"Mi Device"}'
```

#### Enviar Notificación de Prueba
```bash
curl -X POST http://localhost:8000/api/notifications/test \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Prueba",
    "body": "¿Recibes esto?"
  }'
```

#### Enviar Broadcast
```bash
curl -X POST http://localhost:8000/api/notifications/broadcast \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Mantenimiento",
    "body": "Sistema en mantenimiento",
    "topic": "all"
  }'
```

---

## Endpoints API

### POST `/api/fcm-token`
**Descripción**: Registrar o actualizar un token FCM  
**Autenticación**: No requerida  
**Body**:
```json
{
  "fcm_token": "string (requerido)",
  "device_name": "string (opcional)"
}
```

### GET `/api/fcm-tokens`
**Descripción**: Obtener tokens del usuario autenticado  
**Autenticación**: Requerida (Bearer Token)  
**Response**:
```json
{
  "success": true,
  "tokens": [
    {
      "id": 1,
      "token": "...",
      "device_name": "Mi iPhone",
      "last_used_at": "2026-03-25T10:30:00",
      "created_at": "2026-03-25T09:00:00"
    }
  ],
  "count": 1
}
```

### DELETE `/api/fcm-tokens/{tokenId}`
**Descripción**: Eliminar un token FCM específico  
**Autenticación**: Requerida  
**Response**: `{"success": true, "message": "Token eliminado correctamente"}`

### POST `/api/notifications/test`
**Descripción**: Enviar una notificación de prueba al usuario autenticado  
**Autenticación**: Requerida  
**Body**:
```json
{
  "title": "string (requerido)",
  "body": "string (requerido)",
  "user_id": "integer (opcional)"
}
```

### POST `/api/notifications/broadcast`
**Descripción**: Enviar una notificación a todos (requiere admin)  
**Autenticación**: Requerida + Admin  
**Body**:
```json
{
  "title": "string (requerido)",
  "body": "string (requerido)",
  "topic": "string (opcional, default: 'all')"
}
```

---

## Logs

Todos los eventos se registran en `storage/logs/laravel.log`:

```
[2026-03-25 10:30:00] local.INFO: Mensaje FCM enviado correctamente {"deviceToken":"eyJ...","messageId":"0:1234567890:sendMessageId"}
[2026-03-25 10:31:00] local.INFO: Token FCM guardado en el servidor {"user_id":1}
```

---

## Troubleshooting

### ❌ "No tienes tokens FCM registrados"
**Solución**: La app debe estar instalada y el usuario debe autorizar notificaciones

### ❌ "Error al obtener permiso de notificación"
**Posibles causas**:
- El navegador no soporta notificaciones
- El usuario rechazó el permiso
- El Device está en modo "no molestar"

**Solución**: Verificar en DevTools → Console si hay errores de FCM

### ❌ "Service Worker no registrado"
**Solución**: Verificar que `public/firebase-messaging-sw.js` exista

### ✅ Cómo Ver las Notificaciones en Dev
```javascript
// En la consola del navegador
firebase.messaging().onMessage(msg => console.log(msg))
```

### ✅ Resetear Permisos (Chrome)
1. Click en el icono del sitio (izquierda de la barra URL)
2. Configuración → Notificaciones
3. Cambiar a "Permitir" o "Bloquear"

---

## Estructura de Datos

### Tabla `fcm_tokens`
```sql
id                BIGINT PRIMARY KEY
user_id           BIGINT (nullable, FK → users)
token             TEXT UNIQUE
device_name       VARCHAR(255)
last_used_at      TIMESTAMP
created_at        TIMESTAMP
updated_at        TIMESTAMP
```

---

## Próximos Pasos (Opcional)

1. **Segmentación avanzada**: Enviar a subgrupos de usuarios
2. **Analytics**: Registrar cuándo se leen las notificaciones
3. **Plantillas**: Crear plantillas de notificaciones reutilizables
4. **Scheduling**: Programar notificaciones para el futuro
5. **Rate Limiting**: Evitar spam de notificaciones

---

## Más Información

- [Firebase Cloud Messaging Docs](https://firebase.google.com/docs/cloud-messaging)
- [Firebase VAPID Keys](https://firebase.google.com/docs/cloud-messaging/js/client)
- [Laravel Firebase Package](https://github.com/kreait/firebase-php)

---

**Última Actualización**: 25 de Marzo, 2026
