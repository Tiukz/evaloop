# Evaloop — Product Version Tracker

Scope aktif adalah aplikasi web responsif. Tabel dan catatan fase di bawah memuat riwayat verifikasi.

| Versi produk | Scope | Status |
| --- | --- | --- |
| v1.0 — Faculty Pilot | Satu fakultas, beberapa prodi/organisasi; empat modul inti | Baseline rancangan, bukan rilis terverifikasi |
| v1.1 — University MVP | Satu universitas pilot, beberapa fakultas/prodi; organisasi universitas/fakultas/prodi, admin universitas, koordinasi lintas fakultas; semua modul v1.0 dipertahankan | Target aktif, aplikasi web responsif |
| v1.2 — Multi-University | Potensi beberapa universitas, isolasi data, onboarding institusi, langganan universitas | Future roadmap; tidak diimplementasikan |

Nomor paket dokumen lama `Evaloop_Markdown_MVP_TecFest_2026_v1.1` menunjukkan revisi dokumentasi yang dahulu menambahkan web; bukan bukti produk University MVP sudah dirilis. Nama paket lama tetap penanda riwayat dokumen; root kerja/Git sekarang /Users/user/EvaLoop setelah pemindahan file oleh pengguna dan persetujuan pemindahan metadata Git. Dokumen aktif mengikuti produk v1.1 dan revisi Phase 2, sedangkan hasil Phase 1 disimpan sebagai riwayat.

Implemented berarti source tersedia, tested berarti ada output pengujian yang dijelaskan, blocked berarti pemeriksaan/pekerjaan tertentu terhalang. Tested tidak berarti rilis siap.

| Area | Implemented | Tested | Planned / Blocked |
| --- | --- | --- | --- |
| Dokumentasi scope/flow/ERD/PDM | Produk v1.1 universitas, 15 tabel dan kontrak role/API diselaraskan | Review konsistensi source/schema/route dan fence Markdown | Review manusia akhir tetap diperlukan |
| Migration/model | 1 migration awal, 14 model Eloquent; event_targets pivot relasi tanpa model terpisah | SQLite in-memory dan MySQL 9.7.2: migration Ran, 13 pemeriksaan FK/CHECK/UNIQUE/ENUM lulus; 15 tabel domain + migrations | Tidak ada blocker migration lokal; browser/cookie nyata dan bootstrap data institusi masih perlu diperiksa |
| Auth web | Register/login/logout, dashboard protected, validasi, hash, rotasi sesi, CSRF dan rate limit | Tes HTTP kernel termasuk CSRF dengan bypass unit test dimatikan | Cookie/browser autentikasi nyata belum diuji; HTTP server lokal Radar 200 sudah terverifikasi |
| Blade/Radar | 7 komponen, layout, form auth, metadata Radar/detail dari DB, daftar organisasi/event berizin, placeholder bisnis | Rendering, build Tailwind, cache route/config | Filter Radar dan tampilan visual browser masih planned/belum diuji |
| Policy | University manage, organization view/manage, event viewWorkspace/manage; membership dan masa berlaku | Test penolakan lintas objek, admin institusi tanpa bypass privat, expiry dan event membership independen | CRUD administratif dan policy per aksi bisnis berikutnya planned |
| Modul bisnis penuh | Belum | Belum | Planned: bootstrap administratif, CRUD anggota/event, collision, notulen/keputusan/task, legacy approval/sharing |

Verifikasi terbaru 18 November 2026: **24 tes lulus, 141 assertion**, 42 file PHP lulus lint, 20 route tersedia, build Tailwind berhasil. Detail dan batas bukti ada di docs/TESTING.md. Tidak ada commit otomatis, data aplikasi yang dihapus, atau dependency baru.

Tidak membangun multi-tenancy, pemilihan universitas saat register, billing, atau dashboard admin kompleks pada Phase 2. Admin bootstrap melalui proses console/seeder tepercaya oleh tim; tidak ada default credential.

