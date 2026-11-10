// Shop Assistant: vanilla JS for the chat UI.
document.addEventListener('DOMContentLoaded', () => {
    // Show the latest message.
    const messages = document.getElementById('chat-messages');
    if (messages) {
        messages.scrollTop = messages.scrollHeight;
    }

    const form = document.querySelector('[data-chat-form]');
    if (!form) {
        return;
    }
    const input = form.querySelector('[data-chat-input]');
    const button = form.querySelector('[data-chat-submit]');

    // While the agent runs (one full request), block double submits.
    form.addEventListener('submit', () => {
        button.disabled = true;
        button.textContent = button.dataset.busyText;
        input.readOnly = true;
    });
});
