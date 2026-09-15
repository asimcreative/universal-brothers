<?php

namespace App\Support\PageBuilder;

use App\Models\ContentBlock;
use App\Models\MediaItem;
use App\Support\Content\RichText;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Turns submitted page-builder sections into clean, typed data, and explains
 * every problem in words an office administrator understands.
 *
 * Two modes:
 *   lenient (saving a draft)  nothing typed is ever thrown away. Problems are
 *                             returned as warnings and the draft still saves.
 *   strict  (publishing)      the same problems are errors, and publishing stops.
 *
 * A hidden section is never shown to visitors, so its problems are always
 * warnings — they become errors only when it is shown again and published.
 *
 * Output shape of one section:
 *   ['id' => 's_ab12cd34', 'type' => 'text', 'visible' => true, 'data' => [...]]
 */
class SectionValidator
{
    public const ID_PATTERN = '/^s_[a-z0-9]{6,24}$/';

    private const MAX_SECTIONS = 60;

    /** @var array<string, string> */
    private array $errors = [];

    /** @var list<array{key: string, message: string, section: string|null}> */
    private array $warnings = [];

    public function __construct(private readonly bool $strict) {}

    public static function newId(): string
    {
        return 's_'.Str::lower(Str::random(10));
    }

    /**
     * @param  array  $input  sections as submitted (keyed by section id, in page order) or as stored (a list)
     * @return array{sections: list<array>, errors: array<string, string>, warnings: list<array>}
     */
    public function validate(array $input): array
    {
        $this->errors = [];
        $this->warnings = [];
        $sections = [];
        $seen = [];
        $position = 0;

        foreach ($input as $key => $raw) {
            if (! is_array($raw)) {
                continue;
            }

            $type = (string) ($raw['type'] ?? '');
            $definition = BlockRegistry::find($type);

            if (! $definition) {
                $this->warn('sections', 'A section of an unknown kind was removed.', null);

                continue;
            }

            $id = (string) ($raw['id'] ?? $key);
            if (! preg_match(self::ID_PATTERN, $id) || isset($seen[$id])) {
                $id = self::newId();
            }
            $seen[$id] = true;
            $position++;

            if ($position > self::MAX_SECTIONS) {
                $this->problem("sections.{$id}", 'A page can have at most '.self::MAX_SECTIONS.' sections. Remove some sections or split the content into two pages.', $id, true);
                break;
            }

            $visible = filter_var($raw['visible'] ?? true, FILTER_VALIDATE_BOOL);
            $prefix = 'Section '.$position.' ('.$definition['name'].')';
            $data = is_array($raw['data'] ?? null) ? $raw['data'] : [];

            $clean = $this->fields($definition['fields'], $data, "sections.{$id}.data", $prefix, $id, $visible);

            if ($type === 'saved_block') {
                $this->checkLinkedBlock($clean, "sections.{$id}.data.block_id", $prefix, $id, $visible);
            }

            $sections[] = ['id' => $id, 'type' => $type, 'visible' => $visible, 'data' => $clean];
        }

        return ['sections' => $sections, 'errors' => $this->errors, 'warnings' => $this->warnings];
    }

    /** Validate one saved section (the saved-sections screen) the same way. */
    public function validateBlock(string $type, array $data): array
    {
        $result = $this->validate([['id' => 's_savedblock', 'type' => $type, 'visible' => true, 'data' => $data]]);

        return [
            'data' => $result['sections'][0]['data'] ?? BlockRegistry::defaults($type),
            'errors' => collect($result['errors'])->mapWithKeys(fn ($m, $k) => [Str::after($k, 'sections.s_savedblock.') => Str::ucfirst(Str::after($m, '): '))])->all(),
            'warnings' => $result['warnings'],
        ];
    }

    private function fields(array $fields, array $data, string $path, string $prefix, string $sectionId, bool $visible): array
    {
        $clean = [];

        foreach ($fields as $field) {
            $name = $field['name'];
            $clean[$name] = $this->field($field, $data[$name] ?? null, "{$path}.{$name}", $prefix, $sectionId, $visible);
        }

        // Required checks run after every field is read, so `show_when` and
        // button pairs can look at their neighbours' cleaned values.
        foreach ($fields as $field) {
            if (! $this->applies($field, $clean)) {
                continue;
            }

            $name = $field['name'];
            $label = $field['label'];
            $empty = $this->isEmpty($field, $clean[$name]);

            if (! empty($field['required']) && $empty) {
                $this->problem("{$path}.{$name}", "{$prefix}: {$this->bare($label)} is empty. ".$this->howToFill($field), $sectionId, $visible);
            } elseif ($field['type'] === 'items' && ! empty($field['min_items']) && count($clean[$name]) < $field['min_items']) {
                $this->problem("{$path}.{$name}", "{$prefix}: add at least {$field['min_items']} ".Str::plural(Str::lower($field['item_label'] ?? 'item'), $field['min_items']).'.', $sectionId, $visible);
            }

            if (! empty($field['pair']) && ! $empty && $this->isEmpty(['type' => 'text'], $clean[$field['pair']] ?? '')) {
                $pairLabel = collect($fields)->firstWhere('name', $field['pair'])['label'] ?? 'the other button field';
                $this->problem("{$path}.{$field['pair']}", "{$prefix}: {$this->bare($label)} is filled in but {$this->bare($pairLabel)} is empty. Fill in both, or clear both to hide the button.", $sectionId, $visible);
            }
        }

        return $clean;
    }

