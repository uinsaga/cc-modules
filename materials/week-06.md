# 🌐 Modul Week 06: PHP-FPM Configuration & Dynamic Web App Deployment

> **Format:** Praktikum bertahap. Setiap tahap punya tujuan, langkah, penjelas rinci, dan checkpoint.
> Selesaikan satu tahap dulu sebelum lanjut ke tahap berikutnya.

---

## 🗺️ Peta Perjalanan

| Tahap | Topik | Output |
| --- | --- | --- |
| **1** | Beda XAMPP vs LEMP & Konsep FastCGI | Paham arsitektur Nginx $\leftrightarrow$ PHP-FPM |
| **2** | Install & Konfigurasi Service PHP-FPM | Service PHP-FPM aktif via Unix Socket |
| **3** | Integrasi Server Block Nginx dengan PHP | Nginx bisa mengeksekusi `.php` |
| **4** | Pengujian & Tuning Konfigurasi PHP-FPM | Paham `php.ini` & `www.conf` dasar |
| **5** | Deploy Dynamic Web App (Aplikasi Kasir/Kalkulator) | Web dinamis berbasis PHP jalan di `cloud.local` |
| **6** | Tugas Akhir & Laporan | Laporan praktikum |

---

# 📘 TAHAP 1 — Konsep Nginx, PHP-FPM, dan FastCGI

## 1.1 Kenapa Beda dengan XAMPP (Apache)?

Di **XAMPP (Apache)**, modul PHP biasanya tertanam langsung di dalam web server (`mod_php`). Apache membaca file `.php` dan mengeksekusinya secara internal dalam satu proses yang sama.

Di **Nginx (LEMP Stack)**, Nginx **TIDAK BISA** memproses kode PHP sendiri. Nginx murni bertindak sebagai web server yang menangani file statis (HTML, CSS, JS, Gambar) dan meneruskan file dinamis ke program luar.

```text
               +-------------------------------------------------------+
               |                      VM / SERVER                      |
               |                                                       |
[Browser] ---> | [Nginx (Port 80/443)]                                 |
               |        |                                              |
               |        | (Kirim file .php via Unix Socket)            |
               |        v                                              |
               | [PHP-FPM Service] ---> Eksekusi Kode ---> Balikkan HTML|
               +-------------------------------------------------------+

```

## 1.2 Apa Itu PHP-FPM dan FastCGI?

* **FastCGI:** Protokol komunikasi standar antara web server (Nginx) dengan program pemroses aplikasi dinamis (PHP).
* **PHP-FPM (*FastCGI Process Manager*):** Layanan (*service*) independen yang berjalan terpisah di sistem operasi Linux, bertugas mengelola pool pemrosesan script PHP.
* **Unix Socket vs TCP Socket:**
* **Unix Domain Socket** (`/run/php/php8.3-fpm.sock`): Komunikasi via file internal sistem. Lebih cepat, hemat resource, cocok untuk 1 server.
* **TCP Socket** (`127.0.0.1:9000`): Komunikasi via port jaringan. Digunakan jika web server dan server PHP berada di VM/mesin terpisah.



---

# 🛠️ TAHAP 2 — Install & Konfigurasi Service PHP-FPM

**Tujuan:** Menginstall PHP-FPM beserta ekstensi dasar, dan memastikan service-nya aktif.

## 2.1 Update & Install PHP-FPM

SSH ke VM kamu:

```bash
ssh user@192.168.1.50

```

Update repositori dan install PHP-FPM beserta ekstensi standar yang sering digunakan aplikasi web:

```bash
sudo apt update
sudo apt install php-fpm php-cli php-common php-mbstring php-xml php-zip -y

```

> **Catatan Versioning:** Ubuntu versi terbaru akan otomatis menginstall versi stable seperti **PHP 8.1 / 8.2 / 8.3**. Cek versi yang terinstall dengan perintah:

```bash
php -v

```

*Ganti angka versi di langkah berikutnya sesuai output perintah di atas (misal: `php8.3-fpm` atau `php8.2-fpm`).*

## 2.2 Cek Status & Socket PHP-FPM

Pastikan *service* PHP-FPM berjalan:

```bash
sudo systemctl status php*-fpm

```

Harus berstatus **active (running)**.

Cek lokasi file socket yang dibuat oleh PHP-FPM:

```bash
ls -l /run/php/

```

Kamu akan melihat file bernama `php8.x-fpm.sock` (misal: `php8.3-fpm.sock`). Catat path ini!

