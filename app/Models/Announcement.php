<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'category',
        'audience_type',
        'audience_value',
        'publish_at',
        'expires_at',
        'status',
        'is_pinned',
        'created_by',
        'published_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'audience_value' => 'array',
            'publish_at' => 'datetime',
            'expires_at' => 'datetime',
            'published_at' => 'datetime',
            'is_pinned' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_reads')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where(fn (Builder $query) => $query->whereNull('publish_at')->orWhere('publish_at', '<=', now()));
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->published()
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        $roleIds = $user->roles()->pluck('roles.id')->all();
        $classIds = $this->classIdsForUser($user);

        return $query->where(function (Builder $query) use ($user, $roleIds, $classIds): void {
            $query->where('audience_type', 'all');

            if ($roleIds !== []) {
                $query->orWhere(function (Builder $query) use ($roleIds): void {
                    $query->where('audience_type', 'role')
                        ->where(function (Builder $query) use ($roleIds): void {
                            foreach ($roleIds as $roleId) {
                                $query->orWhereJsonContains('audience_value', $roleId);
                            }
                        });
                });
            }

            if ($classIds !== []) {
                $query->orWhere(function (Builder $query) use ($classIds): void {
                    $query->where('audience_type', 'class')
                        ->where(function (Builder $query) use ($classIds): void {
                            foreach ($classIds as $classId) {
                                $query->orWhereJsonContains('audience_value', $classId);
                            }
                        });
                });
            }

            $query->orWhere(function (Builder $query) use ($user): void {
                $query->where('audience_type', 'specific_users')
                    ->whereJsonContains('audience_value', $user->id);
            });
        });
    }

    public function isVisible(): bool
    {
        return $this->status === 'published'
            && ($this->publish_at === null || $this->publish_at->lte(now()))
            && ($this->expires_at === null || $this->expires_at->gt(now()));
    }

    public function isVisibleFor(User $user): bool
    {
        return static::query()
            ->whereKey($this->getKey())
            ->visible()
            ->forUser($user)
            ->exists();
    }

    public function isReadBy(User $user): bool
    {
        return $this->reads()->where('user_id', $user->id)->exists();
    }

    public function markAsReadBy(User $user): void
    {
        $this->reads()->firstOrCreate(
            ['user_id' => $user->id],
            ['read_at' => now()]
        );
    }

    public function publish(User $user): void
    {
        $this->update([
            'status' => 'published',
            'published_by' => $user->id,
            'published_at' => now(),
        ]);
    }

    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    /**
     * @return array<int, int>
     */
    private function classIdsForUser(User $user): array
    {
        $classIds = [];

        if ($user->hasRole('Student')) {
            $classIds = array_merge(
                $classIds,
                StudentEnrollment::query()
                    ->where('status', 'active')
                    ->whereHas('student', fn (Builder $query) => $query->whereRaw('LOWER(email) = ?', [mb_strtolower($user->email)]))
                    ->pluck('class_id')
                    ->all()
            );
        }

        if ($user->hasRole('Parent')) {
            $classIds = array_merge(
                $classIds,
                StudentEnrollment::query()
                    ->where('status', 'active')
                    ->whereHas('student.parents', fn (Builder $query) => $query->whereRaw('LOWER(parents.email) = ?', [mb_strtolower($user->email)]))
                    ->pluck('class_id')
                    ->all()
            );
        }

        if ($user->hasRole('Teacher') && $user->teacher_id !== null) {
            $classIds = array_merge(
                $classIds,
                TeacherAssignment::query()
                    ->where('teacher_id', $user->teacher_id)
                    ->where('status', 'active')
                    ->pluck('class_id')
                    ->all()
            );
        }

        return array_values(array_unique(array_map('intval', $classIds)));
    }
}
