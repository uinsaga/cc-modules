# 🌐 Modul Week 04: Web Server Configuration (Nginx)

## 📌 Capaian Pembelajaran (Sub-CPMK 7.1.4)
Mahasiswa mampu mendeploy dan mengonfigurasi **Web Server Nginx** di Linux, memahami konsep *Server Block / Virtual Host*, mengelola pemetaan port HTTP (80) & HTTPS (443), serta menyajikan aplikasi web statis sederhana.

---

## 📑 Daftar Isi
1. [Prasyarat Perangkat & Sistem](#-prasyarat-perangkat--sistem)
2. [Landasan Teori](#-landasan-teori)
3. [Langkah-Langkah Praktikum (Hands-On)](#-langkah-langkah-praktikum-hands-on)
4. [Tugas & Evaluasi Mandiri](#-tugas--evaluasi-mandiri)
5. [Panduan Troubleshooting (Solusi Kendala)](#-panduan-troubleshooting-solusi-kendala)

---

## 🛠️ Prasyarat Perangkat & Sistem

Sebelum memulai praktikum:
* Telah berhasil mengonfigurasi akses login SSH tanpa password (*materi Week 03*).
* Memiliki akses hak administrator (`sudo`) di Linux server remote.
* Memiliki aplikasi Web Browser (Google Chrome / Mozilla Firefox) di laptop lokal.

---

## 📚 Landasan Teori

### 1. Apa itu Web Server & Nginx?
**Web Server** adalah perangkat lunak yang bertugas menerima permintaan HTTP/HTTPS dari klien (web browser) dan menyajikan balasan berupa halaman web (HTML, CSS, JS, Gambar) atau meneruskan permintaan ke aplikasi *backend*.

**Nginx** (dibaca *"Engine-X"*) adalah salah satu web server paling populer di dunia. Nginx dirancang dengan arsitektur *event-driven* yang sangat efisien, tangguh menangani ribuan koneksi bersamaan (*concurrent connections*), dan hemat konsumsi RAM.

### 2. Anatomi Alur Kerja Web Server
```text
Browser User (Client)                 Nginx Web Server (Linux)
+-------------------+  HTTP (Port 80) +--------------------------+
| http://IP_SERVER  | --------------> | /var/www/html/index.html |
|                   | <-------------- | (Membaca file & kirim)   |
+-------------------+  Response 200   +--------------------------+

```

### 3. Struktur Konfigurasi Nginx di Ubuntu/Debian

* `/etc/nginx/nginx.conf`: Berkas konfigurasi utama Nginx.
* `/etc/nginx/sites-available/`: Direktori tempat menyimpan berkas konfigurasi situs/domain.
* `/etc/nginx/sites-enabled/`: Direktori tempat mengaktifkan situs via *Symbolic Link* (symlink).
* `/var/www/html/`: Direktori default penyimpan berkas web/HTML publik.

---

## 🧪 Langkah-Langkah Praktikum (Hands-On)

### Bagian 1: Instalasi & Pengelolaan Service Nginx

1. Login ke remote server via SSH:
```bash
ssh myserver

```


2. Perbarui indeks repositori paket Linux dan install Nginx:
```bash
sudo apt update
sudo apt install nginx -y

```


3. Periksa status operasional servis Nginx:
```bash
sudo systemctl status nginx

```


*(Pastikan status menunjukkan **active (running)**. Tekan tombol `q` pada keyboard untuk keluar).*

---

### Bagian 2: Pengujian Akses Default Web Server

1. Buka aplikasi **Web Browser** di laptop lokal kamu.
2. Masukkan alamat IP Server kamu pada address bar browser:
`http://IP_SERVER_KAMU`
3. **Hasil:** Kamu akan melihat halaman selamat datang standar *"Welcome to nginx!"*.

---

### Bagian 3: Pembangunan Halaman Web Kustom

1. Masuk ke direktori web root default:
```bash
cd /var/www/html

```


2. Sandarkan/backup file `index.html` lama bawaan Nginx:
```bash
sudo mv index.html index.html.bk

```


3. Buat berkas `index.html` kustom baru menggunakan editor `nano`:
```bash
sudo nano index.html

```


4. Isikan kode HTML sederhana berikut:
```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloud Web Server - Week 04</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .card {
            background-color: #1e293b;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 450px;
            border: 1px solid #334155;
        }
        h1 { color: #38bdf8; margin-bottom: 0.5rem; }
        p { color: #94a3b8; line-height: 1.6; }
        .badge {
            background-color: #0284c7;
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            display: inline-block;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Cloud Computing Server</h1>
        <p>Web server Nginx berhasil dikonfigurasi dan berjalan secara terisolasi di Linux Server.</p>
        <div class="badge">Status: Live Online (Port 80)</div>
    </div>
</body>
</html>

```


5. Simpan berkas (`Ctrl+O` $\rightarrow$ `Enter`) lalu keluar (`Ctrl+X`).
6. Refresh halaman browser `http://IP_SERVER_KAMU` untuk melihat perubahan tampilan web.

---

### Bagian 4: Pengujian Konfigurasi Nginx & Restart Service

Setiap kali melakukan perubahan berkas konfigurasi Nginx, selalu lakukan uji sintaks terlebih dahulu sebelum merefresh servis:

1. Uji sintaks konfigurasi Nginx:
```bash
sudo nginx -t

```


*Pastikan muncul pesan: `syntax is ok` dan `test is successful`.*
2. Reload Nginx untuk menerapkan konfigurasi tanpa memutuskan koneksi aktif:
```bash
sudo systemctl reload nginx

```



---

## 🎯 Tugas & Evaluasi Mandiri

1. **Skenario Praktikum Mandiri:**
* Modifikasi file `/var/www/html/index.html` dengan menambahkan identitas dirimu (Nama, NIM, dan Foto/Avatar profil).
* Tambahkan blok CSS kustom yang mempercantik tampilan kartu profil web kamu.


2. **Laporan Praktikum (Screenshot):**
* Ambil *Screenshot* penuh yang menampilkan:
1. Tampilan halaman web kustom kamu saat diakses via Web Browser di alamat `http://IP_SERVER_KAMU`.
2. Output perintah `sudo systemctl status nginx` dan `sudo nginx -t` di terminal Linux.


* Simpan dengan format: **`Week04_[NIM]_[NamaMahasiswa].png`**.



---

## 🚨 Panduan Troubleshooting (Solusi Kendala)

| Masalah / Pesan Error | Kemungkinan Penyebab | Solusi Pengatasan |
| --- | --- | --- |
| **`ERR_CONNECTION_REFUSED` di Browser** | Service Nginx belum berjalan atau mati. | Jalankan servis Nginx dengan perintah: `sudo systemctl start nginx`. |
| **`403 Forbidden`** | Hak akses folder `/var/www/html` atau file `index.html` tidak dapat dibaca oleh Nginx (`www-data`). | Perbaiki hak akses berkas: `sudo chmod -R 755 /var/www/html` dan `sudo chmod 644 /var/www/html/index.html`. |
| **`404 Not Found`** | Nama file utama bukan `index.html` atau lokasi direktori root keliru. | Pastikan nama berkas tepat `index.html` dan tersimpan tepat di folder `/var/www/html/`. |
| **`job for nginx.service failed` saat restart** | Ada kesalahan penulisan/sintaks pada berkas konfigurasi Nginx. | Jalankan `sudo nginx -t` untuk mendeteksi nomor baris berkas konfigurasi yang mengalami *error*. |
