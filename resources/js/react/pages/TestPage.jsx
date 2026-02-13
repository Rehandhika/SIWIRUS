import React from 'react'

export default function TestPage() {
    console.log('[SIWIRUS] TestPage rendering')
    return (
        <div style={{ padding: '40px', backgroundColor: '#10b981', color: 'white', minHeight: '100vh' }}>
            <h1 style={{ fontSize: '32px', fontWeight: 'bold', marginBottom: '20px' }}>
                ✅ React is Working!
            </h1>
            <p style={{ fontSize: '18px' }}>
                If you can see this green page, React is rendering correctly.
            </p>
            <p style={{ marginTop: '20px', fontSize: '14px', opacity: 0.8 }}>
                Timestamp: {new Date().toISOString()}
            </p>
        </div>
    )
}
