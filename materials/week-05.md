# 🌐 Modul Week 04: Web Server Configuration (Nginx) Advanced

> **Format:** Praktikum bertahap. Setiap tahap punya tujuan, langkah, dan checkpoint.  
> Selesaikan satu tahap dulu sebelum lanjut ke tahap berikutnya.

---

## 🗺️ Peta Perjalanan

| Tahap | Topik | Output |
|---|---|---|
| **1** | Kenalan sama Nginx | Paham konsep |
| **2** | Install & deploy web basic | Web jalan di IP VM |
| **3** | Konfigurasi Nginx (config dasar) | Paham struktur config |
| **4** | Bikin domain lokal sendiri | `cloud.local` jalan |
| **5** | Server Block / Virtual Host | 2 domain, 2 folder |
| **6** | HTTPS lokal | `https://cloud.local` |
| **7** | Static app + custom 404 | Web rapi & lengkap |
| **8** | Tugas akhir | Laporan |

---

# 📘 TAHAP 1 — Kenalan Sama Nginx

## 1.1 Apa Itu Web Server?

**Web Server** = program yang tugasnya **menerima permintaan dari browser**, lalu **membalas dengan halaman web**.

Analogi warung:
- Browser = pembeli yang datang.
- Web server = penjual yang menerima pesanan.
- HTML/CSS/JS = makanan yang disajikan.

## 1.2 Apa Itu Nginx?

**Nginx** (baca: *Engine-X*) = salah satu web server paling populer di dunia.

Kenapa Nginx populer?
| Kelebihan | Penjelasan |
|---|---|
| Cepat | Arsitektur *event-driven* |
| Hemat RAM | Cocok untuk server kecil |
| Tahan banyak koneksi | Ribuan user sekaligus |
| Fleksibel | Bisa jadi reverse proxy, load balancer |

## 1.3 Alur Kerja Nginx

```text
[Browser]  ---- HTTP request ---->  [Nginx]  ---->  [File HTML]
          <--- HTTP response -----          <----
```

Contoh nyata:
1. Kamu buka `http://192.168.1.50`
2. Browser kirim request ke Nginx di VM
3. Nginx baca file `/var/www/html/index.html`
4. Nginx kirim balik ke browser
5. Browser render → muncul halaman

## 1.4 Port Itu Apa?

Bayangkan server = gedung. Port = **nomor pintu**.

| Port | Fungsi |
|---|---|
| 22 | SSH (remote server) |
| 80 | HTTP (web biasa) |
| 443 | HTTPS (web aman) |

## 1.5 Istilah Penting (Hafalin ini dulu)

| Istilah | Arti Simpel |
|---|---|
| **Server Block** | Aturan: domain X → folder Y |
| **Virtual Host** | Nama lain server block |
| **Web Root** | Folder tempat file web disimpan |
| **`index.html`** | File default yang ditampilkan |
| **Symlink** | "Shortcut" ke file/folder lain |
| **`/etc/hosts`** | Buku telepon lokal (domain → IP) |

### ✅ Checkpoint Tahap 1
Kamu harus bisa jawab:
- Nginx itu apa?
- Bedanya port 80 vs 443?
- Server block gunanya apa?

---

# 🛠️ TAHAP 2 — Install & Deploy Web Basic

**Tujuan:** Nginx jalan, dan bisa diakses dari laptop.

## 2.1 Persiapan Jaringan VirtualBox

**Ini penentu segalanya.** Pilih salah satu:

### Opsi A — Bridged Adapter ⭐ (direkomendasikan)
1. VM mati → VirtualBox → **Settings → Network → Adapter 1**
2. Attached to: **Bridged Adapter**
3. Name: pilih WiFi/Ethernet laptop
4. Nyalakan VM
5. Cek IP:
```bash
hostname -I
```
Contoh dapat: `192.168.1.50`. **Catat IP ini.**

### Opsi B — NAT + Port Forwarding
Kalau bridged tidak bisa (WiFi kampus memblokir):
1. Settings → Network → Advanced → **Port Forwarding**
2. Tambah:
   - HTTP: Host `127.0.0.1:8080` → Guest `10.0.2.15:80`