    private function field(array $field, mixed $value, string $key, string $prefix, string $sectionId, bool $visible): mixed
    {
        $label = $this->bare($field['label']);

        switch ($field['type']) {
            case 'text':
            case 'textarea':
                $text = $this->text($value);
                $max = $field['max'] ?? ($field['type'] === 'text' ? 255 : 1000);
                if (mb_strlen($text) > $max) {
                    $this->problem($key, "{$prefix}: {$label} is ".mb_strlen($text)." characters long. Shorten it to {$max} characters or fewer.", $sectionId, $visible);
                }

                return $text;

            case 'richtext':
                $html = RichText::clean(is_string($value) ? $value : '', $field['profile'] ?? 'standard') ?? '';
                $max = $field['max'] ?? 20000;
                if (mb_strlen($html) > $max) {
                    $this->problem($key, "{$prefix}: {$label} is too long for one section. Split it into two text sections.", $sectionId, $visible);
                }

                return $html;

            case 'link':
                $link = $this->text($value);
                if ($link !== '' && ! self::isValidLink($link)) {
                    $this->problem($key, "{$prefix}: {$label} “".Str::limit($link, 60).'” is not a link the website can open. '.self::linkSuggestion($link), $sectionId, $visible);
                }

                return $link;

            case 'video':
                $url = $this->text($value);
                if ($url !== '' && VideoEmbed::embedUrl($url) === null) {
                    $this->problem($key, "{$prefix}: {$label} is not a YouTube or Vimeo video link. Open the video, copy the address from the browser bar and paste it here — for example https://www.youtube.com/watch?v=abc123.", $sectionId, $visible);
                }

                return $url;

            case 'image':
                return $this->image($field, $value, $key, $prefix, $sectionId, $visible);

            case 'select':
            case 'icon':
                $options = BlockRegistry::options($field);
                $choice = is_scalar($value) ? (string) $value : '';

                return array_key_exists($choice, $options) ? $choice : (string) ($field['default'] ?? array_key_first($options) ?? '');

            case 'toggle':
                return $value === null ? (bool) ($field['default'] ?? false) : filter_var($value, FILTER_VALIDATE_BOOL);

            case 'number':
            case 'hidden':
                if ($value === null || $value === '') {
                    return null;
                }
                if (! is_numeric($value)) {
                    $this->problem($key, "{$prefix}: {$label} must be a number.", $sectionId, $visible);

                    return null;
                }
                $number = (int) $value;
                if (isset($field['min']) && $number < $field['min']) {
                    $number = $field['min'];
                }
                if (isset($field['max']) && $number > $field['max']) {
                    $number = $field['max'];
                }

                return $number;

            case 'items':
                $items = [];
                $list = is_array($value) ? array_values(array_filter($value, 'is_array')) : [];
                $max = $field['max_items'] ?? 12;

                foreach ($list as $i => $item) {
                    $itemPrefix = $prefix.', '.($field['item_label'] ?? 'item').' '.($i + 1);
                    $items[] = $this->fields($field['fields'], $item, "{$key}.{$i}", $itemPrefix, $sectionId, $visible);
                }

                if (count($items) > $max) {
                    $this->problem($key, "{$prefix}: {$label} can have at most {$max} ".Str::plural(Str::lower($field['item_label'] ?? 'item'), $max).'. Remove '.(count($items) - $max).'.', $sectionId, $visible);
                }

                return $items;
        }

        return null;
    }

    private function image(array $field, mixed $value, string $key, string $prefix, string $sectionId, bool $visible): array
    {
        $path = is_array($value) ? $this->text($value['path'] ?? '') : $this->text($value);
        $alt = is_array($value) ? $this->text($value['alt'] ?? '') : '';
        $label = $this->bare($field['label']);

        if ($path !== '' && ! self::isKnownImage($path)) {
            $this->problem("{$key}.path", "{$prefix}: the image chosen for {$label} could no longer be found. Choose the image again.", $sectionId, $visible);
        }

        if (mb_strlen($alt) > 255) {
            $this->problem("{$key}.alt", "{$prefix}: the description of {$label} is too long. Keep it under 255 characters — one clear sentence is enough.", $sectionId, $visible);
        }

        if ($path !== '' && ($field['alt'] ?? true) && $alt === '') {
            $this->problem("{$key}.alt", "{$prefix}: describe {$label} for people who cannot see it. For example “Pilgrims performing tawaf around the Kaaba”.", $sectionId, $visible);
        }

        return ['path' => $path === '' ? null : $path, 'alt' => $alt];
    }

