<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientProject;
use App\Models\MasterBillingRate;
use App\Models\ProjectDatabaseTable;
use App\Models\ProjectMenu;
use App\Models\ProjectRole;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProjectScopeController extends Controller
{
    /**
     * Add a new role to the project.
     */
    public function addRole(Request $request, int $projectId): JsonResponse
    {
        $project = ClientProject::findOrFail($projectId);
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'role_name' => ['required', 'string', 'max:100'],
        ]);

        $maxSort = (int) $project->roles()->max('sort_order');

        $role = DB::transaction(function () use ($project, $validated, $maxSort) {
            return ProjectRole::create([
                'project_id' => $project->id,
                'role_name' => trim($validated['role_name']),
                'sort_order' => $maxSort + 1,
            ]);
        });

        return response()->json([
            'message' => "Role '{$role->role_name}' berhasil ditambahkan ke proyek.",
            'data' => $role->load('menus'),
        ], 201);
    }

    /**
     * Delete a role and its associated menus from the project.
     */
    public function deleteRole(int $roleId): JsonResponse
    {
        $role = ProjectRole::with('project')->findOrFail($roleId);
        $project = $role->project;
        Gate::authorize('update', $project);

        DB::transaction(function () use ($role, $project) {
            $role->delete();
            $project->recalculateGrandTotal();
        });

        return response()->json([
            'message' => 'Role berhasil dihapus.',
        ], 200);
    }

    /**
     * Add a new menu/feature to the specified role with PIC and 10 feature capabilities.
     */
    public function addMenu(Request $request, int $roleId): JsonResponse
    {
        $role = ProjectRole::with('project')->findOrFail($roleId);
        $project = $role->project;
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'menu_name' => ['required', 'string', 'max:255'],
            'menu_desc' => ['nullable', 'string'],
            'module_type' => ['nullable', 'string', 'max:100'],
            'pic' => ['nullable', 'string', 'max:255'],
            'pic_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'price_note' => ['nullable', 'string', 'max:255'],
            'is_done' => ['boolean'],
            'completed_date' => ['nullable', 'date'],
            'has_create' => ['boolean'],
            'has_read' => ['boolean'],
            'has_update' => ['boolean'],
            'has_delete' => ['boolean'],
            'has_grafik' => ['boolean'],
            'has_box_dashboard' => ['boolean'],
            'has_import' => ['boolean'],
            'has_export' => ['boolean'],
            'has_filter' => ['boolean'],
            'has_print' => ['boolean'],
            'has_notification' => ['boolean'],
            'has_custom_logic' => ['boolean'],
        ]);

        $maxSort = (int) $role->menus()->max('sort_order');
        $defaultMenuPrice = $project->project_type === 'maintenance'
            ? MasterBillingRate::getRatePrice('maintenance_menu_price', 150000.0)
            : MasterBillingRate::getRatePrice('menu_price', 750000.0);

        $picUserId = isset($validated['pic_user_id']) && $validated['pic_user_id'] ? (int) $validated['pic_user_id'] : null;
        $picName = $validated['pic'] ?? null;
        if ($picUserId && !$picName) {
            $picName = User::find($picUserId)?->name;
        } elseif (!$picUserId && $picName) {
            $picUserId = User::where('name', trim($picName))->first()?->id;
        }
        if (!$picName && !$picUserId) {
            $picName = $project->lead_pic ?? 'Muhammad Iqbal Murtadho';
            $picUserId = $project->lead_pic_id;
        }

        $menu = DB::transaction(function () use ($role, $project, $validated, $maxSort, $defaultMenuPrice, $picName, $picUserId) {
            $m = ProjectMenu::create([
                'project_role_id' => $role->id,
                'menu_name' => trim($validated['menu_name']),
                'menu_desc' => $validated['menu_desc'] ?? null,
                'module_type' => $validated['module_type'] ?? null,
                'pic' => $picName,
                'pic_user_id' => $picUserId,
                'price' => isset($validated['price']) ? (float) $validated['price'] : $defaultMenuPrice,
                'price_note' => $validated['price_note'] ?? null,
                'is_done' => (bool) ($validated['is_done'] ?? false),
                'completed_date' => $validated['completed_date'] ?? null,
                'has_create' => $validated['has_create'] ?? true,
                'has_read' => $validated['has_read'] ?? true,
                'has_update' => $validated['has_update'] ?? true,
                'has_delete' => $validated['has_delete'] ?? true,
                'has_grafik' => $validated['has_grafik'] ?? false,
                'has_box_dashboard' => $validated['has_box_dashboard'] ?? false,
                'has_import' => $validated['has_import'] ?? false,
                'has_export' => $validated['has_export'] ?? false,
                'has_filter' => $validated['has_filter'] ?? true,
                'has_print' => $validated['has_print'] ?? false,
                'has_notification' => $validated['has_notification'] ?? false,
                'has_custom_logic' => $validated['has_custom_logic'] ?? false,
                'sort_order' => $maxSort + 1,
            ]);

            $project->recalculateGrandTotal();

            return $m;
        });

        return response()->json([
            'message' => "Menu '{$menu->menu_name}' berhasil ditambahkan ke role {$role->role_name}.",
            'data' => $menu,
        ], 201);
    }

    /**
     * Update an existing menu (PIC, 10 feature capabilities, price, desc, etc.).
     */
    public function updateMenu(Request $request, int $menuId): JsonResponse
    {
        $menu = ProjectMenu::with('role.project')->findOrFail($menuId);
        $project = $menu->role->project;
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'menu_name' => ['sometimes', 'required', 'string', 'max:255'],
            'menu_desc' => ['nullable', 'string'],
            'module_type' => ['nullable', 'string', 'max:100'],
            'pic' => ['nullable', 'string', 'max:255'],
            'pic_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'price_note' => ['nullable', 'string', 'max:255'],
            'is_done' => ['boolean'],
            'completed_date' => ['nullable', 'date'],
            'has_create' => ['boolean'],
            'has_read' => ['boolean'],
            'has_update' => ['boolean'],
            'has_delete' => ['boolean'],
            'has_grafik' => ['boolean'],
            'has_box_dashboard' => ['boolean'],
            'has_import' => ['boolean'],
            'has_export' => ['boolean'],
            'has_filter' => ['boolean'],
            'has_print' => ['boolean'],
            'has_notification' => ['boolean'],
            'has_custom_logic' => ['boolean'],
        ]);

        if (array_key_exists('pic_user_id', $validated)) {
            $validated['pic_user_id'] = $validated['pic_user_id'] ? (int) $validated['pic_user_id'] : null;
            if ($validated['pic_user_id']) {
                $u = User::find($validated['pic_user_id']);
                if ($u) {
                    $validated['pic'] = $u->name;
                }
            }
        } elseif (array_key_exists('pic', $validated) && !empty($validated['pic'])) {
            $matchedUser = User::where('name', trim($validated['pic']))->first();
            if ($matchedUser) {
                $validated['pic_user_id'] = $matchedUser->id;
            }
        }

        $updatedMenu = DB::transaction(function () use ($menu, $project, $validated) {
            $menu->update($validated);
            $project->recalculateGrandTotal();
            return $menu->fresh();
        });

        return response()->json([
            'message' => "Menu '{$updatedMenu->menu_name}' berhasil diperbarui.",
            'data' => $updatedMenu,
        ], 200);
    }

    /**
     * Toggle menu completion status with optional date and technician PIC.
     */
    public function toggleMenuDone(Request $request, int $menuId): JsonResponse
    {
        $menu = ProjectMenu::with('role.project')->findOrFail($menuId);
        $project = $menu->role->project;
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'is_done' => ['required', 'boolean'],
            'completed_date' => ['nullable', 'date'],
            'pic' => ['nullable', 'string', 'max:255'],
            'pic_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (array_key_exists('pic_user_id', $validated)) {
            $validated['pic_user_id'] = $validated['pic_user_id'] ? (int) $validated['pic_user_id'] : null;
            if ($validated['pic_user_id']) {
                $u = User::find($validated['pic_user_id']);
                if ($u) {
                    $validated['pic'] = $u->name;
                }
            }
        } elseif (array_key_exists('pic', $validated) && !empty($validated['pic'])) {
            $matchedUser = User::where('name', trim($validated['pic']))->first();
            if ($matchedUser) {
                $validated['pic_user_id'] = $matchedUser->id;
            }
        }

        $menu = DB::transaction(function () use ($menu, $validated) {
            $isDone = (bool) $validated['is_done'];
            $menu->is_done = $isDone;
            $menu->completed_date = $isDone ? ($validated['completed_date'] ?? date('Y-m-d')) : null;
            if (isset($validated['pic'])) {
                $menu->pic = $validated['pic'];
            }
            if (array_key_exists('pic_user_id', $validated)) {
                $menu->pic_user_id = $validated['pic_user_id'];
            }
            $menu->save();
            return $menu->fresh();
        });

        return response()->json([
            'message' => "Status menu '{$menu->menu_name}' berhasil diubah.",
            'data' => $menu,
        ], 200);
    }

    /**
     * Delete a menu.
     */
    public function deleteMenu(int $menuId): JsonResponse
    {
        $menu = ProjectMenu::with('role.project')->findOrFail($menuId);
        $project = $menu->role->project;
        Gate::authorize('update', $project);

        DB::transaction(function () use ($menu, $project) {
            $menu->delete();
            $project->recalculateGrandTotal();
        });

        return response()->json([
            'message' => 'Menu berhasil dihapus.',
        ], 200);
    }

    /**
     * Add a database table to the project schema.
     */
    public function addDatabaseTable(Request $request, int $projectId): JsonResponse
    {
        $project = ClientProject::findOrFail($projectId);
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'table_name' => ['required', 'string', 'max:100'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $defaultTablePrice = MasterBillingRate::getRatePrice('database_table_price', 100000.0);

        $table = DB::transaction(function () use ($project, $validated, $defaultTablePrice) {
            $t = ProjectDatabaseTable::create([
                'project_id' => $project->id,
                'table_name' => trim($validated['table_name']),
                'price' => isset($validated['price']) ? (float) $validated['price'] : $defaultTablePrice,
            ]);

            $project->recalculateGrandTotal();
            return $t;
        });

        return response()->json([
            'message' => "Tabel '{$table->table_name}' berhasil ditambahkan ke skema basis data.",
            'data' => $table,
        ], 201);
    }

    /**
     * Toggle database table completion status.
     */
    public function toggleTableDone(Request $request, int $tableId): JsonResponse
    {
        $table = ProjectDatabaseTable::with('project')->findOrFail($tableId);
        Gate::authorize('update', $table->project);

        $validated = $request->validate([
            'is_done' => ['required', 'boolean'],
        ]);

        $table->is_done = (bool) $validated['is_done'];
        $table->save();

        return response()->json([
            'message' => "Status tabel '{$table->table_name}' berhasil diperbarui.",
            'data' => $table,
        ], 200);
    }

    /**
     * Delete a database table.
     */
    public function deleteDatabaseTable(int $tableId): JsonResponse
    {
        $table = ProjectDatabaseTable::with('project')->findOrFail($tableId);
        $project = $table->project;
        Gate::authorize('update', $project);

        DB::transaction(function () use ($table, $project) {
            $table->delete();
            $project->recalculateGrandTotal();
        });

        return response()->json([
            'message' => 'Tabel database berhasil dihapus.',
        ], 200);
    }
}
