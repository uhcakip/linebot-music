<?php

namespace App\Repos;

use App\Record;
use Illuminate\Support\Arr;

class RecordRepo
{
    public function getRecords(array $args, bool $all = true)
    {
        $args += getDefalutColumns();
        $query = Record::query();

        if (($user   = Arr::get($args, 'user',   false)) !== false) $query->User($user);
        if (($type   = Arr::get($args, 'type',   false)) !== false) $query->Type($type);
        if (($status = Arr::get($args, 'status', false)) !== false) $query->Status($status);

        $query->orderBy($args['order'], $args['sort'])
              ->skip($args['skip'])
              ->take($args['take']);

        return $all ? $query->get() : $query;
    }

    public function create(array $args)
    {
        $record = new Record();

        $record->user = Arr::get($args, 'user');
        $record->type = Arr::get($args, 'type');
        // $record->status = Arr::get($args, 'status');

        return $record->save();
    }

    public function update(Record $record, array $args)
    {
        if (($type = Arr::get($args, 'type', false)) !== false) $record->type = $type;
        // if (($keyword = Arr::get($args, 'keyword', false)) !== false) $record->keyword = $keyword;
        // if (($status  = Arr::get($args, 'status',  false)) !== false) $record->status = $status;

        return $record->save();
    }
}
