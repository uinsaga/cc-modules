# 🐳 Modul Week 12 (Tahap 2): Docker Compose — Implementasi Real-World Multi-Service & Load Balancing

## 📌 Capaian Pembelajaran (Sub-CPMK 7.1.11b)
Mahasiswa mampu mengorkestrasi arsitektur web multi-service menggunakan **Docker Compose**, menerapkan **service discovery**, **environment variables**, **healthcheck**, **volume persistence**, dan **scaling replika** untuk membangun kembali arsitektur **load-balanced LEMP stack** (Nginx + PHP-FPM + MySQL) — yang sebelumnya dipelajari secara manual di Week 09 dan Week 10 — dalam satu file konfigurasi.

> 📌 **Catatan tahapan:** Modul ini adalah **Tahap 2 dari 2**. Pada **Tahap 1 (Week 11)** Anda telah menguasai instalasi Docker, container lifecycle, port mapping, volume, dan Dockerfile. Modul ini melanjutkan dengan orkestrasi multi-container.

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
- Telah menyelesaikan **Tahap 1 (Week 11)** — Docker Engine terpasang dan berjalan (`docker run --rm hello-world` berhasil).
- Memahami perintah `docker run`, `docker build`, `docker ps`, `docker logs`, `docker exec`, dan konsep **volume** serta **bind mount**.
- Memahami konfigurasi **Nginx sebagai reverse proxy** dengan `proxy_pass`, `upstream`, dan `fastcgi_pass` (Week 09).
- RAM host minimal **8 GB** (Docker Desktop memakan 2–3 GB, MySQL dan PHP-FPM memakan ~500 MB).
- Ruang disk kosong minimal **5 GB** untuk image MySQL, Nginx, dan PHP-FPM.
- Koneksi internet aktif untuk mengunduh image baru (`mysql:8.0`, `php:8.2-fpm-alpine`).

> ⚠️ **Penting:** Modul ini menggunakan **Docker Compose v2** (perintah `docker compose` dengan spasi). Jika di Tahap 1 Anda memakai Linux dan menginstal paket `docker-compose-plugin`, perintah ini sudah tersedia. Jika Anda memakai Windows/macOS, Docker Desktop versi terbaru sudah menyertakannya. Verifikasi dengan `docker compose version` — **harus v2.x.x**.

---

## 📚 Landasan Teori

### 1. Masalah yang Diselesaikan Docker Compose

Di Tahap 1 Anda menjalankan satu container per perintah `docker run`. Bayangkan harus menjalankan **tiga container sekaligus** yang saling terhubung:

```bash
docker network create lemp-net
docker run -d --name mysql --network lemp-net -v db_data:/var/lib/mysql mysql:8.0
docker run -d --name php --network lemp-net --link mysql php:fpm
docker run -d --name nginx --network lemp-net -p 8080:80 --link php nginx:alpine
```

Tiga masalah muncul:

1. **Panjang dan rawan typo** — satu argumen salah, container tidak saling terhubung.
2. **Tidak reproducible** — tidak ada catatan versi image, environment variable, dan volume dalam satu file yang bisa dibagikan ke rekan tim.
3. **Tidak scalable** — untuk menambah replika PHP-FPM, harus mengulang `docker run` dengan nama berbeda.

**Docker Compose** menjawab ketiganya dengan satu file YAML.

### 2. Anatomi `docker-compose.yml`

Struktur minimum file Compose v2:

```yaml
services:           # deklarasi semua container
  web:
    image: nginx
  app:
    build: ./app

volumes:            # deklarasi volume (opsional)
  data:

networks:           # deklarasi network (opsional)
  app-net:
```

Setiap **key** di bawah `services:` adalah **nama service**. Compose otomatis membuat:
- Satu **bridge network** dengan nama `<project>_default`.
- **DNS internal** yang me-resolve nama service menjadi IP container.
- **Container** dengan nama `<project>-<service>-<index>`.

Jika folder proyek Anda bernama `lemp`, maka service `php` akan diberi nama container `lemp-php-1`.

### 3. Service Discovery — DNS Internal Compose

Ini adalah fitur paling penting yang membedakan Compose dari `docker run` manual.

**Tanpa Compose**, container `nginx` yang ingin menghubungi container `php` harus menggunakan **IP internal** (mis. `172.18.0.3:9000`) — yang bisa berubah setiap kali container dibuat ulang.

**Dengan Compose**, container `nginx` cukup menghubungi **`php:9000`** — nama service diresolusi oleh Docker DNS menjadi IP yang benar, dan otomatis diperbarui jika container `php` di-restart.

