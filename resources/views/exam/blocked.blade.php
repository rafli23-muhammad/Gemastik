<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Akses Ujian Diblokir — AegisExam</title>
    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            min-height: 100%;
            background: #f4f7fb;
            color: #0f172a;
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
        }
        .wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: linear-gradient(180deg, #f4f7fb 0%, #e6edf5 100%);
        }
        .card {
            width: 100%;
            max-width: 560px;
            background: #ffffff;
            border-radius: 24px;
            padding: 32px 28px;
            text-align: center;
            box-shadow: 0 24px 80px rgba(15, 23, 42, 0.12);
            border: 1px solid #e2e8f0;
        }
        .icon {
            width: 88px;
            height: 88px;
            margin: 0 auto 20px;
            border-radius: 20px;
            background: #fff1f2;
            color: #e11d48;
            font-size: 40px;
            line-height: 88px;
        }
        h1 {
            margin: 0 0 12px;
            font-size: 28px;
            color: #0f172a;
        }
        p { margin: 0 0 12px; line-height: 1.6; color: #475569; font-size: 15px; }
        .stats {
            margin: 20px 0;
            padding: 14px 18px;
            border-radius: 14px;
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #9f1239;
            font-weight: 600;
        }
        .info {
            margin: 16px 0;
            padding: 14px 16px;
            border-radius: 14px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
            font-size: 14px;
            text-align: left;
        }
        .status {
            margin: 16px 0;
            padding: 12px 14px;
            border-radius: 14px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            margin-top: 16px;
            padding: 12px 20px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #334155;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="icon">&#128274;</div>
            <h1>AKSES UJIAN DIBLOKIR</h1>
            <p>
                Sesi ujian <strong>{{ $exam->title }}</strong> dihentikan oleh sistem keamanan
                <strong>AegisExam</strong> karena pelanggaran melebihi batas.
            </p>
            <div class="stats">
                Pelanggaran: {{ $violationCount }} / {{ $exam->max_violation }}
                </div>
            <div class="info">
                Hubungi dosen untuk <strong>Buka Blokir</strong> di dashboard dosen jika ini kesalahan teknis.
                    </div>
            <div id="unblock-status" class="status">
                Memantau pembukaan blokir dari dosen...
            </div>
            <a class="btn" href="{{ url('/logout') }}">Logout</a>
        </div>
    </div>
<script>
        (function () {
            document.documentElement.style.backgroundColor = '#f4f7fb';
            document.body.style.backgroundColor = '#f4f7fb';

            if (window.aegisDesktop && typeof window.aegisDesktop.setFullScreen === 'function') {
                window.aegisDesktop.setFullScreen(false);
            }
            if (document.fullscreenElement && document.exitFullscreen) {
                document.exitFullscreen().catch(function () {});
            }

            var statusBox = document.getElementById('unblock-status');
            var isChecking = false;

            function checkExamStatus() {
            if (isChecking) return;
            isChecking = true;

                fetch('/api/exam/status', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                })
                    .then(function (response) {
                        if (!response.ok) throw new Error('status failed');
                        return response.json();
                    })
                    .then(function (data) {
                if (data.is_blocked === false && data.redirect) {
                    if (statusBox) {
                                statusBox.textContent = 'Blokir dibuka. Mengalihkan ke ujian...';
                    }
                            window.location.replace(data.redirect);
                    return;
                }
                if (statusBox && typeof data.violation_count !== 'undefined') {
                            statusBox.textContent = 'Masih diblokir. Pelanggaran: ' + data.violation_count + '. Cek lagi otomatis...';
                }
                    })
                    .catch(function () {
                if (statusBox) {
                            statusBox.textContent = 'Cek status otomatis bermasalah. Akan dicoba lagi...';
                }
                    })
                    .finally(function () {
                isChecking = false;
                    });
        }

        checkExamStatus();
        setInterval(checkExamStatus, 5000);

            var reloadKey = 'aegis_blank_reload_' + location.pathname;
            function reloadCount() { return Number(sessionStorage.getItem(reloadKey) || 0); }
            function autoReloadIfBlank() {
                var text = (document.body.innerText || '').replace(/\s+/g, ' ').trim();
                var ok = !!document.querySelector('h1, .card') && text.length >= 12;
                if (ok) { sessionStorage.removeItem(reloadKey); return; }
                if (reloadCount() >= 4) return;
                sessionStorage.setItem(reloadKey, String(reloadCount() + 1));
                location.reload();
            }
            setTimeout(autoReloadIfBlank, 900);
            setTimeout(autoReloadIfBlank, 2600);
        })();
</script>
</body>
</html>
