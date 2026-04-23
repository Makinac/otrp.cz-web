/**
 * Main vanilla JS — RDR2 dark style UI behaviours.
 */
(function () {
    'use strict';

    // ── User dropdown (JS toggle — CSS :focus-within as fallback) ────────
    const dropdown    = document.getElementById('userDropdown');
    const dropTrigger = document.getElementById('userDropdownTrigger');

    if (dropdown && dropTrigger) {
        dropTrigger.addEventListener('click', function () {
            const isOpen = dropdown.hasAttribute('data-open');
            dropdown.toggleAttribute('data-open', !isOpen);
            dropTrigger.setAttribute('aria-expanded', String(!isOpen));
        });

        document.addEventListener('click', function (e) {
            if (!dropdown.contains(e.target)) {
                dropdown.removeAttribute('data-open');
                dropTrigger.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                dropdown.removeAttribute('data-open');
                dropTrigger.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ── Active nav link highlight ─────────────────────────────────────────
    const path = window.location.pathname;
    document.querySelectorAll('.site-nav a').forEach(function (link) {
        const href = link.getAttribute('href');
        if (href === '/' ? path === '/' : (path === href || path.startsWith(href + '/'))) {
            link.style.color        = '#ffffff';
            link.style.borderBottomColor = '#cc0000';
        }
    });

    // ── Mobile navigation toggle ──────────────────────────────────────────
    const toggle = document.querySelector('.nav-toggle');
    const nav    = document.querySelector('.site-nav');

    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            const isOpen = nav.classList.toggle('open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            toggle.innerHTML = isOpen ? '&#10005;' : '&#9776;';
        });
    }

    // ── Reject modal (Tester panel) ───────────────────────────────────────
    const modal           = document.getElementById('rejectModal');
    const confirmBtn      = document.getElementById('confirmReject');
    const cancelBtn       = document.getElementById('cancelReject');
    const errorCountInput = document.getElementById('modalErrorCount');

    let activeRejectForm = null;

    document.querySelectorAll('.reject-trigger').forEach(function (btn) {
        btn.addEventListener('click', function () {
            activeRejectForm = btn.closest('.reject-form');
            if (errorCountInput) errorCountInput.value = '0';
            if (modal) {
                modal.hidden = false;
                errorCountInput && errorCountInput.focus();
            }
        });
    });

    if (cancelBtn && modal) {
        cancelBtn.addEventListener('click', function () {
            modal.hidden = true;
            activeRejectForm = null;
        });
    }

    if (confirmBtn && modal) {
        confirmBtn.addEventListener('click', function () {
            if (!activeRejectForm) return;
            const count = parseInt(errorCountInput ? errorCountInput.value : '0', 10);
            if (isNaN(count) || count < 0) {
                alert('Zadej platný počet chyb (≥ 0).');
                return;
            }
            const hidden = activeRejectForm.querySelector('.reject-error-count');
            if (hidden) hidden.value = String(count);
            modal.hidden = true;
            activeRejectForm.submit();
        });
    }

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                modal.hidden = true;
                activeRejectForm = null;
            }
        });
        // Close on Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hidden) {
                modal.hidden = true;
                activeRejectForm = null;
            }
        });
    }

    // ── Auto-dismiss flash messages after 5 seconds ───────────────────────
    document.querySelectorAll('.flash').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity 0.4s, max-height 0.4s';
            el.style.opacity    = '0';
            el.style.maxHeight  = '0';
            el.style.overflow   = 'hidden';
            setTimeout(function () { el.remove(); }, 420);
        }, 5000);
    });

    // ── Subtle scroll-in for cards ────────────────────────────────────────
    if ('IntersectionObserver' in window) {
        const style = document.createElement('style');
        style.textContent = '.reveal{opacity:0;transform:translateY(18px);transition:opacity .45s ease,transform .45s ease}.reveal.visible{opacity:1;transform:none}';
        document.head.appendChild(style);

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.card, .news-card, .team-card, .tester-card').forEach(function (el) {
            el.classList.add('reveal');
            observer.observe(el);
        });
    }

})();

