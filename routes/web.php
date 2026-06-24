<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/**
 * Data ujian dummy untuk mode simulasi.
 */
function simExam(): object
{
    return (object) [
        'title' => 'UTS Pemrograman Web Pro',
        'max_violation' => 3,
    ];
}

function simMaxViolation(): int
{
    return simExam()->max_violation;
}

function simViolationCount(): int
{
    return (int) session('violation_count', 0);
}

function currentStudentIdentity(): array
{
    return [
        'student_name' => session('student_name'),
        'student_nim' => session('student_nim'),
    ];
}

// Data backend dipakai bersama oleh app Mahasiswa & Dosen (Electron terpisah).
function aegisSharedDataDir(): string
{
    $fromEnv = getenv('AEGIS_SHARED_DATA');
    if (is_string($fromEnv) && $fromEnv !== '') {
        return $fromEnv;
    }

    if (PHP_OS_FAMILY === 'Windows') {
        $appData = getenv('APPDATA');
        if (!is_string($appData) || $appData === '') {
            $profile = getenv('USERPROFILE') ?: '';
            $appData = $profile !== '' ? $profile.'\\AppData\\Roaming' : '';
        }

        if ($appData !== '') {
            return $appData.'\\AegisExam\\data';
        }
    }

    $home = getenv('HOME') ?: '';
    if ($home !== '') {
        return $home.DIRECTORY_SEPARATOR.'.aegisexam'.DIRECTORY_SEPARATOR.'data';
    }

    return storage_path('app');
}

function localBackendDataPath(): string
{
    return storage_path('app/backend_data.json');
}

function mergeBackendPayload(array $target, array $source, bool $includeBlockedStudents = true): array
{
    foreach (['question_codes', 'questions', 'students', 'blocked_students', 'violation_logs'] as $key) {
        if ($key === 'blocked_students' && !$includeBlockedStudents) {
            continue;
        }

        $targetList = $target[$key] ?? [];
        $sourceList = $source[$key] ?? [];

        if (!is_array($targetList) || !is_array($sourceList)) {
            continue;
        }

        if (empty($targetList) && !empty($sourceList)) {
            $target[$key] = $sourceList;
            continue;
        }

        if ($key === 'blocked_students') {
            foreach ($sourceList as $blockedStudent) {
                if (!isStudentBlocked($target, $blockedStudent['student_name'] ?? null, $blockedStudent['student_nim'] ?? null)) {
                    $target['blocked_students'][] = $blockedStudent;
                }
            }
        }
    }

    if (!isset($target['exam_duration_seconds']) && isset($source['exam_duration_seconds'])) {
        $target['exam_duration_seconds'] = $source['exam_duration_seconds'];
    }

    return $target;
}

function migrateBackendDataToSharedStore(string $sharedPath): void
{
    $localPath = localBackendDataPath();
    $candidates = array_values(array_unique(array_filter([$localPath, $sharedPath])));

    $payloads = [];
    foreach ($candidates as $candidate) {
        if (!is_string($candidate) || !file_exists($candidate)) {
            continue;
        }

        $decoded = json_decode((string) file_get_contents($candidate), true);
        if (is_array($decoded)) {
            $payloads[] = $decoded;
        }
    }

    if (empty($payloads)) {
        return;
    }

    $merged = array_shift($payloads);
    foreach ($payloads as $payload) {
        $merged = mergeBackendPayload($merged, $payload);
    }

    if (!is_dir(dirname($sharedPath))) {
        mkdir(dirname($sharedPath), 0777, true);
    }

    file_put_contents($sharedPath, json_encode($merged, JSON_PRETTY_PRINT));
}

function backendDataPath(): string
{
    $sharedDir = aegisSharedDataDir();
    if (!is_dir($sharedDir)) {
        mkdir($sharedDir, 0777, true);
    }

    $sharedPath = rtrim($sharedDir, '\\/').DIRECTORY_SEPARATOR.'backend_data.json';

    if (!file_exists($sharedPath)) {
        migrateBackendDataToSharedStore($sharedPath);
    } elseif (file_exists(localBackendDataPath())) {
        $shared = json_decode((string) file_get_contents($sharedPath), true) ?: [];
        $local = json_decode((string) file_get_contents(localBackendDataPath()), true) ?: [];
        // Jangan gabungkan blocked_students dari file lokal — supaya "Buka Blokir" tidak muncul lagi.
        $merged = mergeBackendPayload($shared, $local, false);
        $local['blocked_students'] = $shared['blocked_students'] ?? [];
        file_put_contents(localBackendDataPath(), json_encode($local, JSON_PRETTY_PRINT));

        if (json_encode($merged) !== json_encode($shared)) {
            file_put_contents($sharedPath, json_encode($merged, JSON_PRETTY_PRINT));
        }
    }

    return $sharedPath;
}

