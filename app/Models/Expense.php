<?php

namespace App\Models;

use App\Models\Concerns\HasStableUuid;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasStableUuid;

    protected $fillable = [
        'name',
        'category',
        'amount',
        'expense_date',
        'note',
    ];
}
