<div class="me-visual-item">
    <span class="me-visual-item-label">{{ $item['label'] }}</span>
    @if ($item['percent'] !== null)
    <span class="s-badge s-achievement">{{ number_format($item['percent'], 1) }}%</span>
    @endif
</div>
