# AegisExam — Ujian Online (Laravel 10)

## Setup cepat

```bash
# Buat database MySQL bernama aegis_exam, lalu:
php artisan migrate:fresh --seed
php artisan serve
```

Login demo: `mahasiswa@aegisexam.test` / `password`  
Halaman ujian: `http://127.0.0.1:8000/exam/1`

## Struktur utama

| Komponen | Lokasi |
|----------|--------|
| Migrations | `database/migrations/` |
| Models | `app/Models/` |
| Controller | `app/Http/Controllers/ExamController.php` |
| Route web | `routes/web.php` |
| Route API pelanggaran | `routes/api.php` → `POST /api/exam/violation` |
| Blade ujian | `resources/views/exam/take.blade.php` |
