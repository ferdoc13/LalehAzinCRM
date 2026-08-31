<?php

test('the home page redirects guests to customer login', function () {
    $this->get('/')
        ->assertRedirect('/login');
});
