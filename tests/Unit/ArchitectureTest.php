<?php

declare(strict_types=1);

arch('application code declares strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('database code declares strict types')
    ->expect('Database')
    ->toUseStrictTypes();

arch('no debugging calls are left behind')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();

arch('controllers are suffixed')
    ->expect('App\Http\Controllers')
    ->classes()
    ->toHaveSuffix('Controller');

arch('the scheduler package stays framework-free')
    ->expect('StudyLoop\Scheduler')
    ->not->toUse(['App', 'Illuminate', 'Laravel', 'Livewire', 'Carbon']);

arch('the scheduler package declares strict types')
    ->expect('StudyLoop\Scheduler')
    ->toUseStrictTypes();
