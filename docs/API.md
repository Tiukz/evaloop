# Kontrak Rute Web dan API Android — Evaloop v1.1

Bagian 14–17 merangkum kontrak web dan API Android serta menandai endpoint Android yang masih tersedia di backend lokal. Bagian 3 dan 9–13 mencatat riwayat fase rancangan, termasuk respons scaffold 501 yang kemudian diganti dengan mutasi fungsional.

## 1. Aplikasi web Laravel

Blade menyajikan halaman HTML dengan middleware `web`, cookie sesi browser, dan CSRF pada form mutasi. Rute privat memeriksa sesi dan policy per objek. Autentikasi memakai sesi Laravel standar.

## 2. Auth sederhana: register/login/logout

### Website (form Blade)

1. `GET /register` menampilkan nama, email, password, konfirmasi password; `POST /register` memvalidasi, menyimpan hash password, melakukan login dan meregenerasi sesi.
2. `GET /login` menampilkan email/password; `POST /login` memanggil `Auth::attempt(...)`, meregenerasi sesi saat sukses.
3. `POST /logout` menjalankan `Auth::logout()`, menginvalidasi sesi, meregenerasi CSRF, lalu redirect.
4. Semua POST web menggunakan `@csrf`. Registrasi tidak menerima pilihan role admin/panitia.

### Keamanan minimal

- Password menggunakan hashing Laravel, validasi register/login dan rate limiting login.
- HTTPS pada deployment, session cookie `HttpOnly`, `Secure` bila HTTPS, konfigurasi `SameSite` yang tepat; jangan log cookie/CSRF/credentials.
- Middleware `auth`/policy memvalidasi setiap objek privat termasuk setelah login berhasil.
- Akun register baru hanya pengguna biasa, admin dan panitia harus ditambahkan oleh admin berwenang.
- Registration tanpa email verification **hanya untuk demo terbatas**; jangan menganggap identitas email terjamin dalam penggunaan nyata. Sebelum admin mengundang akun berdasarkan email, lakukan pemeriksaan identitas manual untuk demo atau tambahkan verifikasi email jika digunakan sungguhan.

## 3. Baseline rute Web (Phase 2, dilanjutkan Phase 3)

| Method | Path | Akses | Implementasi |
| --- | --- | --- | --- |
| GET | `/`, `/events` | Publik | Radar metadata publik dari DB, pagination 12; filter belum tersedia |
| GET | `/events/{id}` | Publik | Hanya published_at terisi dan status published/completed/cancelled; draft 404 |
| GET/POST | `/register` | Guest; POST CSRF, throttle 6/menit/IP | Validasi, hash, auto-login dan regenerate session; tanpa role |
| GET/POST | `/login` | Guest; POST CSRF, named throttle login | Auth::attempt dan regenerate session; maksimal 5 percobaan/menit/email+IP dan 30/IP |
| POST | `/logout` | Login + CSRF | Logout, invalidasi sesi, regenerasi CSRF |
| GET | `/dashboard` | Login | Kerangka dashboard; tidak memberikan hak organisasi/event |
| GET | `/my/organizations` | Login | Membership organisasi aktif dan masih berlaku dari DB |
| GET | `/my/events` | Login | Panitia event atau admin organisasi berlaku, dari DB |
| GET | `/my/tasks` | Login | Placeholder jelas; belum membaca/mengubah task |
| GET | `/organizations/{organization}` | Membership organisasi berlaku | Kerangka overview |
| GET | `/organizations/{organization}/members`, `/organizations/{organization}/events` | Admin organisasi berlaku | Placeholder pengelolaan; belum ada mutasi |
| GET | `/workspace/events/{event}` | Admin organisasi berlaku atau panitia event | Kerangka overview, policy per objek |
| GET | `/workspace/events/{event}/meetings`, `/tasks`, `/legacy` | Izin workspace event yang sama | Kerangka; belum membaca notulen/task/lesson |
| GET | `/up` | Publik | Health framework, bukan pemeriksaan MySQL |

Daftar di atas sesuai routes/web.php; 20 route pada baseline Phase 2; Phase 3 kini 46 route (GET/HEAD dihitung satu). Nama route dan guest/auth middleware digunakan langsung oleh Blade. Tidak ada `/workspace` atau route mutasi domain yang diklaim tersedia. Overview workspace, daftar anggota/event/seri mendapat halaman baca; form/penulisan tetap scaffold, bukan CRUD selesai. Tambahan terbaru ada pada bagian 10.

Kesalahan kredensial/validasi web kembali ke form dengan error; password tidak di-flash kembali. Rate limit menghasilkan 429, akses privat 403/404. QueryException menghasilkan 503 dengan pesan umum, tanpa SQL/credential dan tanpa mengganti DB dengan data contoh. Sesi file + CSRF default Laravel tetap aktif. Pengunjung JSON pada route auth privat menerima 401.

Seluruh scope web saat ini satu universitas pilot; tidak ada selector universitas atau mekanisme tenant. Faculty/prodi/organisasi/time filter Radar masih direncanakan. Tampilan waktu WITA, penyimpanan UTC.

## 4. Visibilitas publik dan workspace

Event Radar hanya membaca metadata publik dari event dengan timestamp publikasi dan status yang diizinkan. Workspace, rapat, tugas, dan evaluasi mengikuti akses per event. Lihat implementasi rute web pada bagian 14 dan 15.

## 5. Aturan bentrokan

Dua jadwal beririsan jika `startA < endB` dan `startB < endA`. Irisan dengan lokasi sama memberi peringatan lokasi. Irisan target program studi memberi peringatan peserta. Lihat rute dan tampilan peringatan aktual pada bagian 13.

## 6. Response dan status kesalahan

- Web: kesalahan validasi kembali ke form Blade; `403/404` untuk akses objek yang tidak sah.
- Semua request data privat harus mencari aktor dari sesi; role event/org diverifikasi Laravel. Tidak ada `user_id` body sebagai identitas login.

## 7. Rujukan teknis resmi

- Laravel session: https://laravel.com/docs/session
- Laravel authentication: https://laravel.com/docs/authentication
- Laravel CSRF: https://laravel.com/docs/csrf

**Catatan:** versi Laravel yang dipasang menentukan konfigurasi dan middleware persisnya. Rancangan ini belum menggantikan uji implementasi dan uji keamanan.

## 8. Otorisasi administratif dan membership

Rute administrasi organisasi dan kepanitiaan yang tersedia dicatat di bagian 10 dan 14.

Aturan lintas endpoint:

