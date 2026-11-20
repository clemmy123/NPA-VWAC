//

/*
 * Plan grids (Plan Builder + Data Collection sheet): drag the right edge of the
 * sticky indicator column's header to resize it. The width is shared by both
 * grids and remembered; double-click resets it. Text in the column wraps, so a
 * narrower column simply grows taller.
 */
document.addEventListener('DOMContentLoaded', () => {
    const KEY = 'pbIndicatorWidth';
    const MIN = 160;
    const MAX = 720;
    const clamp = (width) => Math.min(MAX, Math.max(MIN, width));

    document.querySelectorAll('.pb-sheet').forEach((sheet) => {
        const header = sheet.querySelector('thead th.pb-col-indicator');
        if (!header) return;

        const apply = (width) => {
            sheet.style.setProperty('--pb-indicator-w', clamp(width) + 'px');
            sheet.querySelectorAll('textarea.pb-text').forEach((box) => {
                box.style.height = 'auto';
                box.style.height = box.scrollHeight + 'px';
            });
        };

        try {
            const saved = Number(localStorage.getItem(KEY));
            if (saved) apply(saved);
        } catch (e) { /* storage unavailable — default width */ }

        const handle = document.createElement('div');
        handle.className = 'pb-col-resizer';
        handle.title = 'Drag to resize · double-click to reset';
        header.appendChild(handle);

        handle.addEventListener('pointerdown', (event) => {
            event.preventDefault();
            handle.setPointerCapture(event.pointerId);
            const startX = event.clientX;
            const startWidth = header.getBoundingClientRect().width;
            document.body.classList.add('pb-resizing');

            const move = (e) => apply(startWidth + e.clientX - startX);
            const stop = () => {
                handle.removeEventListener('pointermove', move);
                handle.removeEventListener('pointerup', stop);
                handle.removeEventListener('pointercancel', stop);
                document.body.classList.remove('pb-resizing');
                try { localStorage.setItem(KEY, String(Math.round(header.getBoundingClientRect().width))); } catch (e) { /* ignore */ }
            };
            handle.addEventListener('pointermove', move);
            handle.addEventListener('pointerup', stop);
            handle.addEventListener('pointercancel', stop);
        });

        handle.addEventListener('dblclick', () => {
            sheet.style.removeProperty('--pb-indicator-w');
            sheet.querySelectorAll('textarea.pb-text').forEach((box) => {
                box.style.height = 'auto';
                box.style.height = box.scrollHeight + 'px';
            });
            try { localStorage.removeItem(KEY); } catch (e) { /* ignore */ }
        });
    });
});

/*
 * Toolbar selects on the plan grids (financial year, reporting period) are
 * searchable Select2 boxes that reload the page when the choice changes.
 */
document.addEventListener('DOMContentLoaded', () => {
    if (!(window.jQuery && jQuery.fn.select2)) return;

    jQuery('.pb-year-bar select.pb-bar-select').each(function () {
        const select = jQuery(this);
        select.select2({ width: '220px', minimumResultsForSearch: 0, dropdownCssClass: 'pb-select2-dropdown' });
        select.on('change', () => this.form.submit());
    });
});