```text
[ nginx ] --(fastcgi_pass php:9000)--> [ Docker DNS ] --resolve--> [ php container ]
```

Inilah sebabnya konfigurasi Nginx di Compose **tidak** memakai `127.0.0.1` seperti di Week 09, melainkan nama service.

### 4. `.env` File dan Environment Variables

**Jangan hardcode password** di `docker-compose.yml`. Compose secara otomatis membaca file `.env` di folder yang sama dan mengganti variabel `${VAR}` dengan nilainya.

```env
MYSQL_ROOT_PASSWORD=rootpass
MYSQL_USER=appuser
MYSQL_PASSWORD=apppass
```

```yaml
services:
  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
```

**Aturan:**
- `.env` **tidak boleh** di-commit ke Git (masukkan ke `.gitignore`).
- Sertakan `.env.example` (tanpa nilai asli) sebagai template untuk rekan tim.

### 5. `depends_on` dan `healthcheck`

`depends_on` menentukan **urutan startup**, tetapi **tidak menunggu container benar-benar siap**. Container MySQL butuh 10–30 detik untuk benar-benar menerima koneksi. Karena itu, `depends_on` perlu dipadukan dengan **healthcheck**.

```yaml
services:
  app:
    depends_on:
      db:
        condition: service_healthy    # tunggu sampai db benar-benar sehat

  db:
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5
```

Dengan kombinasi ini, container `app` **baru akan dijalankan setelah MySQL benar-benar menerima koneksi**.

### 6. Scaling Replika

Docker Compose mendukung penambahan replika per service dengan satu perintah:

```bash
docker compose up -d --scale php=2
```

Container `php` akan menjadi dua: `<project>-php-1` dan `<project>-php-2`. Docker DNS untuk service `php` akan me-resolve ke **dua IP** sekaligus.

**Catatan penting:**
- Service yang di-scale **tidak boleh** memiliki `container_name:` fixed.
- Service yang di-scale **tidak boleh** memakai host port (`ports:`) — karena dua container tidak bisa bind ke port host yang sama. Karena itu `php` dan `db` **tidak boleh** ada `ports:`, hanya `nginx` yang boleh.
- Jika `php` perlu diakses dari luar, gunakan **load balancer** di depannya (inilah peran `nginx`).

### 7. Nginx sebagai Load Balancer di Depan Replika

Nginx me-resolve nama service `php` ke **semua IP replika** saat startup, lalu mendistribusikan request secara **round-robin**:

```nginx
upstream php_backend {
    server php:9000;    # DNS akan resolve ke semua replika php
}

location ~ \.php$ {
    fastcgi_pass php_backend;
}
```

> ⚠️ **Catatan:** Nginx me-resolve DNS **hanya saat startup**. Jika Anda menambah replika setelah Nginx jalan, Nginx tidak akan mengenali replika baru sampai di-restart. Karena itu, urutan yang benar saat scaling: `docker compose up -d --scale php=2` → `docker compose restart nginx`.

### 8. Rekap Perbedaan Compose vs Manual

| Aspek | `docker run` (Tahap 1) | Docker Compose (Tahap 2) |
| :--- | :--- | :--- |
| Deklarasi | Perintah panjang di terminal | Satu file YAML |
| Koneksi antar container | Manual `--link` atau IP statis | Otomatis via nama service |
| Reproducibility | Harus hafal urutan perintah | Cukup bagikan `docker-compose.yml` |
| Scaling | Replikasi manual | `--scale service=N` |
| Konfigurasi (env, volume) | Argumen CLI | Deklaratif di YAML |
| Rebuild | `docker build` + `docker run` | `docker compose up --build` |

---

## 🧪 Langkah-Langkah Praktikum (Hands-On)

### Bagian 1: Persiapan Proyek

1. **Buat struktur folder proyek:**

   ```bash
   mkdir -p ~/docker-lab/lemp/nginx
   mkdir -p ~/docker-lab/lemp/php/src
   mkdir -p ~/docker-lab/lemp/mysql/init
   cd ~/docker-lab/lemp
   ```

2. **Buat file `.gitignore`** (jika proyek akan di-commit ke Git):

   ```text
   .env
   *.log
   ```

3. **Buat file `.env`** — menyimpan semua kredensial:

   ```env
   MYSQL_ROOT_PASSWORD=rootpass
   MYSQL_DATABASE=appdb
   MYSQL_USER=appuser
   MYSQL_PASSWORD=apppass
   ```

