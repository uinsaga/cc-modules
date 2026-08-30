# 🐙 Modul Week 07: Multi-Container Management dengan Docker Compose & Persistensi Data (Volumes)

## 📌 Capaian Pembelajaran (Sub-CPMK 7.1.7)
Mahasiswa mampu mengelola aplikasi multi-kontainer secara deklaratif menggunakan **Docker Compose**, mengonfigurasi jaringan antar-kontainer (*Container Networking*), serta menerapkan mekanisme persistensi data menggunakan **Docker Volumes**.

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
* Telah menguasai pembuatan *custom image* via Dockerfile (*materi Week 06*).
* Memiliki akun Docker Hub dan akses SSH remote server.
* Docker Engine & Docker Compose Plugin terinstal di server.

---

## 📚 Landasan Teori

### 1. Apa itu Docker Compose?
**Docker Compose** adalah alat (*tool*) deklaratif dari Docker yang digunakan untuk mendefinisikan dan mengelola aplikasi berarsitektur **Multi-Container**. 

Dibandingkan mengetikkan puluhan perintah `docker run` yang panjang dan rawan kesalahan secara manual, Docker Compose memungkinkan kita mendefinisikan seluruh infrastruktur layanan (*services*), jaringan (*networks*), dan penyimpanan (*volumes*) dalam satu berkas konfigurasi bernama **`compose.yaml`** (atau `docker-compose.yml`).

### 2. Anatomi Arsitektur Multi-Container (Web + Database)
```text
 +-----------------------------------------------------------------------+
 |                     DOCKER BRIDGE NETWORK (app-network)               |
 |                                                                       |
 |   +------------------------+             +------------------------+   |
 |   |   Web Server Service   |             |    Database Service    |   |
 |   |        (Nginx)         | --(db:3306)->|       (MySQL)          |   |
 |   +------------------------+             +------------------------+   |
 |               |                                      |                |
 +---------------+--------------------------------------+----------------+
                 | (Port 8080:80)                       |
            Host Server                          Docker Volume (db_data)
                                                 [Persistensi Data]

```

### 3. Masalah Transient Container & Solusi Docker Volume

Secara standar, kontainer bersifat *ephemeral* (sementara). Ketika kontainer dihapus, seluruh data yang dibuat di dalamnya akan hilang.

**Docker Volume** adalah mekanisme yang disediakan oleh Docker untuk menyimpan data di luar siklus hidup kontainer (*persistent storage*) pada berkas sistem Host Server (`/var/lib/docker/volumes/`). Dengan Volume, data database tetap aman meskipun kontainer di-restart atau dihapus.

---

## 🧪 Langkah-Langkah Praktikum (Hands-On)

### Bagian 1: Persiapan File Konfigurasi Docker Compose

1. Login ke server remote via SSH:
```bash
ssh myserver

```


2. Buat direktori kerja baru untuk stack multi-kontainer:
```bash
mkdir -p ~/multi-container-stack
cd ~/multi-container-stack

```


3. Buat berkas konfigurasi `compose.yaml`:
```bash
nano compose.yaml

```


4. Tuliskan spesifikasi *stack* (Web Nginx + Database MySQL) berikut:
```yaml
services:
  web:
    image: nginx:alpine
    container_name: web_service
    ports:
      - "8080:80"
    volumes:
      - ./html:/usr/share/nginx/html
    networks:
      - app-network
    depends_on:
      - db

  db:
    image: mysql:8.0
    container_name: db_service
    environment:
      MYSQL_ROOT_PASSWORD: secretpassword
      MYSQL_DATABASE: cloud_db
    volumes:
      - db_data:/var/lib/mysql
    networks:
      - app-network

volumes:
  db_data:

networks:
  app-network:
    driver: bridge

```


*(Simpan dengan `Ctrl+O` -> `Enter`, keluar dengan `Ctrl+X`).*

---

### Bagian 2: Menyiapkan Mount Volume Lokal untuk Web Server

1. Buat direktori `html` di folder saat ini:
```bash
mkdir -p html

```


2. Buat file `index.html` kustom di dalam folder `html`:
```bash
nano html/index.html

```


