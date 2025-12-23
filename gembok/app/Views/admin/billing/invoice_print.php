<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= str_pad($invoice['id'], 6, '0', STR_PAD_LEFT) ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace; /* Font struk */
            font-size: 14px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
        }
        .invoice-box {
            max-width: 80mm; /* Ukuran kertas struk thermal standard 80mm */
            margin: auto;
            border: 1px solid #eee;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .header h2 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 12px; }
        
        .details { margin-bottom: 15px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .label { font-weight: bold; }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            border-bottom: 1px dashed #000;
        }
        .items-table th { text-align: left; border-bottom: 1px solid #000; padding: 5px 0; }
        .items-table td { padding: 5px 0; }
        .text-right { text-align: right; }
        
        .total-section {
            border-top: 1px dashed #000;
            padding-top: 10px;
            margin-bottom: 20px;
        }
        
        .footer {
            text-align: center;
            font-size: 12px;
            margin-top: 20px;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }
        
        .status-paid {
            border: 2px solid #000;
            padding: 5px 10px;
            transform: rotate(-10deg);
            display: inline-block;
            font-weight: bold;
            font-size: 16px;
            margin-top: 10px;
        }
        
        @media print {
            .invoice-box { border: none; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">🖨️ Cetak Struk</button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer;">❌ Tutup</button>
    </div>

    <div class="invoice-box">
        <div class="header">
            <h2><?= $company['app_name'] ?? 'GEMBOK APP' ?></h2>
            <p><?= $company['company_address'] ?? 'Alamat Kantor Tidak Diset' ?></p>
            <p>Telp: <?= $company['company_phone'] ?? '-' ?></p>
        </div>
        
        <div class="details">
            <div class="row">
                <span class="label">No. Inv:</span>
                <span>#<?= str_pad($invoice['id'], 6, '0', STR_PAD_LEFT) ?></span>
            </div>
            <div class="row">
                <span class="label">Tanggal:</span>
                <span><?= date('d/m/Y H:i', strtotime($invoice['created_at'])) ?></span>
            </div>
            <div class="row">
                <span class="label">Pelanggan:</span>
                <span><?= esc($invoice['customer_name']) ?></span>
            </div>
             <div class="row">
                <span class="label">ID Pel:</span>
                <span><?= esc($invoice['pppoe_username']) ?></span>
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Deskripsi</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <?= esc($invoice['description']) ?><br>
                        <small>Paket: <?= esc($invoice['package_name']) ?></small>
                    </td>
                    <td class="text-right">Rp <?= number_format($invoice['amount'], 0, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>
        
        <div class="total-section">
            <div class="row" style="font-weight: bold; font-size: 16px;">
                <span>TOTAL BAYAR:</span>
                <span>Rp <?= number_format($invoice['amount'], 0, ',', '.') ?></span>
            </div>
            
            <div style="text-align: center; margin-top: 15px;">
                <?php if ($invoice['paid']): ?>
                    <div class="status-paid">LUNAS</div>
                    <p style="font-size: 11px; margin-top: 5px;">Tgl Bayar: <?= date('d/m/Y', strtotime($invoice['updated_at'])) ?></p>
                <?php else: ?>
                    <div style="border: 1px solid #000; padding: 5px; background: #eee;">BELUM LUNAS</div>
                    <p style="font-size: 11px; margin-top: 5px;">Jatuh Tempo: <?= date('d/m/Y', strtotime($invoice['due_date'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="footer">
            <p>Terima kasih atas pembayaran Anda.</p>
            <p>Simpan struk ini sebagai bukti pembayaran yang sah.</p>
            <small>Dicetak pada: <?= date('d/m/Y H:i:s') ?></small>
        </div>
    </div>

</body>
</html>
