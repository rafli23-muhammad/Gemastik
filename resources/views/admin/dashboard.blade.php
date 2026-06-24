@extends('layouts.exam')

@section('title', 'Admin Dashboard - AegisExam')

@php
    $activeTab = $activeTab ?? 'dashboard';
    
    // Read statistics
    $studentsList = $backend['students_list'] ?? [];
    $teachersList = $backend['teachers_list'] ?? [];
    $usersList = $backend['users_list'] ?? [];
    
    $studentCount = count($studentsList);
    $teacherCount = count($teachersList);
    $userCount = count($usersList);
    
    $questionCodes = $backend['question_codes'] ?? [];
    $examCount = count($questionCodes);
    $questions = $backend['questions'] ?? [];
    $totalQuestions = count($questions);
    
    // Live exam logs & results
    $students = $backend['students'] ?? [];
    $violationLogs = $backend['violation_logs'] ?? [];
    $examResults = $backend['exam_results'] ?? [];
    
    $activeExamsCount = count(array_filter($students, function($s) {
        return empty($s['last_result']) && (!empty($s['started_at']) || !empty($s['last_seen']));
    }));
    $completedExamsCount = count($examResults);
    $violationCount = count($violationLogs);

    $pageTitles = [
        'dashboard' => 'Dashboard Analitik',
        'mahasiswa' => 'Kelola Data Mahasiswa',
        'dosen' => 'Kelola Data Dosen',
        'users' => 'Kelola Akun Pengguna',
        'monitoring' => 'Monitoring Ujian Berlangsung',
        'reports' => 'Laporan Hasil Ujian',
    ];
    $pageTitle = $pageTitles[$activeTab] ?? $pageTitles['dashboard'];
@endphp

