<?php

namespace App\Repositories\Local;

use App\Repositories\Contracts\PermissionCatalogRepositoryInterface;
use App\Support\PermissionCatalog;

class StaticPermissionCatalogRepository implements PermissionCatalogRepositoryInterface
{
    public function all(): array
    {
        return PermissionCatalog::all();
    }
}