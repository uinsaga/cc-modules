# ☸️ Modul Week 14: Container Orchestration Fundamentals dengan Kubernetes & Minikube

## 📌 Capaian Pembelajaran (Sub-CPMK 7.1.14)
Mahasiswa mampu memahami konsep dasar **Container Orchestration**, arsitektur **Kubernetes Cluster** (Control Plane & Worker Node), menginstalasi lingkungan *local cluster* menggunakan **Minikube** & **kubectl**, serta mengelola objek fundamental Kubernetes (**Pod**, **Deployment**, dan **Service**).

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
* Telah menguasai konsep kontainerisasi Docker (*materi Week 05 & 06*).
* Memiliki remote server Linux dengan spesifikasi **minimal 2 vCPU & 2-4 GB RAM**.
* Docker Engine terinstal dan aktif di server.

---

## 📚 Landasan Teori

### 1. Mengapa Perlu Container Orchestration?
Mengelola puluhan atau ratusan kontainer secara manual menggunakan Docker CLI di lingkungan produksi sangat kompleks. **Container Orchestrator** seperti **Kubernetes (K8s)** hadir untuk menangani:
* **Auto-healing:** Mengganti atau mereboot kontainer yang crash secara otomatis.
* **Auto-scaling:** Menambah/mengurangi jumlah replika kontainer berdasarkan beban trafik.
* **Load Balancing & Service Discovery:** Mendistribusikan lalu lintas secara otomatis ke Pod yang sehat.
* **Automated Rollouts & Rollbacks:** Mengubah versi aplikasi tanpa *downtime*.

### 2. Arsitektur Kubernetes Cluster
```text
+-----------------------------------------------------------------------+
|                         KUBERNETES CONTROL PLANE                      |
|  [ API Server ] <---> [ etcd ] <---> [ Scheduler ] <---> [ Controller ]|
+-----------------------------------------------------------------------+
                                   |
                +------------------+------------------+
                v                                     v
+-------------------------------+     +-------------------------------+
|         WORKER NODE 1         |     |         WORKER NODE 2         |
|  [ Kubelet ]   [ Kube-Proxy ] |     |  [ Kubelet ]   [ Kube-Proxy ] |
|  +-------------------------+  |     |  +-------------------------+  |
|  | Pod (Container App A)   |  |     |  | Pod (Container App A)   |  |
|  +-------------------------+  |     |  +-------------------------+  |
+-------------------------------+     +-------------------------------+

```

### 3. Objek Utama Kubernetes (Workloads & Network)

* **Pod:** Unit eksekusi terkecil di Kubernetes yang membungkus satu atau lebih kontainer Docker.
* **Deployment:** Pengelola deklaratif (*controller*) yang mengatur replika Pod, pembaruan (*rolling update*), dan pembatalan (*rollback*).
* **Service:** Abstraksi jaringan yang menyediakan *Stable IP/DNS Name* dan Load Balancer internal untuk mengakses sekumpulan Pod.
* *ClusterIP (Default):* Akses internal antar-Pod di dalam cluster.
* *NodePort:* Membuka port statis pada setiap Node agar bisa diakses dari luar cluster.



---

## 🧪 Langkah-Langkah Praktikum (Hands-On)

### Bagian 1: Instalasi kubectl CLI & Minikube

1. Login ke server remote via SSH:
```bash
ssh myserver

```


2. Unduh dan instal perintah CLI resmi Kubernetes (**`kubectl`**):
```bash
curl -LO "[https://dl.k8s.io/release/$(curl](https://dl.k8s.io/release/$(curl) -L -s [https://dl.k8s.io/release/stable.txt)/bin/linux/amd64/kubectl](https://dl.k8s.io/release/stable.txt)/bin/linux/amd64/kubectl)"
sudo install -o root -g root -m 0755 kubectl /usr/local/bin/kubectl
kubectl version --client

```


3. Unduh dan instal **Minikube** (*Local Kubernetes Engine*):
```bash
curl -LO [https://storage.googleapis.com/minikube/releases/latest/minikube-linux-amd64](https://storage.googleapis.com/minikube/releases/latest/minikube-linux-amd64)
sudo install minikube-linux-amd64 /usr/local/bin/minikube

```


4. Jalankan Minikube cluster menggunakan driver Docker:
```bash
minikube start --driver=docker

```


5. Verifikasi status cluster dan daftar node:
```bash
kubectl cluster-info
kubectl get nodes

```



---

### Bagian 2: Meproduksi Deployment Pertama

1. Buat **Deployment** baru bernama `web-app` menggunakan image Nginx Alpine:
```bash
kubectl create deployment web-app --image=nginx:alpine

```


2. Tampilkan daftar Pod yang telah dibuat otomatis oleh Deployment:
```bash
kubectl get pods

```


