# PRAKTIKUM I (CLOUD COMPUTING)

## Akses Remote Server Menggunakan SSH

---

### A. Tujuan Praktikum

Setelah menyelesaikan praktikum ini, mahasiswa diharapkan mampu:

1. Menginstal dan mengkonfigurasi VirtualBox pada sistem operasi Windows.
2. Membuat dan menginstal mesin virtual Linux (Debian/Ubuntu) menggunakan ISO Netinst.
3. Memahami konsep dan implementasi NAT Port Forwarding pada VirtualBox.
4. Mengkonfigurasi SSH Server pada sistem Linux.
5. Melakukan akses remote ke server menggunakan SSH dari Windows.

---

### B. Alat dan Bahan

| No  | Alat/Bahan | Spesifikasi                              |
| --- | ---------- | ---------------------------------------- |
| 1   | Laptop/PC  | OS Windows 10/11, minimal RAM 8GB        |
| 2   | VirtualBox | Versi terbaru (7.0.x)                    |
| 3   | ISO Linux  | Debian 12.x / Ubuntu 22.04 LTS (Netinst) |
| 4   | Terminal   | Windows Command Prompt / PowerShell      |

---

### C. Dasar Teori

**SSH (Secure Shell)** adalah protokol jaringan yang memungkinkan komunikasi data secara aman antara dua perangkat melalui jaringan yang tidak aman. SSH menggunakan enkripsi kriptografi untuk mengamankan sesi remote login, eksekusi perintah, dan transfer file.

**Port Forwarding** adalah teknik yang memetakan port pada host (Windows) ke port pada guest (VM Linux), sehingga akses dari luar dapat diteruskan ke layanan di dalam VM.

---

### D. Langkah-Langkah Praktikum

#### **Tahap 1: Instalasi VirtualBox di Windows**

