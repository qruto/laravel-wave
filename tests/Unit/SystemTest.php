<?php

it('successfully caches routes', function () {
    $this->artisan('route:cache')->assertExitCode(0);
});