- Identitas aktor berasal dari sesi. Field `user_id` pada form administratif merujuk ke akun yang dikelola.
- Register/profil tidak boleh mengubah is_platform_admin, membership, atau role. Tidak ada endpoint publik untuk menaikkan hak platform.
- Hak organisasi memerlukan status active, joined_at <= now, dan ended_at NULL atau now < ended_at. `ended_at` wajib lebih besar dari `joined_at` bila diisi.
- Admin organisasi yang berlaku dapat membaca workspace/arsip, mengelola event, rapat, keputusan, tugas, dan evaluasi organisasi sendiri. Ketua diberi kewenangan operasional hanya pada event sendiri; publikasi dan approval lesson tetap oleh admin organisasi.
- Endpoint daftar/buat rapat dan tugas, keputusan, lesson, my/events, detail privat, konflik dan legacy harus menerima admin organisasi yang berlaku atau anggota/ketua event sesuai aksi. Anggota biasa hanya mengubah progres tugas sendiri. Validasi nested object tetap wajib.
- Keanggotaan organisasi yang berakhir tidak mencabut event membership; pada MVP record event membership yang ada menentukan akses event. Sesi tidak boleh menyimpan izin yang tidak diperiksa ulang.
- Platform admin bootstrap universitas dan admin universitas pertama melalui proses tepercaya. Admin universitas dengan university_members berlaku mengelola struktur dan administrasi awal organisasi universitas sendiri; keduanya tidak otomatis membaca data privat.
- Legacy lintas edisi untuk panitia hanya lesson visibility=series yang disetujui dalam seri yang sesuai; admin organisasi berlaku dapat membaca arsip organisasinya.
- Konflik mengecualikan event sendiri, draft dan cancelled. Hanya pembanding published_at terisi dan status published/completed; overlap `[start,end)`, venue kosong tidak dianggap sama.
- Respons administratif tidak mengirim hash password/cookie. Kontrak pagination respons daftar domain ditetapkan saat implementasi; bentuk resource dan pagination final perlu ditetapkan saat fitur terkait dikerjakan.

## 9. Integrasi collision dan legacy untuk tim

Belum ada route collision atau hasil warning aktual. Kontrak bagian 5 tetap acuan implementasi berikutnya: pembanding published_at dengan status published/completed, exclude cancelled/draft/event sendiri; interval setengah terbuka, venue terisi yang sama atau target prodi beririsan. Data draft organisasi lain tidak dikirim. Lintas fakultas tetap dalam satu universitas pilot.

Belum ada endpoint membaca/menyetujui lesson atau menindaklanjutinya menjadi task. Policy workspace bukan izin otomatis membaca seluruh arsip edisi lama; implementasi legacy harus memeriksa seri, visibility, approval dan membership relevan sesuai DATABASE.md. FK komposit lingkup organisasi bukan pengganti policy per objek.

## 10. Route web Phase 3 — read dan write scaffold

Semua route di bawah memakai middleware auth dan web/CSRF. **POST/PATCH/PUT/DELETE di tabel ini belum melakukan persistence:** setelah izin dan validasi relevan, respons 501 (bukan sukses/redirect sukses). Form menonaktifkan tombol simpan dan menampilkan notice.

| Method | Path | Hak akses / status |
| --- | --- | --- |
| GET | `/universities/{university}/organizations` | Admin universitas berlaku; daftar dasar dari DB, tanpa workspace privat |
| GET/POST | `/universities/{university}/organizations/create` (GET), `/universities/{university}/organizations` (POST) | Form/validasi organisasi + initial_admin_user_id existing; POST 501 |
| GET/PATCH | `/universities/{university}/organizations/{organization}/edit` (GET), `/universities/{university}/organizations/{organization}` (PATCH) | Admin universitas berlaku; org wajib milik parent; PATCH 501 |
| GET | `/organizations/{organization}/members` | Admin organisasi berlaku; daftar nama/role/status/masa berlaku aktual |
| GET/POST | `/organizations/{organization}/members/create` (GET), `/organizations/{organization}/members` (POST) | Admin organisasi; tambah existing user, unique pair; POST 501 |
| GET/PATCH | `/organizations/{organization}/members/{membership}/edit` (GET), `/organizations/{organization}/members/{membership}` (PATCH) | Scoped membership, admin organisasi; role sendiri dan penggantian user_id ditolak; PATCH 501 |
| GET | `/organizations/{organization}/events` | Admin organisasi; daftar edisi organisasi dari DB |
| GET/POST | `/organizations/{organization}/events/create` (GET), `/organizations/{organization}/events` (POST) | Admin organisasi; Form Request event; POST 501 |
| GET/PATCH | `/workspace/events/{event}/edit` (GET), `/workspace/events/{event}` (PATCH) | Admin organisasi atau coordinator event; PATCH 501 |
| GET/POST | `/workspace/events/{event}/publish` | Admin organisasi berlaku saja; POST 501 |
| GET | `/organizations/{organization}/series` | Admin organisasi; daftar seri dan jumlah edisi |
| GET/POST | `/organizations/{organization}/series/create` (GET), `/organizations/{organization}/series` (POST) | Admin organisasi; POST 501 |
| GET | `/organizations/{organization}/series/{eventSeries}` | Scoped series organisasi; hanya admin organisasi untuk riwayat penuh yang dapat memuat draft |
| GET | `/workspace/events/{event}/members` | Izin viewWorkspace; roster nama/role dari DB, tanpa email pengguna |
| GET/POST | `/workspace/events/{event}/members/create` (GET), `/workspace/events/{event}/members` (POST) | Admin organisasi/coordinator; hanya role member lewat form biasa; POST 501 |
| GET/PATCH/DELETE | `/workspace/events/{event}/members/{membership}/edit` (GET), `/workspace/events/{event}/members/{membership}` (PATCH/DELETE) | Scoped anggota biasa, bukan coordinator; admin organisasi/coordinator; mutasi 501 |
| PUT | `/workspace/events/{event}/leader` | Admin organisasi berlaku saja; user_id existing, role tidak dari payload; 501 |

Validasi yang tersedia:

- Scope universitas/organisasi/event berasal dari route + policy; payload university_id/organization_id/event_id tidak boleh memindahkan objek. Scoped binding menolak nested ID yang tidak cocok sebelum controller.
- Organisasi: name <=160; faculty/prodi harus cocok scope; status organisasi dilarang karena tidak ada kolomnya; initial admin hanya pada create.
- Membership organisasi: role admin/member, status active/inactive; satu pasangan user–org; tanggal berformat datetime-local WITA dan ended_at setelah joined_at; user_id tidak dapat diganti lewat PATCH. Penonaktifan menggunakan PATCH status, bukan DELETE.
- Event: title <=200, venue <=160, seri hanya organisasi yang sama, target_program_ids array distinct dan prodi universitas yang sama, waktu akhir setelah awal. Status/published_at dilarang dari form umum; konversi WITA ke UTC dan penetapan draft masih TODO persistence tim.
- Role coordinator tidak diterima dari form anggota panitia, termasuk bila dipanggil ketua. Penggantian ketua hanya melalui aksi leader oleh admin organisasi.
- Field tambahan yang tidak termasuk validated whitelist tidak boleh dipakai tim pada persistence; tidak menggunakan request->all(). Validasi belum berarti aksi bisnis sudah selesai.

Belum ada endpoint hard delete event, sync anggota antar-edisi, collision, atau approval legacy. Form edit anggota biasa hanya kerangka role member; tidak memperkenalkan role baru. Pencabutan panitia dan pergantian ketua memerlukan transaksi/penanganan task sebelum endpoint 501 diaktifkan.

## 11. Phase 3B — inventaris mutasi dan kontrak implementasi tim

Audit awal menemukan **12 endpoint 501**. Kekurangan route cancel dilengkapi sebagai scaffold; inventaris terkini **13 endpoint 501** dan **48 route total**. Seluruh endpoint memakai auth, web/CSRF dan policy; parent/child nested memakai scoped binding. Tidak ada duplikasi controller/model atau persistence baru.

