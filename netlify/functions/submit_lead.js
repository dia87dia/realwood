const mysql = require('mysql2/promise');
const axios = require('axios');

// Database configuration from environment variables
const dbConfig = {
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
};

// Telegram Bot configuration from environment variables
const TELEGRAM_BOT_TOKEN = process.env.TELEGRAM_BOT_TOKEN;
const TELEGRAM_CHAT_ID = process.env.TELEGRAM_CHAT_ID;

exports.handler = async (event, context) => {
  // Handle CORS preflight
  if (event.httpMethod === 'OPTIONS') {
    return {
      statusCode: 200,
      headers: {
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'POST, OPTIONS',
        'Access-Control-Allow-Headers': 'Content-Type'
      },
      body: ''
    };
  }

  if (event.httpMethod !== 'POST') {
    return {
      statusCode: 405,
      body: JSON.stringify({ error: 'Method not allowed' })
    };
  }

  try {
    const body = JSON.parse(event.body);
    const { phone, type, volume, message } = body;

    // Validate required fields
    if (!phone) {
      return {
        statusCode: 400,
        headers: { 'Access-Control-Allow-Origin': '*' },
        body: JSON.stringify({ error: 'Телефон обязателен' })
      };
    }

    // 1. Save to Database
    let leadId = 'N/A';
    try {
      const connection = await mysql.createConnection(dbConfig);
      
      // Based on the provided schema in submit.php
      // Table: requests (client_name, phone, id_product, comment, request_date, status)
      const fullComment = `Тип: ${type || 'Опт'}\nОбъём: ${volume || 'Не указан'}\nКомментарий: ${message || ''}`;
      
      const query = 'INSERT INTO requests (client_name, phone, comment, request_date, status) VALUES (NULL, ?, ?, NOW(), ?)';
      const [result] = await connection.execute(query, [phone, fullComment, 'новая']);
      leadId = result.insertId;
      
      await connection.end();
    } catch (dbError) {
      console.error('Database Error:', dbError);
      // We continue even if DB fails to ensure Telegram notification is sent
    }

    // 2. Send Telegram notification
    await sendTelegramNotification(leadId, phone, type, volume, message);

    return {
      statusCode: 200,
      headers: { 'Access-Control-Allow-Origin': '*' },
      body: JSON.stringify({
        success: true,
        message: 'Заявка успешно отправлена. Менеджер свяжется с вами в ближайшее время.',
        leadId: leadId
      })
    };
  } catch (error) {
    console.error('Error:', error);
    return {
      statusCode: 500,
      headers: { 'Access-Control-Allow-Origin': '*' },
      body: JSON.stringify({
        error: 'Внутренняя ошибка сервера',
        details: error.message
      })
    };
  }
};

async function sendTelegramNotification(leadId, phone, type, volume, message) {
  try {
    const text = `🔥 <b>Новая заявка #${leadId}</b>\n\n` +
                 `📞 <b>Телефон:</b> ${phone}\n` +
                 `📦 <b>Формат:</b> ${type || 'Опт'}\n` +
                 (volume ? `📊 <b>Объём:</b> ${volume}\n` : '') +
                 (message ? `💬 <b>Комментарий:</b> ${message}\n` : '') +
                 `\n⏰ ${new Date().toLocaleString('ru-RU', { timeZone: 'Europe/Minsk' })}`;

    const url = `https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage`;
    
    await axios.post(url, {
      chat_id: TELEGRAM_CHAT_ID,
      text: text,
      parse_mode: 'HTML'
    });

    console.log('Telegram notification sent successfully');
  } catch (error) {
    console.error('Error sending Telegram notification:', error);
    // Don't throw here to not break the main handler
  }
}