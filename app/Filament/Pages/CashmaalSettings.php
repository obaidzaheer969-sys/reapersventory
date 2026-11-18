<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
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
class CashmaalSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'Earning';

    protected static ?string $navigationLabel = 'Cashmaal settings';

    protected static ?string $title = 'Cashmaal settings';

    protected static ?string $slug = 'cashmaal-settings';

    protected static ?int $navigationSort = 4;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'cashmaal_enabled' => (bool) SystemSetting::getValue(
                'cashmaal_enabled',
                (bool) config('earning.deposit.cashmaal.enabled', true),
            ),
            'cashmaal_web_id' => (string) SystemSetting::getValue('cashmaal_web_id', ''),
            'cashmaal_ipn_key' => (string) SystemSetting::getValue('cashmaal_ipn_key', ''),
            'cashmaal_pay_method' => (string) SystemSetting::getValue(
                'cashmaal_pay_method',
                (string) config('earning.deposit.cashmaal.pay_method', ''),
            ),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Toggle::make('cashmaal_enabled')
                ->label('Enable Cashmaal deposits')
                ->helperText('When off, users cannot start new deposits.'),
            TextInput::make('cashmaal_web_id')
                ->label('Cashmaal Web ID')
                ->required()
                ->maxLength(120)
                ->helperText('The Web ID you get after adding this site in Cashmaal merchant settings.'),
            TextInput::make('cashmaal_ipn_key')
                ->label('IPN key')
                ->password()
                ->revealable()
                ->required()
                ->maxLength(255)
                ->helperText('Cashmaal\'s IPN key for verifying IPN notifications.'),
            TextInput::make('cashmaal_pay_method')
                ->label('Pay method (optional)')
                ->maxLength(60)
                ->helperText('Leave empty to let Cashmaal show its default methods.'),
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
            'cashmaal_enabled',
            (bool) ($data['cashmaal_enabled'] ?? false),
            'earning',
            'Enable Cashmaal wallet deposits',
        );

        SystemSetting::setValue(
            'cashmaal_web_id',
            trim((string) ($data['cashmaal_web_id'] ?? '')),
            'earning',
            'Cashmaal merchant Web ID',
        );

        SystemSetting::setValue(
            'cashmaal_ipn_key',
            trim((string) ($data['cashmaal_ipn_key'] ?? '')),
            'earning',
            'Cashmaal IPN verification key',
        );

        SystemSetting::setValue(
            'cashmaal_pay_method',
            trim((string) ($data['cashmaal_pay_method'] ?? '')),
            'earning',
            'Cashmaal payment method (optional)',
        );

        Notification::make()
            ->title('Cashmaal settings saved')
            ->success()
            ->send();
    }
}
