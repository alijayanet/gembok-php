<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syarat & Ketentuan - Gembok Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --bg-page: #f8fafc;
            --surface: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        .header {
            background: var(--surface);
            padding: 1rem 1.25rem;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .back-btn {
            color: var(--text-main);
            text-decoration: none;
            font-size: 1.1rem;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1.25rem;
        }

        h1 { font-size: 1.5rem; margin-bottom: 2rem; }
        h2 { font-size: 1.1rem; margin-top: 2rem; color: var(--primary); }
        p { margin-bottom: 1rem; color: var(--text-muted); }
        ul { margin-bottom: 1rem; color: var(--text-muted); padding-left: 1.5rem; }
        li { margin-bottom: 0.5rem; }
    </style>
</head>
<body>

    <div class="header">
        <a href="javascript:history.back()" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <span style="font-weight: 600;">Kembali</span>
    </div>

    <div class="container">
        <h1>Syarat dan Ketentuan Layanan</h1>

        <h2>1. Pendahuluan</h2>
        <p>Selamat datang di layanan Internet Gembok Net. Dengan melalukan pembayaran dan menggunakan layanan kami, Anda dianggap telah membaca, memahami, dan menyetujui seluruh Syarat dan Ketentuan ini.</p>

        <h2>2. Pembayaran</h2>
        <ul>
            <li>Tagihan layanan internet diterbitkan setiap bulan sesuai dengan siklus penagihan Anda.</li>
            <li>Pembayaran dapat dilakukan melalui metode transfer bank, e-wallet, atau gerai retail yang tersedia di halaman pembayaran.</li>
            <li>Keterlambatan pembayaran dapat mengakibatkan penghentian sementara layanan (isolir) secara otomatis.</li>
            <li>Biaya administrasi mungkin dikenakan tergantung pada metode pembayaran yang dipilih.</li>
        </ul>

        <h2>3. Pengembalian Dana (Refund)</h2>
        <p>Dana yang sudah dibayarkan untuk layanan yang telah aktif tidak dapat dikembalikan, kecuali terdapat kesalahan sistem atau kelebihan bayar yang terverifikasi.</p>

        <h2>4. Penggunaan Layanan</h2>
        <p>Pelanggan dilarang menggunakan layanan ini untuk kegiatan ilegal, melanggar hukum di Indonesia, atau merugikan pihak lain.</p>

        <h2>5. Perubahan Ketentuan</h2>
        <p>Kami berhak mengubah Syarat dan Ketentuan ini sewaktu-waktu tanpa pemberitahuan sebelumnya. Perubahan akan berlaku efektif segera setelah dipublikasikan.</p>

        <p style="margin-top: 3rem; font-size: 0.9rem;">Terakhir diperbarui: <?= date('d F Y') ?></p>
    </div>

</body>
</html>
