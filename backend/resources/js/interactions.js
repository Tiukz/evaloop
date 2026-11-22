// Native forms/navigation remain authoritative. This module only presents pending/disclosure states.
const pendingForms = new Map();
const status = document.getElementById('ev-submit-status');
const pageProgress = document.getElementById('ev-page-progress');
let progressTimer;

function startPageProgress() {
    if (!pageProgress || !pageProgress.hidden || progressTimer !== undefined) return;
    progressTimer = window.setTimeout(() => {
        progressTimer = undefined;
        pageProgress.hidden = false;
    }, 150);
}

function isPageNavigation(href, target) {
    if (target && target.toLowerCase() !== '_self') return false;
    const current = new URL(window.location.href);
    const next = new URL(href, current);
    return ['http:', 'https:'].includes(next.protocol) && next.origin === current.origin;
}

function resetForm(form) {
    const state = pendingForms.get(form);
    if (!state) return;
    for (const [name, value] of state.formAttributes) {
        if (value === null) form.removeAttribute(name); else form.setAttribute(name, value);
    }
    for (const [button, attributes] of state.buttons) {
        for (const [name, value] of attributes) {
            if (value === null) button.removeAttribute(name); else button.setAttribute(name, value);
        }
    }
    pendingForms.delete(form);
    if (status && pendingForms.size === 0) status.textContent = '';
}

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (event.defaultPrevented || !(form instanceof HTMLFormElement)) return;
    if (pendingForms.has(form)) { event.preventDefault(); return; }
    const buttons = [...form.elements].filter((control) =>
        ((control instanceof HTMLButtonElement && control.type === 'submit') ||
         (control instanceof HTMLInputElement && ['submit', 'image'].includes(control.type))) && !control.disabled);
    const submitter = event.submitter;
    const state = {
        formAttributes: ['aria-busy'].map((name) => [name, form.getAttribute(name)]),
        buttons: buttons.map((button) => [button, ['aria-disabled', 'aria-busy', 'aria-label'].map((name) => [name, button.getAttribute(name)])]),
    };
    pendingForms.set(form, state);
    form.setAttribute('aria-busy', 'true');
    for (const button of buttons) button.setAttribute('aria-disabled', 'true');
    if (submitter && !submitter.disabled) {
        // Do NOT set disabled or mutate name/value: the browser has not serialized the form yet.
        submitter.setAttribute('aria-busy', 'true');
        const label = submitter.getAttribute('aria-label') || submitter.querySelector('.ev-button-label')?.textContent.trim() || submitter.textContent.trim() || submitter.value;
        submitter.setAttribute('aria-label', `${label} — Memproses…`);
    }
    if (status) status.textContent = 'Memproses formulir…';
    // Another handler may cancel submission. Never strand its controls in a pending state.
    queueMicrotask(() => {
        if (event.defaultPrevented) {
            resetForm(form);
        } else if (pageProgress && (submitter?.getAttribute('formmethod') || form.method) !== 'dialog'
            && isPageNavigation(submitter?.getAttribute('formaction') ?? form.action, submitter?.getAttribute('formtarget') ?? form.target)) {
            startPageProgress();
        }
    });
});

document.addEventListener('click', (event) => {
    const button = event.target instanceof Element ? event.target.closest('button, input[type="submit"], input[type="image"]') : null;
    if (button?.form && pendingForms.has(button.form)) event.preventDefault();
});
if (pageProgress) window.addEventListener('click', (event) => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
    if (!link || link.hasAttribute('download') || !isPageNavigation(link.href, link.target)) return;
    const current = new URL(window.location.href);
    const next = new URL(link.href);
    if (link.href.includes('#') && next.pathname === current.pathname && next.search === current.search) return;
    startPageProgress();
});

window.addEventListener('pageshow', () => {
    for (const form of [...pendingForms.keys()]) resetForm(form);
    if (pageProgress) {
        window.clearTimeout(progressTimer);
        progressTimer = undefined;
        pageProgress.hidden = true;
    }
});

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const menus = [...document.querySelectorAll('[data-account-menu], [data-filter-menu]')];
const easing = getComputedStyle(document.documentElement).getPropertyValue('--ease-ev').trim() || 'cubic-bezier(.2,.8,.2,1)';
const animations = new Map();
const duration = (token, fallback) => parseFloat(getComputedStyle(document.documentElement).getPropertyValue(token)) || fallback;

