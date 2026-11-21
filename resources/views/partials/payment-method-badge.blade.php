@php
    $m = strtolower($method ?? 'cash');
    $variant = $variant ?? 'short'; // 'short' (icon) or 'full' (complete branding logo)
    $showLabel = $showLabel ?? true;

    // Short logos (square icons) — ideal for inline badges, tables, receipts, offcanvas, list rows
    $shortLogos = [
        'bkash' => asset('assets/images/methods/bkash-short-logo.png'),
        'nagad' => asset('assets/images/methods/nagad-short-logo.png'),
        'nagod' => asset('assets/images/methods/nagod-short-logo.png'),
        'rocket' => asset('assets/images/methods/rocket-short-logo.png'),
        'bank_transfer' => asset('assets/images/methods/bank.svg'),
        'bank' => asset('assets/images/methods/bank.svg'),
        'cheque' => asset('assets/images/methods/bank.svg'),
        'cash' => asset('assets/images/methods/cash.svg'),
    ];

    // Full logos (complete branding with text/mark) — ideal for payment selection cards, prominent headers
    $fullLogos = [
        'bkash' => asset('assets/images/methods/bkash-full-logo.png'),
        'nagad' => asset('assets/images/methods/nagad-full-logo.png'),
        'nagod' => asset('assets/images/methods/nagod-full-logo.png'),
        'rocket' => asset('assets/images/methods/rocket-full-logo.png'),
        'bank_transfer' => asset('assets/images/methods/bank.svg'),
        'bank' => asset('assets/images/methods/bank.svg'),
        'cheque' => asset('assets/images/methods/bank.svg'),
        'cash' => asset('assets/images/methods/cash.svg'),
    ];

    $logos = ($variant === 'full') ? $fullLogos : $shortLogos;
    $logo = $logos[$m] ?? ($variant === 'full' ? ($fullLogos['cash'] ?? asset('assets/images/methods/cash.svg')) : ($shortLogos['cash'] ?? asset('assets/images/methods/cash.svg')));

    $localized = __('finance.method_' . $m);
    if ($localized === 'finance.method_' . $m) {
        $localized = __('donations.method_' . $m);
    }
    if ($localized === 'donations.method_' . $m) {
        $localized = __('portal.payment_methods.' . $m);
    }
    if (str_starts_with($localized, 'portal.payment_methods.')) {
        $localized = ucfirst(str_replace('_', ' ', $m));
    }

    $label = $localized;
    $badgeClass = 'method-' . ($m === 'bank' || $m === 'cheque' ? 'bank_transfer' : $m);
    $variantClass = ($variant === 'full') ? 'badge-full' : 'badge-short';
@endphp
<span class="payment-method-badge {{ $badgeClass }} {{ $variantClass }} {{ $class ?? '' }}">
    <img src="{{ $logo }}" alt="{{ $label }}" class="method-logo {{ $variant === 'full' ? 'method-full-logo' : 'method-short-logo' }}" loading="lazy">
    @if($showLabel)
        <span class="method-name">{{ $label }}</span>
    @endif
</span>