Pada 18 November 2026, database evaloop diverifikasi kosong sebelum migrate. Migration batch 1 berhasil; pengulangan menyatakan Nothing to migrate. Ada 5 CHECK dan 16 tabel termasuk migrations. Semua fixture smoke test di-rollback, jumlah baris domain kembali 0. Radar melalui HTTP kernel + MySQL kini 200. Tidak mengubah source migration atau melakukan reset.

## Phase 3 — baseline sebelum review 3B

| Area | Implemented / Tested | Remaining |
| --- | --- | --- |
| Organisasi | Administrasi universitas scoped, daftar/detail dasar, daftar anggota dan masa berlaku; form dasar berizin | Store/update organisasi + admin pertama dan membership masih stub 501; status organisasi belum ada pada schema |
| Event | Daftar event organisasi, metadata workspace/status, form create/edit dan konfirmasi publikasi; request memeriksa waktu, seri, target dan field sensitif | Draft creation, update, target sync, konversi WITA→UTC, publikasi/transisi status belum menulis data |
| Event Series | Daftar seri/jumlah edisi, history penuh terbatas admin organisasi, scoped binding | Store seri masih 501; tidak menyalin data edisi |
| Kepanitiaan | Roster DB, form anggota biasa, form penunjukan ketua, gate publish/assignLeader terpisah | Add/update/delete anggota dan replace coordinator masih 501; transaksi dan penanganan tugas dikerjakan tim |
| Keamanan scaffold | CSRF, nested binding, policy, validasi, penolakan role/parent ID; role sendiri tidak dapat diubah | Periksa ulang otorisasi dalam transaksi saat persistence diaktifkan; review last-admin dan concurrency |
| Verifikasi | **36 tests / 273 assertions lulus**, lint 54 file PHP, build CSS berhasil, 46 route, cache Blade/config/route berhasil | Tes memakai SQLite terisolasi; CRUD MySQL dan browser terautentikasi belum diuji karena CRUD belum diimplementasikan |
| MySQL lokal | migrate:status tetap [1] Ran; HTTP Radar server nyata 200 | Tidak ada migration/schema baru atau write data aplikasi pada Phase 3 |
| Git / struktur | Root Git /Users/user/EvaLoop, .gitignore/.editorconfig dipindahkan dengan persetujuan pengguna | Folder paket lama hanya cache/snapshot lokal diabaikan Git; tidak dihapus; tidak ada commit otomatis |
| Seeder/Phase 4 | Migration dan dependency hashes tidak berubah; fixture test berlabel Simulation | Seeder belum dibuat/dijalankan. Collision/filter Radar/Legacy business dan Phase 4 tidak dimulai |

Batas penting: tombol simpan/publish/keluarkan nonaktif. Endpoint tulis hanya scaffold auth/CSRF/validasi → **501 tanpa write**, bukan fitur CRUD selesai. Kerangka siap menjadi titik kerja tim; tidak menyatakan seluruh acceptance test bisnis lulus. Baca API bagian 10 dan TESTING bagian Phase 3.

### Kesiapan Phase 4 (belum dimulai)

Radar publik dan relasi waktu/lokasi/target/organisasi/seri tersedia. Integrasi collision belum siap end-to-end sampai tim menyelesaikan create/update draft, target sync dan publikasi yang benar-benar menyimpan data. Contract overlap `[start,end)`, exclude cancelled/draft/event sendiri, warning non-blocking dan privasi tetap acuan. Jangan menggunakan scope Published saja sebagai pembanding collision karena scope Radar juga menyertakan cancelled. Filter, normalisasi venue dan pengujian target/kasus batas perlu ditinjau pada Phase 4; tidak ada algoritma collision atau rekomendasi AI yang diimplementasikan di sini.

## Phase 3B — hasil review sebelumnya

