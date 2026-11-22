# EVALOOP — DEVELOPMENT ASSISTANCE: SCAFFOLDING, LOGIC REVIEW, DEBUGGING & SYNTAX CORRECTION

Saya sedang mengembangkan **Evaloop**, platform koordinasi kegiatan dan pembelajaran organisasi mahasiswa yang mengintegrasikan pengelolaan event, koordinasi kepanitiaan, tugas, serta pemanfaatan evaluasi dari penyelenggaraan sebelumnya.

Teknologi utama:
- Laravel 13 sebagai backend.
- Blade sebagai template engine.
- Tailwind CSS 4 untuk antarmuka responsif.
- MySQL sebagai database.
- PHP dan JavaScript untuk implementasi fitur.
- Flutter sebagai pengembangan aplikasi mobile pendamping.

Fitur utama Evaloop:
1. Event Radar — menampilkan kegiatan dari berbagai organisasi mahasiswa.
2. Jadwal & Bentrokan — mengidentifikasi potensi konflik berdasarkan waktu, lokasi, dan sasaran peserta.
3. Meeting Hub — mendokumentasikan rapat dan keputusan.
4. Tugas & Progres — mengatur penanggung jawab, tenggat, dan status pekerjaan.
5. Legacy Insights — menyimpan dan mewariskan pembelajaran kegiatan antaredisi.
6. Legacy-to-Action — memungkinkan rekomendasi evaluasi sebelumnya digunakan sebagai dasar pembuatan tugas pada edisi berikutnya.

Saya membutuhkan bantuan dalam empat kategori berikut.

## 1. SCAFFOLDING — PENYUSUNAN KERANGKA PROYEK

Bantu saya memeriksa dan menyiapkan kerangka awal aplikasi Evaloop dengan struktur Laravel yang rapi dan mudah dikembangkan.

Cakupan bantuan:

A. Struktur Proyek
- Memeriksa struktur direktori Laravel.
- Menyiapkan kerangka controller, model, middleware, form request, dan route.
- Menyiapkan struktur Blade dan Tailwind CSS.
- Memastikan dependency dan konfigurasi awal sesuai kebutuhan.
- Menyiapkan kerangka awal pengujian Laravel.

B. Database
- Membantu menyusun struktur awal migration berdasarkan rancangan database.
- Menyiapkan kerangka model dan relationship Eloquent.
- Memeriksa hubungan antara User, Organization, Event, EventSeries, Meeting, Task, dan Lesson berdasarkan model aktual.
- Memeriksa penggunaan primary key, foreign key, dan constraint.
- Memastikan rancangan mendukung integritas referensial.

C. Autentikasi
- Menyiapkan kerangka autentikasi berbasis session Laravel.
- Memeriksa middleware autentikasi.
- Menyiapkan struktur authorization berdasarkan peran pengguna.
- Memastikan CSRF protection tetap aktif.

D. Antarmuka
- Menyiapkan kerangka layout Blade.
- Menyiapkan komponen navigasi, form, tabel, dan tampilan dasar.
- Memastikan Tailwind CSS dan Vite terkonfigurasi dengan benar.
- Menyiapkan fondasi responsive design.

Batasan:
- Fokus pada kerangka dasar dan konfigurasi.
- Jangan langsung mengimplementasikan seluruh logika bisnis.
- Jangan membuat fitur di luar ruang lingkup Evaloop.
- Jangan menghapus atau mereset database.
- Jangan mengubah keputusan arsitektur tanpa penjelasan.

Jelaskan struktur yang disiapkan dan alasan pemilihan komponennya.

## 2. DISKUSI LOGIKA PEMROGRAMAN

Bantu saya meninjau logika dari fitur utama Evaloop sebelum atau selama implementasi.

A. Deteksi Bentrokan Jadwal