Singkatan: UA = admin universitas berlaku; OA = admin organisasi berlaku; EL = coordinator event. Model pada tabel adalah model yang harus ditangani persistence tim, bukan klaim model sudah berubah. **Respons aktual semua baris = 501 setelah validasi/izin**; kolom respons menjelaskan target setelah implementasi tim. Kegagalan auth/izin/binding/validasi tetap 401 atau redirect login, 403/404, 422 atau redirect dengan errors; CSRF 419.

| ID / endpoint | Tujuan | Role | Validasi / request | Model terkait | Respons target setelah tim mengimplementasikan | Acceptance criteria belum lulus bisnis |
| --- | --- | --- | --- | --- | --- | --- |
| B01 POST `/universities/{university}/organizations` | Buat organisasi + admin pertama | UA universitas route | OrganizationRequest: name, faculty/prodi sesuai scope, initial_admin_user_id existing; university/status/flag dilarang | University, Faculty, StudyProgram, Organization, OrganizationMember, User | 302 daftar organisasi + flash sukses | Organisasi dan admin pertama active dibuat atomik; rollback keduanya jika salah satu gagal; bukan flag platform admin |
| B02 PATCH `/universities/{university}/organizations/{organization}` | Edit informasi dasar | UA pemilik scope | OrganizationRequest + nested org; initial_admin_user_id/parent/status dilarang | Organization, Faculty, StudyProgram | 302 form/detail administrasi + flash | Hanya whitelist informasi dasar berubah; tidak memindahkan universitas atau menimpa admin |
| B03 POST `/organizations/{organization}/members` | Tambah akun existing | OA | OrganizationMemberRequest: user exists, pair unik, role/status, tanggal | OrganizationMember, User, Organization | 302 daftar anggota + flash | Satu record per pair; tidak mengubah account role global; duplikasi concurrent ditolak tanpa data parsial |
| B04 PATCH `/organizations/{organization}/members/{membership}` | Ubah role/status/masa | OA | Scoped membership; tidak mengganti user_id atau role sendiri; date range valid | OrganizationMember, Organization | 302 daftar anggota + flash; 422 untuk guard admin terakhir | Inactive/future/expired kehilangan hak organisasi; tidak menghapus arsip/event membership; admin terakhir tidak dinonaktifkan/diturunkan tanpa pengganti berlaku |
| B05 POST `/organizations/{organization}/events` | Buat draft + target | OA | EventRequest: title, waktu, venue, seri organisasi, target prodi universitas; parent/status/published_at dilarang | Event, EventSeries, StudyProgram, pivot event_targets | 302 overview workspace + flash | Draft tersimpan, published_at NULL, target atomik; tidak tampil Radar; tanggal invalid ditolak |
| B06 PATCH `/workspace/events/{event}` | Edit metadata/target | OA atau EL pada event itu | EventRequest; organisasi tidak dipindah; seri/target/waktu valid | Event, EventSeries, StudyProgram, event_targets | 302 overview workspace + flash | Tidak mengubah status/published_at dari input; target dan event tidak parsial; event organisasi lain ditolak |
| B07 POST `/workspace/events/{event}/publish` | Publikasi metadata | OA | EventStatusRequest + publish gate; status/id/timestamp tidak dari payload | Event, OrganizationMember | 302 overview + flash | Recheck izin/transisi di transaksi; server menetapkan status/published_at; metadata publik tampil, workspace tetap privat |
| B08 POST `/organizations/{organization}/series` | Buat identitas seri | OA | EventSeriesRequest: name, organization_id dilarang | EventSeries, Organization | 302 daftar seri + flash | Pemilik dari route; dua edisi satu seri tetap memiliki data workspace terpisah |
| B09 POST `/workspace/events/{event}/members` | Tambah panitia biasa | OA atau EL | EventMemberRequest: registered user, unique event/user, role member saja | EventMember, Event, User | 302 roster + flash | Tidak ada duplikasi, penunjukan coordinator tidak lewat endpoint ini, tidak menyalin panitia edisi lain |
| B10 PATCH `/workspace/events/{event}/members/{membership}` | Kerangka perubahan anggota biasa | OA atau EL | Scoped member, bukan coordinator; role hanya member; user_id tetap | EventMember, Event | 302 roster + flash setelah aksi bermakna disepakati | Schema hanya coordinator/member; tidak mengarang role baru. Karena member→member tidak mengubah peran, tim perlu menilai apakah PATCH ini diperlukan; jangan mengklaim fitur role lebih luas |
| B11 DELETE `/workspace/events/{event}/members/{membership}` | Keluarkan anggota biasa | OA atau EL | Scoped member, bukan coordinator; guard task di transaksi masih TODO | EventMember, Task, Event | 302 roster + flash; usulan 409 bila masih dirujuk tugas | Tidak menghapus/mengosongkan/memindahkan task otomatis; record tugas dan riwayat dipertahankan; semua FK terpenuhi |
| B12 PUT `/workspace/events/{event}/leader` | Tetapkan/ganti ketua | OA | EventLeaderRequest: user existing, tanpa role/event_id dari input | Event, EventMember, User | 302 roster + flash | Ganti coordinator atomik, ketua lama tidak lagi berwenang dari role coordinator; hak OA independen tetap dihormati |
| B13 POST `/workspace/events/{event}/cancel` | Kerangka pembatalan, tambahan 3B | OA | EventStatusRequest + cancel gate; parent/status/timestamp/target dari payload ditolak | Event, OrganizationMember | 302 overview + flash setelah transisi disepakati | Cancel tidak hard delete; published_at historis dipertahankan; cancelled yang belum pernah publish tetap tidak publik; tidak menjadi pembanding collision |

GET `/workspace/events/{event}/cancel` bernama workspace.cancel.confirm menyediakan form nonaktif. POST bernama workspace.cancel tetap 501. Pembatasan cancel mengikuti hak publikasi OA, bukan memberi EL hak administratif baru; tim meninjau transisi status sebelum mengaktifkan aksi.

Perubahan validasi 3B: EventStatusRequest juga dipakai endpoint publish existing. Deskripsi tetap maksimal 20.000 karakter dan kini maksimal 65.535 byte agar cocok MySQL TEXT; tidak mengubah schema. Nilai old input yang bertipe salah tidak boleh membuat Blade error 500. Isian sah tetap dipulihkan setelah validasi gagal; password tidak dirender kembali.

Jangan mengganti 501 menjadi redirect sukses saja. Setelah persistence tim selesai, barulah aktifkan tombol yang sesuai, kirim flash status dan ganti assertion 501 dengan pembacaan hasil DB/transisi yang benar.

## 12. Phase 3C — satu vertical slice: Event Series Creation

Pemeriksaan source belum menemukan endpoint yang telah diimplementasikan tim: seluruh B01–B13 masih 501. Tidak menambah scaffold/route/model atau menulis keseluruhan persistence. Inventaris lengkap bagian 11 tetap berlaku.

### Prioritas dan batas per fitur

