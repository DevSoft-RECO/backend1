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
            // Esto asegura que si el usuario fue bloqueado en la Madre, el hijo lo sepa de inmediato.
            $motherUrl = config('services.app_madre.url', 'http://localhost:8000');
            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->acceptJson()
                ->get("{$motherUrl}/api/me");

            if ($response->failed()) {
                throw new \Exception("No se pudo validar la sesión con la Madre: " . $response->status());
            }

            $userData = $response->json();
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

            // 4. Inyectar usuario en la sesión de Laravel (Memoria)
            // Creamos un GenericUser que expone sus atributos al ser convertido a JSON
            $user = new class([
                'id' => $userData['id'] ?? $decoded->sub,
                'name' => $userData['name'] ?? null,
                'email' => $userData['email'] ?? null,
                'avatar' => $userData['avatar'] ?? $userData['foto'] ?? null,
                'puesto' => $userData['puesto'] ?? $userData['position'] ?? null,
                'roles' => $userData['roles'] ?? [],
                'permisos' => $userData['permisos'] ?? [],
                'permissions' => $userData['permisos'] ?? [], // Fallback estandarizado
                'roles_list' => $userData['roles'] ?? [], // Fallback estandarizado
                'token_scopes' => $decoded->scopes ?? [],
            ]) extends GenericUser implements \JsonSerializable {
                public function jsonSerialize(): mixed {
                    return $this->attributes;
                }
            };


            Auth::setUser($user);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Acceso Denegado: ' . $e->getMessage()], 401);
        }

        return $next($request);
    }
}