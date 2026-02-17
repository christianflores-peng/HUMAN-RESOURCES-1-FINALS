/**
 * HR1 SPA Framework
 * Handles AJAX page loading, form interception, and client-side routing
 */
const HR1SPA = {
    currentPage: null,
    contentArea: null,
    isLoading: false,
    pageCache: {},
    _confirmModalEl: null,
    _confirmResolve: null,

    init() {
        this.contentArea = document.getElementById('spa-content');
        if (!this.contentArea) return;

        // Intercept sidebar nav clicks
        this.bindNavLinks();

        // Intercept all form submissions within content area
        this.bindForms();

        // Handle browser back/forward
        window.addEventListener('popstate', (e) => {
            if (e.state && e.state.page) {
                this.loadPage(e.state.page, false);
            }
        });

        // Load initial page from URL or default
        const initialPage = this.getPageFromUrl();
        if (initialPage) {
            this.loadPage(initialPage, true);
        }
    },

    getPageFromUrl() {
        const params = new URLSearchParams(window.location.search);
        return params.get('page') || 'dashboard';
    },

    bindNavLinks() {
        document.querySelectorAll('.sidebar .nav-link[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = link.getAttribute('data-page');
                if (page && page !== this.currentPage) {
                    this.loadPage(page, true);
                }
            });
        });
    },

    updateActiveNav(page) {
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('data-page') === page) {
                link.classList.add('active');
            }
        });
    },

    showLoading() {
        this.isLoading = true;
        this.contentArea.style.opacity = '0.5';
        this.contentArea.style.pointerEvents = 'none';
        // Show top loading bar
        let bar = document.getElementById('spa-loading-bar');
        if (!bar) {
            bar = document.createElement('div');
            bar.id = 'spa-loading-bar';
            document.body.appendChild(bar);
        }
        bar.className = 'spa-loading-bar active';
    },

    hideLoading() {
        this.isLoading = false;
        this.contentArea.style.opacity = '1';
        this.contentArea.style.pointerEvents = '';
        const bar = document.getElementById('spa-loading-bar');
        if (bar) bar.className = 'spa-loading-bar done';
    },

    async loadPage(page, pushState = true, params = '') {
        if (this.isLoading) return;

        this.showLoading();
        this.currentPage = page;
        this.updateActiveNav(page);

        const separator = params ? '&' : '';
        const url = `${page}.php?ajax=1${separator}${params}`;

        try {
            const response = await fetch(url, {
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (response.redirected) {
                window.location.href = response.url;
                return;
            }

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const html = await response.text();

            // Fade out, swap, fade in
            this.contentArea.style.transition = 'opacity 0.15s ease';
            this.contentArea.style.opacity = '0';

            setTimeout(() => {
                this.contentArea.innerHTML = html;
                this.contentArea.style.opacity = '1';
                this.hideLoading();

                // Re-init Lucide icons
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }

                // Re-bind forms in new content
                this.bindForms();

                // Re-bind any in-content nav links
                this.bindContentLinks();

                // Execute any inline scripts in the loaded content
                this.executeScripts();

                // Update page title
                const titleEl = this.contentArea.querySelector('[data-page-title]');
                if (titleEl) {
                    document.title = titleEl.getAttribute('data-page-title') + ' - HR1';
                }

                // Scroll to top
                this.contentArea.scrollTop = 0;
                window.scrollTo(0, 0);
            }, 150);

            // Update URL
            if (pushState) {
                const newUrl = `index.php?page=${page}${separator}${params}`;
                history.pushState({ page: page }, '', newUrl);
            }

        } catch (error) {
            console.error('SPA load error:', error);
            this.contentArea.innerHTML = `
                <div style="text-align: center; padding: 4rem 2rem;">
                    <div style="width: 70px; height: 70px; background: rgba(239, 68, 68, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                        <i data-lucide="alert-triangle" style="width: 36px; height: 36px; color: #ef4444;"></i>
                    </div>
                    <h2 style="color: #e2e8f0; margin-bottom: 0.5rem;">Failed to load page</h2>
                    <p style="color: #94a3b8; margin-bottom: 1.5rem;">${error.message}</p>
                    <button onclick="HR1SPA.loadPage('${page}')" style="background: #0ea5e9; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-size: 0.9rem;">
                        <i data-lucide="refresh-cw" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 0.5rem;"></i>
                        Try Again
                    </button>
                </div>`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            this.hideLoading();
        }
    },

    bindForms() {
        this.contentArea.querySelectorAll('form').forEach(form => {
            if (form._spabound) return;
            form._spabound = true;

            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                // Respect any other handlers that already prevented the event.
                if (e.defaultPrevented) return;

                const submitter = e.submitter || null;
                const ok = await this.confirmIfNeeded(form, submitter);
                if (!ok) return;

                await this.submitForm(form, submitter);
            });
        });
    },

    ensureConfirmModal() {
        if (this._confirmModalEl) return this._confirmModalEl;

        const wrap = document.createElement('div');
        wrap.id = 'hr1-confirm-modal';
        wrap.style.cssText = [
            'position:fixed',
            'inset:0',
            'display:none',
            'align-items:center',
            'justify-content:center',
            'background:rgba(0,0,0,0.65)',
            'z-index:4000'
        ].join(';');

        wrap.innerHTML = `
            <div style="width:min(520px,92vw);background:rgba(30,41,54,0.98);border:1px solid rgba(58,69,84,0.6);border-radius:14px;box-shadow:0 25px 70px rgba(0,0,0,0.55);overflow:hidden;">
                <div style="padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(58,69,84,0.5);">
                    <div id="hr1-confirm-title" style="margin:0;font-size:1rem;color:#e2e8f0;font-weight:700;display:flex;align-items:center;gap:0.5rem;"></div>
                    <button type="button" id="hr1-confirm-x" aria-label="Close" style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:1.2rem;padding:0.25rem 0.4rem;">&times;</button>
                </div>
                <div style="padding:1.1rem 1.25rem;color:#cbd5e1;font-size:0.9rem;line-height:1.45;">
                    <div id="hr1-confirm-message"></div>
                </div>
                <div style="padding:1rem 1.25rem;display:flex;justify-content:flex-end;gap:0.6rem;border-top:1px solid rgba(58,69,84,0.5);">
                    <button type="button" id="hr1-confirm-cancel" style="border:none;border-radius:10px;padding:0.55rem 0.9rem;cursor:pointer;font-size:0.85rem;font-weight:700;background:rgba(100,116,139,0.25);color:#e2e8f0;border:1px solid rgba(100,116,139,0.35);">Cancel</button>
                    <button type="button" id="hr1-confirm-ok" style="border:none;border-radius:10px;padding:0.55rem 0.9rem;cursor:pointer;font-size:0.85rem;font-weight:800;background:rgba(14,165,233,0.18);color:#dbeafe;border:1px solid rgba(14,165,233,0.35);">Confirm</button>
                </div>
            </div>
        `;

        const close = () => {
            wrap.style.display = 'none';
        };
        const resolve = (val) => {
            const fn = this._confirmResolve;
            this._confirmResolve = null;
            close();
            if (typeof fn === 'function') fn(val);
        };

        wrap.addEventListener('click', (e) => {
            if (e.target === wrap) resolve(false);
        });
        document.addEventListener('keydown', (e) => {
            if (wrap.style.display === 'flex' && e.key === 'Escape') resolve(false);
        });
        wrap.querySelector('#hr1-confirm-x')?.addEventListener('click', () => resolve(false));
        wrap.querySelector('#hr1-confirm-cancel')?.addEventListener('click', () => resolve(false));
        wrap.querySelector('#hr1-confirm-ok')?.addEventListener('click', () => resolve(true));

        document.body.appendChild(wrap);
        this._confirmModalEl = wrap;
        return wrap;
    },

    confirmDialog({
        title = 'Please confirm',
        message = 'Are you sure?',
        confirmText = 'Confirm',
        cancelText = 'Cancel',
        danger = false
    } = {}) {
        const modal = this.ensureConfirmModal();

        const titleEl = modal.querySelector('#hr1-confirm-title');
        const msgEl = modal.querySelector('#hr1-confirm-message');
        const okBtn = modal.querySelector('#hr1-confirm-ok');
        const cancelBtn = modal.querySelector('#hr1-confirm-cancel');

        if (titleEl) {
            const icon = danger
                ? '<i data-lucide="alert-triangle" style="width:18px;height:18px;color:#ef4444;"></i>'
                : '<i data-lucide="help-circle" style="width:18px;height:18px;color:#38bdf8;"></i>';
            titleEl.innerHTML = `${icon}<span>${String(title)}</span>`;
        }
        if (msgEl) msgEl.textContent = String(message);

        if (okBtn) {
            okBtn.textContent = String(confirmText);
            okBtn.style.background = danger ? 'rgba(239,68,68,0.15)' : 'rgba(14,165,233,0.18)';
            okBtn.style.borderColor = danger ? 'rgba(239,68,68,0.35)' : 'rgba(14,165,233,0.35)';
            okBtn.style.color = danger ? '#fecaca' : '#dbeafe';
        }
        if (cancelBtn) cancelBtn.textContent = String(cancelText);

        if (typeof lucide !== 'undefined') {
            try { lucide.createIcons(); } catch (e) {}
        }

        modal.style.display = 'flex';
        return new Promise((resolve) => {
            this._confirmResolve = resolve;
        });
    },

    async confirmIfNeeded(form, submitter = null) {
        const message = (submitter && (submitter.dataset.confirm || submitter.dataset.confirmMessage))
            || form.dataset.confirm
            || form.dataset.confirmMessage
            || '';

        if (!message) return true;

        const title = (submitter && submitter.dataset.confirmTitle)
            || form.dataset.confirmTitle
            || 'Please confirm';
        const confirmText = (submitter && submitter.dataset.confirmOk)
            || form.dataset.confirmOk
            || ((submitter && submitter.dataset.confirmVariant === 'danger') ? 'Yes, Continue' : 'Confirm');
        const cancelText = (submitter && submitter.dataset.confirmCancel)
            || form.dataset.confirmCancel
            || 'Cancel';
        const danger = ((submitter && submitter.dataset.confirmVariant === 'danger')
            || form.dataset.confirmVariant === 'danger');

        return await this.confirmDialog({ title, message, confirmText, cancelText, danger });
    },

    async submitForm(form, submitter = null) {
        const submitBtn = form.querySelector('[type="submit"]');
        const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader-2" class="spin" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-right:0.5rem;"></i> Processing...';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        try {
            const formData = new FormData(form);
            // Preserve clicked submit button name/value for server-side action routing.
            // Many backend handlers rely on button names (e.g., request_toggle, verify_toggle_otp).
            if (submitter && submitter.name && !formData.has(submitter.name)) {
                formData.append(submitter.name, submitter.value || '1');
            }
            const method = (form.getAttribute('method') || 'POST').toUpperCase();
            const rawAction = (form.getAttribute('action') || '').trim();
            const action = (!rawAction || rawAction === '#')
                ? `${this.currentPage}.php?ajax=1`
                : rawAction;

            let requestUrl = action;
            const fetchOptions = {
                method: method,
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            };

            if (method === 'GET') {
                const urlObj = new URL(action, window.location.href);
                for (const [key, value] of formData.entries()) {
                    urlObj.searchParams.set(key, value);
                }
                requestUrl = urlObj.toString();
            } else {
                fetchOptions.body = formData;
            }

            const response = await fetch(requestUrl, fetchOptions);

            const contentType = response.headers.get('content-type') || '';

            if (contentType.includes('application/json')) {
                const data = await response.json();
                this.handleJsonResponse(data, form);
            } else {
                // HTML response — replace content
                const html = await response.text();
                this.contentArea.innerHTML = html;
                if (typeof lucide !== 'undefined') lucide.createIcons();
                this.bindForms();
                this.bindContentLinks();
                this.executeScripts();
            }
        } catch (error) {
            console.error('Form submit error:', error);
            this.showToast('An error occurred. Please try again.', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        }
    },

    handleJsonResponse(data, form) {
        if (data.success) {
            this.showToast(data.message || 'Action completed successfully.', 'success');
            if (data.redirect) {
                this.loadPage(data.redirect);
            } else if (data.reload) {
                this.loadPage(this.currentPage);
            } else if (data.html) {
                this.contentArea.innerHTML = data.html;
                if (typeof lucide !== 'undefined') lucide.createIcons();
                this.bindForms();
                this.bindContentLinks();
                this.executeScripts();
            }
        } else {
            this.showToast(data.message || 'An error occurred.', 'error');
        }
    },

    bindContentLinks() {
        this.contentArea.querySelectorAll('a[data-page]').forEach(link => {
            if (link._spabound) return;
            link._spabound = true;
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = link.getAttribute('data-page');
                const params = link.getAttribute('data-params') || '';
                if (page) this.loadPage(page, true, params);
            });
        });
    },

    executeScripts() {
        this.contentArea.querySelectorAll('script').forEach(oldScript => {
            const newScript = document.createElement('script');
            if (oldScript.src) {
                newScript.src = oldScript.src;
            } else {
                newScript.textContent = oldScript.textContent;
            }
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    },

    showToast(message, type = 'success') {
        // Remove existing toasts
        document.querySelectorAll('.spa-toast').forEach(t => t.remove());

        const toast = document.createElement('div');
        toast.className = `spa-toast spa-toast-${type}`;
        const icon = type === 'success' ? 'check-circle' : 'alert-circle';
        toast.innerHTML = `<i data-lucide="${icon}" style="width:18px;height:18px;flex-shrink:0;"></i><span>${message}</span>`;
        document.body.appendChild(toast);

        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Animate in
        requestAnimationFrame(() => toast.classList.add('show'));

        // Auto dismiss
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
};

// Make available to inline scripts loaded via SPA (global scope access may not be on window).
try { window.HR1SPA = HR1SPA; } catch (e) {}

// Auto-init when DOM is ready
document.addEventListener('DOMContentLoaded', () => HR1SPA.init());
