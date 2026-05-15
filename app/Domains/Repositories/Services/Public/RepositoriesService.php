<?php

declare(strict_types=1);

namespace App\Domains\Repositories\Services\Public;

use App\Domains\Repositories\Models\Repository;

class RepositoriesService
{
    public function find(int $id): ?Repository
    {
        return Repository::query()->find($id);
    }
}