| Urutan | Alur/validasi/authorization | Eloquent yang diperlukan tim | Transaksi | Response dan acceptance target |
| --- | --- | --- | --- | --- |
| 1 Event Series (B08) | POST series.store; session/CSRF → route organization → EventSeriesRequest name <=180 + manage policy OA berlaku | Create melalui relasi Organization.eventSeries, hanya validated name; FK pemilik dari route | Satu INSERT atomik; transaksi multi-record tidak diperlukan hanya untuk insert ini. Bila tim mensyaratkan serialisasi dengan pencabutan admin, gunakan protokol lock organisasi + recheck dalam transaksi bersama semua writer terkait | 302 series.index untuk org tersebut + flash status; row tersimpan dan tampil kembali; no-write bila invalid/forbidden |
| 2 Event Creation (B05, lalu B06) | OA create; metadata/waktu/seri pemilik/target prodi universitas sesuai EventRequest; edit OA/EL sesuai policy | Organization.events create/update metadata; relationship targets sync; WITA→UTC; server menetapkan draft dan published_at NULL | Event + pivot targets satu unit atomik; kegagalan salah satu tidak menyisakan data parsial | 302 workspace.overview; event/target dapat dibaca; draft tidak publik; invalid dates dan cross-org ditolak |
| 3 Event Publication (B07; B13 setelah aturan cancel disepakati) | OA berlaku; EventStatusRequest tidak menerima status/parent/timestamp dari client | Update state dan timestamp server pada event berizin, tanpa membuka workspace privat | Lock event/recheck actor/state sesuai kontrak DATABASE.md | 302 overview + flash; published metadata tampil Radar; cancel mengikuti published_at historis dan dikecualikan collision, tanpa hard delete |
| 4 Committee (B09–B12) | ViewWorkspace untuk roster; OA/EL untuk member biasa; hanya OA menunjuk ketua; pair unik/scoped binding | Event.memberships tambah/ubah anggota; role coordinator dipisahkan; tidak menyentuh edisi lain | Parent event lock untuk mutasi terkait; ganti ketua atomik; pencabutan memeriksa semua rujukan task | 302 roster; ketua lama tidak lagi manage dari role coordinator, tanpa mencabut hak OA independen. Usulan penanganan task dan PATCH member→member masih perlu keputusan tim |
| 5 Administration (B01–B04) | UA di universitas sendiri untuk org; OA untuk membership; scope fakultas/prodi, masa/pair/role sesuai request | Org + initial OrganizationMember; update whitelist informasi/membership existing | Org+admin atomik; lock/recheck pada membership dan guard admin terakhir | Redirect administrasi/anggota + flash; hanya scope berizin berubah; admin terakhir/arsip/event membership terlindungi |

Prasyarat bukan perluasan urutan: MySQL lokal saat audit berisi **0 organisasi, 0 seri, 0 admin organisasi berlaku**. Sebelum browser dapat menjalankan slice 1, tim harus menyiapkan satu organisasi pilot dan akun OA tepercaya, tanpa membuat role pilihan register atau password admin default. Tidak ada bootstrap/data administratif yang dibuat otomatis pada audit ini.

### Handoff konkret slice 1 untuk tim

File existing yang perlu diselesaikan: EventSeriesController::store, EventSeriesRequest, Organization.eventSeries, EventSeries, serta events/series/form.blade.php. Tidak membutuhkan controller/service/repository tambahan.

1. Pertahankan urutan middleware web/auth/CSRF, binding organization dan authorize manage melalui EventSeriesRequest. OA wajib active, sudah joined dan belum ended. UA/platform admin tidak otomatis boleh create seri hanya dari role institusi.
2. Ambil name dari validated whitelist saja. Pemilik berasal dari model organization pada route, bukan organization_id payload atau user_id input. Gunakan operasi create pada relasi eventSeries organisasi agar parent FK terikat. Jangan memakai request->all().
3. Signature store sekarang `: never` karena abort(501). Tim perlu mengubahnya ke return type redirect yang sesuai ketika benar-benar mengembalikan RedirectResponse, bukan hanya menghapus abort.
4. Setelah insert berhasil, redirect ke route `series.index` dengan organization yang sama dan flash key `status`, yang sudah dirender layout. Jangan mengirim sukses sebelum DB berhasil.
5. Aktifkan tombol dan hapus notice belum diimplementasikan **hanya pada form seri** setelah endpoint dan tesnya benar-benar lulus; form mutasi lain tetap nonaktif selama masih 501.
6. PDM tidak memiliki UNIQUE(organization_id,name). Jangan diam-diam menambah uniqueness nama atau memperlakukan retry sebagai update existing. Perilaku duplikasi nama perlu review bila nantinya dibutuhkan; bukan alasan menambah schema sekarang.
7. Satu seri tidak membuat event/anggota/rapat/task/lesson. Series creation sendiri adalah satu insert. Dua edisi dalam satu seri diverifikasi terpisah pada slice event berikutnya; bukan disalin otomatis.

### Gate sebelum slice 2

- Valid name oleh OA: satu row baru, organization_id tepat, redirect/flash dan daftar menampilkan seri.
- Invalid name (kosong, bukan string, >180), organization_id payload, ordinary/event-only user, OA inactive/expired/future, atau route organisasi lain: request ditolak dan jumlah/data seri tidak berubah.
- Baca kembali dengan query MySQL berdasarkan id+organization_id dan lewat browser login OA pada series.index/show; pengguna organisasi lain ditolak.
- Kegagalan insert tidak memberi flash sukses dan tidak menyisakan row. Jika tim memilih transaksi/locking, uji kegagalan serta pencabutan hak concurrent dengan MySQL.
- Ganti assertion valid POST 501 dan count-unchanged pada test seri existing menjadi redirect+count meningkat+foreign key/name tersimpan **setelah implementasi tim tersedia**. Pertahankan assertion invalid/403/404/isolasi edisi.

Selama store masih 501 dan tombol nonaktif, hasil MySQL/browser persistence ditandai belum diuji. Menyisipkan data langsung lewat model/SQL untuk membuktikan relasi tidak boleh disebut bukti endpoint berhasil. Slice berikutnya belum diverifikasi sebagai fitur fungsional.


## 13. Phase 4 — Radar dan peninjauan jadwal (aktual)

`GET /events` tetap publik dan membaca DB. Query GET dapat dikombinasikan:

| Parameter | Kontrak |
| --- | --- |
| period | all (default), upcoming, history; upcoming mencakup published yang belum berakhir termasuk sedang berlangsung; history mencakup completed/cancelled atau jadwal yang sudah berakhir |
| q | Potongan nama event, maksimal 120 karakter; `%`/`_` literal, query parameterized |
| faculty_id | ID fakultas valid; cocok pada lingkup organisasi ATAU fakultas dari target prodi eksplisit |
| study_program_id | ID prodi valid; cocok pada organisasi ATAU target eksplisit; bila fakultas dipilih prodi wajib berasal dari fakultas itu |
| organization_id | ID organisasi penyelenggara valid |
| date_from, date_to | YYYY-MM-DD, rentang inklusif tanggal WITA; date_to tidak boleh sebelum date_from |
| page | Integer minimal 1; 12 event per halaman; filter dipertahankan pada pagination |

