<?php
$message = "";
$target_dir = "uploads/"; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES["fileToUpload"])) {
        if (!is_dir($target_dir)) {
            $message = "<div class='msg-box msg-error'>❌ <strong>Oops... Gagal upload file.</strong> Cari tahu kenapa sistem menolak proses ini!</div>";
        } else {
            $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                $message = "<div class='msg-box msg-success'>✅ <strong>Sukses:</strong> Berkas <code>" . htmlspecialchars(basename($_FILES["fileToUpload"]["name"])) . "</code> berhasil diunggah.</div>";
            } else {
                $message = "<div class='msg-box msg-error'>❌ <strong>Oops Gagal:</strong> Terjadi kesalahan internal saat memindahkan berkas ke direktori tujuan. Silakan lakukan *troubleshooting* pada server!</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Uji Coba Unggah Berkas</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
            max-width: 600px; 
            margin: 50px auto; 
            padding: 0 20px; 
            color: #2d3748;
            line-height: 1.6;
            background-color: #f8fafc;
        }
        h2 { 
            color: #1e3a8a; 
            font-size: 1.75rem;
            margin-bottom: 25px;
        }
        .box { 
            background: #ffffff; 
            padding: 30px; 
            border-radius: 12px; 
            border: 1px solid #e2e8f0; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }
        p {
            margin-top: 0;
            color: #4a5568;
        }
        input[type="file"] {
            display: block;
            width: 100%;
            padding: 10px;
            background: #f1f5f9;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            box-sizing: border-box;
            cursor: pointer;
        }
        input[type="file"]:hover {
            background: #e2e8f0;
        }
        button {
            background-color: #2563eb;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        button:hover {
            background-color: #1d4ed8;
        }
        /* Penataan Kotak Pesan */
        .msg-box {
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 8px;
            font-size: 0.95rem;
        }
        .msg-success { 
            background: #dcfce7; 
            border: 1px solid #bbf7d0; 
            color: #166534; 
        }
        .msg-error { 
            background: #fee2e2; 
            border: 1px solid #fecaca; 
            color: #991b1b; 
        }
        code {
            background: rgba(0, 0, 0, 0.05);
            padding: 2px 4px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9em;
        }
        .back-link { 
            color: #2563eb; 
            text-decoration: none; 
            font-weight: 600; 
            display: inline-block;
        }
        .back-link:hover { 
            text-decoration: underline; 
        }
    </style>
</head>
<body>

    <h2>Form Pengujian Unggah Berkas (Upload)</h2>
    
    <?php if (!empty($message)): ?>
        <?php echo $message; ?>
    <?php endif; ?>

    <div class="box">
        <form action="" method="post" enctype="multipart/form-data">
            <p>Pilih berkas untuk diunggah (Uji file kecil atau file besar &gt; 300MB):</p>
            <input type="file" name="fileToUpload" required><br><br>
            <button type="submit" name="submit">Unggah Berkas</button>
        </form>
    </div>

    <p><a href="index.php" class="back-link">&larr; Kembali ke Beranda</a></p>

</body>
</html>
