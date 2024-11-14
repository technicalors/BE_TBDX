<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Database\Eloquent\Model;

class QueryHelper
{
    public static function generateNewId(Model $model, string $prefix, int $pad = 3, string $field = 'id'): string | int
    {
        $latestRecord = $model->query()
            ->where($field, 'like', "$prefix%")
            ->orderBy($field, 'desc')
            ->first();

        if (!$latestRecord) {
            $newId = $prefix . str_pad(1, $pad, '0', STR_PAD_LEFT);
        } else {
            $latestId = $latestRecord->{$field};
            $latestNumber = intval(substr($latestId, strlen($prefix)));
            $newNumber = str_pad($latestNumber + 1, $pad, '0', STR_PAD_LEFT);
            $newId = "{$prefix}{$newNumber}";
        }
        return $newId;
    }
}