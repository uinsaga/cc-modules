# 🔒 Modul Week 10: HTTPS Security & SSL/TLS Automation dengan Let's Encrypt (Certbot)

## 📌 Capaian Pembelajaran (Sub-CPMK 7.1.10)
Mahasiswa mampu memahami arsitektur keamanan web berbasis protocol **HTTPS & SSL/TLS**, mengonfigurasi sertifikat SSL/TLS otomatis dari **Let's Encrypt** menggunakan **Certbot**, serta mengimplementasikan pengalihan otomatis (*HTTP to HTTPS Redirection*) dan *auto-renewal* pada Nginx Server.

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
* Telah menguasai Reverse Proxy & Load Balancing Nginx (*materi Week 09*).
* Memiliki **Domain Name** (atau Subdomain) publik aktif yang DNS **A Record**-nya sudah diarahkan (*pointed*) ke Public IP Server kamu (misal: `app.domainkamu.com`).
* Port **80 (HTTP)** dan **443 (HTTPS)** terbuka (*allow*) di Firewall / Security Group cloud provider server kamu.

---

## 📚 Landasan Teori

### 1. Mengapa Perlu HTTP vs HTTPS?
* **HTTP (Port 80):** Komunikasi data antara browser dan server dikirim dalam bentuk teks polos (*plaintext*). Rentan terhadap serangan *Man-in-the-Middle (MitM)*, pencurian kredensial, dan manipulasi data.
* **HTTPS (Port 443):** Komunikasi data dienkripsi menggunakan protokol **TLS (Transport Layer Security)**. Menjamin 3 pilar keamanan:
  1. **Confidentiality:** Data terenkripsi sehingga tidak bisa dibaca pihak ketiga.
  2. **Integrity:** Data tidak dapat diubah di tengah jalan tanpa terdeteksi.
  3. **Authentication:** Membuktikan bahwa server benar-benar pemilik domain resmi.

```text
[ Browser ] --( Kredensial Enkripsi: TLS )--> [ Nginx Proxy (Port 443) ] --( Plain HTTP Internal )--> [ Container Backend ]

```

### 2. Let's Encrypt & Protokol ACME

**Let's Encrypt** adalah Certificate Authority (CA) nirlaba yang menyediakan sertifikat SSL/TLS secara **gratis, otomatis, dan terbuka**. Let's Encrypt menggunakan protokol **ACME (Automated Certificate Management Environment)** untuk memverifikasi kepemilikan domain melalui tantangan *HTTP-01 Challenge*.

### 3. Apa itu Certbot?

**Certbot** adalah alat bantu (*CLI Client*) resmi dari Electronic Frontier Foundation (EFF) yang bertugas meminta, memverifikasi, memasang, dan memperbarui (*renew*) sertifikat SSL Let's Encrypt pada web server secara otomatis.

---

## 🧪 Langkah-Langkah Praktikum (Hands-On)

### Bagian 1: Persiapan Domain & Server Block Nginx

1. Login ke server remote via SSH:
```bash
ssh myserver

```


2. Buat berkas konfigurasi Nginx baru khusus untuk domain kamu:
```bash
sudo nano /etc/nginx/sites-available/secure_app.conf

```


3. Tuliskan blok server HTTP (Port 80) dasar terlebih dahulu:
```nginx
server {
    listen 80;
    server_name domainkamu.com [www.domainkamu.com](https://www.domainkamu.com); # Ubah dengan nama domain aktif kamu

    location / {
        proxy_pass [http://127.0.0.1:8081](http://127.0.0.1:8081); # Diarahkan ke backend container kamu
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}

```


4. Aktifkan konfigurasi server block dan reload Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/secure_app.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

```



---

### Bagian 2: Instalasi Certbot & Plugin Nginx

1. Pastikan indeks paket sistem diperbarui dan instal **Certbot** beserta plugin **python3-certbot-nginx**:
```bash
sudo apt update
sudo apt install -y certbot python3-certbot-nginx

```


2. Verifikasi status instalasi Certbot:
```bash
certbot --version

