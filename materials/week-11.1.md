# 🐳 Modul Week 11 (Tahap 1): Docker Fundamentals — Instalasi & Container Dasar

## 📌 Capaian Pembelajaran (Sub-CPMK 7.1.11a)
Mahasiswa mampu memahami arsitektur **containerization** menggunakan **Docker**, melakukan instalasi Docker Engine pada sistem operasi yang digunakan, serta mengoperasikan container tunggal secara mandiri — meliputi *image management*, *container lifecycle*, *port mapping*, *volume*, dan penulisan **Dockerfile** sederhana.

> 📌 **Catatan tahapan:** Modul ini adalah **Tahap 1 dari 2**. Tahap 1 berfokus pada **fondasi Docker dan container tunggal**. Orkestrasi multi-service menggunakan **Docker Compose** akan dibahas pada **Tahap 2 (minggu berikutnya)**, bersama implementasi arsitektur *load-balanced* yang telah dipelajari di Week 09.

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
- Telah menyelesaikan praktikum **LAMP Stack manual**, **Reverse Proxy & Load Balancing Nginx** (Week 09), dan **HTTPS/SSL dengan Nginx** (Week 10).
- Laptop dengan OS **Windows 10/11**, **Linux (Ubuntu 22.04/24.04)**, atau **macOS** (Intel / Apple Silicon). VirtualBox diperbolehkan sebagai alternatif dengan RAM VM minimal **4 GB**.
- **RAM host minimal 8 GB** (Docker Desktop sendiri memakan 2–3 GB).
- **Virtualisasi hardware aktif** di BIOS/UEFI (*Intel VT-x* atau *AMD-V*). Wajib untuk Windows dan Linux, tidak perlu untuk macOS.
- **Ruang disk kosong minimal 10 GB** untuk image dan volume Docker.
- Koneksi internet aktif untuk mengunduh image dari Docker Hub.

> ⚠️ **Penting:** Jika menggunakan Windows atau macOS, instalasi Docker dilakukan melalui **Docker Desktop**. Jika menggunakan Linux, instalasi dilakukan langsung sebagai **Docker Engine** (tanpa GUI). Jika menggunakan VirtualBox, install Docker **di dalam VM**, bukan di host.

---

## 📚 Landasan Teori

### 1. Mengapa Containerization?

Sebelum Docker, memasang aplikasi web berarti meng-install PHP, MySQL, dan Nginx langsung ke sistem operasi host. Masalah yang muncul:

- **"Works on my machine"** — versi PHP di laptop A berbeda dengan laptop B, aplikasi jalan di satu tempat tapi error di tempat lain.
- **Konflik dependensi** — dua aplikasi butuh versi library berbeda, tidak bisa hidup berdampingan.
- **Repot reproduksi** — setup LAMP yang butuh 15 langkah manual harus diulang di setiap server baru.

**Docker** menyelesaikan ini dengan mengemas aplikasi **beserta seluruh dependensinya** ke dalam satu unit bernama **container** yang bersifat *isolated* dan *portable*.

### 2. Container vs Virtual Machine

Container bukan VM kecil. Container adalah **proses terisolasi** di atas kernel host, yang melihat filesystem-nya sendiri. Itu sebabnya container bisa boot dalam hitungan detik.

| Aspek | Virtual Machine (VM) | Container (Docker) |
| :--- | :--- | :--- |
| Virtualisasi | Hardware (hypervisor) | Level OS (namespace + cgroup) |
| Kernel | Memiliki kernel sendiri | Berbagi kernel host |
| Ukuran | Gigabytes | Megabytes |
| Waktu boot | 30 detik – 2 menit | 1 – 3 detik |
| Isolasi | Kuat (hardware) | Kuat (process-level) |

```text
[ Laptop Host / VM ]
│
├── Kernel Linux (shared)
│
├── Container Nginx   (proses terisolasi, FS sendiri)
├── Container PHP-FPM (proses terisolasi, FS sendiri)
└── Container MySQL   (proses terisolasi, FS sendiri)
```

