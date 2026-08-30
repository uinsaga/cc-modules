# ☁️ Cloud Computing Practical Lab Guides

Selamat datang di repositori resmi praktikum mata kuliah **Cloud Computing**. Repositori ini dirancang khusus sebagai panduan belajar berbasis praktik (*hands-on*) untuk mahasiswa semester 5. 

Seluruh modul berfokus pada pengalaman nyata dalam memilih, mengintegrasikan, dan mengadministrasikan infrastruktur *cloud* modern dari tingkat dasar hingga siap *live* di internet.

---

## 📂 Struktur Repositori

```text
cc-modules/
├── README.md                   # Panduan & informasi utama repositori
├── materials/                  # Panduan teori & langkah-langkah praktikum mingguan
│   ├── week-01.md              # Pengenalan Cloud & Akses Server (SSH)
│   ├── week-02.md              # Administrasi Linux Server (CLI)
│   ├── week-03.md              # Keamanan Akses Server (SSH Key)
│   ├── week-04.md              # Konfigurasi Web Server (Nginx)
│   ├── week-05.md              # Managed Cloud Database (PostgreSQL)
│   ├── week-06.md              # Cloud Object Storage (S3 Bucket)
│   ├── week-07.md              # Otomatisasi Deployment (Git & CI/CD)
│   ├── week-08.md              # Ujian Tengah Semester (UTS)
│   ├── week-09.md              # Isolasi Aplikasi (Docker Container)
│   ├── week-10.md              # Custom Image (Dockerfile)
│   ├── week-11.md              # Container Registry (Docker Hub / GHCR)
│   ├── week-12.md              # Multi-Container (Docker Compose)
│   ├── week-13.md              # Domain, DNS, CDN, & SSL/HTTPS
│   ├── week-14.md              # Monitoring Server & Cost Management
│   └── week-15.md              # Showcase Proyek Cloud
└── practices/                  # Kode sumber, starter kit, & template latihan
    ├── practice_01/
    ├── practice_02/
    └── ...

```

---

## 🗺️ Silabus Praktikum Mingguan

| Minggu | Topik Utama | Fokus Skill | Modul |
| --- | --- | --- | --- |
| **01** | Pengenalan Cloud & Akses Server | SSH, Terminal Access, Remote Server | [`week-01.md`](https://www.google.com/search?q=materials/week-01.md) |
| **02** | Administrasi Linux Server | Navigation, File Management, Permissions, Nano | [`week-02.md`](https://www.google.com/search?q=materials/week-02.md) |
| **03** | Keamanan Akses Server | SSH Key Pair, Asymmetric Encryption, Config | [`week-03.md`](https://www.google.com/search?q=materials/week-03.md) |
| **04** | Konfigurasi Web Server | Nginx Setup, Virtual Host, Port Binding | [`week-04.md`](https://www.google.com/search?q=materials/week-04.md) |
| **05** | Managed Cloud Database | PostgreSQL, Supabase/Neon, API Integration | [`week-05.md`](https://www.google.com/search?q=materials/week-05.md) |
| **06** | Cloud Object Storage | S3 Buckets, Public Policy, Media Upload | [`week-06.md`](https://www.google.com/search?q=materials/week-06.md) |
| **07** | Otomatisasi Deployment | GitHub, Webhooks, CI/CD, PaaS Auto-deploy | [`week-07.md`](https://www.google.com/search?q=materials/week-07.md) |
| **08** | **Ujian Tengah Semester (UTS)** | **Mid-Term Practical Live-Lab Exam** | **UTS** |
| **09** | Isolasi Aplikasi | Docker Basics, Container Lifecycle, Port Mapping | [`week-09.md`](https://www.google.com/search?q=materials/week-09.md) |
| **10** | Custom Image | Dockerfile Writing, Image Building, Environment (.env) | [`week-10.md`](https://www.google.com/search?q=materials/week-10.md) |
| **11** | Container Registry | Docker Hub, GHCR, Tagging, Pushing & Pulling | [`week-11.md`](https://www.google.com/search?q=materials/week-11.md) |
| **12** | Multi-Container | Docker Compose, Multi-Service Network | [`week-12.md`](https://www.google.com/search?q=materials/week-12.md) |
| **13** | Domain, DNS, CDN & Security | Cloudflare DNS, A Record, CDN, HTTPS/SSL | [`week-13.md`](https://www.google.com/search?q=materials/week-13.md) |
| **14** | Monitoring & Cost Management | `htop`, `df -h`, Server Metrics, Cost Calculator | [`week-14.md`](https://www.google.com/search?q=materials/week-14.md) |
| **15** | Showcase Proyek Cloud | Architecture Presentation & Live Demo | [`week-15.md`](https://www.google.com/search?q=materials/week-15.md) |

---

## 🛠️ Prasyarat Minimum (Prerequisites)

Sebelum memulai praktikum, pastikan perangkat laptop kamu telah terpasang beberapa *tools* dasar berikut:

1. **Terminal / Shell Client:**
* **Windows:** Git Bash / PowerShell / Command Prompt.
* **macOS / Linux:** Terminal Bawaan.


2. **Text Editor:** [Visual Studio Code](https://code.visualstudio.com/) (disarankan memasang ekstensi *Remote - SSH*).
3. **Version Control:** [Git Client](https://git-scm.com/) terpasang di komputer lokal.
4. **Akun Layanan (Free-Tier):**
* Akun [GitHub](https://github.com/) aktif.
* Akun [Docker Hub](https://hub.docker.com/).
* Akun [Supabase](https://supabase.com/) / [Neon](https://neon.tech/).
* Akun [Cloudflare](https://www.cloudflare.com/).



---

## 🚀 Cara Menggunakan Repositori Ini

1. **Clone Repositori:**
```bash
git clone [https://github.com/username-kamu/cc-modules.git](https://github.com/username-kamu/cc-modules.git)
cd cc-modules

```


2. **Buka Modul Mingguan:**
Masuk ke folder `materials/` dan buka berkas sesuai dengan minggu perkuliahan berjalan (misal: `materials/week-01.md`).
3. **Gunakan Starter Code:**
Jika pertemuan membutuhkan *starter code*, masuk ke direktori `practices/practice_XX` yang bersangkutan.

---

## 🤝 Kontribusi & Kendala

Jika menemukan kendala teknis, *typo* pada modul, atau *error* saat mengikuti langkah praktikum, silakan buat **Issues** baru pada repositori ini atau diskusikan langsung saat sesi laboratorium berlangsung.
