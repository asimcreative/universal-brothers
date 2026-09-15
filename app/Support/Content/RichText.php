<?php

namespace App\Support\Content;

use Illuminate\Support\HtmlString;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Formatted text written in the admin's rich text editor.
 *
 * Three rules hold everywhere this class is used:
 *
 * 1. HTML is sanitised on the way IN (when an admin saves) and again on the
 *    way OUT (when a page renders). Saving keeps the database clean; rendering
 *    protects against anything that reached the database some other way (a
 *    seeder, an import, a row written before this class existed).
 * 2. Older content is plain text. It is never rewritten in the database: it is
 *    recognised as plain text and shown the way it always was — escaped, with
 *    its line breaks kept — so nothing changes until someone edits it.
 * 3. Anything that is not HTML to a reader (search engine descriptions, the AI
 *    assistant's knowledge, JSON-LD) uses toPlainText(), never the HTML.
 *
 * Profiles decide how much formatting a field allows:
 *   full      pages and news: headings, lists, links, quotes, tables, images, safe video embeds
 *   standard  descriptions and answers: headings, lists, links, quotes, alignment
 *   basic     short notes: bold, italic, underline, lists, links
 *   inline    text shown inside a sentence or a list item: bold, italic, links, line breaks
 */
class RichText
{
    public const PROFILES = ['full', 'standard', 'basic', 'inline'];

    /**
     * Text colours the editor offers. Limited to the site's own palette so an
     * editor cannot make text unreadable or off-brand, and so the sanitiser can
     * accept an exact list instead of arbitrary CSS.
     */
    public const COLORS = [
        '#101b45' => 'Navy',
        '#7a5f14' => 'Gold',
        '#4b5563' => 'Grey',
        '#1e7e34' => 'Green',
        '#a12626' => 'Red',
    ];

    /** Video embeds allowed in "full" content: privacy-enhanced YouTube and Vimeo players only. */
    public const EMBED_PATTERN = '~^https://(www\.)?(youtube-nocookie\.com|youtube\.com)/embed/[A-Za-z0-9_-]{6,20}(\?[A-Za-z0-9_=&;.-]*)?$|^https://player\.vimeo\.com/video/\d{3,12}(\?[A-Za-z0-9_=&;.-]*)?$~';

    /** Tags that mean a stored value is HTML rather than older plain text. */
    private const HTML_PATTERN = '~<(p|br|h[1-6]|ul|ol|li|strong|em|b|i|u|s|a|blockquote|table|thead|tbody|tr|td|th|div|span|img|figure|figcaption|iframe|hr)(\s[^>]*)?/?>~i';

    /** @var array<string, HtmlSanitizer> */
    private static array $sanitizers = [];

    /**
     * Clean a submitted value before it is stored. Returns null for content
     * that is empty to a reader (the editor submits "<p></p>" for an empty box).
     */
    public static function clean(?string $value, string $profile = 'standard'): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(str_replace("\r\n", "\n", $value));

        if ($value === '') {
            return null;
        }

        if (! self::isHtml($value)) {
            // A browser without JavaScript submits the plain textarea; keep it
            // as plain text, it renders exactly as typed.
            return $value;
        }

        $clean = trim(self::sanitize($value, $profile));

        return self::isEmpty($clean) ? null : $clean;
    }

    /** Safe HTML for a public page. */
    public static function render(?string $value, string $profile = 'standard'): HtmlString
    {
        if ($value === null || trim($value) === '') {
            return new HtmlString('');
        }

        if (! self::isHtml($value)) {
            return new HtmlString(self::plainToHtml($value, $profile === 'inline'));
        }

        return new HtmlString(self::sanitize($value, $profile));
    }

    /** What the editor should load: HTML stays HTML, plain text becomes paragraphs. */
    public static function forEditor(?string $value, string $profile = 'standard'): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        return self::isHtml($value)
            ? self::sanitize($value, $profile)
            : self::plainToHtml($value, false);
    }

    private static function sanitize(string $value, string $profile): string
    {
        // The sanitiser only drops body elements; <style>, <title> and friends
        // belong to <head>, so without this their contents ("p{color:red}")
        // would survive as visible text. Removed with everything inside.
        $value = preg_replace('~<(script|style|template|noscript|title|head)\b[^>]*>.*?</\1\s*>~is', '', $value);
        $value = preg_replace('~<(meta|link|base)\b[^>]*>~i', '', $value);

        $html = self::sanitizer($profile)->sanitize($value);

        // A frame whose address was not an allowed video player loses its
        // `src`; the empty frame and its wrapper would still take up space.
        $html = preg_replace('~<iframe(?![^>]*\ssrc=)[^>]*>.*?</iframe>~is', '', $html);
        $html = preg_replace('~<img(?![^>]*\ssrc=)[^>]*>~i', '', $html);

        return preg_replace('~<div data-(youtube-video|video-embed)(="[^"]*")?>\s*</div>~i', '', $html);
    }

    /** Readable text with no markup, for meta descriptions, JSON-LD and the AI assistant. */
    public static function toPlainText(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (! self::isHtml($value)) {
            return trim($value);
        }

        $text = preg_replace('~<li(\s[^>]*)?>~i', '- ', $value);
        $text = preg_replace('~<(br|hr|ul|ol)(\s[^>]*)?/?>~i', "\n", $text);
        $text = preg_replace('~</(p|h[1-6]|li|blockquote|tr|table|figure|figcaption|div|ul|ol)>~i', "\n", $text);
        $text = preg_replace('~</t[dh]>~i', ' ', $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text);
        $text = preg_replace('/ *\n */', "\n", $text);
        $text = preg_replace("/\n{2,}/", "\n", $text);

        return trim($text);
    }

    public static function isHtml(?string $value): bool
    {
        return $value !== null && preg_match(self::HTML_PATTERN, $value) === 1;
    }

    /** True when nothing would be visible: no text, no image, no embed, no table. */
    public static function isEmpty(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return true;
        }

        if (preg_match('~<(img|iframe|hr|table)\b~i', $value)) {
            return false;
        }

        return trim(self::toPlainText($value)) === '';
    }

    public static function wordCount(?string $value): int
    {
        $text = self::toPlainText($value);

        return $text === '' ? 0 : count(preg_split('/\s+/u', $text));
    }

    /** Older plain text, shown the way it always was: escaped, paragraphs and line breaks kept. */
    private static function plainToHtml(string $value, bool $inline): string
    {
        $value = str_replace("\r\n", "\n", trim($value));

        if ($inline) {
            return nl2br(e($value), false);
        }

        $paragraphs = preg_split("/\n{2,}/", $value);

        return collect($paragraphs)
            ->map(fn (string $p) => '<p>'.nl2br(e(trim($p)), false).'</p>')
            ->implode('');
    }

    private static function sanitizer(string $profile): HtmlSanitizer
    {
        if (! in_array($profile, self::PROFILES, true)) {
            $profile = 'standard';
        }

        return self::$sanitizers[$profile] ??= new HtmlSanitizer(self::config($profile));
    }

    private static function config(string $profile): HtmlSanitizerConfig
    {
        $config = (new HtmlSanitizerConfig)
            // A tag this profile does not allow is unwrapped, not deleted: a
            // heading pasted into a short note keeps its words. Elements that
            // carry code or foreign content are removed with everything inside.
            ->defaultAction(HtmlSanitizerAction::Block)
            ->dropElement('script')
            ->dropElement('style')
            ->dropElement('template')
            ->dropElement('noscript')
            ->dropElement('object')
            ->dropElement('embed')
            ->dropElement('svg')
            ->dropElement('math')
            ->dropElement('form')
            ->dropElement('input')
            ->dropElement('button')
            ->dropElement('select')
            ->dropElement('textarea')
            ->dropElement('head')
            ->dropElement('title')
            ->dropElement('meta')
            ->dropElement('link')
            // The default limit (20 000 bytes) would silently cut a long page.
            ->withMaxInputLength(500_000)
            ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->allowMediaSchemes(['https', 'http'])
            ->allowRelativeMedias()
            ->allowElement('br')
            ->allowElement('strong')
            ->allowElement('b')
            ->allowElement('em')
            ->allowElement('i')
            ->allowElement('u')
            ->allowElement('s')
            ->allowElement('a', ['href', 'title', 'target'])
            // Every link opened in a new tab must not hand the new page a
            // handle on this one; forcing it on every link is simplest.
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->withAttributeSanitizer(new Sanitizers\LinkTargetSanitizer)
            ->withAttributeSanitizer(new Sanitizers\StyleSanitizer(array_keys(self::COLORS)));

        if ($profile === 'inline') {
            return $config;
        }

        $config = $config
            ->allowElement('p', ['style'])
            ->allowElement('ul')
            ->allowElement('ol', ['start'])
            ->allowElement('li');

        if ($profile === 'basic') {
            return $config;
        }

        $config = $config
            ->allowElement('h2', ['style'])
            ->allowElement('h3', ['style'])
            ->allowElement('h4', ['style'])
            ->allowElement('blockquote')
            ->allowElement('span', ['style'])
            ->allowElement('hr');

        if ($profile === 'standard') {
            return $config;
        }

        return $config
            ->allowElement('table')
            ->allowElement('thead')
            ->allowElement('tbody')
            ->allowElement('tr')
            ->allowElement('th', ['colspan', 'rowspan', 'style'])
            ->allowElement('td', ['colspan', 'rowspan', 'style'])
            ->allowElement('img', ['src', 'alt', 'title', 'width', 'height'])
            ->allowElement('figure')
            ->allowElement('figcaption')
            ->allowElement('div', ['data-youtube-video', 'data-video-embed'])
            ->allowElement('iframe', ['src', 'title', 'width', 'height', 'allowfullscreen'])
            ->withAttributeSanitizer(new Sanitizers\EmbedSourceSanitizer(self::EMBED_PATTERN));
    }
}
