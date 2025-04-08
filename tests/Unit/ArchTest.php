<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

arch()->preset()->php();
arch()->preset()->security()->ignoring(['md5', 'sha1']);

arch()
    ->expect('App')
    ->not->toUse(['die', 'dd', 'dump', 'var_dump']);

arch('globals')
    ->expect(['dd', 'dump', 'var_dump'])
    ->not->toBeUsed();

arch()
    ->expect('App\Models')
    ->toBeClasses()
    ->toExtend(Model::class);

arch('app')
    ->expect('App\Enums')
    ->toBeEnums()
    ->and('App\Actions\Pages')
    ->toHaveSuffix('Page')
    ->and('App\Actions\Pages\Profile')
    ->toHaveSuffix('Page');
