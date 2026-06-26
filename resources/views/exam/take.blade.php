@extends('layouts.exam')

@section('title', $exam->title . ' — AegisExam Simulasi')

@php
    $studentName = session('student_name', 'Mahasiswa Simulasi');
    $questionsPayload = $questionsPayload ?? [];
    $examDuration = $examDuration ?? 7200;
    $selectedQuestionCode = $selectedQuestionCode ?? null;
@endphp

@section('content')
{{-- Layar mulai simulasi --}}
<div id="pre-exam-screen" class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(45,212,191,0.16),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(251,146,60,0.12),_transparent_24%),linear-gradient(180deg,_#f4f7fb_0%,_#edf3f8_48%,_#e6edf5_100%)] px-4 py-10"
    style="min-height:100vh;background:linear-gradient(180deg,#f4f7fb 0%,#e6edf5 100%);">
    <div class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-6xl items-center justify-center">
    <div class="w-full max-w-2xl rounded-[32px] border border-white/80 bg-white/92 p-8 shadow-[0_30px_90px_rgba(15,23,42,0.10)] text-center backdrop-blur-xl">
        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-3xl bg-teal-600 text-white shadow-lg shadow-teal-900/20">
            <i class="fa-solid fa-graduation-cap text-3xl"></i>
        </div>
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-teal-700">
            {{ $selectedQuestionCode['course_code'] ?? 'Kode Ujian' }}
        </p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $exam->title }}</h1>
        <p class="mt-2 text-sm text-slate-500">
            <i class="fa-regular fa-user mr-1"></i>{{ $studentName }}
        </p>
        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Mata Kuliah</p>
                <p class="mt-1 text-sm font-bold text-slate-800">{{ $selectedQuestionCode['course_name'] ?? $exam->title }}</p>
            </div>
            <div class="rounded-2xl border border-teal-200 bg-teal-50 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-teal-500">Jumlah Soal</p>
                <p class="mt-1 text-2xl font-bold text-teal-700">{{ count($questionsPayload) }}</p>
            </div>
        </div>
        <p class="mt-4 text-sm text-slate-500">
            Batas pelanggaran:
            <span class="font-semibold text-orange-600">{{ $exam->max_violation }}x</span>
            | Saat ini:
            <span id="violation-count-pre" class="font-semibold text-rose-600">{{ $violationCount }}</span>
        </p>
        <p class="mt-6 text-xs leading-relaxed text-slate-500">
            Waktu pengerjaan: {{ gmdate('H:i:s', $examDuration) }}
        </p>
        <p class="mt-3 text-xs text-slate-400">
            Setelah klik Mulai Ujian, tampilan otomatis masuk mode layar penuh.
        </p>
        <button type="button" id="btn-start-exam"
            class="mt-8 inline-flex items-center gap-2 rounded-2xl bg-[linear-gradient(135deg,_#0f766e_0%,_#155e75_100%)] px-8 py-3.5 text-sm font-semibold text-white shadow-[0_18px_40px_rgba(15,118,110,0.25)] transition hover:opacity-95">
            <i class="fa-solid fa-play"></i>
            Mulai Ujian
        </button>
        
    </div>
    </div>
</div>

<style>
    #exam-interface {
        -webkit-user-select: none; /* Safari */
        -moz-user-select: none;    /* Firefox */
        -ms-user-select: none;     /* IE 10+ */
        user-select: none;         /* Standard */
    }
</style>