3. Isikan kode HTML berikut:
```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Docker Compose Stack - Week 07</title>
    <style>
        body { font-family: Arial, sans-serif; background: #0f172a; color: #fff; text-align: center; padding-top: 50px; }
        .box { background: #1e293b; padding: 2rem; display: inline-block; border-radius: 10px; border: 1px solid #334155; }
        h1 { color: #38bdf8; }
        .status { color: #4ade80; font-weight: bold; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Multi-Container App Active! 🚀</h1>
        <p>Service Web (Nginx) & Database (MySQL) berjalan via Docker Compose.</p>
        <p class="status">Persistensi Data Volume Active</p>
    </div>
</body>
</html>

```



---

### Bagian 3: Mengelola Lifecycle Application Stack

1. Jalankan seluruh *services* secara simultan di latar belakang (*detached mode*):
```bash
docker compose up -d

```


*Amati prosesnya: Docker akan otomatis mengunduh image, membuat volume `db_data`, membuat network `app-network`, dan menyalakan kedua kontainer.*
2. Periksa status operasional kontainer yang dikelola oleh Compose:
```bash
docker compose ps

```


3. Periksa log gabungan dari seluruh layanan untuk memastikan MySQL & Nginx berjalan tanpa eror:
```bash
docker compose logs -f

```


*(Tekan `Ctrl+C` untuk keluar dari tampilan log).*
4. Uji akses web service dari browser laptop:
`http://IP_SERVER_KAMU:8080`

---

### Bagian 4: Pengujian Persistensi Data Docker Volume

1. Periksa daftar volume yang terdaftar di Docker Engine:
```bash
docker volume ls

```


*(Kamu akan melihat volume bernama `multi-container-stack_db_data`).*
2. Hentikan dan hapus seluruh kontainer stack:
```bash
docker compose down

```


3. Verifikasi bahwa volume penyimpanan database **tetap aman dan tidak terhapus**:
```bash
docker volume ls

```


4. Nyalakan kembali stack multi-kontainer:
```bash
docker compose up -d

```


*Data pada database MySQL akan tetap utuh seperti sebelum kontainer dimatikan.*

---

## 🎯 Tugas & Evaluasi Mandiri

1. **Skenario Praktikum Mandiri:**
* Tambahkan satu layanan baru bernama **`adminer`** (manajemen GUI database berbasis web) ke dalam file `compose.yaml`.
* Gunakan image **`adminer:latest`**, petakan ke **Port `8081:8080**`, dan hubungkan ke network **`app-network`**.
* Jalankan `docker compose up -d` dan uji akses dasbor Adminer via browser di `http://IP_SERVER_KAMU:8081`.


2. **Laporan Praktikum (Screenshot):**
* Ambil *Screenshot* penuh yang menampilkan:
1. Tampilan browser saat mengakses dasbor Adminer di port `8081`.
2. Output perintah `docker compose ps` yang memperlihatkan ketiga layanan (`web`, `db`, `adminer`) berstatus *Up/Running*.


* Simpan dengan format: **`Week07_[NIM]_[NamaMahasiswa].png`**.



---

## 🚨 Panduan Troubleshooting (Solusi Kendala)

| Masalah / Pesan Error | Kemungkinan Penyebab | Solusi Pengatasan |
| --- | --- | --- |
| **`yaml: line X: mapping values are not allowed in this context`** | Kesalahan indentasi pada berkas `compose.yaml`. | YAML sangat sensitif terhadap spasi. Gunakan **2 spasi** untuk indentasi dan **JANGAN menggunakan tombol Tab**. |
| **`port is already allocated` saat `compose up**` | Port 8080 atau 3306 sudah digunakan oleh servis/kontainer lain di luar Compose. | Ubah pemetaan port host di file `compose.yaml` (misal ubah `"8080:80"` menjadi `"8090:80"`). |
| **`command 'docker compose' is not a docker command`** | Menggunakan Docker Compose v1 (legacy/lama). | Gunakan perintah sintaks v2 tanpa tanda hubung: `docker compose` (bukan `docker-compose`). |
