<?php

declare(strict_types=1);

it('renders the landing page with a skip link and the synthetic data notice', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('Skip to content')
        ->assertSee('All course data is synthetic.');
});
