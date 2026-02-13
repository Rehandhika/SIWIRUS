import React from 'react'
import { Home, Info, LogIn, Menu } from 'lucide-react'

import { Button } from '@/components/ui/button'
import { Menubar, MenubarContent, MenubarItem, MenubarMenu, MenubarTrigger } from '@/components/ui/menubar'
import StoreStatusPopover from '@/react/components/StoreStatusPopover'
import ThemeToggle from '@/react/components/ThemeToggle'

function useActivePath() {
    const [path, setPath] = React.useState(() => window.location.pathname)

    React.useEffect(() => {
        const onPopState = () => setPath(window.location.pathname)
        window.addEventListener('popstate', onPopState)
        return () => window.removeEventListener('popstate', onPopState)
    }, [])

    return path
}

export default function PublicNavbar() {
    const path = useActivePath()
    const isAbout = path === '/tentang'

    return (
        <div className="sticky top-3 sm:top-6 z-50 px-3 sm:px-4 mb-4 sm:mb-8">
            <div className="max-w-7xl mx-auto">
                <div className="relative rounded-xl sm:rounded-2xl border border-border bg-background/80 backdrop-blur-xl shadow-[0_4px_30px_rgba(0,0,0,0.1)] px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4 flex items-center justify-between gap-2">
                    {/* Logo - Left */}
                    <a href="/" className="flex items-center gap-1.5 sm:gap-2 group shrink-0 min-w-0 z-10">
                        <div className="h-6 w-6 sm:h-7 sm:w-7 md:h-8 md:w-8 flex items-center justify-center flex-shrink-0 bg-transparent">
                            <img 
                                src="/images/logo.png" 
                                alt="SIWIRUS" 
                                className="h-full w-full object-contain"
                            />
                        </div>
                        <div className="flex flex-col min-w-0">
                            <span className="font-semibold text-xs sm:text-sm md:text-base text-foreground tracking-tight leading-none group-hover:text-primary transition-colors truncate">
                                SIWIRUS
                            </span>
                            <span className="hidden sm:block text-[8px] md:text-[9px] uppercase tracking-[0.08em] md:tracking-[0.1em] text-muted-foreground truncate">
                                UKM Kewirausahaan STIS
                            </span>
                        </div>
                    </a>

                    {/* Store Status - Always Center (absolute positioning) */}
                    <div className="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                        <div className="scale-[0.7] sm:scale-90 md:scale-100">
                            <StoreStatusPopover />
                        </div>
                    </div>

                    {/* Desktop Menu - Right */}
                    <div className="hidden md:flex items-center justify-end gap-2 shrink-0 z-10">
                        <Button
                            asChild
                            variant="ghost"
                            size="sm"
                            className={[
                                isAbout ? '' : 'bg-accent text-accent-foreground',
                                'text-muted-foreground hover:text-foreground',
                            ].join(' ')}
                        >
                            <a href="/">Katalog</a>
                        </Button>
                        <Button
                            asChild
                            variant="ghost"
                            size="sm"
                            className={[
                                isAbout ? 'bg-accent text-accent-foreground' : '',
                                'text-muted-foreground hover:text-foreground',
                            ].join(' ')}
                        >
                            <a href="/tentang">Tentang</a>
                        </Button>
                        <ThemeToggle />
                        <Button
                            asChild
                            size="sm"
                            className="rounded-lg bg-primary/10 hover:bg-primary/20 text-primary border border-primary/30"
                            variant="outline"
                        >
                            <a href="/admin/masuk">Login</a>
                        </Button>
                    </div>

                    {/* Mobile Menu - Right */}
                    <div className="flex md:hidden items-center gap-1.5 shrink-0 z-10">
                        <ThemeToggle />
                        <Menubar className="border-border bg-background/60 h-8">
                            <MenubarMenu>
                                <MenubarTrigger className="px-2 h-7 data-[state=open]:bg-accent">
                                    <Menu className="h-4 w-4" />
                                </MenubarTrigger>
                                <MenubarContent align="end" className="min-w-40">
                                    <MenubarItem asChild>
                                        <a href="/" className="flex items-center gap-2">
                                            <Home className="h-4 w-4 text-muted-foreground" />
                                            Katalog
                                        </a>
                                    </MenubarItem>
                                    <MenubarItem asChild>
                                        <a href="/tentang" className="flex items-center gap-2">
                                            <Info className="h-4 w-4 text-muted-foreground" />
                                            Tentang
                                        </a>
                                    </MenubarItem>
                                    <MenubarItem asChild>
                                        <a href="/admin/masuk" className="flex items-center gap-2">
                                            <LogIn className="h-4 w-4 text-muted-foreground" />
                                            Login
                                        </a>
                                    </MenubarItem>
                                </MenubarContent>
                            </MenubarMenu>
                        </Menubar>
                    </div>
                </div>
            </div>
        </div>
    )
}
