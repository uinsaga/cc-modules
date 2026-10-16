<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pemrosesan Laporan</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f6f8;
            color: #333;
            line-height: 1.6;
            padding: 40px 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        .box {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        .success-box {
            border-left: 5px solid #28a745;
        }
        .success-title {
            color: #28a745;
            margin-top: 0;
        }
        .error-box {
            border-left: 5px solid #dc3545;
        }
        .error-title {
            color: #dc3545;
            margin-top: 0;
        }
        .error-message {
            background-color: #fff5f5;
            border: 1px solid #ffe3e3;
            color: #b52b27;
            padding: 12px;
            border-radius: 4px;
            font-family: monospace;
            margin: 15px 0;
            word-break: break-all;
        }
        .text-muted {
            color: #666;
            font-size: 0.9em;
        }
        .back-link {
            display: inline-block;
            text-decoration: none;
            color: #0066cc;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <?php 
    try { 
        if (!extension_loaded('imagick')) { 
            throw new Exception("Ekstensi tidak ditemukan! Fitur laporan visual tidak dapat diinisialisasi."); 
        } 
        
        $image = new Imagick(); // Proses tiruan pembuatan laporan grafis 
        
        ?>
        <div class="box success-box">
            <h1 class="success-title">✓ Laporan Berhasil Digenerate</h1>
            <p>Modul pemrosesan grafik aktif dengan sempurna.</p>
        </div>
        <?php
        
    } catch (Exception $e) { 
        http_response_code(500); 
        
        ?>
        <div class="box error-box">
            <h1 class="error-title">⚠ 500 Internal Server Error</h1>
            <p>Terjadi kesalahan pada sistem:</p>
            
            <div class="error-message">
                <?php echo htmlspecialchars($e->getMessage()); ?>
            </div>
            
            <p class="text-muted"><em>Silakan periksa error log server Anda untuk informasi lebih lanjut.</em></p>
        </div>
        <?php 
    } 
    ?>

    <!-- Link Kembali -->
    <div style="text-align: center;">
        <a href="index.php" class="back-link">&larr; Kembali ke Beranda</a>
    </div>
</div>

</body>
</html>
