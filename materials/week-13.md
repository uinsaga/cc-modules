# 📦 Modul Week 13: Cloud Object Storage & S3-Compatible Storage dengan MinIO

## 📌 Capaian Pembelajaran (Sub-CPMK 7.1.13)
Mahasiswa mampu memahami konsep dasar **Object Storage** dan perbedaannya dengan Block/File Storage, mengonfigurasi **MinIO** sebagai layanan S3-compatible storage mandiri, mengelola *Bucket* & *Access Policies*, serta mengintegrasikan API S3 ke dalam aplikasi web.

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
* Menguasai pengelolaan multi-kontainer via Docker Compose (*materi Week 07*).
* Memiliki akses SSH remote server dengan hak akses `sudo`.
* Port **9000 (MinIO API)** dan **9001 (MinIO Console)** terbuka di server kamu.

---

## 📚 Landasan Teori

### 1. Komparasi Tipe Penyimpanan Data (Block vs File vs Object Storage)
| Fitur | Block Storage (EBS/Disk) | File Storage (NFS/NAS) | Object Storage (S3/MinIO) |
| :--- | :--- | :--- | :--- |
| **Struktur** | Sektor Raw / Unformatted Block. | Hirarki Folder/Direktori. | Struktur Datar (*Flat Namespace*). |
| **Akses Data** | Dibaca oleh OS via Protokol Disk. | Dibaca via SMB/NFS Protokol. | Dibaca via **REST API HTTP/HTTPS**. |
| **Metadata** | Sangat Terbatas. | Terbatas (Nama, Ukuran, Izin). | **Kustom & Tak Terbatas** (JSON/KeyValue). |
| **Skalabilitas** | Terbatas pada ukuran Disk. | Terbatas pada kapasitas NAS. | **Hampir Tak Terbatas (Petabytes)**. |
| **Penggunaan** | Database, OS Root Partition. | Shared Network Drive Office. | **Assets Web, Video, Media, Backup**. |

### 2. Apa itu MinIO & AWS S3 API Standard?
**AWS S3 (Simple Storage Service)** telah menjadi standar industri (*de facto standard*) untuk protokol penyimpan objek (*object storage*). 

**MinIO** adalah server penyimpan objek *open-source* berkinerja tinggi yang kompatibel 100% dengan standar **AWS S3 API**. MinIO memungkinkan kita memiliki cloud storage mandiri (*self-hosted*) dengan performa enterprise di infrastruktur server lokal maupun cloud private.

### 3. Konsep Utama MinIO / S3
* **Bucket:** Wadah utama tempat menyimpan objek (serupa dengan folder tingkat atas/root directory).
* **Object:** Berkas/file yang disimpan beserta metadata kustomnya. Setiap objek diakses melalui kunci unik (*Object Key/URL*).
* **Access Keys:** Pasangan **Access Key (Username)** dan **Secret Key (Password)** untuk autentikasi API S3.
* **Bucket Policy:** Aturan hak akses terhadap bucket (misal: *Private*, *Public Read-Only*, atau *Custom policy*).

---

## 🧪 Langkah-Langkah Praktikum (Hands-On)

### Bagian 1: Deployment MinIO Server via Docker Compose

1. Login ke server remote via SSH:
   ```bash
   ssh myserver

```

2. Buat direktori kerja baru untuk MinIO Stack:
```bash
mkdir -p ~/minio-stack
cd ~/minio-stack

```


3. Buat berkas `compose.yaml`:
```bash
nano compose.yaml

```


4. Tuliskan spesifikasi layanan MinIO beserta volume persistensi datanya:
```yaml
services:
  minio:
    image: minio/minio:latest
    container_name: minio_server
    ports:
      - "9000:9000"   # Port API S3
      - "9001:9001"   # Port Console UI Web
    environment:
      MINIO_ROOT_USER: adminminio
      MINIO_ROOT_PASSWORD: supersecretpassword
    volumes:
      - minio_data:/data
    command: server /data --console-address ":9001"
    restart: always

volumes:
  minio_data:

```


*(Simpan `Ctrl+O` -> `Enter`, keluar `Ctrl+X`).*
5. Jalankan kontainer MinIO:
```bash
docker compose up -d
docker compose ps

```



---

### Bagian 2: Manajemen Bucket & Access Policy via MinIO Console UI

1. Buka browser laptop kamu dan akses MinIO Console:
`http://IP_SERVER_KAMU:9001`
2. Login menggunakan kredensial yang ada di `compose.yaml`:
* **Username:** `adminminio`
* **Password:** `supersecretpassword`


