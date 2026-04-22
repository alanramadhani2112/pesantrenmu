<?php

namespace App\Services\Sso;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserService
{
    protected static string $token = '';
    
    protected static function getCredentials(string $token): mixed
    {
        self::$token = $token;

        $req = \Illuminate\Support\Facades\Http::withHeaders([
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
                'role_id' => $role_id
            ]);
        } else {
            $user->update([
                'email' => $user_data['email'],
                'name' => $user_data['name'],
                'role_id' => $role_id
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
