@php
    use App\Support\Currency;
@endphp

@unless(Currency::chosen())
    {{--
        Asked once, and then not again for a year — the answer is remembered in
        a cookie, not only for the visit.

        The three brochures are separately printed price lists rather than
        conversions of one another, and a package with no price in the chosen
        list is left out rather than shown as "N/A". So this is not a display
        preference — it decides which list is being read, which is why it is
        worth asking for rather than guessing from an IP address.

        A form POST, so it works with JavaScript switched off, and every route
        out of it is a real choice: there is no dismiss that leaves the question
        hanging and re-asks on the next page.
    --}}
    <div class="ub-currency-gate" role="dialog" aria-modal="true" aria-labelledby="ub-currency-title" data-ub-currency-gate>
        <div class="ub-currency-gate__backdrop"></div>

        <div class="ub-currency-gate__panel">
            <p class="ub-currency-gate__eyebrow">Universal Brothers</p>
            <h2 class="ub-currency-gate__title" id="ub-currency-title">Which currency would you like prices in?</h2>
            <p class="ub-currency-gate__lead">Our Hajj 2027 packages are published in three separate price lists. Pick the one you book in — you can change it at any time from the top of the page.</p>

            <form method="POST" action="{{ route('currency.store') }}" class="ub-currency-gate__options">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ request()->getRequestUri() }}">

                @foreach(Currency::options() as $ubOption)
                    <button type="submit" name="currency" value="{{ $ubOption['code'] }}" class="ub-currency-gate__option">
                        <span class="ub-currency-gate__code">{{ $ubOption['code'] }}</span>
                        <span class="ub-currency-gate__label">{{ $ubOption['label'] }}</span>
                        <span class="ub-currency-gate__note">{{ $ubOption['note'] }}</span>
                    </button>
                @endforeach
            </form>
        </div>
    </div>
@endunless
