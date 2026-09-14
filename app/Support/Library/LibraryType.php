<?php

namespace App\Support\Library;

use App\Models\Package;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * The description of one kind of reusable content: what it is called in plain
 * words, which fields its form has, how its list is searched and filtered,
 * how to find the packages that use a record, and which of its values get
 * copied into those packages when the admin asks for them to be updated.
 *
 * One definition drives the list page, the form, validation, usage counts and
 * the update action, so the nine library sections behave identically.
 */
class LibraryType
{
    /**
     * @param  class-string<Model>  $model
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<int, array<string, mixed>>  $columns
     * @param  array<string, array{label: string, options: array<string, string>}>  $filters
     * @param  array<string, string>  $syncMap  package-row column => library field
     */
    public function __construct(
        public readonly string $key,
        public readonly string $model,
        public readonly string $label,
        public readonly string $singular,
        public readonly string $icon,
        public readonly string $intro,
        public readonly array $fields,
        public readonly array $columns,
        public readonly array $search,
        public readonly array $filters = [],
        public readonly ?string $usageRelation = null,
        public readonly array $syncMap = [],
        public readonly array $fixed = [],
        public readonly ?string $subtitleField = null,
    ) {}

    public function titleColumn(): string
    {
        return ($this->model)::libraryTitleColumn();
    }

    public function query(): Builder
    {
        return ($this->model)::query()->where($this->fixed);
    }

    public function newRecord(): Model
    {
        $record = new ($this->model);
        $record->forceFill($this->fixed);

        foreach ($this->fields as $field) {
            if (array_key_exists('default', $field)) {
                $record->{$field['name']} = $field['default'];
            }
        }

        return $record;
    }

    public function tracksUsage(): bool
    {
        return $this->usageRelation !== null;
    }

    /** Live packages (not deleted) with at least one row copied from $record. */
    public function packagesUsing(Model $record): Builder
    {
        $relation = $this->usageRelation;
        $foreignKey = $record->{$relation}()->getForeignKeyName();
        $table = $record->{$relation}()->getRelated()->getTable();

        return Package::query()
            ->whereIn('id', fn ($q) => $q->select('package_id')->from($table)->where($foreignKey, $record->getKey()))
            ->orderBy('sort_order');
    }

    public function canPushToPackages(): bool
    {
        return $this->tracksUsage() && $this->syncMap !== [];
    }

    /**
     * Copies the record's current values into every package row that was
     * picked from it. This is the ONLY way a library edit reaches a package,
     * and it is always an explicit admin action — see the migration docblock
     * in 2026_09_14_200000_create_package_library_tables.
     *
     * Overwrites any package-specific change made to those copied fields.
     *
     * @return array{rows: int, packages: int}
     */
    public function pushToPackages(Model $record): array
    {
        if (! $this->canPushToPackages()) {
            return ['rows' => 0, 'packages' => 0];
        }

        $values = collect($this->syncMap)->map(fn ($field) => $record->getRawOriginal($field))->all();
        $relation = $record->{$this->usageRelation}();

        $packages = (clone $relation)->distinct()->count('package_id');
        $rows = $relation->update($values);

        return ['rows' => $rows, 'packages' => $packages];
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(?Model $record = null): array
    {
        $rules = [];

        foreach ($this->fields as $field) {
            $name = $field['name'];
            $base = ! empty($field['required']) ? ['required'] : ['nullable'];

            $typeRules = match ($field['type']) {
                'text' => ['string', 'max:'.($field['max'] ?? 255)],
                'textarea' => ['string', 'max:'.($field['max'] ?? 5000)],
                'number' => ['integer', 'min:'.($field['min'] ?? 0), 'max:'.($field['maxValue'] ?? 100000)],
                'price' => ['numeric', 'min:0', 'max:99999999'],
                'select' => [Rule::in(array_keys($field['options']))],
                'checkbox' => ['boolean'],
                'url' => ['url', 'max:500'],
                'image' => ['image', 'max:4096'],
                'days' => ['array', 'max:60'],
                default => ['string'],
            };

            $rules[$name] = array_merge($base, $typeRules);

            if ($field['type'] === 'days') {
                $rules["{$name}.*.day_number"] = ['nullable', 'integer', 'min:1', 'max:60'];
                $rules["{$name}.*.date_gregorian"] = ['nullable', 'date'];
                foreach (['date_hijri_label', 'city', 'accommodation_a', 'accommodation_b'] as $text) {
                    $rules["{$name}.*.{$text}"] = ['nullable', 'string', 'max:255'];
                }
                $rules["{$name}.*.notes"] = ['nullable', 'string', 'max:2000'];
            }
        }

        return $rules;
    }

    /** Values from a validated request, ready to save. */
    public function attributesFrom(array $validated, bool $hasFile = false): array
    {
        $attributes = [];

        foreach ($this->fields as $field) {
            $name = $field['name'];

            if ($field['type'] === 'image') {
                continue;
            }

            if ($field['type'] === 'checkbox') {
                $attributes[$name] = ! empty($validated[$name]);

                continue;
            }

            if ($field['type'] === 'days') {
                $attributes[$name] = collect($validated[$name] ?? [])
                    ->filter(fn ($row) => collect($row)->filter(fn ($v) => filled($v))->isNotEmpty())
                    ->values()
                    ->map(fn ($row, $i) => array_merge($row, ['day_number' => filled($row['day_number'] ?? null) ? (int) $row['day_number'] : $i + 1]))
                    ->all();

                continue;
            }

            if (array_key_exists($name, $validated)) {
                $attributes[$name] = $validated[$name];
            }
        }

        return array_merge($attributes, $this->fixed);
    }

    public function formatColumn(Model $record, array $column): string
    {
        $value = $record->{$column['field']};

        return match ($column['format'] ?? 'text') {
            'option' => (string) ($column['options'][$value] ?? Str::headline((string) $value)),
            'stars' => $value ? str_repeat('★', (int) $value) : '—',
            'money' => $value !== null ? trim(($record->currency ?? '').' '.number_format((float) $value)) : 'On request',
            'included' => $value ? 'Included' : 'Extra cost',
            'count' => (string) count((array) $value),
            'limit' => Str::limit((string) $value, 60) ?: '—',
            default => filled($value) ? (string) $value : '—',
        };
    }
}