{{-- Antarmuka CBT --}}
<div id="exam-interface" class="hidden min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(45,212,191,0.16),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(251,146,60,0.12),_transparent_24%),linear-gradient(180deg,_#f4f7fb_0%,_#edf3f8_48%,_#e6edf5_100%)]"
    style="min-height:100vh;background:linear-gradient(180deg,#f4f7fb 0%,#e6edf5 100%);">
    <header class="hidden">
        <div class="mx-auto flex max-w-[1600px] flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <div class="min-w-0 flex-1">
                <p class="text-xs font-medium uppercase tracking-wider text-indigo-400">AegisExam CBT — Simulasi</p>
                <h1 class="truncate text-lg font-bold text-slate-900 sm:text-2xl">{{ $exam->title }}</h1>
            </div>
            <div class="flex flex-wrap items-center gap-4 sm:gap-6">
                <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-teal-600 text-white">
                        <i class="fa-solid fa-user-graduate"></i>
                    </span>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Peserta</p>
                        <span class="font-semibold text-slate-800">{{ $studentName }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-2xl border border-orange-200 bg-orange-50 px-4 py-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-orange-500 text-white">
                        <i class="fa-solid fa-clock"></i>
                    </span>
                    <div class="text-right">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-orange-400">Sisa Waktu</p>
                        <p id="countdown-timer-hidden" class="font-mono text-lg font-bold tabular-nums text-orange-700">{{ gmdate('H:i:s', $examDuration) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto flex w-full max-w-[1500px] flex-1 flex-col gap-4 p-4 lg:h-screen lg:flex-row lg:gap-5 lg:p-5">
        <section class="flex min-h-0 flex-1 flex-col rounded-[28px] border border-white/70 bg-white/92 shadow-[0_18px_60px_rgba(15,23,42,0.08)]">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium uppercase tracking-wider text-indigo-400">AegisExam CBT - Simulasi</p>
                        <h1 class="truncate text-lg font-bold text-slate-900 sm:text-xl">{{ $exam->title }}</h1>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-teal-600 text-white">
                                <i class="fa-solid fa-user-graduate"></i>
                            </span>
                            <div>
                                <p class="text-[9px] font-semibold uppercase tracking-[0.16em] text-slate-400">Peserta</p>
                                <span class="font-semibold text-slate-800">{{ $studentName }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 rounded-2xl border border-orange-200 bg-orange-50 px-3 py-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-orange-500 text-white">
                                <i class="fa-solid fa-clock"></i>
                            </span>
                            <div class="text-right">
                                <p class="text-[9px] font-semibold uppercase tracking-[0.16em] text-orange-400">Sisa Waktu</p>
                                <p id="countdown-timer" class="font-mono text-sm font-bold tabular-nums text-orange-700">{{ gmdate('H:i:s', $examDuration) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-2 rounded-2xl bg-teal-50 px-4 py-2 text-sm font-semibold text-teal-700">
                            <i class="fa-solid fa-file-lines"></i>
                            Soal <span id="current-question-label">1</span>
                        </span>
                        <span class="rounded-2xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700">
                            Pelanggaran:
                            <span id="violation-count" class="font-bold">{{ $violationCount }}</span>/{{ $exam->max_violation }}
                        </span>
                    </div>
                    <span id="question-status-badge" class="hidden rounded-2xl bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-700">
                        <i class="fa-solid fa-flag mr-1"></i>Ragu-ragu
                    </span>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-5 py-6 sm:px-6">
                <div class="rounded-[28px] bg-slate-50 p-5 sm:p-6">
                <p id="question-text" class="text-base leading-relaxed text-slate-800 sm:text-xl"></p>
                <fieldset id="options-container" class="mt-6 space-y-3">
                    <legend class="sr-only">Pilihan jawaban</legend>
                </fieldset>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-5 py-5 sm:px-6">
                <button type="button" id="btn-prev"
                    class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40">
                    <i class="fa-solid fa-chevron-left"></i>
                    Sebelumnya
                </button>
                <div class="flex flex-wrap items-center gap-3">
                <button type="button" id="btn-doubt"
                    class="inline-flex items-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm font-semibold text-amber-700 shadow-sm transition hover:bg-amber-100">
                    <i class="fa-solid fa-flag"></i>
                    Ragu-Ragu
                </button>
                <button type="button" id="btn-next"
                    class="inline-flex items-center gap-2 rounded-2xl bg-[linear-gradient(135deg,_#0f766e_0%,_#155e75_100%)] px-6 py-3 text-sm font-semibold text-white shadow-[0_18px_40px_rgba(15,118,110,0.22)] transition hover:opacity-95">
                    <i id="btn-next-icon" class="fa-solid fa-chevron-right"></i>
                    <span id="btn-next-label">Selanjutnya</span>
                </button>
                </div>
            </div>
        </section>

        <aside class="w-full shrink-0 lg:w-[300px] xl:w-[340px]">
            <div class="sticky top-5 max-h-[calc(100vh-2.5rem)] overflow-y-auto rounded-[28px] border border-white/70 bg-white/92 p-4 shadow-[0_18px_60px_rgba(15,23,42,0.08)] sm:p-5">
                <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <i class="fa-solid fa-table-cells text-teal-600"></i>
                    Navigasi Soal
                </h2>
                <div class="mb-4 flex flex-wrap gap-x-3 gap-y-2 text-[11px] text-slate-500">
                    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded border border-slate-300 bg-slate-100"></span> Belum</span>
                    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-emerald-600"></span> Terjawab</span>
                    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-amber-500"></span> Ragu-ragu</span>
                    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded ring-2 ring-teal-500 bg-teal-500/20"></span> Aktif</span>
                </div>
                <div id="question-grid" class="grid grid-cols-5 gap-2 sm:grid-cols-8 lg:grid-cols-5 xl:grid-cols-6"></div>
                <p class="mt-4 text-center text-xs text-slate-500">Total: {{ count($questionsPayload) }} soal</p>
                <div class="my-5 h-px bg-slate-200"></div>
                <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <i class="fa-solid fa-camera-retro text-teal-600"></i>
                    Kamera & Deteksi Wajah
                </h2>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                    <video id="webcam-video" class="aspect-video w-full bg-slate-200 object-cover" autoplay muted playsinline></video>
                </div>
                <div class="mt-3 grid gap-2 text-xs text-slate-700">
                    <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <span>Status webcam</span>
                        <span id="webcam-status" class="rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-700">Menunggu</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <span>Wajah terdeteksi</span>
                        <span id="webcam-face-count" class="font-semibold text-slate-900">0</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <span>Deteksi ganda</span>
                        <span id="webcam-multi-face" class="font-semibold text-slate-900">Tidak</span>
                    </div>
                    <div id="webcam-alert" class="hidden rounded-2xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                        🔴 Tidak ada wajah lebih dari 10 detik.
                    </div>
                </div>
                <p class="mt-3 text-[11px] leading-relaxed text-slate-500">
                    Pastikan kamera aktif dan wajah terlihat jelas.
                </p>
            </div>
        </aside>
    </main>
</div>

{{-- Modal soal belum terisi --}}
<div id="unanswered-modal" class="fixed inset-0 z-[9998] hidden items-center justify-center px-4" role="alertdialog" aria-modal="true">
    <div class="absolute inset-0 bg-slate-900/30 backdrop-blur-sm"></div>
    <div id="unanswered-panel" class="relative w-full max-w-md scale-95 rounded-[28px] border border-white/80 bg-white p-6 text-center opacity-0 shadow-[0_24px_80px_rgba(15,23,42,0.20)] transition-all duration-200">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-3xl bg-amber-50 text-amber-700 ring-4 ring-amber-100">
            <i class="fa-solid fa-triangle-exclamation text-3xl"></i>
        </div>
        <h2 class="text-xl font-bold text-slate-900">Soal Belum Terisi</h2>
        <p class="mt-3 text-sm leading-relaxed text-slate-600">
            Masih ada <span id="unanswered-count" class="font-bold text-amber-700">0</span> soal yang belum dijawab.
        </p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <button type="button" id="btn-review-unanswered"
                class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                Periksa Lagi
            </button>
            <button type="button" id="btn-continue-finish"
                class="rounded-2xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white shadow-[0_14px_30px_rgba(245,158,11,0.22)] transition hover:bg-amber-400">
                Tetap Selesaikan
            </button>
        </div>
    </div>
</div>

{{-- Modal konfirmasi selesai ujian --}}
<div id="finish-confirm-modal" class="fixed inset-0 z-[9998] hidden items-center justify-center px-4" role="alertdialog" aria-modal="true">
    <div class="absolute inset-0 bg-slate-900/30 backdrop-blur-sm"></div>
    <div id="finish-confirm-panel" class="relative w-full max-w-md scale-95 rounded-[28px] border border-white/80 bg-white p-6 text-center opacity-0 shadow-[0_24px_80px_rgba(15,23,42,0.20)] transition-all duration-200">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-3xl bg-teal-50 text-teal-700 ring-4 ring-teal-100">
            <i class="fa-solid fa-circle-check text-3xl"></i>
        </div>
        <h2 class="text-xl font-bold text-slate-900">Selesaikan Ujian?</h2>
        <p class="mt-3 text-sm leading-relaxed text-slate-600">
            Apakah Anda yakin ingin menyelesaikan ujian sekarang?
        </p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <button type="button" id="btn-cancel-finish"
                class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                Batal
            </button>
            <button type="button" id="btn-confirm-finish"
                class="rounded-2xl bg-[linear-gradient(135deg,_#0f766e_0%,_#155e75_100%)] px-5 py-2.5 text-sm font-semibold text-white shadow-[0_14px_30px_rgba(15,118,110,0.22)] transition hover:opacity-95">
                Ya, Selesaikan
            </button>
        </div>
    </div>
</div>

{{-- Modal peringatan pelanggaran --}}
<div id="violation-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center px-4" role="alertdialog" aria-modal="true">
    <div class="absolute inset-0 bg-transparent" style="backdrop-filter: none !important; -webkit-backdrop-filter: none !important;"></div>
    <div id="violation-modal-panel" class="relative w-full max-w-md scale-95 rounded-2xl border-2 border-red-500 bg-red-950 p-6 text-center opacity-0 shadow-2xl shadow-red-900/50 transition-all duration-200">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-600/30 ring-4 ring-red-500/40">
            <i class="fa-solid fa-triangle-exclamation text-3xl text-red-400"></i>
        </div>
        <h2 class="text-xl font-bold text-red-300">Peringatan!</h2>
        <p class="mt-3 text-sm leading-relaxed text-red-100/90">
            Peringatan! Anda terdeteksi keluar dari halaman ujian!
        </p>
        <button type="button" id="btn-close-violation-modal"
            class="mt-6 rounded-lg bg-red-600 px-6 py-2 text-sm font-semibold text-white hover:bg-red-500">
            Saya Mengerti
        </button>
    </div>
</div>

    <div id="blocked-warning-modal" class="fixed inset-0 z-[10000] hidden items-center justify-center px-4" role="alertdialog" aria-modal="true">
        <div class="absolute inset-0 bg-transparent" style="backdrop-filter: none !important; -webkit-backdrop-filter: none !important;"></div>
        <div id="blocked-warning-modal-panel" class="relative w-full max-w-md scale-95 rounded-2xl border-2 border-amber-500 bg-amber-950 p-6 text-center opacity-0 shadow-2xl shadow-amber-900/50 transition-all duration-200">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-600/30 ring-4 ring-amber-500/40">
                <i class="fa-solid fa-shield-halved text-3xl text-amber-400"></i>
            </div>
            <h2 class="text-xl font-bold text-amber-300">Peringatan Tingkat Tinggi</h2>
            <p class="mt-3 text-sm leading-relaxed text-amber-100/90">
                Anda telah lebih dari 3 kali terdeteksi tanpa wajah lebih dari 10 detik atau dengan lebih dari satu wajah. Jika terus terjadi, akses ujian dapat diblokir.
            </p>
            <button type="button" id="btn-close-blocked-warning-modal"
                class="mt-6 rounded-lg bg-amber-600 px-6 py-2 text-sm font-semibold text-white hover:bg-amber-500">
                Saya Mengerti
            </button>
        </div>
    </div>

    <div id="blocked-fullscreen-overlay" class="fixed inset-0 z-[20000] items-center justify-center bg-[#f4f7fb] px-4" style="display:none;background:#f4f7fb;" role="alertdialog" aria-modal="true">
        <div class="w-full max-w-md rounded-2xl border-2 border-rose-500 bg-white p-8 text-center shadow-2xl" style="background:#fff;max-width:28rem;width:100%;padding:2rem;border-radius:1rem;">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                <i class="fa-solid fa-shield-halved text-3xl"></i>
            </div>
            <h2 class="text-xl font-bold text-rose-700">Akses Ujian Diblokir</h2>
            <p class="mt-3 text-sm text-slate-600">Mengalihkan ke halaman informasi blokir...</p>
            <p class="mt-2 text-xs text-slate-500">Jangan tutup aplikasi.</p>
        </div>
    </div>

    <div id="face-warning-modal" class="fixed inset-0 z-[10001] hidden items-center justify-center px-4" role="alertdialog" aria-modal="true">
        <div class="absolute inset-0 bg-transparent" style="backdrop-filter: none !important; -webkit-backdrop-filter: none !important;"></div>
        <div id="face-warning-modal-panel" class="relative w-full max-w-md scale-95 rounded-2xl border-2 border-red-500 bg-red-950 p-6 text-center opacity-0 shadow-2xl shadow-red-900/50 transition-all duration-200">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-600/30 ring-4 ring-red-500/40">
                <i class="fa-solid fa-triangle-exclamation text-3xl text-red-400"></i>
            </div>
            <h2 id="face-warning-title" class="text-xl font-bold text-red-300">Peringatan!</h2>
            <p id="face-warning-message" class="mt-3 text-sm leading-relaxed text-red-100/90">
                Deteksi wajah bermasalah.
            </p>
            <button type="button" id="btn-close-face-warning-modal"
                class="mt-6 rounded-lg bg-red-600 px-6 py-2 text-sm font-semibold text-white hover:bg-red-500">
                Saya Mengerti
            </button>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const questions = @json($questionsPayload);
        const configuredDuration = {{ (int) $examDuration }};
        const maxViolation = {{ $exam->max_violation }};
        const violationUrl = '/api/exam/violation';
        const blockedUrl = '/blocked';
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
    axios.defaults.headers.common['Accept'] = 'application/json';

    let examActive = false;
    let blurGracePeriod = false;
    let violationCooldown = false;
    let currentIndex = 0;
    let countdownSeconds = configuredDuration;
    let countdownInterval = null;
    let examFinishing = false;

    const answers = {};
    const doubtful = new Set();

    const preExamScreen = document.getElementById('pre-exam-screen');
    const examInterface = document.getElementById('exam-interface');
    const btnStart = document.getElementById('btn-start-exam');
    const btnPrev = document.getElementById('btn-prev');
    const btnDoubt = document.getElementById('btn-doubt');
    const btnNext = document.getElementById('btn-next');
    const btnNextLabel = document.getElementById('btn-next-label');
    const btnNextIcon = document.getElementById('btn-next-icon');
    const questionText = document.getElementById('question-text');
    const optionsContainer = document.getElementById('options-container');
    const questionGrid = document.getElementById('question-grid');
    const currentQuestionLabel = document.getElementById('current-question-label');
    const questionStatusBadge = document.getElementById('question-status-badge');
    const countdownTimer = document.getElementById('countdown-timer');
    const violationModal = document.getElementById('violation-modal');
    const violationModalPanel = document.getElementById('violation-modal-panel');
    const btnCloseViolationModal = document.getElementById('btn-close-violation-modal');
    const unansweredModal = document.getElementById('unanswered-modal');
    const unansweredPanel = document.getElementById('unanswered-panel');
    const unansweredCount = document.getElementById('unanswered-count');
    const btnReviewUnanswered = document.getElementById('btn-review-unanswered');
    const btnContinueFinish = document.getElementById('btn-continue-finish');
    const finishConfirmModal = document.getElementById('finish-confirm-modal');
    const finishConfirmPanel = document.getElementById('finish-confirm-panel');
    const btnCancelFinish = document.getElementById('btn-cancel-finish');
    const btnConfirmFinish = document.getElementById('btn-confirm-finish');
    const blockedWarningModal = document.getElementById('blocked-warning-modal');
    const blockedWarningModalPanel = document.getElementById('blocked-warning-modal-panel');
    const btnCloseBlockedWarningModal = document.getElementById('btn-close-blocked-warning-modal');
    const faceWarningModal = document.getElementById('face-warning-modal');
    const faceWarningModalPanel = document.getElementById('face-warning-modal-panel');
    const faceWarningTitle = document.getElementById('face-warning-title');
    const faceWarningMessage = document.getElementById('face-warning-message');
    const btnCloseFaceWarningModal = document.getElementById('btn-close-face-warning-modal');
    const violationCountEl = document.getElementById('violation-count');
    const violationCountPreEl = document.getElementById('violation-count-pre');

    const webcamVideo = document.getElementById('webcam-video');
    const webcamStatus = document.getElementById('webcam-status');
    const webcamFaceCount = document.getElementById('webcam-face-count');
    const webcamMultiFace = document.getElementById('webcam-multi-face');
    const webcamAlert = document.getElementById('webcam-alert');

    let webcamStream = null;
    let faceDetector = null;
    let tfFaceModel = null;
    let lastFaceSeenAt = performance.now();
    let noFaceThresholdMs = 10000;
    let webcamInitialized = false;
    let warningCount = 0;
    let noFaceWarningCount = 0;
    let noFaceEventActive = false;
    let multiFaceEventActive = false;
    const studentName = @json(session('student_name'));
    const studentNim = @json(session('student_nim'));
    let snapshotIntervalId = null;

    function pad(n) {
        return String(n).padStart(2, '0');
    }

    function updateCountdown() {
        if (countdownSeconds <= 0) {
            countdownTimer.textContent = '00:00:00';
            clearInterval(countdownInterval);
            finishExam();
            return;
        }
        const h = Math.floor(countdownSeconds / 3600);
        const m = Math.floor((countdownSeconds % 3600) / 60);
        const s = countdownSeconds % 60;
        countdownTimer.textContent = `${pad(h)}:${pad(m)}:${pad(s)}`;
        countdownSeconds--;
    }

    function startCountdown() {
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
        updateCountdown();
        countdownInterval = setInterval(updateCountdown, 1000);
    }

    function updateViolationDisplay(count) {
        if (violationCountEl) violationCountEl.textContent = count;
        if (violationCountPreEl) violationCountPreEl.textContent = count;
    }

    function updateWebcamStatus(status, isWarning = false) {
        if (!webcamStatus) return;
        webcamStatus.textContent = status;
        webcamStatus.classList.toggle('bg-emerald-100', !isWarning);
        webcamStatus.classList.toggle('text-emerald-700', !isWarning);
        webcamStatus.classList.toggle('bg-red-100', isWarning);
        webcamStatus.classList.toggle('text-red-700', isWarning);
    }

    function setWebcamAlert(active) {
        if (!webcamAlert) return;
        webcamAlert.classList.toggle('hidden', !active);
    }

    function updateWebcamFaceState(faceCount) {
        if (!webcamFaceCount || !webcamMultiFace) return;
        webcamFaceCount.textContent = faceCount;
        webcamMultiFace.textContent = faceCount > 1 ? 'Ya' : 'Tidak';
        webcamMultiFace.classList.toggle('text-red-700', faceCount > 1);
        webcamMultiFace.classList.toggle('text-slate-900', faceCount <= 1);
    }

    function loadScriptOnce(src, globalName, timeoutMs = 8000) {
        return new Promise((resolve, reject) => {
            if (globalName && window[globalName]) {
                resolve(window[globalName]);
                return;
            }

            const existingScript = document.querySelector(`script[data-dynamic-src="${src}"]`);
            if (existingScript) {
                existingScript.addEventListener('load', () => resolve(globalName ? window[globalName] : true), { once: true });
                existingScript.addEventListener('error', reject, { once: true });
                return;
            }

            const script = document.createElement('script');
            const timer = setTimeout(() => {
                script.remove();
                reject(new Error(`Timeout memuat ${src}`));
            }, timeoutMs);

            script.src = src;
            script.async = true;
            script.dataset.dynamicSrc = src;
            script.onload = () => {
                clearTimeout(timer);
                resolve(globalName ? window[globalName] : true);
            };
            script.onerror = () => {
                clearTimeout(timer);
                reject(new Error(`Gagal memuat ${src}`));
            };

            document.head.appendChild(script);
        });
    }

    async function loadBlazefaceFallback() {
        if (!window.tf) {
            await loadScriptOnce('https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.13.0/dist/tf.min.js', 'tf');
        }

        if (!window.blazeface) {
            await loadScriptOnce('https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface/dist/blazeface.min.js', 'blazeface');
        }

        return window.blazeface;
    }

    function showFaceWarning(type, severity = 'normal') {
        if (!faceWarningModal || !faceWarningModalPanel || !faceWarningTitle || !faceWarningMessage) return;

        if (type === 'multi-face') {
            if (severity === 'escalated') {
                faceWarningTitle.textContent = 'Peringatan Deteksi Lebih dari 2 Wajah';
                faceWarningMessage.textContent = 'Anda telah beberapa kali terdeteksi dengan lebih dari satu wajah. Jika terus terjadi, akses ujian dapat diblokir.';
            } else {
                faceWarningTitle.textContent = 'Terdeteksi 2 Wajah';
                faceWarningMessage.textContent = 'Terdeteksi lebih dari satu wajah di kamera. Pastikan hanya satu orang berada di depan kamera.';
            }
        } else {
            faceWarningTitle.textContent = 'Peringatan Tidak Ada Wajah';
            faceWarningMessage.textContent = 'Wajah tidak ada lebih dari 10 detik. Pastikan wajah terlihat di kamera.';
        }

        faceWarningModal.classList.remove('hidden');
        faceWarningModal.classList.add('flex');
        faceWarningModal.style.display = '';
        faceWarningModal.style.opacity = '';
        faceWarningModal.style.zIndex = '';
        faceWarningModalPanel.style.display = '';
        requestAnimationFrame(() => {
            faceWarningModalPanel.classList.remove('scale-95', 'opacity-0');
            faceWarningModalPanel.classList.add('scale-100', 'opacity-100');
        });
    }

    function hideFaceWarning() {
        if (!faceWarningModal || !faceWarningModalPanel) return;
        faceWarningModalPanel.classList.add('scale-95', 'opacity-0');
        faceWarningModalPanel.classList.remove('scale-100', 'opacity-100');
        setTimeout(() => {
            faceWarningModal.classList.add('hidden');
            faceWarningModal.classList.remove('flex');
            faceWarningModal.style.display = '';
            faceWarningModal.style.opacity = '';
            faceWarningModal.style.zIndex = '';
            faceWarningModalPanel.style.display = '';
        }, 200);
    }

    function showBlockedWarningModal() {
        if (!blockedWarningModal || !blockedWarningModalPanel) return;
        hideFaceWarning();
        blockedWarningModal.classList.remove('hidden');
        blockedWarningModal.classList.add('flex');
        requestAnimationFrame(() => {
            blockedWarningModalPanel.classList.remove('scale-95', 'opacity-0');
            blockedWarningModalPanel.classList.add('scale-100', 'opacity-100');
        });
    }

    function hideBlockedWarningModal() {
        if (!blockedWarningModal || !blockedWarningModalPanel) return;
        blockedWarningModalPanel.classList.add('scale-95', 'opacity-0');
        blockedWarningModalPanel.classList.remove('scale-100', 'opacity-100');
        setTimeout(() => {
            blockedWarningModal.classList.add('hidden');
            blockedWarningModal.classList.remove('flex');
        }, 200);
    }

    function maybeShowRepeatedWarning(type) {
        if (type === 'multi-face' && warningCount >= 2) {
            warningCount += 1;
            showFaceWarning('multi-face', 'escalated');
            reportViolation({
                showModal: false,
                violationType: 'multi-face',
                violationMessage: 'Terdeteksi lebih dari satu wajah pada kamera mahasiswa.',
            });
            return;
        }

        warningCount += 1;
        if (warningCount >= 3) {
            showBlockedWarningModal();
        }
    }

    async function initWebcamDetection() {
        console.log('initWebcamDetection');
        if (webcamInitialized) return;
        webcamInitialized = true;

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            updateWebcamStatus('Browser tidak mendukung webcam', true);
            return;
        }

        try {
            webcamStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
            webcamVideo.srcObject = webcamStream;
            await webcamVideo.play();
            lastFaceSeenAt = performance.now();
            console.log('webcam acquired');
        } catch (error) {
            console.error('Gagal mengakses webcam:', error);
            let message = 'Akses webcam ditolak';
            if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                message = 'Kamera tidak ditemukan di laptop ini';
            } else if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                message = 'Izinkan kamera untuk AegisExam di Pengaturan Windows';
            } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
                message = 'Kamera sedang dipakai aplikasi lain (tutup Zoom/Teams)';
            } else if (!window.isSecureContext) {
                message = 'Koneksi tidak aman untuk kamera — simpan ulang IP/Cloud lalu restart app';
            }
            updateWebcamStatus(message, true);
            return;
        }

        if ('FaceDetector' in window) {
            try {
                faceDetector = new FaceDetector({ fastMode: true, maxDetectedFaces: 5 });
                updateWebcamStatus('Webcam aktif', false);
                detectFaces();
                return;
            } catch (error) {
                console.warn('FaceDetector gagal diinisialisasi, mencoba fallback:', error);
            }
        }

        if (!tfFaceModel) {
            try {
                updateWebcamStatus('Memuat deteksi wajah...', false);
                const blazefaceModel = await loadBlazefaceFallback();
                tfFaceModel = await blazefaceModel.load();
                updateWebcamStatus('Webcam aktif (fallback)', false);
                detectFaces();
                return;
            } catch (error) {
                console.error('Gagal memuat model Blazeface:', error);
            }
        }

        updateWebcamStatus('Deteksi wajah tidak didukung', true);
    }

    async function detectFaces() {
        if (!webcamVideo || (!faceDetector && !tfFaceModel)) return;
        if (webcamVideo.readyState < 2) {
            requestAnimationFrame(detectFaces);
            return;
        }

        try {
            let detections = [];

            if (faceDetector) {
                detections = await faceDetector.detect(webcamVideo);
            } else if (tfFaceModel) {
                const predictions = await tfFaceModel.estimateFaces(webcamVideo, false);
                detections = Array.isArray(predictions) ? predictions : [];
            }

            const faceCount = detections.length;
            updateWebcamFaceState(faceCount);

            if (faceCount > 0) {
                lastFaceSeenAt = performance.now();
                noFaceEventActive = false;
            }

            if (faceCount === 1) {
                multiFaceEventActive = false;
                updateWebcamStatus('Wajah terdeteksi', false);
                setWebcamAlert(false);
            }

            if (faceCount === 0) {
                updateWebcamStatus('Tidak ada wajah', true);
                if (performance.now() - lastFaceSeenAt >= noFaceThresholdMs) {
                    setWebcamAlert(true);
                    if (!noFaceEventActive) {
                        noFaceEventActive = true;
                        noFaceWarningCount += 1;
                        showFaceWarning('no-face');

                        if (noFaceWarningCount === 3) {
                            reportViolation({
                                showModal: true,
                                violationType: 'no-face',
                                violationMessage: 'Wajah mahasiswa tidak terdeteksi lebih dari 10 detik.',
                            });
                        } else if (noFaceWarningCount > 3) {
                            showBlockedWarningModal();
                            reportViolation({
                                showModal: false,
                                violationType: 'no-face',
                                violationMessage: 'Wajah mahasiswa tidak terdeteksi berulang kali lebih dari 10 detik.',
                            });
                        }
                    }
                }
            }

            if (faceCount > 1) {
                updateWebcamStatus('Terdeteksi lebih dari 1 wajah', true);
                if (!multiFaceEventActive) {
                    multiFaceEventActive = true;
                    showFaceWarning('multi-face');
                    if (warningCount >= 3) {
                        reportViolation({
                            showModal: false,
                            violationType: 'multi-face',
                            violationMessage: 'Terdeteksi lebih dari satu wajah pada kamera mahasiswa.',
                        });
                    } else {
                        maybeShowRepeatedWarning('multi-face');
                    }
                }
            }
        } catch (error) {
            console.error('Gagal mendeteksi wajah:', error);
            updateWebcamStatus('Kesalahan deteksi wajah', true);
            hideFaceWarning();
        }

        requestAnimationFrame(detectFaces);
    }

    function stopWebcam() {
        if (!webcamStream) return;
        webcamStream.getTracks().forEach(track => track.stop());
        webcamStream = null;
        webcamVideo.srcObject = null;
    }

    function getGridCellClass(index) {
        const q = questions[index];
        const base = 'flex h-10 w-full items-center justify-center rounded-2xl text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-teal-400';
        if (index === currentIndex) {
            return base + ' ring-2 ring-teal-500 bg-teal-600 text-white shadow-sm';
        }
        if (doubtful.has(q.id)) {
            return base + ' bg-amber-400 text-amber-950 hover:bg-amber-300';
        }
        if (answers[q.id]) {
            return base + ' bg-emerald-600 text-white hover:bg-emerald-500';
        }
        return base + ' border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100';
    }

    function renderGrid() {
        questionGrid.innerHTML = '';
        questions.forEach((q, index) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = getGridCellClass(index);
            btn.textContent = q.number;
            btn.addEventListener('click', () => goToQuestion(index));
            questionGrid.appendChild(btn);
        });
    }

    function renderQuestion() {
        const q = questions[currentIndex];
        if (!q) {
            currentQuestionLabel.textContent = '0';
            questionText.textContent = 'Belum ada pertanyaan yang tersedia.';
            optionsContainer.innerHTML = '<div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-500">Dosen belum menambahkan soal untuk ujian ini.</div>';
            btnPrev.disabled = true;
            btnDoubt.disabled = true;
            btnNext.disabled = true;
            questionStatusBadge.classList.add('hidden');
            return;
        }

        currentQuestionLabel.textContent = q.number;
        questionText.textContent = q.text;

        const selected = answers[q.id] || '';
        const optionEntries = Array.isArray(q.options)
            ? q.options.map((label, index) => {
                const cleanedLabel = String(label).trim();
                const match = cleanedLabel.match(/^([A-Za-z])[\.\:\)]\s*(.+)$/);
                return match
                    ? [match[1].toUpperCase(), match[2]]
                    : [String.fromCharCode(65 + index), cleanedLabel];
            })
            : Object.entries(q.options || {});
        optionsContainer.innerHTML = '';

        if (optionEntries.length === 0) {
            optionsContainer.innerHTML = `
                <div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-500">
                    Soal ini belum memiliki pilihan jawaban.
                </div>
            `;
        }

        optionEntries.forEach(([letter, label]) => {
            const id = `opt-${q.id}-${letter}`;
            const labelEl = document.createElement('label');
            labelEl.className = 'group flex cursor-pointer items-start gap-3 rounded-[24px] border border-slate-200 bg-white p-4 transition hover:border-teal-300 hover:bg-slate-50 has-[:checked]:border-teal-500 has-[:checked]:bg-teal-50 has-[:checked]:ring-1 has-[:checked]:ring-teal-300';
            labelEl.innerHTML = `
                <input type="radio" name="answer_${q.id}" id="${id}" value="${letter}" class="mt-1 h-4 w-4 border-slate-300 text-teal-600 focus:ring-teal-500" ${selected === letter ? 'checked' : ''}>
                <span class="flex min-w-0 flex-1 gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-sm font-bold text-teal-700 group-has-[:checked]:bg-teal-600 group-has-[:checked]:text-white">${letter}</span>
                    <span class="pt-1 text-sm leading-relaxed text-slate-700 sm:text-base">${label}</span>
                </span>
            `;
            labelEl.querySelector('input').addEventListener('change', () => {
                answers[q.id] = letter;
                doubtful.delete(q.id);
                updateDoubtBadge();
                renderGrid();
            });
            optionsContainer.appendChild(labelEl);
        });

        btnPrev.disabled = currentIndex === 0;
        btnDoubt.disabled = false;
        btnNext.disabled = false;
        updateNavigationButtons();
        updateDoubtBadge();
        renderGrid();
    }

    function updateNavigationButtons() {
        const isLastQuestion = currentIndex === questions.length - 1;

        if (!btnNext || !btnNextLabel || !btnNextIcon) return;

        btnNextLabel.textContent = isLastQuestion ? 'Selesai' : 'Selanjutnya';
        btnNextIcon.className = isLastQuestion ? 'fa-solid fa-check' : 'fa-solid fa-chevron-right';
        btnNext.classList.toggle('bg-emerald-600', isLastQuestion);
        btnNext.classList.toggle('hover:bg-emerald-500', isLastQuestion);
        btnNext.classList.toggle('bg-indigo-600', !isLastQuestion);
        btnNext.classList.toggle('hover:bg-indigo-500', !isLastQuestion);
    }

    function getUnansweredIndexes() {
        return questions
            .map((q, index) => answers[q.id] ? null : index)
            .filter(index => index !== null);
    }

    function showUnansweredModal(unansweredIndexes) {
        if (!unansweredModal || !unansweredPanel) return;
        if (unansweredCount) unansweredCount.textContent = unansweredIndexes.length;
        unansweredModal.dataset.firstUnansweredIndex = String(unansweredIndexes[0] ?? 0);
        unansweredModal.classList.remove('hidden');
        unansweredModal.classList.add('flex');
        requestAnimationFrame(() => {
            unansweredPanel.classList.remove('scale-95', 'opacity-0');
            unansweredPanel.classList.add('scale-100', 'opacity-100');
        });
    }

    function hideUnansweredModal() {
        if (!unansweredModal || !unansweredPanel) return;
        unansweredPanel.classList.add('scale-95', 'opacity-0');
        unansweredPanel.classList.remove('scale-100', 'opacity-100');
        setTimeout(() => {
            unansweredModal.classList.add('hidden');
            unansweredModal.classList.remove('flex');
        }, 200);
    }

    function showFinishConfirm() {
        if (!finishConfirmModal || !finishConfirmPanel) return;
        finishConfirmModal.classList.remove('hidden');
        finishConfirmModal.classList.add('flex');
        requestAnimationFrame(() => {
            finishConfirmPanel.classList.remove('scale-95', 'opacity-0');
            finishConfirmPanel.classList.add('scale-100', 'opacity-100');
        });
    }

    function hideFinishConfirm() {
        if (!finishConfirmModal || !finishConfirmPanel) return;
        finishConfirmPanel.classList.add('scale-95', 'opacity-0');
        finishConfirmPanel.classList.remove('scale-100', 'opacity-100');
        setTimeout(() => {
            finishConfirmModal.classList.add('hidden');
            finishConfirmModal.classList.remove('flex');
        }, 200);
    }

    async function finishExam() {
        if (examFinishing) return;
        examFinishing = true;
        examActive = false;

        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
        if (snapshotIntervalId) {
            clearInterval(snapshotIntervalId);
            snapshotIntervalId = null;
        }

        stopWebcam();

        await exitFullscreenMode();

        try {
            const { data } = await axios.post('/api/exam/submit', {
                student_name: studentName,
                student_nim: studentNim,
                answers,
            });

            window.location.href = data.redirect || '/exam/result';
        } catch (error) {
            console.error('Gagal menyimpan hasil ujian:', error);
            window.location.href = '/exam/result';
        }
    }

    function updateDoubtBadge() {
        const q = questions[currentIndex];
        if (doubtful.has(q.id)) {
            questionStatusBadge.classList.remove('hidden');
        } else {
            questionStatusBadge.classList.add('hidden');
        }
    }

    function goToQuestion(index) {
        if (index < 0 || index >= questions.length) return;
        currentIndex = index;
        renderQuestion();
    }

    function showExamUi() {
        window.aegisExamActive = true;
        preExamScreen.classList.add('hidden');
        examInterface.classList.remove('hidden');
        examInterface.classList.add('flex');
        renderGrid();
        renderQuestion();
        startCountdown();
    }

    function showViolationModal() {
        hideFaceWarning();
        violationModal.classList.remove('hidden');
        violationModal.classList.add('flex');
        requestAnimationFrame(() => {
            violationModalPanel.classList.remove('scale-95', 'opacity-0');
            violationModalPanel.classList.add('scale-100', 'opacity-100');
        });
    }

    function hideViolationModal() {
        violationModalPanel.classList.add('scale-95', 'opacity-0');
        violationModalPanel.classList.remove('scale-100', 'opacity-100');
        setTimeout(() => {
            violationModal.classList.add('hidden');
            violationModal.classList.remove('flex');
        }, 200);
    }

    async function requestFullscreen() {
        window.aegisExamActive = true;

        try {
            if (window.aegisDesktop?.setFullScreen) {
                window.aegisDesktop.setFullScreen(true);
                await new Promise((resolve) => setTimeout(resolve, 250));
                window.aegisDesktop.setFullScreen(true);
                return;
            }

            const el = document.documentElement;
            if (el.requestFullscreen) {
                await el.requestFullscreen({ navigationUI: 'hide' });
            } else if (el.webkitRequestFullscreen) {
                await el.webkitRequestFullscreen();
            }
        } catch (e) {
            console.warn('Fullscreen tidak tersedia:', e);
        }
    }

    async function exitFullscreenMode() {
        window.aegisExamActive = false;

        try {
            if (window.aegisDesktop?.setFullScreen) {
                window.aegisDesktop.setFullScreen(false);
                return;
            }

            if (document.fullscreenElement && document.exitFullscreen) {
                await document.exitFullscreen();
            } else if (document.webkitFullscreenElement && document.webkitExitFullscreen) {
                await document.webkitExitFullscreen();
            }
        } catch (error) {
            console.warn('Gagal keluar fullscreen:', error);
        }
    }

    function buildBlockedPageHtml(violationCountValue) {
        const examTitle = @json($exam->title);
        const maxViolationValue = maxViolation;
        const countText = violationCountValue ?? '?';

        return `<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Akses Ujian Diblokir</title>
<style>
*{box-sizing:border-box}html,body{margin:0;min-height:100%;background:#f4f7fb;color:#0f172a;font-family:system-ui,sans-serif}
.wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;background:linear-gradient(180deg,#f4f7fb 0%,#e6edf5 100%)}
.card{width:100%;max-width:560px;background:#fff;border-radius:24px;padding:32px 28px;text-align:center;box-shadow:0 24px 80px rgba(15,23,42,.12);border:1px solid #e2e8f0}
.icon{width:88px;height:88px;margin:0 auto 20px;border-radius:20px;background:#fff1f2;color:#e11d48;font-size:40px;line-height:88px}
h1{margin:0 0 12px;font-size:28px;color:#0f172a}p{margin:0 0 12px;line-height:1.6;color:#475569;font-size:15px}
.stats{margin:20px 0;padding:14px 18px;border-radius:14px;background:#fff1f2;border:1px solid #fecdd3;color:#9f1239;font-weight:600}
.info{margin:16px 0;padding:14px 16px;border-radius:14px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;font-size:14px;text-align:left}
.status{margin:16px 0;padding:12px 14px;border-radius:14px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;font-size:14px}
.btn{display:inline-block;margin-top:16px;padding:12px 20px;border-radius:14px;background:#f8fafc;border:1px solid #cbd5e1;color:#334155;text-decoration:none;font-weight:600;font-size:14px}
</style>
</head>
<body>
<div class="wrap"><div class="card">
<div class="icon">&#128274;</div>
<h1>AKSES UJIAN DIBLOKIR</h1>
<p>Sesi ujian <strong>${examTitle}</strong> dihentikan karena pelanggaran melebihi batas.</p>
<div class="stats">Pelanggaran: ${countText} / ${maxViolationValue}</div>
<div class="info">Hubungi dosen untuk <strong>Buka Blokir</strong> di dashboard dosen.</div>
<div id="unblock-status" class="status">Memantau pembukaan blokir dari dosen...</div>
<a class="btn" href="/logout">Logout</a>
</div></div>
<script>
(function(){
document.documentElement.style.backgroundColor='#f4f7fb';
document.body.style.backgroundColor='#f4f7fb';
if(window.aegisDesktop&&window.aegisDesktop.setFullScreen){window.aegisDesktop.setFullScreen(false);}
if(document.fullscreenElement&&document.exitFullscreen){document.exitFullscreen().catch(function(){});}
var statusBox=document.getElementById('unblock-status');
function checkExamStatus(){
fetch('/api/exam/status',{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'})
.then(function(r){if(!r.ok)throw new Error('fail');return r.json();})
.then(function(data){
if(data.is_blocked===false&&data.redirect){
if(statusBox){statusBox.textContent='Blokir dibuka. Mengalihkan...';}
window.location.replace(data.redirect);
return;
}
if(statusBox&&typeof data.violation_count!=='undefined'){
statusBox.textContent='Masih diblokir. Pelanggaran: '+data.violation_count;
}})
.catch(function(){if(statusBox){statusBox.textContent='Cek status otomatis bermasalah.';}});
}
checkExamStatus();
setInterval(checkExamStatus,5000);
})();
<\/script>
</body></html>`;
    }

    async function redirectToBlockedPage() {
        examActive = false;
        stopWebcam();

        if (snapshotIntervalId) {
            clearInterval(snapshotIntervalId);
            snapshotIntervalId = null;
        }

        const currentCount = violationCountEl?.textContent || violationCountPreEl?.textContent || '';

        const overlay = document.getElementById('blocked-fullscreen-overlay');
        if (overlay) {
            overlay.style.display = 'flex';
        }

        document.documentElement.style.backgroundColor = '#f4f7fb';
        document.body.style.backgroundColor = '#f4f7fb';

        try {
            await exitFullscreenMode();
        } catch (error) {
            console.warn('Keluar fullscreen saat blokir:', error);
        }

        try {
            document.open();
            document.write(buildBlockedPageHtml(currentCount));
            document.close();
        } catch (error) {
            console.warn('Gagal render halaman blokir inline, coba redirect:', error);
            window.location.replace(blockedUrl);
        }
    }

    function captureSnapshotDataUrl() {
        if (!webcamVideo || webcamVideo.readyState < 2) return null;

        try {
            const canvas = document.createElement('canvas');
            const w = 320;
            const h = Math.round((webcamVideo.videoHeight / webcamVideo.videoWidth) * w) || 240;
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(webcamVideo, 0, 0, w, h);
            return canvas.toDataURL('image/jpeg', 0.6);
        } catch (error) {
            console.warn('Gagal mengambil snapshot pelanggaran:', error);
            return null;
        }
    }

    async function reportViolation(options = { showModal: true }) {
        if (!examActive || violationCooldown) return;
        violationCooldown = true;
        if (options.showModal) {
            showViolationModal();
        }

        try {
            const payload = {
                student_name: studentName,
                student_nim: studentNim,
                violation_type: options.violationType || 'general',
                violation_message: options.violationMessage || 'Mahasiswa terdeteksi melakukan pelanggaran ujian.',
                snapshot_image: captureSnapshotDataUrl(),
            };

            const { data } = await axios.post(violationUrl, payload);

            if (data.violation_count !== undefined) {
                updateViolationDisplay(data.violation_count);
            }

            if (data.is_blocked === true) {
                await redirectToBlockedPage();
                return;
            }
        } catch (err) {
            console.error('Gagal mencatat pelanggaran:', err);
        } finally {
            setTimeout(() => { violationCooldown = false; }, 1200);
        }
    }

    async function startExamBackendRegistration() {
        try {
            const { data } = await axios.post('/api/exam/start', {
                student_name: studentName,
                student_nim: studentNim,
            });
            return data;
        } catch (e) {
            if (e.response?.status === 403 && e.response?.data) {
                return e.response.data;
            }
            console.warn('Gagal registrasi start ke backend:', e);
            return null;
        }
    }

    async function sendSnapshot() {
        const dataUrl = captureSnapshotDataUrl();
        if (!dataUrl) return;
        try {
            await axios.post('/api/exam/snapshot', {
                student_name: studentName,
                student_nim: studentNim,
                image: dataUrl,
            });
        } catch (e) {
            console.warn('Snapshot gagal:', e);
        }
    }

    if (btnStart) {
        btnStart.addEventListener('click', async () => {
            btnStart.disabled = true;
            btnStart.classList.add('opacity-70', 'cursor-wait');

            try {
                blurGracePeriod = true;
                setTimeout(() => { blurGracePeriod = false; }, 3000);
                examActive = true;
                countdownSeconds = configuredDuration;
                await requestFullscreen();
                showExamUi();

                startExamBackendRegistration().then(async (info) => {
                    if (info && info.status === 'blocked' && info.redirect) {
                        await redirectToBlockedPage();
                        return;
                    }

                    if (info?.exam_duration_seconds) {
                        countdownSeconds = Number(info.exam_duration_seconds);
                    }
                });

                initWebcamDetection().catch((error) => {
                    console.error('Gagal memulai webcam:', error);
                    updateWebcamStatus('Webcam gagal dimulai', true);
                });

                // start snapshot upload every 5 seconds
                snapshotIntervalId = setInterval(sendSnapshot, 5000);
            } catch (error) {
                console.error('Gagal memulai ujian:', error);
                examActive = false;
                btnStart.disabled = false;
                btnStart.classList.remove('opacity-70', 'cursor-wait');
            }
        });
    }

    btnPrev.addEventListener('click', () => goToQuestion(currentIndex - 1));

    btnDoubt.addEventListener('click', () => {
        const q = questions[currentIndex];
        doubtful.add(q.id);
        updateDoubtBadge();
        renderGrid();
    });

    btnNext.addEventListener('click', async () => {
        if (currentIndex === questions.length - 1) {
            const unansweredIndexes = getUnansweredIndexes();
            if (unansweredIndexes.length > 0) {
                showUnansweredModal(unansweredIndexes);
                return;
            }
            showFinishConfirm();
            return;
        }

        if (currentIndex < questions.length - 1) {
            goToQuestion(currentIndex + 1);
        }
    });

    if (btnCloseViolationModal) {
        btnCloseViolationModal.addEventListener('click', hideViolationModal);
    }
    if (btnReviewUnanswered) {
        btnReviewUnanswered.addEventListener('click', () => {
            const firstUnansweredIndex = Number(unansweredModal?.dataset.firstUnansweredIndex || 0);
            hideUnansweredModal();
            goToQuestion(firstUnansweredIndex);
        });
    }
    if (btnContinueFinish) {
        btnContinueFinish.addEventListener('click', () => {
            hideUnansweredModal();
            setTimeout(showFinishConfirm, 220);
        });
    }
    if (unansweredModal) {
        unansweredModal.addEventListener('click', (event) => {
            if (event.target === unansweredModal) {
                hideUnansweredModal();
            }
        });
    }
    if (btnCancelFinish) {
        btnCancelFinish.addEventListener('click', hideFinishConfirm);
    }
    if (finishConfirmModal) {
        finishConfirmModal.addEventListener('click', (event) => {
            if (event.target === finishConfirmModal) {
                hideFinishConfirm();
            }
        });
    }
    if (btnConfirmFinish) {
        btnConfirmFinish.addEventListener('click', async () => {
            btnConfirmFinish.disabled = true;
            btnConfirmFinish.textContent = 'Menyelesaikan...';
            await finishExam();
        });
    }
    if (btnCloseBlockedWarningModal) {
        btnCloseBlockedWarningModal.addEventListener('click', hideBlockedWarningModal);
    }
    if (btnCloseFaceWarningModal) {
        btnCloseFaceWarningModal.addEventListener('click', hideFaceWarning);
    }

    document.addEventListener('visibilitychange', () => {
        if (!examActive || !document.hidden || blurGracePeriod) return;
        reportViolation({
            showModal: true,
            violationType: 'tab-switch',
            violationMessage: 'Mahasiswa keluar dari halaman ujian atau berpindah tab browser.',
        });
    });

    window.addEventListener('blur', () => {
        if (examActive && !blurGracePeriod) {
            reportViolation({
                showModal: true,
                violationType: 'window-blur',
                violationMessage: 'Mahasiswa berpindah fokus dari jendela ujian.',
            });
        }
    });

    // Block clipboard actions
    document.addEventListener('copy', (e) => {
        if (!examActive) return;
        e.preventDefault();
        reportViolation({
            showModal: true,
            violationType: 'clipboard-copy',
            violationMessage: 'Mahasiswa mencoba menyalin teks (Copy) dari ujian.',
        });
    });

    document.addEventListener('cut', (e) => {
        if (!examActive) return;
        e.preventDefault();
        reportViolation({
            showModal: true,
            violationType: 'clipboard-cut',
            violationMessage: 'Mahasiswa mencoba memotong teks (Cut) dari ujian.',
        });
    });

    document.addEventListener('paste', (e) => {
        if (!examActive) return;
        e.preventDefault();
        reportViolation({
            showModal: true,
            violationType: 'clipboard-paste',
            violationMessage: 'Mahasiswa mencoba menempel teks (Paste) ke kolom jawaban.',
        });
    });

    // Block dangerous keyboard shortcuts
    window.addEventListener('keydown', (e) => {
        if (!examActive) return;

        // Block F12
        if (e.key === 'F12') {
            e.preventDefault();
            reportViolation({
                showModal: true,
                violationType: 'keyboard-shortcut',
                violationMessage: 'Mahasiswa menekan tombol F12 (Membuka Developer Tools).',
            });
            return false;
        }

        // Block Ctrl/Cmd combinations
        if (e.ctrlKey || e.metaKey) {
            const key = e.key.toLowerCase();
            
            // Common forbidden actions: C (Copy), V (Paste), X (Cut), U (Source), S (Save), P (Print), R (Reload), F (Search)
            if (['c', 'v', 'x', 'u', 's', 'p', 'r', 'f'].includes(key)) {
                e.preventDefault();
                const shortcutName = `Ctrl + ${key.toUpperCase()}`;
                let actionName = 'shortcut berbahaya';
                if (key === 'c') actionName = 'menyalin teks';
                if (key === 'v') actionName = 'menempel teks';
                if (key === 'x') actionName = 'memotong teks';
                if (key === 'u') actionName = 'melihat kode sumber';
                if (key === 's') actionName = 'menyimpan halaman';
                if (key === 'p') actionName = 'mencetak halaman';
                if (key === 'r') actionName = 'memuat ulang halaman';
                if (key === 'f') actionName = 'mencari teks';

                reportViolation({
                    showModal: true,
                    violationType: 'keyboard-shortcut',
                    violationMessage: `Mahasiswa menekan shortcut ${shortcutName} (${actionName}).`,
                });
                return false;
            }

            // Block Ctrl + Shift + I/J/C (Developer Tools shortcuts)
            if (e.shiftKey && ['i', 'j', 'c'].includes(key)) {
                e.preventDefault();
                reportViolation({
                    showModal: true,
                    violationType: 'keyboard-shortcut',
                    violationMessage: `Mahasiswa menekan shortcut Ctrl+Shift+${key.toUpperCase()} (Developer Tools).`,
                });
                return false;
            }
        }
    }, true);

    // Remote desktop detection via electron IPC
    let remoteDesktopLogged = false;
    if (window.aegisDesktop && typeof window.aegisDesktop.onRemoteDesktopDetected === 'function') {
        window.aegisDesktop.onRemoteDesktopDetected((detected) => {
            if (!examActive) return;
            if (!detected) {
                remoteDesktopLogged = false;
                return;
            }
            if (remoteDesktopLogged) return;
            remoteDesktopLogged = true;
            reportViolation({
                showModal: true,
                violationType: 'remote-desktop',
                violationMessage: 'Mahasiswa terdeteksi menjalankan aplikasi Remote Desktop / Screen Sharing (AnyDesk, TeamViewer, RDP, UltraViewer, RustDesk, dll).',
            });
        });
    }
    });
</script>
@endpush
