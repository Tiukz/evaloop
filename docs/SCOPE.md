# Scope & PRD Ringkas — Evaloop MVP v1.1

**Status produk** — University MVP v1.1 berpusat pada web responsif untuk satu universitas pilot. Android tersedia sebagai aplikasi pendamping dengan nomor build tersendiri. Validasi dampak pada pengguna kampus belum tercatat.

## 1. Masalah yang ingin divalidasi

Di satu universitas dengan beberapa fakultas dan prodi, panitia mungkin tidak mengetahui agenda organisasi lain, hasil rapat teknis bisa tidak memiliki tindak lanjut yang terlacak, dan evaluasi event tahun lalu sulit ditemukan saat tim berganti. Ini **hipotesis**, bukan hasil survei.

## 2. Produk & stack

- **Aplikasi web responsif**: Laravel Blade + Tailwind CSS.
- **Aplikasi pendamping Android** — Flutter, build `0.1.0+1`, memakai API JSON Laravel di `/api/v1`.
- **Backend dan database** — Satu backend Laravel dan satu database MySQL untuk kedua klien.
- **Akun**: register nama, email, password; login email/password; logout; sesi Laravel standar; **tanpa Sanctum**. Tidak ada OTP, SSO, login Google, atau email verification untuk demo MVP.
- **Tidak ada role global yang dipilih pengguna saat register.** Pemberian izin admin dan anggota event dilakukan oleh pengguna berwenang.

Web melayani akses dari browser desktop, tablet, dan ponsel, termasuk seluruh alur administrasi. Android mendampingi penjelajahan kegiatan, koordinasi panitia, dan pembaruan tugas pribadi.

## 3. Aktor dan hak akses

| Aktor | Boleh | Dilarang |
| --- | --- | --- |
| Pengunjung | Melihat metadata event dengan published_at dan status published/completed/cancelled; draft selalu privat. | Membaca draft, rapat, tugas, atau lesson privat. |
| Admin platform | Membuat universitas awal dan menetapkan admin universitas pertama melalui proses tepercaya. | Otomatis membaca workspace privat atau memberikan hak melalui register. |
| Admin universitas | Dengan membership universitas yang berlaku, mengelola administrasi dasar organisasi universitas sendiri dan menetapkan admin organisasi pertama. Pengelolaan fakultas dan prodi merupakan cakupan peran institusi. | Mengakses universitas lain atau otomatis membaca notulen/workspace privat. |
| Pengguna terdaftar | Kemampuan publik + profil diri; dapat diundang ke organisasi/event. | Memilih sendiri role admin/panitia. |
| Admin organisasi | Dengan membership berlaku: mengelola anggota, event/seri, publikasi, menunjuk ketua panitia, menyetujui lesson, membaca workspace dan arsip organisasinya. | Membaca workspace privat organisasi lain. |
| Ketua panitia | Mengelola anggota panitia, rapat, keputusan, tugas, dan evaluasi event yang dipimpin. | Membuat kebijakan organisasi lain atau memublikasikan tanpa hak admin. |
| Anggota event | Membaca workspace event yang diikutinya, mengubah status tugasnya sendiri. | Membaca event privat lain atau mengubah tugas pengguna lain. |

`organization_members` dan `event_members` adalah keanggotaan berbeda. Keduanya tidak diberikan hanya karena seseorang register. Akun demo admin dapat disiapkan lewat seeder secara eksplisit.

## 4. MVP wajib (P0)

| ID | Requirement | Acceptance criteria |
| --- | --- | --- |
| FR-01 | Register, login, logout email/password pada web. | Login sukses membentuk sesi, logout mencabut sesi aktif; request tanpa auth ditolak. |
| FR-02 | Event Radar publik di web. | Hanya event dengan `published_at` terisi dan status publik (`published`, `completed`, atau `cancelled`) yang muncul; draft tidak boleh tampil. |
| FR-03 | Buat dan kelola event berikut seri tahunannya. | Lomba A 2026 dan 2027 terhubung ke `event_series` yang sama. |
| FR-04 | Tinjau potensi bentrokan. | Irisan waktu menampilkan pembanding yang terpublikasi. Lokasi sama dan sasaran prodi beririsan ditampilkan sebagai alasan tambahan. |
| FR-05 | Workspace dan keanggotaan privat. | Panitia A tidak bisa membaca data privat event B tanpa izin. |
| FR-06 | Meeting Hub. | Ketua panitia mencatat rapat/keputusan, memberikan tugas ke anggota event. |
| FR-07 | Legacy Insights dan tindak lanjut. | Evaluasi yang telah disetujui dapat dibaca oleh panitia edisi penerima yang berwenang, termasuk lampiran. Ketua atau admin organisasi dapat membuat tugas terkait secara eksplisit di web. |

## 5. Business rules tetap

1. **Satu universitas pilot** untuk produk v1.1, dengan banyak fakultas/prodi. Organisasi dapat tingkat universitas, fakultas atau prodi. Tidak hardcode jumlah/nama institusi. v1.2 multi-university belum dibangun.
2. Event sekali jalan boleh `event_series_id = null`; edisi tahunan memakai seri yang sama.
3. Draft bersifat privat. Admin organisasi dapat memublikasikan draft dengan jadwal valid, menandai kegiatan terpublikasi selesai, atau membatalkan draft dan kegiatan terpublikasi. Riwayat publikasi dan arsip dipertahankan.
4. Deteksi bentrokan dibatasi pada event terpublikasi yang dapat diperiksa, berstatus published/completed; cancelled dan draft tidak dihitung; tidak menjamin seluruh agenda kampus tercakup.
5. Interval konflik `[starts_at, ends_at)`: `startA < endB && startB < endA`.
6. Konflik memberi peringatan, **bukan blokir otomatis**.
7. Pewarisan memerlukan evaluasi yang disetujui, organisasi dan seri yang sama, serta edisi sumber yang telah berakhir sebelum atau saat edisi penerima dimulai. Izin baca diperiksa pada workspace penerima. Evaluasi dan lampiran yang disetujui menjadi arsip tetap.
8. Rapat, keputusan, tugas, anggota privat, dan lesson privat tidak dikirim dalam respons publik.
9. Penanggung jawab tugas harus terdaftar pada `event_members` untuk event yang sama.
10. Mutasi web dan Android memakai sesi Laravel serta CSRF. Laravel memeriksa izin untuk setiap objek privat.

