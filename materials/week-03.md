# 🔑 Modul Week 03: Keamanan Akses Server & SSH Key Management

## 📌 Capaian Pembelajaran (Sub-CPMK 7.1.3)
Mahasiswa mampu mengamankan akses remote server menggunakan **SSH Key Management**, menerapkan konsep kriptografi asimetris (Public & Private Key), melakukan *generate* SSH Key Pair, serta mengonfigurasi autentikasi server tanpa password secara aman.

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
* Mengetahui dasar navigasi dan pengelolaan berkas Linux CLI (*materi Week 02*).
* Memiliki akses akun remote server (`mhs_xx`) yang aktif.
* Menggunakan terminal lokal (Git Bash/PowerShell untuk Windows, Terminal bawaan untuk macOS/Linux).

---

## 📚 Landasan Teori

### 1. Bahaya Autentikasi Password
Metode login server berbasis password memiliki celah keamanan tinggi terhadap serangan **Brute-Force Attacks** (percobaan tebak password secara terus-menerus oleh bot hacker). Selain itu, password yang terlalu sederhana atau sering dipakai ulang sangat rawan mengalami kebocoran.

### 2. Kriptografi Asimetris: Public vs Private Key
SSH Key Management menggunakan mekanisme **Kriptografi Kunci Publik (Asymmetric Encryption)** yang terdiri dari sepasang kunci (*Key Pair*):

* **Private Key (`id_ed25519` / `id_rsa`):**
  * Disimpan **RAHASIA** di laptop lokal milik mahasiswa.
  * *Peringatan:* Jangan pernah membagikan, mengunggah, atau mengirimkan Private Key ke siapa pun.
* **Public Key (`id_ed25519.pub` / `id_rsa.pub`):**
  * Disimpan di dalam server target pada berkas `~/.ssh/authorized_keys`.
  * Boleh disebarkan atau diketahui oleh publik secara bebas.

```text
Laptop Lokal (Client)                           Remote Server
+-----------------------+                    +---------------------------+
| Private Key           | --- Challenge ---> | Public Key                |
| (id_ed25519)          | <--- Response ---- | (~/.ssh/authorized_keys)  |
+-----------------------+                    +---------------------------+
                  [ Login Berhasil Tanpa Password ]

```

### 3. Algoritma SSH Key: RSA vs Ed25519

* **RSA:** Algoritma standar lama. Aman jika menggunakan panjang kunci minimum 3072/4096 bit.
* **Ed25519:** Algoritma modern berbasis *Elliptic Curve Cryptography*. Sangat direkomendasikan karena jauh lebih aman, performa verifikasi lebih cepat, dan ukuran kuncinya pendek.

---

## 🧪 Langkah-Langkah Praktikum (Hands-On)

> ⚠️ **PERHATIAN:** Jalankan Bagian 1 & 2 di **TERMINAL LAPTOP LOKAL**, bukan di dalam server!

### Bagian 1: Generate SSH Key Pair di Laptop Lokal

1. Buka Terminal / Git Bash baru di **laptop lokal** kamu.
2. Buat pasangan kunci SSH modern menggunakan algoritma **Ed25519**:
```bash
ssh-keygen -t ed25519 -C "email_mahasiswa@gmail.com"

```


3. **Instruksi Interaktif Terminal:**
* **Location Prompt:** `Enter file in which to save the key (/home/user/.ssh/id_ed25519):` $\rightarrow$ Tekan **Enter** (gunakan lokasi default).
* **Passphrase Prompt:** `Enter passphrase (empty for no passphrase):` $\rightarrow$ Tekan **Enter** (kosongkan untuk latihan ini).
* **Confirm Passphrase:** Tekan **Enter** sekali lagi.


4. Periksa apakah berkas SSH Key berhasil dibuat di laptop lokal:
```bash
ls -la ~/.ssh

```


*Kamu akan melihat dua berkas baru: `id_ed25519` (Private Key) dan `id_ed25519.pub` (Public Key).*

---

### Bagian 2: Transfer Public Key ke Remote Server

