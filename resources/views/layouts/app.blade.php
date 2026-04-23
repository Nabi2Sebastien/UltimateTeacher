<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ultimate Teacher - @yield('title', 'Dashboard')</title>
    <!-- Modern Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 270px;
            --bg-color: #f4f6f9;
            --sidebar-bg: #1e1e2d;
            --sidebar-hover: rgba(255, 255, 255, 0.05);
            --text-muted: #9899ac;
            --text-light: #ffffff;
            --accent: #009ef7;
            --accent-glow: rgba(0, 158, 247, 0.4);
            --border-color: rgba(255, 255, 255, 0.08);
            --card-bg: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            min-height: 100vh;
            color: #3f4254;
        }

        /* Sidebar Styling */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            color: var(--text-light);
            box-shadow: 2px 0 15px rgba(0,0,0,0.05);
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
        }

        .sidebar-header {
            padding: 35px 25px;
            font-size: 1.6rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            letter-spacing: 0.5px;
            color: var(--text-light);
        }

        .sidebar-header span {
            color: var(--accent);
            margin-right: 5px;
        }

        .sidebar-menu {
            margin-top: 30px;
            list-style: none;
            padding: 0 20px;
            flex: 1;
        }

        .sidebar-menu-item {
            margin-bottom: 8px;
        }

        .sidebar-menu-link {
            display: flex;
            align-items: center;
            padding: 14px 18px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 1.05rem;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .sidebar-menu-link svg {
            margin-right: 15px;
            width: 22px;
            height: 22px;
            fill: currentColor;
            transition: all 0.3s ease;
        }

        /* Hover & Active Micro-animations */
        .sidebar-menu-link:hover {
            color: var(--accent);
            background-color: var(--sidebar-hover);
            transform: translateX(6px);
        }

        .sidebar-menu-link.active {
            background-color: var(--accent);
            color: var(--text-light);
            box-shadow: 0 4px 12px var(--accent-glow);
        }

        .sidebar-menu-link.active svg {
            fill: var(--text-light);
        }

        .sidebar-menu-link::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: translateX(-100%);
        }

        .sidebar-menu-link:hover::after {
            transform: translateX(100%);
            transition: transform 0.6s ease;
        }

        /* Main Content Styling */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 40px;
            background-color: var(--bg-color);
            min-height: 100vh;
            max-width: calc(100vw - var(--sidebar-width));
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #181c32;
        }

    </style>
    @stack('styles')
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <span>U</span>ltimateTeacher
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-menu-item">
                <a href="{{ route('dashboard') }}" class="sidebar-menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <!-- Dashboard Icon (Material) -->
                    <svg viewBox="0 0 24 24"><path d="M4 13h6c.55 0 1-.45 1-1V4c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v8c0 .55.45 1 1 1zm0 8h6c.55 0 1-.45 1-1v-4c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v4c0 .55.45 1 1 1zm10 0h6c.55 0 1-.45 1-1v-8c0-.55-.45-1-1-1h-6c-.55 0-1 .45-1 1v8c0 .55.45 1 1 1zM13 4v4c0 .55.45 1 1 1h6c.55 0 1-.45 1-1V4c0-.55-.45-1-1-1h-6c-.55 0-1 .45-1 1z"/></svg>
                    Dashboard
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <!-- Progression Icon (Material) -->
                    <svg viewBox="0 0 24 24"><path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/></svg>
                    Progression
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <!-- Déroulement Icon (Material List) -->
                    <svg viewBox="0 0 24 24"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
                    Déroulement
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <!-- Cours Icon (Material Book) -->
                    <svg viewBox="0 0 24 24"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 9H9V9h10v2zm-4 4H9v-2h6v2zm4-8H9V5h10v2z"/></svg>
                    Cours
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <!-- Évaluation Icon (Material Assignment Check) -->
                    <svg viewBox="0 0 24 24"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm-2 14l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
                    Évaluation
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="{{ route('settings.index') }}" class="sidebar-menu-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <!-- Paramètres Icon (Material Settings Gear) -->
                    <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94L14.4 2.81c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41L9.25 5.35C8.66 5.59 8.12 5.92 7.63 6.29L5.24 5.33c-.22-.08-.47 0-.59.22L2.73 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.08.62-.08.94s.03.64.08.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .43-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.49-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
                    Paramètres
                </a>
            </li>
        </ul>
    </aside>
    <main class="main-content">
        @yield('content')
    </main>
</body>
</html>
