import React from 'react'

export default function PublicFooter() {
    return (
        <footer className="mt-auto border-t border-border/50 bg-background/60 backdrop-blur-sm">
            <div className="max-w-7xl mx-auto px-4 py-6">
                <div className="flex flex-col sm:flex-row justify-between items-center gap-3 text-xs text-muted-foreground">
                    <div className="flex items-center gap-2">
                        <span className="w-1.5 h-1.5 bg-primary/60 rounded-full" />
                        <span className="tracking-wide">SIWIRUS</span>
                        <span className="text-border">•</span>
                        <span>Divisi IT UKM Kewirausahaan STIS</span>
                    </div>
                    
                    <span className="font-mono text-muted-foreground/70">
                        © {new Date().getFullYear()}
                    </span>
                </div>
            </div>
        </footer>
    )
}
