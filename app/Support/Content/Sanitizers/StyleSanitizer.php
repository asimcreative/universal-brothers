<?php

namespace App\Support\Content\Sanitizers;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Inline styles are dangerous in general (backgrounds that load URLs, text
 * positioned over other content), so a `style` attribute keeps only the two
 * declarations the editor itself writes: text alignment, and a text colour
 * from the site's own palette. Everything else in the attribute is dropped.
 */
final class StyleSanitizer implements AttributeSanitizerInterface
{
    private const ALIGNMENTS = ['left', 'center', 'right', 'justify'];

    /** @param list<string> $colors lowercase #rrggbb values */
    public function __construct(private readonly array $colors) {}

    public function getSupportedElements(): ?array
    {
        return null;
    }

    public function getSupportedAttributes(): ?array
    {
        return ['style'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        $kept = [];

        foreach (explode(';', $value) as $declaration) {
            [$property, $propertyValue] = array_pad(array_map('trim', explode(':', $declaration, 2)), 2, '');
            $property = strtolower($property);
            $propertyValue = strtolower($propertyValue);

            if ($property === 'text-align' && in_array($propertyValue, self::ALIGNMENTS, true)) {
                $kept['text-align'] = $propertyValue;
            } elseif ($property === 'color' && ($hex = $this->hex($propertyValue)) !== null && in_array($hex, $this->colors, true)) {
                $kept['color'] = $hex;
            }
        }

        if ($kept === []) {
            return null;
        }

        return collect($kept)->map(fn ($v, $k) => "{$k}: {$v}")->implode('; ');
    }

    /** Accepts #rrggbb or rgb(r, g, b), which is how browsers report a colour back. */
    private function hex(string $value): ?string
    {
        if (preg_match('/^#[0-9a-f]{6}$/', $value)) {
            return $value;
        }

        if (preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/', $value, $m)) {
            return sprintf('#%02x%02x%02x', min(255, (int) $m[1]), min(255, (int) $m[2]), min(255, (int) $m[3]));
        }

        return null;
    }
}