4. **Buat file `.env.example`** — template tanpa nilai asli, untuk dibagikan:

   ```env
   MYSQL_ROOT_PASSWORD=changeme
   MYSQL_DATABASE=appdb
   MYSQL_USER=changeme
   MYSQL_PASSWORD=changeme
   ```

> 📌 **Refleksi:** Perhatikan bahwa `.env` menyimpan **rahasia**, sedangkan `.env.example` hanya template. Ini adalah praktik standar industri agar rahasia tidak bocor ke repository publik.

---

### Bagian 2: Compose Pertama — Nginx Sederhana

Sebelum ke arsitektur kompleks, mulai dari yang sederhana: **satu service saja** untuk memahami perintah Compose.

1. **Buat file `docker-compose.yml`:**

   ```yaml
   services:
     web:
       image: nginx:alpine
       ports:
         - "8080:80"
   ```

2. **Jalankan:**

   ```bash
   docker compose up -d
   ```

3. **Cek status:**

   ```bash
   docker compose ps
   ```

   Output akan menampilkan satu service `web` dengan nama container `<folder>-web-1`.

4. **Akses di browser:** `http://localhost:8080` → *"Welcome to nginx!"*.

5. **Lihat log:**

   ```bash
   docker compose logs -f web
   ```

   Tekan `Ctrl+C` untuk keluar dari log (container tetap jalan).

6. **Hentikan dan hapus:**

   ```bash
   docker compose down
   ```

> 📌 **Refleksi:** Bandingkan dengan `docker run -d --name web -p 8080:80 nginx:alpine` dari Tahap 1. Compose menyimpan parameter di file, sehingga bisa di-commit ke Git dan dijalankan ulang di mesin lain dengan perintah yang **sama persis**.

---

### Bagian 3: Multi-Service — Nginx + PHP-FPM + MySQL

Sekarang bangun arsitektur LEMP penuh. Ini adalah fondasi yang sama dengan Week 09, tetapi dijalankan via Compose.

#### 3.1 Buat Dockerfile PHP-FPM

1. **Buat file `php/Dockerfile`:**

   ```dockerfile
   FROM php:8.2-fpm-alpine

   RUN docker-php-ext-install mysqli pdo pdo_mysql

   WORKDIR /var/www/html
   ```

   Base image `php:8.2-fpm-alpine` sudah menyertakan PHP-FPM. Kita menambahkan ekstensi MySQL agar PHP bisa terhubung ke database.

2. **Build image** untuk memastikan Dockerfile valid:

   ```bash
   cd ~/docker-lab/lemp
   docker build -t myapp-php:v1 ./php
   ```

   Image ini nantinya akan dipakai oleh Compose (Compose akan mem-build ulang jika perlu).

#### 3.2 Buat Aplikasi PHP

1. **Buat file `php/src/index.php`:**

   ```php
   <?php
   $host = getenv('DB_HOST') ?: 'mysql';
   $user = getenv('DB_USER') ?: 'appuser';
   $pass = getenv('DB_PASS') ?: 'apppass';
   $name = getenv('DB_NAME') ?: 'appdb';

   $error = null;
   try {
       $pdo = new PDO(
           "mysql:host=$host;dbname=$name;charset=utf8mb4",
           $user, $pass,
           [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
       );

       // Catat kunjungan (hostname = nama container php yang melayani)
       $pdo->exec("INSERT INTO visits (hostname, visited_at) VALUES ('" . gethostname() . "', NOW())");

       $version = $pdo->query("SELECT VERSION()")->fetchColumn();
       $count   = $pdo->query("SELECT COUNT(*) FROM visits")->fetchColumn();
       $recent  = $pdo->query("SELECT hostname, visited_at FROM visits ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
   } catch (Exception $e) {
       $error = $e->getMessage();
   }
   ?>
   <!DOCTYPE html>
   <html lang="id">
   <head>
     <meta charset="UTF-8">
     <title>KEKOAMANA LEMP Stack</title>
     <style>
       body { font-family: sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; background: #1f3d2b; color: #f0f0f0; }
       h1 { color: #e8c878; }
       .card { background: #2e5a3e; padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1rem; }
       code { background: #1a2b1f; padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.95em; }
       .error { background: #6b1a1a; padding: 1rem; border-radius: 8px; }
       table { width: 100%; border-collapse: collapse; }
       th, td { text-align: left; padding: 0.4rem 0.6rem; border-bottom: 1px solid #3b6b4a; }
     </style>
   </head>
   <body>
     <h1>Halo dari Container!</h1>
     <?php if ($error): ?>
       <div class="error"><strong>Koneksi DB Gagal:</strong><pre><?= htmlspecialchars($error) ?></pre></div>
     <?php else: ?>
       <div class="card">
         <p>PHP-FPM container: <code><?= htmlspecialchars(gethostname()) ?></code></p>
         <p>MySQL version: <code><?= htmlspecialchars($version) ?></code></p>
         <p>Total kunjungan: <strong><?= htmlspecialchars($count) ?></strong></p>
       </div>
       <div class="card">
         <p><strong>5 kunjungan terakhir:</strong></p>
         <table>
           <tr><th>PHP-FPM Container</th><th>Waktu</th></tr>
           <?php foreach ($recent as $row): ?>
             <tr><td><code><?= htmlspecialchars($row['hostname']) ?></code></td><td><?= htmlspecialchars($row['visited_at']) ?></td></tr>
           <?php endforeach; ?>
         </table>
         <p style="margin-top:1rem;font-size:0.9em;opacity:0.8;">
           Refresh halaman beberapa kali. Jika kolom <em>PHP-FPM Container</em> bergantian,
           berarti <strong>load balancing bekerja</strong>.
         </p>
       </div>
     <?php endif; ?>
   </body>
   </html>
   ```

   Aplikasi ini akan mencatat setiap kunjungan, **menyimpan hostname container PHP-FPM yang melayani** — sehingga saat kita scale, kita bisa melihat replika mana yang melayani request.

