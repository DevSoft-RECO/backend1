<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\GenericUser; 

class ValidateSSO
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token requerido'], 401);
        }

        try {
            $publicKeyPath = storage_path('oauth-public.key');
            
            if (!file_exists($publicKeyPath)) {
                throw new \Exception("Falta llave pública en servidor hijo");
            }
            
            $publicKey = file_get_contents($publicKeyPath);
            JWT::$leeway = 60; // Margen de error para relojes desincronizados

            // 1. Decodificar Token con RS256 para validación básica e ID
            $decoded = JWT::decode($token, new Key($publicKey, 'RS256'));

            // Llave de caché/sincronización condicional basada en el parámetro 'sync'
            $shouldSync = $request->query('sync') === 'true';
            $userId = $decoded->sub;
            $userExists = \App\Models\User::where('id', $userId)->exists();

            if ($shouldSync || !$userExists) {
                // 2. Sincronización con la Madre (Solo en Login o Usuario Nuevo)
                $motherUrl = config('services.app_madre.url', 'http://localhost:8000');
                $response = \Illuminate\Support\Facades\Http::withToken($token)
                    ->acceptJson()
                    ->get("{$motherUrl}/api/me");

                if ($response->failed()) {
                    \Illuminate\Support\Facades\Log::error("SSO Validation Failed", [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'token_preview' => substr($token, 0, 10) . '...'
                    ]);
                    throw new \Exception("No se pudo validar la sesión con la Madre: " . $response->status());
                }

                $userData = $response->json();
                \Illuminate\Support\Facades\Log::info("SSO Mother App Data (JIT Sync)", ['data' => $userData]);
                
                if (isset($userData['data'])) {
                    $userData = $userData['data']; // Desempaquetar si viene en un Resource
                }

                // 3. CRÍTICO: "Aplanar" Arrays de Objetos Spatie -> Strings puros
                if (isset($userData['roles']) && is_array($userData['roles'])) {
                    $userData['roles'] = array_map(function($r) { 
                        return is_array($r) ? ($r['name'] ?? $r) : (is_object($r) ? ($r->name ?? $r) : $r); 
                    }, $userData['roles']);
                }
                if (isset($userData['permisos']) && is_array($userData['permisos'])) {
                    $userData['permisos'] = array_map(function($p) { 
                        return is_array($p) ? ($p['name'] ?? $p) : (is_object($p) ? ($p->name ?? $p) : $p); 
                    }, $userData['permisos']);
                }
                if (isset($userData['permissions']) && is_array($userData['permissions'])) {
                     $userData['permissions'] = array_map(function($p) { 
                        return is_array($p) ? ($p['name'] ?? $p) : (is_object($p) ? ($p->name ?? $p) : $p); 
                    }, $userData['permissions']);
                }

                // 4. Sincronización por Espejo (Mirroring) en BD Local
                try {
                    $user = \App\Models\User::find($userId);
                    
                    $needsSave = !$user;

                    if (!$user) {
                        $user = new \App\Models\User();
                        $user->id = $userId;
                    }

                    // Extraer valores nuevos
                    $newUsername = $userData['username'] ?? null;
                    $newName = $userData['name'] ?? null;
                    $newAvatar = ($userData['avatar'] ?? $userData['foto'] ?? null);
                    $newPuesto = (isset($userData['puesto']['name']) ? $userData['puesto']['name'] : (is_string($userData['puesto'] ?? null) ? $userData['puesto'] : null));
                    $newRoles = $userData['roles'] ?? [];
                    $newPermisos = $userData['permissions'] ?? $userData['permisos'] ?? [];

                    // Validar cambios antes de persistir para evitar escrituras en disco innecesarias
                    if ($user->username !== $newUsername) {
                        $user->username = $newUsername;
                        $needsSave = true;
                    }
                    if ($user->name !== $newName) {
                        $user->name = $newName;
                        $needsSave = true;
                    }
                    if ($user->avatar !== $newAvatar) {
                        $user->avatar = $newAvatar;
                        $needsSave = true;
                    }
                    if ($user->puesto !== $newPuesto) {
                        $user->puesto = $newPuesto;
                        $needsSave = true;
                    }
                    if ($user->roles_list !== $newRoles) {
                        $user->roles_list = $newRoles;
                        $needsSave = true;
                    }
                    if ($user->permisos_list !== $newPermisos) {
                        $user->permisos_list = $newPermisos;
                        $needsSave = true;
                    }

                    if ($needsSave) {
                        \Illuminate\Support\Facades\Log::info("SSO Mirroring Saving User", [
                            'target_id' => $userId,
                            'roles_to_save' => $newRoles
                        ]);
                        $user->save();
                        \Illuminate\Support\Facades\Log::info("SSO Mirroring Saved", [
                            'saved_id' => $user->id,
                            'saved_roles' => $user->roles_list
                        ]);
                    }

                    Auth::setUser($user);
                } catch (\Exception $dbEx) {
                    \Illuminate\Support\Facades\Log::error("SSO Mirroring Database Error", [
                        'error' => $dbEx->getMessage(),
                        'user_id' => $userId
                    ]);
                    throw $dbEx;
                }
            } else {
                // 5. Autenticación Local Pasiva (Rápida y Offline en Navegación Cotidiana)
                $user = \App\Models\User::find($userId);
                
                if (!$user) {
                    throw new \Exception("El usuario local no existe, se requiere sincronización inicial.");
                }

                Auth::setUser($user);
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("SSO Unauthorized Access", [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);
            return response()->json(['message' => 'Acceso Denegado: ' . $e->getMessage()], 401);
        }

        return $next($request);
    }
}