### 3. Image vs Container

- **Image** = blueprint, bersifat **read-only**. Ibarat *class* di OOP.
- **Container** = instance dari image, bersifat **running**. Ibarat *object* di OOP.

Satu image bisa dijalankan menjadi banyak container sekaligus. Inilah fondasi yang nanti (di Tahap 2) dipakai untuk **scaling**.

### 4. Docker Architecture (Client–Server)

Docker menggunakan arsitektur client–server:

```text
[ docker CLI ] --(REST API)--> [ dockerd (daemon) ] --(manage)--> [ containers ]
                                       │
                                       └──> [ containerd ] --(run)--> [ runc ]
```

- **`docker` CLI** — perintah yang Anda ketik di terminal.
- **`dockerd`** — daemon yang berjalan di background.
- **`containerd` + `runc`** — komponen yang benar-benar menjalankan container.

Ketika Anda mengetik `docker run`, CLI mengirim request ke `dockerd`, yang menginstruksikan `containerd` untuk membuat container baru.

### 5. Dockerfile

**Dockerfile** adalah skrip teks berisi instruksi untuk membangun image. Instruksi umum:

| Instruksi | Fungsi |
| :--- | :--- |
| `FROM` | Base image (titik awal build). |
| `WORKDIR` | Set direktori kerja di dalam image. |
| `COPY` / `ADD` | Salin file dari host ke image. |
| `RUN` | Jalankan perintah saat build (contoh: `apt install`). |
| `EXPOSE` | Dokumentasi port (tidak otomatis membuka port ke host). |
| `CMD` / `ENTRYPOINT` | Perintah default saat container dijalankan. |

Setiap instruksi di Dockerfile menghasilkan satu **layer**. Layer di-cache: kalau isi instruksi tidak berubah, Docker tidak membangun ulang layer itu.

### 6. Port Mapping, Volume, dan Network

Tiga konsep yang wajib dipahami meskipun di Tahap 1 hanya satu container:

- **Port Mapping** (`-p 8080:80`) — meneruskan port host ke port container. Tanpa ini, container tidak bisa diakses dari luar host.
- **Volume** — penyimpanan **persistent** yang hidup di luar container. Data di volume tidak hilang saat container dihapus.
- **Bind Mount** — folder host di-mount ke container. Cocok untuk development (edit file host, langsung terlihat di container).
- **Bridge Network** — network default Docker; setiap container mendapat IP internal yang bisa diakses oleh container lain di network yang sama.

---

## 🧪 Langkah-Langkah Praktikum (Hands-On)

### Bagian 1: Instalasi Docker (Windows, Linux, macOS)

Pilih **satu** jalur instalasi sesuai sistem operasi yang Anda gunakan. Jangan mencampur langkah antar-platform.

---

#### 🪟 Untuk Pengguna Windows 10/11

Docker di Windows berjalan melalui **Docker Desktop** dengan backend **WSL 2** (Windows Subsystem for Linux). WSL 2 menyediakan kernel Linux ringan yang menjadi tempat container berjalan.

**Prasyarat:**
- Windows 11 64-bit (Home/Pro 22H2+) **atau** Windows 10 64-bit (Home/Pro 22H2 build 19045+).
- **WSL versi 2.1.5 atau lebih baru**.
- RAM minimal **4 GB** (disarankan 8 GB).
- **Virtualisasi hardware** aktif di BIOS/UEFI.

**Langkah 1 — Aktifkan WSL 2:**

Buka **PowerShell sebagai Administrator** (klik kanan Start → *Terminal (Admin)*), lalu jalankan:

```powershell
wsl --install
```

Perintah ini mengaktifkan WSL dan mengunduh Ubuntu default. **Restart komputer** jika diminta. Setelah restart, buka PowerShell lagi dan verifikasi:

```powershell
wsl --version
```

Pastikan versi **≥ 2.1.5**. Jika masih di bawahnya: `wsl --update`.

**Langkah 2 — Unduh dan instal Docker Desktop:**

1. Buka https://www.docker.com/products/docker-desktop
2. Klik **Download for Windows**. File installer (`Docker Desktop Installer.exe`) akan terunduh.
3. Jalankan installer. **Pastikan opsi "Use WSL 2 instead of Hyper-V" tercentang**.
4. Selesaikan instalasi. Restart komputer jika diminta.

**Langkah 3 — Jalankan Docker Desktop:**

1. Buka **Docker Desktop** dari Start Menu.
2. Setujui *Subscription Service Agreement* (gratis untuk pendidikan dan personal).
3. Tunggu hingga ikon paus di system tray berhenti beranimasi — artinya Docker Engine sudah siap.

> ⚠️ Jika sebelumnya Anda pernah meng-install Docker Engine langsung **di dalam** WSL (bukan lewat Docker Desktop), **hapus dulu** dengan `sudo apt remove docker-ce docker-ce-cli containerd.io` di dalam WSL. Menjalankan keduanya bersamaan dapat menyebabkan konflik.

---

#### 🐧 Untuk Pengguna Linux (Ubuntu/Debian)

Di Linux, Docker dipasang sebagai **Docker Engine** (tanpa GUI) langsung dari repositori resmi Docker.

**Prasyarat:**
- Ubuntu 24.04 LTS atau 22.04 LTS, arsitektur 64-bit.
- Akses `sudo`.

**Langkah 1 — Hapus paket Docker lama (jika ada):**

```bash
sudo apt remove docker.io docker-compose docker-compose-v2 docker-doc docker-buildx podman-docker containerd runc
```

**Langkah 2 — Tambahkan repositori resmi Docker:**

```bash
sudo apt update
sudo apt install -y ca-certificates curl gnupg

sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | \
  sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg

echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo $VERSION_CODENAME) stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
```

**Langkah 3 — Instal Docker Engine dan plugin Compose:**

```bash
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

Paket `docker-compose-plugin` menyediakan perintah `docker compose` (dengan spasi). Kita belum memakainya di Tahap 1, tetapi akan dipakai di Tahap 2. Perintah lama `docker-compose` (tanda hubung) sudah usang dan tidak akan dipakai.

**Langkah 4 — Izinkan user menjalankan Docker tanpa `sudo`:**

```bash
sudo usermod -aG docker $USER
```

> ⚠️ **Wajib logout dan login ulang** (atau jalankan `newgrp docker`) agar perubahan grup berlaku.

---

#### 🍎 Untuk Pengguna macOS

Docker di macOS berjalan melalui **Docker Desktop**. Pilih installer sesuai jenis chip Mac.

**Prasyarat:**
- macOS versi terkini atau dua versi mayor sebelumnya.
- RAM minimal **4 GB**.

**Langkah 1 — Unduh installer:**

- **Mac Apple Silicon (M1/M2/M3/M4):** [Docker Desktop for Mac with Apple silicon](https://desktop.docker.com/mac/main/arm64/Docker.dmg)
- **Mac Intel:** [Docker Desktop for Mac with Intel chip](https://desktop.docker.com/mac/main/amd64/Docker.dmg)

**Langkah 2 — Instal:**

1. Buka file `Docker.dmg`.
2. **Seret ikon Docker** ke folder **Applications**.
3. Eject volume installer.

**Langkah 3 — Jalankan Docker Desktop:**

1. Buka **Docker** dari folder Applications.
2. Setujui *Subscription Service Agreement*.
3. Tunggu hingga ikon paus di menu bar berhenti beranimasi.

> 💡 **Opsional:** Jika ada tool CLI yang masih membutuhkan Rosetta 2: `softwareupdate --install-rosetta`.

---

#### ✅ Verifikasi Instalasi (Semua Platform)

Buka terminal (PowerShell di Windows, Terminal di Linux/macOS) dan jalankan:

```bash
docker --version
docker compose version
docker run --rm hello-world
```

**Yang harus Anda lihat:**
- `docker --version` → misalnya `Docker version 28.4.0, build d8eb465`.
- `docker compose version` → misalnya `Docker Compose version v2.39.4`. **Pastikan v2.x.x**.
- `docker run --rm hello-world` → pesan **`Hello from Docker! This message shows that your installation appears to be working correctly.`**

Jika ketiga perintah berhasil tanpa error, lanjutkan ke Bagian 2.

---

### Bagian 2: Menjalankan Container Pertama

1. **Jalankan container Nginx dengan port mapping:**

   ```bash
   docker run -d --name web-test -p 8080:80 nginx:alpine
   ```

   Penjelasan:
   - `-d` = detach (jalan di background).
   - `--name` = nama container, agar mudah di-referensi.
   - `-p 8080:80` = publish port **80 container** ke port **8080 host**.
   - `nginx:alpine` = image + tag (`alpine` = varian kecil).

2. **Akses di browser:** `http://localhost:8080` → akan muncul halaman *"Welcome to nginx!"*.

