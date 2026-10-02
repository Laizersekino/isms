@php
    $selectedAudienceType = old('audience_type', $announcement->audience_type ?? 'all');
    $selectedAudienceValues = old('audience_value', $announcement->audience_value ?? []);
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <p>
        <label for="title">Title</label><br>
        <input id="title" name="title" type="text" maxlength="255" required
            value="{{ old('title', $announcement->title ?? '') }}">
        @error('title')<br><span>{{ $message }}</span>@enderror
    </p>

    <p>
        <label for="content">Content</label><br>
        <textarea id="content" name="content" maxlength="10000" rows="10" required>{{ old('content', $announcement->content ?? '') }}</textarea>
        @error('content')<br><span>{{ $message }}</span>@enderror
    </p>

    <p>
        <label for="category">Category</label><br>
        <select id="category" name="category" required>
            @foreach($categories as $category)
                <option value="{{ $category }}" @selected(old('category', $announcement->category ?? 'general') === $category)>
                    {{ str($category)->title() }}
                </option>
            @endforeach
        </select>
        @error('category')<br><span>{{ $message }}</span>@enderror
    </p>

    <p>
        <label for="audience_type">Audience</label><br>
        <select id="audience_type" name="audience_type" required>
            @foreach($audienceTypes as $audienceType)
                <option value="{{ $audienceType }}" @selected($selectedAudienceType === $audienceType)>
                    {{ str($audienceType)->replace('_', ' ')->title() }}
                </option>
            @endforeach
        </select>
        @error('audience_type')<br><span>{{ $message }}</span>@enderror
    </p>

    <p class="audience-options" data-audience="role">
        <label for="audience_roles">Roles</label><br>
        <select id="audience_roles" name="audience_value[]" multiple>
            @foreach($roles as $role)
                <option value="{{ $role->id }}" @selected($selectedAudienceType === 'role' && in_array($role->id, $selectedAudienceValues))>
                    {{ $role->name }}
                </option>
            @endforeach
        </select>
    </p>

    <p class="audience-options" data-audience="class">
        <label for="audience_classes">Classes</label><br>
        <select id="audience_classes" name="audience_value[]" multiple>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" @selected($selectedAudienceType === 'class' && in_array($class->id, $selectedAudienceValues))>
                    {{ $class->name }}
                </option>
            @endforeach
        </select>
    </p>

    <p class="audience-options" data-audience="specific_users">
        <label for="audience_users">Users</label><br>
        <select id="audience_users" name="audience_value[]" multiple>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected($selectedAudienceType === 'specific_users' && in_array($user->id, $selectedAudienceValues))>
                    {{ $user->name }} ({{ $user->email }})
                </option>
            @endforeach
        </select>
    </p>
    @error('audience_value')<p>{{ $message }}</p>@enderror
    @error('audience_value.*')<p>{{ $message }}</p>@enderror

    <p>
        <label for="publish_at">Schedule publishing</label><br>
        <input id="publish_at" name="publish_at" type="datetime-local"
            value="{{ old('publish_at', isset($announcement->publish_at) ? $announcement->publish_at->format('Y-m-d\TH:i') : '') }}">
        @error('publish_at')<br><span>{{ $message }}</span>@enderror
    </p>

    <p>
        <label for="expires_at">Expires at</label><br>
        <input id="expires_at" name="expires_at" type="datetime-local"
            value="{{ old('expires_at', isset($announcement->expires_at) ? $announcement->expires_at->format('Y-m-d\TH:i') : '') }}">
        @error('expires_at')<br><span>{{ $message }}</span>@enderror
    </p>

    <p>
        <label>
            <input name="is_pinned" type="checkbox" value="1" @checked(old('is_pinned', $announcement->is_pinned ?? false))>
            Pin announcement
        </label>
    </p>

    <button type="submit">{{ $submitLabel }}</button>
</form>

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
