<?php

declare(strict_types=1);

namespace App\Enums;

enum TaxType: string
{
    case Itbis = 'itbis';
    case Isc = 'isc';
    case PropinaLegal = 'propina_legal';
    case Exento = 'exento';
    case NoFacturable = 'no_facturable';
    case Other = 'other';

    /**
     * Get all options as array.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }

    /**
     * Get all exclusive tax types.
     *
     * @return array<int, self>
     */
    public static function exclusiveTypes(): array
    {
        return array_filter(self::cases(), fn (self $type): bool => $type->isExclusive());
    }

    /**
     * Get all accumulative tax types.
     *
     * @return array<int, self>
     */
    public static function accumulativeTypes(): array
    {
        return array_filter(self::cases(), fn (self $type): bool => $type->isAccumulative());
    }

    /**
     * Get the label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Itbis => 'ITBIS',
            self::Isc => 'ISC',
            self::PropinaLegal => 'Propina Legal',
            self::Exento => 'Exento',
            self::NoFacturable => 'No Facturable',
            self::Other => 'Otro',
        };
    }

    /**
     * Determines if this tax type is exclusive.
     * Exclusive types can only have one tax selected per item.
     * For example, ITBIS and Exento are mutually exclusive - you can't have both.
     */
    public function isExclusive(): bool
    {
        return match ($this) {
            self::Itbis, self::Isc, self::Exento, self::NoFacturable => true,
            self::PropinaLegal, self::Other => false,
        };
    }

    /**
     * Determines if this tax type is accumulative.
     * Accumulative types can be added on top of exclusive taxes.
     * For example, Propina Legal can be added alongside ITBIS.
     */
    public function isAccumulative(): bool
    {
        return ! $this->isExclusive();
    }
}
