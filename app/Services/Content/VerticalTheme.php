<?php

namespace App\Services\Content;

use App\Models\Vertical;

/**
 * Rezolvă identitatea vizuală a unei verticale: culoarea de accent și pictograma.
 *
 * Valorile se citesc din coloana jsonb `verticals.metadata`, care există deja în
 * schemă, deci o verticală nouă își poate aduce accentul fără migrare. Când
 * metadata nu spune nimic, alegerea se face determinist după id, astfel încât
 * aceeași verticală să arate la fel la fiecare cerere.
 */
final class VerticalTheme
{
    /**
     * Accentele aprobate. Toate stau la aceeași luminozitate, ca domeniile să se
     * distingă între ele fără ca site-ul să se destrame în cinci branduri.
     *
     * `solid` se folosește pe miniaturi și etichete pline, `soft` ca fundal
     * pentru pictograme. Toate trec 4,5:1 cu text alb.
     *
     * @var array<string, array{solid: string, soft: string}>
     */
    private const PALETTE = [
        'auto' => ['solid' => '#B4451F', 'soft' => '#FBEEE9'],
        'medical' => ['solid' => '#0D6E63', 'soft' => '#E6F2F0'],
        'legal' => ['solid' => '#7B2D52', 'soft' => '#F5E7EE'],
        'it' => ['solid' => '#0056D2', 'soft' => '#E3EDFC'],
        'lang' => ['solid' => '#8A6114', 'soft' => '#F6EEDC'],
        'transport' => ['solid' => '#1A5E7A', 'soft' => '#EAF1F3'],
        'nature' => ['solid' => '#4C6B1F', 'soft' => '#EFF2EB'],
        'civic' => ['solid' => '#3D4E9E', 'soft' => '#EEEFF6'],
        'finance' => ['solid' => '#1D6B45', 'soft' => '#EBF2EE'],
        'air' => ['solid' => '#265C93', 'soft' => '#EBF0F5'],
        'energy' => ['solid' => '#8C5A17', 'soft' => '#F5F0EA'],
        'signal' => ['solid' => '#6A3A94', 'soft' => '#F2EDF5'],
    ];

    /**
     * Pictogramele disponibile, ca să nu ajungă emoji în interfață.
     *
     * Lista e închisă dinadins: o valoare necunoscută în metadata cade pe
     * `book`, deci o greșeală de scriere nu lasă o verticală fără pictogramă.
     */
    private const ICONS = [
        'wheel', 'truck', 'pulse', 'scales', 'gavel', 'terminal', 'globe', 'book',
        'briefcase', 'chart', 'receipt', 'calculator', 'gauge', 'umbrella',
        'antenna', 'target', 'drone', 'bolt', 'radiation', 'anchor', 'train',
        'wrench', 'building', 'map', 'leaf', 'lightbulb', 'shield', 'cap',
    ];

    /**
     * Numele accentelor acceptate, pentru validare în admin și în seed.
     *
     * @return array<int, string>
     */
    public static function palettes(): array
    {
        return array_keys(self::PALETTE);
    }

    /**
     * @return array{solid: string, soft: string, icon: string}
     */
    public static function for(Vertical $vertical): array
    {
        $metadata = $vertical->metadata ?? [];

        return [
            'solid' => self::solid($metadata, $vertical),
            'soft' => self::soft($metadata, $vertical),
            'icon' => self::icon($metadata),
        ];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private static function solid(array $metadata, Vertical $vertical): string
    {
        $custom = data_get($metadata, 'accent.solid');

        if (self::isHexColor($custom)) {
            return mb_strtoupper((string) $custom);
        }

        return self::fallback($metadata, $vertical)['solid'];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private static function soft(array $metadata, Vertical $vertical): string
    {
        $custom = data_get($metadata, 'accent.soft');

        if (self::isHexColor($custom)) {
            return mb_strtoupper((string) $custom);
        }

        return self::fallback($metadata, $vertical)['soft'];
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array{solid: string, soft: string}
     */
    private static function fallback(array $metadata, Vertical $vertical): array
    {
        $named = data_get($metadata, 'accent.palette');

        if (is_string($named) && isset(self::PALETTE[$named])) {
            return self::PALETTE[$named];
        }

        // Determinist: aceeași verticală primește mereu același accent.
        $keys = array_keys(self::PALETTE);

        return self::PALETTE[$keys[$vertical->id % count($keys)]];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private static function icon(array $metadata): string
    {
        $icon = data_get($metadata, 'icon');

        return is_string($icon) && in_array($icon, self::ICONS, true)
            ? $icon
            : 'book';
    }

    private static function isHexColor(mixed $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1;
    }
}
