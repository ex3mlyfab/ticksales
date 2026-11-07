<?php

namespace App\Enums;

/**
 * Permitted accent colours a tenant may apply on top of the Sokora
 * platform identity.
 *
 * The master brand (Deep Forest, Electric Lime, Midnight, Warm Ivory) is
 * platform-owned and can never be overridden by a tenant. Instead, a
 * tenant may pick one of these curated accents, which flows into the
 * single extension token `--tenant-accent` and renders on tenant-owned
 * surfaces such as storefront chrome and receipts.
 *
 * Every option is a mid-tone hue that keeps AA contrast with the ink
 * tokens in both themes and is deliberately outside the platform
 * identity palette (no Electric Lime, no Deep Forest, no Midnight) and
 * outside danger/status semantics.
 */
enum TenantAccent: string
{
    case Evergreen = '#279C78';
    case Harbor = '#4389C7';
    case Harvest = '#E8A33A';

    /**
     * @return list<string>
     */
    public static function permittedHexes(): array
    {
        return array_map(static fn (self $accent): string => $accent->value, self::cases());
    }

    /**
     * Resolve a stored hex value to its permitted accent, or null when
     * the value is empty or not on the permitted list (the platform
     * default applies instead).
     */
    public static function fromStored(?string $hex): ?self
    {
        if ($hex === null) {
            return null;
        }

        return self::tryFrom(strtoupper(trim($hex)));
    }
}
