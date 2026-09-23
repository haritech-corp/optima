<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBudget extends Model
{
    use HasHumanId;

    protected $primaryKey = 'budget_id';
    protected $idPrefix = 'BDG';
    protected $guarded = [];
    protected $casts = ['initial_budget' => 'decimal:2', 'actual' => 'decimal:2', 'remaining' => 'decimal:2'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function refreshRemaining(): void
    {
        $this->forceFill(['remaining' => $this->initial_budget - $this->actual])->saveQuietly();
    }
}