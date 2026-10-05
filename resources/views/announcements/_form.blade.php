@php
    $announcement = $announcement ?? null;
    $selectedAudienceType = old('audience_type', $announcement?->audience_type ?? 'all');
    $selectedAudienceValues = (array) old('audience_value', $announcement?->audience_value ?? []);
@endphp

<x-card>
    <form method="POST" action="{{ $action }}" class="space-y-6">
        @csrf
        @if($method !== 'POST')
            @method($method)
        @endif

        <div class="grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-form.input
                    name="title"
                    label="Announcement Title"
                    maxlength="255"
                    :value="$announcement?->title"
                    placeholder="e.g., Annual Sports Day Schedule"
                    required
                />
            </div>

            <div class="md:col-span-2">
                <x-form.textarea
                    name="content"
                    label="Announcement Content"
                    maxlength="10000"
                    rows="8"
                    :value="$announcement?->content"
                    placeholder="Write your announcement details and instructions here..."
                    required
                />
            </div>

            <x-form.select name="category" label="Category" required>
                @foreach($categories as $category)
                    <option value="{{ $category }}" @selected(old('category', $announcement?->category ?? 'general') === $category)>
                        {{ str($category)->title() }}
                    </option>
                @endforeach
            </x-form.select>

            <x-form.select name="audience_type" label="Target Audience" id="audience_type" required>
                @foreach($audienceTypes as $audienceType)
                    <option value="{{ $audienceType }}" @selected($selectedAudienceType === $audienceType)>
                        {{ str($audienceType)->replace('_', ' ')->title() }}
                    </option>
                @endforeach
            </x-form.select>

            <div class="audience-options md:col-span-2" data-audience="role">
                <x-form.select name="audience_value[]" label="Target Roles" id="audience_roles" multiple size="5">
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected($selectedAudienceType === 'role' && in_array($role->id, $selectedAudienceValues))>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </x-form.select>
                <p class="mt-1 text-xs text-slate-500">Hold Ctrl (Cmd on Mac) to select multiple roles.</p>
            </div>

            <div class="audience-options md:col-span-2" data-audience="class">
                <x-form.select name="audience_value[]" label="Target Classes" id="audience_classes" multiple size="5">
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected($selectedAudienceType === 'class' && in_array($class->id, $selectedAudienceValues))>
                            {{ $class->name }}
                        </option>
                    @endforeach
                </x-form.select>
                <p class="mt-1 text-xs text-slate-500">Hold Ctrl (Cmd on Mac) to select multiple classes.</p>
            </div>

            <div class="audience-options md:col-span-2" data-audience="specific_users">
                <x-form.select name="audience_value[]" label="Target Specific Users" id="audience_users" multiple size="5">
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected($selectedAudienceType === 'specific_users' && in_array($user->id, $selectedAudienceValues))>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </x-form.select>
                <p class="mt-1 text-xs text-slate-500">Hold Ctrl (Cmd on Mac) to select multiple users.</p>
            </div>

            <div class="md:col-span-2">
                <x-form.error name="audience_value" />
                <x-form.error name="audience_value.*" />
            </div>

            <x-form.input
                name="publish_at"
                label="Schedule Publishing (Optional)"
                type="datetime-local"
                :value="isset($announcement->publish_at) ? $announcement->publish_at->format('Y-m-d\\TH:i') : ''"
            />

            <x-form.input
                name="expires_at"
                label="Expiration Date (Optional)"
                type="datetime-local"
                :value="isset($announcement->expires_at) ? $announcement->expires_at->format('Y-m-d\\TH:i') : ''"
            />

            <div class="md:col-span-2 rounded-lg border border-slate-200 bg-slate-50/50 p-4">
                <x-form.checkbox
                    name="is_pinned"
                    label="Pin this announcement to keep it at the top"
                    :checked="$announcement?->is_pinned ?? false"
                />
                <p class="mt-1 pl-6 text-xs text-slate-500">Pinned announcements are prominently displayed at the top of the announcement feed.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-5">
            <x-button type="submit" icon="check">{{ $submitLabel }}</x-button>
            <a href="{{ isset($announcement) ? route('announcements.show', $announcement) : route('announcements.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 focus-visible:outline-none">
                Cancel
            </a>
        </div>
    </form>
</x-card>

<script>
    const audienceType = document.getElementById('audience_type');
    const audienceOptions = document.querySelectorAll('.audience-options');

    function updateAudienceOptions() {
        audienceOptions.forEach((group) => {
            const isSelected = group.dataset.audience === audienceType.value;
            group.hidden = !isSelected;
            group.querySelector('select').disabled = !isSelected;
        });
    }

    audienceType.addEventListener('change', updateAudienceOptions);
    updateAudienceOptions();
</script>
