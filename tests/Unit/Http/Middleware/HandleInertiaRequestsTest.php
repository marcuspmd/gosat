<?php

declare(strict_types=1);

use App\Http\Middleware\HandleInertiaRequests;

describe('HandleInertiaRequests', function () {
    it('can be instantiated', function () {
        $middleware = new HandleInertiaRequests;

        expect($middleware)->toBeInstanceOf(HandleInertiaRequests::class);
    });
});