function readBackendData(): array
{
    $path = backendDataPath();
    if (!file_exists($path)) {
        $initial = [
            'question_codes' => [],
            'questions' => [],
            'exam_duration_seconds' => 7200,
            'blocked_students' => [],
            'violation_logs' => [],
            'students' => [],
            'students_list' => [],
            'teachers_list' => [],
            'users_list' => [
                [
                    'username' => 'admin',
                    'name' => 'Administrator',
                    'role' => 'admin',
                    'password' => 'admin',
                    'created_at' => date('c'),
                ]
            ],
        ];
        file_put_contents($path, json_encode($initial, JSON_PRETTY_PRINT));
        return $initial;
    }

    $json = file_get_contents($path);
    $data = json_decode($json, true) ?: [];
    $changed = false;

    if (!isset($data['question_codes']) || !is_array($data['question_codes'])) {
        $data['question_codes'] = [];
        $changed = true;
    }

    if (!isset($data['questions']) || !is_array($data['questions'])) {
        $data['questions'] = [];
        $changed = true;
    }

    if (!isset($data['students_list']) || !is_array($data['students_list'])) {
        $data['students_list'] = [];
        $changed = true;
    }

    if (!isset($data['teachers_list']) || !is_array($data['teachers_list'])) {
        $data['teachers_list'] = [];
        $changed = true;
    }

    if (!isset($data['users_list']) || !is_array($data['users_list'])) {
        $data['users_list'] = [
            [
                'username' => 'admin',
                'name' => 'Administrator',
                'role' => 'admin',
                'password' => 'admin',
                'created_at' => date('c'),
            ]
        ];
        $changed = true;
    }

    if (!empty($data['questions']) && empty($data['question_codes'])) {
        $legacyCodeId = 'code_umum';
        $data['question_codes'][] = [
            'id' => $legacyCodeId,
            'course_code' => 'UMUM',
            'course_name' => 'Bank Soal Umum',
            'teacher_name' => 'Dosen',
            'created_at' => date('c'),
        ];

        foreach ($data['questions'] as &$question) {
            $question['code_id'] = $question['code_id'] ?? $legacyCodeId;
            $question['course_code'] = $question['course_code'] ?? 'UMUM';
            $question['course_name'] = $question['course_name'] ?? 'Bank Soal Umum';
        }
        unset($question);
        $changed = true;
    }

    if ($changed) {
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    return $data;
}

function writeBackendData(array $data)
{
    file_put_contents(backendDataPath(), json_encode($data, JSON_PRETTY_PRINT));
}

function findStudentIndex(array $students, ?string $studentName, ?string $studentNim): ?int
{
    foreach ($students as $index => $student) {
        if ($studentNim !== null && ($student['student_nim'] ?? null) === $studentNim) {
            return $index;
        }

        if ($studentNim === null && $studentName !== null && ($student['student_name'] ?? null) === $studentName) {
            return $index;
        }
    }

    return null;
}

function studentExamStatus(array $student): string
{
    if (!empty($student['last_result'])) {
        return 'selesai';
    }

    if (!empty($student['started_at']) || !empty($student['last_seen'])) {
        return 'aktif';
    }

    return 'belum_mulai';
}

function isStudentBlocked(array $backend, ?string $studentName, ?string $studentNim): bool
{
    foreach ($backend['blocked_students'] ?? [] as $blockedStudent) {
        if ($studentNim !== null && ($blockedStudent['student_nim'] ?? null) === $studentNim) {
            return true;
        }

        if ($studentNim === null && $studentName !== null && ($blockedStudent['student_name'] ?? null) === $studentName) {
            return true;
        }
    }

    return false;
}

function findQuestionCodeById(array $backend, ?string $codeId): ?array
{
    foreach ($backend['question_codes'] ?? [] as $code) {
        if (($code['id'] ?? null) === $codeId) {
            return $code;
        }
    }

    return null;
}

function findQuestionCodeByCourseCode(array $backend, string $courseCode): ?array
{
    $normalized = strtoupper(trim($courseCode));

    foreach ($backend['question_codes'] ?? [] as $code) {
        if (strtoupper(trim((string) ($code['course_code'] ?? ''))) === $normalized) {
            return $code;
        }
    }

    return null;
}

function backendDashboardView(string $activeTab)
{
    if (!session('backend_user')) return redirect('/backend');

    $data = readBackendData();

    return view('backend.dashboard', [
        'backend' => $data,
        'teacher' => session('backend_user'),
        'activeTab' => $activeTab,
    ]);
}

// Halaman login untuk memasukkan nama + NIM sebelum masuk ke simulasi
Route::get('/', function () {
    return view('exam.login');
});

// Terima form login, simpan ke session, lalu buka halaman input kode
Route::post('/login', function (Request $request) {
    $request->validate([
        'student_name' => 'required|string|max:120',
        'student_nim' => 'required|string|max:50',
        'student_email' => 'required|email|max:120',
    ]);

    $backend = readBackendData();
    $studentNim = trim((string) $request->input('student_nim'));
    $studentName = trim((string) $request->input('student_name'));
    $studentEmail = trim((string) $request->input('student_email'));

    $registeredStudent = collect($backend['students_list'] ?? [])->first(function($s) use ($studentNim, $studentEmail) {
        return strtoupper(trim($s['nim'] ?? '')) === strtoupper($studentNim)
            && strtolower(trim($s['email'] ?? '')) === strtolower($studentEmail);
    });

    if (!$registeredStudent) {
        return back()
            ->withErrors(['student_nim' => 'NIM atau Email Anda tidak terdaftar dalam sistem. Hubungi administrator.'])
            ->withInput();
    }

    session([
        'student_name' => $registeredStudent['name'] ?? $studentName,
        'student_nim' => $registeredStudent['nim'] ?? $studentNim,
        'student_email' => $registeredStudent['email'] ?? $studentEmail,
    ]);

    session()->forget('exam_code_id');

    return redirect('/exam/code');
});

Route::get('/logout', function () {
    session()->forget([
        'student_name',
        'student_nim',
        'exam_code_id',
        'exam_result',
        'violation_count',
        'exam_started_at',
    ]);

    return redirect('/');
});

Route::get('/exam/code', function () {
    if (!session('student_name')) {
        return redirect('/');
    }

    return view('exam.code');
});

Route::post('/exam/code', function (Request $request) {
    if (!session('student_name')) {
        return redirect('/');
    }

    $request->validate([
        'course_code' => 'required|string|max:40',
    ]);

    $backend = readBackendData();
    $code = findQuestionCodeByCourseCode($backend, $request->input('course_code'));

    if (!$code) {
        return back()
            ->withErrors(['course_code' => 'Kode ujian tidak ditemukan. Pastikan kode sesuai dengan yang diberikan dosen.'])
            ->withInput();
    }

    $questionCount = count(array_values(array_filter(
        $backend['questions'] ?? [],
        fn ($question) => ($question['code_id'] ?? null) === ($code['id'] ?? null),
    )));

    if ($questionCount === 0) {
        return back()
            ->withErrors(['course_code' => 'Kode ujian valid, tetapi dosen belum menambahkan soal. Hubungi dosen lalu coba lagi.'])
            ->withInput();
    }

    session(['exam_code_id' => $code['id']]);

    return redirect('/exam');
});

// Halaman ujian setelah login
Route::get('/exam', function () {
    $exam = simExam();
    $backend = readBackendData();
    $student = currentStudentIdentity();

    if (!session('student_name')) {
        return redirect('/');
    }

    $selectedQuestionCode = findQuestionCodeById($backend, session('exam_code_id'));

    if (!$selectedQuestionCode) {
        return redirect('/exam/code');
    }

    $exam->title = $selectedQuestionCode['course_name'] ?? $exam->title;

    if (isStudentBlocked($backend, $student['student_name'], $student['student_nim'])) {
        return redirect('/blocked');
    }

    $studentIndex = findStudentIndex($backend['students'] ?? [], $student['student_name'], $student['student_nim']);
    $violationCount = $studentIndex !== null
        ? (int) ($backend['students'][$studentIndex]['violation_count'] ?? 0)
        : simViolationCount();
    $questionsPayload = [];

    $selectedQuestions = array_values(array_filter($backend['questions'] ?? [], function ($question) use ($selectedQuestionCode) {
        return ($question['code_id'] ?? null) === ($selectedQuestionCode['id'] ?? null);
    }));

    if (!empty($selectedQuestions)) {
        $questionsPayload = array_map(function ($q, $idx) {
            return [
                'id' => $q['id'] ?? ($idx + 1),
                'number' => $idx + 1,
                'text' => $q['text'],
                'options' => is_array($q['options']) ? $q['options'] : [],
            ];
        }, $selectedQuestions, array_keys($selectedQuestions));
    }

    if (empty($questionsPayload) && !empty($backend['question_codes'])) {
        return redirect('/exam/code')->withErrors([
            'course_code' => 'Belum ada soal untuk kode ujian ini. Minta dosen menambahkan soal di dashboard.',
        ]);
    }

    if (empty($questionsPayload) && empty($selectedQuestions) && empty($backend['question_codes'])) {
        $mockQuestions = [
            1 => 'Apa kepanjangan dari CRUD dalam pengembangan perangkat lunak?',
            2 => 'Framework PHP apa yang digunakan pada proyek AegisExam ini?',
            3 => 'Protokol apa yang umum dipakai untuk komunikasi API berbasis web?',
            4 => 'Manakah yang merupakan tipe data primitif di JavaScript?',
            5 => 'Fungsi utama middleware pada Laravel adalah...',
            6 => 'Perintah artisan apa untuk menjalankan migrasi database?',
            7 => 'Apa fungsi Eloquent ORM pada Laravel?',
            8 => 'HTTP method mana yang digunakan untuk mengirim data form besar?',
            9 => 'Di mana file konfigurasi environment Laravel disimpan?',
            10 => 'Apa peran CSRF token pada aplikasi web?',
            11 => 'Manakah status code HTTP untuk "Not Found"?',
            12 => 'Apa kepanjangan REST dalam RESTful API?',
            13 => 'Teknologi CSS utility-first yang dipakai di halaman ini adalah...',
            14 => 'Library JavaScript apa yang dipakai untuk HTTP request di simulasi ini?',
            15 => 'Event browser apa yang terpicu saat user pindah tab?',
            16 => 'Apa fungsi session pada simulasi pelanggaran ini?',
            17 => 'Manakah perintah SQL untuk mengambil data?',
            18 => 'Apa perbedaan GET dan POST secara umum?',
            19 => 'Di Laravel, folder mana yang menyimpan view Blade?',
            20 => 'Apa fungsi route di aplikasi Laravel?',
            21 => 'Manakah yang termasuk prinsip OOP?',
            22 => 'Apa itu foreign key pada database relasional?',
            23 => 'Format pertukaran data ringan yang umum di API modern?',
            24 => 'Apa kegunaan Git dalam pengembangan software?',
            25 => 'Manakah tag HTML untuk hyperlink?',
            26 => 'Apa fungsi index pada database?',
            27 => 'Teknik keamanan untuk mencegah injeksi SQL disebut...',
            28 => 'Apa itu responsive web design?',
            29 => 'Di CBT, warna hijau pada nomor soal biasanya berarti...',
            30 => 'Berapa batas pelanggaran pada simulasi AegisExam ini?',
        ];
        $mockOptions = [
            'A' => 'Jawaban pilihan A (benar untuk demo)',
            'B' => 'Jawaban pilihan B',
            'C' => 'Jawaban pilihan C',
            'D' => 'Jawaban pilihan D',
            'E' => 'Jawaban pilihan E',
        ];
        $questionsPayload = collect($mockQuestions)->map(function ($text, $num) use ($mockOptions) {
            return [
                'id' => $num,
                'number' => $num,
                'text' => $text,
                'options' => $mockOptions,
            ];
        })->values()->all();
    }

    $examDuration = $backend['exam_duration_seconds'] ?? 7200;

    return view('exam.take', compact('exam', 'violationCount', 'questionsPayload', 'examDuration', 'selectedQuestionCode'));
});

// API simulasi: catat pelanggaran (pindah tab / blur)
Route::post('/api/exam/violation', function (Request $request) {
    $backend = readBackendData();
    $studentName = $request->input('student_name') ?: session('student_name') ?: 'Unknown';
    $studentNim = $request->input('student_nim') ?: session('student_nim') ?: null;
    $violationType = $request->input('violation_type', 'general');
    $violationMessage = $request->input('violation_message', 'Pelanggaran terdeteksi oleh sistem pengawasan ujian.');
    $snapshotImage = $request->input('snapshot_image');
    $studentIndex = findStudentIndex($backend['students'] ?? [], $studentName, $studentNim);
    $count = $studentIndex !== null
        ? ((int) ($backend['students'][$studentIndex]['violation_count'] ?? 0) + 1)
        : 1;
    $isBlocked = $count >= simMaxViolation();
    session(['violation_count' => $count]);

    $backend['violation_logs'][] = [
        'student_name' => $studentName,
        'student_nim' => $studentNim,
        'timestamp' => date('c'),
        'violation_count' => $count,
        'violation_type' => $violationType,
        'violation_message' => $violationMessage,
        'snapshot_image' => $snapshotImage,
        'is_blocked' => $isBlocked,
    ];

    // Update or create student record
    if ($studentIndex !== null) {
        $backend['students'][$studentIndex]['student_name'] = $studentName;
        $backend['students'][$studentIndex]['student_nim'] = $studentNim;
        $backend['students'][$studentIndex]['violation_count'] = $count;
        $backend['students'][$studentIndex]['last_violation_at'] = date('c');
        $backend['students'][$studentIndex]['last_seen'] = date('c');
        if ($snapshotImage) {
            $backend['students'][$studentIndex]['last_snapshot'] = $snapshotImage;
        }
    } else {
        $backend['students'][] = [
            'student_name' => $studentName,
            'student_nim' => $studentNim,
            'started_at' => session('exam_started_at') ?: date('c'),
            'violation_count' => $count,
            'last_violation_at' => date('c'),
            'last_seen' => date('c'),
            'last_snapshot' => $snapshotImage,
        ];
    }

    // If blocked, record in blocked_students list
    if ($isBlocked && !isStudentBlocked($backend, $studentName, $studentNim)) {
        $backend['blocked_students'][] = [
            'student_name' => $studentName,
            'student_nim' => $studentNim,
            'blocked_at' => date('c'),
        ];
    }

    writeBackendData($backend);

    return response()->json([
        'violation_count' => $count,
        'is_blocked' => $isBlocked,
    ]);
});

// Student starts exam: register in backend students list
Route::post('/api/exam/start', function (Request $request) {
    $name = $request->input('student_name') ?: session('student_name');
    $nim = $request->input('student_nim') ?: session('student_nim');
    $backend = readBackendData();
    $now = date('c');

    if (isStudentBlocked($backend, $name, $nim)) {
        return response()->json([
            'ok' => false,
            'status' => 'blocked',
            'redirect' => url('/blocked'),
        ], 403);
    }

    // record start in session too
    session(['exam_started_at' => $now]);

    // create or update student entry
    $studentIndex = findStudentIndex($backend['students'] ?? [], $name, $nim);

    if ($studentIndex !== null) {
        $backend['students'][$studentIndex]['started_at'] = $now;
        $backend['students'][$studentIndex]['student_name'] = $name;
        $backend['students'][$studentIndex]['student_nim'] = $nim;
        $backend['students'][$studentIndex]['last_seen'] = $now;
        $backend['students'][$studentIndex]['exam_status'] = 'aktif';
        unset($backend['students'][$studentIndex]['last_result']);
    } else {
        $backend['students'][] = [
            'student_name' => $name,
            'student_nim' => $nim,
            'started_at' => $now,
            'last_seen' => $now,
            'violation_count' => 0,
            'exam_status' => 'aktif',
        ];
    }

    writeBackendData($backend);

    return response()->json(['ok' => true, 'exam_duration_seconds' => $backend['exam_duration_seconds'] ?? 7200]);
});

// Snapshot endpoint: student uploads periodic base64 snapshot
Route::post('/api/exam/snapshot', function (Request $request) {
    $name = $request->input('student_name') ?: session('student_name');
    $nim = $request->input('student_nim') ?: session('student_nim');
    $image = $request->input('image');

    if (!$image) return response()->json(['error' => 'no image'], 400);

    $backend = readBackendData();
    $now = date('c');
    $found = false;
    foreach ($backend['students'] as &$s) {
        if (($s['student_nim'] ?? null) === $nim && $nim !== null) {
            $s['last_snapshot'] = $image;
            $s['last_seen'] = $now;
            $found = true;
            break;
        }
        if ($nim === null && ($s['student_name'] ?? null) === $name) {
            $s['last_snapshot'] = $image;
            $s['last_seen'] = $now;
            $found = true;
            break;
        }
    }
    unset($s);

    if (!$found) {
        $backend['students'][] = [
            'student_name' => $name,
            'student_nim' => $nim,
            'started_at' => session('exam_started_at') ?: $now,
            'last_snapshot' => $image,
            'last_seen' => $now,
            'violation_count' => 0,
        ];
    }

    writeBackendData($backend);
    return response()->json(['ok' => true]);
});

Route::post('/api/exam/submit', function (Request $request) {
    if (!session('student_name')) {
        return response()->json(['error' => 'unauthenticated'], 401);
    }

    $backend = readBackendData();
    $selectedQuestionCode = findQuestionCodeById($backend, session('exam_code_id'));

    if (!$selectedQuestionCode) {
        return response()->json(['error' => 'exam code not selected'], 422);
    }

    $answers = $request->input('answers', []);
    if (!is_array($answers)) {
        $answers = [];
    }

    $selectedQuestions = array_values(array_filter($backend['questions'] ?? [], function ($question) use ($selectedQuestionCode) {
        return ($question['code_id'] ?? null) === ($selectedQuestionCode['id'] ?? null);
    }));

    $correctCount = 0;
    $wrongCount = 0;
    $totalQuestions = count($selectedQuestions);

    foreach ($selectedQuestions as $question) {
        $questionId = (string) ($question['id'] ?? '');
        $studentAnswer = strtoupper(trim((string) ($answers[$questionId] ?? '')));
        $correctAnswer = strtoupper(trim((string) ($question['correct_answer'] ?? '')));

        if ($correctAnswer !== '' && $studentAnswer === $correctAnswer) {
            $correctCount++;
        } else {
            $wrongCount++;
        }
    }

    $score = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100, 2) : 0;
    $now = now();
    $result = [
        'student_name' => session('student_name'),
        'student_nim' => session('student_nim'),
        'course_code' => $selectedQuestionCode['course_code'] ?? '-',
        'course_name' => $selectedQuestionCode['course_name'] ?? '-',
        'teacher_name' => $selectedQuestionCode['teacher_name'] ?? 'Dosen',
        'finished_at' => $now->toIso8601String(),
        'finished_time' => $now->format('H:i:s'),
        'finished_date' => $now->format('d-m-Y'),
        'total_questions' => $totalQuestions,
        'correct_count' => $correctCount,
        'wrong_count' => $wrongCount,
        'score' => $score,
    ];

    session(['exam_result' => $result]);

    $data = $backend;
    $data['exam_results'][] = $result;

    $studentIndex = findStudentIndex($data['students'] ?? [], session('student_name'), session('student_nim'));
    if ($studentIndex !== null) {
        $data['students'][$studentIndex]['last_result'] = $result;
        $data['students'][$studentIndex]['last_seen'] = $now->toIso8601String();
        $data['students'][$studentIndex]['exam_status'] = 'selesai';
    } else {
        $data['students'][] = [
            'student_name' => session('student_name'),
            'student_nim' => session('student_nim'),
            'started_at' => session('exam_started_at') ?: $now->toIso8601String(),
            'last_seen' => $now->toIso8601String(),
            'violation_count' => 0,
            'last_result' => $result,
            'exam_status' => 'selesai',
        ];
    }

    writeBackendData($data);

    return response()->json([
        'ok' => true,
        'redirect' => url('/exam/result'),
    ]);
});

