<?php

namespace App\Livewire\Business;

use App\Livewire\Concerns\RefreshesAfterSave;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CategoryForm extends Component
{
    use RefreshesAfterSave;

    public string $name = '';

    public ?string $description = null;

    public function save(): void
    {
        $user = Auth::user();
        abort_unless($user->can('create', Category::class), 403);

        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where(
                    fn ($q) => $q->where('business_id', $user->business_id)->whereNull('deleted_at')
                ),
            ],
            'description' => ['nullable', 'string'],
        ]);

        Category::query()->create([
            'business_id' => $user->business_id,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(4)),
            'description' => $validated['description'],
            'is_active' => true,
        ]);

        session()->flash('status', 'Category created.');
        $this->redirectAndRefresh(route('business.categories.index'));
    }

    public function render(): View
    {
        return view('livewire.business.category-form');
    }
}
