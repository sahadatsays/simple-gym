<?php

namespace App\Policies;

use App\Models\AssetDisposal;
use App\Models\User;

class AssetDisposalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('asset-disposals.view');
    }

    public function view(User $user, AssetDisposal $assetDisposal): bool
    {
        return $user->can('asset-disposals.view');
    }

    public function create(User $user): bool
    {
        return $user->can('asset-disposals.create');
    }
}
