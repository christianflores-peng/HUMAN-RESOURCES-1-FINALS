/**
 * HR1 SPA Framework
 * Handles AJAX page loading, form interception, and client-side routing
 */
const HR1SPA = {
    currentPage: null,
    contentArea: null,
    isLoading: false,
    pageCache: {},

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
                await this.submitForm(form, e.submitter || null);
            });
        });
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

// Auto-init when DOM is ready
document.addEventListener('DOMContentLoaded', () => HR1SPA.init());
