# Modul Praktikum Week 09: Reverse Proxy & Load Balancing dengan Nginx (Tanpa Docker)

## 📌 Capaian Pembelajaran (Sub-CPMK 7.1.9)

Mahasiswa mampu memahami konsep *Reverse Proxy* dan *Load Balancing*, mengonfigurasi Nginx sebagai pintu masuk (*gateway*) aplikasi *backend*, serta mengimplementasikan seluruh varian algoritma pembagian beban kerja (*load balancing algorithms*) secara mandiri untuk meningkatkan ketersediaan (*availability*) dan skalabilitas sistem.

---

## 📑 Daftar Isi

1. [Prasyarat Perangkat & Sistem](https://www.google.com/search?q=%23-prasyarat-perangkat--sistem)
2. [Landasan Teori Komprehensif](https://www.google.com/search?q=%23-landasan-teori-komprehensif)
3. [Langkah-Langkah Praktikum (Hands-On)](https://www.google.com/search?q=%23-langkah-langkah-praktikum-hands-on)
    * [Bagian 1: Menyiapkan Multiple Node Application via Python](https://www.google.com/search?q=%23bagian-1-menyiapkan-multiple-node-application-via-python)
    * [Bagian 2: Konfigurasi Nginx sebagai Reverse Proxy & Load Balancer (Round Robin)](https://www.google.com/search?q=%23bagian-2-konfigurasi-nginx-sebagai-reverse-proxy--load-balancer-round-robin)
    * [Bagian 3: Implementasi Algoritma Least Connections (least_conn)](https://www.google.com/search?q=%23bagian-3-implementasi-algoritma-least-connections-least_conn)
    * [Bagian 4: Implementasi Algoritma IP Hash (ip_hash)](https://www.google.com/search?q=%23bagian-4-implementasi-algoritma-ip-hash-ip_hash)
    * [Bagian 5: Implementasi Weighted Load Balancing (Bobot Server)](https://www.google.com/search?q=%23bagian-5-implementasi-weighted-load-balancing-bobot-server)
    * [Bagian 6: Pengujian High Availability & Failover (Ketersediaan Tinggi)](https://www.google.com/search?q=%23bagian-6-pengujian-high-availability--failover-ketersediaan-tinggi)

4. [Tugas & Evaluasi Mandiri](https://www.google.com/search?q=%23-tugas--evaluasi-mandiri)
5. [Panduan Troubleshooting (Solusi Kendala)](https://www.google.com/search?q=%23-panduan-troubleshooting-solusi-kendala)

---

## 🛠️ Prasyarat Perangkat & Sistem

Sebelum memulai praktikum ini, pastikan hal-hal berikut terpenuhi:

* Menguasai dasar konfigurasi Nginx Web Server (materi sebelumnya).
* Menggunakan lingkungan Linux (Ubuntu/Debian) di dalam **VirtualBox**.
* Memiliki akses terminal atau SSH ke *virtual machine* dengan hak akses `sudo`.
* Python 3 terinstal di dalam sistem operasi VirtualBox (digunakan untuk menjalankan skrip klaster *backend* server tiruan).

---

## 📚 Landasan Teori Komprehensif

### 1. Arsitektur Web Modern dan Kebutuhan Skalabilitas

Pada aplikasi skala kecil, sebuah server web tunggal (monolitik) biasanya melayani seluruh permintaan klien secara langsung. Namun, seiring bertambahnya jumlah pengguna, server tunggal akan menghadapi kendala serius seperti:

* **Keterbatasan Sumber Daya (Resource Bottleneck):** Penggunaan CPU dan RAM yang tinggi akibat ribuan koneksi bersamaan (*concurrent connections*).
* **Single Point of Failure (SPOF):** Jika server utama down atau *crash*, seluruh layanan aplikasi tidak dapat diakses sama sekali.

Untuk mengatasi masalah ini, arsitektur *backend* dipecah menjadi beberapa *node* server aplikasi yang berjalan paralel, dibungkus di belakang sebuah gerbang utama (*gateway*). Di sinilah peran **Reverse Proxy** dan **Load Balancer** menjadi sangat krusial.

---

### 2. Perbedaan Forward Proxy vs. Reverse Proxy

Secara konseptual, *proxy* adalah perantara antara klien dan internet. Namun, arah dan tujuannya terbagi menjadi dua jenis utama:

* **Forward Proxy:**
* **Posisi:** Berada di dekat klien (atau di dalam jaringan lokal klien).
* **Fungsi Utama:** Bertindak atas nama klien untuk mengakses internet. Sering digunakan untuk menyembunyikan identitas/IP asli klien, mem-bypass pemblokiran jaringan, atau memfilter konten web perusahaan/kampus.
* **Ilustrasi:** Klien -> Forward Proxy -> Internet (Server Tujuan).


* **Reverse Proxy:**
* **Posisi:** Berada di depan satu atau beberapa server aplikasi *backend*.
* **Fungsi Utama:** Bertindak atas nama server *backend*. Klien tidak pernah tahu atau berkomunikasi langsung dengan server aplikasi internal; klien hanya berbicara dengan *Reverse Proxy*. *Reverse Proxy* kemudian meneruskan (*forward*) permintaan tersebut ke server internal yang tepat.
* **Keunggulan:** Menyediakan keamanan tambahan (menyembunyikan topologi jaringan internal), melakukan *SSL termination*, *caching*, dan *compression*.
* **Ilustrasi:** Klien -> Reverse Proxy (Nginx) -> Server Backend 1 / 2 / 3.



---

### 3. Apa itu Load Balancing?

**Load Balancing** adalah teknik mendistribusikan lalu lintas jaringan secara merata ke beberapa server aplikasi *backend* (disebut sebagai *upstream servers*).

```text
                                        +--------------------------------+
                                   +--> | App Server 1 (Python Port 8081)|
                                   |    +--------------------------------+
Klien (Browser)     Reverse Proxy  |
+---------------+   +-----------+  |    +--------------------------------+
|  HTTP Req     | ->|   Nginx   | -+--> | App Server 2 (Python Port 8082)|
| Port 80 / 443 |   |  Gateway  |  |    +--------------------------------+
+---------------+   +-----------+  |
                                   |    +--------------------------------+
                                   +--> | App Server 3 (Python Port 8083)|
                                        +--------------------------------+

```

**Manfaat Utama Load Balancing:**

1. **High Availability (Ketersediaan Tinggi):** Jika salah satu server *backend* mati, *load balancer* otomatis mengalihkan trafik ke server lain yang masih hidup (*Failover*).
2. **Scalability (Skalabilitas Horisontal):** Memungkinkan penambahan server baru (*scale-out*) dengan mudah tanpa mematikan layanan utama.
3. **Optimasi Performa:** Mencegah terjadinya penumpukan beban (*overload*) pada satu server spesifik.

---

### 4. Algoritma Load Balancing pada Nginx

Nginx menyediakan berbagai algoritma bawaan untuk mengatur cara pembagian beban ke server *backend*. Berikut adalah penjelasan lengkap beserta **contoh konfigurasi** dan **ilustrasi visualnya**:

#### A. Round Robin (Default)

* **Konsep & Penjelasan:**
Permintaan didistribusikan secara berurutan dari server pertama hingga terakhir secara berputar. Algoritma ini digunakan secara otomatis oleh Nginx jika tidak ada arahan algoritma khusus yang dituliskan.
* **Ilustrasi Visual:**
```text
Request 1 ──> [ Nginx ] ──> Node 1 (Port 8081)
Request 2 ──> [ Nginx ] ──> Node 2 (Port 8082)
Request 3 ──> [ Nginx ] ──> Node 3 (Port 8083)
Request 4 ──> [ Nginx ] ──> Node 1 (Port 8081)  <-- [Kembali berputar ke awal]

```


* **Contoh Konfigurasi pada Nginx:**
```nginx
upstream backend_servers {
    server 127.0.0.1:8081;
    server 127.0.0.1:8082;
    server 127.0.0.1:8083;
}

```



---

#### B. Least Connections (`least_conn`)

* **Konsep & Penjelasan:**
Permintaan diarahkan ke server yang saat ini sedang memegang jumlah koneksi aktif paling sedikit. Sangat cocok digunakan apabila proses di server *backend* memiliki durasi waktu eksekusi yang bervariasi atau berat.
* **Ilustrasi Visual:**
```text
                                           +---------------------------+
                                      +--> | Node 1 (0 Koneksi Aktif)  | [Terpilih!]
                                      |    +---------------------------+
[ Klien Baru ] ──> Nginx (least_conn) ---> Node 2 (3 Koneksi Aktif)  [Sibuk]
                                      |    +---------------------------+
                                      +--> | Node 3 (1 Koneksi Aktif)  |
                                           +---------------------------+

```


* **Contoh Konfigurasi pada Nginx:**
```nginx
upstream backend_servers {
    least_conn;
    server 127.0.0.1:8081;
    server 127.0.0.1:8082;
    server 127.0.0.1:8083;
}

```



---

#### C. IP Hash (`ip_hash`)

* **Konsep & Penjelasan:**
Memastikan bahwa permintaan dari alamat IP klien yang sama akan selalu diarahkan ke server *backend* yang sama persis (*Session Persistence*). Hal ini berguna jika aplikasi web Anda menyimpan data sesi login secara lokal di memori server tersebut.
* **Ilustrasi Visual:**
```text
IP Klien A (192.168.1.10) ──> [ Nginx (ip_hash) ] ──> Selalu konsisten ke Node 1
IP Klien B (192.168.1.15) ──> [ Nginx (ip_hash) ] ──> Selalu konsisten ke Node 2

```


* **Contoh Konfigurasi pada Nginx:**
```nginx
upstream backend_servers {
    ip_hash;
    server 127.0.0.1:8081;
    server 127.0.0.1:8082;
    server 127.0.0.1:8083;
}

```



---

#### D. Weighted (Bobot)

* **Konsep & Penjelasan:**
Mengalokasikan proporsi beban yang berbeda berdasarkan kapasitas spesifik hardware server (misalnya server utama yang kuat diberi bobot lebih besar dibanding server cadangan).
* **Ilustrasi Visual (Distribusi untuk 5 Request dengan konfigurasi weight 3:1:1):**
```text
Request 1 ──> Node 1 (weight=3)
Request 2 ──> Node 1 (weight=3)
Request 3 ──> Node 1 (weight=3)
Request 4 ──> Node 2 (weight=1)
Request 5 ──> Node 3 (weight=1)

```


* **Contoh Konfigurasi pada Nginx:**
```nginx
upstream backend_servers {
    server 127.0.0.1:8081 weight=3;
    server 127.0.0.1:8082 weight=1;
    server 127.0.0.1:8083 weight=1;
}

```



---

## 🧪 Langkah-Langkah Praktikum (Hands-On)

### Bagian 1: Menyiapkan Multiple Node Application via Python

Pada bagian ini, kita akan menggunakan skrip otomatisasi Python untuk menyalakan 3 server aplikasi *backend* secara bersamaan (*multithreading*) di port `8081`, `8082`, dan `8083`.

1. **Masuk ke terminal VirtualBox (atau melalui SSH):**
```bash
ssh username@ip_virtualbox

```


2. **Buat direktori kerja praktikum:**
```bash
mkdir -p ~/load-balancer-demo
cd ~/load-balancer-demo

```


3. **Buat berkas skrip Python `cluster_backend.py`:**
```bash
nano cluster_backend.py

```


4. **Isikan kode program berikut ke dalam berkas tersebut:**
```python
import http.server
import socketserver
import threading

# Konfigurasi port dan pesan identitas untuk masing-masing node backend
nodes = [
    {"port": 8081, "message": "<h1>Halo dari Node Backend 1 (Port 8081)</h1>"},
    {"port": 8082, "message": "<h1>Halo dari Node Backend 2 (Port 8082)</h1>"},
    {"port": 8083, "message": "<h1>Halo dari Node Backend 3 (Port 8083)</h1>"}
]

def run_server(port, message):
    class CustomHandler(http.server.SimpleHTTPRequestHandler):
        def do_GET(self):
            self.send_response(200)
            self.send_header("Content-type", "text/html")
            self.end_headers()
            self.wfile.write(message.encode("utf-8"))

    with socketserver.TCPServer(("127.0.0.1", port), CustomHandler) as httpd:
        print(f"[*] Backend Node berjalan di http://127.0.0.1:{port}")
        httpd.serve_forever()

if __name__ == "__main__":
    print("[*] Memulai Klaster Backend Server...")
    threads = []

    for node in nodes:
        t = threading.Thread(target=run_server, args=(node["port"], node["message"]))
        t.daemon = True
        threads.append(t)
        t.start()

    print("[*] Seluruh node backend berhasil aktif. Tekan Ctrl+C untuk berhenti.")
    try:
        while True:
            pass
    except KeyboardInterrupt:
        print("\n[*] Menghentikan seluruh node backend...")

```


*(Simpan dengan `Ctrl + O`, `Enter`, lalu keluar dengan `Ctrl + X`)*
5. **Jalankan skrip klaster backend tersebut di latar belakang atau biarkan aktif di tab terminal terpisah:**
```bash
python3 cluster_backend.py &

```


6. **Uji konektivitas lokal ke masing-masing port backend:**
```bash
curl http://127.0.0.1:8081
curl http://127.0.0.1:8082
curl http://127.0.0.1:8083

```



---

### Bagian 2: Konfigurasi Nginx sebagai Reverse Proxy & Load Balancer (Round Robin)

Pada bagian ini, kita akan mengonfigurasi Nginx agar bertindak sebagai *Reverse Proxy* sekaligus *Load Balancer* menggunakan algoritma bawaan (*Round Robin*).

1. **Buat berkas konfigurasi virtual host baru untuk Nginx:**
```bash
sudo nano /etc/nginx/sites-available/load_balancer.conf

```


2. **Tuliskan blok konfigurasi `upstream` dan `server` berikut secara lengkap:**
```nginx
# 1. Definisi Kelompok Server Backend (Upstream Group)
upstream backend_servers {
    # Menggunakan Algoritma Default: Round Robin
    server 127.0.0.1:8081;
    server 127.0.0.1:8082;
    server 127.0.0.1:8083;
}

# 2. Definisi Konfigurasi Reverse Proxy Server pada Port 80
server {
    listen 80;
    server_name _;

    location / {
        proxy_pass http://backend_servers;

        # Meneruskan Header Klien Asli ke Server Backend
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

```


*(Simpan berkas dengan menekan `Ctrl + O`, lalu `Enter`, dan keluar dengan `Ctrl + X`)*
3. **Aktifkan konfigurasi dengan membuat Symbolic Link ke direktori `sites-enabled`:**
```bash
sudo ln -s /etc/nginx/sites-available/load_balancer.conf /etc/nginx/sites-enabled/

```


4. **Hapus konfigurasi default Nginx agar tidak terjadi konflik port 80:**
```bash
sudo rm -f /etc/nginx/sites-enabled/default

```


5. **Uji validitas sintaks konfigurasi dan terapkan perubahan pada Nginx:**
```bash
sudo nginx -t
sudo systemctl reload nginx

```


6. **Pengujian Fungsionalitas Round Robin:**
Jalankan perintah perulangan `curl` di terminal untuk melihat rotasi pembagian beban:
```bash
for i in {1..6}; do curl -s http://localhost; echo ""; done

```


* **Hasil yang diharapkan:** Output terminal akan menampilkan urutan teks secara bergantian: Node 1 -> Node 2 -> Node 3 -> Node 1 -> Node 2 -> Node 3.



---

### Bagian 3: Implementasi Algoritma Least Connections (`least_conn`)

Praktikum ini bertujuan untuk menerapkan algoritma *Least Connections* agar Nginx mengarahkan trafik ke node yang memiliki koneksi aktif paling sedikit.

1. **Buka berkas konfigurasi Nginx yang telah dibuat:**
```bash
sudo nano /etc/nginx/sites-available/load_balancer.conf

```


2. **Tambahkan arahan `least_conn;` di dalam blok `upstream`:**
```nginx
upstream backend_servers {
    least_conn; # <--- Tambahkan baris ini di baris pertama blok upstream
    server 127.0.0.1:8081;
    server 127.0.0.1:8082;
    server 127.0.0.1:8083;
}

server {
    listen 80;
    server_name _;

    location / {
        proxy_pass http://backend_servers;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

```


3. **Uji sintaks dan muat ulang servis Nginx:**
```bash
sudo nginx -t && sudo systemctl reload nginx

```


4. **Uji kembali melalui terminal:**
```bash
for i in {1..6}; do curl -s http://localhost; echo ""; done

```



---

### Bagian 4: Implementasi Algoritma IP Hash (`ip_hash`)

Praktikum ini bertujuan mengaktifkan *session persistence* menggunakan *IP Hash* agar akses dari klien yang sama selalu diarahkan ke server *backend* yang sama.

1. **Buka kembali berkas konfigurasi Nginx:**
```bash
sudo nano /etc/nginx/sites-available/load_balancer.conf

```


2. **Ganti baris `least_conn;` menjadi `ip_hash;` di dalam blok `upstream`:**
```nginx
upstream backend_servers {
    ip_hash; # <--- Mengaktifkan IP Hash
    server 127.0.0.1:8081;
    server 127.0.0.1:8082;
    server 127.0.0.1:8083;
}

server {
    listen 80;
    server_name _;

    location / {
        proxy_pass http://backend_servers;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

```


3. **Uji sintaks dan muat ulang servis Nginx:**
```bash
sudo nginx -t && sudo systemctl reload nginx

```


4. **Uji hasil konfigurasi:**
Jalankan perintah `curl` berulang kali dari terminal lokal yang sama:
```bash
for i in {1..5}; do curl -s http://localhost; echo ""; done

```


* **Hasil yang diharapkan:** Karena IP asal klien sama (`127.0.0.1`), output terminal akan terus menerus menampilkan node yang sama persis (misalnya Node 1 terus-menerus), membuktikan fungsi *session persistence* berjalan.



---

### Bagian 5: Implementasi Weighted Load Balancing (Bobot Server)

Praktikum ini bertujuan mengatur proporsi distribusi beban secara kustom berdasarkan kapasitas masing-masing server menggunakan parameter `weight`.

1. **Buka berkas konfigurasi Nginx:**
```bash
sudo nano /etc/nginx/sites-available/load_balancer.conf

```


2. **Sesuaikan parameter server di dalam blok `upstream` dengan menambahkan atribut `weight`:**
```nginx
upstream backend_servers {
    # Hapus atau beri komentar pada baris ip_hash sebelumnya
    server 127.0.0.1:8081 weight=3; # Node 1 menerima beban 3x lipat
    server 127.0.0.1:8082 weight=1; # Node 2 standar
    server 127.0.0.1:8083 weight=1; # Node 3 standar
}

server {
    listen 80;
    server_name _;

    location / {
        proxy_pass http://backend_servers;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

```


3. **Uji sintaks dan muat ulang servis Nginx:**
```bash
sudo nginx -t && sudo systemctl reload nginx

```


4. **Uji pembagian beban dengan melakukan 10 kali perulangan `curl`:**
```bash
for i in {1..10}; do curl -s http://localhost; echo ""; done

```


* **Hasil yang diharapkan:** Frekuensi kemunculan *Node 1* akan mendominasi dibandingkan *Node 2* dan *Node 3*.



---

### Bagian 6: Pengujian High Availability & Failover (Ketersediaan Tinggi)

Praktikum ini menguji keandalan sistem klaster di mana Nginx secara otomatis melakukan *bypass* pada server *backend* yang mati tanpa mengganggu pengalaman pengguna.

1. **Matikan skrip Python klaster backend** yang sedang berjalan (tekan `Ctrl + C` pada terminal tempat `cluster_backend.py` dieksekusi, atau temukan PID prosesnya lalu matikan).
2. **Jalankan ulang skrip Python, tetapi kita matikan salah satu port (misal port 8082 dimatikan)** atau Anda bisa menghentikan proses spesifik jika dijalankan terpisah. Cara paling mudah dengan skrip Python di atas adalah menghentikan skrip, lalu sesuaikan kode atau jalankan ulang tanpa port 8082, atau matikan proses Python spesifik port 8082 menggunakan:
```bash
# Cari dan matikan proses python yang memegang port 8082
fuser -k 8082/tcp

```


3. **Lakukan pengujian permintaan melalui perulangan `curl` di terminal:**
```bash
for i in {1..6}; do curl -s http://localhost; echo ""; done

```


4. **Analisis Hasil Praktikum:**
* **Hasil:** Meskipun Node 2 telah dimatikan, perintah `curl` tetap berhasil merespon tanpa menghasilkan pesan error *502 Bad Gateway*. Trafik secara otomatis dialihkan dan dibagi hanya ke **Node 1** dan **Node 3**. Hal ini membuktikan bahwa mekanisme *High Availability* dan *Failover* pada Nginx berjalan dengan sukses.



---

## 🎯 Tugas & Evaluasi Mandiri

### Skenario Tugas Mandiri:

1. Pastikan seluruh node backend aktif kembali (nyalakan ulang `cluster_backend.py`).
2. Buat skenario konfigurasi baru di dalam `load_balancer.conf` di mana:
* `node_1` (port 8081) memiliki `weight=2`
* `node_2` (port 8082) memiliki `weight=2`
* `node_3` (port 8083) memiliki `weight=1`


3. Terapkan konfigurasi tersebut, lakukan *reload* Nginx, lalu jalankan perulangan `curl` sebanyak 10 kali.

### Laporan Praktikum (Screenshot):

Kumpulkan dokumen laporan berformat PDF dengan penamaan format `Week09_[NIM]_[NamaMahasiswa].pdf` yang memuat:

1. Tangkapan layar (*Screenshot*) output terminal dari perulangan `curl` sebanyak 10 kali yang membuktikan pembagian beban *Weighted* sesuai ketentuan tugas mandiri.
2. Tangkapan layar isi berkas konfigurasi `/etc/nginx/sites-available/load_balancer.conf` secara keseluruhan.

---

## 🚨 Panduan Troubleshooting (Solusi Kendala)

| Masalah / Pesan Error | Kemungkinan Penyebab | Solusi Pengatasan |
| --- | --- | --- |
| **502 Bad Gateway** | Seluruh server backend di dalam blok *upstream* mati atau port tidak merespon. | Periksa proses background server Python dengan perintah `ps aux | grep python3` dan pastikan port 8081, 8082, 8083 aktif. |
| **403 Forbidden atau Tampilan Nginx Default** | Konfigurasi bawaan Nginx di `/etc/nginx/sites-enabled/default` belum terhapus atau symlink salah. | Hapus symlink default menggunakan `sudo rm /etc/nginx/sites-enabled/default`, lalu jalankan `sudo systemctl reload nginx`. |
| **Tampilan Web Tidak Berganti Saat Di-refresh** | Browser melakukan *caching* halaman secara agresif. | Gunakan mode *Incognito / Private Browsing* pada browser Anda atau lakukan *Hard Refresh* (`Ctrl + F5`). |
