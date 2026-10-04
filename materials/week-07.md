# 🌐 Modul Week 07: Database Integration (MariaDB) & Full LEMP Stack Deployment

> **Format:** Praktikum bertahap. Setiap tahap punya tujuan, langkah, penjelas rinci, dan checkpoint.
> Selesaikan satu tahap dulu sebelum lanjut ke tahap berikutnya.

---

## 🗺️ Peta Perjalanan

| Tahap | Topik | Output |
| --- | --- | --- |
| **1** | Pengenalan MariaDB & Arsitektur Full LEMP | Paham alur Nginx $\leftrightarrow$ PHP-FPM $\leftrightarrow$ MariaDB |
| **2** | Install & Hardening MariaDB Server | MariaDB aktif & aman di VM |
| **3** | Setup Database, Tabel, & User Akses | Database `db_cloud` & user khusus terbuat |
| **4** | Install Ekstensi Driver `php-mysql` | PHP-FPM mengenali perintah MySQL/MariaDB |
| **5** | Integrasi Web App (CRUD Data Transaksi) | Data dari web PHP tersimpan permanen di DB |
| **6** | Tugas Akhir, Laporan & Kisi-Kisi UTS | Laporan praktikum & persiapan UTS |

---

# 📘 TAHAP 1 — Arsitektur Full LEMP Stack

## 1.1 Melengkapi Komponen LEMP Stack

Pada Pekan 06, aplikasi PHP kita hanya mengolah data secara sementara di dalam memori RAM (*volatile*). Ketika halaman di-refresh atau server di-restart, data transaksi hilang.

Di Pekan 07 ini, kita melengkapi huruf **M** (**M**ariaDB/MySQL) untuk menyimpan data secara permanen (*persistent*) ke dalam media penyimpanan server.

```text
               +-------------------------------------------------------+
               |                      VM / SERVER                      |
               |                                                       |
[Browser] ---> | [Nginx (Port 80/443)]                                 |
               |        |                                              |
               |        | (Unix Socket)                                |
               |        v                                              |
               | [PHP-FPM Service]                                     |
               |        |                                              |
               |        | (Port 3306 / Unix Socket)                    |
               |        v                                              |
               | [MariaDB Service] ---> Simpan ke Storage/Disk        |
               +-------------------------------------------------------+

```

## 1.2 Mengapa MariaDB?

**MariaDB** adalah *fork* berlisensi open-source dari MySQL yang dikembangkan oleh pembuat asli MySQL. Di lingkungan Linux server (Ubuntu/Debian), MariaDB menjadi standar industri karena kompatibel 100% dengan query MySQL, lebih hemat resource, dan memiliki performa yang sangat stabil.

---

# 🛠️ TAHAP 2 — Install & Hardening MariaDB Server

**Tujuan:** Menginstall layanan MariaDB Server dan menjalankan prosedur pengamanan dasar (*hardening*).

## 2.1 Install MariaDB Server

SSH ke VM kamu:

```bash
ssh user@192.168.1.50

```

Update repositori dan install paket `mariadb-server`:

```bash
sudo apt update
sudo apt install mariadb-server -y

```

Cek status layanan MariaDB:

```bash
sudo systemctl status mariadb

```

Harus berstatus **active (running)**. Tekan `q` untuk keluar.

## 2.2 Amankan Database (*Security Hardening*)

Jalankan script pengaman bawaan MariaDB untuk menghapus pengaturan default yang berisiko:

```bash
sudo mysql_secure_installation

```

Jawab pertanyaan interaktif yang muncul sebagai berikut:

1. *Enter current password for root (enter for none):* Tekan **Enter**
2. *Switch to unix_socket authentication [Y/n]:* Ketik **n**
3. *Change the root password? [Y/n]:* Ketik **Y** lalu masukkan password root baru (misal: `Admin123!`)
4. *Remove anonymous users? [Y/n]:* Ketik **Y**
5. *Disallow root login remotely? [Y/n]:* Ketik **Y**
6. *Remove test database and access to it? [Y/n]:* Ketik **Y**
7. *Reload privilege tables now? [Y/n]:* Ketik **Y**