#### 3.3 Buat Skema Database

1. **Buat file `mysql/init/01-schema.sql`:**

   ```sql
   CREATE TABLE IF NOT EXISTS visits (
       id INT AUTO_INCREMENT PRIMARY KEY,
       hostname VARCHAR(100) NOT NULL,
       visited_at DATETIME NOT NULL,
       INDEX idx_visited_at (visited_at)
   );
   ```

   File di folder `mysql/init/` akan **otomatis dijalankan** oleh container MySQL saat **pertama kali** inisialisasi. Ini adalah mekanisme resmi image `mysql` untuk seeding data awal.

2. **Catatan penting:** File SQL ini **hanya dijalankan sekali**, saat volume `db_data` belum ada. Jika Anda mengubah file ini setelah volume dibuat, perubahan **tidak akan diterapkan**. Untuk menerapkan ulang, harus `docker compose down -v` (hapus volume).

#### 3.4 Buat Konfigurasi Nginx

1. **Buat file `nginx/default.conf`:**

   ```nginx
   upstream php_backend {
       server php:9000;
   }

   server {
       listen 80;
       server_name _;

       root /var/www/html;
       index index.php index.html;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.php$ {
           fastcgi_pass php_backend;
           fastcgi_index index.php;
           include fastcgi_params;
           fastcgi_param SCRIPT_FILENAME /var/www/html$fastcgi_script_name;
       }
   }
   ```

   Perhatikan baris **`server php:9000;`** — `php` adalah nama service di Compose, bukan IP. Inilah **service discovery** yang disebut di landasan teori.

#### 3.5 Rangkai Semua di `docker-compose.yml`

1. **Ganti isi `docker-compose.yml`** dengan:

   ```yaml
   services:
     nginx:
       image: nginx:alpine
       ports:
         - "8080:80"
       volumes:
         - ./nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
         - ./php/src:/var/www/html:ro
       depends_on:
         - php
       networks:
         - lemp-net

     php:
       build: ./php
       environment:
         DB_HOST: mysql
         DB_NAME: ${MYSQL_DATABASE}
         DB_USER: ${MYSQL_USER}
         DB_PASS: ${MYSQL_PASSWORD}
       volumes:
         - ./php/src:/var/www/html
       depends_on:
         mysql:
           condition: service_healthy
       networks:
         - lemp-net

     mysql:
       image: mysql:8.0
       environment:
         MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
         MYSQL_DATABASE: ${MYSQL_DATABASE}
         MYSQL_USER: ${MYSQL_USER}
         MYSQL_PASSWORD: ${MYSQL_PASSWORD}
       volumes:
         - db_data:/var/lib/mysql
         - ./mysql/init:/docker-entrypoint-initdb.d:ro
       healthcheck:
         test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-u", "root", "-p${MYSQL_ROOT_PASSWORD}"]
         interval: 10s
         timeout: 5s
         retries: 5
         start_period: 30s
       networks:
         - lemp-net

   volumes:
     db_data:

   networks:
     lemp-net:
       driver: bridge
   ```

2. **Validasi file Compose** sebelum dijalankan. Ini akan menampilkan hasil resolve `${...}` dari `.env`:

   ```bash
   docker compose config
   ```

   Pastikan nilai `MYSQL_PASSWORD`, `MYSQL_USER`, dan `MYSQL_DATABASE` sudah terisi sesuai `.env`. Jika muncul `${MYSQL_PASSWORD}` mentah, berarti Compose tidak menemukan file `.env` — cek posisinya.

