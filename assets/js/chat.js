// Chat functionality
document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('chatForm');
    const chatMessages = document.getElementById('chatMessages');
    
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to show new message
                    location.reload();
                } else {
                    alert('Gagal mengirim pesan: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengirim pesan');
            });
        });
    }
    
    // Auto-refresh chat messages every 3 seconds
    if (chatMessages) {
        setInterval(function() {
            const chatType = new URLSearchParams(window.location.search).get('type');
            const chatId = new URLSearchParams(window.location.search).get('id');
            
            if (chatType && chatId) {
                const baseUrl = window.location.origin + '/Notely/';
                fetch(`${baseUrl}api/get_messages.php?type=${chatType}&id=${chatId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.messages) {
                            // Only update if there are new messages
                            const currentMessageCount = chatMessages.children.length;
                            if (data.messages.length !== currentMessageCount) {
                                location.reload(); // Simple reload for now
                            }
                        }
                    })
                    .catch(error => console.error('Error fetching messages:', error));
            }
        }, 3000);
    }
});

