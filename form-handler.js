document.addEventListener('DOMContentLoaded', function() {
  const form = document.querySelector('[data-contact-form]');
  const resultDiv = document.querySelector('[data-form-result]');
  
  if (!form) return;

  form.addEventListener('submit', async function(e) {
    e.preventDefault();

    const submitBtn = form.querySelector('button[type="submit"]');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    // Basic validation
    if (!data.phone) {
      showResult('Пожалуйста, введите номер телефона', 'error');
      return;
    }

    // UI Feedback
    const originalBtnText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Отправка...';
    showResult('', '');

    try {
      // Netlify Functions endpoint
      const response = await fetch('/.netlify/functions/submit_lead', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
      });

      const result = await response.json();

      if (response.ok && result.success) {
        showResult(result.message, 'success');
        form.reset();
      } else {
        showResult(result.error || 'Произошла ошибка при отправке', 'error');
      }
    } catch (error) {
      console.error('Submission error:', error);
      showResult('Ошибка сети. Попробуйте позже.', 'error');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = originalBtnText;
    }
  });

  function showResult(message, type) {
    if (!resultDiv) return;
    
    resultDiv.textContent = message;
    resultDiv.className = 'form-result';
    
    if (type === 'success') {
      resultDiv.style.color = '#27ae60';
      resultDiv.style.marginTop = '15px';
      resultDiv.style.fontWeight = 'bold';
    } else if (type === 'error') {
      resultDiv.style.color = '#e74c3c';
      resultDiv.style.marginTop = '15px';
      resultDiv.style.fontWeight = 'bold';
    }
  }
});
