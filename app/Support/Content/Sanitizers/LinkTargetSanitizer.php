<?php

namespace App\Support\Content\Sanitizers;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/** A link may open in a new tab, and nothing else: `target` accepts only "_blank". */
final class LinkTargetSanitizer implements AttributeSanitizerInterface
{
    public function getSupportedElements(): ?array
    {
        return ['a'];
    }

    public function getSupportedAttributes(): ?array
    {
        return ['target'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        return strtolower(trim($value)) === '_blank' ? '_blank' : null;
    }
}