Analisis bagaimana dua kegiatan dapat dianggap berpotensi bentrok berdasarkan:
- Waktu mulai dan waktu selesai.
- Kesamaan atau perbedaan lokasi.
- Sasaran peserta.
- Status publikasi kegiatan.
- Organisasi penyelenggara.

Bahas:
- Rumus pemeriksaan interval waktu.
- Kondisi ketika dua kegiatan memiliki waktu beririsan.
- Kondisi ketika lokasi sama tetapi waktu berbeda.
- Kondisi ketika waktu sama tetapi sasaran peserta berbeda.
- Penanganan data jadwal yang tidak lengkap.
- Edge case seperti waktu mulai sama dengan waktu selesai kegiatan lain.

Jelaskan logika dan contoh kasusnya tanpa membuat asumsi yang bertentangan dengan aturan sistem.

B. Struktur Organisasi dan Hak Akses

Diskusikan mekanisme:
- Keanggotaan organisasi.
- Pembagian peran admin organisasi, ketua panitia, dan anggota.
- Pembatasan akses berdasarkan event.
- Pemisahan data privat antarorganisasi.
- Hubungan event dengan kepanitiaan.

Pastikan rancangan hak akses tidak memberikan kewenangan yang lebih luas daripada kebutuhan pengguna.

C. Meeting Hub dan Task

Analisis hubungan:
Meeting → Decision → Task.

Bahas bagaimana keputusan rapat dapat menjadi dasar pembuatan tugas, bagaimana PIC ditentukan, serta bagaimana progres pekerjaan diperbarui.

Perhatikan validasi, kemungkinan perubahan status, dan konsistensi hubungan antarentitas.

D. Legacy Insights dan Legacy-to-Action

Diskusikan alur:
Event Series → Edisi Sebelumnya → Evaluasi → Persetujuan → Edisi Berikutnya → Tugas Tindak Lanjut.

Analisis:
- Syarat evaluasi dapat diwariskan.
- Hubungan organisasi dan event series.
- Pengurutan edisi kegiatan.
- Hak akses evaluasi.
- Kondisi evaluasi belum disetujui.
- Pencegahan pembuatan tugas tindak lanjut yang tidak sesuai.
- Cara mempertahankan relasi antara tugas dan evaluasi sumber.

Pembuatan tugas harus tetap memerlukan tindakan pengguna, bukan otomatis.

Untuk setiap diskusi logika, berikan:
1. Penjelasan kondisi.
2. Contoh input dan output.
3. Edge case.
4. Potensi kesalahan.
5. Rekomendasi validasi.
6. Alternatif yang lebih sederhana jika tersedia.

## 3. DEBUGGING — PEMERIKSAAN DAN PERBAIKAN ERROR

Bantu saya melakukan debugging terhadap masalah yang muncul selama pengembangan Evaloop.

A. Laravel dan MySQL
- Periksa error koneksi database.
- Periksa konfigurasi environment.
- Analisis kegagalan migration.
- Periksa foreign key constraint.
- Periksa kesalahan relationship Eloquent.
- Periksa validasi dan penyimpanan data.
- Periksa query yang menghasilkan data tidak sesuai.

Jika migration bermasalah, identifikasi penyebabnya terlebih dahulu.

Jangan menggunakan migrate:fresh, db:wipe, atau reset database tanpa izin.

B. Autentikasi dan Authorization
- Analisis error login atau logout.
- Periksa session dan CSRF.
- Periksa middleware.
- Periksa policy yang menolak akses.
- Pastikan akses antarorganisasi dibatasi dengan benar.

Jangan menonaktifkan middleware keamanan hanya untuk menghilangkan error.

C. Routing dan Controller
- Periksa route yang tidak ditemukan.
- Periksa kesalahan parameter route.
- Periksa route model binding.
- Analisis respons 401, 403, 404, 419, 422, dan 500.
- Pastikan redirect dan respons JSON sesuai konteks pengguna.

