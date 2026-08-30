# 📊 Modul Week 12: Cloud Server & Container Monitoring dengan Prometheus & Grafana

## 📌 Capaian Pembelajaran (Sub-CPMK 7.1.12)
Mahasiswa mampu memahami konsep dasar pemantauan sistem (*system monitoring*), metrik performa (*metrics & time-series data*), mengonfigurasi **Prometheus** untuk pengumpulan metrik server/kontainer, serta membangun visualisasi dasbor interaktif menggunakan **Grafana**.

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
* Menguasai pengelolaan multi-kontainer via Docker Compose (*materi Week 07*).
* Memiliki akses SSH remote server dengan spesifikasi minimal 2 GB RAM.
* Port **9090 (Prometheus)**, **3000 (Grafana)**, dan **9100 (Node Exporter)** terbuka atau dapat diakses.

---

## 📚 Landasan Teori

### 1. Mengapa Perlu Server Monitoring?
Dalam lingkungan cloud dan microservices, pemantauan kondisi server (*health check*) secara real-time sangat krusial untuk mencegah *downtime*, mendeteksi kebocoran memori (*memory leak*), mengamati lonjakan penggunaan CPU, serta merencanakan kapasitas (*capacity planning*).

### 2. Arsitektur Monitoring Stack: Prometheus + Grafana
```text
+-----------------------+      Pull Metrics (HTTP /metrics)      +-------------------+
|  Target (Server Host) | <------------------------------------- |                   |
|  Node Exporter :9100  |                                        |                   |
+-----------------------+                                        |    Prometheus     |
                                                                 |  (TSDB Engine)    |
+-----------------------+      Pull Metrics (HTTP /metrics)      |       :9090       |
| Target (cAdvisor)     | <------------------------------------- |                   |
| Container Metrics:8080|                                        +-------------------+
+-----------------------+                                                  |
                                                                    PromQL Query
                                                                           v
                                                                 +-------------------+
                                                                 |      Grafana      |
                                                                 | (Dashboard Visual)|
                                                                 |       :3000       |
                                                                 +-------------------+

```

* **Node Exporter:** Agen (*exporter*) yang mengumpulkan metrik OS hardware/host Linux (CPU, RAM, Disk, Network).
* **Prometheus:** Database deret waktu (*Time-Series Database / TSDB*) yang menarik (*pull/scrape*) metrik secara berkala dari exporter.
* **Grafana:** Platform analisis dan visualisasi data yang merubah metrik dari Prometheus menjadi grafik dasbor interaktif.

---

## 🧪 Langkah-Langkah Praktikum (Hands-On)

### Bagian 1: Persiapan Stack Monitoring via Docker Compose

1. Login ke server remote via SSH:
```bash
ssh myserver

```


2. Buat direktori kerja baru untuk monitoring stack:
```bash
mkdir -p ~/monitoring-stack
cd ~/monitoring-stack

```


3. Buat berkas konfigurasi Prometheus **`prometheus.yml`**:
```bash
nano prometheus.yml

```


4. Isikan aturan *scraping* metrik berikut:
```yaml
global:
  scrape_interval: 15s

scrape_configs:
  - job_name: 'prometheus'
    static_configs:
      - targets: ['localhost:9090']

  - job_name: 'node_exporter'
    static_configs:
      - targets: ['node_exporter:9100']

```



---

### Bagian 2: Menulis `compose.yaml` untuk Deployment

1. Buat berkas **`compose.yaml`**:
```bash
nano compose.yaml

```


2. Tuliskan spesifikasi layanan untuk Prometheus, Grafana, dan Node Exporter:
```yaml
services:
  prometheus:
    image: prom/prometheus:latest
    container_name: prometheus
    volumes:
      - ./prometheus.yml:/etc/prometheus/prometheus.yml
      - prometheus_data:/prometheus
    ports:
      - "9090:9090"
    restart: always

  node_exporter:
    image: prom/node-exporter:latest
    container_name: node_exporter
    volumes:
      - /proc:/host/proc:ro
      - /sys:/host/sys:ro
      - /:/rootfs:ro
    command:
      - '--path.procfs=/host/proc'
      - '--path.rootfs=/rootfs'
      - '--path.sysfs=/host/sys'
    ports:
      - "9100:9100"
    restart: always

  grafana:
    image: grafana/grafana:latest
    container_name: grafana
    ports:
      - "3000:3000"
    volumes:
      - grafana_data:/var/lib/grafana
    restart: always

volumes:
  prometheus_data:
  grafana_data:

```