| Area | Completed / Verified | Remaining / Gate |
| --- | --- | --- |
| Inventaris | API.md bagian 11: 13 endpoint, tujuan/role/validasi/model/response target/acceptance | Semua 13 mutasi tetap 501; tidak ada klaim persistence |
| Debugging UI/validasi | Old input malformed tidak lagi menyebabkan Blade 500; guard byte description; EventStatusRequest publish/cancel | Form mutation tetap nonaktif, feedback sukses hanya setelah tim menyimpan data |
| Cancel | GET confirmation + POST scaffold, gate OA; sensitif fields ditolak; CSRF aktif | Transisi/persistence belum ada; bukan cancellation functional |
| Transaksi/keamanan | Kontrak lock/recheck/whitelist dan AC admin terakhir/coordinator/task terdokumentasi | Implementasi DB::transaction pada CRUD, last-admin guard dan konkurensi masih dikerjakan tim |
| Pengeluaran anggota | FK task pada semua status diidentifikasi; usulan tolak bila masih direferensikan | Usulan perlu kesepakatan/proses eksplisit; jangan hapus tugas/riwayat atau bypass FK |
| Tes | **42 tes/309 assertion lulus** pada SQLite; lint 56 file PHP, build/cache/Composer valid; 48 route; MySQL rollback probe lulus | Atomicity/controller CRUD, write/read-back MySQL dan browser bisnis belum teruji karena mutasi masih stub |
| Integritas proyek | Schema/model/dependency tidak diubah; tidak ada commit | Tidak ada Phase 4 otomatis |

**End-to-end status: belum siap** untuk CRUD Organization, Event, Series dan Workspace. Halaman baca, form, validasi dan policy tersedia; transaksi, persistence, publikasi/pembatalan dan kepanitiaan belum berjalan. Kelulusan tes scaffold tidak menggantikan acceptance bisnis. Phase 4 menunggu CRUD inti tim benar-benar berfungsi.

## Phase 3C — review sebelum development fixture

| Prioritas | Endpoint / area | Status aktual | Verifikasi / blocker |
| --- | --- | --- | --- |
| 1 | series.store (B08) | **501** | Tes fokus validasi/izin/relasi lulus; store tetap abort, return type never dan form disabled. Tim perlu menyelesaikan satu insert berizin + redirect/flash. |
| 2 | organization.events.store, workspace.update | **501** | Belum lanjut validasi fungsional; event+target, UTC dan draft harus atomik. |
| 3 | workspace.publish/cancel | **501** | Belum ada state persistence; transisi perlu review tim. |
| 4 | workspace.members.store/update/destroy, workspace.leader | **501** | Role/pair, pergantian ketua atomik, dan penanganan task masih blocker. |
| 5 | university.organizations.store/update, organizations.members.store/update | **501** | Org+admin pertama dan guard admin terakhir belum implemented. |

Audit: tidak ada perubahan persistence dari tim dibanding 3B. Fokus baru: **3 tes/34 assertion SQLite lulus**, memperkuat existing series test; bukan full run 42 tes ulang atau bukti CRUD. MySQL read-only: migration Ran, organisasi/seri/OA berlaku masing-masing 0. Prasyarat organisasi+OA tepercaya dibutuhkan sebelum browser verification.

Dokumen handoff slice pertama ada pada API.md bagian 12 dan TESTING.md bagian 14. Tidak menambah scaffold, schema, package atau implementasi bisnis otomatis. Tidak ada commit/Phase 4. Lanjut endpoint berikutnya hanya sesudah tim menyelesaikan dan memverifikasi slice pertama.

## Development fixture — status terkini

- EvaloopDemoSeeder berhasil dijalankan **dua kali** pada MySQL evaloop; data/fingerprint stabil: 1 universitas, 2 fakultas, 5 prodi, 5 organisasi, 7 akun demo. Rincian 15 tabel pada DATABASE.md/TESTING.md.
- Prasyarat OA/organisasi yang kosong pada audit 3C kini tersedia melalui fixture eksplisit; tidak melalui role register dan bukan perubahan endpoint bisnis.
- Password berasal dari EVALOOP_SEED_PASSWORD tanpa default/log; environment hanya local/testing, namespace SIMULASI, firstOrCreate tanpa reset existing dan transaksi rollback pada benturan.
- Tes: 6 seeder tests/88 assertions; full suite **48 tests/406 assertions SQLite lulus**; **33 pemeriksaan HTTP/MySQL-backed login/logout/akses lulus**. Bukan pengujian visual browser atau CRUD persistence lewat endpoint.
- Masih **13 mutasi 501** dan tombol simpan nonaktif. Meeting/task/lesson telah disimpan sebagai fixture, tetapi UI bisnisnya placeholder. Tidak ada migration/schema/dependency/commit/Phase 4 baru.


