<?php

namespace Database\Seeders;

use App\Models\Decision;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\EventSeries;
use App\Models\Faculty;
use App\Models\Lesson;
use App\Models\Meeting;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\StudyProgram;
use App\Models\Task;
use App\Models\University;
use App\Models\UniversityMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EvaloopDemoSeeder extends Seeder
{
    public const UNIVERSITY_CODE = 'EVALOOP-DEMO-UDAYANA';
    public const LABEL = '[TECFEST] ';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('EvaloopDemoSeeder hanya boleh dijalankan pada APP_ENV=local/testing.');
        }
        $password = config('evaloop.seed_password');
        if (! is_string($password) || mb_strlen($password) < 12 || strlen($password) > 72 || trim($password) === '') {
            throw new RuntimeException('Tetapkan EVALOOP_SEED_PASSWORD lokal: minimal 12 karakter, maksimal 72 byte. Tidak ada password default. Jalankan config:clear setelah mengubah .env.');
        }

        DB::transaction(function () use ($password) {
            if (University::where('code', '!=', self::UNIVERSITY_CODE)->exists()) {
                throw new RuntimeException('Universitas non-fixture sudah tersedia. Seeder tidak membuat universitas pilot kedua atau mengambil alih data tersebut.');
            }
            $university = $this->fixture(University::class, ['code' => self::UNIVERSITY_CODE], ['name' => self::LABEL.'Universitas Udayana']);
            // Serialize repeat runs against the same fixture parent.
            University::whereKey($university->id)->lockForUpdate()->firstOrFail();
            if ($university->organizations()->whereIn('name', [
                '[SIMULASI] Himpunan Mahasiswa Informatika',
                '[SIMULASI] Himpunan Mahasiswa Teknik Elektro',
                '[SIMULASI] Himpunan Mahasiswa Teknologi Informasi (HMTI)',
                '[SIMULASI] Himpunan Mahasiswa Elektro (HME)',
            ])->exists()) {
                throw new RuntimeException('Fixture organisasi versi lama masih tersedia. Gunakan database pengujian terpisah untuk seed HMTI/HME.');
            }
            $tech = $this->fixture(Faculty::class, ['university_id' => $university->id, 'code' => 'DEMO-FT'], ['name' => self::LABEL.'Fakultas Teknik']);
            $science = $this->fixture(Faculty::class, ['university_id' => $university->id, 'code' => 'DEMO-FMIPA'], ['name' => self::LABEL.'Fakultas Matematika dan Ilmu Pengetahuan Alam']);
            $programs = [];
            foreach ([
                'TI' => [$tech, 'Teknologi Informasi'], 'EL' => [$tech, 'Teknik Elektro'],
                'ME' => [$tech, 'Teknik Mesin'], 'MA' => [$science, 'Matematika'], 'BI' => [$science, 'Biologi'],
            ] as $code => [$faculty, $name]) {
                $programs[$code] = $this->fixture(StudyProgram::class,
                    ['faculty_id' => $faculty->id, 'code' => 'DEMO-'.$code], ['name' => self::LABEL.$name]);
            }
            $organizations = [];
            foreach ([
                'ti' => ['Himpunan Mahasiswa Teknologi Informasi (HMTI)', $tech->id, $programs['TI']->id],
                'el' => ['Himpunan Mahasiswa Elektro (HME)', $tech->id, $programs['EL']->id],
            ] as $key => [$name, $facultyId, $programId]) {
                $organizations[$key] = $this->fixture(Organization::class,
                    ['university_id' => $university->id, 'name' => self::LABEL.$name],
                    ['faculty_id' => $facultyId, 'study_program_id' => $programId]);
                if ($organizations[$key]->faculty_id !== $facultyId || $organizations[$key]->study_program_id !== $programId) {
                    throw new RuntimeException('Lingkup organisasi fixture berubah. Review manual diperlukan; tidak ditimpa.');
                }
            }
            $users = [];
            foreach ([
                'platform' => ['admin.platform', 'Platform Admin', null],
                'university' => ['admin.university', 'University Admin', null],
                'ti' => ['admin.informatika', 'Admin HMTI', $programs['TI']->id],
                'el' => ['admin.elektro', 'Admin HME', $programs['EL']->id],
                'leader' => ['ketua.lomba2027', 'Ketua Innovatech Challenge 2027', $programs['TI']->id],
                'member' => ['anggota.lomba2027', 'Anggota Innovatech Challenge 2027', $programs['TI']->id],
                'student' => ['mahasiswa', 'Mahasiswa Biasa', $programs['MA']->id],
            ] as $key => [$localPart, $name, $programId]) {
                $user = $this->fixture(User::class, ['email' => $localPart.'@evaloop.test'],
                    ['name' => self::LABEL.$name, 'password' => $password]);
                if ($user->wasRecentlyCreated) {
                    // Trusted local seeder only; neither field is accepted from registration.
                    $user->forceFill(['is_platform_admin' => $key === 'platform', 'study_program_id' => $programId])->save();
                }
                $users[$key] = $user;
            }
            $this->fixture(UniversityMember::class,
                ['university_id' => $university->id, 'user_id' => $users['university']->id],
                ['role' => 'admin', 'status' => 'active', 'joined_at' => '2026-01-01 00:00:00']);
            foreach ([['ti', 'ti', 'admin', 'active'], ['el', 'el', 'admin', 'active'],
                ['ti', 'leader', 'member', 'active'], ['ti', 'member', 'member', 'inactive']] as [$org, $user, $role, $status]) {
                $this->fixture(OrganizationMember::class,
                    ['organization_id' => $organizations[$org]->id, 'user_id' => $users[$user]->id],
                    ['role' => $role, 'status' => $status, 'joined_at' => '2026-01-01 00:00:00',
                        'ended_at' => $status === 'inactive' ? '2026-07-01 00:00:00' : null]);
            }
            $series = $this->fixture(EventSeries::class,
                ['organization_id' => $organizations['ti']->id, 'name' => self::LABEL.'Innovatech Challenge']);
            // All date strings below are UTC; the overlap pair is 09:00–12:00 / 10:00–13:00 WITA.
            $events = [];
            foreach ([
                '2026' => ['ti', 'Innovatech Challenge 2026', 'completed', '2026-05-16 01:00:00', '2026-05-16 08:00:00', $series->id, '2026-04-01 00:00:00', ['TI', 'EL']],
                '2027' => ['ti', 'Innovatech Challenge 2027', 'draft', '2027-05-15 01:00:00', '2027-05-15 08:00:00', $series->id, null, ['TI', 'EL']],
                'seminar' => ['el', 'Seminar Teknologi', 'published', '2027-05-15 01:00:00', '2027-05-15 04:00:00', null, '2026-11-01 00:00:00', ['TI', 'EL']],
                'workshop' => ['ti', 'Workshop AI', 'published', '2027-05-15 02:00:00', '2027-05-15 05:00:00', null, '2026-11-01 00:00:00', ['TI', 'ME']],
                'cancelled' => ['el', 'Kompetisi Inovasi Mahasiswa', 'cancelled', '2027-05-15 02:30:00', '2027-05-15 04:30:00', null, '2026-11-01 00:00:00', ['TI', 'MA', 'BI']],
            ] as $key => [$org, $title, $status, $start, $end, $seriesId, $published, $targets]) {
                $event = $this->fixture(Event::class,
                    ['organization_id' => $organizations[$org]->id, 'title' => self::LABEL.$title],
                    ['event_series_id' => $seriesId, 'description' => self::LABEL.'Dataset demo TecFest untuk Evaloop.',
                        'starts_at' => $start, 'ends_at' => $end, 'venue' => self::LABEL.'Aula Teknik',
                        'status' => $status, 'published_at' => $published]);
                $event->targets()->syncWithoutDetaching(array_map(fn ($code) => $programs[$code]->id, $targets));
                $events[$key] = $event;
            }
            // Extra manual cases: D (time only), G (touching boundary). Existing fixtures are not updated.
            foreach ([
                ['el', 'Diskusi Matematika', '2027-05-15 02:30:00', '2027-05-15 03:30:00', self::LABEL.'Ruang FMIPA', ['MA']],
                ['ti', 'Klinik Biologi', '2027-05-15 04:00:00', '2027-05-15 05:00:00', self::LABEL.'Aula Teknik', ['TI', 'BI']],
            ] as [$org, $title, $start, $end, $venue, $targets]) {
                $comparison = $this->fixture(Event::class,
                    ['organization_id' => $organizations[$org]->id, 'title' => self::LABEL.$title],
                    ['description' => self::LABEL.'Dataset demo TecFest untuk pembandingan jadwal.',
                        'starts_at' => $start, 'ends_at' => $end, 'venue' => $venue,
                        'status' => 'published', 'published_at' => '2026-11-01 00:00:00']);
                $comparison->targets()->syncWithoutDetaching(array_map(fn ($code) => $programs[$code]->id, $targets));
            }
            foreach ([['2026', 'ti', 'coordinator'], ['2026', 'el', 'member'],
                ['2027', 'leader', 'coordinator'], ['2027', 'member', 'member'],
                ['seminar', 'el', 'coordinator'], ['seminar', 'member', 'member'],
                ['workshop', 'ti', 'coordinator']] as [$event, $user, $role]) {
                $this->fixture(EventMember::class, ['event_id' => $events[$event]->id, 'user_id' => $users[$user]->id], ['role' => $role]);
            }
            $oldMeeting = $this->fixture(Meeting::class, ['event_id' => $events['2026']->id, 'title' => self::LABEL.'Evaluasi Innovatech Challenge 2026'],
                ['scheduled_at' => '2026-05-17 01:00:00', 'notes' => self::LABEL.'Panitia mengevaluasi antrean registrasi dan kesiapan perangkat presentasi. Jadwal pembukaan mundur 20 menit karena verifikasi peserta hanya dilayani di satu meja.']);
            $newMeeting = $this->fixture(Meeting::class, ['event_id' => $events['2027']->id, 'title' => self::LABEL.'Rapat Teknis Innovatech Challenge 2027'],
                ['scheduled_at' => '2026-11-21 01:00:00', 'notes' => self::LABEL.'Panitia membagi alur registrasi menjadi verifikasi peserta dan pengambilan kartu. Tim acara menjadwalkan gladi bersih untuk menguji perpindahan peserta ke ruang presentasi.']);
            $oldDecision = $this->fixture(Decision::class, ['meeting_id' => $oldMeeting->id, 'summary' => self::LABEL.'Perbaiki alur registrasi untuk edisi berikutnya.'],
                ['rationale' => 'Antrean registrasi memperlambat pembukaan acara.']);
            $newDecision = $this->fixture(Decision::class, ['meeting_id' => $newMeeting->id, 'summary' => self::LABEL.'Siapkan dua jalur registrasi dan gladi bersih.'],
                ['rationale' => 'Gunakan evaluasi yang telah disetujui dari edisi sebelumnya.']);
            $lesson = $this->fixture(Lesson::class,
                ['event_id' => $events['2026']->id, 'problem' => self::LABEL.'Registrasi peserta terlambat karena antrean panjang.'],
                ['created_by' => $users['ti']->id, 'recommendation' => 'Menyiapkan dua jalur registrasi dan melakukan gladi bersih sebelum kegiatan.',
                    'visibility' => 'series', 'approved_at' => '2026-05-20 00:00:00']);
            $this->fixture(Lesson::class,
                ['event_id' => $events['2026']->id, 'problem' => self::LABEL.'Dua sesi presentasi tertunda karena perangkat peserta belum diuji.'],
                ['created_by' => $users['ti']->id, 'recommendation' => 'Jadwalkan pemeriksaan perangkat setiap tim sebelum sesi dan siapkan satu laptop cadangan di meja operator.',
                    'visibility' => 'series', 'approved_at' => '2026-05-21 00:00:00']);
            $this->fixture(Lesson::class,
                ['event_id' => $events['2026']->id, 'problem' => self::LABEL.'Catatan koordinasi internal yang belum disetujui.'],
                ['created_by' => $users['ti']->id, 'recommendation' => 'Tinjau bersama pengurus sebelum dibagikan.', 'visibility' => 'event', 'approved_at' => null]);
            foreach ([
                ['2026', 'ti', $oldDecision->id, null, 'Arsipkan evaluasi 2026', 'done', '2026-05-18 09:00:00'],
                ['2027', 'member', $newDecision->id, $lesson->id, 'Rancang dua jalur registrasi', 'todo', '2027-05-01 09:00:00'],
                ['2027', 'leader', $newDecision->id, null, 'Jadwalkan gladi bersih panitia', 'in_progress', '2027-05-08 09:00:00'],
            ] as [$event, $user, $decisionId, $lessonId, $title, $status, $due]) {
                $this->fixture(Task::class, ['event_id' => $events[$event]->id, 'title' => self::LABEL.$title],
                    ['assignee_user_id' => $users[$user]->id, 'decision_id' => $decisionId, 'source_lesson_id' => $lessonId, 'status' => $status, 'due_at' => $due]);
            }
        }, 3);
        $this->command?->info('Dataset demo TecFest Evaloop siap. Data dan kata sandi yang sudah ada dipertahankan.');
    }

    /** First-or-create only: never update data already present, even fixture passwords/roles. */
    private function fixture(string $class, array $key, array $values = []): Model
    {
        if ($class::query()->where($key)->limit(2)->get()->count() > 1) {
            throw new RuntimeException('Identifier fixture ambigu. Seeder dibatalkan tanpa deduplikasi/penghapusan.');
        }
        $model = $class::firstOrCreate($key, $values);
        foreach (['name', 'title', 'problem', 'summary'] as $field) {
            if (array_key_exists($field, $model->getAttributes())
                && ! str_starts_with((string) $model->getAttribute($field), self::LABEL)) {
                throw new RuntimeException('Benturan identifier dengan data non-fixture. Seeder dibatalkan tanpa menimpa data.');
            }
        }

        return $model;
    }
}
