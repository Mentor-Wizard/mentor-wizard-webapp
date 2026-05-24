<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperCurrency
 */
#[UseFactory(CurrencyFactory::class)]
#[Fillable([
    'name',
    'slug',
    'symbol',
])]
class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;
}