3. **Jalankan seluruh stack:**

   ```bash
   docker compose up -d --build
   ```

   Proses pertama kali akan memakan waktu ~1–2 menit untuk mengunduh image MySQL (~600 MB) dan PHP-FPM (~80 MB).

4. **Pantau status service:**

   ```bash
   docker compose ps
   ```

   Anda akan melihat 3 service. Kolom `STATUS` untuk `mysql` awalnya `health: starting`, lalu berubah menjadi `healthy` setelah ±20–30 detik.

5. **Akses di browser:** `http://localhost:8080` → akan muncul halaman *"Halo dari Container!"* beserta hostname container PHP-FPM, versi MySQL, dan total kunjungan.

> 📌 **Refleksi:** Anda baru saja menjalankan tiga container, dengan network, healthcheck, volume, environment variable, dan konfigurasi Nginx — semuanya dari **satu file**. Bandingkan dengan Week 09, di mana Anda mengonfigurasi tiga service ini di dua VM terpisah secara manual.

---

### Bagian 4: Verifikasi Service Discovery

Sekarang buktikan bahwa container saling mengenali lewat nama service, bukan IP.

1. **Ping antar-container:**

   ```bash
   docker compose exec php ping -c 2 mysql
   docker compose exec nginx ping -c 2 php
   ```

   Nama `mysql` dan `php` akan di-resolve ke IP internal. Inilah DNS internal Compose.

2. **Cek environment variable yang diterima container `php`:**

   ```bash
   docker compose exec php env | grep DB_
   ```

   Anda akan melihat `DB_HOST=mysql`, `DB_NAME=appdb`, dsb. Variabel ini berasal dari `environment:` di `docker-compose.yml` + `.env`.

3. **Cek konfigurasi Nginx yang sedang aktif:**

   ```bash
   docker compose exec nginx cat /etc/nginx/conf.d/default.conf | head -20
   ```

   File ini adalah **bind mount** dari `./nginx/default.conf` di host.

---

### Bagian 5: Bind Mount & Live Reload

Bind mount memungkinkan edit file di host langsung tercermin di container.

1. **Edit `php/src/index.php`** di host — misalnya ubah judul `<h1>Halo dari Container!</h1>` menjadi `<h1>Halo dari KEKOAMANA!</h1>`.

2. **Refresh browser.** Perubahan **langsung terlihat** tanpa restart container. Ini karena `./php/src` di-bind ke `/var/www/html` di container `php`, dan juga di-mount ke container `nginx` (sebagai `:ro`).

3. **Uji live reload konfigurasi Nginx:** edit `nginx/default.conf`, misalnya ubah `listen 80;` menjadi `listen 80;` (tidak berubah) — atau tambahkan komentar `# test reload`.

   ```bash
   docker compose restart nginx
   ```

   Nginx akan membaca ulang konfigurasi. Tidak perlu rebuild atau recreate container.

> 📌 **Refleksi:** Bind mount sangat berguna untuk **development** (edit → lihat hasil). Untuk **production**, bind mount harus diganti dengan **image yang sudah di-*bake*** (file disalin di Dockerfile) agar konsisten antar server.

---

### Bagian 6: Volume Persistence Study

Buktikan data MySQL hidup di volume, bukan di container.

1. **Cek isi database saat ini:**

   ```bash
   docker compose exec mysql mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "SELECT COUNT(*) FROM appdb.visits;"
   ```

   Atau via query dari dalam container tanpa prompt password:
   ```bash
   docker compose exec mysql sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "SELECT COUNT(*) AS total FROM appdb.visits;"'
   ```

   Catat jumlah kunjungan.

2. **Hentikan dan hapus semua container (volume tetap):**

   ```bash
   docker compose down
   docker compose ps
   ```

   `docker compose ps` akan kosong — container sudah tidak ada. Tapi **volume `db_data` masih ada**:

   ```bash
   docker volume ls | grep lemp
   ```

3. **Jalankan ulang:**

   ```bash
   docker compose up -d
   ```

   Setelah ±30 detik (menunggu MySQL sehat), buka `http://localhost:8080`. **Jumlah kunjungan akan tetap sama** seperti sebelumnya — bukti data hidup di volume.

4. **Sekarang hapus volume (data ikut hilang):**

   ```bash
   docker compose down -v
   docker compose up -d
   ```

   Setelah `up`, tunggu MySQL sehat, lalu buka `http://localhost:8080`. **Jumlah kunjungan akan reset ke 1** (karena tabel `visits` baru dibuat oleh `init.sql`).

