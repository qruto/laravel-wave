<?php

afterEach(fn () => $this->artisan('route:clear'));

it('successfully caches routes', function () {
    $this->artisan('route:cache')->assertExitCode(0);
});