3. **Inspeksi container:**

   ```bash
   docker ps                    # daftar container yang running
   docker ps -a                 # termasuk yang sudah stop
   docker logs web-test         # lihat log container
   docker exec -it web-test sh  # masuk ke shell dalam container
   ```

   Di dalam shell container, jalankan `hostname` → akan muncul ID container. Lalu `exit`.

4. **Hentikan dan hapus:**

   ```bash
   docker stop web-test
   docker rm web-test
   ```

> 📌 **Refleksi:** Berapa menit yang Anda habiskan untuk install Nginx secara manual di Week 09 vs sekarang? Berapa langkah yang bisa salah di kedua pendekatan?

---

### Bagian 3: Container Lifecycle & Image Management

Container punya siklus hidup yang jelas. Pahami transisi status berikut:

```text
created → running → paused → stopped → removed
```

1. **Lihat semua image yang sudah diunduh:**

   ```bash
   docker images
   ```

   Perhatikan kolom `REPOSITORY`, `TAG`, `IMAGE ID`, `SIZE`. Image `nginx:alpine` hanya ~10 MB karena memakai Alpine Linux, sedangkan `nginx:latest` (Debian-based) ~140 MB.

2. **Unduh image tanpa menjalankan container:**

   ```bash
   docker pull redis:7-alpine
   ```

3. **Jalankan container Redis interaktif, lalu keluar:**

   ```bash
   docker run -it --name redis-test redis:7-alpine redis-cli
   ```

   Di prompt `127.0.0.1:6379>`, jalankan `PING` → akan merespons `PONG`. Ketik `exit` untuk keluar.

4. **Hidupkan kembali container yang sudah stop:**

   ```bash
   docker start redis-test
   docker exec -it redis-test redis-cli
   # PING → PONG
   exit
   docker stop redis-test
   ```

   **Perhatikan:** `docker start` **tidak** membuat container baru — ia menghidupkan container lama beserta filesystem perubahannya.

5. **Hapus container dan image:**

   ```bash
   docker rm redis-test
   docker rmi redis:7-alpine
   ```

   > ⚠️ Image tidak bisa dihapus jika masih dipakai container yang running. Hentikan container dulu (`docker stop`), lalu `docker rm`, baru `docker rmi`.

6. **Bersihkan container yang sudah stop secara massal (hati-hati):**

   ```bash
   docker container prune
   ```

   Perintah ini akan menanyakan konfirmasi; hapus semua container berstatus *stopped*.

---

### Bagian 4: Port Mapping Deep Dive

Mari eksplorasi lebih dalam konsep `-p`.

