<?php

namespace App\Filament\Resources\ApiTokens\Pages;

use App\Filament\Resources\ApiTokens\ApiTokenResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

class ManageApiTokens extends ManageRecords
{
    protected static string $resource = ApiTokenResource::class;

    public ?string $generatedPlainTextToken = null;

    public ?string $generatedTokenName = null;

    public ?string $generatedTokenUserName = null;

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            $this->generateTokenAction(),
        ];
    }

    public function generateTokenAction(): Action
    {
        return Action::make('generateToken')
            ->label('Generate API token')
            ->icon(Heroicon::OutlinedPlus)
            ->modalHeading('Generate API token')
            ->modalDescription('Create a Sanctum token for a user. The full token is shown only once.')
            ->modalSubmitActionLabel('Generate token')
            ->schema([
                Select::make('user_id')
                    ->label('User')
                    ->required()
                    ->searchable()
                    ->options(fn (): array => User::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (User $user): array => [
                            $user->id => "{$user->name} ({$user->email})",
                        ])
                        ->all()),
                TextInput::make('name')
                    ->label('Token name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Android phone')
                    ->helperText('Use a name that identifies the device or app using this token.'),
            ])
            ->action(function (array $data): void {
                /** @var User $user */
                $user = User::query()
                    ->where('is_active', true)
                    ->findOrFail($data['user_id']);

                $newToken = $user->createToken($data['name'], ['*']);

                $this->generatedPlainTextToken = $newToken->plainTextToken;
                $this->generatedTokenName = $data['name'];
                $this->generatedTokenUserName = $user->name;

                $this->replaceMountedAction('showGeneratedToken');
            });
    }

    public function showGeneratedTokenAction(): Action
    {
        return Action::make('showGeneratedToken')
            ->label('API token generated')
            ->color('success')
            ->modalHeading('API token generated')
            ->modalDescription(fn (): string => "Copy the token for {$this->generatedTokenUserName}. It will not be shown again.")
            ->modalContent(fn (): View => view('filament.api-tokens.generated-token', [
                'token' => $this->generatedPlainTextToken,
                'name' => $this->generatedTokenName,
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Done');
    }
}
