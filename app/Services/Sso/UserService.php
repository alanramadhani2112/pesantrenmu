<?php

namespace App\Services\Sso;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserService
{
    protected static string $token = '';
    
    protected static function getCredentials(string $token): mixed
    {
        self::$token = $token;

        $req = \Illuminate\Support\Facades\Http::timeout(config('sso.timeout', 10))
            ->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . self::$token,
            ])->get(config('sso.server_url') . 'api/user');


        if ($req->successful()) {
            return $req->json();
        }

        return null;
    }

    protected static function findOrCreate(?array $user_data): ?Model
    {
        if (empty($user_data)) {
            return null;
        }

        $user = \App\Models\User::with('profile_data')->where('email', $user_data['email'])->first();

        if (empty($user)) {
            $profile = Profile::with('user')->where('data->id', $user_data['id'])->first();
            $user = $profile?->user;
        }

        $role_id = match ((int) ($user_data['level'] ?? 0)) {
            1 => 1, // Admin
            2 => 3, // Pesantren
            3 => 2, // Asessor
            default => 3, // Default to Pesantren if unknown
        };

        if (empty($user)) {
            $user = \App\Models\User::create([
                'email' => $user_data['email'],
                'name' => $user_data['name'],
                'password' => \Illuminate\Support\Facades\Hash::make(Str::random()),
                'uuid' => str()->uuid(),
                'role_id' => $role_id,
                'sso_linked_at' => now(),
                'sso_sync_role' => true,
            ]);

            Log::info('sso.user_created', [
                'email' => $user_data['email'],
                'user_id' => $user->id,
                'role_id' => $role_id,
            ]);
        } else {
            $updateData = [
                'email' => $user_data['email'],
                'name' => $user_data['name'],
            ];

            // Only sync role if sso_sync_role is true
            if ($user->sso_sync_role) {
                $updateData['role_id'] = $role_id;
            }

            // Set sso_linked_at only on first SSO login
            if (is_null($user->sso_linked_at)) {
                $updateData['sso_linked_at'] = now();
            }

            $user->update($updateData);

            Log::info('sso.user_linked', [
                'email' => $user_data['email'],
                'user_id' => $user->id,
                'role_id' => $role_id,
                'role_synced' => $user->sso_sync_role,
            ]);
        }

        if ($user->profile_data) {
            $profile = $user->profile_data;
            
            $profile->update([
                'data' => $user_data,
                'access_token' => self::$token
            ]);
        }else {
            $user->profile_data()->create([
                'data' => $user_data,
                'access_token' => self::$token
            ]);
        }

        return $user;
    }

    public static function getUser(string $token): Model | null
    {
        $credentials = self::getCredentials($token);

        if (empty($credentials['id'])) return null;

        return self::findOrCreate($credentials);
    }
}
