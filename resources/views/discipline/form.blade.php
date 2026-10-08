<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <x-form.select name="student_id" label="Student" required>
        <option value="">Select Student</option>
        @foreach($students as $student)
            <option value="{{ $student->id }}" @selected(old('student_id', $disciplinaryCase->student_id ?? '') == $student->id)>
                {{ $student->first_name }} {{ $student->last_name }} ({{ $student->admission_number }})
            </option>
        @endforeach
    </x-form.select>

    <x-form.input name="offence_type" label="Offence Type" :value="old('offence_type', $disciplinaryCase->offence_type ?? '')" required />

    <x-form.input name="incident_date" label="Incident Date" type="date" :value="old('incident_date', isset($disciplinaryCase) && $disciplinaryCase->incident_date ? $disciplinaryCase->incident_date->format('Y-m-d') : '')" required />

    <x-form.select name="status" label="Status" required>
        <option value="open" @selected(old('status', $disciplinaryCase->status ?? 'open') === 'open')>Open</option>
        <option value="resolved" @selected(old('status', $disciplinaryCase->status ?? '') === 'resolved')>Resolved</option>
        <option value="closed" @selected(old('status', $disciplinaryCase->status ?? '') === 'closed')>Closed</option>
    </x-form.select>

    <x-form.select name="reported_by" label="Reported By">
        <option value="">Select User</option>
        @foreach(\App\Models\User::orderBy('name')->get() as $user)
            <option value="{{ $user->id }}" @selected(old('reported_by', $disciplinaryCase->reported_by ?? '') == $user->id)>
                {{ $user->name }}
            </option>
        @endforeach
    </x-form.select>

    <x-form.input name="resolution_date" label="Resolution Date" type="date" :value="old('resolution_date', isset($disciplinaryCase) && $disciplinaryCase->resolution_date ? $disciplinaryCase->resolution_date->format('Y-m-d') : '')" />
</div>

<div class="mt-6">
    <x-form.textarea name="description" label="Description" rows="4" :value="old('description', $disciplinaryCase->description ?? '')" required />
</div>

<div class="mt-6">
    <x-form.textarea name="action_taken" label="Action Taken" rows="3" :value="old('action_taken', $disciplinaryCase->action_taken ?? '')" />
</div>

<div class="mt-6">
    <x-form.textarea name="follow_up" label="Follow Up" rows="3" :value="old('follow_up', $disciplinaryCase->follow_up ?? '')" />
</div>