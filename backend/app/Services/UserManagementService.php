<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserManagementService
{
    /**
     * Create a new user atomically.
     *
     * @param array<string, mixed> $data
     * @param array<int, string> $roles
     */
    public function createUser(array $data, array $roles, bool $isActive = true): User
    {
        return DB::transaction(function () use ($data, $roles, $isActive) {
            return User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $roles,
                'is_active' => $isActive,
                'token_version' => 1,
                'jabatan' => $data['jabatan'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'github_url' => $data['github_url'] ?? null,
                'linkedin_url' => $data['linkedin_url'] ?? null,
                'skills' => $data['skills'] ?? null,
                'allowed_features' => $data['allowed_features'] ?? null,
            ]);
        });
    }

    /**
     * Update an existing user atomically.
     *
     * @param array<string, mixed> $data
     * @param array<int, string>|null $roles
     */
    public function updateUser(User $targetUser, array $data, ?array $roles = null, ?bool $isActive = null): User
    {
        return DB::transaction(function () use ($targetUser, $data, $roles, $isActive) {
            $targetUser->name = $data['name'];
            $targetUser->email = $data['email'];

            if (!empty($data['password'])) {
                $targetUser->password = Hash::make($data['password']);
                $targetUser->token_version = (int) $targetUser->token_version + 1;
            }

            if ($roles !== null) {
                $targetUser->role = $roles;
            }

            if ($isActive !== null) {
                if ($targetUser->is_active && !$isActive) {
                    $targetUser->token_version = (int) $targetUser->token_version + 1;
                }
                $targetUser->is_active = $isActive;
            }

            if (array_key_exists('jabatan', $data)) {
                $targetUser->jabatan = $data['jabatan'];
            }
            if (array_key_exists('phone', $data)) {
                $targetUser->phone = $data['phone'];
            }
            if (array_key_exists('address', $data)) {
                $targetUser->address = $data['address'];
            }
            if (array_key_exists('github_url', $data)) {
                $targetUser->github_url = $data['github_url'];
            }
            if (array_key_exists('linkedin_url', $data)) {
                $targetUser->linkedin_url = $data['linkedin_url'];
            }
            if (array_key_exists('skills', $data)) {
                $targetUser->skills = $data['skills'];
            }
            if (array_key_exists('allowed_features', $data)) {
                $targetUser->allowed_features = $data['allowed_features'];
            }

            $targetUser->save();

            return $targetUser;
        });
    }

    /**
     * Delete user and perform physical session & database cleanup.
     */
    public function deleteUser(User $targetUser): void
    {
        DB::transaction(function () use ($targetUser) {
            $devices = UserDevice::where('user_id', $targetUser->id)->get();
            foreach ($devices as $device) {
                if ($device->session_id) {
                    $sessionFile = storage_path("framework/sessions/{$device->session_id}");
                    if (file_exists($sessionFile)) {
                        @unlink($sessionFile);
                    }
                }
                $device->delete();
            }

            if (method_exists($targetUser, 'tokens')) {
                $targetUser->tokens()->delete();
            }

            $targetUser->delete();
        });
    }

    /**
     * Toggle active/suspended status with session invalidation.
     */
    public function toggleStatus(User $targetUser): bool
    {
        return DB::transaction(function () use ($targetUser) {
            $newStatus = !$targetUser->is_active;
            $targetUser->is_active = $newStatus;

            if (!$newStatus) {
                $targetUser->token_version = (int) $targetUser->token_version + 1;

                $devices = UserDevice::where('user_id', $targetUser->id)->get();
                foreach ($devices as $device) {
                    if ($device->session_id) {
                        $sessionFile = storage_path("framework/sessions/{$device->session_id}");
                        if (file_exists($sessionFile)) {
                            @unlink($sessionFile);
                        }
                    }
                    $device->delete();
                }

                if (method_exists($targetUser, 'tokens')) {
                    $targetUser->tokens()->delete();
                }
            }

            $targetUser->save();

            return $newStatus;
        });
    }

    /**
     * Invalidate all sessions and tokens for the target user immediately.
     */
    public function revokeSessions(User $targetUser): void
    {
        DB::transaction(function () use ($targetUser) {
            $targetUser->increment('token_version');

            $devices = UserDevice::where('user_id', $targetUser->id)->get();
            foreach ($devices as $device) {
                if ($device->session_id) {
                    $sessionFile = storage_path("framework/sessions/{$device->session_id}");
                    if (file_exists($sessionFile)) {
                        @unlink($sessionFile);
                    }
                }
                $device->delete();
            }

            if (method_exists($targetUser, 'tokens')) {
                $targetUser->tokens()->delete();
            }
        });
    }
}
