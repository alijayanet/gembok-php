<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GEMBOK - ISP Management System</title>
    <meta name="description" content="Akses cepat ke admin panel dan customer portal GEMBOK ISP Management System">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0a0a0f;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
        }

        /* Animated background - sama seperti admin login */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 30%, rgba(0, 245, 255, 0.05) 0%, transparent 50%),
                        radial-gradient(circle at 70% 70%, rgba(191, 0, 255, 0.05) 0%, transparent 50%);
            animation: backgroundMove 20s ease-in-out infinite;
            z-index: 0;
        }

        @keyframes backgroundMove {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-5%, -5%) rotate(5deg); }
        }

        .container {
            max-width: 480px;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .profile {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 4rem;
            border: 4px solid rgba(255, 255, 255, 0.3);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        h1 {
            color: #fff;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .version {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        .links {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .link-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 1.5rem;
            text-decoration: none;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .link-card:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .link-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            flex-shrink: 0;
        }

        .link-content {
            flex: 1;
            text-align: left;
        }

        .link-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
            display: block;
        }

        .link-desc {
            font-size: 0.9rem;
            opacity: 0.9;
            display: block;
        }

        .link-arrow {
            font-size: 1.5rem;
            opacity: 0.7;
            transition: transform 0.3s;
        }

        .link-card:hover .link-arrow {
            transform: translateX(5px);
        }

        .footer {
            text-align: center;
            margin-top: 2rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .footer-link {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 1.5rem;
            transition: all 0.3s;
        }

        .footer-link:hover {
            color: #fff;
            transform: scale(1.2);
        }

        @media (max-width: 480px) {
            .container {
                padding: 0;
            }

            h1 {
                font-size: 2rem;
            }

            .logo {
                width: 100px;
                height: 100px;
                font-size: 3rem;
            }

            .link-card {
                padding: 1.25rem;
            }

            .link-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }

            .link-title {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Profile Section -->
        <div class="profile">
            <div class="logo">🔐</div>
            <h1>GEMBOK</h1>
            <p class="subtitle">ISP Management System</p>
            <p class="version">v1.0 - CodeIgniter 4</p>
        </div>

        <!-- Links Section -->
        <div class="links">
            <!-- Admin Login -->
            <a href="/admin/login" class="link-card">
                <div class="link-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="link-content">
                    <span class="link-title">Admin Panel</span>
                    <span class="link-desc">Dashboard & Management</span>
                </div>
                <i class="fas fa-chevron-right link-arrow"></i>
            </a>

            <!-- Customer Portal -->
            <a href="/login" class="link-card">
                <div class="link-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="link-content">
                    <span class="link-title">Customer Portal</span>
                    <span class="link-desc">Cek tagihan & info paket</span>
                </div>
                <i class="fas fa-chevron-right link-arrow"></i>
            </a>

            <!-- GitHub -->
            <a href="https://github.com/alijayanet/gembok-php" target="_blank" class="link-card">
                <div class="link-icon">
                    <i class="fab fa-github"></i>
                </div>
                <div class="link-content">
                    <span class="link-title">GitHub Repository</span>
                    <span class="link-desc">Source code & documentation</span>
                </div>
                <i class="fas fa-external-link-alt link-arrow"></i>
            </a>

            <!-- Documentation -->
            <a href="https://github.com/alijayanet/gembok-php#readme" target="_blank" class="link-card">
                <div class="link-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="link-content">
                    <span class="link-title">Documentation</span>
                    <span class="link-desc">Panduan & tutorial lengkap</span>
                </div>
                <i class="fas fa-external-link-alt link-arrow"></i>
            </a>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; <?= date('Y') ?> GEMBOK</p>
            <div class="footer-links">
                <a href="https://github.com/alijayanet" target="_blank" class="footer-link" title="GitHub">
                    <i class="fab fa-github"></i>
                </a>
                <a href="https://wa.me/6281947215703" target="_blank" class="footer-link" title="WhatsApp">
                    <i class="fab fa-whatsapp"></i>
                </a>
                <a href="mailto:alijayanet@gmail.com" class="footer-link" title="Email">
                    <i class="fas fa-envelope"></i>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