1. **Jalankan container nginx tanpa port mapping:**

   ```bash
   docker run -d --name web-a nginx:alpine
   ```

2. **Coba akses dari host:**

   ```bash
   curl http://localhost:80
   ```

   → **Connection refused**. Container ini tidak membuka port ke host. Ia hanya bisa diakses oleh container lain di network yang sama.

3. **Cek IP internal container:**

   ```bash
   docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' web-a
   ```

   Anda akan mendapat IP seperti `172.17.0.2`. Coba akses `curl http://172.17.0.2:80` — **ini berhasil**. Container bisa diakses lewat IP internal dari host (di Linux), tetapi cara ini rapuh karena IP bisa berubah.

4. **Hentikan, hapus, dan jalankan ulang dengan port mapping:**

   ```bash
   docker stop web-a && docker rm web-a

   # Jalankan 3 container nginx di port berbeda
   docker run -d --name web1 -p 8081:80 nginx:alpine
   docker run -d --name web2 -p 8082:80 nginx:alpine
   docker run -d --name web3 -p 8083:80 nginx:alpine
   ```

5. **Verifikasi ketiga-tiganya aktif:**

   ```bash
   curl -s http://localhost:8081 | grep -i "<title>"
   curl -s http://localhost:8082 | grep -i "<title>"
   curl -s http://localhost:8083 | grep -i "<title>"
   ```

   Ketiganya merespons *"Welcome to nginx!"*. **Bukti bahwa satu image bisa dijalankan jadi banyak container independen**, dan port host berbeda diteruskan ke port 80 container yang sama.

6. **Bersihkan:**

   ```bash
   docker stop web1 web2 web3
   docker rm web1 web2 web3
   ```

> 📌 **Refleksi:** Bayangkan jika `web1`, `web2`, `web3` bukan halaman default, tetapi instance PHP-FPM. Ini adalah **fondasi load balancing** yang nanti di Tahap 2 akan dirangkai lewat Docker Compose.

---

### Bagian 5: Volume & Bind Mount

Data di dalam container bersifat **ephemeral** — hilang saat container dihapus. Volume adalah solusinya.

#### 5.1 Named Volume (untuk data persistent)

1. **Buat named volume:**

   ```bash
   docker volume create mydata
   docker volume ls
   ```

2. **Jalankan container yang menulis ke volume:**

   ```bash
   docker run --rm -v mydata:/data alpine sh -c "echo 'Halo dari container pertama' > /data/pesan.txt && cat /data/pesan.txt"
   ```

3. **Jalankan container **baru** yang membaca volume yang sama:**

   ```bash
   docker run --rm -v mydata:/data alpine cat /data/pesan.txt
   ```

   → File `pesan.txt` **masih ada** meskipun container pertama sudah dihapus (`--rm`). Inilah bukti bahwa data hidup di **volume**, bukan di container.

4. **Cek lokasi fisik volume di host (Linux):**

   ```bash
   sudo ls /var/lib/docker/volumes/mydata/_data/
   ```

   Volume disimpan di luar container. Menghapus container tidak menyentuh volume ini.

#### 5.2 Bind Mount (untuk development)

1. **Buat folder di host:**

   ```bash
   mkdir -p ~/docker-lab/site
   echo '<h1>Halo dari Bind Mount</h1>' > ~/docker-lab/site/index.html
   ```

2. **Jalankan nginx dengan bind mount:**

   ```bash
   docker run -d --name web-bind -p 8090:80 \
     -v ~/docker-lab/site:/usr/share/nginx/html:ro \
     nginx:alpine
   ```

   Penjelasan:
   - `~/docker-lab/site:/usr/share/nginx/html` = mount folder host ke path di container.
   - `:ro` = read-only (container tidak bisa mengubah file host).

3. **Buka `http://localhost:8090`** → akan muncul *"Halo dari Bind Mount"*.

