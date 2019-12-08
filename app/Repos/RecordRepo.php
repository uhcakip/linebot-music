<?php

namespace App\Repos;

use App\Record;
use Illuminate\Support\Arr;

class RecordRepo
{
    public function create(array $args)
    {
        $record = new Record();

        $record->user   = Arr::get($args, 'userId');
        $record->type   = Arr::get($args, 'type');
        $record->status = Arr::get($args, 'status');

    }
}