<?php
// ============================================
// config.php - Конфигурация подключения и сервисов
// ============================================

// ===== 1. БАЗА ДАННЫХ =====
$db_host = 'localhost';
$db_name = 'j29257jc_marta';
$db_user = 'j29257jc';
$db_pass = '547896321Zz';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Ошибка подключения к базе данных']));
}

// ===== 2. TELEGRAM =====
// ⚠️ Если уведомления в Telegram не требуются, оставьте строки пустыми
define('TELEGRAM_TOKEN', '8832705519:AAFJJFphFVEogVObmO0RQgAylGjifZB1A68'); // ← ВАШ ТОКЕН
define('TELEGRAM_CHAT_ID', '943668314');  // ← ВАШ CHAT_ID

// ===== 3. EMAIL =====
define('ADMIN_EMAIL', 'nonamegta87@gmail.com');   // ← Email КУДА придут заявки
define('SITE_EMAIL',  'malaamarta87@gmail.com');  // ← Email ОТ КОГО (лучше на домене Beget)
?>