<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'group', 'type', 'value', 'is_public', 'description'];

    protected $casts = ['value' => 'array', 'is_public' => 'boolean'];

    public function getScalarValue(): mixed
    {
        $v = $this->value;

        return match ($this->type) {
            'string' => is_array($v) ? ($v['value'] ?? null) : $v,
            'number' => is_array($v) ? ($v['value'] ?? null) : $v,
            'boolean' => (bool) (is_array($v) ? ($v['value'] ?? false) : $v),
            default => $v,
        };
    }
}
