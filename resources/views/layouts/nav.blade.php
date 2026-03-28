<nav class="bg-slate-900 shadow-lg sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-wrap justify-between items-center gap-4 py-4">
            <div class="flex items-center gap-3">
                <a href="/" class="text-xl font-bold text-slate-100 tracking-wide">BlanksProject</a>
                <span class="hidden md:inline text-xs uppercase tracking-[0.3em] text-slate-400">Teacher Workspace</span>
            </div>

            <div class="flex items-center gap-2">
                <a href="/tests" class="nav-link text-slate-300 hover:text-white transition duration-200 font-medium px-3 py-2 rounded-lg hover:bg-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span>Тесты</span>
                </a>

                <a href="/groups" class="nav-link text-slate-300 hover:text-white transition duration-200 font-medium px-3 py-2 rounded-lg hover:bg-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5V8H2v12h5m10 0v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4m10 0H7m10-9a2 2 0 11-4 0 2 2 0 014 0zm-6 0a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span>Группы</span>
                </a>
            </div>

            <div class="flex items-center gap-4">
                <div id="guestButtons" class="flex items-center gap-3">
                    <a href="/user/login" class="text-slate-300 hover:text-white transition duration-200 font-medium px-3 py-2 rounded-lg hover:bg-slate-800">
                        Вход
                    </a>
                    <a href="/user/register" class="bg-sky-600 text-white px-4 py-2 rounded-lg hover:bg-sky-500 transition duration-200 font-medium">
                        Регистрация
                    </a>
                </div>

                <div id="userButtons" class="hidden flex items-center gap-3">
                    <a href="/user/profile" class="text-slate-300 hover:text-white transition duration-200 font-medium px-3 py-2 rounded-lg hover:bg-slate-800 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>Профиль</span>
                    </a>
                    <button onclick="logout()" class="bg-rose-600 text-white px-4 py-2 rounded-lg hover:bg-rose-500 transition duration-200 font-medium flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Выйти</span>
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

    window.addEventListener('load', updateNavigation);
    document.addEventListener('DOMContentLoaded', updateNavigation);
</script>
