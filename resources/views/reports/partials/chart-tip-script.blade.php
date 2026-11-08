@once
@php
$reportTipText = [
    'achievement' => __('Achievement'),
    'status' => __('Status'),
    'indicators' => __('Indicators'),
    'share' => __('Share'),
    'shareOfScored' => __('Share of scored indicators'),
    'average' => __('Average achievement'),
    'target' => __('Target'),
    'gap' => __('Gap to target'),
    'noData' => __('No data'),
    'collections' => __('Approved collections'),
    'approved' => __('Approved'),
    'pending' => __('Pending approval'),
    'rejected' => __('Rejected'),
    'change' => __('Change from previous month'),
    'indicatorsReported' => __('Indicators reported'),
    'shareOfCollections' => __('Share of collections'),
    'shareOfApproved' => __('Share of approved'),
    'lastApproved' => __('Last approved: :date'),
    'rank' => __('Rank'),
    'rankOf' => __(':rank of :total'),
    'noCollections' => __('No approved collections in this period.'),
    'untracked' => __('Not tracked in this system'),
    'onTrack' => __('On track'),
    'atRisk' => __('At risk'),
    'offTrack' => __('Off track'),
    'band' => __(':band achievement'),
    'points' => __(':value pts'),
];
@endphp
<script>
window.ReportTip = (function () {
    var text = @json($reportTipText);
    var tip = null;

    function element() {
        if (! tip) {
            tip = document.createElement('div');
            tip.className = 'me-tip';
            tip.setAttribute('role', 'tooltip');
            tip.setAttribute('aria-hidden', 'true');
            document.body.appendChild(tip);
        }

        return tip;
    }

    function format(value, decimals) {
        return Number(value).toLocaleString(undefined, { maximumFractionDigits: decimals === undefined ? 1 : decimals });
    }

    function statusOf(percent) {
        if (percent === null || percent === undefined) {
            return { label: text.noData, key: 'no-data', color: '#94a3b8' };
        }
        if (percent >= 100) {
            return { label: text.onTrack, key: 'on-track', color: '#22c55e' };
        }

        return percent >= 50
            ? { label: text.atRisk, key: 'at-risk', color: '#d97706' }
            : { label: text.offTrack, key: 'off-track', color: '#dc2626' };
    }

    function node(tag, className, content) {
        var created = document.createElement(tag);
        if (className) {
            created.className = className;
        }
        if (content !== undefined && content !== null) {
            created.textContent = content;
        }

        return created;
    }

    function change(current, previous) {
        var difference = current - previous;

        return {
            label: text.change,
            value: difference === 0 ? '0' : (difference > 0 ? '▲ +' : '▼ ') + format(difference, 0),
            tone: difference > 0 ? 'up' : (difference < 0 ? 'down' : null)
        };
    }

    /**
     * card: { title, color, rows: [{ label, value, tone }], status, foot }
     * tone: 'blue' for achievement values, 'up' / 'down' for changes.
     */
    function show(card, clientX, clientY) {
        var box = element();
        box.textContent = '';

        var head = node('div', 'me-tip-title');
        var dot = node('span', 'me-tip-dot');
        dot.style.background = card.color || '#188ae2';
        head.appendChild(dot);
        head.appendChild(node('span', 'me-tip-name', card.title));
        box.appendChild(head);

        (card.rows || []).forEach(function (row) {
            var line = node('div', 'me-tip-row');
            line.appendChild(node('span', 'me-tip-label', row.label));
            line.appendChild(node('strong', 'me-tip-value' + (row.tone ? ' is-' + row.tone : ''), row.value));
            box.appendChild(line);
        });

        if (card.status) {
            var statusRow = node('div', 'me-tip-row');
            statusRow.appendChild(node('span', 'me-tip-label', text.status));
            statusRow.appendChild(node('span', 'me-tip-chip is-' + card.status.key, card.status.label));
            box.appendChild(statusRow);
        }

        if (card.foot) {
            box.appendChild(node('div', 'me-tip-foot', card.foot));
        }

        box.classList.add('is-visible');
        box.setAttribute('aria-hidden', 'false');

        var gap = 14;
        var left = clientX + gap;
        var top = clientY - box.offsetHeight - gap;

        if (left + box.offsetWidth > window.innerWidth - 8) {
            left = clientX - box.offsetWidth - gap;
        }
        if (top < 8) {
            top = clientY + gap;
        }

        box.style.left = Math.max(8, left) + 'px';
        box.style.top = Math.max(8, top) + 'px';
    }

    function hide() {
        if (tip) {
            tip.classList.remove('is-visible');
            tip.setAttribute('aria-hidden', 'true');
        }
    }

    /** Chart.js 2 `tooltips` options that render `describe(index)` in the shared card. */
    function chartTooltips(canvas, describe, lineLike) {
        canvas.addEventListener('mouseleave', hide);

        return {
            enabled: false,
            mode: lineLike ? 'index' : 'nearest',
            intersect: ! lineLike,
            custom: function (model) {
                if (! model || model.opacity === 0 || ! model.dataPoints || ! model.dataPoints.length) {
                    hide();

                    return;
                }

                var rect = canvas.getBoundingClientRect();
                show(describe(model.dataPoints[0].index), rect.left + model.caretX, rect.top + model.caretY);
            }
        };
    }

    function sum(values) {
        return values.reduce(function (total, value) { return total + (Number(value) || 0); }, 0);
    }

    return { text: text, format: format, statusOf: statusOf, change: change, show: show, hide: hide, chartTooltips: chartTooltips, sum: sum };
})();
</script>
@endonce