4. **Edit file di host tanpa restart container:**

   ```bash
   echo '<h1>Berubah dari Host!</h1>' > ~/docker-lab/site/index.html
   curl -s http://localhost:8090
   ```

   → Browser akan langsung menampilkan perubahan. **Bind mount sangat berguna untuk development** karena edit di host langsung tercermin di container.

5. **Bersihkan:**

   ```bash
   docker stop web-bind && docker rm web-bind
   docker volume rm mydata
   ```

> 📌 **Refleksi:** Kapan pakai **named volume** dan kapan pakai **bind mount**? Named volume untuk data yang harus di-backup dan portabel antar mesin (database). Bind mount untuk file yang sedang aktif diedit (source code, konfigurasi).

---

### Bagian 6: Dockerfile Pertama — Custom Nginx Image

Sekarang buat image sendiri.

1. **Buat folder proyek:**

   ```bash
   mkdir -p ~/docker-lab/my-web
   cd ~/docker-lab/my-web
   ```

2. **Buat file `index.html`:**

   ```html
   <!DOCTYPE html>
   <html lang="id">
   <head>
     <meta charset="UTF-8">
     <title>Custom Image Saya</title>
     <style>
       body { font-family: sans-serif; text-align: center; padding: 4rem; background: #1f3d2b; color: #fff; }
       h1 { color: #e8c878; }
       code { background: #2e5a3e; padding: 0.2rem 0.5rem; border-radius: 4px; }
     </style>
   </head>
   <body>
     <h1>Halo dari Custom Image!</h1>
     <p>Container: <code>HOSTNAME</code></p>
     <p>Dibuat oleh: <strong>NAMA_ANDA - NIM</strong></p>
   </body>
   </html>
   ```

3. **Buat file `Dockerfile`** (tanpa ekstensi):

   ```dockerfile
   FROM nginx:alpine
   LABEL author="Nama Anda"
   COPY index.html /usr/share/nginx/html/index.html
   EXPOSE 80
   ```

4. **Build image:**

   ```bash
   docker build -t my-web:v1 .
   ```

   Perhatikan titik (`.`) di akhir — artinya *build context* adalah direktori saat ini (yang berisi Dockerfile). Docker akan mengirim seluruh isi direktori ke daemon saat build.

5. **Lihat layer image:**

   ```bash
   docker history my-web:v1
   ```

   Setiap instruksi di Dockerfile menghasilkan satu layer dengan ukuran berbeda.

6. **Jalankan image kustom Anda:**

   ```bash
   docker run -d --name my-web-1 -p 9000:80 my-web:v1
   ```

7. **Akses `http://localhost:9000`** di browser. Anda akan melihat halaman HTML Anda.

8. **Ubah `index.html` di host, lalu rebuild:**

   ```bash
   # edit index.html, lalu:
   docker build -t my-web:v2 .
   ```

   **Perhatikan output build:** layer `FROM nginx:alpine` akan muncul sebagai **`CACHED`** (tidak diunduh ulang) karena layer itu tidak berubah. Hanya layer `COPY` dan sesudahnya yang dibangun ulang.

> 📌 **Refleksi:** Inilah gunanya **layer caching**. Karena instruksi `COPY` ada di paling bawah, base image tidak perlu diunduh ulang. Jika `COPY` diletakkan di atas `RUN apt install ...`, setiap perubahan file akan memaksa rebuild dependensi — lambat.

9. **Bersihkan:**

   ```bash
   docker stop my-web-1 && docker rm my-web-1
   docker rmi my-web:v1 my-web:v2
   ```

---

### Bagian 7: Perintah Docker yang Wajib Dikuasai

