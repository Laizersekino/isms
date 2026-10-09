<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    <x-form.select name="academic_year_id" label="Academic year" required>
        <option value="">Select academic year</option>
        @foreach($academicYears as $academicYear)
            <option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id', $feeStructure?->academic_year_id) === (string) $academicYear->id)>{{ $academicYear->name }}</option>
        @endforeach
    </x-form.select>

    <x-form.select name="term_id" label="Term" required>
        <option value="">Select term</option>
        @foreach($terms as $term)
            <option value="{{ $term->id }}" data-academic-year="{{ $term->academic_year_id }}" @selected((string) old('term_id', $feeStructure?->term_id) === (string) $term->id)>
                {{ $term->academicYear->name }} — {{ $term->name }}
            </option>
        @endforeach
    </x-form.select>

    <x-form.select name="class_id" label="Class" required>
        <option value="">Select class</option>
        @foreach($classes as $class)
            <option value="{{ $class->id }}" @selected((string) old('class_id', $feeStructure?->class_id) === (string) $class->id)>{{ $class->name }}</option>
        @endforeach
    </x-form.select>

    <x-form.input name="name" label="Name" maxlength="255" :value="$feeStructure?->name" required />

    <x-form.select name="status" label="Status" required>
        @foreach(['draft', 'active', 'archived'] as $status)
            <option value="{{ $status }}" @selected(old('status', $feeStructure?->status ?? 'draft') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </x-form.select>

    <x-form.select name="payment_plan" label="Payment Plan" required>
        @foreach(['full' => 'Full Payment', 'termly' => 'Termly (3 installments)', 'monthly' => 'Monthly', 'custom' => 'Custom'] as $value => $label)
            <option value="{{ $value }}" @selected(old('payment_plan', $feeStructure?->payment_plan ?? 'termly') === $value)>{{ $label }}</option>
        @endforeach
    </x-form.select>

    <x-form.input name="installment_count" label="Installment Count" type="number" min="1" max="12" :value="old('installment_count', $feeStructure?->installment_count ?? 3)" />

    <div class="md:col-span-2 xl:col-span-3">
        <x-form.textarea name="description" label="Description" maxlength="2000" :value="$feeStructure?->description" rows="3" />
    </div>
</div>

<section class="space-y-4">
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Fee items</h2>
            <p class="text-sm text-slate-600">Add each charge included in this fee structure.</p>
        </div>
        <button type="button" id="add-fee-item" class="inline-flex items-center justify-center gap-2 rounded-lg border border-primary-200 px-4 py-2 text-sm font-semibold text-primary-700 hover:bg-primary-50">
            <x-icon name="plus" size="sm" /> Add fee item
        </button>
    </div>
    <div id="fee-items" class="grid gap-4 lg:grid-cols-2">
        @foreach($items as $index => $item)
            <fieldset class="fee-item min-w-0 space-y-4 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                <legend class="px-1 text-sm font-semibold text-slate-800">Fee item</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label for="item-{{ $index }}-name" class="block text-sm font-medium text-slate-700">Item name</label>
                        <input id="item-{{ $index }}-name" data-field="name" name="items[{{ $index }}][name]" maxlength="100" value="{{ $item['name'] ?? '' }}" required class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-primary-500 focus:ring-4 focus:ring-primary-100">
                        <x-form.error :name="'items.'.$index.'.name'" />
                    </div>
                    <div class="space-y-1.5">
                        <label for="item-{{ $index }}-amount" class="block text-sm font-medium text-slate-700">Amount ({{ config('library.currency', 'TZS') }})</label>
                        <input id="item-{{ $index }}-amount" type="number" data-field="amount" name="items[{{ $index }}][amount]" min="0" step="0.01" value="{{ $item['amount'] ?? '' }}" required class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-primary-500 focus:ring-4 focus:ring-primary-100">
                        <x-form.error :name="'items.'.$index.'.amount'" />
                    </div>
                    <div class="space-y-1.5">
                        <label for="item-{{ $index }}-due_date" class="block text-sm font-medium text-slate-700">Due date</label>
                        <input id="item-{{ $index }}-due_date" type="date" data-field="due_date" name="items[{{ $index }}][due_date]" value="{{ $item['due_date'] ?? '' }}" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-primary-500 focus:ring-4 focus:ring-primary-100">
                        <x-form.error :name="'items.'.$index.'.due_date'" />
                    </div>
                    <div class="space-y-1.5">
                        <label for="item-{{ $index }}-order" class="block text-sm font-medium text-slate-700">Order</label>
                        <input id="item-{{ $index }}-order" type="number" data-field="order" name="items[{{ $index }}][order]" min="0" value="{{ $item['order'] ?? $index }}" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-primary-500 focus:ring-4 focus:ring-primary-100">
                        <x-form.error :name="'items.'.$index.'.order'" />
                    </div>
                    <div class="space-y-1.5 sm:col-span-2">
                        <label for="item-{{ $index }}-description" class="block text-sm font-medium text-slate-700">Description</label>
                        <textarea id="item-{{ $index }}-description" data-field="description" name="items[{{ $index }}][description]" maxlength="500" rows="2" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-primary-500 focus:ring-4 focus:ring-primary-100">{{ $item['description'] ?? '' }}</textarea>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-3">
                    <div>
                        <input type="hidden" data-field="is_mandatory" name="items[{{ $index }}][is_mandatory]" value="0">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" data-field="is_mandatory" name="items[{{ $index }}][is_mandatory]" value="1" @checked((bool) ($item['is_mandatory'] ?? false)) class="size-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                            Mandatory fee
                        </label>
                    </div>
                    <button type="button" class="remove-fee-item inline-flex items-center gap-1.5 text-sm font-medium text-danger-700 hover:text-danger-800">
                        <x-icon name="trash" size="sm" /> Remove item
                    </button>
                </div>
            </fieldset>
        @endforeach
    </div>
    <x-form.error name="items" />
</section>

<template id="fee-item-template">
    <fieldset class="fee-item min-w-0 space-y-4 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
        <legend class="px-1 text-sm font-semibold text-slate-800">Fee item</legend>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-slate-700">Item name</label>
                <input data-field="name" maxlength="100" required class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-primary-500 focus:ring-4 focus:ring-primary-100">
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-slate-700">Amount ({{ config('library.currency', 'TZS') }})</label>
                <input type="number" data-field="amount" min="0" step="0.01" required class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-primary-500 focus:ring-4 focus:ring-primary-100">
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-slate-700">Due date</label>
                <input type="date" data-field="due_date" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-primary-500 focus:ring-4 focus:ring-primary-100">
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-slate-700">Order</label>
                <input type="number" data-field="order" min="0" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-primary-500 focus:ring-4 focus:ring-primary-100">
            </div>
            <div class="space-y-1.5 sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Description</label>
                <textarea data-field="description" maxlength="500" rows="2" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-primary-500 focus:ring-4 focus:ring-primary-100"></textarea>
            </div>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-3">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" data-field="is_mandatory" value="0">
                <input type="checkbox" data-field="is_mandatory" value="1" checked class="size-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                Mandatory fee
            </label>
            <button type="button" class="remove-fee-item inline-flex items-center gap-1.5 text-sm font-medium text-danger-700 hover:text-danger-800">
                <x-icon name="trash" size="sm" /> Remove item
            </button>
        </div>
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
        const removeButton = event.target.closest('.remove-fee-item');
        if (removeButton) {
            removeButton.closest('.fee-item').remove();
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