Route::get('/api/exam/status', function () {
    $backend = readBackendData();
    $student = currentStudentIdentity();
    $studentIndex = findStudentIndex($backend['students'] ?? [], $student['student_name'], $student['student_nim']);
    $violationCount = $studentIndex !== null
        ? (int) ($backend['students'][$studentIndex]['violation_count'] ?? 0)
        : simViolationCount();
    $isBlocked = isStudentBlocked($backend, $student['student_name'], $student['student_nim']);

    if (!$isBlocked) {
        session(['violation_count' => $violationCount]);
    }

    return response()->json([
        'is_blocked' => $isBlocked,
        'violation_count' => $violationCount,
        'redirect' => $isBlocked ? url('/blocked') : url('/exam'),
    ]);
});

Route::get('/exam/result', function () {
    $result = session('exam_result');

    if (!$result) {
        return redirect('/exam/code');
    }

    return view('exam.result', compact('result'));
});

// Halaman akses diblokir
Route::get('/blocked', function () {
    $exam = simExam();
    $backend = readBackendData();
    $student = currentStudentIdentity();
    $studentIndex = findStudentIndex($backend['students'] ?? [], $student['student_name'], $student['student_nim']);
    $violationCount = $studentIndex !== null
        ? (int) ($backend['students'][$studentIndex]['violation_count'] ?? 0)
        : simViolationCount();

    if (!isStudentBlocked($backend, $student['student_name'], $student['student_nim'])) {
        return redirect('/exam');
    }

    return view('exam.blocked', compact('exam', 'violationCount'));
});

