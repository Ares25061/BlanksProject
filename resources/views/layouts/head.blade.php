@php
    $pageTitle = trim((string) ($title ?? ''));
    $appName = config('app.name', 'Провериум');
    $documentTitle = $pageTitle !== '' ? $pageTitle.' | '.$appName : $appName;
@endphp
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="only light">
<title>{{ $documentTitle }}</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#edf3ef">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
<link rel="icon" type="image/x-icon" href="/favicon.ico">
<link rel="shortcut icon" href="/favicon.ico">
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
            root.style.colorScheme = themeValue === 'dark' ? 'dark' : 'only light';
            root.style.backgroundColor = themeValue === 'dark' ? '#0b1115' : '#edf3ef';
            root.style.color = themeValue === 'dark' ? '#e5eef5' : '#0f172a';

            if (themeColorMeta) {
                themeColorMeta.setAttribute('content', themeValue === 'dark' ? '#0b1115' : '#edf3ef');
            }

            if (colorSchemeMeta) {
                colorSchemeMeta.setAttribute('content', themeValue === 'dark' ? 'dark' : 'only light');
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
                    mint: {
                        400: '#36d6a2',
                        500: '#1cc794',
                        600: '#10ab7e',
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
            radial-gradient(circle at 16% 10%, rgba(13, 148, 136, 0.10), transparent 20rem),
            radial-gradient(circle at 84% 8%, rgba(59, 130, 246, 0.09), transparent 17rem),
            radial-gradient(circle at 52% 100%, rgba(14, 165, 233, 0.06), transparent 24rem),
            linear-gradient(180deg, #edf3ef 0%, #e8f1ed 44%, #eef4f2 100%);
        --proverium-page-dark:
            radial-gradient(circle at 16% 12%, rgba(45, 212, 191, 0.16), transparent 22rem),
            radial-gradient(circle at 84% 8%, rgba(96, 165, 250, 0.12), transparent 18rem),
            radial-gradient(circle at 52% 100%, rgba(20, 184, 166, 0.10), transparent 26rem),
            linear-gradient(180deg, #0d1317 0%, #11181d 40%, #0b1115 100%);
    }

    html,
    body {
        min-height: 100%;
    }

    html[data-theme="light"],
    html[data-theme="light"] body,
    html[data-theme="light"] button,
    html[data-theme="light"] input,
    html[data-theme="light"] select,
    html[data-theme="light"] textarea {
        color-scheme: only light !important;
        forced-color-adjust: none;
    }

    html[data-theme="light"] {
        background-color: #edf3ef;
        color: #0f172a;
    }

    html[data-theme="light"] body {
        background: var(--proverium-page-light);
        color: #0f172a;
    }

    html.dark {
        background-color: #0b1115;
        color: #e5eef5;
        color-scheme: dark;
        forced-color-adjust: none;
    }

    html.dark body {
        background: var(--proverium-page-dark);
        color: #e5eef5;
    }

    body {
        transition: background-color 0.25s ease, color 0.25s ease;
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
        background-color: rgba(255, 255, 255, 0.88);
        border-color: #cbd5e1;
        color: #0f172a;
    }

    html[data-theme="light"] input::placeholder,
    html[data-theme="light"] textarea::placeholder {
        color: #94a3b8;
    }

    .proverium-panel {
        background: rgba(255, 255, 255, 0.92);
        border: 1px solid rgba(203, 213, 225, 0.72);
        box-shadow: 0 26px 64px -42px rgba(15, 23, 42, 0.24);
        backdrop-filter: blur(22px);
    }

    html.dark .proverium-panel {
        background: rgba(15, 23, 42, 0.78);
        border-color: rgba(255, 255, 255, 0.08);
        box-shadow: 0 36px 90px -38px rgba(0, 0, 0, 0.72);
    }

    html.dark input,
    html.dark select,
    html.dark textarea {
        background-color: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.10);
        color: #e2e8f0;
    }

    html.dark option,
    html.dark optgroup {
        background-color: #0f172a;
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
</style>
