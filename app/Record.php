<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    public function scopeUser($query, string $user)
    {
        return $query->where('user', $user);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
