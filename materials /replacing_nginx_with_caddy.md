# 🌐 Modul Praktikum: Caddy Web Server, SSL/TLS, PHP-FPM, Reverse Proxy & Load Balancer

**Format:** Praktikum bertahap di lingkungan **VM** menggunakan **domain lokal** (tanpa registered domain), dilengkapi konfigurasi **SSL/TLS (Manual Cert & Auto-Renew)**, **PHP-FPM**, **Reverse Proxy**, dan **Load Balancer**. Setiap tahap punya tujuan, langkah, penjelasan rinci, dan checkpoint. Selesaikan satu tahap dulu sebelum lanjut ke tahap berikutnya.

---

### 🗺️ Peta Perjalanan

| Tahap | Topik | Output |
| --- | --- | --- |
| **1** | Arsitektur Caddy, Domain Lokal & SSL/TLS (Manual Cert & Auto-Renew) | Paham `Caddyfile`, `tls <cert> <key>`, `tls internal`, dan auto-renew ACME |
| **2** | Instalasi Caddy & PHP-FPM (Basic Setup + PHP + HTTPS) | Caddy & PHP-FPM aktif, `cloud.local` melayani PHP lewat HTTPS |
| **3** | Reverse Proxy ke Port Aplikasi | Request `api.cloud.local` diteruskan ke backend port lokal |
| **4** | Load Balancer Multi-Backend | `lb.cloud.local` membagi beban ke beberapa target dengan *health check* |
| **5** | Deploy Aplikasi Web Dinamis (Mini POS di Caddy) | Aplikasi PHP multi-file berjalan penuh di `[https://cloud.local](https://cloud.local)` |
| **6** | Tugas Akhir, Kuis, Troubleshooting & Cheatsheet | Laporan praktikum & penguasaan operasional Caddy |

---

## 📘 TAHAP 1 — Arsitektur Caddy, Domain Lokal & SSL/TLS

### 1.1 Kenapa Caddy Menggantikan Nginx + Certbot?

Pada praktikum sebelumnya, Anda mengatur `server_name`, `ssl_certificate`, `ssl_certificate_key`, `fastcgi_pass`, `proxy_pass`, dan `upstream` secara terpisah—sering kali ditambah `certbot` untuk meminta dan memperbarui sertifikat.

Di **Caddy**:

* Seluruh konfigurasi berada di **`/etc/caddy/Caddyfile`** dengan sintaks yang ringkas.
* **Auto-renew (untuk domain publik yang valid):** Caddy otomatis mengurus sertifikat ACME (Let's Encrypt / ZeroSSL) dan **memperbaruinya otomatis (*auto-renew*)** di latar belakang tanpa `certbot` maupun `cron`.
* **Manual cert (`tls <cert_file> <key_file>`):** Dipakai jika Anda sudah punya file sertifikat dan *private key* sendiri (misal sertifikat `openssl` / CA kampus untuk domain `.local` atau sertifikat custom).
* **SSL lokal di VM tanpa registered domain:** Menggunakan `tls internal` (Caddy membuat Root CA internal sendiri) atau sertifikat manual `.crt` & `.key`.

```text
                +---------------------------------------------------+
                |                     VM CADDY                      |
                |                                                   |
[Browser] ----> | [Caddy :80 / :443 + SSL/TLS]                      |
 (hosts -> IP)  |   ├── cloud.local     --> PHP-FPM (.sock)         |
                |   ├── api.cloud.local --> reverse_proxy (:3000)   |
                |   └── lb.cloud.local  --> load balancer (:8081/82)|
                +---------------------------------------------------+

```

### 1.2 Menyiapkan Domain Lokal di Laptop / Client

Karena belum punya registered domain, petakan domain lokal ke IP VM Anda (misal IP VM `192.168.1.50`):

* **Linux / macOS (di laptop):** edit `/etc/hosts`
* **Windows (di laptop, buka Notepad as Administrator):** edit `C:\Windows\System32\drivers\etc\hosts`

Tambahkan baris berikut (sesuaikan dengan IP VM Anda):

```text
192.168.1.50   cloud.local api.cloud.local lb.cloud.local

```

### 1.3 Memahami Mode SSL/TLS di Caddy (Manual Cert vs Auto-Renew)

1. **Manual Certificate (`tls <cert.crt> <key.key>`):**
Digunakan jika memakai file sertifikat dan *private key* sendiri (seperti sertifikat self-signed/CA lokal di lab). **Tidak di-renew otomatis** oleh Caddy—masa berlakunya mengikuti file sertifikat.
2. **Auto-Renew Domain Publik (`acme` otomatis):**
Jika domain publik sudah mengarah ke IP server dan port `80` & `443` terbuka, cukup tulis nama domainnya (dan opsional `email` di blok global). Caddy otomatis menerbitkan dan **me-renew** sertifikat tanpa `certbot`.
3. **Auto-Renew Internal di Lab (`tls internal`):**
Untuk domain `.local` tanpa domain publik, Caddy otomatis membuat dan memperbarui sertifikat internal dari *Internal CA* miliknya.

*(Opsional)* Jika ingin browser di laptop tidak menampilkan peringatan saat memakai `tls internal`, salin Root CA Caddy dari VM ke laptop:

```bash
# Jalankan dari terminal laptop (sesuaikan user & IP VM)
scp user@192.168.1.50:/var/lib/caddy/.local/share/caddy/pki/authorities/local/root.crt ~/caddy-local-root.crt

```

Lalu masukkan `~/caddy-local-root.crt` ke *Trusted Root Certification Authorities* di OS laptop Anda.

### ✅ Checkpoint Tahap 1

* Memahami perbedaan `tls <cert> <key>` (manual), `tls internal` (CA lokal), dan auto-renew ACME (domain publik).
* IP VM sudah dipetakan ke `cloud.local`, `api.cloud.local`, dan `lb.cloud.local` di file `hosts` laptop.

---

## 🛠️ TAHAP 2 — Instalasi Caddy, SSL, PHP-FPM & Basic Setup

**Tujuan:** Menghentikan Nginx, menginstal Caddy dan PHP-FPM, menyiapkan sertifikat SSL (manual atau `tls internal`), serta menjalankan file statis dan PHP di `[https://cloud.local](https://cloud.local)`.

### 2.1 Hentikan Nginx & Instal Caddy

SSH ke VM Anda, lalu hentikan Nginx agar port `80` dan `443` tidak bentrok:

```bash
ssh user@192.168.1.50

sudo systemctl stop nginx
sudo systemctl disable nginx

```

Instal Caddy di Ubuntu/Debian:

```bash
sudo apt update
sudo apt install -y debian-keyring debian-archive-keyring apt-transport-https curl
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | sudo tee /etc/apt/sources.list.d/caddy-stable.list
sudo apt update
sudo apt install -y caddy

```

### 2.2 Instal PHP-FPM & Cek Socket

Instal PHP-FPM beserta ekstensi umum:

```bash
sudo apt install -y php-fpm php-cli php-common php-mbstring php-xml php-zip
php -v
ls -l /run/php/

```

Catat nama file socket yang muncul, misalnya `/run/php/php8.3-fpm.sock` (sesuaikan angkanya jika memakai PHP 8.1/8.2/8.4).

### 2.3 Siapkan Sertifikat Manual (Opsional jika Pakai Manual Cert)

Jika ingin menguji **manual certificate** (`tls /etc/ssl/cloud/cloud.local.crt /etc/ssl/cloud/cloud.local.key`), buat sertifikat lokal dengan SAN:

```bash
sudo mkdir -p /etc/ssl/cloud
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/ssl/cloud/cloud.local.key \
  -out /etc/ssl/cloud/cloud.local.crt \
  -subj "/C=ID/ST=Central Java/L=Salatiga/O=Lab Praktikum/CN=cloud.local" \
  -addext "subjectAltName=DNS:cloud.local,DNS:api.cloud.local,DNS:lb.cloud.local"

sudo chmod 600 /etc/ssl/cloud/cloud.local.key
sudo chmod 644 /etc/ssl/cloud/cloud.local.crt

```

### 2.4 Siapkan Direktori Web & Konfigurasi `cloud.local`

Buat folder web dan file uji `info.php`:

```bash
sudo mkdir -p /var/www/cloud
echo "<?php phpinfo(); ?>" | sudo tee /var/www/cloud/info.php
sudo chown -R www-data:www-data /var/www/cloud
sudo chmod -R 755 /var/www/cloud

```

Edit `/etc/caddy/Caddyfile`:

```bash
sudo nano /etc/caddy/Caddyfile

```

Pilih salah satu opsi SSL di bawah ini untuk blok `cloud.local` (sesuaikan `php8.3-fpm.sock` dengan versi PHP Anda):

**Opsi A — Memakai Sertifikat Manual (`tls <cert> <key>`):**

```caddyfile
cloud.local {
    tls /etc/ssl/cloud/cloud.local.crt /etc/ssl/cloud/cloud.local.key

    root * /var/www/cloud
    encode gzip zstd
    php_fastcgi unix//run/php/php8.3-fpm.sock
    file_server
}

```

**Opsi B — Memakai Internal CA Lokal (`tls internal`):**

```caddyfile
cloud.local {
    tls internal

    root * /var/www/cloud
    encode gzip zstd
    php_fastcgi unix//run/php/php8.3-fpm.sock
    file_server
}

```

Validasi sintaks dan *reload* Caddy:

```bash
sudo caddy validate --config /etc/caddy/Caddyfile
sudo systemctl reload caddy
sudo systemctl status caddy --no-pager

```

### 2.5 Uji di Browser & Hapus `info.php`

Buka `[https://cloud.local/info.php](https://cloud.local/info.php)` di browser laptop. Setelah halaman informasi PHP muncul, **hapus `info.php**` demi keamanan:

```bash
sudo rm /var/www/cloud/info.php

```

### ✅ Checkpoint Tahap 2

* Service `caddy` dan `php*-fpm` berstatus `active (running)`.
* `sudo caddy validate --config /etc/caddy/Caddyfile` berhasil tanpa error.
* `[https://cloud.local/info.php](https://cloud.local/info.php)` tampil dan sudah dihapus setelah pengujian.

---

## ⚙️ TAHAP 3 — Reverse Proxy ke Port Aplikasi

**Tujuan:** Menggantikan `proxy_pass` di Nginx dengan `reverse_proxy` di Caddy untuk meneruskan traffic ke aplikasi yang berjalan di port tertentu.

### 3.1 Jalankan Dummy Backend di Port `3000`

Jalankan *service* HTTP sederhana di port `3000` menggunakan Python bawaan Linux:

```bash
mkdir -p ~/backend-3000
cat <<'EOF' > ~/backend-3000/app.py
from http.server import BaseHTTPRequestHandler, HTTPServer
import json

class Handler(BaseHTTPRequestHandler):
    def do_GET(self):
        payload = {
            "service": "backend-port-3000",
            "path": self.path,
            "host_header": self.headers.get("Host")
        }
        body = json.dumps(payload, indent=2).encode()
        self.send_response(200)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

HTTPServer(("127.0.0.1", 3000), Handler).serve_forever()
EOF

nohup python3 ~/backend-3000/app.py > ~/backend-3000/app.log 2>&1 &
ss -tlnp | grep 3000

```

### 3.2 Tambahkan Blok `api.cloud.local` di `Caddyfile`

Edit `/etc/caddy/Caddyfile` dan tambahkan blok berikut (gunakan `tls internal` atau `tls /etc/ssl/cloud/cloud.local.crt /etc/ssl/cloud/cloud.local.key`):

```caddyfile
api.cloud.local {
    tls internal
    reverse_proxy 127.0.0.1:3000
}

```

Validasi lalu *reload* Caddy:

```bash
sudo caddy validate --config /etc/caddy/Caddyfile
sudo systemctl reload caddy

```

### 3.3 Uji Reverse Proxy

Uji via `curl` atau browser ke `[https://api.cloud.local](https://api.cloud.local)`:

```bash
curl -k https://api.cloud.local

```

### ✅ Checkpoint Tahap 3

* Backend di `127.0.0.1:3000` aktif dan mendengarkan port.
* Akses ke `[https://api.cloud.local](https://api.cloud.local)` mengembalikan respons JSON dari backend port `3000`.

---

## ⚖️ TAHAP 4 — Load Balancer Multi-Backend

**Tujuan:** Menggantikan blok `upstream` di Nginx dengan `reverse_proxy` multi-target, kebijakan *load balancing*, dan *health check* di Caddy.

### 4.1 Jalankan Dua Instance Backend (`8081` dan `8082`)

Buat dua *worker* sederhana di port `8081` dan `8082`:

```bash
mkdir -p ~/lb-workers
cat <<'EOF' > ~/lb-workers/worker.py
import sys, json
from http.server import BaseHTTPRequestHandler, HTTPServer

node_name = sys.argv[1]
port = int(sys.argv[2])

class Handler(BaseHTTPRequestHandler):
    def do_GET(self):
        if self.path == "/health":
            self.send_response(200)
            self.end_headers()
            self.wfile.write(b"OK")
            return
        body = json.dumps({"handled_by": node_name, "port": port}).encode()
        self.send_response(200)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

HTTPServer(("127.0.0.1", port), Handler).serve_forever()
EOF

nohup python3 ~/lb-workers/worker.py node-A 8081 > ~/lb-workers/8081.log 2>&1 &
nohup python3 ~/lb-workers/worker.py node-B 8082 > ~/lb-workers/8082.log 2>&1 &

```

### 4.2 Konfigurasikan `lb.cloud.local` di `Caddyfile`

Tambahkan blok berikut ke `/etc/caddy/Caddyfile`:

```caddyfile
lb.cloud.local {
    tls internal
    reverse_proxy 127.0.0.1:8081 127.0.0.1:8082 {
        lb_policy round_robin
        health_uri /health
        health_interval 10s
        health_timeout 5s
        fail_duration 20s
        max_fails 2
    }
}

```

Validasi lalu *reload* Caddy:

```bash
sudo caddy validate --config /etc/caddy/Caddyfile
sudo systemctl reload caddy

```

### 4.3 Uji Pergantian Node & Failover

Jalankan beberapa request berulang:

```bash
for i in {1..4}; do curl -sk https://lb.cloud.local; echo ""; done

```

Respons akan bergantian antara `node-A` (`8081`) dan `node-B` (`8082`). Uji *failover* dengan mematikan `node-A` (`pkill -f "worker.py node-A"`) lalu ulangi `curl`.

### ✅ Checkpoint Tahap 4

* Dua target backend (`8081` dan `8082`) aktif dan merespons `/health`.
* Request ke `[https://lb.cloud.local](https://lb.cloud.local)` terdistribusi sesuai `lb_policy` dan tetap berjalan saat salah satu node dimatikan.

---

## 🚀 TAHAP 5 — Deploy Aplikasi Web Dinamis (Mini POS di Caddy)

**Tujuan:** Menjalankan aplikasi web PHP multi-file (`index.php`, `proses.php`, `style.css`) di atas Caddy + PHP-FPM.

### 5.1 Buat File Aplikasi di `/var/www/cloud`

Buat `style.css`:

```bash
sudo tee /var/www/cloud/style.css > /dev/null <<'EOF'
* { box-sizing: border-box; font-family: system-ui, -apple-system, sans-serif; }
body { background-color: #0f172a; color: #f8fafc; margin: 0; padding: 2rem; }
.container { max-width: 600px; margin: 0 auto; background: #1e293b; padding: 2rem; border-radius: 12px; border: 1px solid #334155; }
h1, h2 { color: #38bdf8; text-align: center; }
.form-group { margin-bottom: 1.1rem; }
label { display: block; margin-bottom: 0.4rem; font-size: 0.9rem; color: #94a3b8; }
input, select { width: 100%; padding: 0.7rem; border-radius: 6px; border: 1px solid #475569; background: #0f172a; color: #fff; }
button { width: 100%; background: #0284c7; color: #fff; border: none; padding: 0.8rem; border-radius: 6px; cursor: pointer; font-weight: bold; }
button:hover { background: #0369a1; }
.receipt-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
.receipt-table th, .receipt-table td { border-bottom: 1px solid #334155; padding: 0.7rem; text-align: left; }
.total { font-size: 1.2rem; font-weight: bold; color: #4ade80; }
.badge { background: #334155; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; }
.btn-back { display: inline-block; margin-top: 1.2rem; color: #38bdf8; text-decoration: none; }
EOF

```

Buat `index.php`:

```bash
sudo tee /var/www/cloud/index.php > /dev/null <<'EOF'
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Mini POS Caddy + PHP-FPM</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>🛒 Mini POS Cloud (Caddy)</h1>
        <p style="text-align:center;color:#94a3b8;font-size:.9rem;">
            Waktu Server: <strong><?php echo date('d M Y H:i:s'); ?></strong>
        </p>
        <form action="proses.php" method="POST">
            <div class="form-group">
                <label for="nama_pelanggan">Nama Pelanggan / NIM</label>
                <input type="text" id="nama_pelanggan" name="nama_pelanggan" required>
            </div>
            <div class="form-group">
                <label for="item">Pilih Layanan</label>
                <select id="item" name="item" required>
                    <option value="VPS Cloud|150000">VPS Cloud - Rp 150.000</option>
                    <option value="Object Storage 100GB|50000">Object Storage 100GB - Rp 50.000</option>
                    <option value="Domain Lokal .LOCAL|25000">Domain Lokal .LOCAL - Rp 25.000</option>
                </select>
            </div>
            <div class="form-group">
                <label for="jumlah">Jumlah</label>
                <input type="number" id="jumlah" name="jumlah" min="1" value="1" required>
            </div>
            <div class="form-group">
                <label for="diskon">Kode Diskon (Coba: KAMPUS)</label>
                <input type="text" id="diskon" name="diskon" placeholder="KAMPUS">
            </div>
            <button type="submit">Hitung Total Transaksi →</button>
        </form>
    </div>
</body>
</html>
EOF

```

Buat `proses.php`:

```bash
sudo tee /var/www/cloud/proses.php > /dev/null <<'EOF'
<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$nama_pelanggan = htmlspecialchars($_POST['nama_pelanggan'] ?? '', ENT_QUOTES, 'UTF-8');
$raw_item = explode('|', $_POST['item'] ?? 'Layanan|0');
$nama_item = htmlspecialchars($raw_item[0] ?? 'Layanan', ENT_QUOTES, 'UTF-8');
$harga_satuan = (int)($raw_item[1] ?? 0);
$jumlah = max(1, (int)($_POST['jumlah'] ?? 1));
$kode_diskon = strtoupper(trim($_POST['diskon'] ?? ''));

$subtotal = $harga_satuan * $jumlah;
$diskon_persen = ($kode_diskon === 'KAMPUS') ? 20 : 0;
$potongan_harga = ($subtotal * $diskon_persen) / 100;
$total_bayar = $subtotal - $potongan_harga;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Transaksi - Mini POS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>📄 Struk Transaksi</h2>
        <p style="text-align:center;"><span class="badge">Caddy + PHP-FPM</span></p>
        <table class="receipt-table">
            <tr><th>Pelanggan</th><td><?php echo $nama_pelanggan; ?></td></tr>
            <tr><th>Item</th><td><?php echo $nama_item; ?></td></tr>
            <tr><th>Harga Satuan</th><td>Rp <?php echo number_format($harga_satuan, 0, ',', '.'); ?></td></tr>
            <tr><th>Jumlah</th><td><?php echo $jumlah; ?></td></tr>
            <tr><th>Subtotal</th><td>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td></tr>
            <tr><th>Diskon (<?php echo $diskon_persen; ?>%)</th><td>- Rp <?php echo number_format($potongan_harga, 0, ',', '.'); ?></td></tr>
            <tr><th class="total">Total Bayar</th><td class="total">Rp <?php echo number_format($total_bayar, 0, ',', '.'); ?></td></tr>
        </table>
        <div style="text-align:center;">
            <a href="index.php" class="btn-back">← Kembali ke Form</a>
        </div>
    </div>
</body>
</html>
EOF

```

Atur permission:

```bash
sudo chown -R www-data:www-data /var/www/cloud
sudo chmod -R 755 /var/www/cloud

```

### ✅ Checkpoint Tahap 5

* Form transaksi tampil di `[https://cloud.local/](https://cloud.local/)` dan proses `POST` ke `proses.php` menghasilkan perhitungan akurat.

---

## 🎯 TAHAP 6 — Tugas Akhir, Kuis, Troubleshooting & Cheatsheet

### 6.1 Tugas Praktikum

1. Tambahkan satu opsi layanan baru pada `index.php` dan kode diskon `DISKON10` (potongan 10%) di `proses.php`.
2. Ubah `memory_limit` menjadi `512M` dan `date.timezone` menjadi `Asia/Jakarta` pada `/etc/php/8.x/fpm/php.ini`, lalu *restart* `php*-fpm`.
3. Bandingkan konfigurasi SSL manual (`tls /path/cert.crt /path/key.key`) dengan `tls internal` pada `/etc/caddy/Caddyfile`, dan jelaskan kapan auto-renew ACME (pengganti Certbot) bekerja pada domain publik.

### 6.2 Screenshot Wajib untuk Laporan

1. Output `systemctl status caddy` dan `systemctl status php*-fpm`.
2. Isi `/etc/caddy/Caddyfile` lengkap beserta hasil `sudo caddy validate --config /etc/caddy/Caddyfile`.
3. Respons browser / `curl` untuk `[https://api.cloud.local](https://api.cloud.local)` (reverse proxy) dan distribusi beban pada `[https://lb.cloud.local](https://lb.cloud.local)`.
4. Informasi sertifikat TLS yang aktif di browser pada `[https://cloud.local/](https://cloud.local/)`.
5. Tampilan form `index.php` dan struk `proses.php` setelah transaksi diproses.

Format penamaan laporan: `Praktikum_Caddy_[NIM]_[NamaMahasiswa].pdf`.

### 6.3 Kuis Cepat

1. **Apa perbedaan utama penggunaan `tls <cert> <key>` dengan auto-renew di Caddy?**
*Jawaban:* `tls <cert> <key>` memakai file sertifikat manual yang tidak diperbarui otomatis oleh Caddy, sedangkan auto-renew (otomatis pada domain publik tervalidasi ACME atau `tls internal` untuk CA lokal) dikelola otomatis oleh *engine* Caddy tanpa `certbot`.
2. **Apa pengganti `fastcgi_pass` + `location ~ \.php$` di Caddy?**
*Jawaban:* Direktif `php_fastcgi unix//run/php/php8.x-fpm.sock` (atau alamat TCP `127.0.0.1:9000`).
3. **Bagaimana cara menggantikan blok `upstream` dan `proxy_pass` di Nginx menjadi *load balancer* di Caddy?**
*Jawaban:* Menggunakan `reverse_proxy <target1> <target2> { lb_policy ... health_uri ... }` langsung di dalam blok domain.
4. **Mengapa kita tetap perlu me-*restart* `php*-fpm` setelah mengubah `php.ini`?**
*Jawaban:* Karena proses *worker* PHP-FPM membaca konfigurasi saat start/reload service, sehingga perubahan `php.ini` baru aktif setelah service di-*restart*.

### 6.4 Troubleshooting

| Masalah | Penyebab | Solusi |
| --- | --- | --- |
| **`502 Bad Gateway`** | PHP-FPM mati, path `.sock` salah, atau port backend (`3000`/`8081`) tidak berjalan. | Cek `systemctl status php*-fpm`, `ls -l /run/php/`, dan `ss -tlnp`. |
| **File `.php` terunduh / tampil kode mentah** | Baris `php_fastcgi` belum ada atau Caddy belum di-*reload*. | Periksa `php_fastcgi` di `/etc/caddy/Caddyfile`, jalankan `caddy validate`, lalu `systemctl reload caddy`. |
| **Error saat memuat sertifikat manual (`tls ...`)** | Path file salah, format bukan PEM, atau izin baca `caddy` ditolak. | Pastikan path file valid dan `chmod 644` pada `.crt` serta `600`/`640` pada `.key` dengan *owner* yang bisa dibaca. |
| **Domain `.local` tidak terbuka di laptop** | Entri `/etc/hosts` salah atau IP VM berubah. | Cek IP VM dengan `ip a` dan pastikan baris di `hosts` mengarah ke IP VM aktif. |

### 6.5 Cheatsheet

```bash
# Validasi, format & reload Caddy
sudo caddy validate --config /etc/caddy/Caddyfile
sudo caddy fmt --overwrite /etc/caddy/Caddyfile
sudo systemctl reload caddy

# Cek status & log Caddy serta PHP-FPM
sudo systemctl status caddy php8.3-fpm --no-pager
sudo journalctl -u caddy --no-pager -n 40 | grep -i -E "tls|cert|acme|pki|error"
sudo journalctl -u caddy -f
sudo tail -f /var/log/php8.3-fpm.log

```