### ✅ Checkpoint Tahap 2

* [ ] PHP-FPM ter-install dan berstatus **active (running)**
* [ ] Lokasi file Unix Socket `/run/php/php8.x-fpm.sock` ditemukan

---

# ⚙️ TAHAP 3 — Integrasi Server Block Nginx dengan PHP

**Tujuan:** Mengonfigurasi Nginx agar mengenali file `.php` dan mengarahkannya ke PHP-FPM.

## 3.1 Edit Server Block `cloud.local`

Buka file konfigurasi `cloud.local` yang dibuat pada pertemuan minggu lalu:

```bash
sudo nano /etc/nginx/sites-available/cloud.local

```

Ubah atau tambahkan directive berikut di dalam blok server (khususnya blok `listen 443 ssl` jika sudah HTTPS):

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name cloud.local;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name cloud.local;

    ssl_certificate     /etc/ssl/certs/cloud.local.crt;
    ssl_certificate_key /etc/ssl/private/cloud.local.key;

    root /var/www/cloud;

    # 1. Tambahkan index.php di urutan utama
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ =404;
    }

    # 2. Blok penanganan file PHP
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        
        # Sesuaikan dengan versi PHP yang terinstall (contoh: php8.3-fpm.sock)
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    # 3. Mencegah akses ke file hidden (.htaccess, .env, dll)
    location ~ /\.ht {
        deny all;
    }

    error_page 404 /404.html;
    location = /404.html {
        internal;
    }
}

```

## 3.2 Uji & Reload Nginx

Setiap selesai mengedit konfigurasi, **selalu** uji syntax:

```bash
sudo nginx -t

```

Jika `syntax is ok`, lakukan reload:

```bash
sudo systemctl reload nginx

```

## 3.3 Test Eksekusi PHP (`info.php`)

Buat file pengujian PHP di folder web root:

```bash
sudo nano /var/www/cloud/info.php

```

Isi dengan kode berikut:

```php
<?php
phpinfo();
?>