### ✅ Checkpoint Tahap 2

* [ ] Service MariaDB berstatus **active (running)**
* [ ] Script `mysql_secure_installation` selesai dijalankan

---

# ⚙️ TAHAP 3 — Setup Database, Tabel, & User Akses

**Tujuan:** Membikin struktur database baru dan *user* khusus aplikasi agar web tidak menggunakan account `root` (Prinsip *Least Privilege*).

## 3.1 Masuk ke Shell MariaDB

Masuk ke CLI MariaDB menggunakan akses superuser:

```bash
sudo mariadb -u root -p

```

*Masukkan password root MariaDB yang dibuat pada Tahap 2.2.*

## 3.2 Buat Database & User Khusus

Jalankan perintah SQL berikut di dalam prompt `MariaDB [(none)]>`:

```sql
-- 1. Buat database baru
CREATE DATABASE db_cloud;

-- 2. Buat user khusus aplikasi beserta password-nya
CREATE USER 'cloud_user'@'localhost' IDENTIFIED BY 'PasswordCloud123!';

-- 3. Berikan hak akses penuh user tersebut HANYA pada database db_cloud
GRANT ALL PRIVILEGES ON db_cloud.* TO 'cloud_user'@'localhost';

-- 4. Reload tabel hak akses
FLUSH PRIVILEGES;

-- 5. Pindah ke database db_cloud
USE db_cloud;

```

## 3.3 Buat Tabel Transaksi

Buat tabel `transaksi` untuk menyimpan data pesanan dari aplikasi web:

```sql
CREATE TABLE transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_pelanggan VARCHAR(100) NOT NULL,
    nama_item VARCHAR(100) NOT NULL,
    harga_satuan INT NOT NULL,
    jumlah INT NOT NULL,
    subtotal INT NOT NULL,
    diskon INT NOT NULL,
    total_bayar INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

```

Cek apakah tabel berhasil dibuat:

```sql
SHOW TABLES;
DESCRIBE transaksi;

```

Ketik `exit;` untuk keluar dari MariaDB CLI.

## 3.4 Uji Login dengan User Baru

Pastikan user `cloud_user` bisa masuk ke database:

```bash
mariadb -u cloud_user -p db_cloud

```

*Masukkan password: `PasswordCloud123!*`. Jika berhasil masuk, ketik `exit;`.

### ✅ Checkpoint Tahap 3

* [ ] Database `db_cloud` dan tabel `transaksi` berhasil dibuat
* [ ] User `cloud_user` bisa login dan mengakses `db_cloud`

---

# 🔌 TAHAP 4 — Install Ekstensi Driver `php-mysql`

**Tujuan:** Memasang driver pustaka agar modul PHP-FPM dapat berkomunikasi dengan database MariaDB.

## 4.1 Install Driver PHP MySQL

Jalankan perintah berikut:

```bash
sudo apt install php-mysql -y

```

## 4.2 Restart Service PHP-FPM & Nginx

Setiap kali menginstall ekstensi PHP baru, *service* PHP-FPM **wajib** di-restart agar driver dimuat ke dalam RAM:

```bash
sudo systemctl restart php8.3-fpm
sudo systemctl reload nginx

```

*(Sesuaikan versi PHP dengan VM kamu, misal: `php8.1-fpm` atau `php8.2-fpm`)*

## 4.3 Verifikasi Driver via CLI

Cek apakah modul `mysqli` dan `pdo_mysql` sudah aktif:

```bash
php -m | grep -i mysql

```

Output harus menampilkan `mysqli` dan `pdo_mysql`.

### ✅ Checkpoint Tahap 4

