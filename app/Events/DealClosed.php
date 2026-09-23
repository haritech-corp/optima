<?php

namespace App\Events;

use App\Models\Deal;
use Illuminate\Foundation\Events\Dispatchable;

class DealClosed
{
    use Dispatchable;

    public function __construct(public readonly Deal $deal) {}
}