(function () {
    'use strict';

    // ── Reject modal (Tester panel) ───────────────────────────────────────
    const modal         = document.getElementById('rejectModal');
    const confirmBtn    = document.getElementById('confirmReject');
    const cancelBtn     = document.getElementById('cancelReject');
    const errorCountInput = document.getElementById('modalErrorCount');

    let activeRejectForm = null;

    document.querySelectorAll('.reject-trigger').forEach(function (btn) {
        btn.addEventListener('click', function () {
            activeRejectForm = btn.closest('.reject-form');
            if (errorCountInput) {
                errorCountInput.value = '0';
            }
            if (modal) {
                modal.hidden = false;
                errorCountInput && errorCountInput.focus();
            }
        });
    });

    if (cancelBtn && modal) {
        cancelBtn.addEventListener('click', function () {
            modal.hidden = true;
            activeRejectForm = null;
        });
    }

    if (confirmBtn && modal) {
        confirmBtn.addEventListener('click', function () {
            if (!activeRejectForm) { return; }
            const count = parseInt(errorCountInput ? errorCountInput.value : '0', 10);
            if (isNaN(count) || count < 0) {
                alert('Zadej platný počet chyb (≥ 0).');
                return;
            }
            const hidden = activeRejectForm.querySelector('.reject-error-count');
            if (hidden) { hidden.value = String(count); }
            modal.hidden = true;
            activeRejectForm.submit();
        });
    }

    // Close modal on backdrop click.
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                modal.hidden = true;
                activeRejectForm = null;
            }
        });
    }

    // ── Auto-dismiss flash messages after 6 seconds ───────────────────────
    document.querySelectorAll('.flash').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity    = '0';
            setTimeout(function () { el.remove(); }, 500);
        }, 6000);
    });

    // ── Scrollable navs: gradient fade + drag to scroll ─────────────────
    document.querySelectorAll('.app-filter-tabs').forEach(function (nav) {
        var maxScroll = nav.scrollWidth - nav.clientWidth;
        if (maxScroll <= 2) return; // No scrolling needed

        // Wrap nav in a container for fade overlays
        var wrap = document.createElement('div');
        wrap.className = 'scroll-nav-wrap';
        nav.parentNode.insertBefore(wrap, nav);
        wrap.appendChild(nav);

        var fadeL = document.createElement('div');
        var fadeR = document.createElement('div');
        fadeL.className = 'scroll-nav-fade scroll-nav-fade-left';
        fadeR.className = 'scroll-nav-fade scroll-nav-fade-right';
        wrap.appendChild(fadeL);
        wrap.appendChild(fadeR);

        function updateFades() {
            var sl = nav.scrollLeft;
            var max = nav.scrollWidth - nav.clientWidth;
            fadeL.style.opacity = sl > 4 ? '1' : '0';
            fadeR.style.opacity = sl < max - 4 ? '1' : '0';
        }

        nav.addEventListener('scroll', updateFades, { passive: true });
        window.addEventListener('resize', function () {
            updateFades();
        });
        updateFades();

        // Drag to scroll
        var isDragging = false;
        var startX = 0;
        var scrollStart = 0;
        var moved = false;

        nav.addEventListener('mousedown', function (e) {
            if (e.button !== 0) return;
            isDragging = true;
            moved = false;
            startX = e.pageX;
            scrollStart = nav.scrollLeft;
            nav.style.userSelect = 'none';
        });

        window.addEventListener('mousemove', function (e) {
            if (!isDragging) return;
            var dx = e.pageX - startX;
            if (Math.abs(dx) > 3) moved = true;
            nav.scrollLeft = scrollStart - dx;
        });

        window.addEventListener('mouseup', function () {
            if (!isDragging) return;
            isDragging = false;
            nav.style.userSelect = '';
        });

        // Prevent click on links after dragging
        nav.addEventListener('click', function (e) {
            if (moved) {
                e.preventDefault();
                e.stopPropagation();
                moved = false;
            }
        }, true);
    });

    // ── Panel nav dropdown toggle ─────────────────────────────────────────
    document.querySelectorAll('.panel-nav-wrap').forEach(function (wrap) {
        var toggle = wrap.querySelector('.panel-nav-toggle');
        if (!toggle) return;
        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            var isOpen = wrap.hasAttribute('data-open');
            wrap.toggleAttribute('data-open', !isOpen);
            toggle.setAttribute('aria-expanded', String(!isOpen));
        });
    });

    // Close on outside click
    document.addEventListener('click', function (e) {
        document.querySelectorAll('.panel-nav-wrap[data-open]').forEach(function (wrap) {
            if (!wrap.contains(e.target)) {
                wrap.removeAttribute('data-open');
                var t = wrap.querySelector('.panel-nav-toggle');
                if (t) t.setAttribute('aria-expanded', 'false');
            }
        });
    });

    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.panel-nav-wrap[data-open]').forEach(function (wrap) {
                wrap.removeAttribute('data-open');
                var t = wrap.querySelector('.panel-nav-toggle');
                if (t) t.setAttribute('aria-expanded', 'false');
            });
        }
    });

})();
