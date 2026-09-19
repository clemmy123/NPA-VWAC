@once
<style>
    .lang-dropdown {
        position: relative;
        display: inline-block;
        margin-right: 8px;
    }
    .lang-dropdown > summary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        list-style: none;
        cursor: pointer;
        user-select: none;
        padding: 6px 10px;
        border: 1px solid var(--border-soft, #e2e8f0);
        border-radius: var(--radius, 8px);
        background: var(--surface, #fff);
        color: var(--heading-dark, #1e293b);
        font-size: 0.78rem;
        font-weight: 600;
        line-height: 1;
    }
    .lang-dropdown > summary::-webkit-details-marker {
        display: none;
    }
    .lang-dropdown > summary::marker {
        content: '';
    }
    .lang-dropdown > summary:hover {
        border-color: var(--accent-border-hover, #bfdbfe);
    }
    .lang-dropdown[open] > summary {
        border-color: var(--brand-blue, #188ae2);
        box-shadow: 0 0 0 3px rgba(24, 138, 226, 0.12);
    }
    .lang-dropdown-caret {
        font-size: 1rem;
        color: var(--muted-soft, #94a3b8);
        margin-left: 2px;
    }
    .lang-dropdown-menu {
        position: absolute;
        right: 0;
        top: calc(100% + 6px);
        z-index: 1050;
        min-width: 92px;
        padding: 6px 0;
        background: var(--surface, #fff);
        border: 1px solid var(--border-soft, #e2e8f0);
        border-radius: var(--radius, 8px);
        box-shadow: var(--shadow-dropdown, 0 8px 24px rgba(15, 23, 42, 0.12));
    }
    .lang-dropdown-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        color: var(--dropdown-item-text, #334155);
        text-decoration: none !important;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .lang-dropdown-item:hover {
        background: var(--surface-faint, #f8fafc);
        color: var(--heading-dark, #1e293b);
    }
    .lang-dropdown-item.is-active {
        background: var(--nav-active-bg, #eff6ff);
        color: var(--brand-blue, #188ae2);
    }
    .lang-flag {
        width: 20px;
        height: 14px;
        display: block;
        flex-shrink: 0;
        border-radius: 2px;
        box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.12);
    }
</style>
@endonce
@php
    $locale = app()->isLocale('sw') ? 'sw' : 'en';
    $languages = [
        'en' => 'EN',
        'sw' => 'SW',
    ];
    $languageNames = [
        'en' => 'English',
        'sw' => 'Kiswahili',
    ];
@endphp
<details class="lang-dropdown">
    <summary aria-haspopup="listbox" aria-label="{{ __('Language') }}" title="{{ $languageNames[$locale] }}">
        @include('components.language-flag', ['code' => $locale, 'id' => 'current'])
        <span class="lang-dropdown-current">{{ $languages[$locale] }}</span>
        <i class="mdi mdi-chevron-down lang-dropdown-caret" aria-hidden="true"></i>
    </summary>
    <div class="lang-dropdown-menu" role="listbox">
        @foreach ($languages as $code => $label)
        <a href="{{ route('locale.switch', $code) }}"
           class="lang-dropdown-item {{ $locale === $code ? 'is-active' : '' }}"
           hreflang="{{ $code }}"
           title="{{ $languageNames[$code] }}"
           role="option"
           aria-selected="{{ $locale === $code ? 'true' : 'false' }}">
            @include('components.language-flag', ['code' => $code, 'id' => $code])
            <span>{{ $label }}</span>
        </a>
        @endforeach
    </div>
</details>