function cancelAnimation(menu) { animations.get(menu)?.cancel(); animations.delete(menu); }
function positionFilter(menu) {
    if (menu.dataset.filterMenu === undefined) return;
    const panel = menu.querySelector('.ev-menu-panel');
    if (!panel) return;
    if (getComputedStyle(panel).position !== 'absolute') { panel.style.removeProperty('--filter-offset'); return; }
    panel.style.setProperty('--filter-offset', '0px');
    const { left, right } = panel.getBoundingClientRect();
    const offset = Math.max(16 - left, Math.min(0, window.innerWidth - 16 - right));
    panel.style.setProperty('--filter-offset', `${offset}px`);
}
window.addEventListener('resize', () => { for (const menu of menus) if (menu.open) positionFilter(menu); });
function openFeedback(menu) {
    positionFilter(menu);
    const panel = menu.querySelector('.ev-menu-panel');
    const current = animations.has(menu) && panel ? getComputedStyle(panel) : null;
    const from = current ? { opacity: current.opacity, transform: current.transform } : { opacity: 0, transform: 'translateY(-4px)' };
    cancelAnimation(menu);
    if (!reducedMotion.matches && panel?.animate) {
        animations.set(menu, panel.animate([from, { opacity: 1, transform: 'translateY(0)' }],
            { duration: duration('--ev-disclosure', 220), easing }));
    }
}
function closeMenu(menu, returnFocus = false) {
    if (!menu.open) return;
    if (returnFocus) menu.querySelector('summary')?.focus();
    if (menu.dataset.closing === 'true') return;
    const panel = menu.querySelector('.ev-menu-panel');
    const current = panel ? getComputedStyle(panel) : null;
    const from = current ? { opacity: current.opacity, transform: current.transform } : { opacity: 1, transform: 'none' };
    cancelAnimation(menu);
    if (reducedMotion.matches || !panel?.animate) { menu.open = false; return; }
    menu.dataset.closing = 'true';
    const animation = panel.animate([from, { opacity: 0, transform: 'translateY(-3px)' }],
        { duration: duration('--ev-press', 120), easing });
    animations.set(menu, animation);
    animation.finished.then(() => {
        if (animations.get(menu) !== animation) return;
        menu.open = false;
        delete menu.dataset.closing;
        animations.delete(menu);
    }).catch(() => {}); // Reopening/Escape can interrupt an animation safely.
}
for (const menu of menus) {
    const summary = menu.querySelector('summary');
    summary?.setAttribute('aria-expanded', String(menu.open));
    menu.addEventListener('focusout', (event) => {
        // The destination is available before the browser updates activeElement.
        if (event.relatedTarget) {
            if (!menu.contains(event.relatedTarget)) closeMenu(menu);
            return;
        }
        // The native select picker can take focus without a DOM destination.
        if (event.target instanceof HTMLSelectElement) return;
        queueMicrotask(() => { if (!menu.contains(document.activeElement)) closeMenu(menu); });
    });
    menu.addEventListener('toggle', () => {
        summary?.setAttribute('aria-expanded', String(menu.open));
        if (menu.open && menu.dataset.closing !== 'true') openFeedback(menu);
        if (!menu.open) { cancelAnimation(menu); delete menu.dataset.closing; }
    });
    summary?.addEventListener('click', (event) => {
        if (!menu.open) return; // Native summary supports keyboard and no-JS fallback.
        event.preventDefault();
        if (menu.dataset.closing === 'true') {
            delete menu.dataset.closing; openFeedback(menu);
        } else closeMenu(menu, true);
    });
}
document.addEventListener('click', (event) => { for (const menu of menus) if (!menu.contains(event.target)) closeMenu(menu); });
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    for (const menu of menus) if (menu.open && menu.contains(document.activeElement)) { event.preventDefault(); closeMenu(menu, true); }
});
reducedMotion.addEventListener('change', () => {
    if (!reducedMotion.matches) return;
    for (const menu of menus) {
        cancelAnimation(menu);
        if (menu.dataset.closing === 'true') { menu.open = false; delete menu.dataset.closing; }
    }
});

