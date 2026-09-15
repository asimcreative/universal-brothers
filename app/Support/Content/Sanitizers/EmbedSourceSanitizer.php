<?php

namespace App\Support\Content\Sanitizers;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/** An embedded frame may only show a YouTube (privacy-enhanced) or Vimeo player. */
final class EmbedSourceSanitizer implements AttributeSanitizerInterface
{
    public function __construct(private readonly string $pattern) {}

    public function getSupportedElements(): ?array
    {
        return ['iframe'];
    }

    public function getSupportedAttributes(): ?array
    {
        return ['src', 'allowfullscreen'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        if ($attribute === 'allowfullscreen') {
            return 'true';
        }

        return preg_match($this->pattern, trim($value)) === 1 ? trim($value) : null;
    }
}