| Perintah | Fungsi |
| :--- | :--- |
| `docker run -d --name <n> -p H:C <image>` | Jalankan container baru. |
| `docker ps` / `docker ps -a` | Daftar container running / semua. |
| `docker stop <n>` / `docker start <n>` | Hentikan / hidupkan container. |
| `docker rm <n>` | Hapus container (harus berstatus stop). |
| `docker logs <n>` | Lihat log container. |
| `docker exec -it <n> sh` | Masuk ke shell container. |
| `docker images` | Daftar image lokal. |
| `docker pull <image>` | Unduh image tanpa menjalankan. |
| `docker rmi <image>` | Hapus image. |
| `docker build -t <name>:<tag> .` | Build image dari Dockerfile. |
| `docker history <image>` | Lihat layer penyusun image. |
| `docker volume create <n>` | Buat named volume. |
| `docker volume ls` / `docker volume rm <n>` | Daftar / hapus volume. |
| `docker inspect <n>` | Detail lengkap container (termasuk IP internal). |
| `docker container prune` | Hapus semua container yang sudah stop. |

---

## 🎯 Tugas & Evaluasi Mandiri

### Tugas 1 — Custom Image (Wajib)

Buat image kustom berbasis `nginx:alpine` dengan ketentuan:

1. Halaman HTML yang menampilkan: **Nama, NIM, dan nama image Anda**.
2. Buat **dua versi** image Anda (`v1` dan `v2`) dengan perbedaan minimal pada judul halaman.
3. Tunjukkan bahwa **layer caching bekerja** — screenshot output `docker build` versi `v2` yang menunjukkan layer `FROM nginx:alpine` berstatus `CACHED`.

### Tugas 2 — Container Multi-Instance (Wajib)

1. Jalankan **3 container** dari image kustom Anda dengan nama `web-a`, `web-b`, `web-c` di port host `9001`, `9002`, `9003`.
2. Verifikasi ketiganya bisa diakses secara bersamaan.
3. Jelaskan dalam 1–2 paragraf: *"Mengapa ketiga container bisa hidup berdampingan meskipun berasal dari image yang sama?"*

### Tugas 3 — Volume Study (Wajib)

1. Buat named volume `data-tugas`.
2. Jalankan container `alpine` yang menulis **file berisi nama lengkap Anda dan NIM** ke volume tersebut.
3. Hapus container.
4. Jalankan container `alpine` baru yang membaca file dari volume yang sama. Buktikan isinya utuh.
5. Screenshot langkah 2–4.

### Tugas 4 — Laporan Praktikum (Screenshot)

Kumpulkan dalam bentuk **satu file zip** berisi screenshot:

1. **Output `docker --version`, `docker compose version`, `docker run --rm hello-world`** (verifikasi instalasi).
2. **Halaman browser `http://localhost:9001`, `9002`, `9003`** yang menampilkan Nama + NIM (bukti multi-instance dari Tugas 2).
3. **Output `docker build` versi `v2`** yang menunjukkan **layer CACHED** (bukti Tugas 1).
4. **Output `docker ps`** yang menampilkan ketiga container `web-a`, `web-b`, `web-c` berstatus `Up`.

Simpan dalam format: **`Week11_[NIM]_[NamaMahasiswa].zip`**.

> 📌 **Persiapan Tahap 2:** Image `my-web:v2` hasil Tugas 1 **jangan dihapus**. Akan dipakai sebagai base untuk latihan **Docker Compose dan load balancing** di minggu berikutnya.

---

## 🚨 Panduan Troubleshooting (Solusi Kendala)

