// ============================================
// Обработчик контактной формы (отправка на Beget)
// ============================================
(function() {
    const form = document.querySelector('[data-contact-form]');
    if (!form) return;

    const submitBtn = form.querySelector('[data-submit-btn]');
    const resultBox = form.querySelector('[data-form-result]');
    const phoneInput = form.querySelector('#phone');

    // Маска телефона
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,2})(\d{0,3})(\d{0,2})(\d{0,2})/);
            e.target.value = !x[2] ? '+375' + x[1] : '+375 (' + x[2] + ') ' + x[3] + (x[4] ? '-' + x[4] : '') + (x[5] ? '-' + x[5] : '');
        });
    }

    // Отправка формы на Beget
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Клиентская валидация
        const phone = phoneInput ? phoneInput.value : '';
        const phoneDigits = phone.replace(/\D/g, '');
        
        if (phoneDigits.length < 10) {
            showResult('Введите корректный номер телефона (минимум 10 цифр)', 'error');
            if (phoneInput) phoneInput.focus();
            return;
        }

        // Блокируем кнопку
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Отправка...';
        showResult('', '');

        const formData = new FormData(form);

        try {
            // Отправка на Beget
            const response = await fetch('https://j29257jc.beget.tech/submit.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showResult(data.message, 'success');
                form.reset();
            } else {
                showResult(data.message || 'Ошибка при отправке', 'error');
            }
        } catch (error) {
            showResult('Ошибка соединения. Проверьте интернет или свяжитесь с нами через Telegram.', 'error');
            console.error('Form error:', error);
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });

    function showResult(text, type) {
        if (!resultBox) return;
        resultBox.textContent = text;
        resultBox.className = 'form-result';
        if (type) resultBox.classList.add('form-result--' + type);
    }
})();