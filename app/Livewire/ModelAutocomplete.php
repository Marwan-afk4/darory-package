<?php

namespace App\Livewire;

use Livewire\Component;

class ModelAutocomplete extends Component
{
    public string $query = '';
    public array $results = [];

    public ?int $selectedId = null;
    public ?string $selectedName = null;
    public bool $showError = false;

    public string $modelClass;
    public array $searchFields = [];
    public string|array $displayField = 'name';
    public string $inputId = 'autocomplete-input';
    public string $label = '';
    public string $hiddenInputName = 'selected_id';
    public string $emitEvent = 'modelSelected';

    public function mount(
        string $modelClass,
        array $searchFields = ['name'],
        string|array $displayField = 'name',
        string $inputId = 'autocomplete-input',
        ?string $label = null,
        string $hiddenInputName = 'selected_id',
        string $emitEvent = 'modelSelected',
        ?int $selectedId = null,
        ?string $selectedName = ''
    ) {
        $this->modelClass = $modelClass;
        $this->searchFields = $searchFields;
        $this->displayField = $displayField;
        $this->inputId = $inputId;
        $this->label = $label ?? '';
        $this->hiddenInputName = $hiddenInputName;
        $this->emitEvent = $emitEvent;
        $this->selectedId = $selectedId;
        $this->selectedName = $selectedName;
        $this->query = $selectedName ?? '';
    }

    public function updatedQuery($value)
    {
        if ($this->selectedName !== $value) {
            $this->resetSelectionState();
        }

        if (strlen($value) < 2) {
            $this->results = [];
            return;
        }

        $model = $this->modelClass;
        $query = $model::query();

        $this->applySearchConditions($query, $value);
        $this->eagerLoadDisplayRelations($query);

        $this->results = $query->limit(5)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $this->getDisplayName($item),
            ];
        })->toArray();
    }

    public function select($id)
    {
        $model = $this->modelClass;
        $item = $model::find($id);

        if ($item) {
            $this->selectedId = $item->id;
            $this->selectedName = $this->getDisplayName($item);
            $this->query = $this->selectedName;
            $this->results = [];
            $this->showError = false;

            $this->dispatch(event: $this->emitEvent, id: $item->id);
        }
    }

    public function validateOnBlur()
    {
        if (empty($this->query)) {
            $this->resetSelectionState();
            return;
        }

        if (!$this->selectedId || $this->query !== $this->selectedName) {
            $this->resetSelectionState();
            $this->query = '';
            $this->showError = true;
        }
    }

    public function render()
    {
        return view('livewire.model-autocomplete');
    }

    /** ---------------------
     *     Private Helpers
     * --------------------*/

    private function resetSelectionState(): void
    {
        $this->selectedId = null;
        $this->selectedName = '';
        $this->showError = false;
    }

    private function applySearchConditions($query, string $value): void
    {
        foreach ($this->searchFields as $field) {
            if (str_contains($field, '.')) {
                [$relation, $relField] = explode('.', $field, 2);
                $query->orWhereHas($relation, function ($q) use ($relField, $value) {
                    $q->where($relField, 'like', "%{$value}%");
                });
            } else {
                $query->orWhere($field, 'like', "%{$value}%");
            }
        }
    }

    private function eagerLoadDisplayRelations($query): void
    {
        $fields = is_array($this->displayField) ? $this->displayField : [$this->displayField];
        $relations = [];

        foreach ($fields as $field) {
            if (str_contains($field, '.')) {
                $relation = explode('.', $field)[0];

                // Check if it's a valid relation method on the model
                if (method_exists($this->modelClass, $relation)) {
                    $relations[] = $relation;
                }
            }
        }

        if (!empty($relations)) {
            $query->with(array_unique($relations));
        }
    }


    private function getDisplayName($item): string
    {
        $fields = (array) $this->displayField;

        $parts = [];

        foreach ($fields as $fieldPath) {
            $value = data_get($item, $fieldPath);

            // Try to resolve if it's an enum cast
            $model = $item;
            $segments = explode('.', $fieldPath);
            $field = array_pop($segments);

            // Traverse relations to reach the correct model
            foreach ($segments as $relation) {
                if (!method_exists($model, $relation)) {
                    $model = null;
                    break;
                }

                $model = $model->$relation;
                if (!is_object($model)) {
                    break;
                }
            }

            if ($model && is_object($model) && $this->isEnumCast($model, $field, $value)) {
                $parts[] = method_exists($value, 'label') ? $value->label() : $value->name;
            } elseif (is_scalar($value)) {
                $parts[] = trim((string) $value);
            }
        }

        return implode(' - ', array_filter($parts, fn($part) => $part !== ''));
    }


    private function isEnumCast($model, string $field, $value): bool
    {
        if (!method_exists($model, 'getCasts')) {
            return false;
        }

        $casts = $model->getCasts();

        if (!isset($casts[$field])) {
            return false;
        }

        $cast = $casts[$field];

        if (is_string($cast) && enum_exists($cast)) {
            return is_object($value) && $value instanceof \BackedEnum;
        }

        return false;
    }
}
