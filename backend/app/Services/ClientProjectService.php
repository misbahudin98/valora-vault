<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\ClientProject;
use App\Models\MasterBillingRate;
use App\Models\ProjectRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ClientProjectService
{
    /**
     * Create a new project and optionally create a new client atomically.
     *
     * @param array<string, mixed> $data
     */
    public function createProject(array $data): ClientProject
    {
        return DB::transaction(function () use ($data): ClientProject {
            $mode = $data['mode'] ?? 'new_client';

            if ($mode === 'existing_client') {
                $client = Client::findOrFail($data['client_id']);
            } else {
                $client = Client::create([
                    'name' => $data['name'],
                    'contact_person' => $data['contact_person'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'address' => $data['address'] ?? null,
                    'industry' => $data['industry'] ?? null,
                ]);
            }

            $landingPrice = isset($data['landing_price']) ? (float) $data['landing_price'] : MasterBillingRate::getRatePrice('landing', 2500000.0);
            $loginPrice = isset($data['login_price']) ? (float) $data['login_price'] : MasterBillingRate::getRatePrice('login', 1000000.0);
            $registerPrice = isset($data['register_price']) ? (float) $data['register_price'] : MasterBillingRate::getRatePrice('register', 1000000.0);

            $useLanding = (bool) ($data['use_landing'] ?? false);
            $useLogin = (bool) ($data['use_login'] ?? true);
            $useRegister = (bool) ($data['use_register'] ?? false);
            $useAppCore = (bool) ($data['use_app_core'] ?? true);

            $grandTotal = 0.0;
            if ($useLanding) {
                $grandTotal += $landingPrice;
            }
            if ($useLogin) {
                $grandTotal += $loginPrice;
            }
            if ($useRegister) {
                $grandTotal += $registerPrice;
            }

            $leadPicId = isset($data['lead_pic_id']) && $data['lead_pic_id'] ? (int) $data['lead_pic_id'] : null;
            $leadPicName = $data['lead_pic'] ?? null;

            if ($leadPicId && !$leadPicName) {
                $leadUser = User::find($leadPicId);
                if ($leadUser) {
                    $leadPicName = $leadUser->name;
                }
            } elseif (!$leadPicId && $leadPicName) {
                $matchedUser = User::where('name', trim($leadPicName))->first();
                if ($matchedUser) {
                    $leadPicId = $matchedUser->id;
                }
            }

            $project = ClientProject::create([
                'client_id' => $client->id,
                'project_name' => $data['project_name'] ?? $data['app_name'],
                'app_name' => $data['app_name'],
                'project_type' => $data['project_type'] ?? 'development',
                'lead_pic' => $leadPicName,
                'lead_pic_id' => $leadPicId,
                'github_url' => $data['github_url'] ?? null,
                'app_desc' => $data['app_desc'] ?? null,
                'use_landing' => $useLanding,
                'use_login' => $useLogin,
                'use_register' => $useRegister,
                'use_app_core' => $useAppCore,
                'landing_price' => $landingPrice,
                'login_price' => $loginPrice,
                'register_price' => $registerPrice,
                'landing_note' => $data['landing_note'] ?? null,
                'login_note' => $data['login_note'] ?? null,
                'register_note' => $data['register_note'] ?? null,
                'landing_capabilities' => $data['landing_capabilities'] ?? null,
                'login_capabilities' => $data['login_capabilities'] ?? null,
                'register_capabilities' => $data['register_capabilities'] ?? null,
                'status' => $data['status'] ?? 'Aktif',
                'grand_total' => $grandTotal,
                'paid_amount' => 0.0,
            ]);

            // Assign workers if provided
            if (isset($data['worker_ids']) && is_array($data['worker_ids'])) {
                $project->workers()->sync($data['worker_ids']);
            } elseif ($leadPicId) {
                $project->workers()->syncWithoutDetaching([$leadPicId => ['role_in_project' => 'Lead PIC']]);
            }

            $roles = $data['roles'] ?? [];
            if (!empty($roles) && is_array($roles)) {
                $sort = 0;
                foreach ($roles as $roleName) {
                    $cleanRole = trim((string) $roleName);
                    if ($cleanRole !== '') {
                        ProjectRole::create([
                            'project_id' => $project->id,
                            'role_name' => $cleanRole,
                            'sort_order' => $sort++,
                        ]);
                    }
                }
            }

            return $project->load(['client', 'roles', 'leadPicUser', 'workers']);
        });
    }

    /**
     * Update an existing project and its client details atomically.
     *
     * @param array<string, mixed> $data
     */
    public function updateProject(ClientProject $project, array $data): ClientProject
    {
        return DB::transaction(function () use ($project, $data): ClientProject {
            // Update client data if specified
            $clientUpdates = [];
            if (isset($data['name']) || isset($data['client_name'])) {
                $clientUpdates['name'] = $data['name'] ?? $data['client_name'];
            }
            if (isset($data['contact_person']) || isset($data['client_contact'])) {
                $clientUpdates['contact_person'] = $data['contact_person'] ?? $data['client_contact'];
            }
            if (isset($data['phone']) || isset($data['client_phone'])) {
                $clientUpdates['phone'] = $data['phone'] ?? $data['client_phone'];
            }
            if (isset($data['email']) || isset($data['client_email'])) {
                $clientUpdates['email'] = $data['email'] ?? $data['client_email'];
            }
            if (isset($data['address']) || isset($data['client_address'])) {
                $clientUpdates['address'] = $data['address'] ?? $data['client_address'];
            }
            if (isset($data['industry']) || isset($data['client_industry'])) {
                $clientUpdates['industry'] = $data['industry'] ?? $data['client_industry'];
            }
            if (!empty($clientUpdates)) {
                $project->client()->update($clientUpdates);
            }

            // Update project attributes
            $projectFillable = [
                'project_name', 'app_name', 'project_type', 'lead_pic',
                'github_url', 'app_desc', 'use_landing', 'use_login',
                'use_register', 'use_app_core', 'landing_price',
                'login_price', 'register_price', 'landing_note',
                'login_note', 'register_note',
                'landing_capabilities', 'login_capabilities', 'register_capabilities',
                'status', 'paid_amount',
            ];

            foreach ($projectFillable as $field) {
                if (array_key_exists($field, $data)) {
                    $project->{$field} = $data[$field];
                }
            }

            // Update lead PIC and workers
            if (array_key_exists('lead_pic_id', $data)) {
                $project->lead_pic_id = $data['lead_pic_id'] ? (int) $data['lead_pic_id'] : null;
                if ($project->lead_pic_id) {
                    $leadUser = User::find($project->lead_pic_id);
                    if ($leadUser) {
                        $project->lead_pic = $leadUser->name;
                    }
                }
            } elseif (array_key_exists('lead_pic', $data) && !empty($data['lead_pic'])) {
                $project->lead_pic = $data['lead_pic'];
                $matchedUser = User::where('name', trim($data['lead_pic']))->first();
                if ($matchedUser) {
                    $project->lead_pic_id = $matchedUser->id;
                }
            }

            if (isset($data['worker_ids']) && is_array($data['worker_ids'])) {
                $project->workers()->sync($data['worker_ids']);
            }

            // Recalculate components and menus grand total
            $grandTotal = 0.0;
            if ($project->use_landing) {
                $grandTotal += (float) $project->landing_price;
            }
            if ($project->use_login) {
                $grandTotal += (float) $project->login_price;
            }
            if ($project->use_register) {
                $grandTotal += (float) $project->register_price;
            }

            // Include menus and tables if present
            foreach ($project->roles as $role) {
                $grandTotal += (float) $role->menus()->sum('price');
            }
            $grandTotal += (float) $project->databaseTables()->sum('price');

            if ($grandTotal > 0 || isset($data['use_landing']) || isset($data['use_login']) || isset($data['use_register'])) {
                $project->grand_total = $grandTotal;
            }
            $project->save();

            // Update roles if explicitly provided
            if (isset($data['roles']) && is_array($data['roles'])) {
                $project->roles()->delete();
                $sort = 0;
                foreach ($data['roles'] as $roleName) {
                    $cleanRole = trim((string) $roleName);
                    if ($cleanRole !== '') {
                        ProjectRole::create([
                            'project_id' => $project->id,
                            'role_name' => $cleanRole,
                            'sort_order' => $sort++,
                        ]);
                    }
                }
            }

            return $project->load(['client', 'roles', 'leadPicUser', 'workers']);
        });
    }

    /**
     * Delete a project and clean up orphaned clients if necessary.
     */
    public function deleteProject(ClientProject $project): bool
    {
        return DB::transaction(function () use ($project): bool {
            $clientId = $project->client_id;

            // Detach pivot relations
            $project->workers()->detach();

            // Cascade delete roles and their menus
            foreach ($project->roles as $role) {
                $role->menus()->delete();
                $role->delete();
            }

            // Delete database tables
            $project->databaseTables()->delete();

            // Delete payments if any
            $project->payments()->delete();

            $deleted = (bool) $project->delete();

            // Check if client has no other projects remaining
            $remainingProjects = ClientProject::where('client_id', $clientId)->count();
            if ($remainingProjects === 0) {
                Client::where('id', $clientId)->delete();
            }

            return $deleted;
        });
    }
}
