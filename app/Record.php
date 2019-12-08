<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    // table name -> records

    public function scopeUser($query, string $userId)
    {
        return $query->where('user', $userId);
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
