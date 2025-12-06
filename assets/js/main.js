// Main JavaScript for Notely

// Task checkbox handler
document.addEventListener('DOMContentLoaded', function() {
    // Handle task checkboxes
    const taskCheckboxes = document.querySelectorAll('input[type="checkbox"][data-task-id]');
    taskCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const taskId = this.getAttribute('data-task-id');
            const status = this.checked ? 'completed' : 'pending';
            
            const baseUrl = window.location.origin + '/Notely/';
            fetch(baseUrl + 'api/update_task_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `task_id=${taskId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Optionally reload or update UI
                    if (status === 'completed') {
                        this.closest('.task-item').style.opacity = '0.6';
                    } else {
                        this.closest('.task-item').style.opacity = '1';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.checked = !this.checked; // Revert checkbox
            });
        });
    });
    
    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    
    if (mobileMenuToggle && mainNav) {
        mobileMenuToggle.addEventListener('click', function() {
            mainNav.classList.toggle('active');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!mainNav.contains(event.target) && !mobileMenuToggle.contains(event.target)) {
                mainNav.classList.remove('active');
            }
        });
    }
    
    // Auto-scroll chat to bottom
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
});

// Check for reminders periodically
setInterval(function() {
    checkReminders();
}, 60000); // Check every minute

function checkReminders() {
    const baseUrl = window.location.origin + '/Notely/';
    fetch(baseUrl + 'api/check_reminders.php')
        .then(response => response.json())
        .then(data => {
            if (data.hasReminders) {
                // Show notification badge or alert
                updateNotificationBadge();
            }
        })
        .catch(error => console.error('Error checking reminders:', error));
}

function updateNotificationBadge() {
    const baseUrl = window.location.origin + '/Notely/';
    fetch(baseUrl + 'api/get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('.badge');
            if (data.count > 0) {
                if (badge) {
                    badge.textContent = data.count;
                    badge.style.display = 'flex';
                } else {
                    // Create badge if it doesn't exist
                    const notifIcon = document.querySelector('.notification-icon');
                    if (notifIcon) {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'badge';
                        newBadge.textContent = data.count;
                        notifIcon.appendChild(newBadge);
                    }
                }
            } else {
                if (badge) {
                    badge.style.display = 'none';
                }
            }
        })
        .catch(error => console.error('Error updating badge:', error));
}

// Update notification badge on page load
document.addEventListener('DOMContentLoaded', updateNotificationBadge);