1. Unduh installer VirtualBox dari [https://www.virtualbox.org/](https://www.virtualbox.org/)
2. Jalankan installer dan ikuti wizard instalasi (default settings)
3. Centang opsi "Install VirtualBox Networking" saat instalasi
4. Restart komputer jika diperlukan

#### **Tahap 2: Membuat VM Baru dan Instalasi Linux**

1. Buka **VirtualBox** → Klik **New**
2. Isi konfigurasi VM:
   - **Name:** `Server-SSH`
   - **Folder:** (sesuai keinginan)
   - **ISO Image:** Pilih file ISO Netinst yang sudah diunduh
   - **Type:** `Linux`
   - **Version:** `Debian (64-bit)` atau `Ubuntu (64-bit)`
3. Klik **Next** → Atur:
   - **Memory (RAM):** 2048 MB (minimal)
   - **Processors:** 2 CPU
4. Klik **Next** → **Create a virtual hard disk now** → Ukuran: **20 GB**
5. Klik **Finish** → VM siap dijalankan

6. **Instalasi Linux:** [Download ISO](https://cdimage.debian.org/debian-cd/current/amd64/iso-cd/debian-13.6.0-amd64-netinst.iso)
   - Pilih **Start** → Ikuti proses instalasi
   - Pilih **Install** (bukan Graphical Install)
   - Setelan penting:
     - **Hostname:** `server-ssh`
     - **Domain name:** (kosongkan)
     - **Root password:** `root123`
     - **Full name:** `Mahasiswa`
     - **Username:** `student`
     - **Password:** `student123`
   - **Partitioning:** Pilih **Guided - use entire disk**
   - **Software selection:** Centang **SSH server** (penting!)
   - Tunggu hingga instalasi selesai → Reboot

#### **Tahap 3: Konfigurasi Jaringan VirtualBox (NAT + Port Forwarding)**

1. Di VirtualBox, pilih VM → **Settings** → **Network**
2. Pastikan **Adapter 1** terhubung ke **NAT**
3. Klik **Advanced** → **Port Forwarding**
4. Tambahkan aturan port forwarding:

| Name | Protocol | Host IP   | Host Port | Guest IP  | Guest Port |
| ---- | -------- | --------- | --------- | --------- | ---------- |
| SSH  | TCP      | 127.0.0.1 | 2222      | 10.0.2.15 | 22         |

5. Klik **OK** → **OK**

#### **Tahap 4: Konfigurasi SSH Server di Linux (Terminal)**

Login ke VM Linux dengan user `student`.

```bash
# 0. install openssh-server
sudo apt update
sudo apt install openssh-server

# 1. Cek status SSH Server
sudo systemctl status ssh

# 2. Jika belum aktif, aktifkan
sudo systemctl enable ssh
sudo systemctl start ssh

# 3. Cek apakah SSH mendengarkan di port 22
sudo ss -tlnp | grep :22

# 4. Edit konfigurasi SSH (hardening)
sudo nano /etc/ssh/sshd_config
```

Ubah beberapa parameter berikut (cari dan sesuaikan):

```conf
Port 22
PermitRootLogin no
PasswordAuthentication yes
PubkeyAuthentication yes
MaxAuthTries 3
```

Simpan file (`Ctrl+O`, `Enter`, `Ctrl+X`).

```bash
# 5. Restart SSH Server
sudo systemctl restart ssh

# 6. Cek kembali status
sudo systemctl status ssh
```

#### **Tahap 5: Remote SSH dari Windows**

Buka **Command Prompt** atau **PowerShell** di Windows:

```cmd
# Koneksi ke VM melalui SSH
ssh student@127.0.0.1 -p 2222
```

Ketika diminta:

- **Password:** `student123`

Setelah berhasil login, tampilan terminal akan berubah menjadi prompt Linux.

```bash
# Contoh perintah remote
whoami
hostname
ip a
exit
```

---

### E. Tabel Tugas Mahasiswa

| No  | Perintah yang Dijalankan        | Lokasi Eksekusi | Output yang Diharapkan                 | Screenshot (✓) |
| --- | ------------------------------- | --------------- | -------------------------------------- | -------------- |
| 1   | `sudo systemctl status ssh`     | VM Linux        | Status `active (running)`              | ☐              |
| 2   | `sudo ss -tlnp \| grep :22`     | VM Linux        | Menampilkan proses sshd di port 22     | ☐              |
| 3   | `ssh student@127.0.0.1 -p 2222` | Windows CMD     | Prompt Linux (`student@server-ssh:~$`) | ☐              |
| 4   | `whoami` (setelah SSH)          | Windows CMD     | `student`                              | ☐              |
| 5   | `hostname` (setelah SSH)        | Windows CMD     | `server-ssh`                           | ☐              |

---

### F. Soal Evaluasi

1. **Analisis:** Mengapa kita menggunakan Port Forwarding dengan mapping Host Port 2222 ke Guest Port 22, bukan langsung menggunakan port 22?
2. **Analisis:** Apa fungsi dari parameter `PermitRootLogin no` pada file `sshd_config`? Jelaskan dampaknya terhadap keamanan server.

3. **Analisis:** Sebutkan dan jelaskan minimal 3 metode autentikasi yang didukung oleh SSH Server! Mana yang paling aman?

4. **Pemecahan Masalah:** Jika Anda tidak bisa terkoneksi ke VM melalui SSH, sebutkan langkah-langkah troubleshooting yang akan Anda lakukan secara berurutan!

5. **Pengembangan:** Bagaimana cara mengubah port SSH dari 22 menjadi 2222 di sisi server Linux? Tuliskan langkah-langkahnya!

---

---

## Pilihan 2: Panduan Langkah CLI Saja (Singkat & Praktis)

---

# PANDUAN PRAKTIKUM CLI – SSH REMOTE SERVER

## Cloud Computing – Sesi Lab

**Instruksi:** Ikuti urutan perintah di bawah ini secara berurutan. Tandai setiap langkah yang berhasil.

---

### 🔧 A. SETUP VIRTUALBOX (Windows)

| Langkah | Aksi                                                                          |
| ------- | ----------------------------------------------------------------------------- | ----------------------- | -------------------------- |
| 1       | Install VirtualBox dari `virtualbox.org`                                      |
| 2       | **New VM** → Name: `Server-SSH`, Type: Linux, Version: Debian/Ubuntu (64-bit) |
| 3       | RAM: 2048 MB, CPU: 2, Disk: 20 GB                                             |
| 4       | Start VM → Install Linux (pilih **Install**, bukan Graphical)                 |
| 5       | **Hostname:** `server-ssh`                                                    | **Username:** `student` | **Password:** `student123` |
| 6       | Pada **Software selection**, centang **SSH server**                           |
| 7       | Selesai instalasi → Reboot VM                                                 |

---

### 🌐 B. KONFIGURASI PORT FORWARDING (VirtualBox)

| Langkah | Aksi                                                              |
| ------- | ----------------------------------------------------------------- |
| 8       | VM **Settings** → **Network** → Adapter 1: **NAT**                |
| 9       | Klik **Advanced** → **Port Forwarding** → Tambahkan:              |
| 10      | Name: `SSH`, Protocol: `TCP`, Host Port: `2222`, Guest Port: `22` |

---

### 🐧 C. KONFIGURASI SSH SERVER (Di Dalam VM Linux)

Login ke VM sebagai `student`. Jalankan perintah berikut:

```bash
# 11. Cek status SSH
sudo systemctl status ssh

# 12. Aktifkan SSH (jika belum)
sudo systemctl enable --now ssh

# 13. Cek port yang digunakan
sudo ss -tlnp | grep :22

# 14. Edit konfigurasi
sudo nano /etc/ssh/sshd_config
# Ubah: PermitRootLogin no
#       PasswordAuthentication yes
#       MaxAuthTries 3
# Simpan: Ctrl+O, Enter, Ctrl+X

# 15. Restart SSH
sudo systemctl restart ssh

# 16. Verifikasi
sudo systemctl status ssh
```

---

### 💻 D. REMOTE SSH DARI WINDOWS

Buka **Command Prompt** atau **PowerShell** di Windows:

```cmd
# 17. Koneksi SSH
ssh student@127.0.0.1 -p 2222
# Password: student123

# 18. Setelah login, jalankan:
whoami
hostname
ip a
exit
```

---

### ✅ E. CEK KEBERHASILAN

| No  | Indikator Keberhasilan                                  | Status (✓/✗) |
| --- | ------------------------------------------------------- | ------------ |
| 1   | `systemctl status ssh` menampilkan `active (running)`   |              |
| 2   | `ss -tlnp` menampilkan `0.0.0.0:22` atau `*:22`         |              |
| 3   | Perintah `ssh student@127.0.0.1 -p 2222` berhasil login |              |
| 4   | Prompt berubah menjadi `student@server-ssh:~$`          |              |
| 5   | Perintah `ip a` menampilkan alamat IP (10.0.2.15)       |              |

---

## Pilihan 3: Lembar Kerja Mahasiswa (LKM / Sheet Soal & Tugas)

---

# LEMBAR KERJA MAHASISWA (LKM)

## Cloud Computing – Praktikum SSH Server

---

**Nama:** ......................................................

**NIM:** ......................................................

**Tanggal:** ......................................................

**Kelas:** ......................................................

---

### A. Petunjuk Teknis Singkat

1. **Persiapan:**
   - Pastikan VirtualBox terinstal di komputer Anda.
   - Siapkan ISO Linux (Debian/Ubuntu) Netinst.
   - Buat VM dengan spesifikasi: RAM 2GB, CPU 2, HDD 20GB.

2. **Instalasi Linux:**
   - Hostname: `server-ssh`
   - Username: `student`
   - Password: `student123`
   - **Penting:** Centang opsi **SSH server** pada software selection.

3. **Port Forwarding:**
   - Setting VirtualBox: **NAT** + Port Forwarding `Host 2222 → Guest 22`

4. **SSH Server:**
   - Aktifkan dan konfigurasi SSH di dalam VM.
   - Lakukan remote dari Windows menggunakan `ssh student@127.0.0.1 -p 2222`

---

### B. Tabel Perintah & Output/Screenshot

| No  | Perintah                                              | Lokasi Eksekusi        | Output (tulis hasilnya)               | Screenshot |
| --- | ----------------------------------------------------- | ---------------------- | ------------------------------------- | ---------- | ------------------------------------- | --- |
| 1   | `sudo systemctl status ssh`                           | VM Linux               | ..................................... | ☐          |
| 2   | `sudo ss -tlnp \| grep :22`                           | VM Linux               | ..................................... | ☐          |
| 3   | `cat /etc/ssh/sshd_config \| grep -E "PermitRootLogin | PasswordAuthentication | MaxAuthTries"`                        | VM Linux   | ..................................... | ☐   |
| 4   | `ssh student@127.0.0.1 -p 2222`                       | Windows CMD            | ..................................... | ☐          |
| 5   | `whoami && hostname && pwd` (setelah SSH)             | Windows CMD            | ..................................... | ☐          |
| 6   | `ip a show eth0` (setelah SSH)                        | Windows CMD            | ..................................... | ☐          |
| 7   | `exit` (keluar SSH)                                   | Windows CMD            | ..................................... | ☐          |

---

### C. Soal Evaluasi & Analisis

**Petunjuk:** Jawablah pertanyaan berikut dengan jelas dan ringkas!

---

**Soal 1 (Analisis Port Forwarding)**  
Mengapa pada praktikum ini kita menggunakan Host Port `2222` bukan `22`? Jelaskan konsekuensi jika kita menggunakan Host Port `22` langsung!

```
Jawaban:
.................................................................................................................................
.................................................................................................................................
```

---

**Soal 2 (Keamanan SSH)**  
Jelaskan mengapa disarankan untuk mengatur `PermitRootLogin no` dan `MaxAuthTries 3` pada file `sshd_config`! Apa yang terjadi jika nilai `MaxAuthTries` diset terlalu besar?

```
Jawaban:
.................................................................................................................................
.................................................................................................................................
```

---

**Soal 3 (Metode Autentikasi)**  
Sebutkan 2 metode autentikasi yang umum digunakan pada SSH selain password! Bagaimana cara mengaktifkan autentikasi menggunakan kunci publik (public key)?

```
Jawaban:
.................................................................................................................................
.................................................................................................................................
```

---

**Soal 4 (Troubleshooting)**  
Jika Anda mencoba `ssh student@127.0.0.1 -p 2222` tetapi muncul pesan `Connection refused`, sebutkan 4 kemungkinan penyebab dan langkah perbaikannya!

```
Jawaban:
1. ...............................................................................
2. ...............................................................................
3. ...............................................................................
4. ...............................................................................
```

---

**Soal 5 (Pengembangan)**  
Bagaimana cara mengganti port SSH server dari 22 menjadi 2222 di sisi Linux? Tuliskan langkah-langkah lengkapnya!

```
Jawaban:
.................................................................................................................................
.................................................................................................................................
```

---

### D. Rubrik Penilaian (Untuk Dosen/Asisten)

| Kriteria                                       | Bobot    | Nilai |
| ---------------------------------------------- | -------- | ----- |
| Keberhasilan setup VM & instalasi Linux        | 15%      |       |
| Keberhasilan konfigurasi Port Forwarding       | 15%      |       |
| Keberhasilan konfigurasi & aktivasi SSH Server | 20%      |       |
| Keberhasilan koneksi SSH dari Windows          | 15%      |       |
| Kelengkapan tabel output/screenshot            | 15%      |       |
| Kualitas jawaban soal evaluasi (5 soal)        | 20%      |       |
| **Total**                                      | **100%** |       |

---

**Catatan untuk Asisten:** Pastikan setiap mahasiswa menunjukkan koneksi SSH yang berhasil sebelum meninggalkan lab.

---

**Selamat Mengerjakan!** 🚀