// --- Backend (dosen) routes ---
Route::get('/backend/server-info', function () {
    if (!session('backend_user')) {
        return response()->json(['error' => 'unauthorized'], 401);
    }

    $lanUrl = getenv('AEGIS_LAN_URL') ?: request()->getSchemeAndHttpHost();
    $cloudUrl = getenv('AEGIS_CLOUD_URL') ?: null;

    return response()->json([
        'server_url' => $lanUrl,
        'cloud_url' => $cloudUrl,
        'local_url' => request()->getSchemeAndHttpHost(),
    ]);
});

Route::get('/backend', function () {
    return view('backend.login');
});

Route::post('/backend/login', function (Request $request) {
    $request->validate([
        'teacher_name' => 'required|string|max:120',
        'teacher_nip' => 'required|string|max:60',
        'teacher_email' => 'required|email|max:120',
    ]);

    $backend = readBackendData();
    $teacherNip = trim((string) $request->input('teacher_nip'));
    $teacherName = trim((string) $request->input('teacher_name'));
    $teacherEmail = trim((string) $request->input('teacher_email'));

    $registeredTeacher = collect($backend['teachers_list'] ?? [])->first(function($t) use ($teacherNip, $teacherEmail) {
        return strtoupper(trim($t['nip'] ?? '')) === strtoupper($teacherNip)
            && strtolower(trim($t['email'] ?? '')) === strtolower($teacherEmail);
    });

    if (!$registeredTeacher) {
        return back()
            ->withErrors(['teacher_nip' => 'NIP atau Email Anda tidak terdaftar dalam sistem. Hubungi administrator.'])
            ->withInput();
    }

    session([
        'backend_user' => [
            'name' => $registeredTeacher['name'] ?? $teacherName,
            'nip' => $registeredTeacher['nip'] ?? $teacherNip,
            'email' => $registeredTeacher['email'] ?? $teacherEmail,
        ]
    ]);

    return redirect('/backend/dashboard');
});

