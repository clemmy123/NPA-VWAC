@if ($code === 'sw')
<svg class="lang-flag" viewBox="0 0 72 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <path fill="#1eb53a" d="M0 0h72v48H0z"/>
    <path fill="#00a3dd" d="M0 48L72 0v48z"/>
    <path fill="none" stroke="#fcd116" stroke-width="19" d="M0 48L72 0"/>
    <path fill="none" stroke="#000" stroke-width="13" d="M0 48L72 0"/>
</svg>
@else
<svg class="lang-flag" viewBox="0 0 60 30" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <clipPath id="uk-flag-clip-{{ $id }}"><path d="M0 0h60v30H0z"/></clipPath>
    <path fill="#012169" d="M0 0h60v30H0z"/>
    <path d="M0 0l60 30M60 0L0 30" stroke="#fff" stroke-width="6" clip-path="url(#uk-flag-clip-{{ $id }})"/>
    <path d="M0 0l60 30M60 0L0 30" stroke="#C8102E" stroke-width="4" clip-path="url(#uk-flag-clip-{{ $id }})"/>
    <path d="M30 0v30M0 15h60" stroke="#fff" stroke-width="10"/>
    <path d="M30 0v30M0 15h60" stroke="#C8102E" stroke-width="6"/>
</svg>
@endif
