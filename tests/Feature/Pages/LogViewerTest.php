<?php

use function Pest\Laravel\get;

describe('Log Viewer Page', function () {
    beforeEach(function () {
        $this->routePath = config('log-viewer.route_path');
        $this->validCredentials = base64_encode(config('very_basic_auth.user').':'.config('very_basic_auth.password'));
    });

    it('denies access to the log viewer without authentication', fn () => get($this->routePath)->assertUnauthorized()
    );

    it('denies access with invalid authentication credentials', fn () => $this->withHeaders(['Authorization' => 'Basic '.base64_encode('invalid:credentials')])
        ->get($this->routePath)
        ->assertUnauthorized()
    );

    it('grants access to the log viewer with valid authentication credentials', fn () => $this->withHeaders(['Authorization' => 'Basic '.$this->validCredentials])
        ->get($this->routePath)
        ->assertOk()
    );
})->skip('temporarily to run it on CI');

