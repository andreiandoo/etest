<?php

namespace App\Filament\Pages;

use App\Services\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Identificatorii de măsurare și de urmărire publicitară.
 *
 * Stau în baza de date, nu în .env, fiindcă se schimbă de către oameni care nu
 * au acces la server, iar o corecție de ID nu trebuie să ceară un deploy.
 */
class Analytics extends Page
{
    protected static ?string $slug = 'analytics';

    protected static ?string $navigationLabel = 'Analytics și Pixel';

    protected static ?string $title = 'Analytics și Pixel';

    protected static string|UnitEnum|null $navigationGroup = 'Platformă';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?int $navigationSort = 30;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'analytics_enabled' => Settings::boolean('analytics_enabled', false),
            'google_analytics_id' => Settings::string('google_analytics_id'),
            'google_tag_manager_id' => Settings::string('google_tag_manager_id'),
            'facebook_pixel_id' => Settings::string('facebook_pixel_id'),
            'google_site_verification' => Settings::string('google_site_verification'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Comutator general')
                    ->description('Cât timp e oprit, nu se încarcă niciun script de urmărire, indiferent ce identificatori sunt completați mai jos. Ține-l oprit până la lansare.')
                    ->schema([
                        Toggle::make('analytics_enabled')
                            ->label('Încarcă scripturile de măsurare'),
                    ]),

                Section::make('Google')
                    ->schema([
                        TextInput::make('google_analytics_id')
                            ->label('ID Google Analytics 4')
                            ->placeholder('G-XXXXXXXXXX')
                            ->helperText('Îl găsești în Admin → Fluxuri de date.')
                            ->rules(['nullable', 'regex:/^G-[A-Z0-9]{6,15}$/i']),

                        TextInput::make('google_tag_manager_id')
                            ->label('ID Google Tag Manager')
                            ->placeholder('GTM-XXXXXXX')
                            ->helperText('Opțional. Dacă folosești GTM, pune analytics-ul în el, nu aici.')
                            ->rules(['nullable', 'regex:/^GTM-[A-Z0-9]{5,12}$/i']),

                        TextInput::make('google_site_verification')
                            ->label('Cod de verificare Search Console')
                            ->placeholder('doar valoarea, fără eticheta meta')
                            ->helperText('Folosește-l dacă alegi metoda „etichetă HTML” în Search Console.')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Meta')
                    ->schema([
                        TextInput::make('facebook_pixel_id')
                            ->label('ID Meta Pixel')
                            ->placeholder('doar cifre')
                            ->helperText('Îl găsești în Events Manager.')
                            ->rules(['nullable', 'regex:/^[0-9]{10,20}$/']),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make($this->getFormActions())
                        ->alignment('start'),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Settings::put([
            'analytics_enabled' => (bool) ($data['analytics_enabled'] ?? false),
            'google_analytics_id' => trim((string) ($data['google_analytics_id'] ?? '')),
            'google_tag_manager_id' => trim((string) ($data['google_tag_manager_id'] ?? '')),
            'facebook_pixel_id' => trim((string) ($data['facebook_pixel_id'] ?? '')),
            'google_site_verification' => trim((string) ($data['google_site_verification'] ?? '')),
        ]);

        Notification::make()
            ->title('Setările au fost salvate')
            ->success()
            ->send();
    }

    /**
     * @return array<int, Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Salvează')
                ->submit('form'),
        ];
    }
}
