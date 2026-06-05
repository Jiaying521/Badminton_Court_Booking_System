(function () {
    const ICONS  = { success: 'fa-check', error: 'fa-xmark', pending: 'fa-exclamation', loading: 'fa-spinner' };
    const LABELS = { success: 'Success', error: 'Error', pending: 'Notice', loading: 'Loading' };
    const DEFAULT_DURATION = 4000;

    function getContainer() {
        let c = document.getElementById('toastContainer');
        if (!c) {
            c = document.createElement('div');
            c.id = 'toastContainer';
            c.className = 'toast-container';
            document.body.appendChild(c);
        }
        return c;
    }

    function buildToast(text, type) {
        const el = document.createElement('div');
        el.className = 'toast ' + type;
        el.setAttribute('data-toast', '');
        el.innerHTML =
            '<span class="toast-icon"><i class="fas ' + (ICONS[type] || 'fa-bell') + '"></i></span>' +
            '<div class="toast-body">' +
                '<span class="toast-label">' + (LABELS[type] || 'Notice') + '</span>' +
                '<span class="toast-text"></span>' +
            '</div>' +
            '<button class="toast-close" type="button" aria-label="Dismiss">&times;</button>';
        el.querySelector('.toast-text').textContent = text;
        return el;
    }

    function animateIn(el, delay) {
        setTimeout(function () { el.classList.add('show'); }, delay || 60);
    }

    function dismiss(el) {
        if (!el || el._dismissed) return;
        el._dismissed = true;
        el.classList.add('hide');
        setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 400);
    }

    function show(text, type, options) {
        type = (type || 'success').toString().toLowerCase();
        if (!ICONS[type]) type = 'success';
        options = options || {};
        const container = getContainer();
        const el = buildToast(text, type);
        container.appendChild(el);
        animateIn(el, 60);

        const closeBtn = el.querySelector('.toast-close');
        closeBtn.addEventListener('click', function () { dismiss(el); });

        if (type !== 'loading') {
            const duration = typeof options.duration === 'number' ? options.duration : DEFAULT_DURATION;
            if (duration > 0) setTimeout(function () { dismiss(el); }, duration);
        }
        return el;
    }

    /* Loading toast: progress bar fills the capsule background, spinner icon swaps to success/error tick on resolve */
    function loading(text) {
        const el = show(text, 'loading');
        const bar = document.createElement('div');
        bar.className = 'toast-progress';
        el.insertBefore(bar, el.firstChild);
        el._bar = bar;

        function morphTo(finalType, finalText) {
            el.classList.remove('loading');
            el.classList.add(finalType);
            const iconEl  = el.querySelector('.toast-icon i');
            const labelEl = el.querySelector('.toast-label');
            const textEl  = el.querySelector('.toast-text');
            if (iconEl)  iconEl.className  = 'fas ' + (ICONS[finalType] || 'fa-check');
            if (labelEl) labelEl.textContent = LABELS[finalType] || 'Success';
            if (finalText && textEl) textEl.textContent = finalText;
            bar.style.transition = 'opacity 0.4s';
            bar.style.opacity = '0';
            setTimeout(function () { dismiss(el); }, DEFAULT_DURATION);
        }

        return {
            element: el,
            update: function (pct) {
                bar.style.width = Math.max(0, Math.min(100, pct)) + '%';
            },
            resolve: function (finalText, finalType) {
                bar.style.width = '100%';
                setTimeout(function () { morphTo(finalType || 'success', finalText); }, 280);
            },
            fail: function (finalText) {
                this.resolve(finalText, 'error');
            },
            close: function () { dismiss(el); }
        };
    }

    function bootServerRendered() {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        const toasts = container.querySelectorAll('[data-toast]');
        toasts.forEach(function (el, i) {
            animateIn(el, 80 + i * 90);
            const closeBtn = el.querySelector('.toast-close');
            if (closeBtn) closeBtn.addEventListener('click', function () { dismiss(el); });
            if (!el.classList.contains('loading')) {
                setTimeout(function () { dismiss(el); }, DEFAULT_DURATION + i * 200);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootServerRendered);
    } else {
        bootServerRendered();
    }

    window.Toast = { show: show, loading: loading, dismiss: dismiss };
})();
