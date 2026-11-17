<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
class SiteAdsSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Earning';

    protected static ?string $navigationLabel = 'Site ads & approval';

    protected static ?string $title = 'Site ads & approval';

    protected static ?string $slug = 'site-ads-settings';

    protected static ?int $navigationSort = 5;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'site_approved' => (bool) SystemSetting::getValue('site_approved', false),
            'ads_enabled' => (bool) SystemSetting::getValue('ads_enabled', false),
            'ad_provider' => (string) SystemSetting::getValue('ad_provider', 'custom'),
            'ad_code' => (string) SystemSetting::getValue('ad_code', ''),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Toggle::make('site_approved')
                ->label('Website Approved')
                ->helperText('On karne par public pages par “Approved” badge dikhayega.')
                ->live(),
            Toggle::make('ads_enabled')
                ->label('Run site ads')
                ->helperText('On karne par neeche diya gaya ad code public pages par chalega.')
                ->live(),
            Select::make('ad_provider')
                ->label('Ad provider')
                ->options([
                    'custom' => 'Custom HTML / Script',
                    'adsense' => 'Google AdSense',
                    'other' => 'Other network',
                ])
                ->default('custom')
                ->required(),
            Textarea::make('ad_code')
                ->label('Ad code')
                ->rows(8)
                ->helperText('Approved network ka ad script ya banner HTML paste karein. Site header ke niche show hoga.')
                ->visible(fn (): bool => (bool) ($this->data['ads_enabled'] ?? false))
                ->required(fn (): bool => (bool) ($this->data['ads_enabled'] ?? false)),
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
        $adsEnabled = (bool) ($data['ads_enabled'] ?? false);
        $adCode = trim((string) ($data['ad_code'] ?? ''));

        SystemSetting::setValue(
            'site_approved',
            (bool) ($data['site_approved'] ?? false),
            'earning',
            'Show the website Approved badge',
        );

        SystemSetting::setValue(
            'ads_enabled',
            $adsEnabled,
            'earning',
            'Run admin-provided ads on public pages',
        );

        SystemSetting::setValue(
            'ad_provider',
            (string) ($data['ad_provider'] ?? 'custom'),
            'earning',
            'Ad network used for site ads',
        );

        SystemSetting::setValue(
            'ad_code',
            $adsEnabled ? $adCode : '',
            'earning',
            'Ad script or HTML shown on public pages',
        );

        Notification::make()
            ->title('Site ads settings saved')
            ->success()
            ->send();
    }
}
