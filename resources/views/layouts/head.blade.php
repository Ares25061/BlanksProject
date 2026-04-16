@php
    $pageTitle = trim((string) ($title ?? ''));
    $appName = config('app.name', 'Провериум');
    $documentTitle = $pageTitle !== '' ? $pageTitle.' | '.$appName : $appName;
@endphp
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $documentTitle }}</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#0f172a">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<link rel="shortcut icon" href="{{ asset('favicon.png') }}">
<script>
    (() => {
        const storageKey = 'proverium-theme';
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        const storedTheme = localStorage.getItem(storageKey);
        const theme = storedTheme === 'light' || storedTheme === 'dark'
            ? storedTheme
            : (mediaQuery.matches ? 'dark' : 'light');

        document.documentElement.classList.toggle('dark', theme === 'dark');
        document.documentElement.dataset.theme = theme;
        document.documentElement.style.colorScheme = theme;

        window.__proveriumTheme = {
            storageKey,
            get() {
                return document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';
            },
            set(nextTheme) {
                const themeValue = nextTheme === 'dark' ? 'dark' : 'light';
                document.documentElement.classList.toggle('dark', themeValue === 'dark');
                document.documentElement.dataset.theme = themeValue;
                document.documentElement.style.colorScheme = themeValue;
                localStorage.setItem(storageKey, themeValue);
                window.dispatchEvent(new CustomEvent('proverium:theme-change', {
                    detail: {
                        theme: themeValue
                    }
                }));
            },
            toggle() {
                this.set(this.get() === 'dark' ? 'light' : 'dark');
            }
        };
    })();
</script>
<script>
    window.tailwind = window.tailwind || {};
    window.tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    brand: {
                        50: '#eef4ff',
                        100: '#dbe8ff',
                        200: '#bed3ff',
                        300: '#92b0ff',
                        400: '#6687ff',
                        500: '#5564f0',
                        600: '#4b4dd6',
                        700: '#4241af',
                        800: '#34357d',
                        900: '#21244d',
                    },
                    mint: {
                        400: '#36d6a2',
                        500: '#1cc794',
                        600: '#10ab7e',
                    }
                },
                boxShadow: {
                    halo: '0 24px 60px -28px rgba(74, 78, 214, 0.38)',
                }
            }
        }
    };
</script>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    body {
        transition: background-color 0.25s ease, color 0.25s ease;
    }

    .proverium-panel {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(148, 163, 184, 0.24);
        box-shadow: 0 28px 70px -42px rgba(15, 23, 42, 0.18);
        backdrop-filter: blur(18px);
    }

    html.dark body {
        background:
            radial-gradient(circle at top left, rgba(86, 102, 240, 0.18), transparent 28rem),
            radial-gradient(circle at top right, rgba(28, 199, 148, 0.14), transparent 24rem),
            #020617;
        color: #e2e8f0;
    }

    html.dark .proverium-panel {
        background: rgba(15, 23, 42, 0.82);
        border-color: rgba(71, 85, 105, 0.54);
        box-shadow: 0 28px 70px -42px rgba(2, 6, 23, 0.86);
    }

    html.dark input,
    html.dark select,
    html.dark textarea {
        background-color: rgba(15, 23, 42, 0.92);
        border-color: #334155;
        color: #e2e8f0;
    }

    html.dark input::placeholder,
    html.dark textarea::placeholder {
        color: #64748b;
    }

    html.dark input[type="file"] {
        color: #e2e8f0;
    }

    html.dark input[type="file"]::file-selector-button {
        background-color: #1e293b;
        border: 1px solid #334155;
        color: #e2e8f0;
        border-radius: 0.75rem;
        padding: 0.625rem 0.875rem;
        margin-right: 0.875rem;
    }

    html.dark input[type="file"]::-webkit-file-upload-button {
        background-color: #1e293b;
        border: 1px solid #334155;
        color: #e2e8f0;
        border-radius: 0.75rem;
        padding: 0.625rem 0.875rem;
        margin-right: 0.875rem;
    }

    html.dark table th,
    html.dark table td {
        border-color: #334155 !important;
    }

    html.dark img {
        color-scheme: dark;
    }
</style>
