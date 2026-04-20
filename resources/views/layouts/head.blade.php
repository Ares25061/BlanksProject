@php
    $pageTitle = trim((string) ($title ?? ''));
    $appName = config('app.name', 'Провериум');
    $documentTitle = $pageTitle !== '' ? $pageTitle.' | '.$appName : $appName;
    $faviconVersion = (string) (@filemtime(public_path('favicon.ico')) ?: @filemtime(public_path('favicon.png')) ?: time());
@endphp
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="light dark">
<title>{{ $documentTitle }}</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#f4f6fb">
<link rel="icon" type="image/x-icon" href="/favicon.ico?v={{ $faviconVersion }}">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon.png?v={{ $faviconVersion }}">
<link rel="shortcut icon" href="/favicon.ico?v={{ $faviconVersion }}">
<link rel="apple-touch-icon" href="/favicon.png?v={{ $faviconVersion }}">
<script>
    (() => {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        const root = document.documentElement;
        const themeColorMeta = document.querySelector('meta[name="theme-color"]');
        const colorSchemeMeta = document.querySelector('meta[name="color-scheme"]');

        function applyTheme() {
            const themeValue = mediaQuery.matches ? 'dark' : 'light';

            root.classList.toggle('dark', themeValue === 'dark');
            root.dataset.theme = themeValue;
            root.dataset.themeMode = 'system';
            root.style.colorScheme = themeValue;
            root.style.backgroundColor = themeValue === 'dark' ? '#09111f' : '#f4f6fb';
            root.style.color = themeValue === 'dark' ? '#e6ecff' : '#172033';

            if (themeColorMeta) {
                themeColorMeta.setAttribute('content', themeValue === 'dark' ? '#09111f' : '#f4f6fb');
            }

            if (colorSchemeMeta) {
                colorSchemeMeta.setAttribute('content', themeValue);
            }

            window.dispatchEvent(new CustomEvent('proverium:theme-change', {
                detail: {
                    theme: themeValue,
                    mode: 'system'
                }
            }));
        }

        applyTheme();

        if (typeof mediaQuery.addEventListener === 'function') {
            mediaQuery.addEventListener('change', applyTheme);
        } else if (typeof mediaQuery.addListener === 'function') {
            mediaQuery.addListener(applyTheme);
        }
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
                    mist: {
                        100: '#eef2ff',
                        200: '#dbe4ff',
                        300: '#c5d3ff',
                        700: '#394a7a',
                    }
                },
                boxShadow: {
                    halo: '0 20px 48px -28px rgba(74, 78, 214, 0.28)',
                }
            }
        }
    };
</script>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    :root {
        --proverium-page-light:
            linear-gradient(134deg, rgba(255, 255, 255, 0.86) 0 33%, rgba(255, 255, 255, 0) 33% 100%),
            linear-gradient(141deg, rgba(99, 102, 241, 0) 0 58%, rgba(99, 102, 241, 0.11) 58% 72%, rgba(99, 102, 241, 0) 72% 100%),
            linear-gradient(318deg, rgba(129, 140, 248, 0.10) 0 18%, rgba(129, 140, 248, 0) 18% 100%),
            linear-gradient(152deg, rgba(96, 165, 250, 0) 0 74%, rgba(96, 165, 250, 0.09) 74% 86%, rgba(96, 165, 250, 0) 86% 100%),
            linear-gradient(180deg, #f4f6fb 0%, #eef2fb 54%, #f8f9fd 100%);
        --proverium-page-dark:
            linear-gradient(135deg, rgba(34, 43, 74, 0.78) 0 27%, rgba(34, 43, 74, 0) 27% 100%),
            linear-gradient(141deg, rgba(99, 102, 241, 0) 0 57%, rgba(99, 102, 241, 0.18) 57% 70%, rgba(99, 102, 241, 0) 70% 100%),
            linear-gradient(145deg, rgba(59, 130, 246, 0) 0 74%, rgba(59, 130, 246, 0.14) 74% 86%, rgba(59, 130, 246, 0) 86% 100%),
            linear-gradient(318deg, rgba(90, 88, 255, 0.18) 0 15%, rgba(90, 88, 255, 0) 15% 100%),
            linear-gradient(180deg, #0a0f1d 0%, #0d1324 52%, #090e1b 100%);
        --proverium-panel-light: rgba(255, 255, 255, 0.9);
        --proverium-panel-dark: rgba(12, 19, 36, 0.9);
        --proverium-panel-border-light: rgba(191, 202, 224, 0.76);
        --proverium-panel-border-dark: rgba(113, 132, 170, 0.18);
        --proverium-surface-light: rgba(249, 251, 255, 0.96);
        --proverium-surface-dark: rgba(11, 17, 31, 0.96);
        --proverium-text-light: #172033;
        --proverium-text-dark: #e6ecff;
        --proverium-muted-light: #62708d;
        --proverium-muted-dark: #9babd1;
    }

    html,
    body {
        min-height: 100%;
    }

    html[data-theme="light"] {
        background-color: #f4f6fb;
        color: var(--proverium-text-light);
        color-scheme: light;
    }

    html[data-theme="light"] body {
        background: var(--proverium-page-light);
        color: var(--proverium-text-light);
    }

    html.dark {
        background-color: #09111f;
        color: var(--proverium-text-dark);
        color-scheme: dark;
    }

    html.dark body {
        background: var(--proverium-page-dark);
        color: var(--proverium-text-dark);
    }

    body {
        transition: background-color 0.25s ease, color 0.25s ease;
        background-attachment: fixed;
    }

    input,
    select,
    textarea,
    button {
        transition:
            background-color 0.2s ease,
            border-color 0.2s ease,
            color 0.2s ease,
            box-shadow 0.2s ease;
    }

    html[data-theme="light"] input,
    html[data-theme="light"] select,
    html[data-theme="light"] textarea {
        background-color: rgba(255, 255, 255, 0.96);
        border-color: #c7d2e5;
        color: var(--proverium-text-light);
    }

    html[data-theme="light"] input::placeholder,
    html[data-theme="light"] textarea::placeholder {
        color: #8a97b1;
    }

    .proverium-panel {
        background: var(--proverium-panel-light);
        border: 1px solid var(--proverium-panel-border-light);
        box-shadow: 0 28px 68px -44px rgba(30, 41, 59, 0.28);
    }

    html.dark .proverium-panel {
        background: var(--proverium-panel-dark);
        border-color: var(--proverium-panel-border-dark);
        box-shadow: 0 36px 84px -42px rgba(0, 0, 0, 0.64);
    }

    html.dark input,
    html.dark select,
    html.dark textarea {
        background-color: rgba(10, 17, 31, 0.92);
        border-color: rgba(122, 143, 186, 0.18);
        color: #e2e8f0;
    }

    html.dark option,
    html.dark optgroup {
        background-color: #0b1326;
        color: #e2e8f0;
    }

    html.dark select option:checked,
    html.dark select option:hover {
        background-color: #1d4ed8;
        color: #eff6ff;
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

    @supports (-webkit-touch-callout: none) {
        body {
            background-attachment: scroll;
        }
    }
</style>
