<?php

namespace App\Filament\Pages\Concerns;

use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

/**
 * Punte către ecranele de administrare scrise înainte de Filament.
 *
 * Migrarea celor paisprezece manageri deodată ar însemna un panou pe jumătate
 * rupt pentru o vreme. În loc de asta, fiecare manager rămas primește o pagină
 * Filament subțire, care randează componenta Livewire existentă în interiorul
 * panoului. Administratorul are de la început un singur loc, iar migrarea reală
 * se face ecran cu ecran, fără presiune.
 */
trait WrapsLegacyManager
{
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.pages.legacy-manager')
                ->viewData(['livewireComponent' => static::legacyComponent()]),
        ]);
    }

    abstract protected static function legacyComponent(): string;
}