```

Buka di browser laptop: `[https://cloud.local/info.php](https://cloud.local/info.php)`

> **PENTING:** Jika muncul halaman konfigurasi PHP lengkap yang menampilkan informasi versi, environment, dan loaded extensions, artinya Nginx dan PHP-FPM sudah **terhubung sempurna!**

*Hapus file ini setelah selesai pengujian untuk alasan keamanan:*

```bash
sudo rm /var/www/cloud/info.php

```

### ✅ Checkpoint Tahap 3

* [ ] `sudo nginx -t` menunjukkan status sukses
* [ ] Halaman `info.php` berhasil dirender di browser melalui domain `[https://cloud.local/info.php](https://cloud.local/info.php)`

---

# 🎛️ TAHAP 4 — Pengujian & Tuning Konfigurasi PHP-FPM

**Tujuan:** Memahami letak file konfigurasi utama PHP dan menyesuaikan batasan resource untuk aplikasi produksi.

## 4.1 Mengenal File Konfigurasi PHP di Linux

Struktur folder konfigurasi PHP berada di `/etc/php/8.x/`:

```text
/etc/php/8.3/
├── cli/          # Konfigurasi untuk PHP Command Line Interface (Terminal)
└── fpm/          # Konfigurasi khusus untuk PHP-FPM (Web)
    ├── php.ini   # Pengaturan batas memori, upload, waktu eksekusi
    └── pool.d/
        └── www.conf # Pengaturan worker process & socket PHP-FPM

```

## 4.2 Tuning Parameter `php.ini` (Resource Limits)

Aplikasi web sering gagal mengunggah file atau timeout karena batasan bawaan PHP terlalu kecil.

Edit file `php.ini` milik FPM:

```bash
sudo nano /etc/php/8.3/fpm/php.ini

```

Cari parameter berikut (Gunakan `Ctrl + W` di Nano) dan sesuaikan nilainya:

```ini
upload_max_filesize = 20M
post_max_size = 25M
memory_limit = 256M
max_execution_time = 60
date.timezone = Asia/Jakarta

```

Simpan file (`Ctrl + O` $\to$ `Enter` $\to$ `Ctrl + X`).

## 4.3 Mengatur Worker Pool pada `www.conf`

File `www.conf` menentukan berapa banyak *process* PHP yang berjalan di latar belakang untuk melayani *request* bersamaan.

Edit file `www.conf`:

```bash
sudo nano /etc/php/8.3/fpm/pool.d/www.conf

```

Perhatikan bagian `pm` (*Process Manager*):

```ini
pm = dynamic
pm.max_children = 20
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 6

```

* **`pm.max_children`:** Jumlah maksimum proses PHP yang diperbolehkan berjalan sekaligus (mencegah kehabisan RAM).
* **`user` & `group`:** Pengelola hak akses file (default: `www-data`).

Setelah melakukan perubahan pada `php.ini` atau `www.conf`, **restart service PHP-FPM**:

```bash
sudo systemctl restart php8.3-fpm

```

### ✅ Checkpoint Tahap 4

* [ ] Paham perbedaan `/etc/php/8.x/cli` vs `/etc/php/8.x/fpm`
* [ ] Berhasil mengubah timezone dan batas upload di `php.ini`
* [ ] Service PHP-FPM berhasil di-restart tanpa error

---

# 🚀 TAHAP 5 — Deploy Dynamic Web App (Sistem Kasir / Kalkulator Toko)

**Tujuan:** Menambahkan aplikasi web berbasis PHP interaktif (multi-file, menangani sesi/POST request, dan perhitungan dinamis) ke dalam Nginx.

Aplikasi yang dipasang adalah **"Mini Point of Sale (POS) & Invoice Generator"** yang memproses input transaksi tanpa membutuhkan database MySQL terlebih dahulu.

## 5.1 Struktur Folder Aplikasi

Aplikasi akan ditempatkan di dalam direktori `/var/www/cloud/`:

```text
/var/www/cloud/
├── index.php         # Dashboard & Form Transaksi
├── proses.php        # Pemroses Kalkulasi & Struk
├── style.css         # Styling Tampilan UI
└── 404.html          # Custom error page minggu lalu

```

## 5.2 Buat File `style.css`

```bash
sudo nano /var/www/cloud/style.css

```

Isi dengan CSS berikut:

```css
* { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
body { background-color: #0f172a; color: #f8fafc; margin: 0; padding: 2rem; }
.container { max-width: 600px; margin: 0 auto; background: #1e293b; padding: 2rem; border-radius: 12px; border: 1px solid #334155; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3); }
h1, h2 { color: #38bdf8; text-align: center; }
.form-group { margin-bottom: 1.2rem; }
label { display: block; margin-bottom: .5rem; font-size: .9rem; color: #94a3b8; }
input, select { width: 100%; padding: .75rem; border-radius: 6px; border: 1px solid #475569; background: #0f172a; color: #fff; }
button { width: 100%; background: #0284c7; color: white; border: none; padding: .8rem; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 1rem; }
button:hover { background: #0369a1; }
.receipt-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
.receipt-table th, .receipt-table td { border-bottom: 1px solid #334155; padding: .75rem; text-align: left; }
.receipt-table th { color: #38bdf8; }
.total { font-size: 1.25rem; font-weight: bold; color: #4ade80; }
.badge { background: #334155; padding: .25rem .5rem; border-radius: 4px; font-size: .8rem; }
.btn-back { display: inline-block; text-align: center; margin-top: 1.5rem; text-decoration: none; color: #38bdf8; }

```

## 5.3 Buat File `index.php` (Form Input Transaksi)

```bash
sudo nano /var/www/cloud/index.php

```

Isi dengan kode berikut:

```php
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Kasir Cloud - Dynamic PHP</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>🛒 Mini POS Cloud</h1>
        <p style="text-align: center; color: #94a3b8; font-size: .9rem;">
            Server Time: <strong><?php echo date('d M Y H:i:s'); ?></strong>
        </p>
        <hr style="border-color: #334155; margin-bottom: 1.5rem;">

        <form action="proses.php" method="POST">
            <div class="form-group">
                <label for="nama_pelanggan">Nama Pelanggan / NIM</label>
                <input type="text" id="nama_pelanggan" name="nama_pelanggan" placeholder="Masukkan nama atau NIM" required>
            </div>

            <div class="form-group">
                <label for="item">Pilih Produk Layanan Cloud</label>
                <select id="item" name="item" required>
                    <option value="Virtual Private Server|150000">Virtual Private Server - Rp 150.000 / bln</option>
                    <option value="Object Storage 100GB|50000">Object Storage 100GB - Rp 50.000 / bln</option>
                    <option value="Managed Domain .LOCAL|25000">Managed Domain .LOCAL - Rp 25.000 / thn</option>
                </select>
            </div>

            <div class="form-group">
                <label for="jumlah">Jumlah / Durasi</label>
                <input type="number" id="jumlah" name="jumlah" min="1" value="1" required>
            </div>

            <div class="form-group">
                <label for="diskon">Kode Diskon Mahasiswa</label>
                <input type="text" id="diskon" name="diskon" placeholder="Masukkan 'KAMPUS' untuk diskon 20%">
            </div>

            <button type="submit">Hitung Total Transaksi &rarr;</button>
        </form>
    </div>
</body>
</html>

```

## 5.4 Buat File `proses.php` (Eksekusi Logika & Output)

```bash
sudo nano /var/www/cloud/proses.php

```

Isi dengan kode berikut:

```php
<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$nama_pelanggan = htmlspecialchars($_POST['nama_pelanggan']);
$raw_item = explode('|', $_POST['item']);
$nama_item = $raw_item[0];
$harga_satuan = (int)$raw_item[1];
$jumlah = (int)$_POST['jumlah'];
$kode_diskon = strtoupper(trim($_POST['diskon']));

// Kalkulasi
$subtotal = $harga_satuan * $jumlah;
$diskon_persen = 0;

if ($kode_diskon === 'KAMPUS') {
    $diskon_persen = 20;
}

$potongan_harga = ($subtotal * $diskon_persen) / 100;
$total_bayar = $subtotal - $potongan_harga;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Pembayaran - Cloud POS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>📄 Struk Transaksi</h2>
        <p style="text-align: center;"><span class="badge">PHP-FPM Processed</span></p>

        <table class="receipt-table">
            <tr>
                <th>Pelanggan</th>
                <td><?php echo $nama_pelanggan; ?></td>
            </tr>
            <tr>
                <th>Item Dipilih</th>
                <td><?php echo $nama_item; ?></td>
            </tr>
            <tr>
                <th>Harga Satuan</th>
                <td>Rp <?php echo number_format($harga_satuan, 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <th>Jumlah</th>
                <td><?php echo $jumlah; ?></td>
            </tr>
            <tr>
                <th>Subtotal</th>
                <td>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <th>Diskon (<?php echo $diskon_persen; ?>%)</th>
                <td>- Rp <?php echo number_format($potongan_harga, 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <th class="total">Total Bayar</th>
                <td class="total">Rp <?php echo number_format($total_bayar, 0, ',', '.'); ?></td>
            </tr>
        </table>

        <div style="text-align: center;">
            <a href="index.php" class="btn-back">&larr; Kembali ke Form Transaksi</a>
        </div>
    </div>
</body>
</html>

```

## 5.5 Sesuaikan Permission File

Pastikan user sistem web server (`www-data`) memiliki hak akses baca terhadap file aplikasi:

```bash
sudo chown -R www-data:www-data /var/www/cloud
sudo chmod -R 755 /var/www/cloud

```

## 5.6 Test di Browser

1. Akses `[https://cloud.local/](https://cloud.local/)`
2. Isi nama, pilih item, masukkan jumlah, dan ketik kode diskon `KAMPUS`.
3. Klik **Hitung Total Transaksi**.
4. Sistem akan mengeksekusi file `proses.php` secara dinamis dan menghasilkan perhitungan nilai yang akurat.

---

### ✅ Checkpoint Tahap 5

* [ ] Tampilan form aplikasi kasir muncul secara dinamis di `[https://cloud.local](https://cloud.local)`
* [ ] Pengiriman form via metode `POST` berhasil diproses oleh `proses.php`
* [ ] Logika kalkulasi (subtotal, diskon, dan total bayar) berjalan tepat

---

# 🎯 TAHAP 6 — Tugas Akhir & Laporan

## 6.1 Tugas Praktikum Pekan Ini

1. **Eksplorasi PHP-FPM:** Tambahkan satu opsi produk/layanan baru pada aplikasi di `index.php`.
2. **Kustomisasi Logika:** Buat kode diskon baru `DISKON10` untuk memberikan potongan 10% di file `proses.php`.
3. **Konfigurasi Lingkungan:** Atur batas memori PHP (`memory_limit`) pada `php.ini` menjadi `512M` dan pastikan konfigurasi tersebut berhasil diterapkan.

## 6.2 Screenshot Wajib untuk Laporan

1. Hasil eksekusi `php -v` dan status service PHP-FPM (`sudo systemctl status php*-fpm`).
2. Tampilan browser saat mengakses `[https://cloud.local/info.php](https://cloud.local/info.php)` (Menampilkan tabel informasi PHP).
3. Isi blok `location ~ \.php$` pada file konfigurasi Nginx (`/etc/nginx/sites-available/cloud.local`).
4. Tampilan Halaman Utama Form Transaksi (`[https://cloud.local/index.php](https://cloud.local/index.php)`).
5. Tampilan Halaman Hasil/Struk Transaksi (`proses.php`) setelah dikirimkan input data.

## 6.3 Format Laporan

File laporan disubmit dengan format penamaan:

**`Week06_[NIM]_[NamaMahasiswa].pdf`**

---

# 🧠 Kuis Cepat

1. Mengapa Nginx memerlukan PHP-FPM untuk menjalankan script PHP, sedangkan Apache bisa menjalankannya langsung melalui `mod_php`?
2. Apa fungsi dari directive `fastcgi_pass unix:/run/php/php8.3-fpm.sock;` pada konfigurasi Server Block Nginx?
3. Sebutkan perbedaan dasar antara penggunaan **Unix Domain Socket** dan **TCP Socket** pada koneksi FastCGI!
4. Di manakah lokasi konfigurasi `php.ini` khusus untuk modul Nginx/Web Server pada sistem operasi Ubuntu?
5. Mengapa disarankan menghapus file `info.php` dari server produksi setelah proses debugging/installasi selesai?

1. Nginx dirancang hanya untuk melayani file statis secara efisien (*event-driven*). Untuk mengeksekusi kode dinamis, Nginx mengdelegasikan tugas tersebut ke aplikasi luar via protokol FastCGI (PHP-FPM).
2. Directive tersebut memberitahu Nginx untuk meneruskan *request* file `.php` ke layanan PHP-FPM melalui saluran file Unix Domain Socket terkait.
3. **Unix Socket** menggunakan file internal OS (lebih cepat, hemat overhead, untuk 1 server), sedangkan **TCP Socket** menggunakan kombinasi IP:Port (bisa beda server/VM via jaringan).
4. Berada pada `/etc/php/8.x/fpm/php.ini`.
5. Karena file `info.php` mengekspos seluruh detail konfigurasi internal server, versi software, path direktori, hingga variabel lingkungan yang dapat dimanfaatkan oleh pihak tak bertanggung jawab untuk mencari celah keamanan.

---

# 🚨 Troubleshooting

| Masalah | Penyebab | Solusi |
| --- | --- | --- |
| **502 Bad Gateway** | Service PHP-FPM mati, atau path socket di config Nginx salah. | Cek status PHP-FPM (`systemctl status php*-fpm`). Cek apakah file `.sock` di `/run/php/` sesuai dengan baris `fastcgi_pass`. |
| **File PHP Terunduh (Not Processed)** | Konfigurasi `location ~ \.php$` belum ada atau belum di-reload. | Pastikan blok `location ~ \.php$` terpasang dengan benar di Nginx, jalankan `sudo nginx -t` lalu `sudo systemctl reload nginx`. |
| **404 Not Found saat akses .php** | File `.php` tidak berada di folder `root` yang ditentukan. | Cek directive `root` pada server block dan pastikan file `.php` berada di direktori tersebut. |
| **403 Forbidden** | Hak akses file/folder salah atau tidak ada file `index` yang cocok. | Jalankan `sudo chown -R www-data:www-data /var/www/cloud` dan pastikan `index.php` ditulis pada baris directive `index`. |

---

# 🧰 Cheatsheet

```bash
# Cek status & Restart PHP-FPM
sudo systemctl status php8.3-fpm
sudo systemctl restart php8.3-fpm
sudo systemctl reload php8.3-fpm

# Memeriksa lokasi socket aktif
ls -l /run/php/*.sock

# Mengecek error log khusus PHP-FPM
sudo tail -f /var/log/php8.3-fpm.log

# Mengecek error log Nginx
sudo tail -f /var/log/nginx/error.log

```

---

# ✅ Penutup

Setelah menyelesaikan modul praktikum Pekan 06 ini, kamu telah berhasil:

* Memahami arsitektur **LEMP Stack** dan cara kerja **FastCGI/PHP-FPM**.
* Mengonfigurasi Nginx agar sanggup memproses instruksi pemrograman dinamis.
* Melakukan tuning variabel dasar pada `php.ini` dan `www.conf`.
* Mengimplementasikan dan menguji **Aplikasi Web Dinamis** berbasis PHP pada *environment* server lokal.

Selamat mempraktikkan! 🚀