Route::get('/backend/logout', function () {
    session()->forget('backend_user');
    return redirect('/backend');
});

Route::get('/backend/dashboard', function () {
    return backendDashboardView('dashboard');
});

Route::get('/backend/dashboard/questions', function () {
    return backendDashboardView('questions');
});

Route::get('/backend/dashboard/duration', function () {
    return backendDashboardView('duration');
});

Route::get('/backend/dashboard/violations', function () {
    return backendDashboardView('violations');
});

Route::get('/backend/dashboard/students', function () {
    return backendDashboardView('students');
});

Route::get('/backend/dashboard/blocked', function () {
    return backendDashboardView('blocked');
});

Route::post('/backend/question-codes', function (Request $request) {
    if (!session('backend_user')) return redirect('/backend');

    $request->validate([
        'course_code' => 'required|string|max:40',
        'course_name' => 'required|string|max:120',
    ]);

    $data = readBackendData();
    $courseCode = strtoupper(trim((string) $request->input('course_code')));
    $courseName = trim((string) $request->input('course_name'));

    $newCode = [
        'id' => uniqid('code_'),
        'course_code' => $courseCode,
        'course_name' => $courseName,
        'teacher_name' => session('backend_user.name', 'Dosen'),
        'created_at' => date('c'),
    ];

    $data['question_codes'][] = $newCode;
    writeBackendData($data);

    return redirect('/backend/dashboard/questions?code=' . urlencode($newCode['id']));
});

