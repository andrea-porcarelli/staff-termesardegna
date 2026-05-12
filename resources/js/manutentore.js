import Alpine from 'alpinejs'

const lockBody = (lock) => {
    document.body.style.overflow = lock ? 'hidden' : ''
}

Alpine.store('toasts', {
    items: [],
    push(message, type = 'info', ttl = 4000) {
        if (!message) return
        const id = Date.now() + Math.random()
        this.items.push({ id, message, type })
        if (ttl > 0) {
            setTimeout(() => this.dismiss(id), ttl)
        }
    },
    dismiss(id) {
        this.items = this.items.filter((t) => t.id !== id)
    },
})

Alpine.store('drawer', {
    isOpen: false,
    show() { this.isOpen = true; lockBody(true) },
    hide() { this.isOpen = false; lockBody(false) },
    toggle() { this.isOpen ? this.hide() : this.show() },
})

Alpine.store('quickOpen', {
    isOpen: false,
    show() { this.isOpen = true; lockBody(true) },
    hide() { this.isOpen = false; lockBody(false) },
})

// Audio context lazy-creato dopo la prima user gesture (richiesto da iOS Safari).
let audioCtx = null
const unlockAudio = () => {
    if (audioCtx || typeof window === 'undefined') return
    try {
        const Ctor = window.AudioContext || window.webkitAudioContext
        if (Ctor) audioCtx = new Ctor()
        if (audioCtx?.state === 'suspended') audioCtx.resume()
    } catch (_) {
        audioCtx = null
    }
}
['pointerdown', 'keydown', 'touchstart'].forEach((ev) =>
    document.addEventListener(ev, unlockAudio, { once: true, passive: true })
)

const playChime = () => {
    if (!audioCtx) return
    try {
        if (audioCtx.state === 'suspended') {
            audioCtx.resume()
        }
        const now = audioCtx.currentTime
        const tone = (freq, start, dur = 0.35, gain = 0.45) => {
            const osc = audioCtx.createOscillator()
            const g = audioCtx.createGain()
            osc.type = 'sine'
            osc.frequency.value = freq
            g.gain.setValueAtTime(gain, now + start)
            g.gain.exponentialRampToValueAtTime(0.0001, now + start + dur)
            osc.connect(g).connect(audioCtx.destination)
            osc.start(now + start)
            osc.stop(now + start + dur + 0.02)
        }
        tone(880, 0)          // A5
        tone(1175, 0.18)      // D6
        tone(1568, 0.36)      // G6
        if (typeof navigator !== 'undefined' && navigator.vibrate) {
            navigator.vibrate([120, 40, 120])
        }
    } catch (_) {}
}

Alpine.store('notifications', {
    isOpen: false,
    items: [],
    loading: false,
    unreadCount: 0,
    _pollTimer: null,
    _lastKnownUnread: 0,
    _silentBootstrap: true,

    _syncBadge() {
        if (typeof navigator === 'undefined' || !('setAppBadge' in navigator)) return
        try {
            if (this.unreadCount > 0) {
                navigator.setAppBadge(this.unreadCount).catch(() => {})
            } else {
                navigator.clearAppBadge().catch(() => {})
            }
        } catch (_) {}
    },

    setInitialCount(n) {
        this.unreadCount = n | 0
        this._lastKnownUnread = this.unreadCount
        this._syncBadge()
    },

    async show() {
        this.isOpen = true
        lockBody(true)
        await this.fetch()
    },

    hide() { this.isOpen = false; lockBody(false) },

    async fetch({ silent = false } = {}) {
        if (!silent) this.loading = true
        try {
            const res = await fetch('/m/notifications/json', {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            })
            if (res.ok) {
                const payload = await res.json()
                this.items = payload.items || []
                const newCount = payload.unread_count || 0

                if (!this._silentBootstrap && newCount > this._lastKnownUnread) {
                    playChime()
                }

                this.unreadCount = newCount
                this._lastKnownUnread = newCount
                this._silentBootstrap = false
                this._syncBadge()
            }
        } catch (_) {
            // silent
        }
        if (!silent) this.loading = false
    },

    startPolling(intervalMs = 30000) {
        this.stopPolling()
        this._pollTimer = setInterval(() => {
            if (document.hidden) return
            this.fetch({ silent: true })
        }, intervalMs)
    },

    stopPolling() {
        if (this._pollTimer) {
            clearInterval(this._pollTimer)
            this._pollTimer = null
        }
    },

    async markRead(id) {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || ''
        try {
            const res = await fetch(`/m/notifications/${id}/read`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                credentials: 'same-origin',
            })
            if (res.ok) {
                const payload = await res.json()
                this.unreadCount = payload.unread_count ?? this.unreadCount
                const item = this.items.find((i) => i.id === id)
                if (item && !item.read_at) item.read_at = new Date().toISOString()
                this._syncBadge()
            }
        } catch (_) {}
    },

    async markAllRead() {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || ''
        try {
            await fetch('/m/notifications/read-all', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                credentials: 'same-origin',
            })
            const now = new Date().toISOString()
            this.items.forEach((i) => { if (!i.read_at) i.read_at = now })
            this.unreadCount = 0
            this._syncBadge()
        } catch (_) {}
    },
})

Alpine.data('draft', (key) => ({
    key,
    data: {},
    hasSavedDraft: false,
    init() {
        const saved = localStorage.getItem(this.key)
        if (saved) {
            try {
                this.data = JSON.parse(saved)
                this.hasSavedDraft = true
            } catch (_) {
                localStorage.removeItem(this.key)
            }
        }
        this.$watch('data', (v) => {
            localStorage.setItem(this.key, JSON.stringify(v))
        })
    },
    clear() {
        localStorage.removeItem(this.key)
        this.data = {}
        this.hasSavedDraft = false
    },
}))

window.Alpine = Alpine
Alpine.start()

// Avvia il polling delle notifiche a 30s (pausa se la tab è in background).
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        Alpine.store('notifications').fetch({ silent: true })
    }
})
Alpine.store('notifications').startPolling(30000)
