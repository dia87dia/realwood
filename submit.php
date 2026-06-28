<?php
// ============================================
// submit.php - Обработчик контактной формы
// Принимает поля: phone, type, volume, message
// ============================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    // === 1. Получение данных из формы ===
    $phone   = trim($_POST['phone']   ?? '');
    $type    = trim($_POST['type']    ?? 'Опт');
    $volume  = trim($_POST['volume']  ?? '');
    $message = trim($_POST['message'] ?? '');

    // === 2. Валидация обязательных полей ===
    if (empty($phone)) {
        throw new Exception('Укажите контактный телефон');
    }
    
    $phoneDigits = preg_replace('/\D/', '', $phone);
    if (strlen($phoneDigits) < 10) {
        throw new Exception('Неверный формат телефона (минимум 10 цифр)');
    }

    // === 3. Определение id_product по типу фасовки ===
    $id_product = null;
    if (!empty($type)) {
        $stmt = $pdo->prepare("SELECT id_product FROM products WHERE name LIKE ? OR packaging_type LIKE ? LIMIT 1");
        $searchTerm = '%' . $type . '%';
        $stmt->execute([$searchTerm, $searchTerm]);
        $row = $stmt->fetch();
        if ($row) {
            $id_product = (int)$row['id_product'];
        }
    }

    // === 4. Вставка заявки в таблицу requests ===
    // client_name оставляем NULL, так как поле "Имя" отсутствует
    $stmt = $pdo->prepare("
        INSERT INTO requests (client_name, phone, id_product, comment, request_date, status) 
        VALUES (NULL, ?, ?, ?, NOW(), 'новая')
    ");
    
    // Формируем полный комментарий
    $fullComment = '';
    if (!empty($type))    $fullComment .= "Тип: $type\n";
    if (!empty($volume))  $fullComment .= "Объём: $volume\n";
    if (!empty($message)) $fullComment .= "Комментарий: $message";
    
    $stmt->execute([$phone, $id_product, $fullComment]);
    $lead_id = $pdo->lastInsertId();

    // === 5. Отправка уведомления в Telegram ===
    if (defined('TELEGRAM_TOKEN') && TELEGRAM_TOKEN !== '' && defined('TELEGRAM_CHAT_ID') && TELEGRAM_CHAT_ID !== '') {
        sendTelegram($lead_id, $phone, $type, $volume, $message);
    }

    // === 6. Отправка уведомления на Email ===
    if (defined('ADMIN_EMAIL') && ADMIN_EMAIL !== '') {
        sendEmail($lead_id, $phone, $type, $volume, $message);
    }

    // === 7. Успешный ответ ===
    echo json_encode([
        'success' => true,
        'message' => 'Заявка успешно отправлена. Менеджер свяжется с вами в ближайшее время.'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// ============================================
// ФУНКЦИЯ: Отправка уведомления в Telegram
// ============================================
function sendTelegram($lead_id, $phone, $type, $volume, $message) {
    $token   = '8832705519:AAFJJFphFVEogVObmO0RQgAylGjifZB1A68';
    $chat_id = '8832705519';

    $text  = "🔥 *Новая заявка #{$lead_id}*\n\n";
    $text .= "📞 *Телефон:* {$phone}\n";
    $text .= "📦 *Формат:* {$type}\n";

    if (!empty($volume))  $text .= "📊 *Объём:* {$volume}\n";
    if (!empty($message)) $text .= "💬 *Комментарий:* {$message}\n";

    $text .= "\n⏰ " . date('d.m.Y H:i');

    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $params = [
        'chat_id'    => $chat_id,
        'text'       => $text,
        'parse_mode' => 'Markdown'
    ];

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($params),
            'timeout' => 5
        ]
    ]);

    @file_get_contents($url, false, $context);
}

// ============================================
// ФУНКЦИЯ: Отправка HTML-письма на Email
// ============================================
function sendEmail($lead_id, $phone, $type, $volume, $message) {
    $to      = ADMIN_EMAIL;
    $from    = SITE_EMAIL;
    $subject = "🔔 Новая заявка #{$lead_id} с сайта РеалВуд";

    $html = "
    <html>
    <head><meta charset='utf-8'></head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; padding: 20px;'>
        <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);'>
            <div style='background: #f39c12; color: white; padding: 25px; text-align: center;'>
                <h2 style='margin: 0;'>🔔 Новая заявка #{$lead_id}</h2>
                <p style='margin: 5px 0 0; opacity: 0.9;'>Сайт ООО «РеалВуд»</p>
            </div>
            <div style='padding: 25px;'>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 12px; border-bottom: 1px solid #eee; font-weight: bold;'>📞 Телефон:</td>
                        <td style='padding: 12px; border-bottom: 1px solid #eee;'><a href='tel:{$phone}' style='color: #f39c12;'>{$phone}</a></td>
                    </tr>
                    <tr>
                        <td style='padding: 12px; border-bottom: 1px solid #eee; font-weight: bold;'>📦 Формат:</td>
                        <td style='padding: 12px; border-bottom: 1px solid #eee;'>{$type}</td>
                    </tr>";

    if (!empty($volume)) {
        $html .= "
                    <tr>
                        <td style='padding: 12px; border-bottom: 1px solid #eee; font-weight: bold;'>📊 Объём:</td>
                        <td style='padding: 12px; border-bottom: 1px solid #eee;'>{$volume}</td>
                    </tr>";
    }

    if (!empty($message)) {
        $html .= "
                    <tr>
                        <td style='padding: 12px; border-bottom: 1px solid #eee; font-weight: bold; vertical-align: top;'>💬 Комментарий:</td>
                        <td style='padding: 12px; border-bottom: 1px solid #eee;'>" . nl2br(htmlspecialchars($message)) . "</td>
                    </tr>";
    }

    $html .= "
                    <tr>
                        <td style='padding: 12px; font-weight: bold;'>⏰ Время:</td>
                        <td style='padding: 12px;'>" . date('d.m.Y H:i:s') . "</td>
                    </tr>
                </table>
            </div>
            <div style='background: #f9f9f9; padding: 15px; text-align: center; color: #888; font-size: 12px; border-top: 1px solid #eee;'>
                Это автоматическое уведомление с сайта realwood.by
            </div>
        </div>
    </body>
    </html>";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: {$from}\r\n";
    $headers .= "Reply-To: {$phone}\r\n";

    @mail($to, $subject, $html, $headers);
}
?>