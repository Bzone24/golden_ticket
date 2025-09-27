<?php

namespace App\Traits;

use App\Models\DrawDetail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait DrawDetailsTrait
{
    public function drawDetail(): BelongsTo
    {
        return $this->belongsTo(DrawDetail::class);
    }
}