```



---

### Bagian 3: Penerbitan & Pemasangan Sertifikat SSL/TLS Otomatis

1. Jalankan perintah otomatisasi Certbot dengan plugin Nginx:
```bash
sudo certbot --nginx -d domainkamu.com -d [www.domainkamu.com](https://www.domainkamu.com)

```


2. **Proses Interaktif Certbot:**
* **Email Address:** Masukkan email aktif kamu (untuk notifikasi kadaluarsa sertifikat).
* **Terms of Service:** Ketik `Y` lalu `Enter` untuk menyetujui syarat & ketentuan.
* **EFF Electronic Frontier Foundation Email:** Ketik `N` atau `Y` (opsional opsional).
* **Redirect HTTP to HTTPS:** Jika muncul pilihan redirect, pilih opsi **`2: Redirect`** agar seluruh akses HTTP otomatis dialihkan secara aman ke HTTPS.


3. **Verifikasi Otomatisasi Konfigurasi:**
Buka kembali berkas konfigurasi Nginx untuk melihat perubahan otomatis yang dibuat oleh Certbot:
```bash
cat /etc/nginx/sites-available/secure_app.conf

```


*Certbot akan secara otomatis menambahkan blok `listen 443 ssl`, jalur sertifikat `ssl_certificate`, dan aturan pengalihan `return 301 https://$host$request_uri;`.*

---

### Bagian 4: Pengujian HTTPS & Pembaruan Otomatis (Auto-Renewal)

1. Buka browser laptop kamu dan akses domain menggunakan protokol HTTPS:
`https://domainkamu.com`
2. Klik **ikon gembok terkunci** di bilah alamat (*address bar*) browser kamu, lalu pilih **Connection is secure / Certificate is valid**.
3. **Amati rincian sertifikat:** Dipastikan penerbit (*Issuer*) adalah **Let's Encrypt Authority**.
4. **Uji Simulasi Auto-Renewal Certbot:**
Sertifikat Let's Encrypt berlaku selama 90 hari. Certbot menyediakan *cronjob/systemd timer* otomatis untuk memperbaruinya. Lakukan simulasi pembaruan (*dry-run*) untuk memastikan tidak ada kendala:
```bash
sudo certbot renew --dry-run

```


*Jika pesan output menunjukkan `Congratulations, all simulated renewals succeeded`, maka sistem keamanan HTTPS kamu telah berjalan sempurna!*

---

## 🎯 Tugas & Evaluasi Mandiri

1. **Skenario Praktikum Mandiri:**
* Siapkan satu subdomain baru (contoh: `secure.domainkamu.com`).
* Arahkan subdomain tersebut ke IP Server dan buatkan *Nginx Server Block* baru.
* Terbitkan sertifikat SSL/TLS Let's Encrypt khusus untuk subdomain tersebut menggunakan Certbot.
* Uji pengalihan otomatis dari `http://secure.domainkamu.com` agar dipaksa masuk ke `https://secure.domainkamu.com`.


2. **Laporan Praktikum (Screenshot):**
* Ambil *Screenshot* penuh yang menampilkan:
1. Tampilan browser saat mengakses `https://domainkamu.com` beserta **pop-up detail sertifikat SSL Let's Encrypt** yang aktif.
2. Output terminal dari perintah `sudo certbot renew --dry-run` yang berstatus *SUCCESS*.


* Simpan dengan format: **`Week10_[NIM]_[NamaMahasiswa].png`**.



---

## 🚨 Panduan Troubleshooting (Solusi Kendala)

| Masalah / Pesan Error | Kemungkinan Penyebab | Solusi Pengatasan |
| --- | --- | --- |
| **`Certbot failed to authenticate / Connection refused`** | Port 80/443 terblokir oleh Firewall cloud provider atau DNS A Record belum mengarah ke IP Server. | Pastikan aturan *Inbound Rules* Firewall membuka Port 80 & 443, serta cek propagasi IP domain dengan `dig A domainkamu.com` atau `nslookup`. |
| **`Too Many Requests / Rate Limit Exceeded`** | Terlalu banyak mencoba meminta sertifikat SSL untuk domain yang sama dalam waktu singkat. | Batas maksimal Let's Encrypt adalah 5 kali gagal per jam. Gunakan parameter `--test-cert` (staging mode) saat melakukan pengujian berulang kali. |
| **`The requested domain name was not found in Nginx configuration`** | Parameter `server_name` pada file konfigurasi Nginx di `/etc/nginx/sites-available/` tidak cocok dengan domain yang diketikkan di perintah Certbot. | Samakan nama domain di file konfigurasi Nginx terlebih dahulu, jalankan `sudo nginx -t`, baru eksekusi perintah `certbot`. |