> 📌 **Refleksi:** Inilah pelajaran krusial: **`down` tanpa `-v`** = container dihapus, data aman. **`down -v`** = container **dan** volume dihapus, data hilang. Selalu berhati-hati dengan `-v` di production.

---

### Bagian 7: Scaling & Load Balancing

Inilah puncak praktikum — membangun ulang load balancing Week 09, tetapi dalam satu laptop dan satu perintah.

1. **Scale `php` menjadi 2 replika:**

   ```bash
   docker compose up -d --scale php=2
   ```

   Atau jika stack sudah berjalan:
   ```bash
   docker compose up -d --scale php=2 --no-recreate
   ```

2. **Cek container yang sekarang berjalan:**

   ```bash
   docker compose ps
   ```

   Anda akan melihat **dua container php**: `<folder>-php-1` dan `<folder>-php-2`.

3. **Restart Nginx** agar me-resolve ulang DNS ke dua replika baru:

   ```bash
   docker compose restart nginx
   ```

   > ⚠️ Langkah ini **wajib**. Nginx me-resolve `php` hanya saat startup. Tanpa restart, Nginx masih mengarah ke satu replika.

4. **Uji load balancing:** buka `http://localhost:8080` dan **refresh 10 kali**. Perhatikan baris **`PHP-FPM container:`** — nilainya akan **bergantian** antara `...-php-1` dan `...-php-2`. Di tabel *5 kunjungan terakhir*, kedua hostname juga akan muncul bergantian.

   Ini adalah bukti bahwa Nginx mendistribusikan request secara **round-robin** ke dua replika PHP-FPM.

5. **Turunkan kembali ke 1 replika:**

   ```bash
   docker compose up -d --scale php=1
   docker compose restart nginx
   ```

   Container `php-2` akan dihapus otomatis.

> 📌 **Refleksi:** Bandingkan dengan Week 09 — dulu Anda mengonfigurasi dua PHP-FPM di **dua VM terpisah**, masing-masing dengan IP statis yang harus ditulis di `upstream`. Sekarang dua replika hidup berdampingan di satu laptop, dan Docker DNS secara otomatis memberikan kedua IP ke Nginx. **Inilah esensi containerization untuk orkestrasi.**

---

### Bagian 8: Perintah Compose yang Wajib Dikuasai

| Perintah | Fungsi |
| :--- | :--- |
| `docker compose up -d` | Jalankan semua service (detach). |
| `docker compose up -d --build` | Jalankan + paksa rebuild image. |
| `docker compose up -d --scale <svc>=N` | Jalankan dengan N replika untuk service tertentu. |
| `docker compose down` | Hentikan dan hapus container + network (volume tetap). |
| `docker compose down -v` | Hapus container + network **+ volume**. |
| `docker compose ps` | Status semua service. |
| `docker compose logs -f <svc>` | Streaming log satu service. |
| `docker compose exec <svc> <cmd>` | Jalankan perintah di container yang berjalan. |
| `docker compose restart <svc>` | Restart satu service (tanpa hapus container). |
| `docker compose config` | Validasi YAML dan tampilkan hasil resolve `.env`. |
| `docker compose stop` / `start` | Hentikan / lanjutkan tanpa hapus container. |
| `docker compose pull` | Unduh versi terbaru semua image. |

---

## 🎯 Tugas & Evaluasi Mandiri

### Tugas 1 — Modifikasi Stack (Wajib)

Modifikasi `docker-compose.yml` untuk menambahkan:

1. **Service `phpmyadmin`** yang mengakses service `mysql`, dapat dibuka di `http://localhost:8081`. Gunakan image `phpmyadmin:latest` dan set `PMA_HOST=mysql` sebagai environment variable.
2. **Healthcheck** untuk service `php` yang memeriksa keberadaan file `index.php` di container, menggunakan `test: ["CMD", "test", "-f", "/var/www/html/index.php"]`.
3. **`.env.example`** yang diperbarui sesuai variabel baru (jika ada).

### Tugas 2 — Debugging Skenario Rusak (Wajib)

Instruktur akan memberikan `docker-compose.yml` yang **sengaja dirusak** dengan salah satu dari kesalahan berikut:

- `DB_HOST` diset `localhost` (bukan `mysql`).
- `fastcgi_pass php:9000;` diganti `fastcgi_pass 127.0.0.1:9000;`.
- `depends_on` dihilangkan dari service `php`.
- Volume `db_data` lupa dideklarasikan di bagian `volumes:`.
- `MYSQL_PASSWORD` di `.env` diubah setelah volume pertama kali dibuat.

