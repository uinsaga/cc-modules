# 🔀 Modul Week 09: Reverse Proxy & Load Balancing dengan Nginx

## 📌 Capaian Pembelajaran (Sub-CPMK 7.1.9)
Mahasiswa mampu memahami konsep **Reverse Proxy** dan **Load Balancing**, mengonfigurasi Nginx sebagai pintu masuk (*gateway*) aplikasi backend, serta mengimplementasikan algoritma pembagian beban kerja (*load balancing algorithms*) untuk meningkatkan ketersediaan (*availability*) dan skalabilitas sistem.

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
* Menguasai dasar konfigurasi Nginx Web Server (*materi Week 04*).
* Menguasai pengelolaan multi-kontainer via Docker Compose (*materi Week 07*).
* Memiliki akses SSH ke remote server Linux dengan hak akses `sudo`.

---

## 📚 Landasan Teori

### 1. Perbedaan Forward Proxy vs Reverse Proxy
* **Forward Proxy:** Bertindak atas nama **klien** (browser) untuk mengakses internet secara anonim atau melewati pembatasan jaringan.
* **Reverse Proxy:** Bertindak atas nama **server** backend. Klien berinteraksi dengan Reverse Proxy, lalu Reverse Proxy meneruskan permintaan (*request*) tersebut ke satu atau beberapa server aplikasi internal di belakangnya.

### 2. Apa itu Load Balancing?
**Load Balancing** adalah teknik mendistribusikan lalu lintas jaringan secara merata ke beberapa server aplikasi backend (*upstream servers*). Tujuannya adalah untuk mencegah penumpukan beban di satu server, meningkatkan waktu respon, dan menjaga ketersediaan layanan (*high availability*).

```text
                                        +-----------------------+
                                   +--> | App Server 1 (Node A) |
                                   |    +-----------------------+
Klien (Browser)     Reverse Proxy  |
+---------------+   +-----------+  |    +-----------------------+
|  HTTP Req     | ->|   Nginx   | -+--> | App Server 2 (Node B) |
| Port 80 / 443 |   |  Gateway  |  |    +-----------------------+
+---------------+   +-----------+  |
                                   |    +-----------------------+
                                   +--> | App Server 3 (Node C) |
                                        +-----------------------+

```

### 3. Algoritma Load Balancing pada Nginx

* **Round Robin (Default):** Distribusi permintaan secara berurutan ke setiap server backend.
* **Least Connections (`least_conn`):** Memprioritaskan server yang sedang memiliki jumlah koneksi aktif paling sedikit.
* **IP Hash (`ip_hash`):** Memastikan permintaan dari IP klien yang sama selalu diarahkan ke server backend yang sama (*session persistence*).
* **Weighted:** Memberikan bobot kapasitas lebih besar pada server spesifik (misal: `server node1:8080 weight=3;`).

---

## 🧪 Langkah-Langkah Praktikum (Hands-On)

### Bagian 1: Menyiapkan Multiple Node Application (Backend Servers)

Kita akan membuat 3 node aplikasi backend berbasis kontainer yang menampilkan identitas node masing-masing.

1. Login ke server via SSH:
```bash
ssh myserver

```


2. Buat direktori praktikum Week 09:
```bash
mkdir -p ~/load-balancer-demo
cd ~/load-balancer-demo

```


3. Buat berkas `compose.yaml` untuk menyalakan 3 instance backend web:
```bash
nano compose.yaml

```


4. Isikan konfigurasi berikut:
```yaml
services:
  app_node_1:
    image: nginxdemos/hello
    container_name: node_1
    ports:
      - "8081:80"

  app_node_2:
    image: nginxdemos/hello
    container_name: node_2
    ports:
      - "8082:80"

  app_node_3:
    image: nginxdemos/hello
    container_name: node_3
    ports:
      - "8083:80"

```


*(Simpan `Ctrl+O` -> `Enter`, keluar `Ctrl+X`).*
5. Jalankan ketiga node aplikasi:
```bash
docker compose up -d
docker compose ps

```



---

### Bagian 2: Konfigurasi Nginx sebagai Reverse Proxy & Load Balancer

1. Buat berkas konfigurasi Nginx kustom di server host:
```bash
sudo nano /etc/nginx/sites-available/load_balancer.conf

```