D. Frontend
- Periksa kesalahan rendering Blade.
- Periksa asset Vite yang gagal dimuat.
- Periksa form yang tidak mengirim data.
- Periksa masalah navigasi.
- Periksa tampilan responsif yang mengalami overflow.
- Periksa kesalahan JavaScript pada interaksi pengguna.

E. Pengujian
- Jalankan pengujian yang relevan.
- Identifikasi assertion yang gagal.
- Jelaskan penyebab kegagalan.
- Perbaiki sumber masalahnya, bukan sekadar mengubah ekspektasi pengujian.
- Jalankan pengujian ulang setelah perbaikan.

Untuk setiap error:
1. Tunjukkan lokasi masalah.
2. Jelaskan akar penyebabnya.
3. Berikan perbaikan paling minimal.
4. Jelaskan risiko perubahan.
5. Verifikasi hasil perbaikan.

Jangan melakukan refactor besar jika masalah dapat diselesaikan melalui perubahan kecil.

## 4. PERBAIKAN KESALAHAN SINTAKS

Bantu saya mendeteksi dan memperbaiki kesalahan sintaks pada source code Evaloop.

A. PHP dan Laravel
- Kesalahan tanda kurung dan delimiter.
- Kesalahan penulisan namespace.
- Kesalahan import class.
- Kesalahan pemanggilan method.
- Kesalahan penulisan array.
- Kesalahan penggunaan operator.
- Kesalahan deklarasi function dan class.

B. Blade
- Kesalahan directive seperti @if, @foreach, dan @endif.
- Kesalahan penulisan variabel.
- Kesalahan pemanggilan komponen.
- Kesalahan atribut dan ekspresi Blade.
- Kesalahan penutupan struktur template.

C. JavaScript dan Tailwind
- Kesalahan sintaks JavaScript.
- Kesalahan import module.
- Kesalahan pemanggilan function.
- Kesalahan konfigurasi Vite.
- Kesalahan class atau konfigurasi styling yang menyebabkan build gagal.

D. Pemeriksaan

Gunakan pemeriksaan yang relevan seperti:
- PHP syntax lint.
- Laravel test.
- Blade compilation.
- Frontend production build.
- Pemeriksaan JavaScript.

Jelaskan setiap kesalahan yang ditemukan dan lakukan perbaikan secara minimal.

Jangan mengubah fungsi, desain, atau perilaku yang sudah benar hanya untuk merapikan sintaks.

## 5. BATASAN UMUM

Selama melakukan pekerjaan:

- Gunakan kode dan dokumentasi aktual Evaloop sebagai referensi utama.
- Jangan mengarang model, field, endpoint, atau hasil pengujian.
- Jangan mengimplementasikan fitur bisnis baru di luar cakupan bantuan.
- Jangan mengubah schema database tanpa kebutuhan dan persetujuan.
- Jangan menghapus data pengguna.
- Jangan melemahkan autentikasi atau authorization.
- Jangan menambahkan dependency tanpa alasan teknis.
- Jangan melakukan commit, push, atau deployment secara otomatis.
- Jangan menyatakan pengujian berhasil apabila belum dijalankan.
- Prioritaskan perubahan kecil dan mudah diverifikasi.

## 6. OUTPUT YANG DIHARAPKAN

Setelah pemeriksaan atau pengerjaan selesai, berikan laporan:

1. Bagian proyek yang diperiksa.
2. Kategori bantuan: scaffolding, diskusi logika, debugging, atau kesalahan sintaks.
3. Masalah atau kebutuhan yang ditemukan.
4. Penjelasan teknis.
5. Kerangka atau perbaikan yang diberikan.
6. File yang dibuat atau diubah.
7. Pengujian yang dijalankan.
8. Hasil pengujian.
9. Kendala yang belum terselesaikan.

Tujuan bantuan ini adalah mendukung penyusunan kerangka dasar, pemahaman logika, identifikasi error, dan perbaikan sintaks selama pengembangan Evaloop, sambil mempertahankan kendali pengembang terhadap keputusan dan implementasi perangkat lunak.