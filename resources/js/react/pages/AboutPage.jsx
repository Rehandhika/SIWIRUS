import React from 'react'
import {
    Building2,
    Clock,
    Info,
    PencilLine,
} from 'lucide-react'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import { api } from '@/react/lib/api'
import OrganizationSection from '@/react/components/OrganizationSection'
import PublicLayout from '@/react/components/PublicLayout'

const DAYS = [
    { key: 'monday', name: 'Senin' },
    { key: 'tuesday', name: 'Selasa' },
    { key: 'wednesday', name: 'Rabu' },
    { key: 'thursday', name: 'Kamis' },
    { key: 'friday', name: 'Jumat' },
    { key: 'saturday', name: 'Sabtu' },
    { key: 'sunday', name: 'Minggu' },
]

function normalizeContactValue(value) {
    if (!value) return null
    if (value === '-') return null
    return String(value).trim() || null
}

function OperatingHoursGrid({ operatingHours }) {
    const schedule = operatingHours && typeof operatingHours === 'object' ? operatingHours : {}

    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {DAYS.map((day) => {
                const entry = schedule[day.key] ?? null
                const isOpen = Boolean(entry?.is_open)
                const open = entry?.open ?? null
                const close = entry?.close ?? null

                return (
                    <div
                        key={day.key}
                        className={[
                            'relative group p-5 rounded-2xl border transition-all duration-300',
                            isOpen
                                ? 'bg-emerald-500/5 border-emerald-500/20 hover:bg-emerald-500/10'
                                : 'bg-muted/40 border-border/60 hover:bg-muted/60',
                        ].join(' ')}
                    >
                        <div
                            className={[
                                'absolute top-4 right-4 w-2 h-2 rounded-full',
                                isOpen
                                    ? 'bg-emerald-500 shadow-[0_0_10px_#10b981]'
                                    : 'bg-muted-foreground/40',
                            ].join(' ')}
                        />

                        <p
                            className={[
                                'font-bold text-lg mb-2',
                                isOpen ? 'text-foreground' : 'text-muted-foreground',
                            ].join(' ')}
                        >
                            {day.name}
                        </p>

                        {isOpen ? (
                            <p className="text-emerald-700 dark:text-emerald-300 font-mono text-sm tracking-wide">
                                {open} - {close}
                            </p>
                        ) : (
                            <p className="text-muted-foreground font-mono text-sm italic">Tutup</p>
                        )}
                    </div>
                )
            })}
        </div>
    )
}

export default function AboutPage({ initialData }) {
    const seeded = initialData?.about ?? null
    const [data, setData] = React.useState(() => seeded)
    const [loading, setLoading] = React.useState(() => !seeded)

    React.useEffect(() => {
        if (seeded) return
        ;(async () => {
            try {
                const res = await api.get('/api/publik/tentang')
                setData(res.data?.data ?? null)
            } finally {
                setLoading(false)
            }
        })()
    }, [seeded])

    const aboutText = normalizeContactValue(data?.about_text)

    return (
        <PublicLayout>
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <div className="text-center mb-16">
                    <h1 className="text-4xl md:text-5xl font-bold text-foreground mb-6 tracking-tight">
                        Tentang Kami
                    </h1>
                    <p className="text-base md:text-lg text-muted-foreground max-w-2xl mx-auto">
                        Koperasi Mahasiswa - Melayani dengan sepenuh hati dalam semangat inovasi dan transparansi.
                    </p>
                </div>

                <Card className="bg-card/60 backdrop-blur-xl border-border rounded-3xl shadow-2xl mb-12">
                    <CardHeader className="p-8 md:p-10 pb-6">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 rounded-2xl bg-blue-500/10 flex items-center justify-center border border-blue-500/20 text-blue-400">
                                <Building2 className="h-6 w-6" />
                            </div>
                            <CardTitle className="text-2xl font-bold text-foreground">
                                Tentang Koperasi
                            </CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent className="px-8 md:px-10 pb-10">
                        {loading ? (
                            <div className="space-y-3">
                                <div className="h-4 bg-muted rounded" />
                                <div className="h-4 bg-muted rounded w-5/6" />
                                <div className="h-4 bg-muted rounded w-4/6" />
                            </div>
                        ) : aboutText ? (
                            <p className="text-foreground/90 leading-relaxed whitespace-pre-line">
                                {aboutText}
                            </p>
                        ) : (
                            <div className="flex flex-col items-center justify-center py-12 text-center text-muted-foreground">
                                <PencilLine className="h-10 w-10 text-muted-foreground/60 mb-4" />
                                <p className="italic">
                                    Informasi profil koperasi akan segera dilengkapi.
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card className="bg-card/60 backdrop-blur-xl border-border rounded-3xl shadow-2xl">
                    <CardHeader className="p-8 md:p-10 pb-6">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center border border-emerald-500/20 text-emerald-400">
                                <Clock className="h-6 w-6" />
                            </div>
                            <CardTitle className="text-2xl font-bold text-foreground">
                                Jam Operasional
                            </CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent className="px-8 md:px-10 pb-10 space-y-8">
                        {loading ? (
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                {Array.from({ length: 8 }).map((_, idx) => (
                                    <div
                                        key={idx}
                                        className="h-24 rounded-2xl border border-border bg-muted/40"
                                    />
                                ))}
                            </div>
                        ) : (
                            <OperatingHoursGrid operatingHours={data?.operating_hours} />
                        )}

                        <div className="p-4 bg-blue-500/10 border border-blue-500/20 rounded-xl flex items-start gap-3">
                            <Info className="h-4 w-4 text-blue-400 mt-0.5" />
                            <p className="text-sm text-blue-700 dark:text-blue-200">
                                <strong className="text-blue-800 dark:text-blue-100">Catatan:</strong> Jam operasional dapat berubah sewaktu-waktu. Silakan cek status toko secara real-time di indikator HUD bagian atas halaman.
                            </p>
                        </div>

                        <Separator className="bg-border" />
                    </CardContent>
                </Card>

                <div className="mt-16">
                    <OrganizationSection />
                </div>
            </div>
        </PublicLayout>
    )
}
