document.addEventListener('DOMContentLoaded', () => {
    const chatBox = document.getElementById('chat-box');
    const messageInput = document.getElementById('message-input');
    const sendButton = document.getElementById('send-btn');

    const userId = parseInt(chatBox.dataset.userId);
    const receiverId = chatBox.dataset.receiverId;

    function formatMessage(message) {
        const align = message.sender_id === userId ? 'end' : 'start';
        const bgClass = message.sender_id === userId ? 'bg-primary text-white' : 'bg-light text-dark';
        const senderName = message.sender_name;

        return `
            <div class="mb-3 d-flex justify-content-${align}">
              <div>
                <div class="text-muted small text-${align} mb-1">${message.createdAt}</div>
                <div class="p-2 rounded shadow-sm ${bgClass}" style="display: inline-block; max-width: 90%; word-break: break-word; overflow-wrap: break-word; white-space: normal;">


                    <strong>${senderName}</strong><br>
                    ${message.content}
                </div>
             </div>
            </div>
        `;
    }

    function loadMessages() {
        fetch(`/chat/messages/${receiverId}`)
            .then(response => response.json())
            .then(messages => {
                chatBox.innerHTML = messages.map(formatMessage).join('');
                chatBox.scrollTop = chatBox.scrollHeight;
            })
            .catch(console.error);
    }

    sendButton.addEventListener('click', () => {
        const content = messageInput.value.trim();
        if (content === '') return;

        fetch(`/chat/send/${receiverId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ content })
        })
        .then(response => {
            if (response.ok) {
                messageInput.value = '';
                loadMessages();
            } else {
                return response.json().then(data => {
                    alert(data.error || 'Erreur lors de l\'envoi du message');
                });
            }
        })
        .catch(console.error);
    });

    // Actualisation automatique toutes les 5 secondes
    setInterval(loadMessages, 5000);
    loadMessages();
});
