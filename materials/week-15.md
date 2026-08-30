# 🛡️ Modul Week 15: DevSecOps, Container Hardening & Vulnerability Scanning dengan Trivy

## 📌 Capaian Pembelajaran (Sub-CPMK 7.1.15)
Mahasiswa mampu memahami konsep keamanan **DevSecOps**, mengidentifikasi celah keamanan (*vulnerabilities*) pada Docker Image & konfigurasi sistem, mengimplementasikan teknik **Container Hardening** (Non-Root User, Read-Only Filesystem, Resource Limits), serta melakukan pemindaian keamanan otomatis menggunakan **Trivy**.

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
* Telah menguasai pembuatan Dockerfile dan integrasi CI/CD (*materi Week 06 & Week 11*).
* Memiliki akses SSH ke remote server Linux dengan akses `sudo`.
* Docker Engine terinstal dan aktif di server.

---

## 📚 Landasan Teori

### 1. Pergeseran Paradigma Keamanan: Shift-Left Security (DevSecOps)
Dalam pendekatan tradisional (DevOps), keamanan diuji di akhir siklus pengembangan (*Post-Deployment*). **DevSecOps** mengusung prinsip **Shift-Left Security**, yaitu mengintegrasikan analisis dan pengujian keamanan sejak tahap awal penulisan kode (*development*), *build*, hingga *pipeline CI/CD*.

```text
Pendekatan Tradisional:
[ Plan ] ──► [ Code ] ──► [ Build ] ──► [ Test ] ──► [ Deploy ] ──► [ 🔒 Security Check ] (Terlambat!)

Shift-Left (DevSecOps):
[ Plan ] ──► [ Code ] ──► [ Build ] ──► [ Test ] ──► [ Deploy ]
               │             │             │
               🔒 Scan       🔒 Scan       🔒 Scan (Terintegrasi Sejak Awal!)

```

### 2. Vektor Serangan Utama pada Kontainer

* **Menjalankan Kontainer sebagai `root`:** Jika penyerang berhasil mengeksploitasi celah aplikasi (*RCE*), mereka akan mendapatkan akses `root` penuh ke seluruh server host.
* **Image Rentan (Vulnerable Base Image):** Menggunakan base image usang yang memuat pustaka (*libraries*) ber-CVE (*Common Vulnerabilities and Exposures*) tinggi.
* **Sensitif Data Terbuka:** Menyimpan API Key, Password, atau Sertifikat di dalam layer Dockerfile.
* **Tanpa Batasan Sumber Daya (No Resource Limits):** Menghadapkan kontainer pada risiko *Denial of Service (DoS)* yang dapat membuat server host *crash*.

### 3. Apa itu Trivy Security Scanner?

**Trivy** adalah alat pemindai keamanan (*vulnerability & misconfiguration scanner*) *open-source* berkinerja tinggi dari Aqua Security. Trivy mampu memindai:

1. **Container Images** (mendeteksi OS package & application dependencies yang terinfeksi CVE).
2. **IaC / Misconfigurations** (Dockerfile, Kubernetes Manifest, Terraform).
3. **Secrets** (mendeteksi kebocoran token/key yang tidak sengaja ter-commit).

---

## 🧪 Langkah-Langkah Praktikum (Hands-On)

### Bagian 1: Instalasi Trivy Security Scanner

1. Login ke server remote via SSH:
```bash
ssh myserver

```


2. Unduh dan pasang repositori resmi Aqua Security Trivy:
```bash
sudo apt-get install wget apt-transport-https gnupg lsb-release -y
wget -qO - [https://aquasecurity.github.io/trivy-repo/deb/public.key](https://aquasecurity.github.io/trivy-repo/deb/public.key) | gpg --dearmor | sudo tee /usr/share/keyrings/trivy.gpg > /dev/null
echo "deb [signed-by=/usr/share/keyrings/trivy.gpg] [https://aquasecurity.github.io/trivy-repo/deb](https://aquasecurity.github.io/trivy-repo/deb) $(lsb_release -sc) main" | sudo tee -a /etc/list.d/trivy.list /etc/apt/sources.list.d/trivy.list
sudo apt-get update
sudo apt-get install trivy -y

```


3. Verifikasi instalasi Trivy:
```bash
trivy --version

```



---

### Bagian 2: Pemindaian Vulnerability pada Public & Custom Image

1. **Memindai Unsecure Base Image Usang (misal: `python:3.8`):**
```bash
trivy image python:3.8

```


*Amati tabel hasil analisis. Kamu akan melihat ratusan celah keamanan berkategori LOW, MEDIUM, HIGH, hingga CRITICAL.*
2. **Memfokuskan Pemindaian hanya pada Celah Kerentanan Kritis (HIGH & CRITICAL):**
```bash
trivy image --severity HIGH,CRITICAL python:3.8

```


3. **Memindai Image Ringan yang Sudah Di-hardening (`python:3.8-slim` atau `python:3.8-alpine`):**
```bash
trivy image --severity HIGH,CRITICAL python:3.8-alpine

```


*Bandingkan jumlah kerentanan antara image standar vs image berbasis Alpine!*