Urutan discovery menempatkan published yang belum berakhir terlebih dahulu (jadwal terdekat), lalu riwayat terbaru. Pengurutan dan filter periode diterapkan sebelum pagination; query parameter tetap dapat dibagikan. Status record tidak diubah. Tampilan merapikan prefiks fixture menjadi badge Demo, bukan mengubah data.

Filter tanggal mencari irisan jadwal dengan interval kalender WITA yang dikonversi UTC: ends_at > awal hari pertama dan starts_at < awal hari setelah tanggal terakhir. Tidak mengubah waktu penyimpanan. Filter fakultas/prodi bukan algoritma collision dan tidak mengasumsikan semua orang satu fakultas mempunyai target sama.

Scope publik tetap published_at terisi dan status published/completed/cancelled. Draft tidak tampil, termasuk draft dengan published_at lama. Cancelled ditandai **bukan kegiatan aktif**, completed sebagai riwayat. Metadata workspace tidak dikirim. Invalid GET web redirect sekali ke `/events` dengan error/old input; request yang meminta JSON mendapat 422. Opsi organisasi hanya organisasi dengan event publik. Relasi kartu eager-loaded.

### GET /workspace/events/{event}/conflicts — workspace.conflicts

Halaman membaca jadwal tersimpan dan memakai `ScheduleConflicts` bersama API mobile. Middleware auth dan EventPolicy::manage membatasi ke admin organisasi yang berlaku atau coordinator event. Anggota biasa dan admin universitas tanpa kewenangan event ditolak. Daftar pembanding hanya event publik published/completed, mengecualikan event sendiri, draft dan cancelled. `$warnings = null` berarti pemeriksaan tidak dijalankan karena event dibatalkan atau jadwal belum valid.

Titik kerja tim: `app/Http/Controllers/Web/WorkspaceController.php::conflicts`, view `resources/views/events/conflicts.blade.php`, komponen `components/collision-warning.blade.php`. Sesudah algoritma benar-benar tersedia, isi warnings berupa item `['event' => $publicEvent, 'types' => [...]]` dengan tipe time_overlap/venue_overlap/audience_overlap.

- Terapkan pencarian overlap pada seluruh pembanding eligible **sebelum pagination**, bukan hanya 12 row halaman pertama: startA < endB AND startB < endA. Waktu menyentuh batas bukan overlap. Kandidat cancelled tidak diperiksa sebagai kegiatan aktif.
- Untuk pasangan overlap, venue_overlap hanya jika dua venue terisi dan teksnya cocok setelah normalisasi sederhana (Phase 4B: trim, rapikan rangkaian whitespace menjadi satu spasi, lowercase Unicode). Jangan menghapus tanda baca/aksen, menyamakan singkatan atau ejaan berbeda, maupun mencocokkan venue kosong. Kolasi MySQL dapat menyamakan lebih banyak karakter; bandingkan hasil normalisasi secara konsisten, bukan mengandalkan kolasi SQL.
- audience_overlap berasal dari irisan ID target prodi eksplisit. Target kosong berarti informasi tidak cukup, bukan otomatis seluruh fakultas. Waktu tidak lengkap tidak memberi klaim konflik pasti.
- Satu pasangan dapat memiliki beberapa tipe, tanpa scoring, pembatalan otomatis atau klaim cakupan seluruh kampus. Jangan mengirim metadata privat ke view.

Link pada workspace dan edit hanya meninjau jadwal **yang tersimpan**, bukan input belum disimpan. Create/update/publish masih 501; integrasi sebelum publikasi belum bekerja. Seluruh 13 mutasi domain tetap 501. Kontrak autentikasi tetap sama.


### Phase 4B — handoff implementasi tim

Audit kode: `WorkspaceController::conflicts` masih mengisi warnings null; tidak ditemukan EventTarget model, karena target memakai Event::targets() belongsToMany melalui event_targets. Tidak perlu membuat model baru.

Input fungsi existing: Event dari route binding setelah Gate manage, atribut starts_at/ends_at/venue/status dan relasi targets. Output untuk view existing: warnings null bila belum dievaluasi; setelah evaluasi valid, daftar unik pasangan berisi event publik dan types (time_overlap, venue_overlap, audience_overlap). Daftar kosong hanya boleh berarti pemeriksaan benar-benar dijalankan. Kandidat cancelled atau waktu tidak valid memerlukan status tidak diperiksa yang jelas, bukan klaim bebas konflik.

Langkah tim pada fungsi yang sama: (1) periksa kelayakan jadwal kandidat; (2) batasi query publik published/completed dan exclude self; (3) terapkan dua kondisi waktu strict pada query sebelum pagination; (4) eager-load organisasi/targets; (5) klasifikasikan setiap pasangan menggunakan lokasi ternormalisasi dan irisan ID target; (6) kirim warnings ke view, cegah duplikasi dan sesuaikan label daftar yang kini masih bertuliskan belum dievaluasi. Jangan hanya mengganti null menjadi array kosong atau memeriksa 12 event pertama lalu menyimpulkan tidak ada konflik. Tidak membutuhkan service atau transaksi karena hanya read.

Normalisasi lokasi membuang spasi di awal dan akhir, menyatukan spasi beruntun, dan mengubah huruf menjadi lowercase Unicode. Contoh `  Aula   Teknik ` cocok dengan `aula teknik`. `Aula FT`, `NULL`, dan nilai kosong tidak dianggap lokasi yang sama dengan `Aula Teknik`. Implementasi aktif dijelaskan pada bagian 14.

## 14. Kontrak aktif untuk kegiatan, rapat, dan tugas di web

Semua 13 route mutasi pada inventaris B01–B13 kini menyimpan melalui validated whitelist. Series -> detail seri; event create/update/publish/cancel -> workspace; organisasi -> daftar administrasi universitas; tambah anggota organisasi -> daftar anggota, update -> Organisasi Saya (aman bila akses sendiri berakhir); panitia/ketua -> roster. Sukses menggunakan flash `status`, bukan JSON API baru. Tombol form sudah aktif.

Event baru draft; WITA dikonversi UTC, event+target memakai transaksi. Publish hanya draft dengan jadwal valid dan published_at dari server; cancel hanya draft/published, mempertahankan timestamp publikasi lama. Completed/cancelled tidak dapat dipublikasikan ulang. Tidak hard-delete event. Membership dan penunjukan ketua diserialisasi dengan row lock parent; terakhir admin aktif tidak boleh dicabut tanpa pengganti. Ketua lama menjadi member; tugas tetap utuh. Pengeluaran panitia ditolak 422 bila direferensikan tugas, termasuk done: alihkan PIC secara eksplisit dahulu.

Penyelesaian event memakai GET `/workspace/events/{event}/complete` untuk konfirmasi dan POST pada URL yang sama untuk menyimpan. Hanya admin organisasi dengan keanggotaan yang berlaku dapat mengubah event `published` dengan `published_at` terisi menjadi `completed`. POST memakai CSRF dan mengunci ulang event sebelum memeriksa izin dan status. `published_at` dan arsip tetap tersimpan. Sukses mengarahkan kembali ke ringkasan workspace dengan flash status.