Tugas Anda:

1. Identifikasi penyebab error dari output `docker compose logs` (sertakan screenshot log).
2. Perbaiki dan buktikan stack kembali jalan.
3. Tuliskan **kronologi debugging** di laporan (2–3 paragraf): apa gejalanya, apa hipotesisnya, bagaimana Anda memverifikasi, dan apa solusinya.

### Tugas 3 — Eksperimen Volume Persistence (Wajib)

Lakukan serangkaian uji dan catat hasilnya dalam tabel:

| Perintah | Container | Volume `db_data` | Data `visits` |
| :--- | :--- | :--- | :--- |
| `docker compose down` | ? | ? | ? |
| `docker compose down -v` | ? | ? | ? |
| `docker compose stop` | ? | ? | ? |

Isi sel dengan status (ada/hilang/berubah) berdasarkan eksperimen Anda. Sertakan minimal **3 screenshot** yang mendukung tabel.

### Tugas 4 — Refleksi Load Balancing (Wajib)

1. Scale `php` menjadi **3 replika**.
2. Ambil screenshot halaman web setelah refresh **10 kali** — tunjukkan bahwa **ketiga hostname** muncul bergantian.
3. Jawab dalam 1–2 paragraf: *"Mengapa Nginx hanya menunjuk ke satu service name `php:9000`, tetapi request bisa sampai ke tiga container berbeda?"* Kaitkan jawaban Anda dengan konsep **Docker DNS** dan **round-robin**.

### Tugas 5 — Laporan Praktikum (Screenshot)

Kumpulkan dalam **satu file zip** berisi screenshot:

1. **Output `docker compose ps`** yang menunjukkan 3 service (`nginx`, `php`, `mysql`) berstatus `Up` dan `mysql` berstatus `healthy`.
2. **Halaman browser `http://localhost:8080`** — diambil **3 kali** dengan hostname PHP-FPM **berbeda** (bukti load balancing dari Tugas 4).
3. **Output `docker compose logs php`** yang menunjukkan request masuk ke replika yang berbeda-beda.
4. **Hasil eksperimen volume** dari Tugas 3 (screenshot sebelum & sesudah `down`, `down -v`, dan `stop`).
5. **Output `docker compose config`** yang menampilkan hasil resolve `.env` (pastikan password **disamarkan/sensor** untuk keamanan).

Simpan sebagai: **`Week12_[NIM]_[NamaMahasiswa].zip`**.

> 📌 **Tips:** Screenshot dengan **satu jendela terminal + satu jendela browser** berdampingan agar konteksnya jelas. Sertakan **timestamp** pada screenshot (bisa dari jam sistem) untuk membuktikan urutan eksekusi.

---

## 🚨 Panduan Troubleshooting (Solusi Kendala)