## Phase 4 — status terkini (18 November 2026)

Pekerjaan Radar pada Phase 4 berjalan saat CRUD inti masih berupa scaffold.

| Area | Status aktual | Batas / remaining |
| --- | --- | --- |
| Event Radar | Implemented + tested: kartu DB, deskripsi, status cancelled nonaktif, eager loading, empty state | Visual browser masih oleh pemilik |
| Filtering | Implemented + tested: keyword, fakultas/prodi organisasi ATAU target, organisasi, rentang tanggal WITA, GET/pagination, validasi | Tidak ada AJAX/dependency baru |
| Collision | Scaffold + algorithm review: halaman jadwal berizin dan komponen warning | Algoritma klasifikasi belum diimplementasikan; warnings null, bukan hasil nol konflik |
| Workspace integration | Link peninjauan jadwal tersimpan tersedia, akses OA/coordinator | Input belum disimpan dan create/update/publish belum terintegrasi; 13 mutasi tetap 501 |
| Fixture | Seed MySQL berhasil: 7 event/14 target; dua kasus tambahan, baris lama utuh | Data simulasi; bukan bukti CRUD berhasil |
| Verification | 52 tests/444 assertions SQLite; build, cache Blade/route dan HTTP lokal/MySQL lulus | Bukan pengujian visual atau algoritma collision |

Handoff konkret: WorkspaceController::conflicts dan komponen collision-warning; kontrak API bagian 13, skenario A–G TESTING bagian 16. Tim menyelesaikan algoritma pada seluruh kandidat sebelum pagination, lalu verifikasi hasil pasangan dan privasi. Tidak ada migration/schema, dependency, policy autentikasi atau commit baru. **Phase 5 belum siap penuh**: Radar/filter siap dipakai, collision otomatis dan integrasi CRUD belum selesai. Tidak melanjutkan Phase 5 otomatis.

## Phase 4B — audit functionalization

Algoritma tim belum ditemukan: WorkspaceController::conflicts masih warnings null. Kontrak input/output dan langkah implementasi ditempatkan langsung di fungsi existing dan API.md; tidak membuat scaffold baru. Normalisasi venue kini disepakati trim + collapse whitespace + lowercase Unicode, menggantikan exact-text Phase 4. Penjelasan komponen warning diselaraskan. Deteksi otomatis dan integrasi 13 endpoint 501 tetap belum berfungsi; tidak lanjut Phase 5. Hasil fixture pada TESTING adalah expected result, bukan hasil detektor.

## Implementasi fungsional lokal — status terbaru

13 mutasi implemented, tidak ada abort(501) pada controller. Series, event+targets, publish/cancel, organisasi+admin pertama, membership dengan last-admin guard, committee/leader, collision, meeting/decisions/task dan progres berjalan melalui web. 62 route. Auth/policy/scoped binding tetap aktif; tanpa perubahan migration/dependency/Git.

Bukti: 61 pemeriksaan HTTP nyata session/CSRF + committed MySQL read-back lulus; dua uji integrasi MySQL/21 assertion lulus (collision + injected rollback event-target, organisasi-admin dan pergantian ketua). Tes SQLite, lint/build/cache tercatat di TESTING bagian 17. Data verifikasi baru berlabel SIMULASI disimpan untuk browser, bukan menimpa fixture lama. Tidak menyebut pengujian visual browser atau seluruh MVP lulus. Legacy, admin struktur fakultas/prodi, dan deployment tetap belum selesai. Review manual oleh pemilik diperlukan.

## Phase 6 — Legacy Insights & Legacy-to-Action (terkini)

