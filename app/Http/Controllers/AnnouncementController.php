<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\ClassRoom;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AnnouncementController extends Controller implements HasMiddleware
{
    private const CATEGORIES = ['general', 'academic', 'event', 'urgent', 'holiday'];

    private const AUDIENCE_TYPES = ['all', 'role', 'class', 'specific_users'];

    private const STATUSES = ['draft', 'published', 'archived'];

    public static function middleware(): array
    {
        return [
            new Middleware('permission:announcements.view', only: ['index', 'show']),
            new Middleware('permission:announcements.create', only: ['create', 'store']),
            new Middleware('permission:announcements.update', only: ['edit', 'update']),
            new Middleware('permission:announcements.delete', only: ['destroy']),
            new Middleware('permission:announcements.publish', only: ['publish', 'archive']),
        ];
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'category' => ['nullable', Rule::in(self::CATEGORIES)],
            'audience_type' => ['nullable', Rule::in(self::AUDIENCE_TYPES)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
        $user = $request->user();
        $isAdministrator = $this->isAdministrator($user);
        $announcements = Announcement::query()
            ->with(['createdBy:id,name'])
            ->when($isAdministrator, fn (Builder $query) => $query->withCount('reads'))
            ->when(! $isAdministrator, function (Builder $query) use ($user): void {
                $query->where(function (Builder $query) use ($user): void {
                    $query->visible()
                        ->forUser($user)
                        ->orWhere(function (Builder $query) use ($user): void {
                            $query->where('status', 'draft')
                                ->where('created_by', $user->id);
                        });
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $query->where('category', $category))
            ->when($filters['audience_type'] ?? null, fn (Builder $query, string $type) => $query->where('audience_type', $type))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('announcements.index', compact('announcements', 'filters', 'isAdministrator'));
    }

    public function create(): View
    {
        return view('announcements.create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedAnnouncement($request);
        $announcement = Announcement::create([
            ...$this->announcementAttributes($validated),
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('announcements.show', $announcement)
            ->with('success', 'Announcement draft created successfully.');
    }

    public function show(Request $request, Announcement $announcement): View
    {
        $user = $request->user();
        abort_unless($this->canView($announcement, $user), 404);

        $announcement->markAsReadBy($user);

        $announcement->load(['createdBy:id,name', 'publishedBy:id,name'])
            ->loadCount('reads');
        $isAdministrator = $this->isAdministrator($user);
        $canEdit = $this->canEdit($announcement, $user);

        return view('announcements.show', compact('announcement', 'isAdministrator', 'canEdit'));
    }

    public function edit(Request $request, Announcement $announcement): View
    {
        abort_unless($this->canEdit($announcement, $request->user()), 403);

        return view('announcements.edit', [
            ...$this->formOptions(),
            'announcement' => $announcement,
        ]);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        abort_unless($this->canEdit($announcement, $request->user()), 403);
        $validated = $this->validatedAnnouncement($request);
        $announcement->update($this->announcementAttributes($validated));

        return redirect()
            ->route('announcements.show', $announcement)
            ->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Request $request, Announcement $announcement): RedirectResponse
    {
        if (
            in_array($announcement->status, ['published', 'archived'], true)
            && $announcement->published_at !== null
            && $announcement->published_at->lt(now()->subHours(24))
        ) {
            throw ValidationException::withMessages([
                'announcement' => 'Published announcements older than 24 hours cannot be deleted.',
            ]);
        }

        $announcement->delete();

        return redirect()
            ->route('announcements.index')
            ->with('success', 'Announcement deleted successfully.');
    }

    public function publish(Request $request, Announcement $announcement): RedirectResponse
    {
        if ($announcement->status !== 'draft') {
            throw ValidationException::withMessages([
                'announcement' => 'Only a draft announcement can be published.',
            ]);
        }

        if ($announcement->publish_at !== null && $announcement->publish_at->isFuture()) {
            throw ValidationException::withMessages([
                'publish_at' => 'This announcement is scheduled for a future time and cannot be published yet.',
            ]);
        }

        $announcement->publish($request->user());

        return redirect()
            ->route('announcements.show', $announcement)
            ->with('success', 'Announcement published successfully.');
    }

    public function archive(Announcement $announcement): RedirectResponse
    {
        if ($announcement->status !== 'published') {
            throw ValidationException::withMessages([
                'announcement' => 'Only a published announcement can be archived.',
            ]);
        }

        $announcement->archive();

        return redirect()
            ->route('announcements.show', $announcement)
            ->with('success', 'Announcement archived successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'classes' => ClassRoom::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'categories' => self::CATEGORIES,
            'audienceTypes' => self::AUDIENCE_TYPES,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedAnnouncement(Request $request): array
    {
        $audienceType = $request->input('audience_type');
        $audienceTable = match ($audienceType) {
            'role' => 'roles',
            'class' => 'classes',
            'specific_users' => 'users',
            default => null,
        };
        $audienceValueRules = ['nullable', 'array'];

        if ($audienceTable !== null) {
            $audienceValueRules = ['required', 'array', 'min:1'];
        }

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:10000'],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'audience_type' => ['required', Rule::in(self::AUDIENCE_TYPES)],
            'audience_value' => $audienceValueRules,
            'audience_value.*' => $audienceTable === null
                ? ['integer']
                : ['required', 'integer', Rule::exists($audienceTable, 'id')],
            'publish_at' => ['nullable', 'date', 'after_or_equal:now'],
            'expires_at' => ['nullable', 'date'],
            'is_pinned' => ['sometimes', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $expiresAt = $request->input('expires_at');
            if (! is_string($expiresAt) || $expiresAt === '') {
                return;
            }

            $comparisonDate = $request->input('publish_at') ?: now()->toDateTimeString();
            if (strtotime($expiresAt) === false || strtotime($expiresAt) <= strtotime($comparisonDate)) {
                $validator->errors()->add('expires_at', 'The expiration date must be after the publish date.');
            }
        });

        return $validator->validate();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function announcementAttributes(array $validated): array
    {
        $attributes = [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'category' => $validated['category'],
            'audience_type' => $validated['audience_type'],
            'audience_value' => $validated['audience_type'] === 'all'
                ? null
                : array_map('intval', $validated['audience_value']),
            'publish_at' => $validated['publish_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_pinned' => (bool) ($validated['is_pinned'] ?? false),
        ];

        return $attributes;
    }

    private function canView(Announcement $announcement, User $user): bool
    {
        return $this->isAdministrator($user)
            || ($announcement->status === 'draft' && $announcement->created_by === $user->id)
            || ($announcement->isVisible() && $announcement->isVisibleFor($user));
    }

    private function canEdit(Announcement $announcement, User $user): bool
    {
        if (! $user->hasPermission('announcements.update')) {
            return false;
        }

        if ($announcement->status === 'archived') {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return $announcement->status !== 'published'
                || ($announcement->published_at !== null && $announcement->published_at->gt(now()->subHours(24)));
        }

        if ($announcement->created_by !== $user->id) {
            return false;
        }

        return $announcement->status === 'draft'
            || ($announcement->status === 'published'
                && $announcement->published_at !== null
                && $announcement->published_at->gt(now()->subHours(24)));
    }

    private function isAdministrator(User $user): bool
    {
        return $user->hasRole('Super Administrator')
            || $user->hasRole('School Administrator');
    }
}
