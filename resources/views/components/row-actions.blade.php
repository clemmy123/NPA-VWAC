{{--
    Shared per-row actions menu: one ⋮ button; click opens a popup listing each
    action as "coloured icon + name". Put <x-row-action> items inside it.
    Behaviour lives in resources/js/row-actions.js, look in resources/css/app/row-actions.css.
--}}
<div {{ $attributes->class(['ra']) }} data-row-actions>
    <button type="button" class="ra-trigger" aria-haspopup="menu" aria-expanded="false"
            title="{{ __('Actions') }}" aria-label="{{ __('Actions') }}">
        <i class="mdi mdi-dots-vertical"></i>
    </button>
    <div class="ra-menu" role="menu" hidden>{{ $slot }}</div>
</div>