3. Akses dari laptop pakai `http://localhost:8080`

> **Modul ini asumsikan pakai Bridged dengan IP `192.168.1.50`.**  
> Ganti angka sesuai IP VM kamu.

## 2.2 Install Nginx

SSH ke VM:
```bash
ssh user@192.168.1.50
```

Update & install:
```bash
sudo apt update
sudo apt install nginx -y
```

Cek status:
```bash
sudo systemctl status nginx
```
Harus muncul: **active (running)**. Tekan `q` untuk keluar.

Cek port listen:
```bash
sudo ss -tulpn | grep nginx
```
Harus muncul `:80`.

## 2.3 Test dari Laptop

Buka browser → `http://192.168.1.50`

Harus muncul: **"Welcome to nginx!"**

## 2.4 Deploy Web Pertamamu

Masuk ke folder web:
```bash
cd /var/www/html
```

Backup file default:
```bash
sudo mv index.html index.html.bk
```

Buat file baru:
```bash
sudo nano index.html
```

Isi:
```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Web Pertamaku</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #0f172a;
            color: #f8fafc;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .card {
            background: #1e293b;
            padding: 2.5rem;
            border-radius: 16px;
            text-align: center;
            max-width: 480px;
            border: 1px solid #334155;
        }
        h1 { color: #38bdf8; }
        .badge {
            background: #0284c7;
            color: white;
            padding: .4rem .9rem;
            border-radius: 20px;
            font-size: .85rem;
            display: inline-block;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Halo dari Nginx!</h1>
        <p>Web pertamaku berhasil jalan di VM.</p>
        <div class="badge">Port 80 — Online</div>
    </div>
</body>
</html>
```

Simpan: `Ctrl+O` → `Enter` → `Ctrl+X`.

## 2.5 Refresh Browser

Refresh `http://192.168.1.50` → muncul halaman baru.

### ✅ Checkpoint Tahap 2
- [ ] Nginx status = **active (running)**
- [ ] Port 80 listen
- [ ] Halaman custom muncul di browser laptop

---

# ⚙️ TAHAP 3 — Pengenalan Konfigurasi Nginx

**Tujuan:** Paham di mana config Nginx disimpan dan cara mengeditnya.

## 3.1 Struktur Folder Nginx

| Path | Fungsi |
|---|---|
| `/etc/nginx/nginx.conf` | Config utama |
| `/etc/nginx/sites-available/` | "Gudang" config situs |
| `/etc/nginx/sites-enabled/` | Situs yang aktif |
| `/var/www/html/` | Folder web default |
| `/var/log/nginx/` | Log akses & error |

**Analogi:**
- `sites-available` = lemari baju
- `sites-enabled` = baju yang sedang dipakai
- `ln -s` = ambil baju dari lemari, pakai

## 3.2 Lihat Config Default

```bash
cat /etc/nginx/sites-available/default
```

Perhatikan bagian penting:
```nginx
server {
    listen 80 default_server;
    root /var/www/html;
    index index.html;
    server_name _;
}
```

| Baris | Arti |
|---|---|
| `listen 80` | Dengar di port 80 |
| `root /var/www/html` | Folder web |
| `index index.html` | File default |
| `server_name _` | Domain apa saja |

## 3.3 Test Config

Sebelum reload, **selalu** test dulu:
```bash
sudo nginx -t
```
Harus muncul:
```
syntax is ok
test is successful
```

## 3.4 Reload vs Restart

| Perintah | Efek |
|---|---|
| `sudo systemctl reload nginx` | Terapkan config **tanpa** putus koneksi |
| `sudo systemctl restart nginx` | Matikan lalu nyalakan ulang |

Biasakan pakai **reload** kalau hanya ubah config.

## 3.5 Lihat Log

```bash
sudo tail -f /var/log/nginx/access.log
```
Buka browser, refresh halaman → lihat log muncul live.  
`Ctrl+C` untuk keluar.