| Masalah / Pesan Error | Kemungkinan Penyebab | Solusi Pengatasan |
| :--- | :--- | :--- |
| **`yaml: line X: could not find expected ':'`** | Indentasi YAML tidak konsisten (spasi vs tab, atau jumlah spasi berbeda antar-blok). | YAML **wajib** menggunakan **2 spasi** per level, **tanpa tab**. Validasi dengan `docker compose config` — error akan menunjuk baris spesifik. |
| **`service "php" has neither an image nor a build context specified`** | Service `php` di `docker-compose.yml` tidak punya `image:` maupun `build:`. | Tambahkan `build: ./php` (jika punya Dockerfile) atau `image: <nama>` (jika pakai image publik). |
| **`port is already allocated`** | Port `8080` sudah dipakai container/proses lain. | Cek dengan `sudo lsof -i :8080` (Linux/macOS) atau `netstat -ano \| findstr :8080` (Windows). Ubah mapping ke port lain, atau `docker compose down` untuk membersihkan. |
| **`502 Bad Gateway` dari Nginx** | Container `php` belum siap atau crash, atau `fastcgi_pass` salah nama service/port. | Cek `docker compose logs php`. Pastikan `fastcgi_pass php:9000;` (nama service `php`, bukan `127.0.0.1`). Pastikan PHP-FPM listen di `0.0.0.0:9000` (default image sudah benar). |
| **`php_network_getaddresses: getaddrinfo failed`** di log `php` | `DB_HOST` di `.env` atau `environment` **bukan** nama service (`mysql`), atau service `mysql` tidak ada. | Pastikan `DB_HOST=mysql` **persis sama** dengan nama service di `docker-compose.yml`. Cek dengan `docker compose config`. |
| **`Access denied for user 'appuser'@'...'`** | Password di `.env` berbeda dengan yang terlanjur dibuat di volume MySQL (volume lama tersimpan). | **Hapus volume** dengan `docker compose down -v`, lalu `docker compose up -d`. MySQL akan membuat user baru sesuai `.env` terbaru. |
| **MySQL berstatus `unhealthy` terus-menerus** | Password di `healthcheck` tidak cocok dengan `MYSQL_ROOT_PASSWORD`, atau MySQL belum selesai inisialisasi. | Pastikan `test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-u", "root", "-p${MYSQL_ROOT_PASSWORD}"]` — variabel **harus** `${MYSQL_ROOT_PASSWORD}`, bukan hardcode. Tambah `start_period: 30s`. Cek log: `docker compose logs mysql`. |
| **Container `php` restart berulang (`Restarting`)** | PHP-FPM crash karena error fatal di `index.php`, atau ekstensi `pdo_mysql` tidak terpasang. | Cek `docker compose logs php`. Pastikan Dockerfile menyertakan `RUN docker-php-ext-install mysqli pdo pdo_mysql`. Rebuild: `docker compose up -d --build php`. |
| **Setelah `--scale php=2`, request tetap ke satu replika** | Nginx sudah me-resolve DNS saat startup, sebelum replika baru ada. | **Restart Nginx** setelah scale: `docker compose restart nginx`. Urutan: `docker compose up -d --scale php=2` → `docker compose restart nginx`. |
| **`--scale php=2` error: `container name "/lemp-php-1" is already in use`** | Service `php` memakai `container_name:` fixed di `docker-compose.yml`. | Hapus `container_name:` dari service `php`. Compose akan otomatis memberi nama `<project>-php-1`, `<project>-php-2`. |
| **`bind source path does not exist` saat `up`** | Path di `volumes:` (mis. `./nginx/default.conf`) tidak ada di host. | Pastikan file/folder ada di host. `docker compose config` akan menampilkan path absolut yang di-resolve — cek apakah benar. |
| **File `init.sql` tidak jalan di MySQL** | Volume `db_data` sudah ada dari run sebelumnya, sehingga MySQL tidak re-inisialisasi. | `init.sql` hanya jalan **sekali saat volume kosong**. Untuk menerapkan ulang: `docker compose down -v` lalu `up`. |
| **`permission denied` saat bind mount di Windows/macOS** | Docker Desktop memakai VM; folder di luar *shared folder* tidak bisa di-mount. | Pindahkan proyek ke dalam home directory user (`C:\Users\...`, `/Users/...`). Cek **Docker Desktop → Settings → Resources → File Sharing**. |
| **PHP-FPM tidak bisa konek ke MySQL di Windows (error `Connection refused`)** | Container `php` dijalankan sebelum MySQL benar-benar sehat. | Pastikan `depends_on: mysql: condition: service_healthy` ada di service `php`, dan `healthcheck` MySQL valid. Jangan hanya pakai `depends_on: - mysql` (tanpa `condition`). |
| **`no matching manifest for linux/arm64/v8`** (Apple Silicon) | Image `mysql:8.0` sudah multi-arch, tetapi beberapa image lain (mis. `phpmyadmin` versi lama) mungkin belum. | Gunakan image versi terbaru (`phpmyadmin:latest` sudah multi-arch). Jika terpaksa: `docker compose up --build --platform linux/amd64`. |

---

## 🧭 Preview: Setelah Modul Ini

Setelah menyelesaikan Tahap 1 dan Tahap 2, Anda telah membangun ulang — dalam **satu laptop**, dengan **satu file konfigurasi**, dan **satu perintah** — seluruh arsitektur yang sebelumnya membutuhkan:

- Dua VM terpisah (Week 09)
- Konfigurasi Nginx manual
- Setup LAMP manual
- Konfigurasi SSL manual (Week 10)

Ke depan, materi yang **secara alami melanjutkan** dari sini adalah:

1. **CI/CD** — otomatisasi `docker compose up -d` setiap kali ada commit ke repository.
2. **Docker Registry** — mendorong image `myapp-php:v1` ke Docker Hub atau GitHub Container Registry, lalu menariknya di server lain.
3. **Monitoring** — menambahkan service `prometheus` + `grafana` ke Compose yang sama untuk memantau container.
4. **HTTPS otomatis** — menambahkan service `caddy` atau `traefik` sebagai reverse proxy yang menangani SSL Let's Encrypt secara otomatis (menggantikan langkah Week 10 secara deklaratif).
---
