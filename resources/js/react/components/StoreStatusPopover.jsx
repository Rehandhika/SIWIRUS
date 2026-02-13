import React from 'react'

import { Button } from '@/components/ui/button'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Separator } from '@/components/ui/separator'
import { api } from '@/react/lib/api'

export default function StoreStatusPopover() {
    const [status, setStatus] = React.useState(null)
    const [loading, setLoading] = React.useState(true)
    const [error, setError] = React.useState(false)
    const [open, setOpen] = React.useState(false)
    const lastFetchedAtRef = React.useRef(0)

    const load = React.useCallback(async (signal) => {
        try {
            const res = await api.get('/api/publik/status-toko', { signal })
            setStatus(res.data?.data ?? null)
            setError(false)
            lastFetchedAtRef.current = Date.now()
        } catch (e) {
            if (signal?.aborted) return
            setError(true)
            lastFetchedAtRef.current = Date.now()
        }
    }, [])

    React.useEffect(() => {
        const controller = new AbortController()
        let intervalId
        let idleHandle

        const scheduleInitial = () => {
            const run = async () => {
                try {
                    await load(controller.signal)
                } finally {
                    setLoading(false)
                }
            }

            if (typeof window.requestIdleCallback === 'function') {
                idleHandle = window.requestIdleCallback(run, { timeout: 1500 })
            } else {
                idleHandle = window.setTimeout(run, 600)
            }
        }

        scheduleInitial()
        intervalId = window.setInterval(() => {
            if (document.hidden) return
            load(controller.signal)
        }, 30_000)

        return () => {
            controller.abort()
            if (typeof window.cancelIdleCallback === 'function') {
                if (idleHandle) window.cancelIdleCallback(idleHandle)
            } else {
                if (idleHandle) window.clearTimeout(idleHandle)
            }
            if (intervalId) window.clearInterval(intervalId)
        }
    }, [load])

    React.useEffect(() => {
        if (!open) return
        if (document.hidden) return
        if (Date.now() - lastFetchedAtRef.current < 5_000) return
        const controller = new AbortController()
        load(controller.signal)
        return () => controller.abort()
    }, [open, load])

    const isOpen = Boolean(status?.is_open)
    const reason = status?.reason ?? ''
    const attendees = Array.isArray(status?.attendees) ? status.attendees : []
    const nextOpenTime = status?.next_open_time ?? null

    // Cinematic glow styles based on status with smooth transition
    const glowStyle = React.useMemo(() => {
        const baseTransition = 'box-shadow 0.8s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.8s cubic-bezier(0.4, 0, 0.2, 1)'
        if (loading || error) return { transition: baseTransition }
        return isOpen
            ? {
                  boxShadow: '0 0 15px rgba(16, 185, 129, 0.3), 0 0 30px rgba(16, 185, 129, 0.15), 0 0 45px rgba(16, 185, 129, 0.08)',
                  transition: baseTransition,
              }
            : {
                  boxShadow: '0 0 15px rgba(244, 63, 94, 0.3), 0 0 30px rgba(244, 63, 94, 0.15), 0 0 45px rgba(244, 63, 94, 0.08)',
                  transition: baseTransition,
              }
    }, [loading, error, isOpen])

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    style={glowStyle}
                    className={[
                        'h-9 rounded-full bg-background/70 backdrop-blur-sm',
                        'transition-all duration-700 ease-out',
                        error
                            ? 'text-muted-foreground border-border'
                            : isOpen
                              ? 'text-emerald-600 dark:text-emerald-300 border-emerald-500/40 hover:border-emerald-500/60 hover:bg-emerald-500/5'
                              : 'text-rose-600 dark:text-red-300 border-rose-500/40 hover:border-rose-500/60 hover:bg-rose-500/5',
                    ].join(' ')}
                >
                    <span className="relative flex h-2 w-2">
                        {!loading && !error && (
                            <span
                                className={[
                                    'absolute inline-flex h-full w-full rounded-full opacity-75',
                                    'animate-[ping_2s_cubic-bezier(0,0,0.2,1)_infinite]',
                                    isOpen ? 'bg-emerald-400' : 'bg-rose-400',
                                ].join(' ')}
                            />
                        )}
                        <span
                            className={[
                                'relative inline-flex h-2 w-2 rounded-full transition-colors duration-700',
                                error 
                                    ? 'bg-muted-foreground/50' 
                                    : isOpen 
                                        ? 'bg-emerald-500' 
                                        : 'bg-rose-500',
                            ].join(' ')}
                        />
                    </span>
                    <span className="text-xs font-mono tracking-widest">
                        {loading ? 'LOADING' : error ? 'OFFLINE' : isOpen ? 'BUKA' : 'TUTUP'}
                    </span>
                </Button>
            </PopoverTrigger>
            <PopoverContent
                className="w-80 bg-popover/95 backdrop-blur-2xl border-border text-popover-foreground"
                sideOffset={12}
            >
                <div className="space-y-4">
                    <div className="space-y-1 text-center">
                        <div className="text-base font-semibold text-foreground">
                            {error ? 'STATUS TIDAK TERSEDIA' : isOpen ? 'KOPERASI BUKA' : 'KOPERASI TUTUP'}
                        </div>
                        <div className="text-xs text-muted-foreground font-mono">
                            {error
                                ? 'Tidak dapat menghubungi server'
                                : isOpen
                                  ? 'Silakan datang bertransaksi'
                                  : 'Kami sedang tidak beroperasi'}
                        </div>
                    </div>

                    <div className="rounded-xl border border-border bg-muted/40 p-4 space-y-3" style={{ display: !isOpen ? 'block' : 'none' }}>
                        <div className="space-y-1">
                            <div className="text-[10px] uppercase tracking-wider text-muted-foreground font-bold">
                                Keterangan
                            </div>
                            <div className="text-sm text-foreground leading-snug">
                                {error ? 'Periksa koneksi backend (kopma.test) atau coba refresh.' : reason || '-'}
                            </div>
                        </div>

                        {!isOpen && nextOpenTime ? (
                            <>
                                <Separator className="bg-border" />
                                <div className="space-y-1">
                                    <div className="text-[10px] uppercase tracking-wider text-muted-foreground font-bold">
                                        Buka Kembali
                                    </div>
                                    <div className="text-sm text-foreground">{nextOpenTime}</div>
                                </div>
                            </>
                        ) : null}
                    </div>

                    {isOpen && attendees.length > 0 ? (
                        <div className="space-y-3">
                            <div className="text-[10px] uppercase tracking-wider text-muted-foreground font-bold text-center">
                                Petugas Jaga
                            </div>
                            <div className="flex flex-wrap justify-center gap-2">
                                {attendees.map((name) => (
                                    <div
                                        key={name}
                                        className="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-xs text-emerald-700 dark:text-emerald-300"
                                    >
                                        <div className="w-1.5 h-1.5 bg-emerald-500 rounded-full" />
                                        {name}
                                    </div>
                                ))}
                            </div>
                        </div>
                    ) : null}

                    <div className="text-[10px] text-center text-muted-foreground font-mono border-t border-border pt-3">
                        Diperbarui setiap 30 detik
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    )
}