## 6. Di luar scope MVP

Pembayaran/pendaftaran peserta, absensi QR, booking fasilitas, chat/video, push notification, transkripsi rapat otomatis, analitik kompleks, approval birokrasi, SSO, login sosial, verifikasi email, manajemen banyak universitas, onboarding/billing institusi, portal admin terpisah, integrasi resmi universitas, real-time WebSocket, dan fitur lain yang tidak ada pada FR di atas.

## 7. Klaim hasil & batas keamanan

- Implementasi dan pengujian belum terbukti sampai dijalankan.
- Untuk demo gunakan akun/data simulasi berlabel, bukan data asli yang sensitif.
- Register tanpa verifikasi email **hanya memadai untuk demo tertutup**; untuk penggunaan organisasi nyata, penetapan keanggotaan perlu kontrol identitas tambahan.
- Tidak boleh mengklaim efisiensi meningkat tanpa hasil pengukuran.

## 8. Batas peran dan membership

- `is_platform_admin` default false dan tidak diterima dari register/profil. Pemberiannya hanya melalui administrasi tepercaya; tidak memberi akses privat global.
- Membership organisasi tunggal per user–organisasi: `status`, `joined_at`, `ended_at NULL`. Berlaku hanya saat active dan `joined_at <= now < ended_at` (tanpa batas akhir bila NULL).
- Admin organisasi wajib memiliki membership berlaku pada organisasi objek. Haknya termasuk workspace/arsip organisasi sendiri, tanpa perlu event membership.
- Ketua panitia (`event_members.role = coordinator`) mengelola panitia event sendiri; tidak bisa menunjuk/mengganti ketua atau menjadi admin organisasi. Penunjukan ketua oleh admin organisasi.
- Membership event terpisah; pada MVP record yang masih ada merupakan keanggotaan event. Membership organisasi berakhir tidak otomatis mencabut hak event. Sebelum mencabut anggota event, alihkan semua tugas yang masih merujuk kepadanya, termasuk tugas selesai.
- Daftar endpoint administratif/membership di API adalah dukungan hak akses yang disepakati, bukan modul baru atau portal admin terpisah.

## 9. Cakupan institusi dan data

- Event Radar mencakup kegiatan lintas fakultas dalam universitas pilot yang sama. Organisasi menjadi penyelenggara dan dapat menentukan beberapa prodi sasaran.
- Organisasi dapat berlingkup universitas, fakultas, atau prodi. Foreign key komposit menjaga kesesuaian organisasi dengan struktur institusinya. Status aktif atau nonaktif berlaku pada membership. Schema organisasi belum memiliki field status tersendiri.
- Bootstrap universitas dan admin pertama dilakukan melalui proses tepercaya. Web menyediakan administrasi organisasi di universitas yang sama. Antarmuka pengelolaan struktur fakultas dan prodi belum tercakup dalam rute web saat ini.
- Seri menghubungkan edisi dalam organisasi yang sama. Riwayat seri lengkap dapat dibuka oleh admin organisasi. Keanggotaan, rapat, keputusan, dan tugas dikelola per edisi.
- Poster event, logo organisasi, dan lampiran evaluasi disajikan melalui Laravel sesuai izin. Evaluasi dapat memuat hingga lima lampiran. Waktu tersimpan dalam UTC dan tampil dalam WITA.

## 10. Cakupan aplikasi Android

| Modul | Alur Android yang tersedia |
| --- | --- |
| Event Radar | Pengunjung membuka daftar, pencarian, filter, poster, dan detail kegiatan publik. |
| Event Workspace | Pengguna berizin membuka Event Saya, ringkasan jadwal, panitia, dan progres tugas. Ketua dan admin organisasi dapat memeriksa Jadwal & Bentrokan. |
| Meeting Hub | Panitia membaca agenda, notulen, keputusan, dan detail tugas terkait. PIC memperbarui status tugas pribadi melalui Tugas Saya. |
| Legacy Insights | Panitia membaca evaluasi warisan dan pratinjau gambar privat. PIC dapat membuka tugas tindak lanjut miliknya. |

Login, profil, dan logout tersedia di Android. Tautan pendaftaran membuka form web. Pembuatan event, pengaturan panitia, penyuntingan rapat, serta pembuatan dan persetujuan evaluasi dilakukan di web.

Source Flutter sudah mencakup form **Buat Tindak Lanjut**. Memuat pilihan PIC dan menyimpan tugas dari form ini masih memerlukan endpoint yang tersedia pada backend lokal. Cakupan API di `main` untuk Legacy Android meliputi pembacaan evaluasi dan tindak lanjut yang sudah ada.

## 11. Rujukan implementasi

- [Alur web dan Android](FLOWS.md)
- [Kontrak rute dan API](API.md)
- [ERD, PDM, dan aturan integritas](DATABASE.md)
- [Desain web dan Android](DESIGN.md)
- [Riwayat versi dan progres pengembangan](../EVALOOP_VERSION_TRACKER.md)
