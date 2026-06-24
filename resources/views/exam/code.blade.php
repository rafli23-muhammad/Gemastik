@php
    $studentName = session('student_name', 'Mahasiswa');
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masukkan Kode Ujian — AegisExam</title>
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
            max-width: 480px;
            background: #fff;
            border-radius: 24px;
            padding: 32px 28px;
            text-align: center;
            box-shadow: 0 24px 80px rgba(15, 23, 42, 0.12);
            border: 1px solid #e2e8f0;
        }
        .icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            border-radius: 16px;
            background: #f0fdfa;
            color: #0f766e;
            font-size: 28px;
            line-height: 64px;
        }
        h1 { margin: 0 0 8px; font-size: 24px; }
        p { margin: 0 0 12px; color: #64748b; font-size: 14px; line-height: 1.5; }
        .error {
            margin: 16px 0;
            padding: 12px 14px;
            border-radius: 12px;
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            font-size: 14px;
            text-align: left;
        }
        label {
            display: block;
            margin-top: 16px;
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            text-align: left;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        input {
            width: 100%;
            margin-top: 8px;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            text-align: center;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        button {
            width: 100%;
            margin-top: 20px;
            padding: 14px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, #0f766e 0%, #155e75 100%);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }
        a {
            display: inline-block;
            margin-top: 16px;
            color: #64748b;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="icon">&#128273;</div>
            <p style="font-size:11px;font-weight:700;letter-spacing:0.15em;color:#94a3b8;text-transform:uppercase;">Halo, {{ $studentName }}</p>
            <h1>Masukkan Kode Ujian</h1>
            <p>Gunakan kode dari dosen untuk membuka mata kuliah dan soal ujian.</p>

        @if($errors->any())
                <div class="error">{{ $errors->first() }}</div>
        @endif

            <form method="POST" action="/exam/code">
            @csrf
                <label for="course_code">Kode Ujian</label>
                <input id="course_code" name="course_code" value="{{ old('course_code') }}" required autofocus placeholder="Contoh: PWEB-UTS" />
                <button type="submit">Konfirmasi Kode</button>
        </form>

            <a href="/logout">&#8592; Ganti identitas</a>
        </div>
    </div>
    <script>
        (function () {
            var key = 'aegis_blank_reload_' + location.pathname;
            function count() { return Number(sessionStorage.getItem(key) || 0); }
            function check() {
                document.documentElement.style.backgroundColor = '#f4f7fb';
                document.body.style.backgroundColor = '#f4f7fb';
                var text = (document.body.innerText || '').replace(/\s+/g, ' ').trim();
                var ok = !!document.querySelector('form, h1, .card') && text.length >= 8;
                if (ok) { sessionStorage.removeItem(key); return; }
                if (count() >= 4) return;
                sessionStorage.setItem(key, String(count() + 1));
                location.reload();
            }
            setTimeout(check, 900);
            setTimeout(check, 2600);
        })();
    </script>
</body>
</html>
