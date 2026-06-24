@extends('layouts.exam')

@section('title', 'Login Dosen — AegisExam Backend')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 bg-gradient-to-br from-cyan-50 via-slate-50 to-indigo-100">
    <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-2xl text-center">
        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-100 ring-1 ring-amber-300">
            <i class="fa-solid fa-chalkboard-teacher text-3xl text-amber-600"></i>
        </div>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">Masuk Backend Dosen</h1>
        <p class="mt-2 text-sm text-slate-500">Masukkan nama dan NIP untuk mengakses dashboard.</p>

        <form method="POST" action="/backend/login" class="mt-6 text-left">
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

            <label class="block text-xs text-slate-500">Nama Dosen</label>
            <input name="teacher_name" required class="mt-1 mb-3 w-full rounded-2xl border border-slate-300 bg-slate-50 px-3 py-3 text-sm text-slate-900 placeholder:text-slate-400" placeholder="Nama Dosen">

            <label class="block text-xs text-slate-500">NIP</label>
            <input name="teacher_nip" required class="mt-1 mb-3 w-full rounded-2xl border border-slate-300 bg-slate-50 px-3 py-3 text-sm text-slate-900 placeholder:text-slate-400" placeholder="NIP">

            <label class="block text-xs text-slate-500">Email</label>
            <input type="email" name="teacher_email" required class="mt-1 mb-4 w-full rounded-2xl border border-slate-300 bg-slate-50 px-3 py-3 text-sm text-slate-900 placeholder:text-slate-400" placeholder="dosen@email.com">

            <div class="flex items-center justify-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-200 transition hover:bg-slate-800">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Masuk
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
