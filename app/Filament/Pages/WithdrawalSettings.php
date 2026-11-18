<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class WithdrawalSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'Earning';

    protected static ?string $navigationLabel = 'Withdrawal settings';

    protected static ?string $title = 'Withdrawal settings';

    protected static ?string $slug = 'withdrawal-settings';

    protected static ?int $navigationSort = 6;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'withdrawals_enabled' => (bool) SystemSetting::getValue('withdrawals_enabled', true),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Toggle::make('withdrawals_enabled')
                ->label('Show withdrawals to users')
                ->helperText('When off, withdrawal buttons and pages are hidden for all users and new payout requests are blocked. Pending payouts can still be processed in the admin panel.'),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFormContentComponent(),
        ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make([
                    Action::make('save')
                        ->label('Save settings')
                        ->submit('save'),
                ])->alignment(Alignment::End),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SystemSetting::setValue(
            'withdrawals_enabled',
            (bool) ($data['withdrawals_enabled'] ?? true),
            'earning',
            'Show the withdrawal system to users',
        );

        Notification::make()
            ->title('Withdrawal settings saved')
            ->success()
            ->send();
    }
}
