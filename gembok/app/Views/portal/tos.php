<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a0a0f">
    <title>Syarat & Ketentuan - Portal Pelanggan</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: #12121a;
            --bg-card: rgba(20, 20, 35, 0.95);
            --neon-cyan: #00f5ff;
            --neon-purple: #bf00ff;
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --text-muted: rgba(255, 255, 255, 0.5);
            --border-color: rgba(255, 255, 255, 0.1);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            padding-bottom: 70px;
            line-height: 1.6;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 30%, rgba(0, 245, 255, 0.05) 0%, transparent 50%),
                        radial-gradient(circle at 70% 70%, rgba(191, 0, 255, 0.05) 0%, transparent 50%);
            animation: backgroundMove 20s ease-in-out infinite;
            z-index: -1;
        }
        
        @keyframes backgroundMove {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-5%, -5%) rotate(5deg); }
        }
        
        .header {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .back-btn {
            color: var(--neon-cyan);
            text-decoration: none;
            font-size: 1.25rem;
            transition: transform 0.2s;
        }
        
        .back-btn:active { transform: scale(0.9); }
        
        /* Override theme toggle position - move to right */
        .portal-theme-toggle {
            left: auto !important;
            right: 1rem !important;
        }
        
        .content {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        h1 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--neon-cyan), var(--neon-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        h2 {
            font-size: 1.1rem;
            margin-top: 2rem;
            margin-bottom: 0.75rem;
            color: var(--neon-cyan);
        }
        
        p {
            margin-bottom: 1rem;
            color: var(--text-secondary);
        }
        
        ul {
            margin-bottom: 1.5rem;
            color: var(--text-secondary);
            padding-left: 1.5rem;
        }
        
        li {
            margin-bottom: 0.5rem;
        }
        
        .update-date {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
            font-size: 0.875rem;
            color: var(--text-muted);
            text-align: center;
        }
        
        @media (min-width: 768px) {
            .content { padding: 2rem; }
            .header { padding: 1.5rem 2rem; }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="javascript:history.back()" class="back-btn">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 700; flex: 1; background: none; -webkit-text-fill-color: inherit;">Syarat & Ketentuan</h1>
    </div>
    
    <div class="content">
        <h1>Syarat dan Ketentuan Layanan</h1>
        
        <h2>1. Pendahuluan</h2>
        <p>Selamat datang di layanan Internet Gembok Net. Dengan melakukan pembayaran dan menggunakan layanan kami, Anda dianggap telah membaca, memahami, dan menyetujui seluruh Syarat dan Ketentuan ini.</p>
        
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
        
        <h2>5. Penghentian Layanan</h2>
        <p>Kami berhak menghentikan layanan tanpa pemberitahuan jika pelanggan melanggar ketentuan yang berlaku atau menggunakan layanan untuk tujuan yang melanggar hukum.</p>
        
        <h2>6. Batasan Tanggung Jawab</h2>
        <p>Kami tidak bertanggung jawab atas kerugian yang timbul akibat gangguan layanan yang disebabkan oleh force majeure, termasuk namun tidak terbatas pada bencana alam, gangguan listrik, atau kondisi darurat lainnya.</p>
        
        <h2>7. Privasi Data</h2>
        <p>Kami berkomitmen untuk melindungi data pribadi pelanggan sesuai dengan peraturan perundang-undangan yang berlaku di Indonesia. Data pelanggan hanya akan digunakan untuk keperluan operasional layanan.</p>
        
        <h2>8. Perubahan Ketentuan</h2>
        <p>Kami berhak mengubah Syarat dan Ketentuan ini sewaktu-waktu tanpa pemberitahuan sebelumnya. Perubahan akan berlaku efektif segera setelah dipublikasikan di portal pelanggan.</p>
        
        <h2>9. Hubungi Kami</h2>
        <p>Jika Anda memiliki pertanyaan mengenai Syarat dan Ketentuan ini, silakan hubungi layanan pelanggan kami melalui kontak yang tersedia di dashboard portal.</p>
        
        <div class="update-date">
            <i class="fas fa-calendar-alt"></i> Terakhir diperbarui: <?= date('d F Y') ?>
        </div>
    </div>
    
    <?= $this->include('portal/_mobile_nav') ?>
</body>
</html>
