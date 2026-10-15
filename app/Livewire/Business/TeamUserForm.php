<?php

namespace App\Livewire\Business;

use App\Livewire\Concerns\RefreshesAfterSave;
use App\Models\User;
use App\Services\TeamUserService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class TeamUserForm extends Component
{
    use RefreshesAfterSave;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public function save(TeamUserService $teamUsers): void
    {
        $user = Auth::user();
        abort_unless($user->can('create', User::class), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
        ]);

        try {
            $teamUsers->create($user->business, $user, $validated);
        } catch (ValidationException $e) {
            $this->addValidationException($e, 'email');

            return;
        }

        session()->flash('status', 'Team user created.');
        $this->redirectAndRefresh(route('business.team-users.index'));
    }

    public function render(): View
    {
        return view('livewire.business.team-user-form');
    }
}
