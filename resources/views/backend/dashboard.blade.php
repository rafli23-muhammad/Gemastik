@extends('layouts.exam')

@section('title', 'Dashboard Dosen - AegisExam Backend')

@php
    $activeTab = $activeTab ?? 'dashboard';
    $questionCount = count($backend['questions'] ?? []);
    $questionCodes = $backend['question_codes'] ?? [];
    $activeQuestionCodeId = request('code') ?: ($questionCodes[0]['id'] ?? null);
    $activeQuestionCode = collect($questionCodes)->firstWhere('id', $activeQuestionCodeId);
    $activeCodeQuestions = array_values(array_filter($backend['questions'] ?? [], function ($question) use ($activeQuestionCodeId) {
        return ($question['code_id'] ?? null) === $activeQuestionCodeId;
    }));
    $blockedCount = count($backend['blocked_students'] ?? []);
    $students = $backend['students'] ?? [];
    $trackedStudents = array_values(array_filter($students, function ($student) use ($backend) {
        return !collect($backend['blocked_students'] ?? [])->contains(function ($blocked) use ($student) {
            if (!empty($student['student_nim'])) {
                return ($blocked['student_nim'] ?? null) === $student['student_nim'];
            }

            return ($blocked['student_name'] ?? null) === ($student['student_name'] ?? null);
        });
    }));
    $activeStudents = $trackedStudents;
    $inProgressCount = count(array_filter($trackedStudents, fn ($student) => empty($student['last_result'])));
    $finishedCount = count($trackedStudents) - $inProgressCount;
    $activeCount = $inProgressCount;
    $violationCount = count($backend['violation_logs'] ?? []);
    $durationSeconds = (int) ($backend['exam_duration_seconds'] ?? 7200);
    $durationHours = floor($durationSeconds / 3600);
    $durationMinutes = floor(($durationSeconds % 3600) / 60);
    $latestViolation = !empty($backend['violation_logs']) ? end($backend['violation_logs']) : null;
    $pageTitles = [
        'dashboard' => 'Command Center Ujian',
        'questions' => 'Kelola Pertanyaan',
        'duration' => 'Atur Durasi Ujian',
        'violations' => 'Monitoring Pelanggaran',
        'students' => 'Pantau Mahasiswa',
        'blocked' => 'Mahasiswa Diblokir',
    ];
    $pageDescriptions = [
        'dashboard' => 'Pantau mahasiswa, atur pertanyaan, dan kelola durasi ujian dari satu dashboard yang lebih rapi dan cepat dibaca.',
        'questions' => 'Tambahkan dan susun beberapa soal sekaligus agar bank pertanyaan selalu siap dipakai mahasiswa.',
        'duration' => 'Tetapkan durasi ujian yang akan langsung dipakai sebagai countdown pada halaman mahasiswa.',
        'violations' => 'Lihat riwayat pelanggaran terbaru untuk membantu pengawasan ujian tetap akurat.',
        'students' => 'Pantau mahasiswa yang sedang atau sudah mengerjakan lengkap dengan snapshot dan statusnya.',
        'blocked' => 'Kelola mahasiswa yang diblokir dan buka aksesnya kembali jika terjadi kendala teknis.',
    ];
    $pageTitle = $pageTitles[$activeTab] ?? $pageTitles['dashboard'];
    $pageDescription = $pageDescriptions[$activeTab] ?? $pageDescriptions['dashboard'];
@endphp

