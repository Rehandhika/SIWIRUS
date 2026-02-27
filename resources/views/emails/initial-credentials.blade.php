<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kredensial Akun {{ $appName }}</title>
    <style>
        body { font-family: 'Inter', -apple-system, sans-serif; line-height: 1.6; color: #1e293b; background-color: #f8fafc; margin: 0; padding: 0; }
        .wrapper { width: 100%; padding: 40px 0; }
        .container { max-width: 500px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
        .header { background: #1e293b; padding: 24px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 700; }
        .content { padding: 32px; }
        .greeting { font-size: 16px; margin-bottom: 24px; color: #64748b; }
        .greeting strong { color: #1e293b; }
        .card { background: #f1f5f9; border-radius: 8px; padding: 20px; margin-bottom: 24px; }
        .label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; font-weight: 600; }
        .value { font-size: 18px; color: #0f172a; font-family: 'Menlo', 'Monaco', monospace; font-weight: 700; margin-bottom: 16px; }
        .value:last-child { margin-bottom: 0; }
        .btn { display: block; background: #2563eb; color: #ffffff !important; padding: 14px; text-align: center; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; margin-bottom: 24px; }
        .footer { padding: 24px; border-top: 1px solid #f1f5f9; text-align: center; font-size: 12px; color: #94a3b8; }
        .warning { color: #dc2626; font-size: 13px; font-style: italic; text-align: center; margin-bottom: 24px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1>{{ $appName }}</h1>
            </div>
            <div class="content">
                <div class="greeting">
                    Halo, <strong>{{ $user->name }}</strong>!<br>
                    Akun Anda telah siap. Silakan gunakan kredensial berikut untuk masuk ke sistem.
                </div>

                <div class="card">
                    <div class="label">NIM</div>
                    <div class="value">{{ $nim }}</div>
                    
                    <div class="label">Password Sementara</div>
                    <div class="value">{{ $plainPassword }}</div>
                </div>

                <div class="warning">
                    * Segera ganti password Anda setelah berhasil masuk.
                </div>

                <a href="{{ $loginUrl }}" class="btn">Masuk ke Sistem</a>

                <div class="greeting" style="font-size: 13px; text-align: center; margin-bottom: 0;">
                    Jika ada kendala, hubungi Admin IT ({{ $supportEmail }})
                </div>
            </div>
            <div class="footer">
                &copy; {{ date('Y') }} {{ $appName }}.<br>
                Sistem Informasi Koperasi Mahasiswa STIS.
            </div>
        </div>
    </div>
</body>
</html>