---

### Bagian 3: Praktik Container Hardening (Best Practices)

Kita akan membandingkan Dockerfile yang **Tidak Aman (Unsecure)** dengan Dockerfile yang **Sudah Di-hardening (Secure)**.

1. Buat direktori kerja baru:
```bash
mkdir -p ~/container-hardening
cd ~/container-hardening

```


2. Buat file **`Dockerfile.unsecure`**:
```dockerfile
# Skenario Buruk: Fat Base Image, Running as Root, Hardcoded Secrets
FROM node:14

WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .

# Menyimpan Kredensial Sensitif (SANGAT BERBAHAYA)
ENV DATABASE_PASSWORD="SuperSecretAdminPassword123"

EXPOSE 3000
CMD ["node", "server.js"]

```


3. Buat file **`Dockerfile.secure`** (Terapkan 5 Pilar Hardening):
```dockerfile
# 1. Gunakan Minimal Minimalist Base Image (Distroless / Alpine)
FROM node:20-alpine

# 2. Set Environment Production
ENV NODE_ENV=production

WORKDIR /app

# 3. Optimasi Cache & Hanya Install Production Dependencies
COPY package*.json ./
RUN npm ci --only=production && npm cache clean --force

COPY . .

# 4. Keamanan Utama: Ubah User dari 'root' menjadi Non-Root User ('node')
USER node

EXPOSE 3000
CMD ["node", "server.js"]

```


4. **Memindai Miskonfigurasi Dockerfile menggunakan Trivy IaC:**
```bash
trivy config Dockerfile.unsecure
trivy config Dockerfile.secure

```


*Trivy akan mendeteksi bahaya running as root dan instruksi tidak aman pada `Dockerfile.unsecure`.*

---

### Bagian 4: Menjalankan Kontainer dengan Runtime Security Flags

Selain pada level Dockerfile, kita dapat menerapkan *runtime hardening* saat menjalankan kontainer via Docker CLI atau Docker Compose.

1. **Jalankan Kontainer dengan Batasan Akses (Read-Only & Non-Root):**
```bash
docker run -d \
  --name secure_app \
  --read-only \
  --cap-drop=ALL \
  --memory="256m" \
  --cpus="0.5" \
  --user 1000:1000 \
  nginx:alpine

```


2. **Penjelasan Parameter Keamanan Runtime:**
* `--read-only`: Mencegah malware menulis file baru ke sistem file internal kontainer.
* `--cap-drop=ALL`: Mencabut seluruh *Linux Kernel Capabilities* istimewa dari kontainer.
* `--memory="256m"` & `--cpus="0.5"`: Membatasi penggunaan memori RAM dan CPU untuk mencegah serangan DoS.
* `--user 1000:1000`: Memaksa kontainer berjalan sebagai user biasa (*non-privileged*).



---

## 🎯 Tugas & Evaluasi Mandiri

1. **Skenario Praktikum Mandiri:**
* Ambil salah satu aplikasi web/image yang pernah kamu buat di minggu-minggu sebelumnya.
* Jalankan perintah `trivy image --severity HIGH,CRITICAL USERNAME_DOCKERHUB/NAMA_IMAGE:TAG`.
* Lakukan *refactoring* pada Dockerfile aplikasi milikmu dengan menerapkan minimal 3 teknik hardening:
1. Mengganti base image ke versi `-alpine` atau `-slim`.
2. Menambahkan instruksi `USER` (non-root).
3. Menghapus file cache sementara saat proses build.


* Re-build image tersebut dan jalankan kembali Trivy scan untuk membuktikan penurunan jumlah CVE!


2. **Laporan Praktikum (Screenshot):**
* Ambil *Screenshot* penuh yang menampilkan:
1. Perbandingan hasil keluaran terminal `trivy image` **sebelum** dan **sesudah** dilakukan hardening pada image milikmu.
2. Output terminal dari perintah `trivy config Dockerfile.secure` yang terbebas dari kesalahan konfigurasi kritis.


* Simpan dengan format: **`Week15_[NIM]_[NamaMahasiswa].png`**.



---

## 🚨 Panduan Troubleshooting (Solusi Kendala)

| Masalah / Pesan Error | Kemungkinan Penyebab | Solusi Pengatasan |
| --- | --- | --- |
| **`DB download error / DB update failed` saat jalan Trivy** | Koneksi internet server terhambat saat mengunduh database CVE terbaru dari GitHub Release. | Jalankan pemindaian dengan opsi `--light` atau pastikan server dapat mengakses `github.com` via Port 443. |
| **`read-only file system` error saat kontainer berjalan** | Aplikasi mencoba menulis log/temporary file ke direktori internal saat dijalankan dengan flag `--read-only`. | Mounting folder khusus yang butuh akses tulis menggunakan temporary volume dalam memori (`--tmpfs /tmp`). |
| **`Permission Denied` saat instruksi `USER node` di Dockerfile** | Non-root user tidak memiliki hak akses (*ownership*) terhadap direktori kerja `/app`. | Tambahkan instruksi `RUN chown -R node:node /app` sebelum beralih ke `USER node`. |
