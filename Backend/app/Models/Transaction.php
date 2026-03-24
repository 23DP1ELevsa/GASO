<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'cylinder_id',
        'client_id',
        'employee_id',
        'issue_date',
        'return_date',
        'action_type',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'return_date' => 'date',
        ];
    }

    public function cylinder(): BelongsTo
    {
        return $this->belongsTo(Cylinder::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}