Route::post('/backend/questions', function (Request $request) {
    if (!session('backend_user')) return redirect('/backend');

    $request->validate([
        'code_id' => 'required|string',
        'questions' => 'required|array|min:1',
        'questions.*.question' => 'nullable|string',
        'questions.*.options' => 'nullable|string',
        'questions.*.correct_answer' => 'nullable|string|in:A,B,C,D,E',
    ]);

    $data = readBackendData();
    $codeId = $request->input('code_id');
    $code = collect($data['question_codes'] ?? [])->firstWhere('id', $codeId);

    if (!$code) {
        return redirect('/backend/dashboard/questions')->with('error', 'Kode mata kuliah tidak ditemukan.');
    }

    $entries = collect($request->input('questions', []))
        ->map(function ($entry) use ($code) {
            $questionText = trim((string) ($entry['question'] ?? ''));
            $optionsRaw = (string) ($entry['options'] ?? '');
            $options = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $optionsRaw))));
            $correctAnswer = strtoupper(trim((string) ($entry['correct_answer'] ?? '')));

            if ($questionText === '') {
                return null;
            }

            return [
                'id' => uniqid('q_'),
                'code_id' => $code['id'],
                'course_code' => $code['course_code'],
                'course_name' => $code['course_name'],
                'text' => $questionText,
                'options' => $options,
                'correct_answer' => $correctAnswer,
                'created_at' => date('c'),
            ];
        })
        ->filter()
        ->values()
        ->all();

    if (empty($entries)) {
        return redirect('/backend/dashboard/questions?code=' . urlencode($code['id']))->with('error', 'Isi minimal satu pertanyaan.');
    }

    $data['questions'] = array_merge($data['questions'] ?? [], $entries);
    writeBackendData($data);

    return redirect('/backend/dashboard/questions?code=' . urlencode($code['id']));
});

Route::post('/backend/questions/delete', function (Request $request) {
    if (!session('backend_user')) return redirect('/backend');

    $request->validate([
        'question_id' => 'required|string',
        'code_id' => 'nullable|string',
    ]);

    $data = readBackendData();
    $questionId = (string) $request->input('question_id');
    $codeId = $request->input('code_id');

    $questionExists = false;
    $data['questions'] = array_values(array_filter($data['questions'] ?? [], function ($question) use ($questionId, &$questionExists) {
        if (($question['id'] ?? null) === $questionId) {
            $questionExists = true;
            return false;
        }
        return true;
    }));

    if (!$questionExists) {
        return redirect('/backend/dashboard/questions' . ($codeId ? '?code=' . urlencode($codeId) : ''))->with('error', 'Soal tidak ditemukan.');
    }

    writeBackendData($data);

    return redirect('/backend/dashboard/questions' . ($codeId ? '?code=' . urlencode($codeId) : ''));
});

Route::post('/backend/duration', function (Request $request) {
    if (!session('backend_user')) return redirect('/backend');

    $request->validate(['duration' => 'required|integer|min:60']);
    $data = readBackendData();
    $data['exam_duration_seconds'] = (int) $request->input('duration');
    writeBackendData($data);

    return redirect('/backend/dashboard/duration');
});

Route::post('/backend/blocked/clear-all', function () {
    if (!session('backend_user')) {
        return redirect('/backend');
    }

    $data = readBackendData();
    $data['blocked_students'] = [];

    foreach ($data['students'] ?? [] as $index => $student) {
        $data['students'][$index]['violation_count'] = 0;
        $data['students'][$index]['last_violation_at'] = null;
    }

    writeBackendData($data);

    $localPath = localBackendDataPath();
    if (file_exists($localPath)) {
        $local = json_decode((string) file_get_contents($localPath), true) ?: [];
        $local['blocked_students'] = [];
        foreach ($local['students'] ?? [] as $index => $student) {
            $local['students'][$index]['violation_count'] = 0;
            $local['students'][$index]['last_violation_at'] = null;
        }
        file_put_contents($localPath, json_encode($local, JSON_PRETTY_PRINT));
    }

    return redirect('/backend/dashboard/blocked')->with('success', 'Semua daftar akses diblokir telah dihapus.');
});

Route::post('/backend/unblock', function (Request $request) {
    if (!session('backend_user')) return redirect('/backend');

    $nim = $request->input('student_nim');
    $name = $request->input('student_name');
    $data = readBackendData();
    $data['blocked_students'] = array_values(array_filter($data['blocked_students'], function ($b) use ($nim, $name) {
        if ($nim !== null && $nim !== '') {
            return ($b['student_nim'] ?? null) !== $nim;
        }

        return ($b['student_name'] ?? null) !== $name;
    }));

    $studentIndex = findStudentIndex($data['students'] ?? [], $name, $nim ?: null);
    if ($studentIndex !== null) {
        $data['students'][$studentIndex]['violation_count'] = 0;
        $data['students'][$studentIndex]['last_violation_at'] = null;
    }

    writeBackendData($data);

    return redirect('/backend/dashboard/blocked');
});

// Expose logs as JSON for simple monitoring (protected)
Route::get('/backend/logs', function () {
    if (!session('backend_user')) return response()->json(['error' => 'unauthorized'], 401);
    return response()->json(readBackendData());
});

// Polling ringan untuk auto-update dashboard dosen (tanpa foto base64)
Route::get('/backend/monitor', function () {
    if (!session('backend_user')) {
        return response()->json(['error' => 'unauthorized'], 401);
    }

    $data = readBackendData();
    $violationLogs = array_slice($data['violation_logs'] ?? [], -50);

    foreach ($violationLogs as &$log) {
        unset($log['snapshot_image']);
    }
    unset($log);

    $students = array_map(function ($student) {
        $entry = $student;
        unset($entry['last_snapshot']);

        return $entry;
    }, $data['students'] ?? []);

    return response()->json([
        'blocked_students' => $data['blocked_students'] ?? [],
        'violation_logs' => $violationLogs,
        'students' => $students,
        'questions_count' => count($data['questions'] ?? []),
        'exam_duration_seconds' => (int) ($data['exam_duration_seconds'] ?? 7200),
        'updated_at' => date('c'),
    ]);
});

// --- Admin routes ---
function adminDashboardView(string $activeTab)
{
    if (!session('admin_user')) return redirect('/admin/login');

    $data = readBackendData();

    return view('admin.dashboard', [
        'backend' => $data,
        'admin' => session('admin_user'),
        'activeTab' => $activeTab,
    ]);
}

Route::get('/admin', function () {
    if (session('admin_user')) {
        return redirect('/admin/dashboard');
    }
    return redirect('/admin/login');
});

Route::get('/admin/login', function () {
    if (session('admin_user')) {
        return redirect('/admin/dashboard');
    }
    return view('admin.login');
});

