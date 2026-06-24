<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function show(Exam $exam): View
    {
        $examLog = ExamLog::query()
            ->where('user_id', auth()->id())
            ->where('exam_id', $exam->id)
            ->first();

        if ($examLog?->status === 'blocked') {
            return view('exam.blocked', compact('exam', 'examLog'));
        }

        $exam->load('questions');

        return view('exam.take', compact('exam', 'examLog'));
    }

    public function startExam(Request $request, Exam $exam): JsonResponse
    {
        $examLog = ExamLog::query()->firstOrNew([
            'user_id' => $request->user()->id,
            'exam_id' => $exam->id,
        ]);

        if ($examLog->exists && $examLog->status === 'blocked') {
            return response()->json([
                'message' => 'Ujian telah diblokir.',
                'status' => 'blocked',
                'redirect' => route('exam.blocked', $exam),
            ], 403);
        }

        $examLog->status = 'progress';
        $examLog->save();

        return response()->json([
            'message' => 'Ujian dimulai.',
            'status' => $examLog->status,
            'violation_count' => $examLog->violation_count,
            'max_violation' => $exam->max_violation,
        ]);
    }

    public function logViolation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'exam_id' => ['required', 'exists:exams,id'],
        ]);

        $exam = Exam::findOrFail($validated['exam_id']);

        $examLog = ExamLog::query()
            ->where('user_id', $request->user()->id)
            ->where('exam_id', $exam->id)
            ->first();

        if (! $examLog || $examLog->status !== 'progress') {
            return response()->json([
                'message' => 'Log ujian tidak aktif.',
                'status' => $examLog?->status ?? 'unknown',
            ], 422);
        }

        $examLog->increment('violation_count');
        $examLog->refresh();

        if ($examLog->violation_count >= $exam->max_violation) {
            $examLog->update(['status' => 'blocked']);

            return response()->json([
                'message' => 'Batas pelanggaran tercapai. Ujian diblokir.',
                'status' => 'blocked',
                'violation_count' => $examLog->violation_count,
                'max_violation' => $exam->max_violation,
                'redirect' => route('exam.blocked', $exam),
            ]);
        }

        return response()->json([
            'message' => 'Pelanggaran tercatat.',
            'status' => $examLog->status,
            'violation_count' => $examLog->violation_count,
            'max_violation' => $exam->max_violation,
        ]);
    }

    public function blocked(Exam $exam): View
    {
        $examLog = ExamLog::query()
            ->where('user_id', auth()->id())
            ->where('exam_id', $exam->id)
            ->firstOrFail();

        return view('exam.blocked', compact('exam', 'examLog'));
    }
}
