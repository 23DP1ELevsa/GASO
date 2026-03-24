<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cylinder extends Model
{
    use HasFactory;

    protected $fillable = [
        'status_id',
        'serial_number',
        'capacity',
        'manufacture_date',
        'inspection_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'float',
            'manufacture_date' => 'date',
            'inspection_date' => 'date',
        ];
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function latestTransaction(): HasOne
    {
        return $this->hasOne(Transaction::class)->latestOfMany();
    }
}