@section('content')
<div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(99,102,241,0.12),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(168,85,247,0.12),_transparent_24%),linear-gradient(180deg,_#f8fafc_0%,_#f1f5f9_48%,_#e2e8f0_100%)] text-slate-900 print:bg-white print:text-black">
    <div class="mx-auto flex min-h-screen max-w-[1720px] gap-6 px-4 py-4 sm:px-6 lg:px-8 print:p-0 print:m-0">
        
        <!-- Sidebar Navigation (hidden on print) -->
        <aside class="hidden w-[290px] shrink-0 xl:block print:hidden">
            <div class="sticky top-4 flex h-[calc(100vh-2rem)] flex-col overflow-hidden rounded-[32px] border border-indigo-900/10 bg-[linear-gradient(180deg,_#1e1b4b_0%,_#0f172a_100%)] p-6 text-white shadow-[0_25px_80px_rgba(30,27,75,0.28)]">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl bg-white p-1 ring-1 ring-white/30">
                        <i class="fa-solid fa-user-shield text-2xl text-indigo-900"></i>
                    </div>
                    <div>
                        <p class="text-lg font-bold tracking-tight">AegisExam Admin</p>
                        <p class="text-xs text-indigo-200/70">Pusat Administrasi</p>
                    </div>
                </div>

                <div class="mt-8 flex-1 space-y-8">
                    <div>
                        <p class="mb-3 text-[10px] font-semibold uppercase tracking-[0.28em] text-indigo-200/40">Menu Utama</p>
                        <div class="space-y-1">
                            <a href="/admin/dashboard" class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ $activeTab === 'dashboard' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                <i class="fa-solid fa-chart-pie text-base"></i>
                                Dashboard
                            </a>
                            <a href="/admin/dashboard/mahasiswa" class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ $activeTab === 'mahasiswa' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                <i class="fa-solid fa-user-graduate text-base"></i>
                                Kelola Mahasiswa
                            </a>
                            <a href="/admin/dashboard/dosen" class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ $activeTab === 'dosen' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                <i class="fa-solid fa-chalkboard-teacher text-base"></i>
                                Kelola Dosen
                            </a>
                            <a href="/admin/dashboard/users" class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ $activeTab === 'users' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                <i class="fa-solid fa-users text-base"></i>
                                Akun Pengguna
                            </a>
                        </div>
                    </div>

                    <div>
                        <p class="mb-3 text-[10px] font-semibold uppercase tracking-[0.28em] text-indigo-200/40">Monitoring & Nilai</p>
                        <div class="space-y-1">
                            <a href="/admin/dashboard/monitoring" class="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-sm font-medium transition {{ $activeTab === 'monitoring' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                <span class="flex items-center gap-3">
                                    <i class="fa-solid fa-desktop text-base"></i>
                                    Monitoring Ujian
                                </span>
                                @if($activeExamsCount > 0)
                                    <span class="rounded-full bg-emerald-500/25 px-2 py-0.5 text-xs text-emerald-300">{{ $activeExamsCount }}</span>
                                @endif
                            </a>
                            <a href="/admin/dashboard/reports" class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ $activeTab === 'reports' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                <i class="fa-solid fa-file-invoice-amount text-base"></i>
                                Laporan Hasil
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-auto border-t border-white/10 pt-4">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-500 text-white font-bold">
                            {{ strtoupper(substr($admin['username'] ?? 'A', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">{{ $admin['name'] ?? 'Administrator' }}</p>
                            <p class="truncate text-xs text-slate-400 capitalize">{{ $admin['role'] ?? 'Admin' }}</p>
                        </div>
                    </div>
                    <a href="/admin/logout" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-white/5 px-4 py-3 text-sm font-medium text-red-200 transition hover:bg-red-500/10 hover:text-red-100">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        Keluar (Logout)
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="min-w-0 flex-1 py-2 print:p-0">
            
            <!-- Tab Header (hidden on print) -->
            <header class="rounded-[32px] border border-white/70 bg-white/80 p-5 shadow-[0_18px_60px_rgba(15,23,42,0.05)] backdrop-blur-xl print:hidden">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                            <span class="font-medium">AegisExam Admin</span>
                            <span class="text-slate-300">/</span>
                            <span class="font-semibold text-indigo-600">{{ $pageTitle }}</span>
                        </div>
                        <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ $pageTitle }}</h1>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-xs font-semibold text-slate-600">Sistem Online & Terkoneksi</span>
                    </div>
                </div>
            </header>

            <!-- Alerts (hidden on print) -->
            @if(session('success'))
                <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 flex items-center gap-3 print:hidden">
                    <i class="fa-solid fa-circle-check text-lg text-emerald-600"></i>
                    {{ session('success') }}
                </div>
            @endif
            
            @if($errors->any())
                <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800 print:hidden">
                    <div class="flex items-center gap-3 mb-2">
                        <i class="fa-solid fa-circle-xmark text-lg text-red-600"></i>
                        <span>Terdapat kesalahan pada input Anda:</span>
                    </div>
                    <ul class="list-disc pl-8 font-medium text-xs space-y-1">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- CONTENT SECTIONS -->

            <!-- 1. DASHBOARD ANALITIK -->
            @if($activeTab === 'dashboard')
                <div class="mt-6 space-y-6">
                    <!-- Key Statistics Cards -->
                    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                        <div class="rounded-3xl border border-white bg-white/70 p-6 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Mahasiswa</p>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-3xl font-extrabold tracking-tight">{{ $studentCount }}</span>
                                <span class="text-xs text-slate-500">terdaftar</span>
                            </div>
                        </div>
                        <div class="rounded-3xl border border-white bg-white/70 p-6 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Dosen</p>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-3xl font-extrabold tracking-tight">{{ $teacherCount }}</span>
                                <span class="text-xs text-slate-500">terdaftar</span>
                            </div>
                        </div>
                        <div class="rounded-3xl border border-white bg-white/70 p-6 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Mata Kuliah Ujian</p>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-3xl font-extrabold tracking-tight">{{ $examCount }}</span>
                                <span class="text-xs text-slate-500">dibuat dosen</span>
                            </div>
                        </div>
                        <div class="rounded-3xl border border-white bg-white/70 p-6 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Ujian Berlangsung</p>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-3xl font-extrabold tracking-tight text-indigo-600">{{ $activeExamsCount }}</span>
                                <span class="text-xs text-indigo-500 font-semibold">sedang aktif</span>
                            </div>
                        </div>
                    </div>

                    <!-- Visual Widgets & Charts -->
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <!-- Chart 1: Status Ujian Mahasiswa -->
                        <div class="rounded-[28px] border border-white bg-white/75 p-6 shadow-sm lg:col-span-2">
                            <h3 class="text-base font-bold text-slate-800 mb-4">Statistik Ujian Mahasiswa</h3>
                            <div class="relative h-[300px] w-full">
                                <canvas id="examStatusChart"></canvas>
                            </div>
                        </div>

                        <!-- Sidebar Summary Stats -->
                        <div class="rounded-[28px] border border-white bg-white/75 p-6 shadow-sm flex flex-col justify-between">
                            <div>
                                <h3 class="text-base font-bold text-slate-800 mb-4">Aktivitas Ujian</h3>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                        <div class="flex items-center gap-3">
                                            <div class="rounded-xl bg-indigo-50 p-2 text-indigo-600">
                                                <i class="fa-solid fa-circle-info"></i>
                                            </div>
                                            <span class="text-sm font-semibold text-slate-600">Selesai Mengerjakan</span>
                                        </div>
                                        <span class="text-lg font-bold text-slate-800">{{ $completedExamsCount }}</span>
                                    </div>
                                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                        <div class="flex items-center gap-3">
                                            <div class="rounded-xl bg-orange-50 p-2 text-orange-600">
                                                <i class="fa-solid fa-triangle-exclamation"></i>
                                            </div>
                                            <span class="text-sm font-semibold text-slate-600">Pelanggaran Terdeteksi</span>
                                        </div>
                                        <span class="text-lg font-bold text-slate-800">{{ $violationCount }}</span>
                                    </div>
                                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                        <div class="flex items-center gap-3">
                                            <div class="rounded-xl bg-purple-50 p-2 text-purple-600">
                                                <i class="fa-solid fa-file-lines"></i>
                                            </div>
                                            <span class="text-sm font-semibold text-slate-600">Total Soal Aktif</span>
                                        </div>
                                        <span class="text-lg font-bold text-slate-800">{{ $totalQuestions }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-6 rounded-2xl bg-indigo-900/5 p-4 border border-indigo-900/5">
                                <p class="text-xs text-indigo-950 font-medium leading-relaxed">
                                    <i class="fa-solid fa-shield-halved text-indigo-600 mr-1.5"></i>
                                    Sistem memantau kecurangan berbasis AI secara real-time. Kelola NIM mahasiswa dan NIP dosen untuk membatasi akses ujian agar tidak disalahgunakan.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- 2. KELOLA MAHASISWA -->
            @if($activeTab === 'mahasiswa')
                <div class="mt-6 rounded-[28px] border border-white bg-white/75 p-6 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
                        <h3 class="text-lg font-bold text-slate-800">Daftar Mahasiswa Terdaftar</h3>
                        <button onclick="toggleModal('addStudentModal', true)" class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-600/10 transition hover:bg-indigo-700">
                            <i class="fa-solid fa-user-plus"></i>
                            Tambah Mahasiswa
                        </button>
                    </div>

                    <!-- Students Table -->
                    <div class="overflow-x-auto rounded-2xl border border-slate-100">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase">
                                    <th class="px-6 py-4">NIM</th>
                                    <th class="px-6 py-4">Nama Lengkap</th>
                                    <th class="px-6 py-4">Email</th>
                                    <th class="px-6 py-4">Tanggal Registrasi</th>
                                    <th class="px-6 py-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm font-medium">
                                @forelse($studentsList as $student)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-4 font-mono text-slate-600">{{ $student['nim'] ?? '-' }}</td>
                                        <td class="px-6 py-4 text-slate-900">{{ $student['name'] ?? '-' }}</td>
                                        <td class="px-6 py-4 text-slate-500">{{ $student['email'] ?? '-' }}</td>
                                        <td class="px-6 py-4 text-xs text-slate-400">
                                            {{ !empty($student['created_at']) ? date('d-m-Y H:i', strtotime($student['created_at'])) : '-' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-center gap-2">
                                                <button onclick="openEditStudentModal('{{ $student['nim'] }}', '{{ addslashes($student['name']) }}', '{{ $student['email'] }}')" class="rounded-xl bg-slate-100 px-3 py-2 text-slate-600 hover:bg-slate-200 transition" title="Edit">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <button onclick="openDeleteStudentModal('{{ $student['nim'] }}', '{{ addslashes($student['name']) }}')" class="rounded-xl bg-red-50 px-3 py-2 text-red-600 hover:bg-red-100 transition" title="Hapus">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                            <i class="fa-solid fa-box-open text-3xl mb-2 block"></i>
                                            Belum ada data mahasiswa. Silakan tambahkan mahasiswa baru.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- 3. KELOLA DOSEN -->
            @if($activeTab === 'dosen')
                <div class="mt-6 rounded-[28px] border border-white bg-white/75 p-6 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
                        <h3 class="text-lg font-bold text-slate-800">Daftar Dosen Terdaftar</h3>
                        <button onclick="toggleModal('addTeacherModal', true)" class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-600/10 transition hover:bg-indigo-700">
                            <i class="fa-solid fa-user-plus"></i>
                            Tambah Dosen
                        </button>
                    </div>

                    <!-- Teachers Table -->
                    <div class="overflow-x-auto rounded-2xl border border-slate-100">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase">
                                    <th class="px-6 py-4">NIP</th>
                                    <th class="px-6 py-4">Nama Lengkap</th>
                                    <th class="px-6 py-4">Email</th>
                                    <th class="px-6 py-4">Tanggal Registrasi</th>
                                    <th class="px-6 py-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm font-medium">
                                @forelse($teachersList as $teacher)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-4 font-mono text-slate-600">{{ $teacher['nip'] ?? '-' }}</td>
                                        <td class="px-6 py-4 text-slate-900">{{ $teacher['name'] ?? '-' }}</td>
                                        <td class="px-6 py-4 text-slate-500">{{ $teacher['email'] ?? '-' }}</td>
                                        <td class="px-6 py-4 text-xs text-slate-400">
                                            {{ !empty($teacher['created_at']) ? date('d-m-Y H:i', strtotime($teacher['created_at'])) : '-' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-center gap-2">
                                                <button onclick="openEditTeacherModal('{{ $teacher['nip'] }}', '{{ addslashes($teacher['name']) }}', '{{ $teacher['email'] }}')" class="rounded-xl bg-slate-100 px-3 py-2 text-slate-600 hover:bg-slate-200 transition" title="Edit">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <button onclick="openDeleteTeacherModal('{{ $teacher['nip'] }}', '{{ addslashes($teacher['name']) }}')" class="rounded-xl bg-red-50 px-3 py-2 text-red-600 hover:bg-red-100 transition" title="Hapus">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                            <i class="fa-solid fa-box-open text-3xl mb-2 block"></i>
                                            Belum ada data dosen. Silakan tambahkan dosen baru.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- 4. KELOLA AKUN PENGGUNA -->
            @if($activeTab === 'users')
                <div class="mt-6 rounded-[28px] border border-white bg-white/75 p-6 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
                        <h3 class="text-lg font-bold text-slate-800">Daftar Akun Pengguna</h3>
                        <button onclick="toggleModal('addUserModal', true)" class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-600/10 transition hover:bg-indigo-700">
                            <i class="fa-solid fa-user-plus"></i>
                            Tambah Akun
                        </button>
                    </div>

                    <!-- Users Table -->
                    <div class="overflow-x-auto rounded-2xl border border-slate-100">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase">
                                    <th class="px-6 py-4">Username</th>
                                    <th class="px-6 py-4">Nama Lengkap</th>
                                    <th class="px-6 py-4">Role</th>
                                    <th class="px-6 py-4">Password</th>
                                    <th class="px-6 py-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm font-medium">
                                @forelse($usersList as $usr)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-4 font-mono text-slate-600">{{ $usr['username'] ?? '-' }}</td>
                                        <td class="px-6 py-4 text-slate-900">{{ $usr['name'] ?? '-' }}</td>
                                        <td class="px-6 py-4">
                                            <span class="rounded-full px-2.5 py-1 text-xs font-bold capitalize {{ ($usr['role'] ?? '') === 'admin' ? 'bg-indigo-100 text-indigo-800' : 'bg-amber-100 text-amber-800' }}">
                                                {{ $usr['role'] ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-slate-400 font-mono text-xs">••••••••</td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-center gap-2">
                                                <button onclick="openEditUserModal('{{ $usr['username'] }}', '{{ addslashes($usr['name']) }}', '{{ $usr['role'] }}')" class="rounded-xl bg-slate-100 px-3 py-2 text-slate-600 hover:bg-slate-200 transition" title="Edit">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                @if(strtolower(session('admin_user.username')) !== strtolower($usr['username'] ?? ''))
                                                    <button onclick="openDeleteUserModal('{{ $usr['username'] }}')" class="rounded-xl bg-red-50 px-3 py-2 text-red-600 hover:bg-red-100 transition" title="Hapus">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                @else
                                                    <span class="text-xs text-slate-400 font-normal italic">Aktif</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                            <i class="fa-solid fa-box-open text-3xl mb-2 block"></i>
                                            Belum ada data akun pengguna.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- 5. MONITORING UJIAN BERLANGSUNG -->
            @if($activeTab === 'monitoring')
                <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
                    <!-- Left: Ujian yang Dibuat Dosen -->
                    <div class="rounded-[28px] border border-white bg-white/75 p-6 shadow-sm xl:col-span-1">
                        <h3 class="text-lg font-bold text-slate-800 mb-4">Daftar Ujian Aktif</h3>
                        <div class="space-y-4">
                            @forelse($questionCodes as $code)
                                @php
                                    $codeQuestionsCount = count(array_filter($questions, function($q) use ($code) {
                                        return ($q['code_id'] ?? null) === ($code['id'] ?? null);
                                    }));
                                @endphp
                                <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 hover:border-indigo-100 transition">
                                    <div class="flex items-center justify-between">
                                        <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-bold text-indigo-800">{{ $code['course_code'] ?? 'UMUM' }}</span>
                                        <span class="text-xs text-slate-400 font-semibold">{{ $codeQuestionsCount }} Soal</span>
                                    </div>
                                    <h4 class="mt-2 text-sm font-bold text-slate-800">{{ $code['course_name'] ?? 'Mata Kuliah' }}</h4>
                                    <p class="mt-1 text-xs text-slate-500 flex items-center gap-1.5">
                                        <i class="fa-regular fa-user text-slate-400"></i>
                                        Pengampu: {{ $code['teacher_name'] ?? 'Dosen' }}
                                    </p>
                                </div>
                            @empty
                                <div class="text-center py-8 text-slate-400">
                                    <i class="fa-regular fa-calendar-xmark text-3xl mb-2 block"></i>
                                    Belum ada mata kuliah ujian yang diatur oleh dosen.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Right: Mahasiswa Sedang Mengerjakan -->
                    <div class="rounded-[28px] border border-white bg-white/75 p-6 shadow-sm xl:col-span-2">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-slate-800">Daftar Peserta Ujian</h3>
                            <button onclick="location.reload()" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition flex items-center gap-2">
                                <i class="fa-solid fa-rotate"></i> Refresh Data
                            </button>
                        </div>

                        <div class="overflow-x-auto rounded-2xl border border-slate-100">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase">
                                        <th class="px-4 py-3">Mahasiswa</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3">Peralatan/Tab Keluar</th>
                                        <th class="px-4 py-3">Aktif Terakhir</th>
                                        <th class="px-4 py-3 text-center">Webcam</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-xs font-medium">
                                    @forelse($students as $student)
                                        @php
                                            $isFinished = !empty($student['last_result']);
                                            $isOnline = !$isFinished && !empty($student['last_seen']) && (time() - strtotime($student['last_seen']) < 30);
                                        @endphp
                                        <tr class="hover:bg-slate-50/50 transition">
                                            <td class="px-4 py-3">
                                                <p class="font-bold text-slate-850">{{ $student['student_name'] ?? '-' }}</p>
                                                <p class="text-[10px] text-slate-400 font-mono">NIM: {{ $student['student_nim'] ?? '-' }}</p>
                                            </td>
                                            <td class="px-4 py-3">
                                                @if($isFinished)
                                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 font-bold text-slate-600">Selesai</span>
                                                @elseif($isOnline)
                                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-bold text-emerald-800">Ujian Aktif</span>
                                                @else
                                                    <span class="rounded-full bg-orange-100 px-2 py-0.5 font-bold text-orange-800">Offline</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="rounded-full px-2 py-0.5 font-bold {{ ($student['violation_count'] ?? 0) > 0 ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-500' }}">
                                                    {{ $student['violation_count'] ?? 0 }} Pelanggaran
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-400">
                                                {{ !empty($student['last_seen']) ? date('H:i:s', strtotime($student['last_seen'])) : '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @if(!empty($student['last_snapshot']))
                                                    <div class="relative inline-block group">
                                                        <img src="{{ $student['last_snapshot'] }}" class="h-8 w-12 object-cover rounded border border-slate-200 cursor-pointer shadow-sm hover:scale-105 transition" />
                                                        <!-- Preview Zoom Hover -->
                                                        <div class="absolute bottom-full right-0 z-50 mb-2 hidden w-48 rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl group-hover:block">
                                                            <img src="{{ $student['last_snapshot'] }}" class="w-full object-cover rounded-lg" />
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-[10px] text-slate-400 italic">No Feed</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                                <i class="fa-solid fa-face-frown text-3xl mb-2 block"></i>
                                                Belum ada peserta ujian yang online.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- 6. LAPORAN HASIL UJIAN -->
            @if($activeTab === 'reports')
                <div class="mt-6 rounded-[28px] border border-white bg-white/75 p-6 shadow-sm print:p-0 print:border-none print:shadow-none">
                    
                    <!-- Print-only Header -->
                    <div class="hidden print:block text-center mb-6">
                        <h2 class="text-xl font-bold">LAPORAN HASIL NILAI UJIAN</h2>
                        <h3 class="text-md font-semibold text-slate-700">Aplikasi Pengawasan Ujian AegisExam</h3>
                        <p class="text-xs text-slate-500 mt-1">Dicetak pada: {{ date('d-m-Y H:i:s') }}</p>
                        <hr class="mt-4 border-slate-300">
                    </div>

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6 print:hidden">
                        <h3 class="text-lg font-bold text-slate-800">Seluruh Hasil Nilai Mahasiswa</h3>
                        <div class="flex items-center gap-2">
                            <button onclick="window.print()" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                <i class="fa-solid fa-print"></i>
                                Cetak PDF
                            </button>
                            <a href="/admin/reports/export" class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-600/10 transition hover:bg-indigo-700">
                                <i class="fa-solid fa-file-excel"></i>
                                Ekspor CSV (Excel)
                            </a>
                        </div>
                    </div>

                    <!-- Scores Table -->
                    <div class="overflow-x-auto rounded-2xl border border-slate-100 print:border-none">
                        <table class="w-full text-left border-collapse print:text-xs">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase print:bg-slate-100">
                                    <th class="px-6 py-4">Mahasiswa</th>
                                    <th class="px-6 py-4">Mata Kuliah / Dosen</th>
                                    <th class="px-6 py-4 text-center">Waktu</th>
                                    <th class="px-6 py-4 text-center">Soal (Benar/Salah)</th>
                                    <th class="px-6 py-4 text-right">Nilai</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm font-medium">
                                @forelse($examResults as $res)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-4">
                                            <p class="font-bold text-slate-900">{{ $res['student_name'] ?? '-' }}</p>
                                            <p class="text-xs text-slate-400 font-mono">NIM: {{ $res['student_nim'] ?? '-' }}</p>
                                        </td>
                                        <td class="px-6 py-4">
                                            <p class="text-slate-800 font-semibold">{{ $res['course_name'] ?? '-' }}</p>
                                            <p class="text-xs text-slate-500 font-medium">Dosen: {{ $res['teacher_name'] ?? '-' }} ({{ $res['course_code'] ?? '-' }})</p>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <p class="text-slate-700">{{ $res['finished_date'] ?? '-' }}</p>
                                            <p class="text-xs text-slate-400">{{ $res['finished_time'] ?? '-' }}</p>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <p class="text-slate-800">{{ $res['total_questions'] ?? 0 }} Soal</p>
                                            <p class="text-xs text-emerald-600 font-semibold">B: {{ $res['correct_count'] ?? 0 }} | S: {{ $res['wrong_count'] ?? 0 }}</p>
                                        </td>
                                        <td class="px-6 py-4 text-right font-mono text-lg font-bold text-slate-900 print:text-sm">
                                            {{ $res['score'] ?? 0 }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                            <i class="fa-solid fa-clipboard-list text-3xl mb-2 block"></i>
                                            Belum ada mahasiswa yang menyelesaikan ujian.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>

<!-- ================= MODALS ================= -->

<!-- 1. Add Student Modal -->
<div id="addStudentModal" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-3xl border border-slate-100 bg-white p-6 shadow-2xl">
        <h4 class="text-lg font-bold text-slate-800 mb-4">Tambah Mahasiswa Baru</h4>
        <form method="POST" action="/admin/mahasiswa/store" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">NIM</label>
                <input name="nim" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="Contoh: 12210100">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Nama Lengkap</label>
                <input name="name" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="Nama Lengkap">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Email</label>
                <input type="email" name="email" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="alamat@email.com">
            </div>
            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="toggleModal('addStudentModal', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Batal</button>
                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-indigo-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Edit Student Modal -->
<div id="editStudentModal" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-3xl border border-slate-100 bg-white p-6 shadow-2xl">
        <h4 class="text-lg font-bold text-slate-800 mb-4">Edit Data Mahasiswa</h4>
        <form method="POST" action="/admin/mahasiswa/update" class="space-y-4">
            @csrf
            <input type="hidden" name="old_nim" id="edit_student_old_nim">
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">NIM</label>
                <input name="nim" id="edit_student_nim" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Nama Lengkap</label>
                <input name="name" id="edit_student_name" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Email</label>
                <input type="email" name="email" id="edit_student_email" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>
            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="toggleModal('editStudentModal', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Batal</button>
                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-indigo-700">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. Delete Student Modal -->
<div id="deleteStudentModal" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-3xl border border-slate-100 bg-white p-6 shadow-2xl">
        <h4 class="text-lg font-bold text-red-650 mb-2">Hapus Mahasiswa?</h4>
        <p class="text-sm text-slate-500 mb-4">Apakah Anda yakin ingin menghapus mahasiswa <span id="delete_student_label" class="font-bold text-slate-800"></span>? Akun ini tidak akan dapat login lagi ke ruang ujian.</p>
        <form method="POST" action="/admin/mahasiswa/delete">
            @csrf
            <input type="hidden" name="nim" id="delete_student_nim">
            <div class="flex items-center justify-end gap-2">
                <button type="button" onclick="toggleModal('deleteStudentModal', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Batal</button>
                <button type="submit" class="rounded-xl bg-red-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-red-700">Hapus Permanen</button>
            </div>
        </form>
    </div>
</div>

<!-- 4. Add Teacher Modal -->
<div id="addTeacherModal" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-3xl border border-slate-100 bg-white p-6 shadow-2xl">
        <h4 class="text-lg font-bold text-slate-800 mb-4">Tambah Dosen Baru</h4>
        <form method="POST" action="/admin/dosen/store" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">NIP</label>
                <input name="nip" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="Contoh: 20240101">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Nama Lengkap</label>
                <input name="name" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="Nama Lengkap & Gelar">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Email</label>
                <input type="email" name="email" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="dosen@email.com">
            </div>
            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="toggleModal('addTeacherModal', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Batal</button>
                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-indigo-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- 5. Edit Teacher Modal -->
<div id="editTeacherModal" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-3xl border border-slate-100 bg-white p-6 shadow-2xl">
        <h4 class="text-lg font-bold text-slate-800 mb-4">Edit Data Dosen</h4>
        <form method="POST" action="/admin/dosen/update" class="space-y-4">
            @csrf
            <input type="hidden" name="old_nip" id="edit_teacher_old_nip">
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">NIP</label>
                <input name="nip" id="edit_teacher_nip" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Nama Lengkap</label>
                <input name="name" id="edit_teacher_name" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Email</label>
                <input type="email" name="email" id="edit_teacher_email" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>
            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="toggleModal('editTeacherModal', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Batal</button>
                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-indigo-700">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- 6. Delete Teacher Modal -->
<div id="deleteTeacherModal" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-3xl border border-slate-100 bg-white p-6 shadow-2xl">
        <h4 class="text-lg font-bold text-red-655 mb-2">Hapus Dosen?</h4>
        <p class="text-sm text-slate-500 mb-4">Apakah Anda yakin ingin menghapus dosen <span id="delete_teacher_label" class="font-bold text-slate-800"></span>? NIP ini tidak akan dapat masuk kembali ke dashboard pengawasan.</p>
        <form method="POST" action="/admin/dosen/delete">
            @csrf
            <input type="hidden" name="nip" id="delete_teacher_nip">
            <div class="flex items-center justify-end gap-2">
                <button type="button" onclick="toggleModal('deleteTeacherModal', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Batal</button>
                <button type="submit" class="rounded-xl bg-red-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-red-700">Hapus Permanen</button>
            </div>
        </form>
    </div>
</div>

<!-- 7. Add User Modal -->
<div id="addUserModal" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-3xl border border-slate-100 bg-white p-6 shadow-2xl">
        <h4 class="text-lg font-bold text-slate-800 mb-4">Tambah Akun Pengguna</h4>
        <form method="POST" action="/admin/users/store" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Username</label>
                <input name="username" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="Username (huruf kecil)">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Nama Lengkap</label>
                <input name="name" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="Nama Akun">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Role / Peran</label>
                <select name="role" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm bg-white text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="admin">Administrator</option>
                    <option value="dosen">Dosen</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Password</label>
                <input type="password" name="password" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="Minimal 4 karakter">
            </div>
            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="toggleModal('addUserModal', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Batal</button>
                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-indigo-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- 8. Edit User Modal -->
<div id="editUserModal" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-3xl border border-slate-100 bg-white p-6 shadow-2xl">
        <h4 class="text-lg font-bold text-slate-800 mb-4">Edit Akun Pengguna</h4>
        <form method="POST" action="/admin/users/update" class="space-y-4">
            @csrf
            <input type="hidden" name="old_username" id="edit_user_old_username">
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Username</label>
                <input name="username" id="edit_user_username" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Nama Lengkap</label>
                <input name="name" id="edit_user_name" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Role / Peran</label>
                <select name="role" id="edit_user_role" required class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm bg-white text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="admin">Administrator</option>
                    <option value="dosen">Dosen</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Password Baru (kosongkan jika tidak diubah)</label>
                <input type="password" name="password" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="Ketik password baru">
            </div>
            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="toggleModal('editUserModal', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Batal</button>
                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-indigo-700">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- 9. Delete User Modal -->
<div id="deleteUserModal" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-3xl border border-slate-100 bg-white p-6 shadow-2xl">
        <h4 class="text-lg font-bold text-red-650 mb-2">Hapus Akun Pengguna?</h4>
        <p class="text-sm text-slate-500 mb-4">Apakah Anda yakin ingin menghapus akun pengguna dengan username: <span id="delete_user_label" class="font-bold text-slate-800"></span>? Akun ini tidak akan dapat mengakses administrasi/dashboard.</p>
        <form method="POST" action="/admin/users/delete">
            @csrf
            <input type="hidden" name="username" id="delete_user_username">
            <div class="flex items-center justify-end gap-2">
                <button type="button" onclick="toggleModal('deleteUserModal', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Batal</button>
                <button type="submit" class="rounded-xl bg-red-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-red-700">Hapus Permanen</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // General Modal toggle helper
    function toggleModal(modalId, show) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.toggle('hidden', !show);
        }
    }

    // Student CRUD Modals
    function openEditStudentModal(nim, name, email) {
        document.getElementById('edit_student_old_nim').value = nim;
        document.getElementById('edit_student_nim').value = nim;
        document.getElementById('edit_student_name').value = name;
        document.getElementById('edit_student_email').value = email;
        toggleModal('editStudentModal', true);
    }
    function openDeleteStudentModal(nim, name) {
        document.getElementById('delete_student_nim').value = nim;
        document.getElementById('delete_student_label').textContent = name + ' (' + nim + ')';
        toggleModal('deleteStudentModal', true);
    }

    // Teacher CRUD Modals
    function openEditTeacherModal(nip, name, email) {
        document.getElementById('edit_teacher_old_nip').value = nip;
        document.getElementById('edit_teacher_nip').value = nip;
        document.getElementById('edit_teacher_name').value = name;
        document.getElementById('edit_teacher_email').value = email;
        toggleModal('editTeacherModal', true);
    }
    function openDeleteTeacherModal(nip, name) {
        document.getElementById('delete_teacher_nip').value = nip;
        document.getElementById('delete_teacher_label').textContent = name + ' (' + nip + ')';
        toggleModal('deleteTeacherModal', true);
    }

    // User CRUD Modals
    function openEditUserModal(username, name, role) {
        document.getElementById('edit_user_old_username').value = username;
        document.getElementById('edit_user_username').value = username;
        document.getElementById('edit_user_name').value = name;
        document.getElementById('edit_user_role').value = role;
        toggleModal('editUserModal', true);
    }
    function openDeleteUserModal(username) {
        document.getElementById('delete_user_username').value = username;
        document.getElementById('delete_user_label').textContent = username;
        toggleModal('deleteUserModal', true);
    }

    // Render Chart.js on Dashboard
    @if($activeTab === 'dashboard')
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('examStatusChart').getContext('2d');
            
            // Calculate statuses
            const completedCount = {{ $completedExamsCount }};
            const activeCount = {{ $activeExamsCount }};
            const registeredCount = {{ $studentCount }};
            const idleCount = Math.max(0, registeredCount - (completedCount + activeCount));

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Belum Mulai', 'Aktif Mengerjakan', 'Selesai'],
                    datasets: [{
                        label: 'Jumlah Mahasiswa',
                        data: [idleCount, activeCount, completedCount],
                        backgroundColor: [
                            'rgba(148, 163, 184, 0.65)',  // Slate
                            'rgba(99, 102, 241, 0.75)',   // Indigo
                            'rgba(16, 185, 129, 0.75)'    // Emerald
                        ],
                        borderColor: [
                            'rgb(148, 163, 184)',
                            'rgb(99, 102, 241)',
                            'rgb(16, 185, 129)'
                        ],
                        borderWidth: 1.5,
                        borderRadius: 12
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.04)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        });
    @endif
</script>
@endpush

@push('styles')
<style>
    @media print {
        body {
            background-color: white !important;
            color: black !important;
        }
        aside, header, button, a {
            display: none !important;
        }
        main {
            padding: 0 !important;
            margin: 0 !important;
        }
    }
</style>
@endpush
@endsection
