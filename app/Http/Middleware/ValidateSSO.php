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

            // 2. Sincronizar con la Madre (JIT - Just In Time)
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
            \Illuminate\Support\Facades\Log::info("SSO Mother App Data", ['data' => $userData]);
            
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
                    $userId = $userData['id'] ?? $decoded->sub;
                    
                    \Illuminate\Support\Facades\Log::info("SSO Mirroring Attempt", [
                        'target_id' => $userId,
                        'roles_to_save' => $userData['roles'] ?? []
                    ]);

                    $user = \App\Models\User::find($userId);
                    
                    if (!$user) {
                        $user = new \App\Models\User();
                        $user->id = $userId;
                    }

                    $user->username = $userData['username'] ?? $user->username;
                    $user->name = $userData['name'] ?? $user->name;
                    $user->avatar = ($userData['avatar'] ?? $userData['foto'] ?? $user->avatar);
                    $user->puesto = (isset($userData['puesto']['name']) ? $userData['puesto']['name'] : (is_string($userData['puesto'] ?? null) ? $userData['puesto'] : $user->puesto));
                    $user->roles_list = $userData['roles'] ?? [];
                    $user->permisos_list = $userData['permissions'] ?? $userData['permisos'] ?? [];
                    
                    $user->save();
                    
                    \Illuminate\Support\Facades\Log::info("SSO Mirroring Saved", [
                        'saved_id' => $user->id,
                        'saved_roles' => $user->roles_list
                    ]);

                    Auth::setUser($user);
                } catch (\Exception $dbEx) {
                \Illuminate\Support\Facades\Log::error("SSO Mirroring Database Error", [
                    'error' => $dbEx->getMessage(),
                    'user_id' => $userData['id'] ?? 'unknown'
                ]);
                throw $dbEx;
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