* [ ] Paket `php-mysql` ter-install
* [ ] Service PHP-FPM di-restart tanpa error
* [ ] Output `php -m | grep -i mysql` menampilkan ekstensi PDO/MySQLi

---

# 🚀 TAHAP 5 — Integrasi Web App (CRUD Data Transaksi)

**Tujuan:** Memperbarui aplikasi POS Pekan 06 agar data transaksi tersimpan ke MariaDB dan dapat ditampilkan kembali dalam bentuk riwayat (*Read*).

## 5.1 Buat File Koneksi Database (`koneksi.php`)

Buat file baru di root folder web untuk mengelola koneksi PDO (*PHP Data Objects*):

```bash
sudo nano /var/www/cloud/koneksi.php

```

Isi dengan kode berikut:

```php
<?php
$host     = 'localhost';
$dbname   = 'db_cloud';
$username = 'cloud_user';
$password = 'PasswordCloud123!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}
?>

```

## 5.2 Update File `proses.php` (Simpan Data ke Database)

Buka file `proses.php`:

```bash
sudo nano /var/www/cloud/proses.php

```

Ubah kodenya agar menyisipkan fungsi `INSERT INTO` ke database:

```php
<?php
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$nama_pelanggan = htmlspecialchars($_POST['nama_pelanggan']);
$raw_item       = explode('|', $_POST['item']);
$nama_item      = $raw_item[0];
$harga_satuan   = (int)$raw_item[1];
$jumlah         = (int)$_POST['jumlah'];
$kode_diskon    = strtoupper(trim($_POST['diskon']));

// Kalkulasi
$subtotal      = $harga_satuan * $jumlah;
$diskon_persen = ($kode_diskon === 'KAMPUS') ? 20 : 0;
$potongan_harga = ($subtotal * $diskon_persen) / 100;
$total_bayar   = $subtotal - $potongan_harga;

// SIMPAN KE MARIADB VIA PDO PREPARED STATEMENT
try {
    $stmt = $pdo->prepare("INSERT INTO transaksi 
        (nama_pelanggan, nama_item, harga_satuan, jumlah, subtotal, diskon, total_bayar) 
        VALUES (:nama, :item, :harga, :jumlah, :subtotal, :diskon, :total)");
    
    $stmt->execute([
        ':nama'     => $nama_pelanggan,
        ':item'     => $nama_item,
        ':harga'    => $harga_satuan,
        ':jumlah'   => $jumlah,
        ':subtotal' => $subtotal,
        ':diskon'   => $potongan_harga,
        ':total'    => $total_bayar
    ]);
} catch (PDOException $e) {
    die("Gagal menyimpan transaksi: " . $e->getMessage());
}
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
        <p style="text-align: center;"><span class="badge" style="background:#16a34a;">Saved to MariaDB</span></p>

        <table class="receipt-table">
            <tr><th>Pelanggan</th><td><?php echo $nama_pelanggan; ?></td></tr>
            <tr><th>Item Dipilih</th><td><?php echo $nama_item; ?></td></tr>
            <tr><th>Harga Satuan</th><td>Rp <?php echo number_format($harga_satuan, 0, ',', '.'); ?></td></tr>
            <tr><th>Jumlah</th><td><?php echo $jumlah; ?></td></tr>
            <tr><th>Subtotal</th><td>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td></tr>
            <tr><th>Diskon (<?php echo $diskon_persen; ?>%)</th><td>- Rp <?php echo number_format($potongan_harga, 0, ',', '.'); ?></td></tr>
            <tr><th class="total">Total Bayar</th><td class="total">Rp <?php echo number_format($total_bayar, 0, ',', '.'); ?></td></tr>
        </table>

        <div style="text-align: center; margin-top: 1.5rem;">
            <a href="index.php" class="btn-back">&larr; Transaksi Baru</a> | 
            <a href="riwayat.php" class="btn-back" style="color:#4ade80;">Lihat Riwayat &rarr;</a>
        </div>
    </div>
</body>
</html>

```