Implemented: evaluasi internal/create/edit, approval OA, legacy edisi sebelumnya melalui seri+organisasi sama, sumber evaluasi terbatas, action eksplisit menjadi task todo dengan PIC/tenggat dan source_lesson_id. Approved lesson immutable; tidak menyalin rapat/panitia/tugas. Submit ulang memakai task existing, tanpa overwrite. Lock event tujuan + lesson di transaksi; pindah seri atau reschedule yang merusak link legacy ditolak. 70 route; schema/dependency tidak berubah.

Verified: 57 tests/591 assertions SQLite, 3 tests/40 assertions MySQL opt-in (rollback), 51 HTTP checks session/CSRF + committed MySQL read-back, build/cache/lint lulus. Baru ditambahkan lesson 3 pada event 2 dan task 6 pada event 3, berlabel SIMULASI; fixture lama utuh. Demo lokal Legacy-to-Action siap diuji pemilik. Visual browser dan race paralel belum diuji. Tidak ada approver ID dalam schema, hanya approved_at; tidak membuat kolom/workflow tambahan. Tidak ada Git/deployment.

## Phase 7 — UX polish dan QA lokal (terkini)

Dashboard sesuai peran + identitas akun, navigasi kembali/active sidebar, tombol publish/cancel sesuai state, halaman error Indonesia, select validation/a11y, wrapping/focus dan submit feedback diperbaiki. Registrasi menolak password di atas batas efektif bcrypt 72 byte. Tautan publik→workspace hanya bagi pihak berizin. Perubahan task deep-link/locking existing dipertahankan.

Verified: 61 tests/627 assertions SQLite; 4 tests/75 assertions MySQL rollback termasuk journey lengkap; 101 real HTTP checks/73 HTML pages lintas role dengan seluruh domain fingerprints tetap. Build/lint/cache lulus. Safari remote automation tidak aktif, sehingga visual/responsive dan JavaScript browser belum teruji. Checklist demo ada di TESTING bagian 19. Tidak ada schema/dependency/Git/deployment/data reset.

## Interaction polish — DESIGN motion requirements

Shared interaction tokens, four button variants, native account disclosure, link/sidebar/tab/control feedback, stable pending button layout and reduced motion implemented. Native named submitter values are preserved (no disabling before serialization). 61 Laravel tests, 4 JS state tests, 4 MySQL integration tests and 101 HTTP checks pass. Visual browser/animation inspection remains manual; no claim that the entire visual redesign is complete. UI-only files plus Vite JS entry; no schema/dependency/Git changes.

## Structural UI redesign — stage 1 (19 November 2026)

Implemented layout/composition changes to shared shell, Dashboard, Event Radar and Event Workspace. Desktop has fixed grouped navy navigation; mobile uses native compact disclosure. Dashboard is action/agenda-first with role-specific institution layout; Radar uses date-led rows; Workspace uses a masthead, tabs and a main/operational split. New icon/progress components; only read projections added to two controllers. No business-rule/authorization/schema/route/dependency changes.

Verified: 63 SQLite tests/645 assertions, 5 JS state tests, 4 MySQL tests/75 assertions, 101 HTTP checks with domain data unchanged. Visual gate remains manual: Safari session timeout prevented before/after screenshots. Stage 1 is implemented and technically verified, not a claim of rendered visual QA or redesign of every module. No Git/deployment.


## Operational UI redesign — stage 2 (19 November 2026)

Implemented: agenda-led Meeting Hub, notes/decision register, dense issue tracking and personal tasks, real progress summary, organization/member/committee directories, series edition timeline, sectioned CRUD forms with contextual actions. Shared workspace/organization navigation, textarea and form-section components reuse Stage 1 tokens and interactions. No controller/business-rule/schema/authorization/dependency changes.

Tested: 63 SQLite tests/645 assertions; 5 JS state tests; 4 MySQL tests/75 assertions (rollback); 101 HTTP checks/73 HTML pages with 15 domain fingerprints unchanged. Build/cache/compiled Blade lint passed. Visual responsive QA remains manual; known Safari timeout was not retried. No Git/deployment.


## Signature Legacy experience — stage 3 (19 November 2026)

