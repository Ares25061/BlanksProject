<nav class="sticky top-0 z-50 border-b border-slate-800 bg-slate-900 text-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-wrap justify-between items-center gap-4 py-4">
            <div class="flex items-center gap-4 min-w-0">
                <a href="/" class="flex items-center gap-3 min-w-0">
                    <img src="{{ asset('brand/proverium-mark.svg') }}"
                         alt="Логотип Провериум"
                         class="h-11 w-11 shrink-0 rounded-2xl shadow-halo">
                    <div class="min-w-0">
                        <div class="text-xl font-extrabold tracking-tight text-white truncate">
                            {{ config('app.name', 'Провериум') }}
                        </div>
                        <div class="hidden md:block text-[11px] uppercase tracking-[0.34em] text-slate-400 truncate">
                            Платформа авто проверки работ
                        </div>
                    </div>
                </a>

                <div class="hidden lg:flex items-center gap-2">
                    <a href="/tests" class="nav-link inline-flex items-center gap-2 rounded-2xl px-3 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10 hover:text-white dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <span>Тесты</span>
                    </a>

                    <a href="/groups" class="nav-link inline-flex items-center gap-2 rounded-2xl px-3 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10 hover:text-white dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 20h5V8H2v12h5m10 0v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4m10 0H7m10-9a2 2 0 11-4 0 2 2 0 014 0zm-6 0a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span>Группы</span>
                    </a>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button id="themeToggle"
                        type="button"
                        onclick="toggleTheme()"
                        class="inline-flex items-center gap-2 rounded-2xl border border-slate-700 bg-slate-800 px-3 py-2 text-sm font-medium text-slate-100 transition hover:border-slate-500 hover:bg-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-800">
                    <span id="themeToggleIcon" aria-hidden="true"></span>
                    <span id="themeToggleLabel" class="hidden sm:inline">Тема</span>
                </button>

                <div id="guestButtons" class="flex items-center gap-3">
                    <a href="/user/login" class="rounded-2xl px-3 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10 hover:text-white dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">
                        Вход
                    </a>
                    <a href="/user/register" class="rounded-2xl bg-white px-4 py-2 text-sm font-medium text-slate-900 transition hover:bg-slate-100">
                        Регистрация
                    </a>
                </div>

                <div id="userButtons" class="hidden flex items-center gap-3">
                    <a href="/user/profile" class="rounded-2xl px-3 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10 hover:text-white dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">
                        Профиль
                    </a>
                    <button onclick="logout()" class="rounded-2xl bg-white px-4 py-2 text-sm font-medium text-slate-900 transition hover:bg-slate-100 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">
                        Выйти
                    </button>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
    function getStoredToken() {
        return localStorage.getItem('auth_token');
    }

    function getStoredUser() {
        const raw = localStorage.getItem('user');
        if (!raw) {
            return null;
        }

        try {
            return JSON.parse(raw);
        } catch (error) {
            localStorage.removeItem('user');
            return null;
        }
    }

    function setAuthState(payload = {}) {
        const token = payload.authorization?.token || payload.token || null;
        if (token) {
            localStorage.setItem('auth_token', token);
        }

        if (payload.user) {
            localStorage.setItem('user', JSON.stringify(payload.user));
        }
    }

    function clearAuthState() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');
    }

    function isAuthPage() {
        return window.location.pathname === '/user/login' || window.location.pathname === '/user/register';
    }

    function redirectToLogin(reason = 'expired') {
        if (isAuthPage()) {
            return;
        }

        const next = `${window.location.pathname}${window.location.search}${window.location.hash}`;
        const params = new URLSearchParams();
        if (next && next !== '/user/login') {
            params.set('next', next);
        }
        if (reason) {
            params.set('reason', reason);
        }

        const query = params.toString();
        window.location.href = `/user/login${query ? `?${query}` : ''}`;
    }

    function checkAuth() {
        return Boolean(getStoredToken());
    }

    function updateNavigation() {
        const guestButtons = document.getElementById('guestButtons');
        const userButtons = document.getElementById('userButtons');

        document.querySelectorAll('.nav-link').forEach((link) => {
            if (!checkAuth()) {
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    redirectToLogin('missing');
                }, { once: true });
            }
        });

        if (checkAuth()) {
            guestButtons?.classList.add('hidden');
            userButtons?.classList.remove('hidden');
        } else {
            guestButtons?.classList.remove('hidden');
            userButtons?.classList.add('hidden');
        }
    }

    function getThemeIcon(theme) {
        if (theme === 'dark') {
            return `
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2.25m0 13.5V21m9-9h-2.25M5.25 12H3m15.114 6.364-1.591-1.591M7.477 7.477 5.886 5.886m12.228 0-1.591 1.591M7.477 16.523l-1.591 1.591M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
                </svg>
            `;
        }

        return `
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 0 1 11.21 3c0 .34-.03.67-.08 1A9 9 0 1 0 20 12.87c.34-.02.67-.05 1-.08Z"/>
            </svg>
        `;
    }

    function updateThemeToggle() {
        const currentTheme = window.__proveriumTheme?.get?.() || 'light';
        const icon = document.getElementById('themeToggleIcon');
        const label = document.getElementById('themeToggleLabel');

        if (icon) {
            icon.innerHTML = getThemeIcon(currentTheme);
        }

        if (label) {
            label.textContent = currentTheme === 'dark' ? 'Светлая' : 'Тёмная';
        }
    }

    function toggleTheme() {
        window.__proveriumTheme?.toggle?.();
        updateThemeToggle();
    }

    let refreshPromise = null;

    async function refreshAuthToken({ suppressRedirect = false } = {}) {
        if (refreshPromise) {
            return refreshPromise;
        }

        const token = getStoredToken();
        if (!token) {
            clearAuthState();
            updateNavigation();
            if (!suppressRedirect) {
                redirectToLogin('missing');
            }
            return false;
        }

        refreshPromise = (async () => {
            try {
                const response = await fetch('/api/refresh', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    clearAuthState();
                    updateNavigation();
                    if (!suppressRedirect) {
                        redirectToLogin('expired');
                    }
                    return false;
                }

                const data = await response.json();
                setAuthState(data);
                updateNavigation();
                return true;
            } catch (error) {
                console.error('Refresh error:', error);
                clearAuthState();
                updateNavigation();
                if (!suppressRedirect) {
                    redirectToLogin('expired');
                }
                return false;
            } finally {
                refreshPromise = null;
            }
        })();

        return refreshPromise;
    }

    async function authApiFetch(url, options = {}, extra = {}) {
        const {
            accept = 'application/json',
            suppressRedirect = false,
            retryOn401 = true,
        } = extra;

        const request = () => fetch(url, {
            ...options,
            headers: {
                'Authorization': `Bearer ${getStoredToken()}`,
                'Accept': accept,
                ...(options.headers || {})
            }
        });

        if (!getStoredToken()) {
            clearAuthState();
            updateNavigation();
            if (!suppressRedirect) {
                redirectToLogin('missing');
            }
            return new Response(null, { status: 401 });
        }

        let response = await request();

        if (response.status === 401 && retryOn401) {
            const refreshed = await refreshAuthToken({ suppressRedirect: true });
            if (refreshed) {
                response = await request();
            } else if (!suppressRedirect) {
                redirectToLogin('expired');
            }
        }

        return response;
    }

    async function ensureAuthenticatedPage({ redirectIfMissing = true } = {}) {
        if (getStoredToken()) {
            return true;
        }

        clearAuthState();
        updateNavigation();
        if (redirectIfMissing) {
            redirectToLogin('missing');
        }
        return false;
    }

    async function redirectAuthenticatedUser(target = '/user/profile') {
        if (!getStoredToken()) {
            return false;
        }

        const refreshed = await refreshAuthToken({ suppressRedirect: true });
        if (!refreshed) {
            clearAuthState();
            updateNavigation();
            return false;
        }

        window.location.href = target;
        return true;
    }

    async function logout() {
        const token = getStoredToken();

        try {
            await fetch('/api/logout', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            clearAuthState();
            updateNavigation();
            window.location.href = '/';
        }
    }

    window.getStoredToken = getStoredToken;
    window.getStoredUser = getStoredUser;
    window.setAuthState = setAuthState;
    window.clearAuthState = clearAuthState;
    window.refreshAuthToken = refreshAuthToken;
    window.authApiFetch = authApiFetch;
    window.ensureAuthenticatedPage = ensureAuthenticatedPage;
    window.redirectAuthenticatedUser = redirectAuthenticatedUser;
    window.redirectToLogin = redirectToLogin;
    window.toggleTheme = toggleTheme;

    window.addEventListener('load', () => {
        updateNavigation();
        updateThemeToggle();
    });
    window.addEventListener('proverium:theme-change', updateThemeToggle);
    document.addEventListener('DOMContentLoaded', () => {
        updateNavigation();
        updateThemeToggle();
    });
</script>