Route::post('/admin/login', function (Request $request) {
    $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
    ]);

    $backend = readBackendData();
    $username = trim($request->input('username'));
    $password = $request->input('password');

    $user = collect($backend['users_list'] ?? [])->first(function ($u) use ($username) {
        return strtolower(trim($u['username'] ?? '')) === strtolower($username);
    });

    if (!$user || $user['password'] !== $password) {
        return back()->withErrors(['username' => 'Username atau password salah.'])->withInput();
    }

    session(['admin_user' => $user]);

    return redirect('/admin/dashboard');
});

Route::get('/admin/logout', function () {
    session()->forget('admin_user');
    return redirect('/admin/login');
});

Route::get('/admin/dashboard', function () {
    return adminDashboardView('dashboard');
});

Route::get('/admin/dashboard/mahasiswa', function () {
    return adminDashboardView('mahasiswa');
});

Route::get('/admin/dashboard/dosen', function () {
    return adminDashboardView('dosen');
});

Route::get('/admin/dashboard/users', function () {
    return adminDashboardView('users');
});

Route::get('/admin/dashboard/monitoring', function () {
    return adminDashboardView('monitoring');
});

Route::get('/admin/dashboard/reports', function () {
    return adminDashboardView('reports');
});

// CRUD Mahasiswa
Route::post('/admin/mahasiswa/store', function (Request $request) {
    if (!session('admin_user')) return redirect('/admin/login');

    $request->validate([
        'nim' => 'required|string|max:50',
        'name' => 'required|string|max:120',
        'email' => 'required|email|max:120',
    ]);

    $data = readBackendData();
    $nim = trim($request->input('nim'));
    $name = trim($request->input('name'));
    $email = trim($request->input('email'));

    // Check uniqueness
    $exists = collect($data['students_list'] ?? [])->contains(function ($s) use ($nim) {
        return strtoupper($s['nim'] ?? '') === strtoupper($nim);
    });

    if ($exists) {
        return back()->withErrors(['nim' => 'NIM sudah terdaftar.'])->withInput();
    }

    $data['students_list'][] = [
        'nim' => $nim,
        'name' => $name,
        'email' => $email,
        'created_at' => date('c'),
    ];

    writeBackendData($data);
    return redirect('/admin/dashboard/mahasiswa')->with('success', 'Mahasiswa berhasil ditambahkan.');
});

Route::post('/admin/mahasiswa/update', function (Request $request) {
    if (!session('admin_user')) return redirect('/admin/login');

    $request->validate([
        'old_nim' => 'required|string',
        'nim' => 'required|string|max:50',
        'name' => 'required|string|max:120',
        'email' => 'required|email|max:120',
    ]);

    $data = readBackendData();
    $oldNim = trim($request->input('old_nim'));
    $nim = trim($request->input('nim'));
    $name = trim($request->input('name'));
    $email = trim($request->input('email'));

    // Check uniqueness if nim is changed
    if (strtoupper($oldNim) !== strtoupper($nim)) {
        $exists = collect($data['students_list'] ?? [])->contains(function ($s) use ($nim) {
            return strtoupper($s['nim'] ?? '') === strtoupper($nim);
        });

        if ($exists) {
            return back()->withErrors(['nim' => 'NIM baru sudah terdaftar.'])->withInput();
        }
    }

    foreach ($data['students_list'] as &$student) {
        if (strtoupper($student['nim'] ?? '') === strtoupper($oldNim)) {
            $student['nim'] = $nim;
            $student['name'] = $name;
            $student['email'] = $email;
            break;
        }
    }
    unset($student);

    writeBackendData($data);
    return redirect('/admin/dashboard/mahasiswa')->with('success', 'Mahasiswa berhasil diperbarui.');
});

Route::post('/admin/mahasiswa/delete', function (Request $request) {
    if (!session('admin_user')) return redirect('/admin/login');

    $request->validate([
        'nim' => 'required|string',
    ]);

    $data = readBackendData();
    $nim = trim($request->input('nim'));

    $data['students_list'] = array_values(array_filter($data['students_list'] ?? [], function ($s) use ($nim) {
        return strtoupper($s['nim'] ?? '') !== strtoupper($nim);
    }));

    writeBackendData($data);
    return redirect('/admin/dashboard/mahasiswa')->with('success', 'Mahasiswa berhasil dihapus.');
});

// CRUD Dosen
Route::post('/admin/dosen/store', function (Request $request) {
    if (!session('admin_user')) return redirect('/admin/login');

    $request->validate([
        'nip' => 'required|string|max:60',
        'name' => 'required|string|max:120',
        'email' => 'required|email|max:120',
    ]);

    $data = readBackendData();
    $nip = trim($request->input('nip'));
    $name = trim($request->input('name'));
    $email = trim($request->input('email'));

    // Check uniqueness
    $exists = collect($data['teachers_list'] ?? [])->contains(function ($t) use ($nip) {
        return strtoupper($t['nip'] ?? '') === strtoupper($nip);
    });

    if ($exists) {
        return back()->withErrors(['nip' => 'NIP sudah terdaftar.'])->withInput();
    }

    $data['teachers_list'][] = [
        'nip' => $nip,
        'name' => $name,
        'email' => $email,
        'created_at' => date('c'),
    ];

    writeBackendData($data);
    return redirect('/admin/dashboard/dosen')->with('success', 'Dosen berhasil ditambahkan.');
});

Route::post('/admin/dosen/update', function (Request $request) {
    if (!session('admin_user')) return redirect('/admin/login');

    $request->validate([
        'old_nip' => 'required|string',
        'nip' => 'required|string|max:60',
        'name' => 'required|string|max:120',
        'email' => 'required|email|max:120',
    ]);

    $data = readBackendData();
    $oldNip = trim($request->input('old_nip'));
    $nip = trim($request->input('nip'));
    $name = trim($request->input('name'));
    $email = trim($request->input('email'));

    // Check uniqueness if NIP changed
    if (strtoupper($oldNip) !== strtoupper($nip)) {
        $exists = collect($data['teachers_list'] ?? [])->contains(function ($t) use ($nip) {
            return strtoupper($t['nip'] ?? '') === strtoupper($nip);
        });

        if ($exists) {
            return back()->withErrors(['nip' => 'NIP baru sudah terdaftar.'])->withInput();
        }
    }

    foreach ($data['teachers_list'] as &$teacher) {
        if (strtoupper($teacher['nip'] ?? '') === strtoupper($oldNip)) {
            $teacher['nip'] = $nip;
            $teacher['name'] = $name;
            $teacher['email'] = $email;
            break;
        }
    }
    unset($teacher);

    writeBackendData($data);
    return redirect('/admin/dashboard/dosen')->with('success', 'Dosen berhasil diperbarui.');
});