Implemented UI: actual edition lineage, inherited insight problem/recommendation composition, separate internal evaluation/approval register, contextual source-to-task form and persisted follow-up state. Existing permissions and business operations unchanged. Tested: 63 SQLite tests/655 assertions, 5 JS state tests, build/cache/compiled Blade lint. MySQL verification blocked by local connection refused (4 setup errors, 0 assertions); visual desktop/mobile review remains manual. No Git/deployment; no subsequent stage started.


## Event Radar discovery rework (19 November 2026)

Implemented: compact editorial heading/search, active filter chips, shareable real period tabs, active-first DB sorting before pagination, clustered date/title/organizer/time/venue items, distinct history and technical fixture disclosure. Fixture labels use Demo with no DB changes. Tested: 64 SQLite/689 assertions, 4 MySQL/75 assertions, 5 Node tests, 6 read-only HTTP cases with all 15 domain fingerprints unchanged. Chrome CDP screenshots/overflow checked at 375/390/768/1440px; Safari/device QA remains manual. No auth/policy/CRUD/schema/dependency/Git/deployment changes.


## Sxodim-inspired portrait Radar (20 November 2026)

Implemented: 3/2/1 portrait grid with typographic covers (actual event title/date/scope), navy/teal deterministic graphics, consistent footer, new compact headline. Only Radar presentation changed; source queries/filter/publication rules untouched. Verified: 64 SQLite/689 assertions, final focused 14/182, 5 JS tests, build/lint/cache. Chrome screenshots at 1440/768/390px used isolated SQLite demo fixtures; MySQL currently unavailable, current persistence not reverified. No Git/deployment.


## Search/filter and navigation refinement (20 November 2026)

Implemented: native chip filter panels/selected state/reset, lightweight guest topnav, grouped navy workspace sidebar/footer account menu and compact page-context bar. Poster card/grid retained; no backend changes. Verified 64 SQLite/689 assertions, 4 MySQL/75 assertions, 5 JS tests, 32 scoped HTTP checks/seven roles with domain data unchanged. Actual Chrome/MySQL public desktop/tablet/mobile, account and filter keyboard/reduced-motion checks passed. Linear visuals inspected; CreateUnity visuals inaccessible, brief-based implementation disclosed. Safari/device visual review pending. No Git/deployment.


## Fluid responsive refinement (20 November 2026)

Implemented bounded clamp/cqi typography/gutters, content-container grid/search composition, adaptive filter panels/chips, compact tabs and single-row public navigation; cover sizing now3:2 with existing card design unchanged. Verified64 SQLite/689 assertions,4 MySQL/75 assertions,5 Node tests, build/cache/lint and actual Chrome/MySQL nine-width screenshot/overflow checks320–1920px. Equivalent125%/200% reflow checked; native browser zoom/Safari/device verification remains manual. No backend/schema/auth/dependency/Git/deployment changes.


## Final identity — Fresh Editorial Campus Discovery (20 November 2026)

Implemented Ice Blue/White/Aqua/Ocean Teal tokens, local licensed Manrope, existing navigation in light palette, asymmetric hero with actual filtered upcoming public event, and unified discovery toolbar. Approved portrait poster component and artwork retained. Only backend change is the limited read projection for the highlight. DESIGN.md now explicitly rejects Deep Navy + Coral as branding and defines responsive/gradient/font roles without obsolete palette instructions.

Verified65 SQLite/703 assertions,4 MySQL/75 assertions,5 Node tests,32 role-aware HTTP checks with domain fingerprints unchanged, build/cache/lint. Chrome/MySQL public390/768/1024/1440/1920 plus320, authenticated390/1024/1440, filtered/history/empty states and local-font loading inspected. Owner/Safari/device/native zoom review remains manual; no production-readiness or Git/deployment claim.

## Lampiran evaluasi Legacy Insights

Evaluasi dapat menyimpan hingga lima lampiran pada disk Laravel privat. Ketua atau admin organisasi yang dapat mengedit evaluasi dapat mengunggah dan menghapusnya sebelum approval. Panitia edisi penerima mendapat akses melalui policy evaluasi sumber setelah approval, tanpa penyalinan file. Migration aditif diterapkan pada MySQL development lokal. Pengujian unggah dan akses memakai data terisolasi.
