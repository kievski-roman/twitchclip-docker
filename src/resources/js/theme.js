import Alpine from 'alpinejs'
window.Alpine = Alpine

Alpine.store('theme', {
    init() {
        const saved = localStorage.getItem('theme')
        this.dark = saved
            ? saved === 'dark'
            : window.matchMedia('(prefers-color-scheme: dark)').matches
    },
    dark: false,
    toggle() {
        this.dark = !this.dark
        localStorage.setItem('theme', this.dark ? 'dark' : 'light')
    },
})

Alpine.start()