Route::post('/admin/dosen/delete', function (Request $request) {
    if (!session('admin_user')) return redirect('/admin/login');

    $request->validate([
        'nip' => 'required|string',
    ]);

    $data = readBackendData();
    $nip = trim($request->input('nip'));

    $data['teachers_list'] = array_values(array_filter($data['teachers_list'] ?? [], function ($t) use ($nip) {
        return strtoupper($t['nip'] ?? '') !== strtoupper($nip);
    }));

    writeBackendData($data);
    return redirect('/admin/dashboard/dosen')->with('success', 'Dosen berhasil dihapus.');
});

// CRUD Akun Pengguna (Users)
Route::post('/admin/users/store', function (Request $request) {
    if (!session('admin_user')) return redirect('/admin/login');

    $request->validate([
        'username' => 'required|string|max:50',
        'name' => 'required|string|max:120',
        'role' => 'required|string|in:admin,dosen',
        'password' => 'required|string|min:4',
    ]);

    $data = readBackendData();
    $username = strtolower(trim($request->input('username')));
    $name = trim($request->input('name'));
    $role = trim($request->input('role'));
    $password = $request->input('password');

    // Check uniqueness
    $exists = collect($data['users_list'] ?? [])->contains(function ($u) use ($username) {
        return strtolower($u['username'] ?? '') === $username;
    });

    if ($exists) {
        return back()->withErrors(['username' => 'Username sudah terdaftar.'])->withInput();
    }

    $data['users_list'][] = [
        'username' => $username,
        'name' => $name,
        'role' => $role,
        'password' => $password,
        'created_at' => date('c'),
    ];

    writeBackendData($data);
    return redirect('/admin/dashboard/users')->with('success', 'Akun Pengguna berhasil ditambahkan.');
});

Route::post('/admin/users/update', function (Request $request) {
    if (!session('admin_user')) return redirect('/admin/login');

    $request->validate([
        'old_username' => 'required|string',
        'username' => 'required|string|max:50',
        'name' => 'required|string|max:120',
        'role' => 'required|string|in:admin,dosen',
        'password' => 'nullable|string|min:4',
    ]);

    $data = readBackendData();
    $oldUsername = strtolower(trim($request->input('old_username')));
    $username = strtolower(trim($request->input('username')));
    $name = trim($request->input('name'));
    $role = trim($request->input('role'));
    $password = $request->input('password');

    // Check uniqueness if username changed
    if ($oldUsername !== $username) {
        $exists = collect($data['users_list'] ?? [])->contains(function ($u) use ($username) {
            return strtolower($u['username'] ?? '') === $username;
        });

        if ($exists) {
            return back()->withErrors(['username' => 'Username baru sudah terdaftar.'])->withInput();
        }
    }

    foreach ($data['users_list'] as &$user) {
        if (strtolower($user['username'] ?? '') === $oldUsername) {
            $user['username'] = $username;
            $user['name'] = $name;
            $user['role'] = $role;
            if ($password !== null && $password !== '') {
                $user['password'] = $password;
            }
            break;
        }
    }
    unset($user);

    // If currently logged in user is updated, update the session as well
    if (strtolower(session('admin_user.username')) === $oldUsername) {
        $updatedUser = collect($data['users_list'])->firstWhere('username', $username);
        session(['admin_user' => $updatedUser]);
    }

    writeBackendData($data);
    return redirect('/admin/dashboard/users')->with('success', 'Akun Pengguna berhasil diperbarui.');
});

Route::post('/admin/users/delete', function (Request $request) {
    if (!session('admin_user')) return redirect('/admin/login');

    $request->validate([
        'username' => 'required|string',
    ]);

    $data = readBackendData();
    $username = strtolower(trim($request->input('username')));

    // Cannot delete currently logged-in account
    if (strtolower(session('admin_user.username')) === $username) {
        return back()->withErrors(['username' => 'Anda tidak bisa menghapus akun yang sedang Anda gunakan saat ini.']);
    }

    $data['users_list'] = array_values(array_filter($data['users_list'] ?? [], function ($u) use ($username) {
        return strtolower($u['username'] ?? '') !== $username;
    }));

    writeBackendData($data);
    return redirect('/admin/dashboard/users')->with('success', 'Akun Pengguna berhasil dihapus.');
});

// CSV Export for Reports
Route::get('/admin/reports/export', function () {
    if (!session('admin_user')) return redirect('/admin/login');

    $data = readBackendData();
    $results = $data['exam_results'] ?? [];

    $filename = "laporan-nilai-aegisexam-" . date('Ymd-His') . ".csv";

    $headers = [
        "Content-type"        => "text/csv; charset=UTF-8",
        "Content-Disposition" => "attachment; filename=$filename",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $columns = ['NIM', 'Nama Mahasiswa', 'Kode Ujian/Mata Kuliah', 'Nama Mata Kuliah', 'Dosen Pengampu', 'Tanggal', 'Jam Selesai', 'Total Soal', 'Jawaban Benar', 'Jawaban Salah', 'Nilai'];

    $callback = function() use($results, $columns) {
        $file = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($file, $columns);

        foreach ($results as $result) {
            fputcsv($file, [
                $result['student_nim'] ?? '-',
                $result['student_name'] ?? '-',
                $result['course_code'] ?? '-',
                $result['course_name'] ?? '-',
                $result['teacher_name'] ?? '-',
                $result['finished_date'] ?? '-',
                $result['finished_time'] ?? '-',
                $result['total_questions'] ?? 0,
                $result['correct_count'] ?? 0,
                $result['wrong_count'] ?? 0,
                $result['score'] ?? 0
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
});
