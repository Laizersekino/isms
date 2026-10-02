<p>
    <label for="academic_year_id">Academic year</label><br>
    <select id="academic_year_id" name="academic_year_id" required>
        <option value="">Select academic year</option>
        @foreach($academicYears as $academicYear)
            <option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id', $feeStructure?->academic_year_id) === (string) $academicYear->id)>
                {{ $academicYear->name }}
            </option>
        @endforeach
    </select>
</p>
<p>
    <label for="term_id">Term</label><br>
    <select id="term_id" name="term_id" required>
        <option value="">Select term</option>
        @foreach($terms as $term)
            <option value="{{ $term->id }}" data-academic-year="{{ $term->academic_year_id }}" @selected((string) old('term_id', $feeStructure?->term_id) === (string) $term->id)>
                {{ $term->academicYear->name }} — {{ $term->name }}
            </option>
        @endforeach
    </select>
</p>
<p>
    <label for="class_id">Class</label><br>
    <select id="class_id" name="class_id" required>
        <option value="">Select class</option>
        @foreach($classes as $class)
            <option value="{{ $class->id }}" @selected((string) old('class_id', $feeStructure?->class_id) === (string) $class->id)>
                {{ $class->name }}
            </option>
        @endforeach
    </select>
</p>
<p><label for="name">Name</label><br><input id="name" name="name" maxlength="255" value="{{ old('name', $feeStructure?->name) }}" required></p>
<p><label for="description">Description</label><br><textarea id="description" name="description" maxlength="2000">{{ old('description', $feeStructure?->description) }}</textarea></p>
<p>
    <label for="status">Status</label><br>
    <select id="status" name="status" required>
        @foreach(['draft', 'active', 'archived'] as $status)
            <option value="{{ $status }}" @selected(old('status', $feeStructure?->status ?? 'draft') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
</p>

<h2>Fee Items</h2>
<div id="fee-items">
    @foreach($items as $index => $item)
        <fieldset class="fee-item">
            <legend>Fee item</legend>
            <p><label>Item name<br><input data-field="name" name="items[{{ $index }}][name]" maxlength="100" value="{{ $item['name'] ?? '' }}" required></label></p>
            <p><label>Amount (TZS)<br><input type="number" data-field="amount" name="items[{{ $index }}][amount]" min="0" step="0.01" value="{{ $item['amount'] ?? '' }}" required></label></p>
            <p>
                <input type="hidden" data-field="is_mandatory" name="items[{{ $index }}][is_mandatory]" value="0">
                <label><input type="checkbox" data-field="is_mandatory" name="items[{{ $index }}][is_mandatory]" value="1" @checked((bool) ($item['is_mandatory'] ?? false))> Mandatory</label>
            </p>
            <p><label>Description<br><textarea data-field="description" name="items[{{ $index }}][description]" maxlength="500">{{ $item['description'] ?? '' }}</textarea></label></p>
            <button type="button" class="danger remove-fee-item">Remove item</button>
        </fieldset>
    @endforeach
</div>
<button type="button" id="add-fee-item">Add fee item</button>

<template id="fee-item-template">
    <fieldset class="fee-item">
        <legend>Fee item</legend>
        <p><label>Item name<br><input data-field="name" maxlength="100" required></label></p>
        <p><label>Amount (TZS)<br><input type="number" data-field="amount" min="0" step="0.01" required></label></p>
        <p>
            <input type="hidden" data-field="is_mandatory" value="0">
            <label><input type="checkbox" data-field="is_mandatory" value="1" checked> Mandatory</label>
        </p>
        <p><label>Description<br><textarea data-field="description" maxlength="500"></textarea></label></p>
        <button type="button" class="danger remove-fee-item">Remove item</button>
    </fieldset>
</template>

<script>
    const feeItems = document.getElementById('fee-items');
    const itemTemplate = document.getElementById('fee-item-template');
    const addItemButton = document.getElementById('add-fee-item');

    function reindexItems() {
        feeItems.querySelectorAll('.fee-item').forEach((item, index) => {
            item.querySelectorAll('[data-field]').forEach((field) => {
                field.name = `items[${index}][${field.dataset.field}]`;
            });
        });
    }

    addItemButton.addEventListener('click', () => {
        feeItems.append(itemTemplate.content.cloneNode(true));
        reindexItems();
    });

    feeItems.addEventListener('click', (event) => {
        if (event.target.matches('.remove-fee-item')) {
            event.target.closest('.fee-item').remove();
            reindexItems();
        }
    });

    const academicYear = document.getElementById('academic_year_id');
    const term = document.getElementById('term_id');

    function filterTermsByAcademicYear() {
        Array.from(term.options).forEach((option) => {
            if (option.value === '') {
                return;
            }

            option.hidden = option.dataset.academicYear !== academicYear.value;
            option.disabled = option.hidden;
        });

        const selectedTerm = term.selectedOptions[0];
        if (selectedTerm && selectedTerm.disabled) {
            term.value = '';
        }
    }

    academicYear.addEventListener('change', filterTermsByAcademicYear);
    filterTermsByAcademicYear();
</script>
