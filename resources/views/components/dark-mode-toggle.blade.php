<div
    x-data="{
        isDark: false,
        init() {
            const storedTheme = localStorage.getItem('theme')
            if (
                storedTheme === 'dark' ||
                (! storedTheme &&
                    window.matchMedia('(prefers-color-scheme: dark)').matches)
            ) {
                this.isDark = true
                document.documentElement.classList.add('dark')
                document.documentElement.setAttribute('data-theme', 'dark') // Tambahkan ini
            } else {
                this.isDark = false
                document.documentElement.classList.remove('dark')
                document.documentElement.setAttribute('data-theme', 'light') // Tambahkan ini
            }
        },
        toggle() {
            this.isDark = ! this.isDark
            localStorage.setItem('theme', this.isDark ? 'dark' : 'light')
            document.documentElement.setAttribute(
                'data-theme',
                this.isDark ? 'dark' : 'light',
            )
            document.documentElement.classList.toggle('dark', this.isDark)
        },
    }"
    x-init="init()"
>
    <button
        @click="toggle()"
        class="text-slate-400 hover:text-white cursor-pointer"
        aria-label="Toggle theme"
        hidden
    >
        <x-icon name="lucide.sun" x-show="isDark" x-cloak class="w-6 h-6" />
        <x-icon name="lucide.moon" x-show="!isDark" x-cloak class="w-6 h-6" />
    </button>
</div>
