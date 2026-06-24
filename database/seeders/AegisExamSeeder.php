<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AegisExamSeeder extends Seeder
{
    public function run(): void
    {
        $mahasiswa = User::query()->create([
            'name' => 'Mahasiswa Demo',
            'email' => 'mahasiswa@aegisexam.test',
            'password' => Hash::make('password'),
            'role' => 'mahasiswa',
        ]);

        User::query()->create([
            'name' => 'Admin Demo',
            'email' => 'admin@aegisexam.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $exam = Exam::query()->create([
            'title' => 'Ujian Lomba Gemastik — AegisExam',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHours(3),
            'max_violation' => 3,
        ]);

        Question::query()->create([
            'exam_id' => $exam->id,
            'question_text' => 'Apa kepanjangan dari CRUD?',
            'correct_answer' => 'Create Read Update Delete',
        ]);

        Question::query()->create([
            'exam_id' => $exam->id,
            'question_text' => 'Framework PHP apa yang digunakan pada proyek ini?',
            'correct_answer' => 'Laravel',
        ]);

        $this->command?->info("Login mahasiswa: {$mahasiswa->email} / password");
        $this->command?->info("URL ujian: /exam/{$exam->id}");
    }
}