### ✅ Checkpoint Tahap 3
- [ ] Tahu isi `/etc/nginx/sites-available/`
- [ ] Bisa jalankan `nginx -t`
- [ ] Bisa lihat log akses

---

# 🏷️ TAHAP 4 — Bikin Domain Lokal Sendiri

**Tujuan:** Tidak lagi pakai IP. Pakai `cloud.local`.

## 4.1 Kenapa Bisa Bikin Domain Sendiri?

Domain publik (`google.com`) butuh **DNS internet**.  
Di lab lokal, kita pakai file **`/etc/hosts`** = buku telepon kecil di komputer.

Kalau kita tulis:
```text
192.168.1.50   cloud.local
```
Maka browser akan otomatis ke IP VM saat kita buka `cloud.local`.

**Legal, aman, dan cara standar belajar web server.** 😎

## 4.2 Edit `/etc/hosts` di **Laptop (Host)**

**Linux/macOS:**
```bash
sudo nano /etc/hosts
```

**Windows:**  
Buka Notepad sebagai Administrator → `C:\Windows\System32\drivers\etc\hosts`

Tambahkan di baris paling bawah:
```text
192.168.1.50    cloud.local
192.168.1.50    tugas.local
```

> Ganti `192.168.1.50` dengan IP VM kamu.

## 4.3 Test

Dari laptop:
```bash
ping cloud.local
```
Harus balas dari IP VM.

Buka browser: `http://cloud.local`  
Harus muncul halaman yang sama seperti `http://192.168.1.50`. 🎉

## 4.4 (Opsional) Edit `/etc/hosts` di VM Juga

```bash
sudo nano /etc/hosts
```
Tambah:
```text
127.0.0.1   cloud.local
127.0.0.1   tugas.local
```

### ✅ Checkpoint Tahap 4
- [ ] `/etc/hosts` di laptop sudah diisi
- [ ] `ping cloud.local` berhasil
- [ ] `http://cloud.local` muncul di browser

---

# 🏗️ TAHAP 5 — Server Block / Virtual Host

**Tujuan:** 1 IP, 2 domain, 2 folder berbeda.

## 5.1 Konsep

```text
        ┌──────────────────────────┐
        │   Nginx (192.168.1.50)   │
        └────────────┬─────────────┘
                     │
        ┌────────────┴─────────────┐
        │                          │
   cloud.local                tugas.local
        │                          │
        ▼                          ▼
   /var/www/cloud            /var/www/tugas
```

## 5.2 Buat Folder & File

```bash
sudo mkdir -p /var/www/cloud
sudo mkdir -p /var/www/tugas
```

Isi halaman cloud:
```bash
sudo nano /var/www/cloud/index.html
```
```html
<!DOCTYPE html>
<html><head><title>Cloud</title></head>
<body style="font-family:sans-serif;text-align:center;padding:4rem">
<h1>☁️ Halaman Cloud</h1>
<p>Ini situs cloud.local</p>
</body></html>
```

Isi halaman tugas:
```bash
sudo nano /var/www/tugas/index.html
```
```html
<!DOCTYPE html>
<html><head><title>Tugas</title></head>
<body style="font-family:sans-serif;text-align:center;padding:4rem">
<h1>📝 Halaman Tugas</h1>
<p>Ini situs tugas.local</p>
</body></html>
```

## 5.3 Buat Server Block `cloud.local`

```bash
sudo nano /etc/nginx/sites-available/cloud.local
```

Isi:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name cloud.local;

    root /var/www/cloud;
    index index.html;

    location / {
        try_files $uri $uri/ =404;
    }
}
```

## 5.4 Buat Server Block `tugas.local`

```bash
sudo nano /etc/nginx/sites-available/tugas.local
```

Isi:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name tugas.local;

    root /var/www/tugas;
    index index.html;

    location / {
        try_files $uri $uri/ =404;
    }
}
```

## 5.5 Aktifkan via Symlink

```bash
sudo ln -s /etc/nginx/sites-available/cloud.local /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/tugas.local /etc/nginx/sites-enabled/
```

Matikan default (biar tidak bentrok):
```bash
sudo rm /etc/nginx/sites-enabled/default
```