3. **Membuat Bucket Baru:**
* Masuk ke menu **Buckets** pada sidebar kiri -> Klik **Create Bucket**.
* Beri nama bucket: **`app-assets`**.
* Klik **Create Bucket**.


4. **Mengubah Access Policy Menjadi Public:**
* Pilih bucket **`app-assets`** yang baru dibuat.
* Masuk ke tab **Anonymous** / **Access Policy**.
* Ubah policy dari `Private` menjadi **`Public`** (atau **`Custom`** -> isi prefix `*` dengan rule `Read-Only`).
* Klik **Set Policy**. *(Langkah ini memungkinkan aset seperti gambar dapat diakses publik via URL browser).*


5. **Unggah File Uji Coba:**
* Masuk ke menu **Object Browser** -> Pilih bucket **`app-assets`**.
* Klik **Upload** -> **Upload File** -> Pilih gambar/foto bebas dari laptop kamu (misal: `logo.png`).


6. **Uji Akses Direct URL Objektif:**
Buka tab baru di browser dan akses gambar langsung via URL REST API MinIO:
`http://IP_SERVER_KAMU:9000/app-assets/logo.png`

---

### Bagian 3: Konfigurasi Service Account (S3 Access Keys)

1. Kembali ke dasbor MinIO Console UI (`:9001`).
2. Masuk ke menu **Access Keys** pada sidebar kiri.
3. Klik tombol **Create access key**.
4. MinIO akan secara otomatis membuatkan **Access Key** dan **Secret Key**.
* **Simpan/Salin** kedua nilai kuis tersebut (atau download berkas credentials JSON).


5. Kredensial inilah yang nantinya akan digunakan oleh skrip/aplikasi backend (Node.js, Python, PHP, Golang) untuk mengunggah file ke MinIO secara otomatis.

---

### Bagian 4: Manajemen Object Storage via MinIO Client (`mc` CLI)

MinIO menyediakan CLI tool bernama `mc` (*MinIO Client*) untuk mengelola S3 storage langsung dari terminal Linux.

1. Jalankan `mc` langsung dari dalam kontainer MinIO yang sedang aktif:
```bash
docker exec -it minio_server mc alias set myminio http://localhost:9000 adminminio supersecretpassword

```


2. Tampilkan daftar bucket yang ada di MinIO via CLI:
```bash
docker exec -it minio_server mc ls myminio/

```


3. Tampilkan daftar file/objek di dalam bucket `app-assets`:
```bash
docker exec -it minio_server mc ls myminio/app-assets/

```



---

## 🎯 Tugas & Evaluasi Mandiri

1. **Skenario Praktikum Mandiri:**
* Buat bucket baru bernama **`user-uploads`** via CLI (`mc mb myminio/user-uploads`).
* Atur akses policy bucket `user-uploads` tersebut menjadi **`download`** (Public Read) via CLI (`mc anonymous set download myminio/user-uploads`).
* Unggah sebuah berkas teks bernama **`test.txt`** ke dalam bucket tersebut menggunakan perintah `mc cp`.
* Verifikasi keteraksesan file dari terminal server menggunakan perintah `curl http://localhost:9000/user-uploads/test.txt`.


2. **Laporan Praktikum (Screenshot):**
* Ambil *Screenshot* penuh yang menampilkan:
1. Tampilan dasbor **MinIO Console UI** pada menu **Buckets** yang memperlihatkan bucket `app-assets` dan `user-uploads`.
2. Tampilan browser yang berhasil membuka file/gambar publik dari URL API MinIO port 9000 (`http://IP_SERVER_KAMU:9000/app-assets/...`).


* Simpan dengan format: **`Week13_[NIM]_[NamaMahasiswa].png`**.



---

## 🚨 Panduan Troubleshooting (Solusi Kendala)

| Masalah / Pesan Error | Kemungkinan Penyebab | Solusi Pengatasan |
| --- | --- | --- |
| **`AccessDenied: Access Denied` saat membuka URL objek di port 9000** | Access Policy bucket masih berstatus **`Private`**. | Ubah Access Policy bucket pada MinIO Console UI menjadi **`Public`** / **`Read-Only`**, atau jalankan `mc anonymous set download myminio/NAMA_BUCKET`. |
| **`Port 9000 / 9001 Already Allocated`** | Port default MinIO sudah digunakan oleh servis lain di server. | Uji port host alternatif di `compose.yaml` (misal ubah `"9000:9000"` menjadi `"9005:9000"` dan `"9001:9001"` menjadi `"9006:9001"`). |
| **`Connection Refused` saat mengakses MinIO Console** | Lupa mencantumkan flag `--console-address ":9001"` di perintah `command`. | Pastikan file `compose.yaml` menyertakan arahan `command: server /data --console-address ":9001"`. |