    private function checkLinkedBlock(array $data, string $key, string $prefix, string $sectionId, bool $visible): void
    {
        $block = $data['block_id'] ? ContentBlock::find($data['block_id']) : null;

        if (! $block) {
            $this->problem($key, "{$prefix}: the saved section it links to has been deleted. Delete this section, or insert another saved section.", $sectionId, $visible);
        } elseif ($block->is_archived) {
            $this->problem($key, "{$prefix}: the saved section “{$block->name}” is archived, so it would not appear. Restore it under Saved Sections, or delete this section.", $sectionId, $visible);
        }
    }

    /** Does this field count for the current section, given its `show_when` rule? */
    private function applies(array $field, array $clean): bool
    {
        foreach ($field['show_when'] ?? [] as $other => $wanted) {
            if ((string) ($clean[$other] ?? '') !== (string) $wanted) {
                return false;
            }
        }

        return true;
    }

    private function isEmpty(array $field, mixed $value): bool
    {
        return match ($field['type']) {
            'image' => blank($value['path'] ?? null),
            'items' => $value === [],
            'toggle' => false,
            'richtext' => RichText::isEmpty($value),
            default => $value === null || trim((string) $value) === '',
        };
    }

    private function howToFill(array $field): string
    {
        return match ($field['type']) {
            'image' => 'Choose an image, or delete the section if it is not needed.',
            'items' => 'Add at least one '.Str::lower($field['item_label'] ?? 'item').'.',
            'link' => 'Add a link such as /contact or https://example.com.',
            'video' => 'Paste a YouTube or Vimeo link.',
            default => 'Fill it in, or delete the section if it is not needed.',
        };
    }

    private function problem(string $key, string $message, ?string $sectionId, bool $visible): void
    {
        if ($this->strict && $visible) {
            $this->errors[$key] ??= $message;
        } else {
            $this->warn($key, $message, $sectionId);
        }
    }

    private function warn(string $key, string $message, ?string $sectionId): void
    {
        $this->warnings[] = ['key' => $key, 'message' => $message, 'section' => $sectionId];
    }

    private function text(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        // Control characters (other than line breaks and tabs) never belong in page text.
        return trim(preg_replace('/[^\P{C}\n\t]/u', '', str_replace("\r\n", "\n", (string) $value)) ?? '');
    }

    private function bare(string $label): string
    {
        return Str::lcfirst(trim(preg_replace('/\s*\(optional\)$/i', '', $label)));
    }

    /**
     * A link visitors can follow: a page on this site ("/contact"), a place on
     * the same page ("#prices"), a full web address, an email or a phone link.
     */
    public static function isValidLink(string $link): bool
    {
        if (preg_match('~^/(?!/)[^\s<>"]*$~', $link) || preg_match('/^#[A-Za-z][\w-]*$/', $link)) {
            return true;
        }

        if (preg_match('/^mailto:[^\s@<>"]+@[^\s@<>"]+\.[^\s@<>"]+$/i', $link) || preg_match('/^tel:\+?[0-9 ()-]{5,20}$/i', $link)) {
            return true;
        }

        if (! preg_match('~^https?://~i', $link) || filter_var($link, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $host = parse_url($link, PHP_URL_HOST);

        return is_string($host) && (str_contains($host, '.') || $host === 'localhost');
    }

    public static function linkSuggestion(string $link): string
    {
        if (preg_match('/^www\./i', $link) || preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)+(\/.*)?$/i', $link)) {
            return 'Add https:// at the start, like https://'.ltrim($link, '/').'.';
        }

        if (preg_match('/^[\w@.+-]+@[\w-]+\.[\w.]+$/', $link)) {
            return 'For an email address, write mailto:'.$link.'.';
        }

        if (preg_match('/^[a-z0-9-]+(\/[a-z0-9-]*)*$/i', $link)) {
            return 'For a page on this website, start with a slash, like /'.$link.'.';
        }

        return 'Use a page on this website starting with a slash (for example /contact) or a full address starting with https://.';
    }

    /** A stored image path the site can serve: a media library file or a page's own uploaded image. */
    public static function isKnownImage(string $path): bool
    {
        if (str_contains($path, '..') || ! preg_match('~^(media|pages)/[A-Za-z0-9/_.-]+\.(jpe?g|png|webp|gif)$~i', $path)) {
            return false;
        }

        return MediaItem::where('file_path', $path)->exists() || Storage::disk('public')->exists($path);
    }
}