Pindahkan Kunci Publik (**Public Key**) dari laptop kamu ke server target.

#### Cara A: Menggunakan `ssh-copy-id` (Direkomendasikan untuk Linux/macOS/Git Bash)

```bash
ssh-copy-id username@IP_SERVER

```

*(Masukkan password server untuk verifikasi terakhir kali).*

#### Cara B: Manual (Jika `ssh-copy-id` tidak tersedia di Windows PowerShell)

1. Tampilkan isi Public Key di laptop lokal:
```bash
cat ~/.ssh/id_ed25519.pub

```


2. Salin (*copy*) seluruh teks output yang muncul (dimulai dari `ssh-ed25519 AAA...`).
3. Login ke server via SSH biasa (`ssh username@IP_SERVER`).
4. Tempelkan (*paste*) teks Public Key tersebut ke dalam file `~/.ssh/authorized_keys` di server:
```bash
mkdir -p ~/.ssh
chmod 700 ~/.ssh
nano ~/.ssh/authorized_keys

```


*(Paste kunci di baris baru, simpan dengan `Ctrl+O`, lalu keluar `Ctrl+X`).*
5. Amankan hak akses file `authorized_keys` di server:
```bash
chmod 600 ~/.ssh/authorized_keys

```



---

### Bagian 3: Pengujian Login SSH Tanpa Password

1. Keluar dari server jika kamu masih terhubung (`exit`).
2. Dari **laptop lokal**, coba lakukan SSH login ulang ke server:
```bash
ssh username@IP_SERVER

```


3. **Hasil:** Kamu akan langsung berhasil masuk ke dalam server Linux tanpa dimintai password lagi!

---

### Bagian 4: Penyederhanaan Akses via Configuration File (`~/.ssh/config`)

Agar kamu tidak perlu menghafal IP Server dan username yang panjang, konfigurasikan file `config` di laptop lokal.

1. Di **laptop lokal**, buka/buat berkas `~/.ssh/config`:
```bash
nano ~/.ssh/config

```


2. Isikan konfigurasi alias berikut:
```text
Host myserver
    HostName 10.10.1.50
    User mhs_01
    IdentityFile ~/.ssh/id_ed25519
    Port 22

```


3. Simpan dan coba login cukup dengan mengetik nama alias:
```bash
ssh myserver

```



---

## 🎯 Tugas & Evaluasi Mandiri

1. **Skenario Praktikum Mandiri:**
* Pastikan kamu sudah berhasil login ke server menggunakan alias `ssh myserver` tanpa menginput password.
* Di dalam server, periksa daftar Public Key yang diizinkan dengan perintah:
```bash
cat ~/.ssh/authorized_keys

```




2. **Laporan Praktikum (Screenshot):**
* Ambil *Screenshot* terminal lokal yang menampilkan:
1. Eksekusi perintah `ssh myserver` hingga berhasil masuk ke server tanpa prompt password.
2. Output perintah `cat ~/.ssh/authorized_keys` di dalam server.


* Simpan dengan format: **`Week03_[NIM]_[NamaMahasiswa].png`**.



---

## 🚨 Panduan Troubleshooting (Solusi Kendala)

| Masalah / Pesan Error | Kemungkinan Penyebab | Solusi Pengatasan |
| --- | --- | --- |
| **Masih diminta password saat SSH login** | Hak akses file/folder `.ssh` di server terlalu terbuka (*insecure permissions*). | Login via password, lalu jalankan perbaikan izin di server: `chmod 700 ~/.ssh && chmod 600 ~/.ssh/authorized_keys`. |
| **`Permissions 0644 for id_ed25519 are too open`** | Private key di laptop lokal terlalu bebas dibaca oleh user lain. | Jalankan perintah pengetatan akses Private Key di laptop lokal: `chmod 600 ~/.ssh/id_ed25519`. |
| **`Could not resolve hostname myserver`** | Penulisan file `~/.ssh/config` di laptop lokal keliru atau salah direktori. | Pastikan file `config` dibuat tanpa ekstensi `.txt` dan tersimpan tepat di folder `~/.ssh/config`. |