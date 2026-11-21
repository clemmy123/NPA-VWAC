/*
 * On phones, filter panels (.report-filters) are folded behind a "Filters"
 * button that shows how many are active. Desktop is untouched (the CSS hides the
 * toggle and never applies .mf-folded there).
 */
document.addEventListener('DOMContentLoaded', () => {
    const label = document.body.dataset.filtersLabel || 'Filters';
    const phone = window.matchMedia('(max-width: 767px)');

    document.querySelectorAll('form.report-filters, div.report-filters').forEach((panel) => {
        if (panel.classList.contains('d-none')) return;

        const active = [...panel.querySelectorAll('input[name], select[name]')].filter((field) => {
            if (field.type === 'hidden' || field.type === 'submit') return false;
            return field.value !== '' && field.value !== '__none__';
        }).length;

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'mf-toggle';
        toggle.setAttribute('aria-expanded', 'false');
        toggle.innerHTML = '<i class="mdi mdi-filter-variant"></i><span></span>'
            + (active ? '<span class="mf-count"></span>' : '')
            + '<i class="mdi mdi-chevron-down mf-chevron"></i>';
        toggle.querySelector('span').textContent = label;
        if (active) toggle.querySelector('.mf-count').textContent = String(active);

        panel.before(toggle);
        panel.classList.add('mf-folded');

        toggle.addEventListener('click', () => {
            const open = panel.classList.toggle('mf-folded') === false;
            toggle.setAttribute('aria-expanded', String(open));
        });
        phone.addEventListener?.('change', () => {
            if (!phone.matches) panel.classList.remove('mf-folded');
            else if (toggle.getAttribute('aria-expanded') !== 'true') panel.classList.add('mf-folded');
        });
    });
});
