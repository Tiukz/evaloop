# Aturan AI Assistant — Evaloop TecFest 2026 (v1.1)

Sebelum menyarankan perubahan, baca `README.md`, `docs/SCOPE.md`, dan berkas terkait. Jika file belum tersedia atau belum dibuka, jangan mengaku telah memeriksanya.

## Stack & scope wajib

- **Produk:** aplikasi web responsif dengan Laravel Blade + Tailwind CSS, satu backend Laravel, dan MySQL.
- **Tanpa Sanctum**, tanpa JWT/token auth buatan sendiri. Login/register email-password menggunakan **session auth Laravel bawaan**. Form mutasi memakai CSRF Laravel.
- Gunakan komponen Blade hanya ketika memang dapat dipakai ulang.
- Tetap pada **Event Radar, Event Workspace, Meeting Hub, Legacy Insights**. Jangan memakai **Calendar** sebagai nama fitur.
- Jangan menambah React, Vue SPA, Redis, Docker, microservices, chat, push notification, pembayaran, atau AI product features tanpa scope baru yang disetujui.
- `event_series` menyatukan edisi tahunan, tetapi anggota, rapat, tugas, dan hak akses setiap event berbeda.
- Keanggotaan organisasi bukan bukti keanggotaan kepanitiaan. Semua akses privat diverifikasi Laravel per objek.
- Register tidak langsung menjadikan pengguna admin/panitia. Role hanya boleh diberikan pihak berwenang.

## Pembatasan AI TecFest

Guidebook TecFest (halaman 6) membolehkan AI secara terbatas untuk **debugging, kerangka dasar, syntax error, dan diskusi logika**. Jangan mengambil alih pembuatan seluruh fitur/proyek yang melampaui ketentuan. 

## Peraturan kerja anti-halusinasi

1. **Periksa dulu:** pahami kode, versi framework, dan requirement sebelum membahas perbaikan.
2. **Pisahkan fakta dan dugaan:** gunakan `[TERVERIFIKASI]`, `[ASUMSI]`, `[BELUM DIKETAHUI]` bila perlu.
3. **Ubah yang diminta saja:** hindari refactor dan dependency tambahan tanpa alasan.
4. **Jangan mengubah schema/API diam-diam:** jelaskan dampak pada web, database, dan tes terlebih dahulu.
5. **Utamakan kode Laravel standar:** model/controller/request/policy; service hanya untuk logika yang memang berulang atau kompleks.
6. **Jangan palsukan bukti:** laporkan perintah test yang benar-benar dijalankan dan hasil aktual.
7. **Jaga keamanan:** hindari password plaintext, menyetel role dari form register, hardcoded credential, melewati CSRF, atau mempercayai `user_id` request sebagai identitas.
8. **Data simulasi diberi label.** Jangan menyebut dampak di kampus nyata tanpa validasi.

## Checklist sebelum suatu perubahan dianggap selesai

- Selaras `docs/SCOPE.md`, `docs/DATABASE.md`, dan `docs/API.md`.
- Hak akses relevan diperiksa server-side untuk semua rute web.
- Tes relevan tersedia dan hasilnya jujur dilaporkan.
- Perubahan tidak merusak tampilan web dan rute yang sudah ada.
- Tidak ada `.env`, password, data pribadi, dan secret masuk GitHub publik.

## Pola jawaban singkat

**Yang diverifikasi → Masalah/tujuan → Perubahan minimal → Risiko → Cara uji → Hal yang belum diketahui.**

Manusia tetap menjadi pengambil keputusan teknis dan bertanggung jawab atas implementasi akhir.

## Keputusan membership yang tetap berlaku (Phase 1, diselaraskan Phase 2)

- Terapkan aturan masa membership di `docs/DATABASE.md`: active, sudah mulai, dan belum mencapai ended_at. Satu record user–organisasi.
- `users.is_platform_admin` default false, hanya administrasi tepercaya; jangan mass assign dari register/profil. Tidak ada bypass policy global.
- Admin organisasi yang berlaku mengelola anggota/event, menunjuk ketua, dan mengakses workspace/arsip organisasinya sendiri.
- Ketua panitia memakai nilai role teknis `coordinator`; dapat mengelola anggota biasa, rapat, keputusan, tugas, evaluasi event sendiri. Penggantian ketua oleh admin organisasi.
- Event membership independen dari masa membership organisasi. Jangan mencabutnya secara otomatis.
- Konflik mengecualikan cancelled/draft, hanya event terpublikasi, memakai interval `[start,end)` dan tidak membocorkan metadata privat.
- Phase 2 mengizinkan migration/model, fondasi session auth web, policy dan kerangka Blade/Radar. Jangan mengimplementasikan modul bisnis penuh. Tidak melakukan commit otomatis.

## Produk aktif — University MVP v1.1

- Baca `EVALOOP_VERSION_TRACKER.md`; paket dokumentasi v1.1 lama bukan versi rilis produk.
- Satu universitas pilot, beberapa fakultas/prodi; organisasi dapat tingkat universitas/fakultas/prodi. Tidak membangun multi-university v1.2.
- Platform admin bootstrap universitas dan admin universitas pertama. Admin universitas dengan university_members berlaku mengelola struktur dan administrasi organisasi universitas sendiri, bukan membaca semua workspace privat.
- Prioritas keputusan: prompt eksplisit pengguna, tracker produk, flow terbaru, dokumen teknis, kode terverifikasi.
- Foreign key komposit harus menjaga scope organisasi sesuai DATABASE.md; jangan menggantinya dengan pemeriksaan UI saja.
- Tetap gunakan 15 tabel domain yang disepakati; sesi/cache file tidak membutuhkan tabel sessions/cache. Jangan migrate:fresh/reset atau menerapkan migration ketika kondisi DB belum diketahui.
- Jangan membuat fitur baru untuk menutupi kegagalan DB; tampilkan status layanan tidak tersedia secara jujur. Pengujian SQLite terisolasi hanya pemeriksaan fondasi, bukan bukti validasi constraint MySQL.

## Phase 3 — kerangka organisasi dan workspace

- Batas eksplisit pengguna tetap kerangka dasar, diskusi logika, debugging dan syntax; persistence/CRUD bisnis kompetisi dikerjakan tim.
- Halaman GET membaca DB sesuai policy; endpoint penulisan scaffold memeriksa auth/CSRF/binding/policy/validasi lalu mengembalikan 501 tanpa write. Jangan menyebut CRUD atau publikasi sudah bekerja. Form menandai batas ini dan tombol simpan nonaktif.
- Status organisasi belum memiliki kolom pada PDM. Jangan mengarang status organisasi dari status membership atau menambah kolom tanpa keputusan baru.
- Admin universitas hanya administrasi dasar organisasi universitasnya; tidak memperoleh akses daftar anggota organisasi atau workspace privat hanya dari role institusi.
- Riwayat seri penuh mencakup draft: batasi ke admin organisasi. Panitia satu edisi tidak mendapat akses otomatis ke semua edisi/lesson.
- Tetap pisahkan penunjukan ketua (admin organisasi) dari anggota biasa (admin organisasi/ketua event). Ketua tidak dapat mempromosikan anggota menjadi coordinator sendiri.
- Gunakan scoped nested binding pada organisasi/membership/seri dan event/membership. Parent IDs dari route, bukan mass assignment request. Jangan hard delete event/arsip.
- Root proyek sekarang /Users/user/EvaLoop. Metadata Git dipindahkan ke root atas persetujuan pengguna; jangan commit otomatis. Folder paket lama hanya menyisakan artefak lokal yang diabaikan Git.

