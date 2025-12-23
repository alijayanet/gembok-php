<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?> - Gembok</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f5f7fa;
            padding: 2rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .header h1 {
            font-size: 1.75rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 0.9375rem;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-bottom: 1rem;
            transition: background 0.2s;
        }
        
        .back-btn:hover {
            background: #2980b9;
        }
        
        .section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .webhook-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }
        
        .webhook-card:hover {
            border-color: #3498db;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.1);
        }
        
        .webhook-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .webhook-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .webhook-info h3 {
            font-size: 1.125rem;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        
        .webhook-info p {
            color: #7f8c8d;
            font-size: 0.875rem;
        }
        
        .url-container {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .url-text {
            flex: 1;
            font-family: 'Courier New', monospace;
            font-size: 0.9375rem;
            color: #2c3e50;
            word-break: break-all;
        }
        
        .copy-btn {
            padding: 0.75rem 1.5rem;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .copy-btn:hover {
            background: #2980b9;
        }
        
        .copy-btn.copied {
            background: #27ae60;
        }
        
        .method-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            background: #e74c3c;
            color: white;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .info-box {
            background: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 1rem 1.25rem;
            border-radius: 6px;
            margin-top: 1rem;
        }
        
        .info-box p {
            color: #2c3e50;
            font-size: 0.875rem;
            line-height: 1.6;
        }
        
        .api-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .api-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            transition: all 0.3s;
        }
        
        .api-card:hover {
            border-color: #3498db;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.1);
        }
        
        .api-icon {
            width: 40px;
            height: 40px;
            background: #3498db;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }
        
        .api-card h4 {
            font-size: 1rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .api-card p {
            color: #7f8c8d;
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
        }
        
        .api-url {
            font-family: 'Courier New', monospace;
            font-size: 0.8125rem;
            color: #3498db;
            word-break: break-all;
            background: white;
            padding: 0.5rem;
            border-radius: 4px;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .header, .section {
                padding: 1.5rem;
            }
            
            .webhook-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .url-container {
                flex-direction: column;
            }
            
            .copy-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="<?= base_url('admin/dashboard') ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Kembali ke Dashboard
        </a>

        <div class="header">
            <h1><i class="fas fa-plug"></i> <?= esc($title) ?></h1>
            <p>Webhook URLs dan API endpoints untuk integrasi dengan sistem eksternal</p>
        </div>

        <!-- Webhooks Section -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-webhook"></i> Webhook URLs
            </h2>

            <?php foreach ($webhooks as $key => $webhook): ?>
                <div class="webhook-card">
                    <div class="webhook-header">
                        <div class="webhook-icon" style="background: <?= $webhook['color'] ?>">
                            <i class="<?= $webhook['icon'] ?>"></i>
                        </div>
                        <div class="webhook-info">
                            <h3><?= $webhook['title'] ?></h3>
                            <p><?= $webhook['description'] ?></p>
                        </div>
                    </div>

                    <div class="url-container">
                        <div class="url-text" id="url-<?= $key ?>">
                            <?= $webhook['url'] ?>
                        </div>
                        <button class="copy-btn" onclick="copyToClipboard('url-<?= $key ?>', this)">
                            <i class="fas fa-copy"></i>
                            Copy
                        </button>
                    </div>

                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <span class="method-badge"><?= $webhook['method'] ?></span>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="info-box">
                <p>
                    <i class="fas fa-info-circle"></i>
                    <strong>Catatan:</strong> Pastikan webhook endpoint dapat diakses dari internet. 
                    Untuk testing lokal, gunakan tools seperti ngrok atau expose.
                </p>
            </div>
        </div>

        <!-- API Endpoints Section -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-code"></i> API Endpoints
            </h2>

            <div class="api-grid">
                <?php foreach ($apiEndpoints as $key => $api): ?>
                    <div class="api-card">
                        <div class="api-icon">
                            <i class="<?= $api['icon'] ?>"></i>
                        </div>
                        <h4><?= $api['title'] ?></h4>
                        <p><?= $api['description'] ?></p>
                        <div style="margin-bottom: 0.5rem;">
                            <span class="method-badge" style="background: #9b59b6"><?= $api['method'] ?></span>
                        </div>
                        <div class="api-url"><?= $api['url'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="info-box" style="margin-top: 1.5rem;">
                <p>
                    <i class="fas fa-key"></i>
                    <strong>Authentication:</strong> API endpoints memerlukan authentication token. 
                    Generate token di menu User Management atau gunakan session-based auth untuk internal calls.
                </p>
            </div>
        </div>

        <!-- Configuration Info -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-cog"></i> Configuration
            </h2>

            <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1rem;">
                <p style="color: #856404; font-size: 0.875rem; line-height: 1.6;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Base URL:</strong> <?= $baseUrl ?>
                    <br><br>
                    URL di atas otomatis terdeteksi dari server Anda. 
                    Jika berbeda dengan domain production, update di file <code>.env</code> dengan menambahkan:
                    <br><code style="background: white; padding: 0.25rem 0.5rem; border-radius: 4px; margin-top: 0.5rem; display: inline-block;">app.baseURL = 'https://gembok.alijaya.net/'</code>
                </p>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(elementId, button) {
            const text = document.getElementById(elementId).textContent.trim();
            
            // Modern clipboard API
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => {
                    showCopied(button);
                }).catch(err => {
                    // Fallback
                    fallbackCopy(text, button);
                });
            } else {
                // Fallback for older browsers
                fallbackCopy(text, button);
            }
        }

        function fallbackCopy(text, button) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            
            try {
                document.execCommand('copy');
                showCopied(button);
            } catch (err) {
                alert('Failed to copy: ' + err);
            }
            
            document.body.removeChild(textarea);
        }

        function showCopied(button) {
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> Copied!';
            button.classList.add('copied');
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('copied');
            }, 2000);
        }
    </script>
</body>
</html>