@section('content')
<div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(45,212,191,0.16),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(251,146,60,0.14),_transparent_24%),linear-gradient(180deg,_#f4f7fb_0%,_#ecf2f7_48%,_#e8eef4_100%)] text-slate-900">
    <div class="mx-auto flex min-h-screen max-w-[1720px] gap-6 px-4 py-4 sm:px-6 lg:px-8">
        <aside class="hidden w-[290px] shrink-0 xl:block">
            <div class="sticky top-4 flex h-[calc(100vh-2rem)] flex-col overflow-hidden rounded-[32px] border border-teal-900/10 bg-[linear-gradient(180deg,_#10353c_0%,_#0f2d33_100%)] p-6 text-white shadow-[0_25px_80px_rgba(15,45,51,0.28)]">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-3xl bg-white p-1.5 ring-1 ring-white/30">
                        <img src="https://bsi.ac.id/storage/logo_UBSI.webp" alt="Logo Universitas Bina Sarana Informatika" class="h-full w-full object-contain">
                    </div>
                    <div>
                        <p class="text-xl font-bold tracking-tight">Selamat Datang, Dosen</p>
                        <p class="text-sm text-teal-100/70">Panel monitoring ujian</p>
                    </div>
                </div>

                <div class="mt-10">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-[0.28em] text-teal-100/45">Navigasi</p>
                    <div class="space-y-2">
                        <a href="/backend/dashboard" class="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-left text-sm font-medium transition {{ $activeTab === 'dashboard' ? 'bg-white text-slate-900 shadow-[0_12px_30px_rgba(15,23,42,0.16)]' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-chart-pie text-base"></i>
                                Dashboard
                            </span>
                            <span class="rounded-full bg-white/10 px-2.5 py-1 text-xs">Home</span>
                        </a>
                        <a href="/backend/dashboard/questions" class="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-left text-sm font-medium transition {{ $activeTab === 'questions' ? 'bg-white text-slate-900 shadow-[0_12px_30px_rgba(15,23,42,0.16)]' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                            <span class="flex items-center gap-3">
                                <i class="fa-regular fa-rectangle-list text-base"></i>
                                Pertanyaan
                            </span>
                            <span class="rounded-full bg-white/10 px-2.5 py-1 text-xs">{{ $questionCount }}</span>
                        </a>
                        <a href="/backend/dashboard/duration" class="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-left text-sm font-medium transition {{ $activeTab === 'duration' ? 'bg-white text-slate-900 shadow-[0_12px_30px_rgba(15,23,42,0.16)]' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                            <span class="flex items-center gap-3">
                                <i class="fa-regular fa-clock text-base"></i>
                                Durasi
                            </span>
                            <span class="rounded-full bg-white/10 px-2.5 py-1 text-xs">{{ gmdate('H:i', $durationSeconds) }}</span>
                        </a>
                        <a href="/backend/dashboard/violations" class="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-left text-sm font-medium transition {{ $activeTab === 'violations' ? 'bg-white text-slate-900 shadow-[0_12px_30px_rgba(15,23,42,0.16)]' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-triangle-exclamation text-base"></i>
                                Pelanggaran
                            </span>
                            <span class="monitor-count-violations rounded-full bg-white/10 px-2.5 py-1 text-xs">{{ $violationCount }}</span>
                        </a>
                        <a href="/backend/dashboard/students" class="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-left text-sm font-medium transition {{ $activeTab === 'students' ? 'bg-white text-slate-900 shadow-[0_12px_30px_rgba(15,23,42,0.16)]' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-user-graduate text-base"></i>
                                Mahasiswa
                            </span>
                            <span class="monitor-count-students rounded-full bg-white/10 px-2.5 py-1 text-xs">{{ $activeCount }}</span>
                        </a>
                        <a href="/backend/dashboard/blocked" class="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-left text-sm font-medium transition {{ $activeTab === 'blocked' ? 'bg-white text-slate-900 shadow-[0_12px_30px_rgba(15,23,42,0.16)]' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-user-lock text-base"></i>
                                Mahasiswa Blocked
                            </span>
                            <span class="monitor-count-blocked rounded-full bg-rose-500/20 px-2.5 py-1 text-xs text-rose-100">{{ $blockedCount }}</span>
                        </a>
                    </div>
                </div>

                <div class="mt-8 rounded-[28px] border border-white/10 bg-white/5 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-teal-100/45">Ringkasan Cepat</p>
                    <div class="mt-4 space-y-4 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-white/65">Soal siap pakai</span>
                            <span class="font-semibold text-white">{{ $questionCount }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-white/65">Mahasiswa aktif</span>
                            <span class="font-semibold text-white">{{ $activeCount }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-white/65">Butuh tindak lanjut</span>
                            <span class="monitor-count-blocked font-semibold text-amber-200">{{ $blockedCount }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-8 rounded-[28px] border border-white/10 bg-white/5 p-5">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-300 to-cyan-200 text-lg font-bold text-teal-950">
                            {{ strtoupper(substr($teacher['name'] ?? 'D', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-base font-semibold">{{ $teacher['name'] ?? 'Dosen' }}</p>
                            <p class="truncate text-sm text-white/60">{{ $teacher['nip'] ?? 'Backend pengawas ujian' }}</p>
                        </div>
                    </div>
                    <a href="/backend/logout" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-white/10 px-4 py-3 text-sm font-medium text-white transition hover:bg-white/15">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        Logout
                    </a>
                </div>
            </div>
        </aside>

        <div class="min-w-0 flex-1 py-2">
            <header class="rounded-[32px] border border-white/70 bg-white/85 p-5 shadow-[0_18px_60px_rgba(15,23,42,0.08)] backdrop-blur-xl">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-3 text-sm text-slate-500">
                            <a href="/backend" class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 font-medium text-slate-600">
                                <i class="fa-solid fa-house"></i>
                                Backend
                            </a>
                            <span class="text-slate-300">/</span>
                            <span class="font-medium text-slate-500">{{ $pageTitle }}</span>
                        </div>
                        <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ $pageTitle }}</h1>
                        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-500 sm:text-base">
                            {{ $pageDescription }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Status Sistem</p>
                            <p id="backend-live-status" class="mt-1 flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                Monitoring aktif
                            </p>
                        </div>
                        <div class="rounded-2xl border border-orange-200 bg-orange-50 px-4 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-orange-400">Prioritas</p>
                            <p class="mt-1 text-sm font-semibold text-orange-700"><span class="monitor-count-blocked">{{ $blockedCount }}</span> mahasiswa perlu pengecekan</p>
                        </div>
                    </div>
                </div>
            </header>

            <div id="server-lan-banner" class="mt-4 hidden rounded-[24px] border border-cyan-200 bg-cyan-50 px-5 py-4">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">Multi-device</p>
                <p class="mt-2 text-sm text-cyan-900">
                    <strong>LAN</strong> (WiFi sama) — IP:
                    <code id="server-lan-url" class="ml-1 rounded bg-white px-2 py-0.5 font-mono text-sm">memuat...</code>
                </p>
                <p id="server-cloud-row" class="mt-2 hidden text-sm text-violet-900">
                    <strong>Cloud</strong> (rumah ↔ kampus) — URL:
                    <code id="server-cloud-url" class="ml-1 break-all rounded bg-white px-2 py-0.5 font-mono text-sm">memuat...</code>
                </p>
                <p class="mt-2 text-xs text-cyan-800">Mahasiswa: buka AegisExam Mahasiswa → pilih LAN atau Cloud → paste alamat di atas. PC dosen harus tetap menyala.</p>
            </div>

            @if($activeTab === 'dashboard')
            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-[28px] border border-white/70 bg-white/90 p-5 shadow-[0_18px_50px_rgba(15,23,42,0.06)]">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Total Soal</p>
                            <p class="mt-3 text-4xl font-bold tracking-tight text-slate-900">{{ $questionCount }}</p>
                            <p class="mt-2 text-sm text-slate-500">Bank soal aktif untuk ujian saat ini</p>
                        </div>
                        <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-lime-200 text-2xl text-lime-700">
                            <i class="fa-regular fa-rectangle-list"></i>
                        </div>
                    </div>
                    <div class="mt-5 h-2 rounded-full bg-slate-100">
                        <div class="h-2 rounded-full bg-lime-400" style="width: {{ min(100, max(18, $questionCount * 8)) }}%"></div>
                    </div>
                </div>

                <div class="rounded-[28px] border border-white/70 bg-white/90 p-5 shadow-[0_18px_50px_rgba(15,23,42,0.06)]">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Durasi Ujian</p>
                            <p class="mt-3 text-4xl font-bold tracking-tight text-slate-900">{{ gmdate('H:i:s', $durationSeconds) }}</p>
                            <p class="mt-2 text-sm text-slate-500">{{ $durationHours }} jam {{ $durationMinutes }} menit pengawasan</p>
                        </div>
                        <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-cyan-200 text-2xl text-cyan-700">
                            <i class="fa-regular fa-clock"></i>
                        </div>
                    </div>
                    <div class="mt-5 inline-flex items-center rounded-full bg-cyan-50 px-3 py-1.5 text-xs font-semibold text-cyan-700">
                        Durasi mengikuti pengaturan dosen
                    </div>
                </div>

                <div class="rounded-[28px] border border-white/70 bg-white/90 p-5 shadow-[0_18px_50px_rgba(15,23,42,0.06)]">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Mahasiswa Aktif</p>
                            <p class="monitor-count-students mt-3 text-4xl font-bold tracking-tight text-slate-900">{{ $activeCount }}</p>
                            <p class="mt-2 text-sm text-slate-500">Sedang mengerjakan ujian saat ini</p>
                        </div>
                        <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-teal-200 text-2xl text-teal-700">
                            <i class="fa-solid fa-user-graduate"></i>
                        </div>
                    </div>
                    <div class="mt-5 inline-flex items-center rounded-full bg-teal-50 px-3 py-1.5 text-xs font-semibold text-teal-700">
                        Snapshot mahasiswa tersedia di panel mahasiswa
                    </div>
                </div>

                <div class="rounded-[28px] border border-white/70 bg-white/90 p-5 shadow-[0_18px_50px_rgba(15,23,42,0.06)]">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Mahasiswa Blocked</p>
                            <p class="monitor-count-blocked mt-3 text-4xl font-bold tracking-tight text-slate-900">{{ $blockedCount }}</p>
                            <p class="mt-2 text-sm text-slate-500">Perlu dibuka bila ada kesalahan teknis</p>
                        </div>
                        <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-orange-200 text-2xl text-orange-700">
                            <i class="fa-solid fa-user-lock"></i>
                        </div>
                    </div>
                    <div class="mt-5 inline-flex items-center rounded-full bg-orange-50 px-3 py-1.5 text-xs font-semibold text-orange-700">
                        Unblock otomatis mengaktifkan akses mahasiswa lagi
                    </div>
                </div>
            </div>

            <div class="mt-6 grid gap-6 2xl:grid-cols-[1.4fr_0.8fr]">
                <div class="rounded-[32px] border border-white/70 bg-white/90 p-6 shadow-[0_18px_60px_rgba(15,23,42,0.07)]">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Overview</p>
                            <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Aktivitas Dashboard</h2>
                            <p class="mt-2 text-sm text-slate-500">Ringkasan cepat untuk membantu dosen mengambil tindakan tanpa berpindah halaman.</p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <a href="/backend/dashboard/students" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Pantau Mahasiswa</a>
                            <a href="/backend/dashboard/blocked" class="rounded-2xl border border-orange-200 bg-orange-50 px-4 py-2.5 text-sm font-semibold text-orange-700 transition hover:bg-orange-100">Cek Blocked</a>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-4 lg:grid-cols-3">
                        <div class="rounded-[28px] bg-slate-50 p-5">
                            <p class="text-sm font-medium text-slate-500">Pelanggaran Tercatat</p>
                            <p class="monitor-count-violations mt-3 text-3xl font-bold text-slate-900">{{ $violationCount }}</p>
                            <p class="mt-2 text-sm text-slate-500">Log pelanggaran tersimpan untuk monitoring dosen.</p>
                        </div>
                        <div class="rounded-[28px] bg-slate-50 p-5">
                            <p class="text-sm font-medium text-slate-500">Mahasiswa Terakhir Masuk</p>
                            <p class="mt-3 text-lg font-bold text-slate-900">{{ $students ? ($students[array_key_last($students)]['student_name'] ?? '-') : '-' }}</p>
                            <p class="mt-2 text-sm text-slate-500">{{ $students ? ($students[array_key_last($students)]['last_seen'] ?? $students[array_key_last($students)]['started_at'] ?? '-') : 'Belum ada aktivitas mahasiswa.' }}</p>
                        </div>
                        <div class="rounded-[28px] bg-slate-50 p-5">
                            <p class="text-sm font-medium text-slate-500">Insiden Terbaru</p>
                            <p class="mt-3 text-lg font-bold text-slate-900">{{ $latestViolation['student_name'] ?? 'Belum ada pelanggaran' }}</p>
                            <p class="mt-2 text-sm text-slate-500">{{ $latestViolation['timestamp'] ?? 'Sistem masih bersih dari pelanggaran.' }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[32px] border border-white/70 bg-white/90 p-6 shadow-[0_18px_60px_rgba(15,23,42,0.07)]">
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Quick Notes</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Checklist Pengawasan</h2>
                    <div class="mt-6 space-y-4">
                        <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-900">1. Siapkan bank soal</p>
                            <p class="mt-1 text-sm text-slate-500">Tambahkan beberapa pertanyaan sekaligus agar mahasiswa langsung mendapat soal terbaru.</p>
                        </div>
                        <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-900">2. Atur durasi</p>
                            <p class="mt-1 text-sm text-slate-500">Timer mahasiswa otomatis mengikuti angka yang ditetapkan di panel durasi.</p>
                        </div>
                        <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-900">3. Tindak pelanggaran</p>
                            <p class="mt-1 text-sm text-slate-500">Jika ada salah blokir, cukup buka blokir dan halaman mahasiswa akan aktif kembali.</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($activeTab !== 'dashboard')
            <div class="mt-6 rounded-[32px] border border-white/70 bg-white/90 p-4 shadow-[0_18px_60px_rgba(15,23,42,0.07)] sm:p-6">
                <div class="space-y-6">
                    <section id="tab-questions" class="{{ $activeTab === 'questions' ? '' : 'hidden ' }}rounded-[28px] bg-slate-50 p-5 sm:p-6">
                        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                            <div class="max-w-2xl">
                                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Question Code Builder</p>
                                <h3 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Kelola Pertanyaan Berdasarkan Kode</h3>
                                <p class="mt-2 text-sm leading-relaxed text-slate-500">Buat kode untuk setiap mata kuliah, lalu klik kode tersebut untuk menambahkan soal pilihan ganda dan jawaban benar.</p>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <div class="rounded-[24px] border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-700">
                                    <span class="font-semibold">{{ count($questionCodes) }}</span> kode mata kuliah
                                </div>
                                <button type="button" id="open-code-modal" class="inline-flex items-center gap-2 rounded-2xl bg-[linear-gradient(135deg,_#0f766e_0%,_#155e75_100%)] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-teal-900/15 transition hover:opacity-95">
                                    <i class="fa-solid fa-plus"></i>
                                    Tambah Kode
                                </button>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-4 xl:grid-cols-[0.9fr_1.4fr]">
                            <div class="rounded-[28px] border border-slate-200 bg-white p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-400">Kode</p>
                                        <h4 class="mt-2 text-xl font-bold text-slate-900">Mata Kuliah</h4>
                                    </div>
                                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-600">{{ $questionCount }} soal</span>
                                </div>

                                <div class="mt-5 grid gap-3">
                                    @forelse($questionCodes as $code)
                                        @php
                                            $codeQuestionCount = count(array_filter($backend['questions'] ?? [], fn ($q) => ($q['code_id'] ?? null) === ($code['id'] ?? null)));
                                            $isActiveCode = ($code['id'] ?? null) === $activeQuestionCodeId;
                                        @endphp
                                        <a href="/backend/dashboard/questions?code={{ urlencode($code['id']) }}" class="rounded-[24px] border p-4 transition {{ $isActiveCode ? 'border-teal-300 bg-teal-50 ring-4 ring-teal-100' : 'border-slate-200 bg-slate-50 hover:border-teal-200 hover:bg-white' }}">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">{{ $code['course_code'] }}</p>
                                                    <p class="mt-1 text-base font-bold text-slate-900">{{ $code['course_name'] }}</p>
                                                </div>
                                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-teal-700">{{ $codeQuestionCount }} soal</span>
                                            </div>
                                        </a>
                                    @empty
                                        <div class="rounded-[24px] border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                                            Belum ada kode. Klik tombol Tambah Kode untuk membuat kode mata kuliah pertama.
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="rounded-[28px] border border-slate-200 bg-white p-5">
                                @if($activeQuestionCode)
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                        <div>
                                            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-400">{{ $activeQuestionCode['course_code'] }}</p>
                                            <h4 class="mt-2 text-xl font-bold text-slate-900">{{ $activeQuestionCode['course_name'] }}</h4>
                                        </div>
                                        <span class="rounded-full bg-teal-50 px-4 py-2 text-sm font-semibold text-teal-700">{{ count($activeCodeQuestions) }} soal tersimpan</span>
                                    </div>

                                    <form method="POST" action="/backend/questions" class="mt-6">
                                        @csrf
                                        <input type="hidden" name="code_id" value="{{ $activeQuestionCode['id'] }}">
                                        <div id="question-builder" class="space-y-4">
                                            <div class="question-item rounded-[28px] border border-slate-200 bg-slate-50 p-5">
                                                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                                    <div>
                                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Kartu Soal</p>
                                                        <h4 class="mt-1 text-lg font-bold text-slate-900">Pertanyaan <span class="question-number">1</span></h4>
                                                    </div>
                                                    <button type="button" class="remove-question inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                        Hapus
                                                    </button>
                                                </div>
                                                <div class="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
                                                    <div>
                                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Pertanyaan</label>
                                                        <textarea data-kind="question" name="questions[0][question]" class="min-h-[190px] w-full rounded-[24px] border border-slate-200 bg-white px-4 py-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-teal-300 focus:ring-4 focus:ring-teal-100" placeholder="Tulis soal di sini"></textarea>
                                                    </div>
                                                    <div>
                                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Pilihan Jawaban</label>
                                                        <textarea data-kind="options" name="questions[0][options]" class="min-h-[142px] w-full rounded-[24px] border border-slate-200 bg-white px-4 py-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-cyan-300 focus:ring-4 focus:ring-cyan-100" placeholder="A. Pilihan 1&#10;B. Pilihan 2&#10;C. Pilihan 3&#10;D. Pilihan 4"></textarea>
                                                        <label class="mt-3 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Jawaban Benar</label>
                                                        <select data-kind="answer" name="questions[0][correct_answer]" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-amber-300 focus:ring-4 focus:ring-amber-100">
                                                            <option value="">Pilih jawaban</option>
                                                            <option value="A">A</option>
                                                            <option value="B">B</option>
                                                            <option value="C">C</option>
                                                            <option value="D">D</option>
                                                            <option value="E">E</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                                            <button type="button" id="add-question-item" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                                <i class="fa-solid fa-plus"></i>
                                                Tambah Pertanyaan PG
                                            </button>
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-[linear-gradient(135deg,_#0f766e_0%,_#155e75_100%)] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-teal-900/15 transition hover:opacity-95">
                                                <i class="fa-solid fa-floppy-disk"></i>
                                                Simpan Soal
                                            </button>
                                        </div>
                                    </form>

                                    <div class="mt-8 grid gap-4">
                                        @forelse($activeCodeQuestions as $index => $q)
                                            <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                                                <div class="flex items-start justify-between gap-4">
                                                    <div>
                                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Soal {{ $index + 1 }}</p>
                                                        <p class="mt-2 text-sm leading-relaxed text-slate-700">{{ $q['text'] }}</p>
                                                        <p class="mt-3 text-xs font-semibold text-amber-700">Jawaban benar: {{ $q['correct_answer'] ?? '-' }}</p>
                                                    </div>
                                                    <div class="flex flex-col items-end gap-4 sm:flex-row sm:items-center sm:justify-end">
                                                        <div class="rounded-2xl bg-lime-100 px-3 py-1.5 text-xs font-semibold text-lime-700">{{ count($q['options'] ?? []) }} opsi</div>
                                                        <form method="POST" action="/backend/questions/delete" onsubmit="return confirm('Yakin ingin menghapus soal ini?');">
                                                            @csrf
                                                            <input type="hidden" name="question_id" value="{{ $q['id'] }}">
                                                            <input type="hidden" name="code_id" value="{{ $activeQuestionCode['id'] ?? '' }}">
                                                            <button type="submit" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 hover:text-rose-700">
                                                                <i class="fa-solid fa-trash"></i>
                                                                Hapus
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="rounded-[24px] border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                                                Belum ada soal untuk kode ini.
                                            </div>
                                        @endforelse
                                    </div>
                                @else
                                    <div class="flex min-h-[360px] items-center justify-center rounded-[24px] border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                                        <div>
                                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-teal-50 text-teal-700">
                                                <i class="fa-solid fa-code text-2xl"></i>
                                            </div>
                                            <h4 class="mt-4 text-xl font-bold text-slate-900">Pilih atau buat kode dulu</h4>
                                            <p class="mt-2 text-sm text-slate-500">Form pertanyaan PG akan muncul setelah ada kode mata kuliah.</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </section>

                    <section id="tab-duration" class="{{ $activeTab === 'duration' ? '' : 'hidden ' }}rounded-[28px] bg-slate-50 p-5 sm:p-6">
                        <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                            <div class="rounded-[28px] border border-slate-200 bg-white p-5">
                                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Time Control</p>
                                <h3 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Atur Durasi Ujian</h3>
                                <p class="mt-2 text-sm text-slate-500">Durasi ini dikirim ke mahasiswa saat ujian dimulai dan dipakai langsung sebagai countdown.</p>

                                <form method="POST" action="/backend/duration" class="mt-6">
                                    @csrf
                                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Durasi dalam detik</label>
                                    <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                                        <input name="duration" type="number" min="60" value="{{ $durationSeconds }}" class="w-full rounded-[22px] border border-slate-200 bg-slate-50 px-4 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-cyan-300 focus:bg-white focus:ring-4 focus:ring-cyan-100">
                                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-[22px] bg-[linear-gradient(135deg,_#0f766e_0%,_#155e75_100%)] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-teal-900/15 transition hover:opacity-95">
                                            <i class="fa-regular fa-clock"></i>
                                            Simpan Durasi
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="rounded-[28px] border border-slate-200 bg-white p-5">
                                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Preview</p>
                                <div class="mt-5 rounded-[24px] bg-[linear-gradient(135deg,_#0f766e_0%,_#164e63_100%)] p-6 text-white">
                                    <p class="text-sm text-white/70">Durasi saat ini</p>
                                    <p class="mt-3 text-4xl font-bold tracking-tight">{{ gmdate('H:i:s', $durationSeconds) }}</p>
                                    <p class="mt-2 text-sm text-white/75">Setara dengan {{ $durationHours }} jam {{ $durationMinutes }} menit.</p>
                                </div>
                                <div class="mt-4 rounded-[24px] bg-slate-50 p-4 text-sm text-slate-500">
                                    Saat mahasiswa menekan tombol mulai, timer di frontend otomatis mengikuti nilai ini.
                                </div>
                            </div>
                        </div>
                    </section>

                    <section id="tab-violations" class="{{ $activeTab === 'violations' ? '' : 'hidden ' }}rounded-[28px] bg-slate-50 p-5 sm:p-6">
                        <div class="rounded-[28px] border border-slate-200 bg-white p-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Violation Feed</p>
                                    <h3 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Riwayat Pelanggaran Mahasiswa</h3>
                                </div>
                                <div class="rounded-full bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700">{{ $violationCount }} log tercatat</div>
                            </div>

                            <div id="monitor-violations-list" class="mt-6 grid gap-4">
                                @forelse(array_reverse($backend['violation_logs'] ?? []) as $log)
                                    <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                            <div class="flex flex-1 gap-4">
                                                @if(!empty($log['snapshot_image']))
                                                    <img src="{{ $log['snapshot_image'] }}" class="h-28 w-32 rounded-[20px] object-cover" alt="snapshot pelanggaran">
                                                @else
                                                    <div class="flex h-28 w-32 items-center justify-center rounded-[20px] bg-slate-200 text-center text-xs font-semibold text-slate-500">
                                                        Tidak ada foto
                                                    </div>
                                                @endif
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">{{ $log['timestamp'] }}</p>
                                                <p class="mt-2 text-base font-bold text-slate-900">{{ $log['student_name'] }} <span class="font-medium text-slate-400">({{ $log['student_nim'] }})</span></p>
                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">{{ str_replace('-', ' ', $log['violation_type'] ?? 'general') }}</span>
                                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Count: {{ $log['violation_count'] }}</span>
                                                </div>
                                                <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $log['violation_message'] ?? 'Pelanggaran terdeteksi oleh sistem pengawasan ujian.' }}</p>
                                            </div>
                                            </div>
                                            <div class="rounded-2xl px-4 py-2 text-sm font-semibold {{ !empty($log['is_blocked']) ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700' }}">
                                                {{ !empty($log['is_blocked']) ? 'BLOCKED' : 'WARNING' }}
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-[24px] border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                                        Belum ada pelanggaran yang tercatat.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </section>

                    <section id="tab-students" class="{{ $activeTab === 'students' ? '' : 'hidden ' }}rounded-[28px] bg-slate-50 p-5 sm:p-6">
                        <div class="rounded-[28px] border border-slate-200 bg-white p-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Live Students</p>
                                    <h3 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Mahasiswa Sedang / Sudah Mengerjakan</h3>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <div class="rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">{{ $inProgressCount }} sedang mengerjakan</div>
                                    <div class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">{{ $finishedCount }} selesai</div>
                                </div>
                            </div>

                            <div id="monitor-students-list" class="mt-6 grid gap-4 xl:grid-cols-2">
                                @forelse($activeStudents as $student)
                                    @php
                                        $lastResult = $student['last_result'] ?? null;
                                        $examStatus = !empty($lastResult) ? 'selesai' : 'aktif';
                                    @endphp
                                    <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                                        <div class="flex gap-4">
                                            @if(!empty($student['last_snapshot']))
                                                <img src="{{ $student['last_snapshot'] }}" class="h-24 w-28 rounded-[20px] object-cover" alt="snapshot">
                                            @else
                                                <div class="flex h-24 w-28 items-center justify-center rounded-[20px] bg-slate-200 text-xs font-semibold text-slate-500">No Image</div>
                                            @endif
                                            <div class="min-w-0 flex-1">
                                                <p class="text-lg font-bold text-slate-900">{{ $student['student_name'] }}</p>
                                                <p class="mt-1 text-sm text-slate-500">NIM: {{ $student['student_nim'] ?: '-' }}</p>
                                                <p class="mt-1 text-sm text-slate-500">Terakhir terlihat: {{ $student['last_seen'] ?? $student['started_at'] }}</p>
                                                <div class="mt-4 flex flex-wrap gap-2">
                                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Violations: {{ $student['violation_count'] ?? 0 }}</span>
                                                    @if($examStatus === 'selesai')
                                                        <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">Status: Selesai</span>
                                                    @else
                                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Status: Aktif</span>
                                                    @endif
                                                </div>
                                                @if($lastResult)
                                                    <div class="mt-4 rounded-[20px] border border-slate-200 bg-white p-3">
                                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                                            <div>
                                                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $lastResult['course_code'] ?? '-' }}</p>
                                                                <p class="mt-1 text-sm font-bold text-slate-800">{{ $lastResult['course_name'] ?? '-' }}</p>
                                                            </div>
                                                            <span class="rounded-full bg-teal-50 px-3 py-1.5 text-xs font-bold text-teal-700">Nilai: {{ $lastResult['score'] ?? 0 }}</span>
                                                        </div>
                                                        <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                                                            <div class="rounded-2xl bg-slate-50 px-3 py-2">
                                                                <p class="text-[10px] font-semibold uppercase text-slate-400">Soal</p>
                                                                <p class="text-sm font-bold text-slate-800">{{ $lastResult['total_questions'] ?? 0 }}</p>
                                                            </div>
                                                            <div class="rounded-2xl bg-emerald-50 px-3 py-2">
                                                                <p class="text-[10px] font-semibold uppercase text-emerald-600">Benar</p>
                                                                <p class="text-sm font-bold text-emerald-700">{{ $lastResult['correct_count'] ?? 0 }}</p>
                                                            </div>
                                                            <div class="rounded-2xl bg-rose-50 px-3 py-2">
                                                                <p class="text-[10px] font-semibold uppercase text-rose-600">Salah</p>
                                                                <p class="text-sm font-bold text-rose-700">{{ $lastResult['wrong_count'] ?? 0 }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="mt-4 rounded-[20px] border border-dashed border-slate-300 bg-white/70 p-3 text-sm text-slate-500">
                                                        Belum ada hasil ujian tersimpan.
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-[24px] border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500 xl:col-span-2">
                                        Belum ada mahasiswa yang mengerjakan ujian.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </section>

                    <section id="tab-blocked" class="{{ $activeTab === 'blocked' ? '' : 'hidden ' }}rounded-[28px] bg-slate-50 p-5 sm:p-6">
                        <div class="rounded-[28px] border border-slate-200 bg-white p-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Recovery Desk</p>
                                    <h3 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Mahasiswa yang Diblokir</h3>
                                    <p class="mt-2 text-sm text-slate-500">Buka blokir bila insiden berasal dari kesalahan teknis. Mahasiswa akan bisa kembali mengerjakan soal.</p>
                                </div>
                                <div class="flex flex-wrap items-center gap-3">
                                <div class="rounded-full bg-orange-50 px-4 py-2 text-sm font-semibold text-orange-700">{{ $blockedCount }} mahasiswa blocked</div>
                                    @if($blockedCount > 0)
                                        <form method="POST" action="/backend/blocked/clear-all" onsubmit="return confirm('Hapus semua daftar akses diblokir? Mahasiswa bisa masuk ujian lagi.');">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-2xl border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-50">
                                                <i class="fa-solid fa-trash-can"></i>
                                                Hapus Semua Blokir
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            @if(session('success'))
                                <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                                    {{ session('success') }}
                                </div>
                            @endif

                            <div id="monitor-blocked-list" class="mt-6 grid gap-4">
                                @forelse($backend['blocked_students'] ?? [] as $blocked)
                                    <div class="rounded-[24px] border border-rose-200 bg-rose-50/70 p-4">
                                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                            <div>
                                                <p class="text-lg font-bold text-slate-900">{{ $blocked['student_name'] }}</p>
                                                <p class="mt-1 text-sm text-slate-500">NIM: {{ $blocked['student_nim'] ?: '-' }}</p>
                                                <p class="mt-1 text-sm text-slate-500">Blocked at: {{ $blocked['blocked_at'] }}</p>
                                            </div>
                                            <form method="POST" action="/backend/unblock">
                                                @csrf
                                                <input type="hidden" name="student_name" value="{{ $blocked['student_name'] }}">
                                                <input type="hidden" name="student_nim" value="{{ $blocked['student_nim'] }}">
                                                <button class="inline-flex items-center gap-2 rounded-2xl bg-[linear-gradient(135deg,_#f97316_0%,_#ea580c_100%)] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-orange-900/10 transition hover:opacity-95">
                                                    <i class="fa-solid fa-unlock"></i>
                                                    Buka Blokir
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-[24px] border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                                        Belum ada mahasiswa yang diblokir.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </section>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<div id="code-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center px-4">
    <div class="absolute inset-0 bg-slate-900/30 backdrop-blur-sm"></div>
    <div id="code-modal-panel" class="relative w-full max-w-md scale-95 rounded-[28px] border border-white/80 bg-white p-6 opacity-0 shadow-[0_24px_80px_rgba(15,23,42,0.20)] transition-all duration-200">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-400">Kode Baru</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Tambah Kode Mata Kuliah</h2>
            </div>
            <button type="button" id="close-code-modal" class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-500 transition hover:bg-slate-100">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="/backend/question-codes" class="mt-6">
            @csrf
            <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Kode</label>
            <input name="course_code" required maxlength="40" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold uppercase text-slate-900 outline-none transition focus:border-teal-300 focus:bg-white focus:ring-4 focus:ring-teal-100" placeholder="Contoh: PWEB-UTS">

            <label class="mt-4 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Mata Kuliah</label>
            <input name="course_name" required maxlength="120" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-teal-300 focus:bg-white focus:ring-4 focus:ring-teal-100" placeholder="Contoh: Pemrograman Web Pro">

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" id="cancel-code-modal" class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Batal</button>
                <button type="submit" class="rounded-2xl bg-[linear-gradient(135deg,_#0f766e_0%,_#155e75_100%)] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-teal-900/15 transition hover:opacity-95">Buat Kode</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const currentBackendPage = @json($activeTab);
        const builder = document.getElementById('question-builder');
        const addQuestionItemButton = document.getElementById('add-question-item');
        const liveStatus = document.getElementById('backend-live-status');
        const codeModal = document.getElementById('code-modal');
        const codeModalPanel = document.getElementById('code-modal-panel');
        const openCodeModalButton = document.getElementById('open-code-modal');
        const closeCodeModalButton = document.getElementById('close-code-modal');
        const cancelCodeModalButton = document.getElementById('cancel-code-modal');
        const autoRefreshPages = ['dashboard', 'violations', 'students', 'blocked'];

        async function loadServerLanBanner() {
            const banner = document.getElementById('server-lan-banner');
            const lanEl = document.getElementById('server-lan-url');
            const cloudRow = document.getElementById('server-cloud-row');
            const cloudEl = document.getElementById('server-cloud-url');
            if (!banner || !lanEl) return;

            try {
                const response = await fetch('/backend/server-info', {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });

                if (!response.ok) return;

                const data = await response.json();
                if (data.server_url) {
                    banner.classList.remove('hidden');
                    lanEl.textContent = data.server_url;
                }
                if (data.cloud_url && cloudRow && cloudEl) {
                    cloudRow.classList.remove('hidden');
                    cloudEl.textContent = data.cloud_url;
                }
            } catch (error) {
                console.warn('Gagal memuat alamat server:', error);
            }
        }

        loadServerLanBanner();

        function refreshQuestionNumbers() {
            if (!builder) return;

            builder.querySelectorAll('.question-item').forEach((item, index) => {
                const title = item.querySelector('.question-number');
                const questionInput = item.querySelector('textarea[data-kind="question"]') || item.querySelector('textarea[name*="[question]"]');
                const optionsInput = item.querySelector('textarea[data-kind="options"]') || item.querySelector('textarea[name*="[options]"]');
                const answerInput = item.querySelector('select[data-kind="answer"]') || item.querySelector('select[name*="[correct_answer]"]');

                if (title) title.textContent = index + 1;
                if (questionInput) questionInput.name = `questions[${index}][question]`;
                if (optionsInput) optionsInput.name = `questions[${index}][options]`;
                if (answerInput) answerInput.name = `questions[${index}][correct_answer]`;
            });
        }

        function bindQuestionItem(item) {
            const removeButton = item.querySelector('.remove-question');
            if (!removeButton || !builder) return;

            removeButton.addEventListener('click', () => {
                const items = builder.querySelectorAll('.question-item');
                if (items.length === 1) {
                    item.querySelectorAll('textarea').forEach((textarea) => {
                        textarea.value = '';
                    });
                    item.querySelectorAll('select').forEach((select) => {
                        select.value = '';
                    });
                    return;
                }

                item.remove();
                refreshQuestionNumbers();
            });
        }

        function createQuestionItem(index) {
            const wrapper = document.createElement('div');
            wrapper.className = 'question-item rounded-[28px] border border-slate-200 bg-slate-50 p-5';
            wrapper.innerHTML = `
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Kartu Soal</p>
                        <h4 class="mt-1 text-lg font-bold text-slate-900">Pertanyaan <span class="question-number">${index + 1}</span></h4>
                    </div>
                    <button type="button" class="remove-question inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                        <i class="fa-regular fa-trash-can"></i>
                        Hapus
                    </button>
                </div>
                <div class="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
                    <div>
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Pertanyaan</label>
                        <textarea data-kind="question" name="questions[${index}][question]" class="min-h-[190px] w-full rounded-[24px] border border-slate-200 bg-white px-4 py-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-teal-300 focus:ring-4 focus:ring-teal-100" placeholder="Tulis soal di sini"></textarea>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Pilihan Jawaban</label>
                        <textarea data-kind="options" name="questions[${index}][options]" class="min-h-[142px] w-full rounded-[24px] border border-slate-200 bg-white px-4 py-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-cyan-300 focus:ring-4 focus:ring-cyan-100" placeholder="A. Pilihan 1&#10;B. Pilihan 2&#10;C. Pilihan 3&#10;D. Pilihan 4"></textarea>
                        <label class="mt-3 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Jawaban Benar</label>
                        <select data-kind="answer" name="questions[${index}][correct_answer]" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-amber-300 focus:ring-4 focus:ring-amber-100">
                            <option value="">Pilih jawaban</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                        </select>
                    </div>
                </div>
            `;

            bindQuestionItem(wrapper);
            return wrapper;
        }

        if (builder) {
            builder.querySelectorAll('.question-item').forEach(bindQuestionItem);
            refreshQuestionNumbers();
        }

        if (addQuestionItemButton && builder) {
            addQuestionItemButton.addEventListener('click', () => {
                const nextIndex = builder.querySelectorAll('.question-item').length;
                builder.appendChild(createQuestionItem(nextIndex));
                refreshQuestionNumbers();
            });
        }

        function showCodeModal() {
            if (!codeModal || !codeModalPanel) return;
            codeModal.classList.remove('hidden');
            codeModal.classList.add('flex');
            requestAnimationFrame(() => {
                codeModalPanel.classList.remove('scale-95', 'opacity-0');
                codeModalPanel.classList.add('scale-100', 'opacity-100');
            });
        }

        function hideCodeModal() {
            if (!codeModal || !codeModalPanel) return;
            codeModalPanel.classList.add('scale-95', 'opacity-0');
            codeModalPanel.classList.remove('scale-100', 'opacity-100');
            setTimeout(() => {
                codeModal.classList.add('hidden');
                codeModal.classList.remove('flex');
            }, 200);
        }

        if (openCodeModalButton) openCodeModalButton.addEventListener('click', showCodeModal);
        if (closeCodeModalButton) closeCodeModalButton.addEventListener('click', hideCodeModal);
        if (cancelCodeModalButton) cancelCodeModalButton.addEventListener('click', hideCodeModal);
        if (codeModal) {
            codeModal.addEventListener('click', (event) => {
                if (event.target === codeModal) hideCodeModal();
            });
        }

        const MONITOR_POLL_MS = 1200;
        const MONITOR_POLL_FAST_MS = 700;
        const liveMonitorPages = ['violations', 'blocked', 'students'];
        const monitorPollMs = liveMonitorPages.includes(currentBackendPage)
            ? MONITOR_POLL_FAST_MS
            : MONITOR_POLL_MS;

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function isStudentBlockedInData(data, studentName, studentNim) {
            return (data.blocked_students || []).some((blocked) => {
                if (studentNim && (blocked.student_nim ?? null) === studentNim) {
                    return true;
                }

                return !studentNim && studentName && (blocked.student_name ?? null) === studentName;
            });
        }

        function getStudentExamStatus(student) {
            return student.last_result ? 'selesai' : 'aktif';
        }

        function computeMonitorStats(data) {
            const students = (data.students || []).filter((student) => !isStudentBlockedInData(
                data,
                student.student_name,
                student.student_nim || null,
            ));
            const inProgressCount = students.filter((student) => getStudentExamStatus(student) === 'aktif').length;
            const finishedCount = students.length - inProgressCount;

            return {
                violationCount: (data.violation_logs || []).length,
                blockedCount: (data.blocked_students || []).length,
                activeCount: inProgressCount,
                finishedCount,
                trackedCount: students.length,
                questionCount: data.questions_count ?? (data.questions || []).length,
            };
        }

        function buildViolationBlockSignature(data) {
            return JSON.stringify({
                blocked_count: (data.blocked_students || []).length,
                violations_count: (data.violation_logs || []).length,
                latest_blocked: (data.blocked_students || []).map((student) => `${student.student_nim || ''}:${student.student_name || ''}:${student.blocked_at || ''}`).join('|'),
                latest_violation: (data.violation_logs || []).map((log) => `${log.timestamp || ''}:${log.student_nim || ''}:${log.student_name || ''}:${log.violation_count || 0}:${log.violation_type || ''}:${log.is_blocked ? 1 : 0}`).join('|'),
            });
        }

        function buildStudentsSignature(data) {
            const stats = computeMonitorStats(data);

            return JSON.stringify({
                students_count: (data.students || []).length,
                active_count: stats.activeCount,
                latest_student_seen: (data.students || []).map((student) => `${student.student_nim || ''}:${student.last_seen || student.started_at || ''}:${student.violation_count || 0}:${getStudentExamStatus(student)}:${student.last_result?.finished_at || ''}`).join('|'),
            });
        }

        function updateMonitorBadges(data) {
            const stats = computeMonitorStats(data);

            document.querySelectorAll('.monitor-count-violations').forEach((el) => {
                el.textContent = stats.violationCount;
            });
            document.querySelectorAll('.monitor-count-blocked').forEach((el) => {
                el.textContent = stats.blockedCount;
            });
            document.querySelectorAll('.monitor-count-students').forEach((el) => {
                el.textContent = stats.activeCount;
            });
        }

        function renderViolationsList(data) {
            const container = document.getElementById('monitor-violations-list');
            if (!container) return;

            const logs = [...(data.violation_logs || [])].reverse();
            if (!logs.length) {
                container.innerHTML = '<div class="rounded-[24px] border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">Belum ada pelanggaran yang tercatat.</div>';
                return;
            }

            container.innerHTML = logs.map((log) => {
                const blockedBadge = log.is_blocked
                    ? '<div class="rounded-2xl px-4 py-2 text-sm font-semibold bg-rose-100 text-rose-700">BLOCKED</div>'
                    : '<div class="rounded-2xl px-4 py-2 text-sm font-semibold bg-amber-100 text-amber-700">WARNING</div>';

                return `
                    <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="flex flex-1 gap-4">
                                <div class="flex h-28 w-32 items-center justify-center rounded-[20px] bg-slate-200 text-center text-xs font-semibold text-slate-500">Live update</div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">${escapeHtml(log.timestamp)}</p>
                                    <p class="mt-2 text-base font-bold text-slate-900">${escapeHtml(log.student_name)} <span class="font-medium text-slate-400">(${escapeHtml(log.student_nim || '-')})</span></p>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">${escapeHtml((log.violation_type || 'general').replace(/-/g, ' '))}</span>
                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Count: ${escapeHtml(log.violation_count)}</span>
                                    </div>
                                    <p class="mt-3 text-sm leading-relaxed text-slate-600">${escapeHtml(log.violation_message || 'Pelanggaran terdeteksi oleh sistem pengawasan ujian.')}</p>
                                </div>
                            </div>
                            ${blockedBadge}
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderBlockedList(data) {
            const container = document.getElementById('monitor-blocked-list');
            if (!container) return;

            const blockedStudents = data.blocked_students || [];
            if (!blockedStudents.length) {
                container.innerHTML = '<div class="rounded-[24px] border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">Belum ada mahasiswa yang diblokir.</div>';
                return;
            }

            container.innerHTML = blockedStudents.map((blocked) => `
                <div class="rounded-[24px] border border-rose-200 bg-rose-50/70 p-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-lg font-bold text-slate-900">${escapeHtml(blocked.student_name)}</p>
                            <p class="mt-1 text-sm text-slate-500">NIM: ${escapeHtml(blocked.student_nim || '-')}</p>
                            <p class="mt-1 text-sm text-slate-500">Blocked at: ${escapeHtml(blocked.blocked_at)}</p>
                        </div>
                        <form method="POST" action="/backend/unblock">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="student_name" value="${escapeHtml(blocked.student_name)}">
                            <input type="hidden" name="student_nim" value="${escapeHtml(blocked.student_nim || '')}">
                            <button class="inline-flex items-center gap-2 rounded-2xl bg-[linear-gradient(135deg,_#f97316_0%,_#ea580c_100%)] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-orange-900/10 transition hover:opacity-95">
                                <i class="fa-solid fa-unlock"></i>
                                Buka Blokir
                            </button>
                        </form>
                    </div>
                </div>
            `).join('');
        }

        function renderStudentStatusBadge(student) {
            const status = getStudentExamStatus(student);

            if (status === 'selesai') {
                return '<span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">Status: Selesai</span>';
            }

            return '<span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Status: Aktif</span>';
        }

        function renderStudentResultCard(student) {
            const result = student.last_result;
            if (!result) {
                return '<div class="mt-4 rounded-[20px] border border-dashed border-slate-300 bg-white/70 p-3 text-sm text-slate-500">Belum ada hasil ujian tersimpan.</div>';
            }

            return `
                <div class="mt-4 rounded-[20px] border border-slate-200 bg-white p-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">${escapeHtml(result.course_code || '-')}</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">${escapeHtml(result.course_name || '-')}</p>
                        </div>
                        <span class="rounded-full bg-teal-50 px-3 py-1.5 text-xs font-bold text-teal-700">Nilai: ${escapeHtml(result.score ?? 0)}</span>
                    </div>
                </div>
            `;
        }

        function renderStudentsList(data) {
            const container = document.getElementById('monitor-students-list');
            if (!container) return;

            const trackedStudents = (data.students || []).filter((student) => !isStudentBlockedInData(
                data,
                student.student_name,
                student.student_nim || null,
            ));

            if (!trackedStudents.length) {
                container.innerHTML = '<div class="rounded-[24px] border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500 xl:col-span-2">Belum ada mahasiswa yang mengerjakan ujian.</div>';
                return;
            }

            container.innerHTML = trackedStudents.map((student) => `
                <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                    <div class="flex gap-4">
                        <div class="flex h-24 w-28 items-center justify-center rounded-[20px] bg-slate-200 text-xs font-semibold text-slate-500">Live update</div>
                        <div class="min-w-0 flex-1">
                            <p class="text-lg font-bold text-slate-900">${escapeHtml(student.student_name)}</p>
                            <p class="mt-1 text-sm text-slate-500">NIM: ${escapeHtml(student.student_nim || '-')}</p>
                            <p class="mt-1 text-sm text-slate-500">Terakhir terlihat: ${escapeHtml(student.last_seen || student.started_at || '-')}</p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Violations: ${escapeHtml(student.violation_count ?? 0)}</span>
                                ${renderStudentStatusBadge(student)}
                            </div>
                            ${renderStudentResultCard(student)}
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function applyMonitorData(data) {
            updateMonitorBadges(data);

            if (currentBackendPage === 'violations') {
                renderViolationsList(data);
            }

            if (currentBackendPage === 'blocked') {
                renderBlockedList(data);
            }

            if (currentBackendPage === 'students') {
                renderStudentsList(data);
            }
        }

        function flashLiveStatus(message, tone = 'emerald') {
            if (!liveStatus) return;

            const toneClasses = {
                emerald: 'bg-emerald-500',
                cyan: 'bg-cyan-500',
                amber: 'bg-amber-500',
            };

            liveStatus.innerHTML = `<span class="h-2.5 w-2.5 rounded-full ${toneClasses[tone] || toneClasses.emerald}"></span>${message}`;
        }

        let monitorInFlight = false;
        let violationBlockSignature = null;
        let studentsSignature = null;
        let reloadScheduled = false;

        function scheduleAutoReload(reason) {
            if (reloadScheduled) {
                return;
            }

            reloadScheduled = true;
            flashLiveStatus(reason, 'cyan');
            window.setTimeout(() => window.location.reload(), 350);
        }

        async function syncBackendMonitoring() {
            if (!autoRefreshPages.includes(currentBackendPage) || document.hidden || monitorInFlight || reloadScheduled) {
                return;
            }

            monitorInFlight = true;

            try {
                const response = await fetch('/backend/monitor', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });

                if (!response.ok) {
                    throw new Error('Gagal mengambil data backend.');
                }

                const data = await response.json();
                const nextViolationBlockSignature = buildViolationBlockSignature(data);
                const nextStudentsSignature = buildStudentsSignature(data);

                if (violationBlockSignature === null) {
                    violationBlockSignature = nextViolationBlockSignature;
                    studentsSignature = nextStudentsSignature;
                    flashLiveStatus('Auto reload aktif (~1 detik): pelanggaran, blokir & mahasiswa');
                    monitorInFlight = false;
                    return;
                }

                if (violationBlockSignature !== nextViolationBlockSignature) {
                    violationBlockSignature = nextViolationBlockSignature;
                    studentsSignature = nextStudentsSignature;

                    if (liveMonitorPages.includes(currentBackendPage)) {
                        scheduleAutoReload('Data pelanggaran/blokir berubah, memuat ulang halaman...');
                        return;
                    }

                    updateMonitorBadges(data);
                } else if (studentsSignature !== nextStudentsSignature) {
                    studentsSignature = nextStudentsSignature;

                    if (liveMonitorPages.includes(currentBackendPage)) {
                        scheduleAutoReload('Data mahasiswa berubah, memuat ulang halaman...');
                        return;
                    }

                    updateMonitorBadges(data);
                } else if (liveStatus && liveStatus.textContent.includes('tertunda')) {
                    flashLiveStatus('Auto reload aktif (~1 detik): pelanggaran, blokir & mahasiswa');
                }
            } catch (error) {
                flashLiveStatus('Auto update tertunda', 'amber');
                console.warn('Auto update backend gagal:', error);
            } finally {
                monitorInFlight = false;
            }
        }

        if (autoRefreshPages.includes(currentBackendPage)) {
            syncBackendMonitoring();
            setInterval(syncBackendMonitoring, monitorPollMs);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    syncBackendMonitoring();
                }
            });
        }

    });
</script>
@endpush
