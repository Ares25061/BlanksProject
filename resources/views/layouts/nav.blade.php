<style>
    .proverium-nav-link[data-active="true"] {
        background: rgba(14, 165, 233, 0.12);
        color: #0369a1;
        box-shadow: inset 0 0 0 1px rgba(14, 165, 233, 0.18);
    }

    html.dark .proverium-nav-link[data-active="true"] {
        background: rgba(56, 189, 248, 0.18);
        color: #e0f2fe;
        box-shadow: inset 0 0 0 1px rgba(56, 189, 248, 0.22);
    }

    .proverium-profile-button {
        background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
        color: #ffffff;
        box-shadow: 0 18px 38px -28px rgba(37, 99, 235, 0.85);
    }

    .proverium-profile-button:hover {
        filter: brightness(1.06);
    }

    .proverium-profile-button[data-active="true"] {
        box-shadow:
            0 20px 42px -28px rgba(79, 70, 229, 0.92),
            inset 0 0 0 1px rgba(255, 255, 255, 0.18);
    }
</style>

<nav class="sticky top-0 z-50 border-b border-black/5 bg-white/82 text-slate-900 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-slate-950/82 dark:text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-4 py-3">
            <div class="flex min-w-0 items-center gap-4">
                <a href="/" class="flex min-w-0 items-center gap-3 rounded-3xl px-2 py-1.5 transition hover:bg-slate-100/80 dark:hover:bg-slate-900/70">
                    <img src="{{ asset('brand/proverium-mark.svg') }}"
                         alt="Логотип Провериум"
                         class="h-11 w-11 shrink-0 rounded-2xl shadow-halo">
                    <div class="min-w-0">
                        <div class="truncate text-lg font-extrabold tracking-tight text-slate-950 dark:text-white">
                            Провериум
                        </div>
                        <div class="hidden truncate text-[10px] uppercase tracking-[0.28em] text-slate-500 md:block dark:text-slate-400">
                            Платформа авто проверки работ
                        </div>
                    </div>
                </a>

                <div class="hidden lg:flex items-center gap-2">
                    <a href="/take-test" data-nav-match="/take-test" class="proverium-nav-link inline-flex items-center gap-2 rounded-2xl px-3 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-50 hover:text-emerald-900 dark:text-emerald-300 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-5.197-3.03A1 1 0 0 0 8 9.03v5.94a1 1 0 0 0 1.555.832l5.197-3.03a1 1 0 0 0 0-1.664Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                        <span>Пройти тест</span>
                    </a>

                    <a href="/tests" data-auth-guard="true" data-nav-match="/tests" class="proverium-nav-link inline-flex items-center gap-2 rounded-2xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/>
                        </svg>
                        <span>Тесты</span>
                    </a>

                    <a href="/groups" data-auth-guard="true" data-nav-match="/groups" class="proverium-nav-link inline-flex items-center gap-2 rounded-2xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5V8H2v12h5m10 0v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4m10 0H7m10-9a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm-6 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0z"/>
                        </svg>
                        <span>Группы</span>
                    </a>
                </div>
            </div>

            <div class="hidden md:flex items-center gap-2">
                <div data-guest-buttons class="flex items-center gap-2">
                    <a href="/user/login" class="rounded-2xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">
                        Вход
                    </a>
                    <a href="/user/register" class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">
                        Регистрация
                    </a>
                </div>

                <div data-user-buttons class="hidden flex items-center gap-2">
                    <a href="/user/profile" data-nav-match="/user/profile,/user/edit" class="proverium-profile-button rounded-2xl px-4 py-2 text-sm font-medium transition">
                        Профиль
                    </a>
                    <button onclick="logout()" class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">
                        Выйти
                    </button>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 border-t border-slate-200/70 pb-3 pt-3 dark:border-slate-800 lg:hidden">
            <a href="/take-test" data-nav-match="/take-test" class="proverium-nav-link inline-flex items-center gap-2 rounded-2xl px-3 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-50 hover:text-emerald-900 dark:text-emerald-300 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-100">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-5.197-3.03A1 1 0 0 0 8 9.03v5.94a1 1 0 0 0 1.555.832l5.197-3.03a1 1 0 0 0 0-1.664Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <span>Пройти тест</span>
            </a>

            <a href="/tests" data-auth-guard="true" data-nav-match="/tests" class="proverium-nav-link inline-flex items-center gap-2 rounded-2xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/>
                </svg>
                <span>Тесты</span>
            </a>

            <a href="/groups" data-auth-guard="true" data-nav-match="/groups" class="proverium-nav-link inline-flex items-center gap-2 rounded-2xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5V8H2v12h5m10 0v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4m10 0H7m10-9a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm-6 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0z"/>
                </svg>
                <span>Группы</span>
            </a>

            <div class="ml-auto flex items-center gap-2">
                <div data-guest-buttons class="flex items-center gap-2">
                    <a href="/user/login" class="rounded-2xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">
                        Вход
                    </a>
                    <a href="/user/register" class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">
                        Регистрация
                    </a>
                </div>

                <div data-user-buttons class="hidden flex items-center gap-2">
                    <a href="/user/profile" data-nav-match="/user/profile,/user/edit" class="proverium-profile-button rounded-2xl px-4 py-2 text-sm font-medium transition">
                        Профиль
                    </a>
                    <button onclick="logout()" class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">
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

    function isNavLinkActive(link, currentPath) {
        const matches = String(link.dataset.navMatch || '')
            .split(',')
            .map((item) => item.trim())
            .filter(Boolean);

        if (!matches.length) {
            return false;
        }

        return matches.some((match) => currentPath === match || currentPath.startsWith(`${match}/`));
    }

    function updateNavigation() {
        const isAuthenticated = checkAuth();
        const currentPath = window.location.pathname;

        document.querySelectorAll('[data-guest-buttons]').forEach((element) => {
            element.classList.toggle('hidden', isAuthenticated);
        });

        document.querySelectorAll('[data-user-buttons]').forEach((element) => {
            element.classList.toggle('hidden', !isAuthenticated);
        });

        document.querySelectorAll('[data-auth-guard="true"]').forEach((link) => {
            if (link.dataset.authGuardBound === 'true') {
                return;
            }

            link.dataset.authGuardBound = 'true';
            link.addEventListener('click', (event) => {
                if (!checkAuth()) {
                    event.preventDefault();
                    redirectToLogin('missing');
                }
            });
        });

        document.querySelectorAll('.proverium-nav-link, .proverium-profile-button').forEach((link) => {
            link.dataset.active = isNavLinkActive(link, currentPath) ? 'true' : 'false';
        });
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
                        Authorization: `Bearer ${token}`,
                        Accept: 'application/json'
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
                Authorization: `Bearer ${getStoredToken()}`,
                Accept: accept,
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
                    Authorization: `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
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

    window.addEventListener('load', updateNavigation);
    document.addEventListener('DOMContentLoaded', updateNavigation);
</script>
