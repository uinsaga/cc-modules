
# 🚀 Modul Week 11: CI/CD Pipeline Fundamentals dengan GitHub Actions & Docker Integration

## 📌 Capaian Pembelajaran (Sub-CPMK 7.1.11)
Mahasiswa mampu memahami konsep dasar **Continuous Integration & Continuous Deployment (CI/CD)**, mengonfigurasi otomatisasi *workflow* menggunakan **GitHub Actions**, serta membangun *pipeline* otomatis untuk proses *testing*, *building*, dan *pushing* Docker Image ke **Docker Hub** secara otomatis setiap kali ada perubahan kode (*commit/push*).

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
* Memahami pembuatan custom Docker image via Dockerfile (*materi Week 06*).
* Memiliki akun aktif di **GitHub** ([github.com](https://github.com)) dan **Docker Hub** ([hub.docker.com](https://hub.docker.com)).
* Git Client terinstal pada komputer lokal atau server kamu.

---

## 📚 Landasan Teori

### 1. Apa itu CI/CD?
* **Continuous Integration (CI):** Praktik pengintegrasian perubahan kode secara otomatis ke repositori utama secara berkala. Setiap integrasi diverifikasi secara otomatis melalui proses *build* dan pengujian (*automated testing*) untuk mendeteksi bug sedini mungkin.
* **Continuous Deployment (CD):** Tahapan otomatisasi lanjutan di mana kode yang telah lolos pengujian pada tahap CI langsung di-deploy secara otomatis ke lingkungan produksi (seperti Docker Registry atau Cloud Server).

```text
[ Developer Push Code ] 
       │
       ▼ (Git Push)
[ GitHub Repository ] ──triggers──► [ GitHub Actions Runner ]
                                           │
                                           ├─► 1. Checkout Code
                                           ├─► 2. Docker Login
                                           ├─► 3. Build Docker Image
                                           └─► 4. Push Image to Docker Hub

```

### 2. Konsep Utama GitHub Actions

* **Workflow:** Alur kerja otomatisasi yang didefinisikan dalam berkas berkategori YAML di folder `.github/workflows/`.
* **Event:** Pemicu (*trigger*) yang memulai sebuah workflow (contoh: `push` ke branch `main`, `pull_request`, atau pembuatan `release`).
* **Jobs:** Serangkaian langkah (*steps*) yang dieksekusi dalam runner yang sama.
* **Steps:** Perintah individual yang menjalankan aksi (*actions*) tertentu atau skrip shell.
* **Runner:** Server lingkungan eksekusi milik GitHub (misal: `ubuntu-latest`) yang menjalankan pekerjaan tersebut.

### 3. Keamanan Kredensial dengan GitHub Secrets

**JANGAN EVER** menyimpan kredensial sensitif seperti *password* atau *API Token* langsung di dalam file konfigurasi publik atau berkas skrip. GitHub menyediakan fitur **Encrypted Secrets** untuk menyimpan data rahasia yang dapat diakses secara aman oleh skrip workflow CI/CD.

---

## 🧪 Langkah-Langkah Praktikum (Hands-On)

### Bagian 1: Persiapan Repositori GitHub & Aplikasi Web

1. Buat repositori baru di akun GitHub milikmu dengan nama **`cicd-docker-demo`** (Public/Private).
2. *Clone* repositori tersebut ke lokal atau server kamu:
```bash
git clone [https://github.com/USERNAME_GITHUB/cicd-docker-demo.git](https://github.com/USERNAME_GITHUB/cicd-docker-demo.git)
cd cicd-docker-demo

```


3. Buat berkas `index.html` sederhana:
```bash
nano index.html

```


*Isi dengan kode HTML:*
```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>CI/CD Automated Deployment</title>
    <style>
        body { font-family: sans-serif; background: #0f172a; color: #fff; text-align: center; padding-top: 50px; }
        .card { background: #1e293b; padding: 2rem; display: inline-block; border-radius: 10px; border: 1px solid #334155; }
        h1 { color: #38bdf8; }
        .badge { background: #22c55e; color: #000; padding: 5px 10px; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Automated Build with GitHub Actions ⚡</h1>
        <p>Image ini dibangun dan di-push otomatis via CI/CD Pipeline.</p>
        <p><span class="badge">BUILD STATUS: SUCCESS</span></p>
    </div>
</body>
</html>

```


4. Buat berkas **`Dockerfile`**:
```bash
nano Dockerfile

```


*Isi dengan skrip berikut:*
```dockerfile
FROM nginx:alpine
COPY index.html /usr/share/nginx/html/index.html
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]

```



---

### Bagian 2: Konfigurasi Encrypted Secrets di GitHub

1. Buka browser, masuk ke akun **Docker Hub** ([hub.docker.com](https://hub.docker.com)).
2. Buat **Personal Access Token (PAT)**:
* Masuk ke **Account Settings** -> **Security** -> **New Access Token**.
* Beri nama `github-cicd-token`, beri akses **Read & Write**, lalu klik **Generate**.
* **Salin (copy)** token tersebut!


3. Buka repositori **`cicd-docker-demo`** di GitHub.
4. Masuk ke tab **Settings** -> **Secrets and variables** -> **Actions**.
5. Klik tombol **New repository secret** dan buat 2 rahasia berikut:
* **Secret 1:**
* Name: `DOCKERHUB_USERNAME`
* Secret: *[Masukkan Username Docker Hub kamu]*


* **Secret 2:**
* Name: `DOCKERHUB_TOKEN`
* Secret: *[Masukkan Personal Access Token yang disalin dari Docker Hub]*





---

### Bagian 3: Membuat Workflow File GitHub Actions

1. Buat struktur folder khusus `.github/workflows/` di dalam direktori proyek kamu:
```bash
mkdir -p .github/workflows

```


2. Buat berkas YAML konfigurasinya:
```bash
nano .github/workflows/docker-ci.yml

```


3. Tuliskan skrip otomatisasi pipeline berikut:
```yaml
name: Build and Push Docker Image

# Trigger: Jalankan workflow setiap kali ada push ke branch main
on:
  push:
    branches:
      - main

jobs:
  build-and-push:
    runs-on: ubuntu-latest

    steps:
      # Step 1: Checkout repositori kode dari GitHub
      - name: Checkout Repository
        uses: actions/checkout@v4

      # Step 2: Login ke Docker Hub menggunakan GitHub Secrets
      - name: Log in to Docker Hub
        uses: docker/login-action@v3
        with:
          username: ${{ secrets.DOCKERHUB_USERNAME }}
          password: ${{ secrets.DOCKERHUB_TOKEN }}

      # Step 3: Setup Docker Buildx untuk fitur build canggih
      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      # Step 4: Build image dan Push ke Docker Hub
      - name: Build and Push Docker Image
        uses: docker/build-push-action@v5
        with:
          context: .
          file: ./Dockerfile
          push: true
          tags: |
            ${{ secrets.DOCKERHUB_USERNAME }}/cicd-demo:latest
            ${{ secrets.DOCKERHUB_USERNAME }}/cicd-demo:${{ github.sha }}

```



---

### Bagian 4: Pengujian & Eksekusi Pipeline

1. Commit seluruh berkas baru dan push ke repositori GitHub:
```bash
git add .
git commit -m "feat: setup CI/CD pipeline with GitHub Actions"
git branch -M main
git push -u origin main

```


2. Buka repositori di GitHub via browser, lalu klik tab **Actions**.
3. **Amati Workflow yang Berjalan:** Kamu akan melihat pekerjaan `Build and Push Docker Image` sedang berjalan otomatis secara real-time.
4. Setelah berstatus **Centang Hijau (Success)**, buka akun **Docker Hub** kamu.
5. Verifikasi bahwa repositori **`cicd-demo`** telah berhasil dibuat dan ter-update otomatis dengan tag `latest` dan tag commit hash!

---

## 🎯 Tugas & Evaluasi Mandiri

1. **Skenario Praktikum Mandiri:**
* Lakukan perubahan konten pada file `index.html` (misal: tambahkan Nama & NIM kamu).
* Lakukan `git commit` dan `git push` ke branch `main`.
* Perhatikan otomatisasi pemicu pada tab **Actions** di GitHub.
* Pull image terbaru tersebut di server remote kamu (`docker pull USERNAME_DOCKERHUB/cicd-demo:latest`) dan jalankan kontainernya untuk memverifikasi perubahan halaman web!


2. **Laporan Praktikum (Screenshot):**
* Ambil *Screenshot* penuh yang menampilkan:
1. Halaman tab **GitHub Actions** yang memperlihatkan status *Workflow Run* berstatus **Success / Passed** (centang hijau).
2. Halaman dasbor **Docker Hub** yang memperlihatkan daftar *Tags* yang ter-push secara otomatis.


* Simpan dengan format: **`Week11_[NIM]_[NamaMahasiswa].png`**.



---

## 🚨 Panduan Troubleshooting (Solusi Kendala)

| Masalah / Pesan Error | Kemungkinan Penyebab | Solusi Pengatasan |
| --- | --- | --- |
| **`Error: Username and password required` pada step Login** | Penulisan nama variabel Secret di file `docker-ci.yml` tidak cocok dengan yang dibuat di Settings GitHub. | Pastikan ejaan `DOCKERHUB_USERNAME` dan `DOCKERHUB_TOKEN` di GitHub Secrets persis sama dengan sintaks di file YAML. |
| **`Process completed with exit code 1` (Invalid Dockerfile)** | Terdapat kesalahan sintaks penulisan di dalam `Dockerfile` atau letak berkas tidak sesuai direktori `context`. | Cek log kegagalan di detail *Actions Step*, pastikan nama file adalah **`Dockerfile`** (D kapital) dan berada di root folder. |
| **Workflow tidak terpicu secara otomatis** | File YAML diletakkan di folder yang salah atau nama branch di trigger `push` tidak sesuai. | Pastikan struktur folder harus tepat: **`.github/workflows/nama-file.yml`** dan branch default lokal kamu sesuai dengan konfigurasi (`main` vs `master`). |

