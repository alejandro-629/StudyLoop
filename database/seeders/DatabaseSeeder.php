<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Synthetic demo data only.
     */
    public function run(): void
    {
        $this->call(DemoOrganizationsSeeder::class);
    }
}