## 5.3 Buat File `riwayat.php` (Tampilkan Riwayat Transaksi)

Buat file baru `riwayat.php` untuk menampilkan daftar transaksi yang tersimpan di MariaDB:

```bash
sudo nano /var/www/cloud/riwayat.php

```

Isi dengan kode berikut:

```php
<?php
require_once 'koneksi.php';

try {
    $stmt = $pdo->query("SELECT * FROM transaksi ORDER BY id DESC");
    $daftar_transaksi = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal mengambil data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Transaksi - Cloud POS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 900px;">
        <h1>📊 Riwayat Transaksi Cloud</h1>
        <p style="text-align: center; color: #94a3b8;">Data ditarik langsung dari MariaDB Server</p>
        <hr style="border-color: #334155; margin-bottom: 1.5rem;">

        <table class="receipt-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Waktu</th>
                    <th>Pelanggan</th>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Total Bayar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($daftar_transaksi) > 0): ?>
                    <?php foreach ($daftar_transaksi as $row): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><small><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></small></td>
                            <td><?php echo htmlspecialchars($row['nama_pelanggan']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_item']); ?></td>
                            <td><?php echo $row['jumlah']; ?></td>
                            <td style="color:#4ade80; font-weight:bold;">Rp <?php echo number_format($row['total_bayar'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;">Belum ada data transaksi.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="text-align: center; margin-top: 1.5rem;">
            <a href="index.php" class="btn-back">&larr; Kembali ke Form Transaksi</a>
        </div>
    </div>
</body>
</html>

```

## 5.4 Update Permissions & Testing

Atur ulang hak akses folder web:

```bash
sudo chown -R www-data:www-data /var/www/cloud
sudo chmod -R 755 /var/www/cloud

```

**Pengujian Aplikasi:**

1. Buka browser: `[https://cloud.local/](https://cloud.local/)`
2. Masukkan transaksi baru (Nama, Item, Jumlah) dan kirim.
3. Klik tombol **Lihat Riwayat** atau buka `[https://cloud.local/riwayat.php](https://cloud.local/riwayat.php)`.
4. Pastikan transaksi yang baru dimasukkan tampil di dalam tabel riwayat!

---

# 🎯 TAHAP 6 — Tugas Akhir, Laporan & Kisi-Kisi UTS

## 6.1 Tugas Praktikum Pekan Ini

1. **Uji Validasi Data:** Lakukan input minimal 3 transaksi berbeda via web browser.
2. **Verifikasi via CLI:** Masuk ke CLI MariaDB (`mariadb -u cloud_user -p db_cloud`) dan jalankan query `SELECT * FROM transaksi;`. Ambil screenshot hasil query CLI tersebut.

## 6.2 Screenshot Wajib untuk Laporan

1. Output status service MariaDB (`sudo systemctl status mariadb`).
2. Output command `SHOW TABLES;` dan `DESCRIBE transaksi;` pada MariaDB CLI.
3. Tampilan Halaman Struk Transaksi (`proses.php`) yang menampilkan indikator **Saved to MariaDB**.
4. Tampilan Halaman Riwayat Transaksi (`riwayat.php`) yang berisi minimal 3 data transaksi.
5. Tampilan CLI MariaDB saat mengeksekusi `SELECT * FROM transaksi;`.

## 6.3 Format Laporan

File laporan disubmit dengan format penamaan:

**`Week07_[NIM]_[NamaMahasiswa].pdf`**

---

# 📝 KISI-KISI REVIEW UJIAN TENGAH SEMESTER (UTS)

Materi UTS akan mencakup seluruh praktikum dari **Week 01 s.d. Week 07**:

1. **Virtualization & Network:** Konfigurasi VM VirtualBox (Bridged Adapter vs NAT), alokasi IP, SSH remote management.
2. **Nginx Web Server Basics:** Installation, Service Management (`systemctl`), Port 80 (HTTP) & 443 (HTTPS), Struktur Direktori (`/etc/nginx/`).
3. **Local Domain & Server Block:** Pengaturan `/etc/hosts`, pembuatan Virtual Host (Server Block), Symlink `sites-available` $\to$ `sites-enabled`.
4. **SSL / HTTPS Lokal:** Konsep sertifikat digital, penggunaan Self-Signed Certificate / `mkcert`.
5. **LEMP Stack Integration:**
* Peran **PHP-FPM** dan konfigurasi Unix Socket.
* Konfigurasi `php.ini` & `www.conf`.
* Pengelolaan Database **MariaDB** (User, Privilege, Table).
* Driver `php-mysql` & Prepared Statement (PDO) untuk keamanan aplikasi web.



---

# 🧠 Kuis Cepat

1. Mengapa sangat tidak disarankan menggunakan account `root` MariaDB untuk koneksi dari script aplikasi web?
2. Apa keuntungan menggunakan PDO (*PHP Data Objects*) dan *Prepared Statements* dibandingkan query SQL biasa?
3. Mengapa setelah menginstall paket `php-mysql`, kita harus melakukan restart pada service `php-fpm`?
4. Apa fungsi dari perintah `FLUSH PRIVILEGES;` pada MariaDB CLI?
5. Di manakah file database MariaDB secara fisik disimpan pada sistem operasi Ubuntu Server?

1. Menggunakan account `root` melanggar prinsip keamanan *Least Privilege*. Jika aplikasi web memiliki celah keamanan (misal SQL Injection), peretas bisa mendapatkan kontrol penuh atas seluruh sistem database dan server.
2. PDO mendukung multi-database driver dan *Prepared Statements* secara otomatis mengisolasi input dari pengguna, sehingga mencegah serangan **SQL Injection**.
3. Agar service PHP-FPM memuat (*load*) modul/driver pustaka baru `pdo_mysql` ke dalam memori sistem saat mengeksekusi script.
4. Menyegarkan (*reload*) memori internal server MariaDB agar perubahan hak akses (*grant/privilege*) user yang baru dibuat segera diterapkan tanpa perlu meng-restart service database.
5. Secara default tersimpan di direktori `/var/lib/mysql/`.

---

# 🚨 Troubleshooting

| Masalah | Penyebab | Solusi |
| --- | --- | --- |
| **SQLSTATE[HY000] [1045] Access denied for user** | Username/password di `koneksi.php` tidak cocok dengan MariaDB. | Cek kembali username, password, dan grant privileges pada MariaDB CLI. |
| **SQLSTATE[HY000] [2002] No such file or directory** | Service MariaDB mati, atau host `localhost` mencari socket di path yang beda. | Cek status MariaDB (`sudo systemctl status mariadb`). Ganti host di `koneksi.php` menjadi `127.0.0.1` jika diperlukan. |
| **Fatal error: Uncaught Error: Class 'PDO' not found** | Modul `php-mysql` belum di-install atau PHP-FPM belum di-restart. | Jalankan `sudo apt install php-mysql` dan restart service PHP-FPM (`sudo systemctl restart php8.3-fpm`). |

---

# 🧰 Cheatsheet

```bash
# Service Database
sudo systemctl start|stop|restart|status mariadb

# Login Database
sudo mariadb -u root -p                     # Login via Root
mariadb -u cloud_user -p db_cloud           # Login via User Aplikasi

# Maintenance Database
sudo mariadb-check --all-databases          # Cek integritas database

# Check Error Log
sudo tail -f /var/log/mysql/error.log

```

---

# ✅ Penutup

Selamat! Dengan menyelesaikan modul Pekan 07 ini, kamu telah berhasil membangun **Full LEMP Stack (Linux, Nginx, MariaDB, PHP)** secara utuh dari nol. Server kamu kini siap melayani aplikasi web dinamis yang aman dan berkinerja tinggi.

Sampai jumpa di **Ujian Tengah Semester (UTS)**!
