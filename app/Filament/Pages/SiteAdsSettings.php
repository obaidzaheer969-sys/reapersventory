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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class SiteAdsSettings extends Page
{
    private const SLOT_FIELDS = [
        'ad_slot_top' => 'Top banner — header ke niche (sab pages)',
        'ad_slot_content' => 'Content banner — page content se pehle',
        'ad_slot_bottom' => 'Bottom banner — content ke baad',
        'ad_slot_footer' => 'Footer banner — footer se pehle',
    ];

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
            'site_verification_tag' => (string) SystemSetting::getValue('site_verification_tag', ''),
            'ads_enabled' => (bool) SystemSetting::getValue('ads_enabled', false),
            'ad_provider' => (string) SystemSetting::getValue('ad_provider', 'custom'),
            'ad_auto_code' => (string) SystemSetting::getValue('ad_auto_code', ''),
            ...collect(self::SLOT_FIELDS)
                ->mapWithKeys(fn (string $label, string $key): array => [
                    $key => (string) SystemSetting::getValue($key, ''),
                ])
                ->all(),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Website approval')
                ->description('Ad network verification tag yahan save karein. Iske bina ads nahi chalenge.')
                ->schema([
                    Toggle::make('site_approved')
                        ->label('Website Approved')
                        ->helperText('On karne par public pages par “Approved” badge dikhayega.')
                        ->live(),
                    Textarea::make('site_verification_tag')
                        ->label('Verification tag')
                        ->rows(3)
                        ->placeholder('<meta name="google-site-verification" content="...">')
                        ->helperText('Ad network ka meta tag ya verification snippet yahan paste karein. Ye har page ke <head> me show hoga.')
                        ->columnSpanFull()
                        ->required(fn (): bool => (bool) ($this->data['ads_enabled'] ?? false)),
                ]),
            Section::make('Ads')
                ->description('Approved hona aur verification tag save hona zaroori hai. Har slot alag se chalaya ja sakta hai.')
                ->schema([
                    Toggle::make('ads_enabled')
                        ->label('Run site ads')
                        ->helperText('Master switch. Off karne par koi bhi ad nahi chalega.')
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
                    Textarea::make('ad_auto_code')
                        ->label('Head / auto ads code')
                        ->rows(6)
                        ->helperText('AdSense auto ads script ya custom head script. Approved aur verification tag hone par hi chalega.')
                        ->columnSpanFull()
                        ->visible(fn (): bool => (bool) ($this->data['ads_enabled'] ?? false)),
                    ...collect(self::SLOT_FIELDS)
                        ->map(fn (string $label, string $key) => Textarea::make($key)
                            ->label($label)
                            ->rows(4)
                            ->helperText('Khali chhorein to ye slot band rahega. Ad network ka banner/script yahan paste karein.')
                            ->columnSpanFull()
                            ->visible(fn (): bool => (bool) ($this->data['ads_enabled'] ?? false)))
                        ->all(),
                ]),
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
            'site_approved',
            (bool) ($data['site_approved'] ?? false),
            'earning',
            'Show the website Approved badge',
        );

        SystemSetting::setValue(
            'site_verification_tag',
            trim((string) ($data['site_verification_tag'] ?? '')),
            'earning',
            'Ad network site verification tag',
        );

        SystemSetting::setValue(
            'ads_enabled',
            (bool) ($data['ads_enabled'] ?? false),
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
            'ad_auto_code',
            trim((string) ($data['ad_auto_code'] ?? '')),
            'earning',
            'Ad script shown in the page head',
        );

        foreach (array_keys(self::SLOT_FIELDS) as $key) {
            SystemSetting::setValue(
                $key,
                trim((string) ($data[$key] ?? '')),
                'earning',
                'Ad slot code: '.$key,
            );
        }

        Notification::make()
            ->title('Site ads settings saved')
            ->success()
            ->send();
    }
}