Collision: query publik published/completed + published_at, jadwal valid, exclude self, dua kondisi waktu strict diterapkan sebelum pagination (12 pasangan per halaman). Venue trim/collapse whitespace/lowercase Unicode, kosong tidak cocok. Audience irisan ID target. Satu event muncul sekali, dapat memiliki beberapa tipe. Kandidat cancelled/jadwal invalid diberi status tidak diperiksa, bukan sukses kosong. Total pasangan dan pagination ditampilkan; halaman di luar rentang tidak menyatakan nol konflik. Input belum disimpan tidak diperiksa.

Rute baru di bawah prefix `/workspace/events/{event}` memakai auth + scoped binding; hak baca mengikuti viewWorkspace dan mutasi mengikuti manage pada EventPolicy yang sama. Admin universitas/platform tidak memperoleh bypass.

| Method | Suffix | Nama / fungsi |
| --- | --- | --- |
| GET | /meetings | workspace.meetings, daftar rapat |
| GET / POST | /meetings/create / /meetings | meetings.create / meetings.store |
| GET / GET / PATCH | /meetings/{meeting} / /meetings/{meeting}/edit / /meetings/{meeting} | meetings.show / edit / update |
| POST | /meetings/{meeting}/decisions | decisions.store |
| GET / PATCH | /meetings/{meeting}/decisions/{decision}/edit / /meetings/{meeting}/decisions/{decision} | decisions.edit / update |
| GET | /tasks | workspace.tasks, daftar dan ringkasan progres |
| GET / POST | /tasks/create / /tasks | tasks.create / store |
| GET / PATCH | /tasks/{task}/edit / /tasks/{task} | tasks.edit / update |
| PATCH | /tasks/{task}/status | tasks.status, manage atau assignee yang masih dapat mengakses workspace |

Rapat: title max180, scheduled_at WITA wajib, notes opsional max15000. Keputusan: summary wajib dan rationale opsional max15000; parent IDs route, bukan payload. Tugas: title max200, status todo/in_progress/done, due_at WITA opsional, assignee hanya anggota event, decision hanya rapat event yang sama. source_lesson_id tidak diterima pada alur ini. Manager dapat edit tugas/PIC; anggota hanya status tugas miliknya. GET `/my/tasks` membaca tugas sendiri yang masih dapat diakses. Tracker menerima GET `view=all|active|done`, `q` (judul, maksimal120 karakter), `status=todo|in_progress|done`, `event` (ID positif), `sort=deadline_asc|deadline_desc`, dan `page`. Semua filter diterapkan dalam scope tugas sendiri/workspace berizin sebelum pagination20; tenggat kosong selalu terakhir. Pilihan event hanya berasal dari tugas berizin pengguna. Tab mempertahankan pencarian/event/sort dan mengosongkan filter status; pagination mempertahankan query. PIC seluruh hasil adalah pengguna saat ini. Update status tetap memakai PATCH existing dengan CSRF dan konfirmasi eksplisit. Progress: total/todo/in_progress/done/overdue (due_at < now dan bukan done), seluruhnya query DB. Tidak menambah aksi hapus rapat/keputusan/tugas atau fitur di luar permintaan.

## 15. Phase 6 — evaluasi, legacy dan tindak lanjut (aktual)

Prefix `/workspace/events/{event}`, middleware auth/web/CSRF. Mutasi sukses redirect + flash; invalid 422 JSON/redirect web, unauthorized 403, nested parent mismatch 404. Halaman daftar/detail evaluasi menggunakan GET /legacy.

| Method | Suffix | Route / izin |
| --- | --- | --- |
| GET | /legacy | workspace.legacy; viewWorkspace, evaluasi sendiri + legacy eligible |
| GET / POST | /evaluations/create / /evaluations | evaluations.create/store; manage event |
| GET / PATCH | /evaluations/{lesson}/edit / /evaluations/{lesson} | evaluations.edit/update; manage event, belum approved, scoped ke event sumber |
| POST | /evaluations/{lesson}/approve | evaluations.approve; OA aktif pemilik, event memiliki seri valid |
| GET | /legacy/sources/{sourceLesson} | legacy.source; viewForEvent, hanya konteks evaluasi yang boleh dibaca |
| GET / POST | /legacy/sources/{sourceLesson}/action | legacy.action.create/store; manage event tujuan dan source eligible |

Evaluasi problem/recommendation wajib string max15000; event/author/visibility/approval dari server, tidak boleh diinput. Baru visibility event dan approved_at null. Approval menetapkan visibility series + timestamp server; approved tidak bisa diedit dan approve ulang tidak mengubah timestamp. Schema tidak memiliki approved_by, sehingga tidak menampilkan identitas approver buatan.

Inheritance: visibility series + approved_at tidak di masa depan; source dan tujuan satu organisasi/seri valid, source berbeda, source.starts_at < target.starts_at dan source.ends_at <= target.starts_at (UTC). Query eager-load event/author; tidak membagikan workspace, notulen atau tugas lama. Cross-edition source binding sengaja tidak scoped ke event.lessons, tetapi dijaga LessonPolicy. Internal lesson hanya dapat dibaca pada workspace sumber oleh pihak berizin.

Action memerlukan title max200, PIC dari panitia event tujuan, deadline WITA sebelum mulai event. Status awal todo dari server. Route menentukan source; payload event_id/lesson_id/source_lesson_id/status/approval ditolak. Transaksi mengunci event tujuan dan lesson, memeriksa ulang izin/kelayakan/PIC, lalu memeriksa task existing untuk pasangan event/source. Retry tidak membuat duplikat atau menimpa task pertama. Semua penulisan source baru melalui endpoint ini; form Task biasa menolak penggantian source. Tidak ada unique constraint baru: serialisasi dijaga parent lock aplikasi, bukan jaminan terhadap SQL manual di luar aplikasi.

Task Tracking, My Tasks dan edit task memperlihatkan tautan sumber dalam konteks event tujuan; tautan bukan akses otomatis ke workspace lama. Seri tidak dapat diganti setelah ada approved lesson atau source-linked task; jadwal tidak boleh membalik urutan edisi pada link existing. Tidak ada revoke/delete approval atau multi-level workflow.

### Lampiran evaluasi Legacy Insights

Route web memakai prefix dan middleware sesi yang sama seperti evaluasi.

`POST /evaluations` menerima `attachments[]` opsional lewat multipart. Jika ada lampiran, evaluasi dan metadata lampiran disimpan dalam satu transaksi. File yang sudah ditulis dibersihkan bila transaksi gagal.

| Method | Suffix | Izin |
| --- | --- | --- |
| POST | `/evaluations/{lesson}/attachments` | Izin edit evaluasi sumber yang belum disetujui. Form multipart dengan `attachments[]`. |
| DELETE | `/evaluations/{lesson}/attachments/{attachment}` | Izin edit evaluasi sumber. Lampiran harus milik evaluasi pada URL. |
| GET | `/legacy/sources/{sourceLesson}/attachments/{attachment}/{disposition}` | `LessonPolicy::viewForEvent` pada lesson dan event konteks. `disposition` ialah `preview` atau `download`. |

PDF dan gambar dapat dibuka di tab baru melalui route terotorisasi. DOCX hanya mendukung unduhan. Respons file memakai `Cache-Control: private, no-store` dan `X-Content-Type-Options: nosniff`. URL Radar tidak mencantumkan lampiran. Metadata file tetap terkait dengan evaluasi sumber, sehingga approval berlaku pada kumpulan file yang sama tanpa salinan antaredisi.