3. Jalankan seluruh layanan monitoring:
```bash
docker compose up -d
docker compose ps

```



---

### Bagian 3: Verifikasi Prometheus Target & Metric Queries

1. Buka browser dan akses antarmuka web Prometheus:
`http://IP_SERVER_KAMU:9090`
2. Masuk ke menu **Status** -> **Targets**.
3. Pastikan status `node_exporter` dan `prometheus` menunjukkan indikator **`UP`** (berwarna hijau).
4. Kembali ke tab **Graph**, ketik kueri PromQL dasar berikut untuk melihat penggunaan memori RAM gratis:
```promql
node_memory_MemFree_bytes

```


5. Klik tombol **Execute** untuk melihat hasilnya.

---

### Bagian 4: Integrasi Data Source & Dashboard di Grafana

1. Akses antarmuka Grafana via browser:
`http://IP_SERVER_KAMU:3000`
2. Login pertama kali menggunakan kredensial standar:
* **Username:** `admin`
* **Password:** `admin`
*(Atur kata sandi baru sesuai petunjuk).*


3. **Menambahkan Data Source Prometheus:**
* Di dasbor Grafana, masuk ke **Connections** -> **Data sources** -> Klik **Add data source**.
* Pilih **Prometheus**.
* Pada kolom **Prometheus server URL**, isikan URL jaringan kontainer internal Prometheus:
`http://prometheus:9090`
* Scroll ke bawah dan klik tombol **Save & test**. *(Pastikan muncul notifikasi hijau "Data source is working")*.


4. **Import Dashboard Node Exporter Resmi:**
* Klik ikon **`+`** (Plus) di pojok kanan atas -> Pilih **Import dashboard**.
* Pada kolom *Import via grafana.com*, masukkan ID dashboard resmi Node Exporter Full: **`1860`**.
* Klik **Load**.
* Pilih Data Source Prometheus yang telah dibuat tadi, lalu klik **Import**.
* **Selesai!** Kamu sekarang memiliki dasbor monitoring kelas enterprise yang menampilkan statistik CPU, RAM, Disk I/O, dan Network Bandwidth secara real-time.



---

## 🎯 Tugas & Evaluasi Mandiri

1. **Skenario Praktikum Mandiri:**
* Tambahkan layanan **`cAdvisor`** (`google/cadvisor:latest`) ke dalam file `compose.yaml` di port `8080` untuk memantau metrik penggunaan CPU/RAM khusus per-kontainer Docker.
* Tambahkan target `cAdvisor` ke file `prometheus.yml`.
* Import Dasbor Grafana khusus cAdvisor (ID Dashboard: **`14282`**) dan verifikasi visualisasinya.


2. **Laporan Praktikum (Screenshot):**
* Ambil *Screenshot* penuh yang menampilkan:
1. Tampilan dasbor **Grafana Node Exporter Full (ID 1860)** yang memperlihatkan grafik aktivitas CPU & RAM server kamu secara real-time.
2. Tampilan halaman Prometheus **Status -> Targets** berstatus *UP*.


* Simpan dengan format: **`Week12_[NIM]_[NamaMahasiswa].png`**.



---

## 🚨 Panduan Troubleshooting (Solusi Kendala)

| Masalah / Pesan Error | Kemungkinan Penyebab | Solusi Pengatasan |
| --- | --- | --- |
| **Status Target di Prometheus `DOWN` / Connection Refused** | Nama service di `prometheus.yml` tidak sesuai dengan nama service di `compose.yaml`. | Gunakan nama service Docker Compose sebagai hostname target (misal: `node_exporter:9100` bukan `localhost:9100`). |
| **`HTTP Error Bad Gateway` saat test data source di Grafana** | Grafana tidak dapat terhubung ke Prometheus via network internal. | Pastikan URL diisi `http://prometheus:9090` (menggunakan nama container/service, bukan IP `127.0.0.1`). |
| **Grafik Dashboard Grafana Kosong / N/A** | Range waktu pada Grafana tidak sesuai atau time-sync server bermasalah. | Sesuaikan filter waktu di kanan atas Grafana menjadi *Last 15 minutes* dan pastikan jam di server Linux kamu sudah tersinkronisasi (NTP). |
