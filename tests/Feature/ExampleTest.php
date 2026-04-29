<?php

it('returns a successful response from the health check endpoint', function () {
    $response = $this->get('/up');

    $response->assertOk();
});
