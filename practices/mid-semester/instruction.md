# SOAL UTS TAKE-HOME: CLOUD COMPUTING & SERVER TROUBLESHOOTING

**Mata Kuliah:** Cloud Computing

**Model Ujian:** Individual (Take-Home)

**Output:** Laporan PDF (Analisis Problem, Investigasi, & Perbaikan Server)

---

### PETUNJUK UMUM:

1. Kerjakan di Virtual Machine (Ubuntu Server + Nginx + PHP) yang telah Anda gunakan selama praktikum.
2. Unduh paket berkas UTS yang disediakan oleh Dosen (berisi folder `uts` yang di dalamnya terdapat `index.php`, `upload_form.php`, `generate_report.php`, serta file sampel uji coba berukuran besar **±300 MB**) melalui Google Classroom.
3. Kerjakan 4 skenario tugas di bawah ini secara berurutan. Dokumentasikan seluruh proses, hasil analisis, dan tangkapan layar ke dalam **1 File Laporan PDF**.

---

### 🚨 SKENARIO TUGAS PRAKTIKUM UTS

#### TUGAS 1: Konfigurasi Akses Jaringan & SSH (Bridge & Custom Port)

- **Skenario:**
  Ubah mode jaringan Virtual Machine Anda dari NAT menjadi **Bridge Adapter**. Selanjutnya, ubah konfigurasi SSH (`/etc/ssh/sshd_config`) agar service SSH tidak lagi menggunakan port default (Port 22), melainkan port khusus pilihan Anda (contoh: `2222`).
- **Pengujian:**
  Coba lakukan _remote login_ SSH dari terminal laptop host menggunakan IP Bridge dan port baru tersebut.
- **Yang Harus Dilaporkan di PDF:**
  - Screenshot perubahan mode jaringan ke Bridge.
  - Screenshot baris konfigurasi port di file `sshd_config`.
  - Screenshot bukti sukses _login_ SSH dari terminal laptop host menggunakan IP & Port baru.

---

#### TUGAS 2: Deployment Site Nginx & Pengujian Halaman Utama

- **Skenario:**

  1. Salin atau pindahkan folder `uts` yang diberikan oleh Dosen ke direktori web server server Anda pada path `/var/www/uts/`.
  2. Buat dan konfigurasikan _site block_ Nginx agar direktori tersebut dapat diakses menggunakan domain lokal `uts.local`.

- **Pengujian:**
  Buka browser di laptop host dan akses `uts.local` untuk memastikan file `index.php` terbaca dan situs berjalan dengan baik.
- **Yang Harus Dilaporkan di PDF:**
  - Screenshot konfigurasi file _site block_ Nginx yang Anda buat.
  - Screenshot akses `uts.local` di browser yang menampilkan instruksi dari file `index.php`.

---

#### TUGAS 3: Pengujian Form Upload 

- **Skenario:**
  Buka halaman `uts.local/upload_form.php` melalui browser Anda.
- **Pengujian & Analisis:**

  1. Apakah halaman dan fitur _upload_ tersebut dapat langsung berjalan dengan lancar saat mencoba mengunggah file?
  2. Cari tahu penyebabnya secara mandiri, lalu lakukan perbaika hingga proses _upload_ file berjalan sukses.

- **Yang Harus Dilaporkan di PDF:**
  - Screenshot hasil pengujian awal saat form diakses dan dicoba untuk mengunggah file.
  - Screenshot baris error pada log server yang menunjukkan penyebab masalah.
  - Screenshot perintah terminal saat Anda melakukan perbaikan (misal membuat folder atau mengatur _permission_).
  - Screenshot bukti file berhasil di-upload dan tersimpan dengan sempurna.

---

#### TUGAS 4: Uji Coba Upload file Ukuran Berkas (>300 MB)

- **Skenario:**
  Setelah fitur _upload_ di Tugas 3 berjalan normal, gunakan form yang sama untuk mengunggah file sampel berukuran besar (**>300 MB**) yang telah disediakan oleh Dosen.
- **Pengujian & Analisis:**
  Apakah file berukuran >300 MB tersebut berhasil terunggah? Jika server menolak dan menampilkan pesan kesalahan, analisis penyebabnya, temukan konfigurasi server yang membatasinya, lakukan penyesuaian parameter konfigurasi, _restart_ layanan, dan pastikan file besar tersebut berhasil diunggah dengan sempurna.
- **Yang Harus Dilaporkan di PDF:**
  - Screenshot hasil pengujian saat mengunggah file >300 MB pertama kali (menampilkan respons error dari server).
  - Analisis singkat mengapa server menolak file tersebut.
  - Screenshot baris konfigurasi yang Anda ubah/tambahkan di Nginx.
  - Screenshot bukti file >300 MB akhirnya berhasil terunggah sepenuhnya.

---

#### TUGAS 5: Eksekusi Fitur Laporan Dinamis & Analisis Modul PHP

- **Skenario:**
  Akses halaman `uts.local/generate_report.php` melalui browser Anda. Halaman ini bertugas mengolah data laporan dinamis dari skrip PHP.
- **Pengujian & Analisis:**
  Apakah halaman web dapat tampil dengan sempurna? Jika halaman mengalami kegagalan (_blank page_ / _Internal Server Error 500_), buka dan baca _error log_ server Anda untuk menemukan modul atau ekstensi PHP apa yang kurang/belum terpasang. Lakukan instalasi modul yang dibutuhkan melalui terminal, aktifkan, dan pastikan halaman laporan dapat diakses dengan sempurna.
- **Yang Harus Dilaporkan di PDF:**
  - Screenshot tampilan error / halaman gagal saat `generate_report.php` diakses pertama kali.
  - Screenshot baris _error log_ yang menunjukkan modul PHP yang hilang.
  - Screenshot perintah terminal saat Anda menginstal ekstensi PHP yang dibutuhkan.
  - Screenshot halaman `generate_report.php` yang sudah berhasil tampil sempurna di browser.

---

### 📄 FORMAT PENGUMPULAN LAPORAN:

Kumpulkan 1 file laporan berformat **PDF** ke Google Classroom dengan penamaan file:

`UTS_Cloud_[NIM]_[NamaLengkap].pdf`

\*Setiap tugas disusun secara berurutan dengan memuat: **(1) Pengujian Awal, (2) Analisis Masalah / Log, (3) Screenshot Perintah Terminal / Konfigurasi, dan (4) Screenshot Hasil Akhir Berhasil.\***
