<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTS Cloud Computing - Portal Ujian</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
            max-width: 750px; 
            margin: 50px auto; 
            padding: 0 20px; 
            color: #2d3748; 
            line-height: 1.7; 
            background-color: #f8fafc;
        }
        h1 { 
            color: #1e3a8a; 
            font-size: 2rem; 
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }
        .lead-text {
            color: #4a5568;
            font-size: 1.05rem;
            margin-bottom: 30px;
        }
        .card { 
            background: #ffffff; 
            border: 1px solid #e2e8f0; 
            padding: 30px; 
            border-radius: 12px; 
            margin-bottom: 25px; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        h3 {
            color: #1e293b;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.25rem;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 10px;
        }
        ul { 
            list-style: none; 
            padding-left: 0; 
            margin: 0;
        }
        li { 
            margin-bottom: 20px; 
            padding-bottom: 20px;
            border-bottom: 1px dashed #e2e8f0;
        }
        li:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        .modul-title {
            color: #0f172a;
            display: inline-block;
            margin-bottom: 6px;
        }
        .modul-desc {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 12px;
        }
        a { 
            color: #2563eb; 
            text-decoration: none; 
            font-weight: 600; 
            display: inline-block;
            transition: color 0.2s ease;
        }
        a:hover { 
            color: #1d4ed8;
            text-decoration: underline; 
        }
        code {
            background: #f1f5f9;
            color: #b91c1c;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9em;
        }
        .note {
            color: #64748b;
            font-size: 0.9rem;
            background: #f1f5f9;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #94a3b8;
        }
    </style>
</head>
<body>
    <h1>Portal Ujian Tengah Semester (UTS)</h1>
    <p class="lead-text">Selamat datang di server pengujian Cloud Computing. Halaman ini menandakan bahwa konfigurasi site Nginx dan PHP-FPM Anda pada domain <strong>uts.local</strong> telah berhasil berjalan dengan normal.</p>

    <div class="card">
        <h3>Daftar Modul Ujian & Tugas</h3>
        <ul>
            <li>
                <strong class="modul-title">Tugas 3 (Form Upload & Direktori)</strong>
                <div class="modul-desc">Silakan lakukan pengujian fitur unggah berkas dan analisis struktur penyimpanan internal jika terjadi kendala operasional.</div>
                <a href="upload_form.php">Akses upload_form.php &rarr;</a>
            </li>
            <li>
                <strong class="modul-title">Tugas 5 (Laporan Dinamis)</strong>
                <div class="modul-desc">Silakan lakukan pengujian halaman laporan dinamis berbasis grafik dan analisis kesiapan ekstensi PHP pada server.</div>
                <a href="generate_report.php">Akses generate_report.php &rarr;</a>
            </li>
        </ul>
    </div>

    <div class="note">
        <strong>Catatan Investigasi:</strong> Jika Anda mengalami kendala saat mengakses fitur di atas, silakan periksa log server Anda (misalnya di <code>/var/log/nginx/error.log</code> atau log PHP-FPM terkait) untuk melakukan analisis mandiri.
    </div>
</body>
</html>
