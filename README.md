# Evaloop

Evaloop adalah aplikasi web responsif untuk menemukan kegiatan kampus, mengelola kepanitiaan, dan meneruskan pembelajaran dari satu edisi acara ke edisi berikutnya. Mahasiswa bisa menjelajahi **Event Radar** tanpa akun. Panitia memakai ruang kerja privat untuk menyiapkan acara, mencatat keputusan, membagi tugas, dan mendokumentasikan evaluasi.

Proyek ini dikembangkan untuk **TecFest 2026**. Cakupan aktifnya adalah **University MVP v1.1**, dengan satu universitas pilot dan kegiatan lintas fakultas.

## Fitur utama

| Modul | Yang bisa dilakukan |
| --- | --- |
| **Event Radar** | Menemukan kegiatan publik, mencari berdasarkan kata kunci, menyaring berdasarkan fakultas, prodi, organisasi, tanggal, dan periode, serta membuka detail dan poster acara. |
| **Event Workspace** | Mengelola draft, jadwal, target peserta, poster, status publikasi, dan daftar panitia. **Event Series** menghubungkan edisi tahunan dengan kepanitiaan terpisah. Tab **Jadwal & Bentrokan** memeriksa irisan waktu, lokasi, dan target program studi terhadap kegiatan publik yang memenuhi syarat. |
| **Meeting Hub** | Menjadwalkan rapat, mencatat hasil dan keputusan, lalu membuat tugas tindak lanjut dengan PIC dan tenggat. Anggota memperbarui progres tugasnya melalui **Tugas Saya**. |
| **Legacy Insights** | Menyimpan masalah, rekomendasi, dan lampiran evaluasi. Setelah disetujui admin organisasi, pembelajaran dapat dibaca oleh panitia edisi berikutnya yang berwenang dan dijadikan tugas lewat **Buat Tindak Lanjut**. |

Admin organisasi juga bisa mengelola keanggotaan, logo HIMA, dan penunjukan ketua pada event miliknya. Admin universitas dapat mengelola organisasi dan menetapkan admin organisasi pertama dalam lingkup universitasnya.

## Menjalankan secara lokal

**Prasyarat** — PHP 8.3 atau lebih baru dengan ekstensi PDO MySQL, Fileinfo, dan Zip, Composer, MySQL 8.0.16 atau lebih baru, serta Node.js 20.19+ pada seri 20 atau 22.12+ dengan npm. Aplikasi memakai Laravel 13, Blade, Tailwind CSS 4, Vite, dan autentikasi sesi Laravel.

1. Dari root proyek, siapkan dependensi dan konfigurasi lokal.

   ```bash
   cd backend
   composer install
   npm ci
   cp -n .env.example .env
   ```

2. Buat database MySQL lokal, misalnya `evaloop_demo`. Di `backend/.env`, set `DB_DATABASE` ke nama database tersebut dan isi `DB_HOST`, `DB_PORT`, `DB_USERNAME`, serta `DB_PASSWORD` sesuai koneksi lokal. Isi `APP_KEY` pada instalasi baru, jalankan migrasi, dan bangun aset.

   ```bash
   php artisan key:generate
   php artisan migrate
   npm run build
   ```

   Pertahankan `APP_KEY` yang sudah ada saat memakai instalasi lama. Simpan kredensial hanya di `.env` lokal.

