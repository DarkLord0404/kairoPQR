<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complaint extends Model
{
    protected $fillable = ['reference', 'contact_name', 'contact_email', 'contact_phone', 'category', 'description', 'status', 'assigned_to', 'created_by', 'response', 'responded_at', 'closed_at', 'received_at'];

    protected function casts(): array
    {
        return ['received_at' => 'datetime', 'responded_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function histories(): HasMany { return $this->hasMany(ComplaintHistory::class)->latest(); }
}
