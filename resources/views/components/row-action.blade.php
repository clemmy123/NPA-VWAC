@props(['label', 'icon', 'tone' => 'slate', 'href' => null, 'action' => null, 'method' => 'POST', 'confirm' => null, 'redirect' => false, 'fields' => []])
@if ($href)
<a role="menuitem" href="{{ $href }}" {{ $attributes->class(['ra-item', 'ra-tone-'.$tone]) }}>
    <i class="mdi {{ $icon }}"></i><span>{{ $label }}</span>
</a>
@else
<form action="{{ $action }}" method="POST" class="ra-form"@if ($confirm) data-confirm="{{ $confirm }}"@endif>
    @csrf
    @if (strtoupper($method) !== 'POST')@method($method)@endif
    @foreach ($fields as $fieldName => $fieldValue)<input type="hidden" name="{{ $fieldName }}" value="{{ $fieldValue }}">@endforeach
    @if ($redirect)<input type="hidden" name="redirect_to" value="{{ url()->current() }}">@endif
    <button type="submit" role="menuitem" {{ $attributes->class(['ra-item', 'ra-tone-'.$tone]) }}>
        <i class="mdi {{ $icon }}"></i><span>{{ $label }}</span>
    </button>
</form>
@endif
