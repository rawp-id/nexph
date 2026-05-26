<?php
namespace Core\UI\Components;

class LoginPage
{
    public static function render(string $csrfToken = ''): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en" id="html-root">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Login | Nexph</title>
            <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
            <script src="https://unpkg.com/lucide@latest"></script>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
            <style type="text/tailwindcss">
                @custom-variant dark (&:where(.dark, .dark *));
                @theme { --font-inter: "Inter", sans-serif; }
                body { font-family: "Inter", sans-serif; }
            </style>
            <script>
                if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            </script>
        </head>
        <body class="bg-slate-50 dark:bg-[#020617] min-h-screen flex items-center justify-center transition-colors">
            <div class="max-w-md w-full mx-4">
                <div class="bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 p-10 rounded-3xl shadow-2xl backdrop-blur-xl">
                    <div class="flex justify-center mb-8">
                        <div class="w-14 h-14 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-xl shadow-indigo-500/30">
                            <i data-lucide="zap" class="w-8 h-8 text-white fill-current"></i>
                        </div>
                    </div>
                    <h2 class="text-3xl font-bold text-slate-900 dark:text-white text-center mb-2">Welcome Back</h2>
                    <p class="text-slate-500 text-center mb-8 text-sm">Sign in to your Nexph dashboard</p>
                    <form id="loginForm" class="space-y-5">
                        <input type="hidden" name="_csrf_token" value="{$csrfToken}">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Username</label>
                            <input type="text" name="username" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 focus:outline-none transition-all" required>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Password</label>
                            <input type="password" name="password" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 focus:outline-none transition-all" required>
                        </div>
                        <div id="loginError" class="hidden text-red-400 text-sm text-center"></div>
                        <button type="submit" class="w-full bg-indigo-600 text-white py-3.5 rounded-xl font-bold hover:bg-indigo-500 hover:shadow-lg hover:shadow-indigo-500/30 hover:-translate-y-0.5 active:translate-y-0 transition-all mt-2">Sign In</button>
                    </form>
                    <p class="text-slate-400 dark:text-slate-600 text-xs text-center mt-6">Nexph Engine &copy; 2026</p>
                </div>
            </div>
            <script>
                lucide.createIcons();
                document.getElementById('loginForm').onsubmit = async (e) => {
                    e.preventDefault();
                    const err = document.getElementById('loginError');
                    err.classList.add('hidden');
                    const formData = new FormData(e.target);
                    const res = await fetch('/admin/login', {
                        method: 'POST',
                        body: JSON.stringify(Object.fromEntries(formData)),
                        headers: { 'Content-Type': 'application/json' }
                    });
                    const data = await res.json();
                    if (data.success) {
                        window.location.href = '/admin';
                    } else {
                        err.textContent = 'Invalid username or password';
                        err.classList.remove('hidden');
                    }
                };
            </script>
        </body>
        </html>
HTML;
    }


}