3. Bila ingin langsung mencoba peran dan workflow yang berbeda, jalankan [seeder demo](#data-demo-dengan-seeder). Setelah itu jalankan server dari `backend/`.

   ```bash
   php artisan serve --host=127.0.0.1 --port=8000
   ```

   Buka [Event Radar](http://127.0.0.1:8000/events) atau [halaman login](http://127.0.0.1:8000/login). Untuk pengembangan CSS dan JavaScript, jalankan `npm run dev` di terminal lain.

### Mencoba unggahan lampiran besar

PHP lokal sering menetapkan batas unggahan di bawah batas aplikasi. Untuk mencoba hingga 5 lampiran evaluasi berukuran 10 MiB per file, hentikan server sebelumnya dan jalankan perintah berikut dari `backend/` dengan konfigurasi batas bawaan Evaloop.

```bash
cd public
php -d upload_max_filesize=11M -d post_max_size=55M -S 127.0.0.1:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
```

Poster dan logo mendukung JPG, PNG, serta WebP hingga 2 MiB per gambar. Evaluasi mendukung PDF, DOCX, JPG, PNG, dan WebP. PDF dan gambar bisa dipratinjau, sedangkan DOCX tersedia untuk diunduh. File disimpan di `backend/storage/app/private/` dan dilayani oleh Laravel sesuai izin.

## Data demo dengan seeder

[EvaloopDemoSeeder](backend/database/seeders/EvaloopDemoSeeder.php) menyiapkan universitas simulasi, fakultas dan prodi contoh, dua organisasi HMTI dan HME, tujuh akun, event seri tahunan, skenario bentrokan, rapat, tugas, dan evaluasi. Gunakan database development terpisah untuk mencoba dataset ini.

1. Di `backend/.env`, set `APP_ENV=local` dan isi `EVALOOP_SEED_PASSWORD` dengan password demo pilihan sendiri, minimal **12 karakter** dan maksimal **72 byte**.
2. Setelah migrasi, jalankan dari `backend/`.

   ```bash
   php artisan config:clear
   php artisan db:seed --class=EvaloopDemoSeeder
   ```

3. Login menggunakan salah satu email berikut dan password yang dipakai saat akun tersebut pertama kali dibuat.

| Akun demo | Cocok untuk mencoba |
| --- | --- |
| `admin.university@evaloop.test` | Administrasi organisasi dalam universitas pilot. |
| `admin.informatika@evaloop.test` | Kelola HMTI, event, poster, panitia, dan persetujuan evaluasi. |
| `admin.elektro@evaloop.test` | Kelola HME dan periksa bentrokan Seminar Teknologi. |
| `ketua.lomba2027@evaloop.test` | Kelola workspace Innovatech Challenge 2027, rapat, keputusan, tugas, dan tindak lanjut evaluasi. |
| `anggota.lomba2027@evaloop.test` | Lihat workspace yang diikuti dan perbarui progres di Tugas Saya. |
| `mahasiswa@evaloop.test` | Coba pengalaman akun biasa tanpa keanggotaan. |
| `admin.platform@evaloop.test` | Coba akun dengan atribut admin platform. Akses workspace tetap mengikuti izin per event. |

Seeder berjalan pada environment `local` atau `testing` dan mempertahankan data serta password akun yang sudah ada. Mengubah `EVALOOP_SEED_PASSWORD` lalu seed ulang tidak mengganti password akun lama. Jika seeder menolak database yang sudah berisi universitas lain atau fixture versi lama, buat database demo baru.

## Cara memakai Evaloop

1. **Jelajahi Radar.** Buka `/events`, lalu cari **Seminar Teknologi** atau **Workshop AI**. Keduanya tersedia sebagai kegiatan publik pada dataset demo.
2. **Kelola acara.** Login sebagai admin HMTI, buka **Organisasi Saya** → **HMTI** → **Buat Event**. Pilih Event Series bila acara merupakan edisi tahunan, isi jadwal dan target peserta, lalu unggah poster jika tersedia. Draft yang disimpan bisa dibuka dari **Event Saya**.
3. **Tinjau bentrokan sebelum publikasi.** Dari workspace, buka **Jadwal & Bentrokan**. Untuk contoh hasil yang sudah terisi, login sebagai admin HME dan buka **Seminar Teknologi**. Jadwalnya beririsan dengan **Workshop AI**. Admin organisasi dapat memublikasikan event setelah meninjau jadwal.
4. **Atur kerja panitia.** Dari tab **Panitia**, admin organisasi menunjuk ketua. Ketua memakai **Meeting Hub** untuk mencatat keputusan dan tab **Tugas** untuk menetapkan PIC. Anggota membuka **Tugas Saya** untuk mengubah status tugasnya.
5. **Teruskan pembelajaran.** Buka **Legacy Insights** pada **Innovatech Challenge 2027** sebagai ketua demo. Evaluasi yang sudah disetujui dari edisi 2026 tersedia di sana. Salah satunya sudah memiliki tugas. Pilih evaluasi lain yang belum ditindaklanjuti untuk mencoba **Buat Tindak Lanjut**.

Registrasi biasa menghasilkan akun mahasiswa tanpa peran kepanitiaan. Hak mengelola organisasi dan acara diberikan melalui keanggotaan oleh pengguna yang berwenang.

## Pengujian

Jalankan dari `backend/`.

```bash
php vendor/bin/phpunit
node --test tests/UI/*.test.mjs
```

Panduan alur demonstrasi, regresi, dan hasil pengujian tersedia di [docs/TESTING.md](docs/TESTING.md).

## Riwayat versi produk

- **Versi awal v1.0 — Faculty Pilot.** Evaloop dirancang untuk satu fakultas dengan beberapa prodi dan organisasi. Empat modul inti ditetapkan pada tahap ini.
- **v1.1 — University MVP.** Cakupan berkembang menjadi satu universitas pilot dengan koordinasi lintas fakultas. Ini adalah target pengembangan aktif.

## Dokumentasi proyek

- [Scope dan aturan produk](docs/SCOPE.md), [alur pengguna](docs/FLOWS.md), dan [kontrak rute](docs/API.md)
- [Skema database](docs/DATABASE.md) dan [panduan desain](docs/DESIGN.md)
- [Aturan kerja proyek](AGENTS.md) dan [riwayat pengembangan](EVALOOP_VERSION_TRACKER.md)
- [Panduan TecFest](docs/TECFEST.md) dan [catatan penggunaan AI](docs/AI_USAGE_LOG.md)
