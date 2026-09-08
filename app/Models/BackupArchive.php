<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class BackupArchive extends Model
{
    protected $fillable = [
        'month',
        'status',
        'archived_at',
        'deleted_at',
        'deleted_transaction_marker',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}