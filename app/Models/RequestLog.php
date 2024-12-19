<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestLog extends Model
{
    protected $fillable = [
        'ip_address',
        'uri',
        'method',
        'controller_action',
        'middleware',
        // 'headers',
        'payload',
        'response_status',
        'duration',
        // 'memory',
        'requested_by',
        'response'
    ];

    public static function queryableFields(): array
    {
        return [
            'ip_address',
            'uri',
            'method',
            'middleware',
            'response_status',
        ];
    }

    public function scopeFilter($query, array $filters): void
    {
        foreach ($filters as $field => $value) {
            if (in_array($field, self::queryableFields())) {
                $query->where($field, 'like', "%{$value}%");
            }
        }
    }
}
