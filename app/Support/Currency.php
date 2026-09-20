<?php

namespace App\Support;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;

/**
 * Which currency the visitor is reading prices in.
 *
 * The brochure is published three times — in rupees, in riyals and in dollars
 * — and they are not conversions of one another: they are three separately
 * printed price lists. So the currency is not a display format, it is which
 * list the visitor is reading, which is why it is worth asking for rather
 * than guessing from an IP address.
 *
 * A package with no price in the chosen currency is left out rather than
 * shown as "N/A" — that was the old behaviour and it made the site look
 * broken rather than honest. All 26 packages are priced in all three lists
 * today, so it currently hides nothing; it is still the right rule for the
 * day one is published without a full set.
 *
 * The choice is asked for once and then remembered for a year, so a visitor
 * who comes back next week is not asked again. It can be changed from the
 * header at any time.
 */
class Currency
{
    public const SESSION_KEY = 'ub_currency';

    /**
     * The same name as the session key, because it is the same idea: the
     * session carries it within a visit, the cookie carries it between them.
     *
     * Deliberately NOT encrypted (see `bootstrap/app.php`). It holds one of
     * three fixed strings, it is validated against SUPPORTED every time it is
     * read, and someone who forges it only changes the prices they themselves
     * see. Encrypting it would buy nothing and would make the preference
     * unreadable to anything but PHP.
     */
    public const COOKIE = 'ub_currency';

    /** A year. This is a reader's preference, not a property of one visit. */
    private const COOKIE_MINUTES = 60 * 24 * 365;

    /** Rupees first: it is the home market and the only list that is complete. */
    public const SUPPORTED = ['PKR', 'SAR', 'USD'];

    public const DEFAULT = 'PKR';

    /**
     * How each one is written in front of a number, and what to call it.
     *
     * @var array<string, array{symbol: string, label: string, note: string}>
     */
    private const META = [
        'PKR' => ['symbol' => 'PKR ', 'label' => 'Pakistani Rupee', 'note' => 'As printed in the rupee brochure'],
        'SAR' => ['symbol' => 'SAR ', 'label' => 'Saudi Riyal', 'note' => 'As printed in the riyal brochure'],
        'USD' => ['symbol' => 'US$', 'label' => 'US Dollar', 'note' => 'As printed in the dollar brochure'],
    ];

    /** The currency in force, falling back to the default. */
    public static function current(): string
    {
        return self::remembered() ?? self::DEFAULT;
    }

    /** Whether the visitor has actually chosen, as opposed to being defaulted. */
    public static function chosen(): bool
    {
        return self::remembered() !== null;
    }

    public static function set(string $currency): bool
    {
        $currency = strtoupper(trim($currency));

        if (! in_array($currency, self::SUPPORTED, true)) {
            return false;
        }

        Session::put(self::SESSION_KEY, $currency);
        Cookie::queue(self::COOKIE, $currency, self::COOKIE_MINUTES);

        return true;
    }

    /**
     * The choice as it was last made, or null if it never was.
     *
     * The session is asked first so that a change made on this request is in
     * force on the very next one: the cookie the browser sent is still the
     * old value until it has been round-tripped.
     */
    private static function remembered(): ?string
    {
        foreach ([Session::get(self::SESSION_KEY), Cookie::get(self::COOKIE)] as $value) {
            if (is_string($value) && in_array($value, self::SUPPORTED, true)) {
                return $value;
            }
        }

        return null;
    }

    public static function symbol(?string $currency = null): string
    {
        return self::META[$currency ?? self::current()]['symbol'] ?? '';
    }

    public static function label(?string $currency = null): string
    {
        return self::META[$currency ?? self::current()]['label'] ?? '';
    }

    public static function note(?string $currency = null): string
    {
        return self::META[$currency ?? self::current()]['note'] ?? '';
    }

    /** The column on `package_room_options` holding this currency's price. */
    public static function column(?string $currency = null): string
    {
        return 'price_'.strtolower($currency ?? self::current());
    }

    /** A price written the way this currency writes it. Null stays null. */
    public static function format(int|float|string|null $amount, ?string $currency = null): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        return self::symbol($currency).number_format((float) $amount);
    }

    /** @return array<int, array{code: string, label: string, note: string}> */
    public static function options(): array
    {
        return array_map(
            fn (string $code) => ['code' => $code] + self::META[$code],
            self::SUPPORTED,
        );
    }
}
