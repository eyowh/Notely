// Task form validation
document.addEventListener('DOMContentLoaded', function() {
    const dueDateInput = document.getElementById('due_date');
    const dueDateError = document.getElementById('due_date_error');
    const taskForm = document.getElementById('taskForm');
    
    // Get current datetime in format YYYY-MM-DDTHH:mm
    function getCurrentDateTime() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }
    
    if (dueDateInput) {
        // Check if this is edit mode
        const isEditMode = dueDateInput.getAttribute('data-is-edit') === 'true' || 
                          (dueDateInput.value && new Date(dueDateInput.value) < new Date(getCurrentDateTime()));
        
        // Set minimum due_date to current time (only for new tasks, not edit)
        if (!isEditMode) {
            const currentDateTime = getCurrentDateTime();
            dueDateInput.min = currentDateTime;
        }
        
        // Show error for due_date
        function showDueDateError() {
            if (dueDateError) {
                dueDateError.style.display = 'block';
                dueDateInput.style.borderColor = 'var(--danger-color)';
            }
        }
        
        // Hide error for due_date
        function hideDueDateError() {
            if (dueDateError) {
                dueDateError.style.display = 'none';
                dueDateInput.style.borderColor = '';
            }
        }
        
        // Validate due_date when it changes (only for new tasks)
        dueDateInput.addEventListener('change', function() {
            const isEditMode = dueDateInput.getAttribute('data-is-edit') === 'true' || 
                              (dueDateInput.value && new Date(dueDateInput.value) < new Date(getCurrentDateTime()));
            
            if (!isEditMode) {
                const currentDateTime = getCurrentDateTime();
                if (dueDateInput.value < currentDateTime) {
                    showDueDateError();
                } else {
                    hideDueDateError();
                }
            }
        });
        
        // Validate on form submit
        if (taskForm) {
            taskForm.addEventListener('submit', function(e) {
                // Check if this is edit mode
                const isEditMode = dueDateInput.getAttribute('data-is-edit') === 'true' || 
                                  (dueDateInput.value && new Date(dueDateInput.value) < new Date(getCurrentDateTime()));
                
                if (!isEditMode && dueDateInput.value) {
                    const currentDateTime = getCurrentDateTime();
                    if (dueDateInput.value < currentDateTime) {
                        e.preventDefault();
                        showDueDateError();
                        dueDateInput.focus();
                        alert('Deadline tidak boleh kurang dari waktu sekarang!');
                        return false;
                    }
                }
            });
        }
    }
});

