const statusText = document.getElementById('status-text');
const toggleButton = document.getElementById('toggle-theme');
const refreshButton = document.getElementById('update-time');

function updateFooter() {
    const footer = document.querySelector('.footer');
    const now = new Date();
    footer.textContent = `Last checked: ${now.toLocaleString()}. Visit your server URL in a browser to see the deployed page.`;
}

function setStatus(message) {
    if (statusText) {
        statusText.textContent = message;
    }
}

if (toggleButton) {
    toggleButton.addEventListener('click', () => {
        document.body.classList.toggle('theme-light');
        const isLight = document.body.classList.contains('theme-light');
        setStatus(isLight ? 'Light theme enabled.' : 'Dark theme enabled.');
    });
}

if (refreshButton) {
    refreshButton.addEventListener('click', () => {
        updateFooter();
        setStatus('Footer timestamp updated.');
    });
}

function initPasswordToggles() {
    document.querySelectorAll('.password-toggle').forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.dataset.target;
            const input = document.getElementById(targetId);
            if (!input) {
                return;
            }

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.textContent = isPassword ? 'Hide' : 'Show';
        });
    });
}

initPasswordToggles();

setStatus('Page loaded successfully.');
updateFooter();