### Phase 7 — UX/validasi, URI dan otorisasi tetap

Dashboard menampilkan pintasan sesuai membership yang berlaku; admin institusi tidak dibawa ke workspace privat. Aksi publish/cancel yang tidak sesuai state disembunyikan pada UI dan tetap ditolak server. HTML 403/404/419/429 diberi pesan Indonesia serta tautan aman; status HTTP/JSON tidak diubah. Registrasi memvalidasi maksimum **72 byte** agar password tidak diterima melampaui batas efektif bcrypt (maksimal karakter sebelumnya tetap bukan jaminan byte). Form feedback/double-click guard merupakan progressive enhancement; tidak menggantikan validasi, CSRF, transaksi atau deduplikasi backend.

### Poster event dan logo organisasi di web

Form pembuatan dan pengubahan event menerima file `poster` opsional melalui multipart. Logo diunggah melalui `PUT /organizations/{organization}/logo` oleh admin organisasi, dengan field `logo`. Maksimal 2 MiB dan 4096 × 4096 piksel, dengan JPG, PNG, atau WebP.

`GET /events/{id}/poster` mengikuti visibilitas Radar atau izin workspace. `GET /organizations/{organization}/logo` tersedia bagi anggota organisasi berizin dan saat organisasi mempunyai event publik. File disajikan melalui Laravel dari disk privat.

## 16. API JSON Evaloop Android

Grup `/api/v1` memakai sesi Laravel melalui middleware `web` dan guard `web`. Kirim `Accept: application/json` dan gunakan `Content-Type: application/json` untuk body JSON. Semua respons sukses pada tabel di bawah berstatus `200`. Registrasi tetap melalui form web yang dibuka dari aplikasi Android.

| Method | Path | Akses dan respons `data` |
| --- | --- | --- |
| GET | `/api/v1/session` | Publik. Memulai atau meneruskan sesi, mengembalikan `csrf_token`. |
| POST | `/api/v1/login` | CSRF dan rate limiter login existing. Body `email` dan `password`. Sukses mengembalikan `user` dan token CSRF baru. |
| GET | `/api/v1/me` | Sesi autentikasi. Profil diri berisi `id`, `name`, `email`, dan `study_program` (objek `id` dan `name`, atau `null`). |
| POST | `/api/v1/logout` | Sesi autentikasi dan CSRF. Membatalkan sesi lama, mengembalikan `user: null` dan token baru. |
| GET | `/api/v1/events` | Publik. 12 event per halaman dengan filter, urutan, dan aturan publikasi Event Radar saat ini. |
| GET | `/api/v1/events/{event}` | Publik. Detail event yang lolos scope publik yang sama. Draft menghasilkan 404. |
| GET | `/api/v1/my/events` | Sesi autentikasi. Maksimal 12 workspace per halaman, `q` mencari judul atau penyelenggara. `featured` mengikuti aturan Workspace utama pada web. Akses panitia atau admin organisasi aktif. |
| GET | `/api/v1/my/events/{event}` | Sesi autentikasi dan `viewWorkspace` pada event. Ringkasan identitas, jadwal, lokasi, seri, ringkasan panitia, dan jumlah tugas per status. `can_manage` mengikuti policy `manage`. |
| GET | `/api/v1/my/events/{event}/conflicts` | Sesi autentikasi dan policy `manage`. Jadwal dan target prodi event sendiri, pembanding berupa metadata publik, dan alasan bentrokan. `page` mengatur 12 hasil per halaman. |
| GET | `/api/v1/my/events/{event}/meetings` | Policy `viewWorkspace`. Agenda dengan ringkasan notulen dan jumlah keputusan. Menerima `q` untuk judul/notulen dan `page` dengan 15 hasil. |
| GET | `/api/v1/my/events/{event}/meetings/{meeting}` | Policy `viewWorkspace` dan rapat terikat ke event. Notulen, keputusan, alasan, serta ringkasan tugas yang terhubung di event yang sama. |
| GET | `/api/v1/my/events/{event}/meetings/{meeting}/tasks/{task}` | Detail baca mengikuti akses workspace dan hanya untuk tugas dari keputusan di rapat tersebut. Nama PIC tersedia bila ditetapkan, tanpa email. |
| GET | `/api/v1/my/events/{event}/legacy` | Sesi autentikasi dan akses workspace event tujuan. Daftar evaluasi warisan, maksimal 10 per halaman. Menerima `page` dan pencarian `q`. |
| GET | `/api/v1/my/events/{event}/legacy/{lesson}` | Sesi autentikasi. Detail evaluasi yang memenuhi scope pewarisan untuk event tujuan, termasuk metadata gambar privat. |
| GET | `/api/v1/my/tasks` | Sesi autentikasi. Maksimal 20 tugas per halaman, dari tugas PIC sendiri pada workspace yang masih boleh diakses. Tenggat terdekat lebih dahulu, tenggat kosong di akhir. |
| GET | `/api/v1/my/tasks/{task}` | Sesi autentikasi. Tugas yang ada dalam scope daftar pribadi. |
| PATCH | `/api/v1/my/tasks/{task}/status` | Sesi autentikasi dan CSRF. Body `status` dengan nilai `todo`, `in_progress`, atau `done`. Sukses mengembalikan tugas yang sudah diperbarui. |

Daftar menggunakan paginator Laravel dengan `data`, `links`, dan `meta`. Event Radar menerima parameter yang tercantum pada bagian 13. Daftar Tugas Saya memakai parameter `view`, `q`, `status`, `event`, `sort`, dan `page` dengan nilai yang sama seperti tracker web. `task_filters.events` berisi ID dan judul event dari tugas pribadi yang dapat diakses. Timestamp JSON berformat ISO 8601 UTC.

Daftar Event Radar juga mengembalikan `radar.reference_time` untuk status jadwal, `radar.featured` (event publik yang memenuhi filter atau `null`), dan pilihan `radar.faculties`, `radar.study_programs`, serta `radar.organizations`. Opsi berisi `id` dan `name`; pilihan prodi juga berisi `faculty_id`. Organisasi pilihan hanya berasal dari penyelenggara dengan kegiatan publik. Objek `organization` pada daftar dan detail memuat nama fakultas dan prodi melalui `faculty` dan `study_program` bila tersedia.

### Contoh respons

Bootstrap sesi:

```json
{"data":{"csrf_token":"<token-dari-server>"}}
```

Login dan logout:

```json
{"data":{"user":{"id":7,"name":"Anggota Demo","email":"anggota@example.test","study_program":null},"csrf_token":"<token-baru>"}}
```

```json
{"data":{"user":null,"csrf_token":"<token-sesi-guest-baru>"}}
```

Profil pribadi, detail event, dan detail atau hasil pembaruan tugas:

```json
{"data":{"id":7,"name":"Anggota Demo","email":"anggota@example.test","study_program":null}}
```

