<?php

declare(strict_types=1);

it('health check returns ok', function (): void {
    $response = $this->getJson('/up');

    $response->assertOk();
});
