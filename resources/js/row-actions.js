/*
 * Row actions menu (components/row-actions.blade.php): one ⋮ button per row that
 * opens a popup of actions. The popup is position:fixed, placed from the
 * trigger's rect, so scrolling/overflow tables never clip it. Only one is open at
 * a time; click-away, Esc, scroll and resize close it; arrow keys move focus.
 */
(() => {
    let open = null;

    const close = () => {
        if (!open) return;
        open.menu.hidden = true;
        open.trigger.setAttribute('aria-expanded', 'false');
        open = null;
    };

    const place = ({ trigger, menu }) => {
        const rect = trigger.getBoundingClientRect();
        menu.style.visibility = 'hidden';
        menu.hidden = false;
        const width = menu.offsetWidth;
        const height = menu.offsetHeight;
        const left = Math.max(8, Math.min(rect.right - width, window.innerWidth - width - 8));
        const below = rect.bottom + 6;
        const top = below + height > window.innerHeight - 8 ? Math.max(8, rect.top - height - 6) : below;
        menu.style.left = left + 'px';
        menu.style.top = top + 'px';
        menu.style.visibility = '';
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('.ra-trigger');
        if (!trigger) {
            if (!event.target.closest('.ra-menu')) close();
            return;
        }
        const menu = trigger.closest('[data-row-actions]').querySelector('.ra-menu');
        const wasOpen = open && open.menu === menu;
        close();
        if (wasOpen) return;
        open = { trigger, menu };
        place(open);
        trigger.setAttribute('aria-expanded', 'true');
        menu.querySelector('.ra-item')?.focus({ preventScroll: true });
    });

    document.addEventListener('keydown', (event) => {
        if (!open) return;
        if (event.key === 'Escape') {
            const { trigger } = open;
            close();
            trigger.focus();
        } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            const items = [...open.menu.querySelectorAll('.ra-item')];
            const index = items.indexOf(document.activeElement);
            const next = event.key === 'ArrowDown' ? index + 1 : index - 1;
            items[(next + items.length) % items.length].focus();
        }
    });

    window.addEventListener('resize', close);
    document.addEventListener('scroll', close, true);
})();