3. **Uji Fitur Auto-Healing:** Hapus Pod yang sedang berjalan secara paksa:
```bash
kubectl delete pod <NAMA_POD_KAMU>

```


*Cek kembali dengan `kubectl get pods`. Kubernetes secara otomatis akan membuat Pod baru untuk menggantikan Pod yang terhapus!*
4. **Uji Fitur Scaling (Replikasi Pod):** Tingkatkan jumlah replika menjadi 3 Pod:
```bash
kubectl scale deployment web-app --replicas=3
kubectl get pods

```



---

### Bagian 3: Expose Aplikasi via Service NodePort

1. Buat objek **Service** dengan tipe `NodePort` untuk mengekspos Deployment `web-app` ke port jaringan:
```bash
kubectl expose deployment web-app --type=NodePort --port=80

```


2. Periksa detail Service yang baru dibuat:
```bash
kubectl get svc

```


*(Amati kolom `PORT(S)`. Contoh: `80:31234/TCP` berarti port internal 80 dipetakan ke **NodePort 31234**).*
3. Dapatkan IP Cluster Minikube:
```bash
minikube ip

```


4. Uji keteraksesan aplikasi web Nginx dari dalam server:
```bash
curl http://$(minikube ip):<PORT_NODEPORT>

```



---

### Bagian 4: Menulis Manifest File Kubernetes (YAML Standard)

Dibandingkan perintah imperative (`kubectl create`), praktik terbaik di industri adalah menggunakan pendekatan **Declarative Manifest YAML**.

1. Buat direktori kerja baru:
```bash
mkdir -p ~/k8s-manifests
cd ~/k8s-manifests

```


2. Buat berkas `app-stack.yaml`:
```bash
nano app-stack.yaml

```


3. Tuliskan manifest gabungan Deployment & Service berikut:
```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: demo-app
  labels:
    app: demo-app
spec:
  replicas: 2
  selector:
    matchLabels:
      app: demo-app
  template:
    metadata:
      labels:
        app: demo-app
    spec:
      containers:
      - name: nginx
        image: nginxdemos/hello
        ports:
        - containerPort: 80
---
apiVersion: v1
kind: Service
metadata:
  name: demo-app-service
spec:
  type: NodePort
  selector:
    app: demo-app
  ports:
    - port: 80
      targetPort: 80
      nodePort: 30080

```


4. Terapkan (*apply*) manifest deklaratif tersebut ke dalam cluster:
```bash
kubectl apply -f app-stack.yaml

```


5. Verifikasi seluruh resource yang dibuat oleh manifest:
```bash
kubectl get all

```


6. Uji akses via `curl`:
```bash
curl http://$(minikube ip):30080

```



---

## 🎯 Tugas & Evaluasi Mandiri

1. **Skenario Praktikum Mandiri:**
* Ubah berkas `app-stack.yaml` untuk menaikkan jumlah `replicas` Pod dari **2 menjadi 5**.
* Ubah image kontainer menjadi **`USERNAME_DOCKERHUB/my-web-app:v1.0`** (Image custom milikmu dari Week 06).
* Terapkan kembali perubahan dengan `kubectl apply -f app-stack.yaml`.
* Buktikan bahwa 5 Pod baru telah berjalan aktif via perintah `kubectl get pods`.


2. **Laporan Praktikum (Screenshot):**
* Ambil *Screenshot* penuh yang menampilkan:
1. Tampilan terminal output `kubectl get all` yang memperlihatkan 5 Pod berstatus *Running* dan Service berstatus *NodePort*.
2. Output perintah `curl http://$(minikube ip):30080` yang menampilkan halaman HTML aplikasi kamu.


* Simpan dengan format: **`Week14_[NIM]_[NamaMahasiswa].png`**.



---

## 🚨 Panduan Troubleshooting (Solusi Kendala)

| Masalah / Pesan Error | Kemungkinan Penyebab | Solusi Pengatasan |
| --- | --- | --- |
| **`ImagePullBackOff` / `ErrImagePull**` | Nama image Docker Hub salah, bersifat private, atau belum di-push ke registry. | Pastikan ejaan `image:` benar dan repositori bersifat public, atau jalankan `kubectl describe pod <NAMA_POD>` untuk melihat log detail kesalahan. |
| **`Exiting due to GUEST_DIRTY_DOCKER` saat `minikube start**` | Sesi Minikube sebelumnya berhenti secara tidak wajar. | Jalankan `minikube delete` untuk mereset cluster lokal, lalu eksekusi `minikube start --driver=docker` kembali. |
| **`provided port is not in the valid range`** | Nilai `nodePort` di manifest YAML berada di luar jangkauan port standar Kubernetes. | Kubernetes secara standar menetapkan batas port `NodePort` pada rentang **30000 - 32767**. Gunakan nilai di dalam rentang tersebut. |