// Move the same role-aware navigation into a native modal on small screens.
const shellNavigation = document.querySelector('[data-shell-navigation]');
const sidebarDrawer = document.getElementById('mobile-sidebar');
if (shellNavigation && typeof sidebarDrawer?.showModal === 'function') {
    const sidebar = shellNavigation.parentElement;
    const content = shellNavigation.querySelector('.sidebar-body');
    const toggle = content.querySelector('[data-sidebar-toggle]');
    const close = content.querySelector('[data-sidebar-close]');
    const open = sidebar.querySelector('[data-sidebar-open]');
    const tooltip = sidebar.querySelector('[data-sidebar-tooltip]');
    const desktop = window.matchMedia('(min-width: 1024px)');
    let collapsed = false;
    let animationTimer;
    let hovered = null;
    try { collapsed = localStorage.getItem('evaloop.sidebar') === 'collapsed'; } catch {}

    function updateRail() {
        sidebar.toggleAttribute('data-sidebar-collapsed', desktop.matches && collapsed);
        toggle.setAttribute('aria-expanded', String(!collapsed));
        toggle.setAttribute('aria-label', collapsed ? 'Lebarkan sidebar' : 'Ciutkan sidebar');
    }

    function updateLayout() {
        const wasFocusedInside = content.contains(document.activeElement);
        const wasFocusedOnTrigger = document.activeElement === open;
        if (sidebarDrawer.open) sidebarDrawer.close();
        hovered = null;
        tooltip.hidden = true;
        open.hidden = desktop.matches;
        toggle.hidden = !desktop.matches;
        close.hidden = desktop.matches;
        shellNavigation.open = desktop.matches;
        (desktop.matches ? shellNavigation : sidebarDrawer).append(content);
        updateRail();
        if (wasFocusedInside || wasFocusedOnTrigger) (desktop.matches ? toggle : open).focus();
    }

    toggle.addEventListener('click', () => {
        collapsed = !collapsed;
        sidebar.dataset.sidebarAnimating = '';
        clearTimeout(animationTimer);
        animationTimer = setTimeout(() => delete sidebar.dataset.sidebarAnimating, duration('--ev-hover', 160) + 40);
        updateRail();
        tooltip.hidden = true;
        try { localStorage.setItem('evaloop.sidebar', collapsed ? 'collapsed' : 'expanded'); } catch {}
    });
    open.addEventListener('click', () => {
        sidebarDrawer.showModal();
        open.setAttribute('aria-expanded', 'true');
        close.focus();
    });
    close.addEventListener('click', () => sidebarDrawer.close());
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || event.defaultPrevented || !sidebarDrawer.open) return;
        event.preventDefault();
        sidebarDrawer.close();
    });
    sidebarDrawer.addEventListener('click', (event) => {
        if (event.target === sidebarDrawer) sidebarDrawer.close();
    });
    sidebarDrawer.addEventListener('close', () => {
        open.setAttribute('aria-expanded', 'false');
        if (!desktop.matches) open.focus();
    });

    function updateTooltip() {
        const target = hovered ?? document.activeElement?.closest('[data-sidebar-label]');
        if (!collapsed || !desktop.matches || !target || !sidebar.contains(target) || target.closest('[data-account-menu][open]')) {
            tooltip.hidden = true;
            return;
        }
        const bounds = target.getBoundingClientRect();
        tooltip.textContent = target.dataset.sidebarLabel;
        tooltip.style.left = `${bounds.right + 12}px`;
        tooltip.style.top = `${Math.max(18, Math.min(window.innerHeight - 18, bounds.top + bounds.height / 2))}px`;
        tooltip.hidden = false;
    }
    sidebar.addEventListener('pointerover', (event) => {
        hovered = event.target.closest('[data-sidebar-label]');
        updateTooltip();
    });
    sidebar.addEventListener('pointerleave', () => { hovered = null; updateTooltip(); });
    sidebar.addEventListener('focusin', () => { hovered = null; updateTooltip(); });
    sidebar.addEventListener('focusout', () => queueMicrotask(updateTooltip));
    sidebar.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') tooltip.hidden = true;
    });
    sidebar.addEventListener('toggle', updateTooltip, true);
    sidebar.addEventListener('scroll', updateTooltip, true);
    desktop.addEventListener('change', updateLayout);
    sidebar.dataset.sidebarReady = '';
    updateLayout();
} else if (shellNavigation) {
    const desktop = window.matchMedia('(min-width: 1024px)');
    const summary = shellNavigation.querySelector('summary');
    const sync = () => summary.setAttribute('aria-expanded', String(shellNavigation.open));
    const update = () => { shellNavigation.open = desktop.matches; sync(); };
    desktop.addEventListener('change', update);
    shellNavigation.addEventListener('toggle', sync);
    shellNavigation.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !desktop.matches && shellNavigation.open) {
            shellNavigation.open = false;
            summary.focus();
            event.preventDefault();
        }
    });
    update();
}