## 5.6 Test & Reload

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## 5.7 Test di Browser

- `http://cloud.local` → halaman cloud
- `http://tugas.local` → halaman tugas

**Inilah inti Virtual Host:** 1 server, banyak domain, banyak folder.

### ✅ Checkpoint Tahap 5
- [ ] 2 folder `/var/www/cloud` & `/var/www/tugas`
- [ ] 2 server block aktif
- [ ] 2 domain buka halaman berbeda

---

# 🔐 TAHAP 6 — HTTPS Lokal

**Tujuan:** `https://cloud.local` jalan tanpa Certbot.

## 6.1 Kenapa Bukan Certbot?

Certbot (Let's Encrypt) butuh:
1. Domain publik
2. IP publik
3. Validasi dari internet

Di VirtualBox dengan IP privat → **tidak memenuhi syarat**.  
Solusinya: **self-signed** atau **mkcert**.

## 6.2 Opsi A — Self-Signed (Simpel, Ada Warning)

### Buat sertifikat:
```bash
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
-keyout /etc/ssl/private/cloud.local.key \
-out /etc/ssl/certs/cloud.local.crt \
-subj "/CN=cloud.local"
```

### Update server block:
```bash
sudo nano /etc/nginx/sites-available/cloud.local
```

Ganti jadi:
```nginx
# Redirect HTTP → HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name cloud.local;
    return 301 https://$host$request_uri;
}

# HTTPS
server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name cloud.local;

    ssl_certificate     /etc/ssl/certs/cloud.local.crt;
    ssl_certificate_key /etc/ssl/private/cloud.local.key;

    root /var/www/cloud;
    index index.html;

    location / {
        try_files $uri $uri/ =404;
    }
}
```

### Test & reload:
```bash
sudo nginx -t
sudo systemctl reload nginx
```

### Akses:
Buka `https://cloud.local` → browser **warning** → **Advanced → Proceed**.  
Normal kok untuk praktikum.

---

## 6.3 Opsi B — mkcert (Tanpa Warning, Recommended)

### Install mkcert di **laptop**:

**Linux:**
```bash
sudo apt install libnss3-tools -y
wget https://github.com/FiloSottile/mkcert/releases/latest/download/mkcert-v1.4.4-linux-amd64
chmod +x mkcert-v1.4.4-linux-amd64
sudo mv mkcert-v1.4.4-linux-amd64 /usr/local/bin/mkcert
```

**macOS:** `brew install mkcert`  
**Windows:** `choco install mkcert`

### Buat CA & sertifikat:
```bash
mkcert -install
mkcert cloud.local tugas.local
```

Hasil: `cloud.local+1.pem` + `cloud.local+1-key.pem`

### Copy ke VM:
```bash
scp cloud.local+1.pem cloud.local+1-key.pem user@192.168.1.50:/tmp/
```

### Pindahkan di VM:
```bash
sudo mv /tmp/cloud.local+1.pem /etc/ssl/certs/cloud.local.crt
sudo mv /tmp/cloud.local+1-key.pem /etc/ssl/private/cloud.local.key
sudo chmod 644 /etc/ssl/certs/cloud.local.crt
sudo chmod 600 /etc/ssl/private/cloud.local.key
```

Update server block sama seperti Opsi A, lalu:
```bash
sudo nginx -t
sudo systemctl reload nginx
```

Buka `https://cloud.local` → **tanpa warning** ✨

### ✅ Checkpoint Tahap 6
- [ ] HTTP otomatis redirect ke HTTPS
- [ ] HTTPS bisa dibuka
- [ ] (Bonus) Tanpa warning kalau pakai mkcert

---

# 📁 TAHAP 7 — Static App + Custom 404

**Tujuan:** Web rapi dengan CSS, JS, gambar, dan halaman 404 sendiri.

## 7.1 Struktur Folder

```text
/var/www/cloud/
├── index.html
├── style.css
├── script.js
├── 404.html
└── img/
    └── avatar.png
```

## 7.2 Buat File CSS

```bash
sudo nano /var/www/cloud/style.css
```
```css
body {
    background: linear-gradient(135deg, #0f172a, #1e293b);
    color: #e2e8f0;
    font-family: 'Segoe UI', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
}
.card {
    background: #1e293b;
    padding: 2.5rem;
    border-radius: 16px;
    border: 1px solid #334155;
    text-align: center;
    max-width: 480px;
}
h1 { color: #38bdf8; }
```

## 7.3 Buat File JS

```bash
sudo nano /var/www/cloud/script.js
```
```javascript
console.log("Static app loaded!");
```

## 7.4 Custom 404

```bash
sudo nano /var/www/cloud/404.html
```
```html
<!DOCTYPE html>
<html>
<head><title>404 Not Found</title></head>
<body style="text-align:center;font-family:sans-serif;padding:4rem;background:#0f172a;color:#e2e8f0">
<h1 style="color:#38bdf8">404</h1>
<p>Halaman tidak ditemukan.</p>
<a href="/" style="color:#38bdf8">← Kembali ke beranda</a>
</body>
</html>
```

## 7.5 Update `index.html`

```bash
sudo nano /var/www/cloud/index.html
```
```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cloud Server</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <div class="card">
        <h1>☁️ Cloud Web Server</h1>
        <p>Nginx berjalan dengan HTTPS dan static app.</p>
        <p><small>Nama: [ISI NAMAMU] | NIM: [ISI NIM]</small></p>
    </div>
    <script src="/script.js"></script>
</body>
</html>
```

## 7.6 Update Server Block

```bash
sudo nano /etc/nginx/sites-available/cloud.local
```

Tambahkan di dalam block HTTPS:
```nginx
error_page 404 /404.html;
location = /404.html {
    internal;
}

# Cache static
location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
    expires 30d;
    add_header Cache-Control "public";
}
```

## 7.7 Permission

```bash
sudo chown -R www-data:www-data /var/www/cloud
sudo chmod -R 755 /var/www/cloud
sudo find /var/www/cloud -type f -exec chmod 644 {} \;
```

## 7.8 Reload & Test

```bash
sudo nginx -t
sudo systemctl reload nginx
```

Test:
- `https://cloud.local` → halaman rapi dengan CSS
- `https://cloud.local/ngawur` → halaman 404 kustom

### ✅ Checkpoint Tahap 7
- [ ] CSS & JS ter-load
- [ ] 404 kustom muncul
- [ ] Halaman tampil cantik

---

# 🎯 TAHAP 8 — Tugas Akhir & Laporan

## 8.1 Tugas

1. **Kartu Profil** — modifikasi `cloud.local` dengan nama, NIM, foto/avatar.
2. **Dua Domain** — `cloud.local` & `tugas.local` dengan server block terpisah.
3. **HTTPS** — aktifkan di `cloud.local` (self-signed **atau** mkcert).
4. **Static App** — CSS, JS, folder `img/`, custom 404.

## 8.2 Screenshot Wajib

1. `http://cloud.local` (redirect ke HTTPS)
2. `https://cloud.local` (halaman profil)
3. `https://tugas.local`
4. Output `sudo systemctl status nginx`
5. Output `sudo nginx -t`
6. Isi `/etc/hosts` di laptop (`cat /etc/hosts`)
7. Struktur folder: `ls -R /var/www/cloud`
8. Halaman 404 kustom

## 8.3 Format Laporan

**`Week04_[NIM]_[NamaMahasiswa].pdf`**

## 8.4 Rubrik Penilaian

| Komponen | Bobot |
|---|---:|
| Nginx install & running | 10% |
| Halaman kustom + identitas | 15% |
| Domain lokal (`/etc/hosts`) jalan | 15% |
| Server block berjalan | 20% |
| HTTPS (self-signed/mkcert) | 15% |
| Static app + custom 404 | 10% |
| Laporan & screenshot | 10% |
| Kerapian config | 5% |

---

# 🧠 Kuis Cepat

1. Nginx itu apa, dan kenapa populer?
2. Beda port 80 dan 443?
3. Fungsi `server_name`?
4. Beda `reload` dan `restart`?
5. Apa itu symlink di `sites-enabled`?
6. Fungsi `/etc/hosts`?
7. Kenapa Certbot tidak dipakai di praktikum ini?
8. Arti `try_files $uri $uri/ =404;`?
9. Kenapa muncul 403 Forbidden?
10. Beda Server Block vs Web Root?

<details>
<summary>Kunci Jawaban</summary>

1. Web server event-driven, cepat, hemat RAM.  
2. 80 HTTP, 443 HTTPS.  
3. Mencocokkan domain yang diminta browser.  
4. Reload tanpa putus koneksi; restart matikan lalu nyala.  
5. Shortcut dari `sites-available` ke `sites-enabled`.  
6. Buku telepon lokal (domain → IP).  
7. Tidak punya domain publik + IP privat.  
8. Cari file, kalau tidak ada balas 404.  
9. Permission file/folder salah.  
10. Server block = aturan domain → folder; web root = folder fisiknya.

</details>

---

# 🚨 Troubleshooting

| Masalah | Penyebab | Solusi |
|---|---|---|
| `ERR_CONNECTION_REFUSED` | Nginx mati | `sudo systemctl start nginx` |
| Halaman default muncul terus | Default belum di-disable | `sudo rm /etc/nginx/sites-enabled/default` |
| `403 Forbidden` | Permission salah | `chmod -R 755 /var/www/xxx` |
| `404 Not Found` | File/root salah | Cek `root` & `index.html` |
| `job for nginx.service failed` | Syntax error | `sudo nginx -t` |
| Domain tidak resolve | `/etc/hosts` belum diisi | `cat /etc/hosts` |
| IP VM tidak bisa di-ping | Mode NAT | Ganti Bridged / port forward |
| HTTPS warning | Self-signed | Normal, klik Proceed |
| Config tidak aktif | Symlink salah | `ls -l /etc/nginx/sites-enabled/` |

---

# 🧰 Cheatsheet

```bash
# Service
sudo systemctl start|stop|restart|reload nginx
sudo systemctl status nginx

# Test config
sudo nginx -t

# Edit config
sudo nano /etc/nginx/sites-available/cloud.local

# Aktifkan site
sudo ln -s /etc/nginx/sites-available/cloud.local /etc/nginx/sites-enabled/

# Nonaktifkan
sudo rm /etc/nginx/sites-enabled/cloud.local

# Cek port
sudo ss -tulpn | grep nginx

# Cek IP VM
hostname -I

# Log
sudo tail -f /var/log/nginx/access.log
sudo tail -f /var/log/nginx/error.log

# Test lokal
curl -I http://cloud.local
curl -kI https://cloud.local
```

---

# 📖 Bacaan Opsional

> Tidak dinilai, tapi bagus buat wawasan.

- **UFW** — firewall; berisiko terkunci SSH di VM lokal.
- **Certbot** — untuk VPS dengan domain publik (Week berikutnya).
- **Reverse Proxy** — Nginx → aplikasi Node/Python.
- **Security Headers** — `X-Frame-Options`, `server_tokens off`, dll.

---

# 📚 Referensi

- Nginx Docs: https://nginx.org/en/docs/
- Ubuntu Nginx: https://ubuntu.com/tutorials/install-nginx-on-ubuntu-server
- mkcert: https://github.com/FiloSottile/mkcert
- VirtualBox Networking: https://www.virtualbox.org/manual/ch06.html

---

# ✅ Penutup

Setelah 8 tahap ini kamu sudah bisa:
- Install & kelola Nginx
- Bikin **domain lokal sendiri**
- Konfigurasi **server block** multi-domain
- Aktifkan **HTTPS lokal** tanpa Certbot
- Deploy **static app** rapi

> 🎯 **Intinya:** Nginx itu cuma resepsionis. Dia dengar di pintu (port), baca buku tamu (config), lalu antar tamu ke kamar yang tepat (folder web). Kalau sudah paham alurnya, mau domain lokal atau publik — tinggal ganti alamatnya.

Selamat praktik! 🚀
