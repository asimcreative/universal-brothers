<?php

namespace App\Support\Ai;

/**
 * A deliberately small language hint for the system prompt.
 *
 * The model does the real work of matching the visitor's language; this only
 * nudges it in the cases where getting it wrong is most likely and most
 * annoying. The hard case is Roman Urdu, which is indistinguishable from
 * English by script alone — "mujhe hajj package chahiye" is plain Latin text —
 * so it is detected by common Urdu function words instead.
 *
 * Script detection is a Unicode range check, which is reliable. The Roman Urdu
 * check is a heuristic and is treated as one: it only fires on two or more
 * distinct marker words, so an English sentence containing "hajj" or "umrah"
 * is not mistaken for Urdu.
 */
class LanguageDetector
{
    /**
     * Function words, not topic words. "hajj", "umrah" and "package" appear in
     * English questions constantly and would produce false positives.
     */
    private const ROMAN_URDU_MARKERS = [
        'kya', 'kia', 'kaise', 'kaisay', 'kese', 'kitna', 'kitni', 'kitne', 'kahan', 'kahaan',
        'mujhe', 'mujhy', 'hume', 'humein', 'mera', 'meri', 'mere', 'apka', 'aapka', 'apki',
        'hai', 'hain', 'hay', 'ho', 'hoga', 'hogi', 'nahi', 'nahin', 'nhi', 'nhe',
        'karna', 'karne', 'karna', 'karo', 'kardo', 'karden', 'chahiye', 'chahye', 'chaiye',
        'batao', 'bataye', 'bataen', 'batayen', 'sakta', 'sakte', 'sakti',
        'se', 'ka', 'ki', 'ke', 'ko', 'me', 'mein', 'par', 'aur', 'ya', 'phir', 'lekin',
        'bhai', 'please', 'kon', 'kaun', 'jo', 'wo', 'woh', 'ye', 'yeh', 'is', 'us',
        'rate', 'kimat', 'qeemat', 'paisa', 'paise', 'kitnay',
    ];

    public static function detect(string $text): ?string
    {
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        // Script checks first: these are unambiguous.
        // Arabic block covers Arabic and the Urdu extensions, so Urdu-specific
        // characters are tested before falling back to Arabic.
        if (preg_match('/[\x{0679}\x{0688}\x{0691}\x{06BA}\x{06BE}\x{06C1}\x{06CC}\x{06D2}]/u', $text)) {
            return 'Urdu (Urdu script)';
        }

        if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return 'Arabic';
        }

        if (preg_match('/[\x{0900}-\x{097F}]/u', $text)) {
            return 'Hindi (Devanagari)';
        }

        if (preg_match('/[\x{0980}-\x{09FF}]/u', $text)) {
            return 'Bengali';
        }

        if (preg_match('/[\x{4E00}-\x{9FFF}]/u', $text)) {
            return 'Chinese';
        }

        if (preg_match('/[\x{0400}-\x{04FF}]/u', $text)) {
            return 'Russian (Cyrillic)';
        }

        if (self::looksLikeRomanUrdu($text)) {
            return 'Roman Urdu (Urdu written in English letters)';
        }

        // Latin script that is NOT Roman Urdu. This used to return null, which
        // meant no hint reached the prompt at all — and with nothing to go on,
        // the model leaned on "Karachi-based company" and answered a plain
        // English question in Roman Urdu. Found by testing against the live
        // API: "Do you guarantee my Hajj visa will be approved?" came back as
        // "Main aapko yeh bata nahi sakta...".
        //
        // Worded to cover Malay and other Latin-script languages as well as
        // English, rather than asserting English outright.
        if (preg_match('/\p{Latin}/u', $text)) {
            return self::LATIN_NOT_ROMAN_URDU;
        }

        return null;
    }

    public const LATIN_NOT_ROMAN_URDU = 'English, or whichever other Latin-script language the visitor actually wrote in. It is NOT Urdu — do not reply in Urdu or in Roman Urdu';

    private static function looksLikeRomanUrdu(string $text): bool
    {
        preg_match_all('/[a-z\']+/i', mb_strtolower($text), $matches);

        $words = $matches[0] ?? [];

        if (count($words) < 2) {
            return false;
        }

        $hits = array_unique(array_intersect($words, self::ROMAN_URDU_MARKERS));

        // Two distinct markers, so a single shared word ("me", "par", "se")
        // inside an English sentence is not enough on its own.
        return count($hits) >= 2;
    }
}
