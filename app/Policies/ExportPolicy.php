<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Export;
use App\Models\User;

class ExportPolicy
{
    public function view(User $user, Export $export): bool
    {
        return $user->company_id === $export->company_id;
    }
}
