<?php

it('serves the page that mounts the Vue app', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('id="app"', escape: false);
});
