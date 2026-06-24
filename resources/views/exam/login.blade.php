@extends('layouts.exam')

@section('title', 'Login Mahasiswa — AegisExam')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-8 bg-[radial-gradient(circle_at_top_left,_rgba(45,212,191,0.16),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(251,146,60,0.12),_transparent_24%),linear-gradient(180deg,_#f4f7fb_0%,_#edf3f8_48%,_#e6edf5_100%)]">
    <div class="w-full max-w-lg rounded-[28px] border border-white/70 bg-white/92 p-8 text-center shadow-[0_24px_80px_rgba(15,23,42,0.12)] backdrop-blur-xl">

        <div id="server-connection-panel" class="mb-6 hidden rounded-2xl border border-teal-100 bg-teal-50/60 p-4 text-left">
            <div class="flex items-center gap-2 text-sm font-bold text-teal-800">
                <i class="fa-solid fa-network-wired"></i>
                Koneksi ke Server Ujian
            </div>
            <p class="mt-1 text-xs text-teal-900/80">Device dosen beda? Buka dosen dulu, lalu isi <strong>IP WiFi</strong> (LAN) atau <strong>URL Cloud</strong> dari dashboard dosen.</p>
            <p id="current-server-label" class="mt-2 rounded-xl bg-white/80 px-3 py-2 text-xs font-medium text-slate-600"></p>

            <div class="mt-3 flex gap-2">
                <button type="button" id="tab-lan" class="flex-1 rounded-xl border border-teal-200 bg-teal-700 px-3 py-2 text-xs font-bold text-white">LAN</button>
                <button type="button" id="tab-cloud" class="flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600">Cloud</button>
            </div>

            <div id="panel-lan" class="mt-3">
                <label class="block text-xs font-semibold text-slate-600">IP server (WiFi sama)</label>
                <input id="server-host" type="text" placeholder="192.168.1.10"
                    class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-teal-400 focus:ring-2 focus:ring-teal-100" />
                <label class="mt-2 block text-xs font-semibold text-slate-600">Port</label>
                <input id="server-port" type="number" value="8000"
                    class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-teal-400 focus:ring-2 focus:ring-teal-100" />
            </div>

            <div id="panel-cloud" class="mt-3 hidden">
                <label class="block text-xs font-semibold text-slate-600">URL cloud</label>
                <input id="server-cloud-url" type="text" placeholder="https://xxxx.loca.lt"
                    class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-teal-400 focus:ring-2 focus:ring-teal-100" />
            </div>

            <p id="setup-error" class="mt-2 hidden text-xs font-medium text-red-600"></p>
            <button type="button" id="btn-save-server"
                class="mt-3 w-full rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-teal-800">
                Simpan koneksi
            </button>
            <p class="mt-2 text-[11px] text-slate-500">Setelah simpan, app restart otomatis (agar kamera aktif). Pindah device = isi ulang IP/URL di sini.</p>
            <button type="button" id="btn-retry-server"
                class="mt-2 w-full rounded-xl border border-teal-200 bg-white px-4 py-2.5 text-xs font-bold text-teal-800 transition hover:bg-teal-50">
                Coba sambungkan lagi
            </button>
        </div>

        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-3xl bg-teal-50 text-teal-700 ring-4 ring-teal-100">
            <i class="fa-solid fa-user-graduate text-3xl"></i>
        </div>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">Masuk Ujian</h1>
        <p class="mt-2 text-sm text-slate-500">Masukkan nama dan NIM untuk memulai simulasi.</p>

        <form method="POST" action="/login" class="mt-6 text-left">
            @csrf
            @if ($errors->any())
                <div class="mb-4 rounded-2xl bg-red-50 p-4 text-xs font-medium text-red-600 border border-red-100">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <label class="block text-xs font-semibold text-slate-600">Nama</label>
            <input name="student_name" required class="mt-1 mb-3 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-400 focus:ring-2 focus:ring-teal-100" placeholder="Nama Lengkap">

            <label class="block text-xs font-semibold text-slate-600">NIM</label>
            <input name="student_nim" required class="mt-1 mb-3 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-400 focus:ring-2 focus:ring-teal-100" placeholder="NIM">

            <label class="block text-xs font-semibold text-slate-600">Email</label>
            <input type="email" name="student_email" required class="mt-1 mb-4 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-400 focus:ring-2 focus:ring-teal-100" placeholder="alamat@email.com">

            <div class="flex items-center justify-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-[linear-gradient(135deg,_#0f766e_0%,_#155e75_100%)] px-6 py-3 text-sm font-semibold text-white shadow-[0_18px_40px_rgba(15,118,110,0.22)] transition hover:opacity-95">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Masuk
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const panel = document.getElementById('server-connection-panel');
    if (!panel || !window.aegisDesktop?.saveServerConnection) {
        return;
    }

    panel.classList.remove('hidden');

    let mode = 'lan';
    const tabLan = document.getElementById('tab-lan');
    const tabCloud = document.getElementById('tab-cloud');
    const panelLan = document.getElementById('panel-lan');
    const panelCloud = document.getElementById('panel-cloud');
    const err = document.getElementById('setup-error');
    const btn = document.getElementById('btn-save-server');
    const label = document.getElementById('current-server-label');

    function setMode(next) {
        mode = next;
        const lanActive = mode === 'lan';
        tabLan.className = lanActive
            ? 'flex-1 rounded-xl border border-teal-200 bg-teal-700 px-3 py-2 text-xs font-bold text-white'
            : 'flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600';
        tabCloud.className = !lanActive
            ? 'flex-1 rounded-xl border border-teal-200 bg-teal-700 px-3 py-2 text-xs font-bold text-white'
            : 'flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600';
        panelLan.classList.toggle('hidden', !lanActive);
        panelCloud.classList.toggle('hidden', lanActive);
    }

    tabLan.addEventListener('click', () => setMode('lan'));
    tabCloud.addEventListener('click', () => setMode('cloud'));

    window.aegisDesktop.getServerInfo().then((info) => {
        if (!info) {
            label.textContent = 'Belum terhubung ke server.';
            return;
        }

        label.textContent = 'Terhubung ke: ' + (info.baseUrl || '-');
        if (info.cloudUrl) {
            setMode('cloud');
            document.getElementById('server-cloud-url').value = info.cloudUrl;
        } else if (info.host) {
            setMode('lan');
            document.getElementById('server-host').value = info.host;
            document.getElementById('server-port').value = info.port || 8000;
        }
    }).catch(() => {
        label.textContent = 'Belum terhubung ke server.';
    });

    const retryBtn = document.getElementById('btn-retry-server');
    if (retryBtn && window.aegisDesktop.retryServerConnection) {
        retryBtn.addEventListener('click', async () => {
            retryBtn.disabled = true;
            retryBtn.textContent = 'Menghubungkan...';
            try {
                const result = await window.aegisDesktop.retryServerConnection();
                if (!result?.ok) {
                    err.textContent = result?.message || 'Server dosen belum bisa dihubungi.';
                    err.classList.remove('hidden');
                    retryBtn.disabled = false;
                    retryBtn.textContent = 'Coba sambungkan lagi';
                }
            } catch (e) {
                err.textContent = 'Gagal menghubungi server dosen.';
                err.classList.remove('hidden');
                retryBtn.disabled = false;
                retryBtn.textContent = 'Coba sambungkan lagi';
            }
        });
    }

    btn.addEventListener('click', async () => {
        err.classList.add('hidden');
        btn.disabled = true;
        btn.textContent = 'Menyimpan...';

        try {
            let result;
            if (mode === 'cloud') {
                result = await window.aegisDesktop.saveServerConnection({
                    connectionMode: 'cloud',
                    cloudUrl: document.getElementById('server-cloud-url').value.trim(),
                });
            } else {
                const host = document.getElementById('server-host').value.trim();
                const port = Number(document.getElementById('server-port').value || 8000);
                if (!host) {
                    err.textContent = 'IP server wajib diisi.';
                    err.classList.remove('hidden');
                    btn.disabled = false;
                    btn.textContent = 'Simpan koneksi';
                    return;
                }
                result = await window.aegisDesktop.saveServerConnection({ connectionMode: 'lan', host, port });
            }

            if (!result?.ok) {
                err.textContent = result?.message || 'Gagal menyimpan koneksi.';
                err.classList.remove('hidden');
                btn.disabled = false;
                btn.textContent = 'Simpan koneksi';
                return;
            }

            btn.textContent = 'Tersimpan, memuat ulang...';
            setTimeout(() => {
                btn.disabled = false;
                btn.textContent = 'Simpan koneksi';
            }, 4000);
        } catch (e) {
            err.textContent = 'Gagal menyimpan. Pastikan AegisExam Dosen sudah dibuka.';
            err.classList.remove('hidden');
            btn.disabled = false;
            btn.textContent = 'Simpan koneksi';
        }
    });
})();
</script>
@endsection