2. Tuliskan blok upstream dan server reverse proxy berikut:
```nginx
# 1. Definisi Kelompok Server Backend (Upstream Group)
upstream backend_servers {
    # Algoritma Default: Round Robin
    server 127.0.0.1:8081;
    server 127.0.0.1:8082;
    server 127.0.0.1:8083;
}

# 2. Definisi Reverse Proxy Server
server {
    listen 80;
    server_name _;

    location / {
        proxy_pass http://backend_servers;

        # Meneruskan Header Klien Asli ke Backend
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

```


3. Aktifkan konfigurasi dengan membuat *Symbolic Link*:
```bash
sudo ln -s /etc/nginx/sites-available/load_balancer.conf /etc/nginx/sites-enabled/

```


4. Hapus konfigurasi default Nginx agar tidak tumpang tindih (*conflict*):
```bash
sudo rm -f /etc/nginx/sites-enabled/default

```


5. Uji sintaks dan reload servis Nginx:
```bash
sudo nginx -t
sudo systemctl reload nginx

```



---

### Bagian 3: Pengujian Distribusi Beban (Round Robin)

1. Buka browser laptop kamu dan akses alamat IP server:
`http://IP_SERVER_KAMU`
2. Lakukan **Refresh** halaman browser beberapa kali secara berkala.
3. **Amati Perubahan:** Perhatikan bagian **Server Name / Server Address** atau **Server ID** pada tampilan web. Identitas server akan bergantian secara berurutan antara `node_1`, `node_2`, dan `node_3` (Mekanisme Round Robin).
4. Uji melalui terminal menggunakan perintah `curl` berulang:
```bash
for i in {1..6}; do curl -s http://localhost | grep -i "Server Name"; done

```



---

### Bagian 4: Penerapan Algoritma Failover & Least Connections

1. Edit berkas konfigurasi Nginx untuk menambahkan algoritma `least_conn`:
```bash
sudo nano /etc/nginx/sites-available/load_balancer.conf

```


2. Tambahkan arahan `least_conn;` di dalam blok `upstream`:
```nginx
upstream backend_servers {
    least_conn;
    server 127.0.0.1:8081;
    server 127.0.0.1:8082;
    server 127.0.0.1:8083;
}

```


3. Simpan dan reload Nginx:
```bash
sudo nginx -t && sudo systemctl reload nginx

```


4. **Pengujian High Availability (Failover):**
Matikan salah satu node backend (`node_2`):
```bash
docker stop node_2

```


5. Akses kembali `http://IP_SERVER_KAMU` via browser/curl. Nginx secara cerdas akan langsung memindahkan seluruh lalu lintas ke `node_1` dan `node_3` tanpa menimbulkan pesan error 502/504 pada pengguna!

---

## 🎯 Tugas & Evaluasi Mandiri

1. **Skenario Praktikum Mandiri:**
* Nyalakan kembali kontainer `node_2` (`docker start node_2`).
* Ubah algoritma Load Balancer menjadi **Weighted Load Balancing** dengan ketentuan:
* `node_1` menerima beban 3x lebih banyak (`weight=3`).
* `node_2` menerima beban standar (`weight=1`).
* `node_3` menerima beban standar (`weight=1`).


* Reload Nginx dan buktikan dengan eksekusi skrip `curl` berulang sebanyak 10 kali.


2. **Laporan Praktikum (Screenshot):**
* Ambil *Screenshot* penuh yang menampilkan:
1. Tampilan output terminal dari perintah perulangan `curl` yang membuktikan pembagian beban berimbang (*Weighted Load Balancing*).
2. Isi berkas `/etc/nginx/sites-available/load_balancer.conf` akhir.


* Simpan dengan format: **`Week09_[NIM]_[NamaMahasiswa].png`**.



---

## 🚨 Panduan Troubleshooting (Solusi Kendala)

| Masalah / Pesan Error | Kemungkinan Penyebab | Solusi Pengatasan |
| --- | --- | --- |
| **`502 Bad Gateway`** | Seluruh server backend di dalam blok `upstream` mati atau port tidak merespon. | Periksa status kontainer backend dengan `docker compose ps` dan pastikan port mapping (8081, 8082, 8083) benar. |
| **`403 Forbidden` atau Tampilan Nginx Default** | Konfigurasi default Nginx di `/etc/nginx/sites-enabled/default` belum dihapus. | Hapus symlink default dengan `sudo rm /etc/nginx/sites-enabled/default` lalu reload Nginx. |
| **Tampilan Web Tidak Berganti Saat Di-refresh** | Browser melakukan *caching* respons secara agresif. | Gunakan mode **Incognito / Private Browsing** atau gunakan pintasan `Ctrl + F5` untuk *hard refresh*. |
