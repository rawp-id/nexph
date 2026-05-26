<?php
namespace Core\UI\Components;

class Layout
{
    public static function render(string $title, string $content, array $tables = []): string
    {
        $sidebar = '';
        foreach ($tables as $t) {
            $name = $t['table'];
            if ($name === 'job_workers') continue;
            $isSynced = \Core\Database\Migration::isSynced($name, $t);
            $dot = $isSynced ? '' : '<span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse ml-auto" title="Out of sync"></span>';
            $eName = e($name);
            $sidebar .= "<li><a hx-get='/admin/{$name}' hx-target='#content' hx-push-url='true' title='{$eName}' class='nav-link flex items-center gap-3 px-4 py-2 rounded-lg transition-all duration-200 cursor-pointer text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white hover:bg-indigo-50 dark:hover:bg-slate-800/50'>
                <i data-lucide='table-2' class='w-4 h-4'></i>
                <span class='text-sm font-medium'>{$eName}</span>
                {$dot}
            </a></li>";
        }

        $eTitle = e($title);
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en" id="html-root">
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$eTitle} | Nexph</title>
            <script src="https://unpkg.com/htmx.org@1.9.10"></script>
            <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
            <script src="https://unpkg.com/lucide@latest"></script>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
            <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
            <style type="text/tailwindcss">
                @custom-variant dark (&:where(.dark, .dark *));
                @theme {
                    --font-inter: "Inter", sans-serif;
                }
                @layer base {
                    body { @apply font-inter; }
                    ::-webkit-scrollbar { @apply w-1 h-1; }
                    ::-webkit-scrollbar-track { @apply bg-transparent; }
                    ::-webkit-scrollbar-thumb { @apply bg-slate-300 dark:bg-slate-700 rounded-full hover:bg-indigo-500 transition-colors; }
                }
                #sidebar { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
                #sidebar.collapsed { @apply w-20; }
                #sidebar.collapsed .nav-link { @apply justify-center px-0 gap-0; }
                #sidebar.collapsed .nav-link span, 
                #sidebar.collapsed nav p, 
                #sidebar.collapsed .cloud-usage,
                #sidebar.collapsed #sidebar-title { display: none !important; }
                #sidebar.collapsed .nav-link i { @apply w-5 h-5; }
                #sidebar.collapsed .sidebar-header { @apply px-0 justify-center; }
                #sidebar.collapsed .sidebar-header-logo { @apply gap-0 justify-center; }
                #sidebar.collapsed #sidebar-collapse { @apply hidden; }
                #sidebar.collapsed:hover #sidebar-collapse { @apply flex absolute right-[-12px] top-8 z-30 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-full shadow-md p-1; }
            </style>
            <script>
                function toggleTheme() {
                    const html = document.getElementById('html-root');
                    html.classList.toggle('dark');
                    localStorage.theme = html.classList.contains('dark') ? 'dark' : 'light';
                }
                if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            </script>
        </head>
        <body class="bg-slate-50 dark:bg-[#020617] text-slate-900 dark:text-slate-200 flex h-screen overflow-hidden transition-colors duration-300">
            <!-- Sidebar -->
            <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-[#020617] flex flex-col flex-shrink-0 transition-all duration-300 transform -translate-x-full lg:translate-x-0 lg:static lg:inset-0">
                <div class="p-6 flex items-center justify-between sidebar-header relative">
                    <div class="flex items-center gap-3 sidebar-header-logo">
                        <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center shadow-lg shadow-indigo-500/20 flex-shrink-0">
                            <i data-lucide="zap" class="w-5 h-5 text-white fill-current"></i>
                        </div>
                        <h1 id="sidebar-title" class="text-xl font-bold tracking-tight dark:text-white transition-all duration-300">Nexph</h1>
                    </div>
                    <button id="sidebar-collapse" class="p-1.5 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-all">
                        <i data-lucide="chevrons-left" class="w-4 h-4"></i>
                    </button>
                </div>
                
                <nav class="flex-1 px-4 py-4 overflow-y-auto space-y-8">
                    <div>
                        <p class="px-4 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-4">Core</p>
                        <ul class="space-y-1">
                            <li><a hx-get="/admin" hx-target="#content" hx-push-url="true" title="Dashboard" class="nav-link flex items-center gap-3 px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white hover:bg-indigo-50 dark:hover:bg-slate-800/50 transition-all cursor-pointer">
                                <i data-lucide="layout-dashboard" class="w-4 h-4"></i><span class="text-sm font-medium">Dashboard</span>
                            </a></li>
                            <li><a hx-get="/admin/jobs" hx-target="#content" hx-push-url="true" title="Job Queue" class="nav-link flex items-center gap-3 px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white hover:bg-indigo-50 dark:hover:bg-slate-800/50 transition-all cursor-pointer">
                                <i data-lucide="activity" class="w-4 h-4"></i><span class="text-sm font-medium">Job Queue</span>
                            </a></li>
                            <li><a hx-get="/admin/schema/new" hx-target="#content" hx-push-url="true" title="New Table" class="nav-link flex items-center gap-3 px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white hover:bg-indigo-50 dark:hover:bg-slate-800/50 transition-all cursor-pointer">
                                <i data-lucide="plus-square" class="w-4 h-4"></i><span class="text-sm font-medium">New Table</span>
                            </a></li>
                            <li><a hx-get="/admin/relations" hx-target="#content" hx-push-url="true" title="Relations" class="nav-link flex items-center gap-3 px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white hover:bg-indigo-50 dark:hover:bg-slate-800/50 transition-all cursor-pointer">
                                <i data-lucide="git-branch" class="w-4 h-4"></i><span class="text-sm font-medium">Relations</span>
                            </a></li>
                            <li><a hx-get="/admin/api-explorer" hx-target="#content" hx-push-url="true" title="API Explorer" class="nav-link flex items-center gap-3 px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white hover:bg-indigo-50 dark:hover:bg-slate-800/50 transition-all cursor-pointer">
                                <i data-lucide="code" class="w-4 h-4"></i><span class="text-sm font-medium">API Explorer</span>
                            </a></li>
                            <li><a hx-get="/admin/api-settings" hx-target="#content" hx-push-url="true" title="API Auth" class="nav-link flex items-center gap-3 px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white hover:bg-indigo-50 dark:hover:bg-slate-800/50 transition-all cursor-pointer">
                                <i data-lucide="shield-check" class="w-4 h-4"></i><span class="text-sm font-medium">API Auth</span>
                            </a></li>
                            <li><a hx-get="/admin/session-config" hx-target="#content" hx-push-url="true" title="Session Config" class="nav-link flex items-center gap-3 px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white hover:bg-indigo-50 dark:hover:bg-slate-800/50 transition-all cursor-pointer">
                                <i data-lucide="settings" class="w-4 h-4"></i><span class="text-sm font-medium">Session Config</span>
                            </a></li>
                        </ul>
                    </div>
                    
                    <div>
                        <p class="px-4 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-4">Database</p>
                        <ul class="space-y-1">
                            {$sidebar}
                        </ul>
                    </div>
                </nav>

                <div class="p-6 border-t border-slate-200 dark:border-slate-800 cloud-usage">
                    <div class="bg-indigo-600/10 p-4 rounded-xl">
                        <p class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase mb-2">Cloud Usage</p>
                        <div class="w-full bg-indigo-200 dark:bg-slate-800 h-1 rounded-full overflow-hidden">
                            <div class="bg-indigo-600 h-full w-1/3"></div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="flex-1 flex flex-col min-w-0 bg-slate-50 dark:bg-[#020617]">
                <header class="h-16 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-8 bg-white/80 dark:bg-[#020617]/50 backdrop-blur-md sticky top-0 z-40">
                    <button id="sidebar-toggle" class="lg:hidden p-2 text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors mr-4">
                        <i data-lucide="menu" class="w-5 h-5"></i>
                    </button>
                    <div class="flex items-center gap-2 text-sm flex-1">
                        <span class="text-slate-500">Admin</span>
                        <i data-lucide="chevron-right" class="w-3 h-3 text-slate-400"></i>
                        <span class="text-slate-900 dark:text-slate-200 font-medium capitalize" id="page-title">{$eTitle}</span>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <button onclick="toggleTheme()" class="p-2 text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                            <i data-lucide="sun" class="w-5 h-5 dark:hidden"></i>
                            <i data-lucide="moon" class="w-5 h-5 hidden dark:block"></i>
                        </button>
                        
                        <div class="h-8 w-px bg-slate-200 dark:bg-slate-800"></div>
                        
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <p class="text-xs font-bold text-slate-900 dark:text-white">Admin Nexph</p>
                                <p class="text-[10px] text-slate-500 font-medium">Developer</p>
                            </div>
                            <button class="relative group">
                                <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                                    AD
                                </div>
                                <!-- Dropdown (Hidden by default) -->
                                <div class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl opacity-0 group-focus-within:opacity-100 transition-opacity pointer-events-none group-focus-within:pointer-events-auto">
                                    <a href="/logout" class="flex items-center gap-3 px-4 py-3 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-400/10 rounded-xl transition-all">
                                        <i data-lucide="log-out" class="w-4 h-4"></i> Logout
                                    </a>
                                </div>
                            </button>
                        </div>
                    </div>
                </header>
                
                <main class="flex-1 overflow-y-auto p-8" id="content">
                    {$content}
                </main>
            </div>
            <script>
                lucide.createIcons();
                function updateActiveNav() {
                    document.querySelectorAll('.nav-link').forEach(link => {
                        const href = link.getAttribute('hx-get');
                        const isActive = window.location.pathname === href;
                        if(isActive) {
                            link.classList.add('bg-indigo-50', 'dark:bg-slate-800', 'text-indigo-600', 'dark:text-white', 'shadow-sm');
                            link.classList.remove('text-slate-600', 'dark:text-slate-400', 'hover:text-indigo-600', 'dark:hover:text-white', 'hover:bg-indigo-50', 'dark:hover:bg-slate-800/50');
                        } else {
                            link.classList.remove('bg-indigo-50', 'dark:bg-slate-800', 'text-indigo-600', 'dark:text-white', 'shadow-sm');
                            link.classList.add('text-slate-600', 'dark:text-slate-400', 'hover:text-indigo-600', 'dark:hover:text-white', 'hover:bg-indigo-50', 'dark:hover:bg-slate-800/50');
                        }
                    });
                }
                document.body.addEventListener('htmx:pushedIntoHistory', updateActiveNav);
                document.body.addEventListener('htmx:afterSwap', function() {
                    lucide.createIcons();
                });
                updateActiveNav();
                
                const sidebar = document.getElementById('sidebar');
                const sidebarToggle = document.getElementById('sidebar-toggle');
                const sidebarCollapse = document.getElementById('sidebar-collapse');
                const sidebarTitle = document.getElementById('sidebar-title');
                let sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                
                function updateSidebar() {
                    if (sidebarCollapsed) {
                        sidebar.classList.add('collapsed');
                        sidebarCollapse.innerHTML = '<i data-lucide="chevrons-right" class="w-4 h-4"></i>';
                    } else {
                        sidebar.classList.remove('collapsed');
                        sidebarCollapse.innerHTML = '<i data-lucide="chevrons-left" class="w-4 h-4"></i>';
                    }
                    lucide.createIcons();
                }
                
                sidebarCollapse.addEventListener('click', function() {
                    sidebarCollapsed = !sidebarCollapsed;
                    localStorage.setItem('sidebarCollapsed', sidebarCollapsed);
                    updateSidebar();
                });
                
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('-translate-x-full');
                });
                
                updateSidebar();
            </script>
        </body>
        </html>
HTML;
    }

}
