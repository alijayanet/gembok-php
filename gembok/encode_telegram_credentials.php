<?php
/**
 * Helper Script: Encode Telegram Credentials
 * 
 * Gunakan script ini untuk encode token dan chat ID baru
 * jika Anda ingin mengganti credentials di install.php
 * 
 * Usage:
 *   php encode_telegram_credentials.php
 */

echo "===========================================\n";
echo "  TELEGRAM CREDENTIALS ENCODER\n";
echo "===========================================\n\n";

// Input token
echo "Masukkan Bot Token (dari @BotFather):\n";
echo "Contoh: 123456789:ABCdefGHIjklMNOpqrsTUVwxyz\n";
echo "Token: ";
$botToken = trim(fgets(STDIN));

// Input chat ID
echo "\nMasukkan Chat ID (dari @userinfobot):\n";
echo "Contoh: 567858628\n";
echo "Chat ID: ";
$chatId = trim(fgets(STDIN));

// Validate input
if (empty($botToken) || empty($chatId)) {
    echo "\n❌ Error: Token dan Chat ID tidak boleh kosong!\n";
    exit(1);
}

// Encode
$encodedToken = base64_encode($botToken);
$encodedChatId = base64_encode($chatId);

// Display results
echo "\n===========================================\n";
echo "  HASIL ENCODING\n";
echo "===========================================\n\n";

echo "Original Bot Token:\n";
echo "  $botToken\n\n";

echo "Encoded Bot Token:\n";
echo "  $encodedToken\n\n";

echo "Original Chat ID:\n";
echo "  $chatId\n\n";

echo "Encoded Chat ID:\n";
echo "  $encodedChatId\n\n";

// Show code to copy
echo "===========================================\n";
echo "  COPY CODE INI KE install.php\n";
echo "===========================================\n\n";

echo "Ganti baris di install.php dengan:\n\n";
echo "```php\n";
echo "\$botToken = base64_decode('$encodedToken');\n";
echo "\$chatId = base64_decode('$encodedChatId');\n";
echo "```\n\n";

// Test decode
$decodedToken = base64_decode($encodedToken);
$decodedChatId = base64_decode($encodedChatId);

echo "===========================================\n";
echo "  VERIFIKASI\n";
echo "===========================================\n\n";

echo "Decoded Bot Token: " . ($decodedToken === $botToken ? "✅ MATCH" : "❌ ERROR") . "\n";
echo "Decoded Chat ID: " . ($decodedChatId === $chatId ? "✅ MATCH" : "❌ ERROR") . "\n";

echo "\n✅ Encoding berhasil!\n";
echo "Silakan copy code di atas ke install.php\n\n";

?>
