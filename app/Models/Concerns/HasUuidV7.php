<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

trait HasUuidV7
{
    use HasUuids;

    /**
     * استخدام UUID v7 (sortable, timestamp-based) بدل v4 الافتراضي
     * عشان نتجنب index fragmentation في الـ primary key.
     */
    public function newUniqueId(): string
    {
        return (string) Str::uuid7();
    }
}