```json
{
  "data": {
    "id": 5,
    "title": "Seminar Teknologi",
    "description": "Contoh deskripsi publik.",
    "organization": {"id": 2, "name": "HME", "faculty": null, "study_program": "Teknik Elektro"},
    "starts_at": "2027-05-15T01:00:00.000000Z",
    "ends_at": "2027-05-15T04:00:00.000000Z",
    "venue": "Aula Teknik",
    "status": "published",
    "published_at": "2027-04-01T00:00:00.000000Z",
    "poster_url": null
  }
}
```

```json
{"data":{"id":14,"title":"Siapkan registrasi","status":"in_progress","due_at":null,"event":{"id":3,"title":"Innovatech Challenge 2027"},"can_update_status":true,"source_event_title":null,"decision_summary":null}}
```

Validasi gagal:

```json
{"message":"Periksa kembali isian Anda.","errors":{"event_id":["Field ini tidak boleh dikirim."]}}
```

Error lain memakai `{"message":"..."}`. Sesi yang hilang menghasilkan 401 pada route privat. Akses yang ditolak dapat menghasilkan 403 atau 404. Tugas di luar scope pribadi menghasilkan 404. Token CSRF yang salah menghasilkan 419 dan dapat diperiksa sebelum izin pada request mutasi. Field dan kredensial yang tidak valid menghasilkan 422. Login pada sesi yang sudah aktif menghasilkan 409. Rate limiter mengembalikan 429 dan header `Retry-After`. Kesalahan server 500 serta gangguan database 503 memakai pesan umum.

### Cookie dan CSRF

1. Panggil `GET /api/v1/session`. Simpan cookie Laravel dari `Set-Cookie` pada cookie jar untuk host backend dan simpan `data.csrf_token` untuk sesi tersebut.
2. Pada `POST /api/v1/login`, kirim cookie yang sama dan header `X-CSRF-TOKEN` dengan nilai dari bootstrap. Simpan cookie dan token dari respons login karena Laravel meregenerasi sesi. Cookie menyimpan penanda sesi melalui mekanisme Laravel. ID sesi tidak dikirim di JSON.
3. Kirim cookie pada route privat. Pada setiap mutasi, sertakan token untuk sesi yang sedang aktif. Setelah logout, gunakan cookie dan token guest yang baru atau bootstrap ulang. Pada 419, perbarui sesi dan token sebelum mengirim ulang permintaan yang aman.
4. Gunakan HTTPS saat di luar lingkungan pengembangan. Session API, profil pribadi, tugas, dan respons error memakai `Cache-Control: private, no-store`. Jangan mencatat kredensial, cookie, atau token.

Pembaruan tugas memakai transaksi dan pemeriksaan izin yang sama dengan website. API ini membatasi aksi pada **Tugas Saya**, sehingga tugas harus milik PIC yang sedang login, termasuk saat dicek ulang setelah dikunci. Event, PIC, judul, keputusan, dan sumber evaluasi tidak dapat diubah melalui endpoint status.

Tugas Saya mengembalikan `source_event_title` hanya untuk evaluasi yang boleh diakses dalam konteks event tujuan. Detail dan respons pembaruan menyertakan `decision_summary` jika keputusan berasal dari rapat di event tugas. Aplikasi meminta konfirmasi baru bila PATCH ditolak dengan 419 dan tidak mengulang PATCH secara otomatis.

Daftar Legacy mengembalikan `event`, `data`, `meta` dan `summary`. Setiap evaluasi memuat permasalahan, rekomendasi, edisi sumber, penulis, tanggal persetujuan, dan ringkasan tindak lanjut jika ada. `personal_task_id` hanya tersedia untuk PIC. Detail menambahkan `images` dengan `id`, `name`, dan `size_bytes`. Flutter mengambil gambar lewat rute preview privat existing pada bagian 15 dengan cookie sesi. Rute tersebut tetap memeriksa izin setiap permintaan.

Ringkasan workspace menyertakan `has_poster` tanpa path storage. Jika tersedia, poster diminta melalui `GET /events/{id}/poster` dengan sesi. Tugas dari workspace memakai `GET /api/v1/my/tasks?event={event}` dengan scope tugas pribadi yang sama.

Pemeriksaan bentrokan mengembalikan `event`, `warnings`, `unavailable_reason`, serta `meta`. Setiap peringatan berisi metadata event pembanding yang sudah publik, beserta `reasons` dengan `type`, `label`, dan `description`. Tipe mengikuti logika web existing, yaitu `time_overlap`, `venue_overlap`, dan `audience_overlap`. `unavailable_reason` membedakan pemeriksaan yang tidak dijalankan dari hasil nol irisan.

Meeting Hub memakai `reference_time` dari server untuk mengelompokkan agenda berdasarkan waktu. Notulen dan keputusan dikembalikan sebagai teks. Detail tugas dari rapat selalu mengembalikan `can_update_status: false`. Pembaruan progres melalui Tugas Saya tetap memakai endpoint dan aturan yang sama.

## 17. Tindak lanjut Legacy di web dan Android

Rute draf web tersedia di `main` dan memerlukan konfigurasi layanan draf di server. Rute tindak lanjut yang berprefix `/api/v1` masih tersedia di backend lokal. Detail evaluasi Android pada backend lokal menambahkan `data.can_create_follow_up`, bernilai `true` bila policy `actOn` mengizinkan dan belum ada tugas untuk evaluasi sumber pada event tujuan.

| Method | Path | Respons |
| --- | --- | --- |
| GET | `/api/v1/my/events/{event}/legacy/{sourceLesson}/action` | `200` dengan `data.event` (`id`, `title`, `starts_at`) dan `data.members` (`id`, `name`) untuk panitia event tujuan. `409` bila tugas sudah ada. |
| POST | `/api/v1/my/events/{event}/legacy/{sourceLesson}/action` | `201` dengan `data.id` dan `data.created: true` ketika tugas dibuat. `200` dengan ID tugas lama dan `data.created: false` saat pengiriman ulang yang valid. |
| POST | `/api/v1/my/events/{event}/legacy/{sourceLesson}/action/draft` | `200` dengan `draft.title`, `draft.description`, dan `requires_review: true`. `409` bila tugas sudah ada, `422` bila request berisi field tambahan, `429` bila melewati batas, atau `503` bila layanan draf tidak tersedia. |
| POST | `/workspace/events/{event}/legacy/sources/{sourceLesson}/action/draft` | Respons JSON draf yang sama untuk form web. |

Seluruh rute memerlukan sesi dan policy `actOn` pada pasangan evaluasi sumber dan event tujuan. POST memerlukan CSRF. Simpan tugas dengan `title` maksimal 200 karakter, `assignee_user_id` anggota event tujuan, dan `due_at` berformat `YYYY-MM-DDTHH:mm` dalam WITA sebelum mulai event. Server mengisi status `todo` dan menentukan sumber dari URL. Ketentuan transaksi dan deduplikasi pada bagian 15 berlaku pada web dan Android.

Permintaan draf memakai body kosong selain token CSRF bila dibutuhkan dan dibatasi lima kali per menit. Layanan eksternal menerima potongan teks masalah, rekomendasi, serta judul dua edisi dari data server. Respons sukses memakai `Cache-Control: private, no-store`. Pengguna meninjau dan mengedit hasilnya sebelum menekan **Simpan tindak lanjut**. Deskripsi draf tersedia sebagai referensi dan tidak ikut disimpan dalam tugas.
