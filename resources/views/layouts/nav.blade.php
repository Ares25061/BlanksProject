<nav class="sticky top-0 z-50 border-b border-black/5 bg-white/72 text-slate-900 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-slate-950/78 dark:text-white">
    <div class="mx-auto max-w-6xl px-4">
        <div class="flex flex-wrap items-center justify-between gap-3 py-3">
            <div class="flex min-w-0 items-center gap-3">
                <a href="/" class="flex min-w-0 items-center gap-2.5">
                    <img src="{{ asset('brand/proverium-mark.svg') }}"
                         alt="Логотип Провериум"
                         class="h-10 w-10 shrink-0 rounded-2xl shadow-halo">
                    <div class="min-w-0">
                        <div class="truncate text-lg font-extrabold tracking-tight text-slate-950 dark:text-white">
                            Провериум
                        </div>
                        <div class="hidden truncate text-[10px] uppercase tracking-[0.28em] text-slate-500 md:block dark:text-slate-400">
                            Платформа авто проверки работ
                        </div>
                    </div>
                </a>

                <div class="hidden items-center gap-1.5 lg:flex">
                    <a href="/tests" class="nav-link inline-flex items-center gap-2 rounded-2xl px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/>
                        </svg>
                        <span>Тесты</span>
                    </a>

                    <a href="/groups" class="nav-link inline-flex items-center gap-2 rounded-2xl px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 20h5V8H2v12h5m10 0v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4m10 0H7m10-9a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm-6 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0z"/>
                        </svg>
                        <span>Группы</span>
                    </a>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <div id="guestButtons" class="flex items-center gap-2">
                    <a href="/user/login" class="rounded-2xl px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">
                        Вход
                    </a>
                    <a href="/user/register" class="rounded-2xl bg-slate-900 px-4 py-1.5 text-sm font-medium text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">
                        Регистрация
                    </a>
                </div>

                <div id="userButtons" class="hidden items-center gap-2">
                    <a href="/user/profile" class="rounded-2xl px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">
                        Профиль
                    </a>
                    <button onclick="logout()" class="rounded-2xl bg-slate-900 px-4 py-1.5 text-sm font-medium text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">
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

        if (checkAuth()) {
            guestButtons?.classList.add('hidden');
            userButtons?.classList.remove('hidden');
        } else {
            guestButtons?.classList.remove('hidden');
            userButtons?.classList.add('hidden');
        }
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
