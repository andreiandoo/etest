<?php

test('the homepage is available', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('e-test.ro');
});

test('the dashboard requires authentication', function () {
    $this->get('/panou')
        ->assertRedirect('/autentificare');
});
