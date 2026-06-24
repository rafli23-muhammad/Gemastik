@extends('layouts.exam')

@section('title', 'Hasil Ujian - AegisExam')

@push('styles')
<style>
    @media print {
        body {
            background: #ffffff !important;
        }

        .no-print {
            display: none !important;
        }

        .print-card {
            box-shadow: none !important;
            border: 1px solid #dbe3ee !important;
        }
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(45,212,191,0.16),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(251,146,60,0.12),_transparent_24%),linear-gradient(180deg,_#f4f7fb_0%,_#edf3f8_48%,_#e6edf5_100%)] px-4 py-10">
    <div class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-5xl items-center justify-center">
        <div class="print-card w-full rounded-[32px] border border-white/80 bg-white/92 p-6 shadow-[0_30px_90px_rgba(15,23,42,0.10)] backdrop-blur-xl sm:p-8">
            <div class="text-center">
                <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-3xl bg-teal-50 text-teal-700 ring-4 ring-teal-100">
                    <i class="fa-solid fa-clipboard-check text-3xl"></i>
                </div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-teal-700">{{ $result['course_code'] ?? '-' }}</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Hasil Pengerjaan Ujian</h1>
                <p class="mt-2 text-sm text-slate-500">{{ $result['student_name'] ?? 'Mahasiswa' }}</p>
            </div>

            <div class="mt-8 grid gap-4 lg:grid-cols-2">
                <div class="rounded-[28px] border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Mata Kuliah</p>
                    <p class="mt-2 text-xl font-bold text-slate-900">{{ $result['course_name'] ?? '-' }}</p>
                </div>
                <div class="rounded-[28px] border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Nama Dosen</p>
                    <p class="mt-2 text-xl font-bold text-slate-900">{{ $result['teacher_name'] ?? 'Dosen' }}</p>
                </div>
                <div class="rounded-[28px] border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Waktu</p>
                    <p class="mt-2 text-xl font-bold text-slate-900">{{ $result['finished_time'] ?? '-' }}</p>
                </div>
                <div class="rounded-[28px] border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Tanggal</p>
                    <p class="mt-2 text-xl font-bold text-slate-900">{{ $result['finished_date'] ?? '-' }}</p>
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-4">
                <div class="rounded-[28px] border border-teal-200 bg-teal-50 p-5 text-center">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-600">Jumlah Soal</p>
                    <p class="mt-3 text-4xl font-bold text-teal-700">{{ $result['total_questions'] ?? 0 }}</p>
                </div>
                <div class="rounded-[28px] border border-emerald-200 bg-emerald-50 p-5 text-center">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-600">Benar</p>
                    <p class="mt-3 text-4xl font-bold text-emerald-700">{{ $result['correct_count'] ?? 0 }}</p>
                </div>
                <div class="rounded-[28px] border border-rose-200 bg-rose-50 p-5 text-center">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-600">Salah</p>
                    <p class="mt-3 text-4xl font-bold text-rose-700">{{ $result['wrong_count'] ?? 0 }}</p>
                </div>
                <div class="rounded-[28px] border border-amber-200 bg-amber-50 p-5 text-center">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-600">Nilai</p>
                    <p class="mt-3 text-4xl font-bold text-amber-700">{{ $result['score'] ?? 0 }}</p>
                </div>
            </div>

            <div class="no-print mt-8 flex flex-wrap justify-center gap-3">
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-2xl border border-teal-200 bg-teal-50 px-6 py-3 text-sm font-semibold text-teal-700 transition hover:bg-teal-100">
                    <i class="fa-solid fa-download"></i>
                    Download PDF
                </button>
                <a href="/logout" class="inline-flex items-center gap-2 rounded-2xl bg-[linear-gradient(135deg,_#0f766e_0%,_#155e75_100%)] px-6 py-3 text-sm font-semibold text-white shadow-[0_18px_40px_rgba(15,118,110,0.22)] transition hover:opacity-95">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Selesai
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
