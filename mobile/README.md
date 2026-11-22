# Evaloop Mobile

Aplikasi Android native Flutter. Login, profil, dan logout memakai endpoint sesi Laravel di `/api/v1`. Event Radar membaca kegiatan publik dari Laravel. Tugas Saya menampilkan tugas yang diberikan kepada akun aktif dan menyimpan perubahan status melalui Laravel.

## Android Emulator

1. Jalankan Laravel pada port 8000 di komputer host.
2. Buka direktori `mobile/` sebagai proyek Flutter di Android Studio, pilih emulator, lalu jalankan dalam mode **Debug**. Aplikasi memakai `http://10.0.2.2:8000` secara otomatis.

## HP Android fisik melalui USB

Jalankan Laravel pada port 8000, aktifkan **USB debugging**, lalu sambungkan dan izinkan debug dari komputer. Pilih HP sebagai target di Android Studio, atau jalankan dari direktori `mobile/`:

```bash
adb devices
adb -s <id-hp> reverse tcp:8000 tcp:8000
flutter run -d <id-hp> --dart-define=API_BASE_URL=http://127.0.0.1:8000
```

Jika memakai tombol **Run** di Android Studio, masukkan bagian `--dart-define=...` di **Run → Edit Configurations → Additional run args**. Mulai ulang aplikasi setelah mengganti alamat API.

## Akses dari jaringan mana pun

Jalankan Laravel di server yang memiliki origin HTTPS publik. Laravel terhubung ke MySQL dari server. Flutter memakai origin yang sama untuk endpoint sesi dan halaman daftar. Pastikan endpoint `/api/v1/session` merespons JSON melalui HTTPS sebelum membangun APK.

Gunakan domain backend sebenarnya sebagai `API_BASE_URL` tanpa akhiran `/api/v1`. Contoh dari direktori `mobile/`:

```bash
flutter build apk --release --dart-define=API_BASE_URL=https://evaloop.example.com
```

Konfigurasi signing saat ini memakai kunci debug untuk APK uji. Siapkan signing rilis sebelum distribusi resmi. Simpan kredensial database dan signing di luar repo publik.

## Referensi visual

Website pada viewport 375 dan 390 px menjadi referensi untuk topbar publik, form login, drawer workspace, breadcrumb, menu akun, serta judul Dashboard, Event Radar, dan Tugas Saya. Tema Flutter memusatkan warna, radius input dan tombol, serta font Inter dan Geist. Wordmark memakai Manrope seperti website. Lisensi font disertakan di `assets/fonts/`.

Profil native memakai data `/api/v1/me`. Tautan Daftar membuka formulir web existing di browser Android. Cookie dan token CSRF berada di memori proses, sehingga pengguna masuk kembali setelah menutup proses aplikasi. Aplikasi memeriksa sesi ketika workspace kembali aktif.

## Pemeriksaan di Android Studio

Coba login dengan password salah dan benar. Buka drawer, buka Profil, cocokkan email dan program studi, lalu pilih Keluar. Periksa form saat keyboard terbuka dan navigasi pada layar 375–390 dp.

Untuk memeriksa sinkronisasi Tugas Saya, gunakan akun anggota demo pada database pengembangan. Catat status awal, buka detail tugas, pilih status lain, lalu tekan **Simpan status**. Setelah muncul konfirmasi, refresh **Tugas & progres** pada event yang sama di website dan cocokkan statusnya. Kembalikan tugas ke status awal setelah selesai bila fixture dipakai bersama.

Untuk Legacy Insights, buka **Event Saya**, pilih **Innovatech Challenge 2027**, lalu buka sumber evaluasi dari edisi sebelumnya. Jika evaluasi memiliki gambar, pratinjau tersedia di detail. Tombol **Lihat tugas** tersedia bila tindak lanjut ditugaskan kepada akun yang sedang login.

Pada **Event Saya**, pilih **Buka workspace**. Ringkasan menampilkan jadwal, panitia, dan angka progres seluruh event. **Tugas & Progres** membuka tugas pribadi pada event yang sama, dan **Legacy Insights** membuka evaluasi warisan. Ketua dan admin organisasi yang berwenang bisa membuka **Jadwal & bentrokan** dan menarik halaman ke bawah untuk memeriksa jadwal terbaru. **Lihat detail** membuka informasi publik kegiatan pembanding.

Buka **Meeting Hub → Buka catatan** untuk membaca notulen, keputusan, dan tugas terkait. Tekan **Muat ulang Meeting Hub** di bagian atas atau tarik halaman ke bawah untuk memuat data terbaru. Detail tugas dari rapat hanya untuk dibaca. Pengubahan notulen dan keputusan dilakukan melalui website.
