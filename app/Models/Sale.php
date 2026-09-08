<?php

namespace App\Models;

use App\Models\Concerns\HasStableUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use HasStableUuid;

    protected $fillable = [
        'product_id',
        'quantity',
        'price',
        'total',
        'sold_at',
    ];

    /**
     * `sold_at` disimpan sebagai kolom `date` di database (lihat migration
     * create_sales_table), jadi harus di-cast ke Carbon supaya method
     * seperti ->format()/->isToday() bisa dipakai langsung, konsisten
     * dengan created_at/updated_at yang sudah otomatis di-cast Eloquent.
     */
    protected $casts = [
        'sold_at' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