| Masalah / Pesan Error | Kemungkinan Penyebab | Solusi Pengatasan |
| :--- | :--- | :--- |
| **`Cannot connect to the Docker daemon at unix:///var/run/docker.sock`** | Daemon belum jalan, atau user belum masuk grup `docker`. | Linux: `sudo systemctl start docker`, lalu `sudo usermod -aG docker $USER` dan **logout–login ulang**. Windows/macOS: pastikan Docker Desktop dalam status *Running* (ikon paus tidak beranimasi). |
| **`docker: command not found`** | Docker belum terpasang, atau PATH belum ter-update. | Linux: ulangi langkah instalasi Bagian 1. Windows: pastikan PowerShell dibuka setelah Docker Desktop selesai instal. macOS: pastikan Docker Desktop dari folder Applications sudah dijalankan sekali. |
| **`wsl --version` menampilkan `wsl` tidak dikenali** | WSL belum terpasang atau versi Windows terlalu lama. | Pastikan Windows 10 build 19045+ atau Windows 11. Jalankan `wsl --install` di PowerShell Admin, lalu restart. |
| **`WSL 2 installation is incomplete` saat membuka Docker Desktop** | WSL 2 belum terkonfigurasi sebagai versi default. | Di PowerShell Admin: `wsl --set-default-version 2`, lalu restart Docker Desktop. |
| **`port is already allocated`** | Port host (mis. `8080`) sudah dipakai container/proses lain. | Cari proses: `sudo lsof -i :8080` (Linux/macOS) atau `netstat -ano \| findstr :8080` (Windows). Ubah mapping ke port lain (mis. `8081:80`), atau hentikan container lama. |
| **`Cannot connect to the Docker daemon` hanya saat `sudo` tidak dipakai (Linux)** | User belum benar-benar masuk grup `docker` (efek `newgrp` belum diterapkan di sesi terminal saat ini). | Logout dari GUI lalu login ulang, atau `newgrp docker`. Verifikasi: `groups \| grep docker`. |
| **`docker build` gagal: `Cannot locate specified Dockerfile`** | Perintah `docker build` dijalankan dari direktori yang salah, atau Dockerfile tidak bernama `Dockerfile` (huruf besar `D`). | `cd` ke direktori yang berisi Dockerfile. Jika nama file berbeda, gunakan `docker build -f NamaFile -t tag .`. |
| **`docker build` error: `failed to solve: failed to read dockerfile: open Dockerfile: no such file or directory`** | Sama seperti di atas — build context salah. | Pastikan `.` di akhir perintah menunjuk ke direktori yang berisi Dockerfile. Jangan lupa titiknya. |
| **`docker rmi` error: `image is being used by running container`** | Masih ada container yang memakai image. | `docker ps -a` untuk melihat container yang memakai image. `docker stop <nama>` → `docker rm <nama>` → baru `docker rmi <image>`. |
| **Bind mount tidak terlihat efeknya di Windows/macOS** | Docker Desktop memakai VM Linux; folder yang di-mount harus berada di **shared folder** (biasanya `C:\Users\...` atau `/Users/...`). | Pindahkan folder proyek ke dalam home directory user. Cek **Settings → Resources → File Sharing** di Docker Desktop dan pastikan drive/folder tersebut diizinkan. |
| **`permission denied while trying to connect to the Docker daemon socket`** | Sama seperti masalah grup `docker` di Linux, atau folder yang di-mount tidak punya izin akses untuk container. | Linux: `sudo usermod -aG docker $USER` + relogin. Bind mount: pastikan folder host punya izin baca (`chmod o+rx`) atau jalankan container dengan `--user $(id -u):$(id -g)`. |
| **`no matching manifest for linux/arm64/v8`** (khusus Apple Silicon) | Image yang dipakai hanya tersedia untuk `amd64`. | Gunakan image yang mendukung multi-arch (sebagian besar image resmi sudah). Jika terpaksa, jalankan dengan `docker run --platform linux/amd64 <image>`, tetapi performanya akan lebih lambat. |

---

*Setelah menyelesaikan Tahap 1 ini, Anda sudah bisa menjalankan container tunggal, membangun image kustom, mengelola volume, dan membaca log. Tahap 2 (minggu berikutnya) akan menyatukan semua ini dengan **Docker Compose**, **multi-service architecture (Nginx + PHP-FPM + MySQL)**, dan **load balancing** — membangun ulang arsitektur Week 09 dan Week 10 dalam satu laptop dengan satu perintah.*
