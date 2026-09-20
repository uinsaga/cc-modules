# 🔑 Modul Week 03: Keamanan Akses Server & SSH Key Management (VirtualBox & Windows Client)

## 📌 Capaian Pembelajaran (Sub-CPMK 7.1.3)

Mahasiswa mampu mengamankan akses remote server Linux pada VirtualBox menggunakan **SSH Key Management**, menerapkan konsep kriptografi asimetris (Public & Private Key), melakukan *generate* SSH Key Pair pada sistem operasi Windows, serta mengonfigurasi autentikasi server tanpa password secara aman.

---

## 📑 Daftar Isi

1. [Prasyarat Perangkat & Sistem](https://www.google.com/search?q=%2523-prasyarat-perangkat--sistem&utm_source=gemini)
2. [Landasan Teori](https://www.google.com/search?q=%2523-landasan-teori&utm_source=gemini)
3. [Langkah-Langkah Praktikum (Hands-On)](https://www.google.com/search?q=%2523-langkah-langkah-praktikum-hands-on&utm_source=gemini)
4. [Tugas & Evaluasi Mandiri](https://www.google.com/search?q=%2523-tugas--evaluasi-mandiri&utm_source=gemini)
5. [Panduan Troubleshooting (Solusi Kendala)](https://www.google.com/search?q=%2523-panduan-troubleshooting-solusi-kendala&utm_source=gemini)

---

## 🛠️ Prasyarat Perangkat & Sistem

Sebelum memulai praktikum, pastikan kondisi lingkungan praktikum memenuhi hal berikut:

1. **Virtual Machine (Server):**
* VirtualBox dengan VM Linux Server (Ubuntu/Debian) sudah *running*.
* Menggunakan adapter jaringan **Host-Only Adapter** atau **Bridged Adapter** (agar IP server bisa diakses dari laptop Windows/Host).
* Mengetahui IP Address server (bisa diuji di VM dengan perintah `ip a` atau `hostname -I`).


2. **Laptop Lokal (Windows Client):**
* Menggunakan **PowerShell** / **Command Prompt (CMD)** atau **Git Bash**.
* Mengetahui kredensial login awal server (`username` dan `password`).



---

## 📚 Landasan Teori

### 1. Bahaya Autentikasi Password

Metode login server berbasis password memiliki celah keamanan tinggi terhadap serangan **Brute-Force Attacks** (percobaan tebak password secara otomatis dan terus-menerus oleh bot hacker). Password yang sederhana atau dipakai berulang juga sangat rawan bocor.

### 2. Kriptografi Asimetris: Public vs Private Key

SSH Key Management menggunakan mekanisme **Kriptografi Kunci Publik (Asymmetric Encryption)** yang terdiri dari sepasang kunci (*Key Pair*):

* **Private Key (`id_ed25519`):**
* Disimpan **RAHASIA** di laptop lokal Windows (`C:\Users\NamaUser\.ssh\id_ed25519`).
* *Peringatan:* Jangan pernah membagikan, mengunggah, atau mengirimkan Private Key ke siapa pun.


* **Public Key (`id_ed25519.pub`):**
* Disimpan di dalam VM Linux server pada berkas `~/.ssh/authorized_keys`.
* Boleh disebarkan atau diketahui oleh publik secara bebas.



```text
Laptop Windows (Client)                       VM Server (VirtualBox)
+-----------------------+                    +---------------------------+
| Private Key           | --- Challenge ---> | Public Key                |
| (id_ed25519)          | <--- Response ---- | (~/.ssh/authorized_keys)  |
+-----------------------+                    +---------------------------+
                  [ Login Berhasil Tanpa Password ]

```

### 3. Algoritma SSH Key: RSA vs Ed25519

* **RSA:** Algoritma standar lama (panjang kunci rekomendasi 3072/4096 bit).
* **Ed25519:** Algoritma modern berbasis *Elliptic Curve Cryptography*. Sangat direkomendasikan karena jauh lebih aman, proses autentikasi lebih cepat, dan ukuran kuncinya pendek.

---

## 🧪 Langkah-Langkah Praktikum (Hands-On)

> ⚠️ **PERHATIAN:** Jalankan Bagian 1, 2, dan 4 di **TERMINAL WINDOWS LOKAL** (PowerShell / Git Bash), bukan di dalam VirtualBox!

---

### Bagian 1: Generate SSH Key Pair di Laptop Windows

1. Buka **PowerShell** atau **Git Bash** di Windows kamu.
2. Buat pasangan kunci SSH modern dengan perintah berikut:
```bash
ssh-keygen -t ed25519 -C "email_mahasiswa@gmail.com"

```


3. **Instruksi Interaktif Terminal:**
* **Location Prompt:** `Enter file in which to save the key (C:\Users\NamaUser/.ssh/id_ed25519):` $\rightarrow$ Tekan **Enter** (gunakan lokasi default).
* **Passphrase Prompt:** `Enter passphrase (empty for no passphrase):` $\rightarrow$ Tekan **Enter** (kosongkan untuk latihan ini).
* **Confirm Passphrase:** Tekan **Enter** sekali lagi.


4. Cek apakah berkas SSH Key berhasil dibuat di laptop lokal:
* **Di PowerShell:**
```powershell
Get-ChildItem ~\.ssh

```


* **Di Git Bash:**
```bash
ls -la ~/.ssh

```




*(Kamu akan melihat dua berkas baru: `id_ed25519` [Private Key] dan `id_ed25519.pub` [Public Key]).*

---

### Bagian 2: Transfer Public Key ke Server VirtualBox

Pindahkan isi Public Key (`id_ed25519.pub`) dari Windows ke VM Linux Server.

#### Opsional A: Menggunakan `ssh-copy-id` (Jika Menggunakan Git Bash)

Jika kamu menggunakan **Git Bash**, cukup jalankan:

```bash
ssh-copy-id username@IP_SERVER_VIRTUALBOX

```

*(Masukkan password server untuk verifikasi terakhir kali).*

---

#### Opsional B: Manual lewat PowerShell (Sangat Direkomendasikan untuk Windows)

1. Tampilkan isi Public Key di PowerShell laptop lokal:
```powershell
Get-Content ~\.ssh\id_ed25519.pub

```


2. Salin (*copy*) seluruh teks output yang muncul (diawali dengan `ssh-ed25519 AAA...`).
3. Login ke server VirtualBox via SSH biasa dari PowerShell:
```powershell
ssh username@IP_SERVER_VIRTUALBOX

```


4. Setelah masuk ke dalam server VirtualBox, jalankan perintah berikut untuk menyimpan kunci publik:
```bash
mkdir -p ~/.ssh
chmod 700 ~/.ssh
nano ~/.ssh/authorized_keys

```


5. Tempelkan (*paste*) baris Public Key yang sudah disalin tadi ke dalam file tersebut.
* Simpan file: Tekan `Ctrl + O`, lalu `Enter`.
* Keluar dari editor: Tekan `Ctrl + X`.


6. Amankan hak akses file `authorized_keys` di dalam server:
```bash
chmod 600 ~/.ssh/authorized_keys

```


7. Keluar dari server VirtualBox:
```bash
exit

```



---

### Bagian 3: Pengujian Login SSH Tanpa Password

1. Pastikan kamu sudah kembali ke terminal lokal Windows (bukan di dalam VM server).
2. Lakukan SSH login ulang ke server VirtualBox:
```powershell
ssh username@IP_SERVER_VIRTUALBOX

```


3. **Hasil:** Kamu akan langsung berhasil masuk ke dalam server Linux tanpa dimintai password lagi!

---

### Bagian 4: Penyederhanaan Akses via Configuration File (`~/.ssh/config`)

Agar tidak perlu menghafal IP Address VirtualBox dan username yang panjang, kita buatkan alias di Windows.

1. Di terminal **Windows lokal**, buka atau buat file `config` di dalam folder `.ssh`:
* **Di PowerShell:**
```powershell
notepad ~\.ssh\config

```


* **Di Git Bash:**
```bash
nano ~/.ssh/config

```




2. Isikan teks konfigurasi berikut (sesuaikan `HostName` dengan IP VirtualBox dan `User` dengan username server kamu):
```text
Host myserver
    HostName 192.168.56.101
    User mhs_01
    IdentityFile ~/.ssh/id_ed25519
    Port 22

```


3. Simpan dan tutup file tersebut (Pastikan file tersimpan sebagai `config` tanpa ekstensi `.txt`).
4. Sekarang kamu bisa login ke VirtualBox cukup dengan mengetik:
```powershell
ssh myserver

```



---

## 🎯 Tugas & Evaluasi Mandiri

1. **Skenario Praktikum Mandiri:**
* Pastikan kamu sudah bisa login ke server VirtualBox cukup dengan mengetik perintah `ssh myserver` tanpa menginput password.
* Di dalam server, periksa daftar Public Key yang diizinkan dengan menjalankan:
```bash
cat ~/.ssh/authorized_keys

```




2. **Laporan Praktikum (Screenshot):**
* Ambil *Screenshot* terminal Windows lokal yang menampilkan:
1. Eksekusi perintah `ssh myserver` dari Windows hingga berhasil masuk ke server tanpa prompt password.
2. Output perintah `cat ~/.ssh/authorized_keys` di dalam VM server.


* Simpan gambar dengan format: **`Week03_[NIM]_[NamaMahasiswa].png`**.



---

## 🚨 Panduan Troubleshooting (Solusi Kendala)

| Masalah / Pesan Error | Kemungkinan Penyebab | Solusi Pengatasan |
| --- | --- | --- |
| **`Connection refused` / `Connection timed out**` | IP Server VirtualBox salah, SSH service di VM belum aktif, atau tipe Network Adapter VirtualBox masih *NAT*. | 1. Cek IP VM dengan `ip a`.<br>

<br>2. Pastikan Network Adapter VM diset ke **Host-Only Adapter** / **Bridged**.<br>

<br>3. Pastikan service SSH di VM aktif: `sudo systemctl status ssh`. |
| **Masih diminta password saat `ssh myserver**` | Hak akses folder `.ssh` atau file `authorized_keys` di server terlalu terbuka (*insecure permissions*). | Login ke server via password, lalu perbaiki izin direktori di server:<br>

<br>`chmod 700 ~/.ssh && chmod 600 ~/.ssh/authorized_keys` |
| **`Permissions 0644 for id_ed25519 are too open`** | Private key di laptop lokal terlalu bebas dibaca oleh user lain. | **Di Git Bash Windows:**<br>

<br>`chmod 600 ~/.ssh/id_ed25519`<br>

<br>**Di PowerShell:** Sesuaikan hak akses file via *Properties -> Security -> Advanced* agar hanya akun Windows kamu yang memiliki akses penuh. |
| **`Could not resolve hostname myserver`** | Penulisan nama file `config` keliru atau tersimpan sebagai `config.txt`. | Buka PowerShell, jalankan `Get-ChildItem ~\.ssh`. Jika terdapat `config.txt`, ubah namanya menjadi `config` tanpa ekstensi dengan perintah:<br>

<br>`Rename-Item ~\.ssh\config.txt ~\.ssh\config` |
