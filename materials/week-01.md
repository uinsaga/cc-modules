# ☁️ Modul Week 01: Pengenalan Cloud Computing & Akses Remote Server (SSH)

## 📌 Capaian Pembelajaran (Sub-CPMK 7.1.1)
Mahasiswa mampu memahami konsep dasar Cloud Computing, mengenali arsitektur layanan cloud modern, serta menguasai teknik konfigurasi dan akses remote server menggunakan protokol **SSH (Secure Shell)**.

---

## 📑 Daftar Isi
1. [Prasyarat Perangkat & Sistem](#-prasyarat-perangkat--sistem)
2. [Landasan Teori](#-landasan-teori)
3. [Langkah-Langkah Praktikum (Hands-On)](#-langkah-langkah-praktikum-hands-on)
4. [Tugas & Evaluasi Mandiri](#-tugas--evaluasi-mandiri)
5. [Panduan Troubleshooting (Solusi Kendala)](#-panduan-troubleshooting-solusi-kendala)

---

## 🛠️ Prasyarat Perangkat & Sistem

Sebelum memulai praktikum, pastikan perangkat laptop kamu telah memenuhi kesiapan berikut:
* **Aplikasi Terminal / Shell Client:**
  * **Windows:** Command Prompt (CMD), PowerShell, atau **Git Bash** (Sangat direkomendasikan).
  * **macOS / Linux:** Terminal bawaan OS.
* **Kredensial Server (Diberikan oleh Dosen/Asisten Lab):**
  * **IP Server (Host):** `192.168.x.x` atau `10.10.x.x`
  * **Username:** `mhs_xx` (sesuai nomor urut/NIM)
  * **Password Default:** `******`
  * **Port SSH:** `22` (default)

---

## 📚 Landasan Teori

### 1. Apa itu Cloud Computing?
**Cloud Computing** (Komputasi Awan) adalah model penyediaan sumber daya komputasi—seperti server, database, penyimpanan data (storage), jaringan, dan perangkat lunak—melalui jaringan internet secara *on-demand*. 

Dengan teknologi cloud, kita tidak perlu membeli, membangun, dan merawat fisik server di gedung sendiri (On-Premise). Kita cukup menyewa infrastruktur sesuai kebutuhan (*Pay-As-You-Go*) dari penyedia layanan cloud (*Cloud Provider*) seperti Linode, AWS, GCP, atau DigitalOcean.

### 2. Tiga Model Layanan Cloud Computing
* **IaaS (Infrastructure as a Service):**
  * Penyedia cloud menyediakan infrastruktur dasar berupa komputer virtual (*Virtual Machine/VPS*), jaringan, dan ruang penyimpanan.
  * Mahasiswa memiliki kendali penuh atas Operating System (OS), instalasi aplikasi, dan konfigurasi keamanan.
  * *Contoh:* Linode, DigitalOcean Droplet, AWS EC2.
* **PaaS (Platform as a Service):**
  * Penyedia cloud menyediakan platform siap pakai untuk mendeploy kodingan aplikasi. Pengembang tidak perlu memikirkan pemeliharaan OS atau web server.
  * *Contoh:* Vercel, Render, Cloudflare Pages.
* **SaaS (Software as a Service):**
  * Layanan perangkat lunak siap pakai untuk pengguna akhir (*end-user*).
  * *Contoh:* Google Drive, Gmail, Canva, Office 365.

```text
  +-------------------------------------------------------+
  |                   SaaS (Aplikasi)                    |
  +-------------------------------------------------------+
  |              PaaS (Platform Deployment)               |
  +-------------------------------------------------------+
  |             IaaS (Infrastruktur Server/VM)            |
  +-------------------------------------------------------+

```

### 3. Konsep Protokol SSH (Secure Shell)

**SSH (Secure Shell)** adalah protokol jaringan kriptografi yang bekerja pada layer aplikasi (default port **22**) untuk memfasilitasi komunikasi data dan eksekusi perintah secara aman antara komputer lokal (*client*) dengan server jarak jauh (*remote server*).

Seluruh instruksi yang dikirimkan melalui SSH dienkripsi untuk mencegah kebocoran data (*eavesdropping*) atau peretasan di tengah jalan.

---

## 🧪 Langkah-Langkah Praktikum (Hands-On)

### Bagian 1: Orientasi Lingkungan Terminal Lokal

1. Buka aplikasi **Terminal** (macOS/Linux) atau **Git Bash / PowerShell** (Windows).
2. Periksa identitas pengguna lokal dan direktori aktif kamu saat ini dengan menjalankan perintah:
```bash
whoami
pwd

```



---

### Bagian 2: Autentikasi & Login Remote Server via SSH

1. Ketikkan format perintah SSH berikut pada terminal lokal kamu (sesuai kredensial yang dibagikan):
```bash
ssh username@IP_SERVER

```


*Contoh:*
```bash
ssh mhs_01@10.10.1.50

```


2. **Penanganan Fingerprint Authenticity (Khusus Login Pertama):**
Saat pertama kali terhubung ke server baru, SSH akan menampilkan peringatan keamanan:
```text
The authenticity of host '10.10.1.50 (10.10.1.50)' can't be established.
ED25519 key fingerprint is SHA256:xX87aK9...
Are you sure you want to continue connecting (yes/no/[fingerprint])?

```


* Ketik **`yes`** lalu tekan **Enter**. Langkah ini menyimpan kunci *host* server ke dalam berkas `~/.ssh/known_hosts` di laptop kamu.


3. **Input Password:**
Terminal akan meminta password user:
```text
mhs_01@10.10.1.50's password:

```


> ⚠️ **PENTING:** Saat mengetikkan password, **karakter/kursor memang sengaja tidak akan muncul atau bergerak** di layar terminal. Ini adalah fitur keamanan standar Linux. Ketik password dengan teliti lalu tekan **Enter**.


4. **Tanda Keberhasilan Login:**
Jika berhasil, tampilan *command prompt* di terminal akan berubah menunjukkan identitas server remote:
```bash
mhs_01@ubuntu-server:~$

```



---

### Bagian 3: Eksplorasi Informasi System & Network Server

Setelah berada di dalam server Linux remote, jalankan serangkaian perintah berikut untuk memeriksa spesifikasi server:

1. **Mengecek Detail Sistem Operasi (Distribution & Version):**
```bash
cat /etc/os-release

```


*Amati output untuk mengetahui versi OS (misal: Ubuntu 22.04 LTS atau Debian).*
2. **Mengecek Nama Host Server (Hostname):**
```bash
hostnamectl

```


3. **Mengecek IP Public Server dari Sisi Internet:**
```bash
curl ifconfig.me
echo ""

```


4. **Mengecek Durasi Server Bekerja & Beban Sistem (Uptime):**
```bash
uptime

```


5. **Mengecek Kapasitas Memori (RAM):**
```bash
free -h

```



---

### Bagian 4: Mengakhiri Sesi SSH Remote

Setelah selesai melakukan eksplorasi server, keluar dari sesi remote untuk kembali ke terminal laptop lokal:

```bash
exit

```

*Tampilan prompt akan kembali ke identitas laptop lokal kamu.*

---

## 🎯 Tugas & Evaluasi Mandiri

1. **Laporan Praktikum (Screenshot):**
* Ambil *Screenshot* (Tangkapan Layar) penuh yang memperlihatkan:
1. Proses eksekusi perintah login `ssh username@IP_SERVER` hingga berhasil masuk.
2. Output dari perintah `cat /etc/os-release` dan `free -h`.


* Simpan berkas gambar dengan format: **`Week01_[NIM]_[NamaMahasiswa].png`**.


2. **Pertanyaan Analisis (Jawab singkat pada lembar tugas):**
* Mengapa karakter password tidak ditampilkan saat kita mengetikkannya di terminal SSH?
* Apa perbedaan mendasar antara model layanan IaaS dan PaaS berdasarkan pemahamanmu pada praktikum hari ini?



---

## 🚨 Panduan Troubleshooting (Solusi Kendala)

| Masalah / Pesan Error | Kemungkinan Penyebab | Solusi Pengatasan |
| --- | --- | --- |
| **`ssh: connect to host IP_SERVER port 22: Connection refused`** | Service SSH di server mati atau port 22 diblokir. | Pastikan IP server sudah benar dan mintalah asisten lab mengecek status `systemctl status ssh` di server utama. |
| **`Connection timed out`** | Laptop tidak berada dalam satu jaringan dengan server. | Pastikan laptop kamu terhubung ke jaringan Wi-Fi/VPN Kampus yang sama dengan lokasi server berada. |
| **`Permission denied (publickey,password)`** | Penulisan username atau password salah. | Periksa kembali huruf besar/kecil (case-sensitive) pada username dan password. Pastikan tidak ada spasi yang terselip. |
| **`WARNING: REMOTE HOST IDENTIFICATION HAS CHANGED!`** | Kunci fingerprint server telah berubah (server di-reinstall). | Buka terminal lokal, jalankan perintah: `ssh-keygen -R IP_SERVER` untuk menghapus chache *known_hosts* lama, lalu coba SSH ulang. |
