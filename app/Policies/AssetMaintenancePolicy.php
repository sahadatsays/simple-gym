<?php

namespace App\Policies;

use App\Models\AssetMaintenance;
use App\Models\User;

class AssetMaintenancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('asset-maintenances.view');
    }

    public function view(User $user, AssetMaintenance $assetMaintenance): bool
    {
        return $user->can('asset-maintenances.view');
    }

    public function create(User $user): bool
    {
        return $user->can('asset-maintenances.create');
    }

    public function update(User $user, AssetMaintenance $assetMaintenance): bool
    {
        return $user->can('asset-maintenances.edit');
    }

    public function delete(User $user, AssetMaintenance $assetMaintenance): bool
    {
        return $user->can('asset-maintenances.delete');
    }
}
