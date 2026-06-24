@extends('layouts.exam')

@section('title', 'Login Administrator — AegisExam Admin')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 bg-gradient-to-br from-violet-50 via-slate-50 to-indigo-100">
    <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-2xl text-center">
        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-100 ring-1 ring-indigo-300">
            <i class="fa-solid fa-user-shield text-3xl text-indigo-600"></i>
        </div>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">Masuk AegisExam Admin</h1>
        <p class="mt-2 text-sm text-slate-500">Masukkan username dan password administrator.</p>

        <form method="POST" action="/admin/login" class="mt-6 text-left">
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

            <label class="block text-xs font-semibold text-slate-500">Username</label>
            <input name="username" required class="mt-1 mb-3 w-full rounded-2xl border border-slate-300 bg-slate-50 px-3 py-3 text-sm text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="Username">

            <label class="block text-xs font-semibold text-slate-500">Password</label>
            <input type="password" name="password" required class="mt-1 mb-4 w-full rounded-2xl border border-slate-300 bg-slate-50 px-3 py-3 text-sm text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="Password">

            <div class="flex items-center justify-center gap-3">
                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-indigo-900 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-200 transition hover:bg-indigo-800">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Masuk
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
