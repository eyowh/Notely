// Schedule form validation
document.addEventListener('DOMContentLoaded', function() {
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const startTimeError = document.getElementById('start_time_error');
    const endTimeError = document.getElementById('end_time_error');
    const scheduleForm = document.getElementById('scheduleForm');
    
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
    
    if (startTimeInput) {
        // Set minimum start_time to current time (only for new schedules, not edit)
        // Check if this is edit mode by looking for existing value that's in the past
        const isEditMode = startTimeInput.value && new Date(startTimeInput.value) < new Date();
        
        if (!isEditMode) {
            const currentDateTime = getCurrentDateTime();
            startTimeInput.min = currentDateTime;
        }
        
        // Show error for start_time
        function showStartTimeError() {
            if (startTimeError) {
                startTimeError.style.display = 'block';
                startTimeInput.style.borderColor = 'var(--danger-color)';
            }
        }
        
        // Hide error for start_time
        function hideStartTimeError() {
            if (startTimeError) {
                startTimeError.style.display = 'none';
                startTimeInput.style.borderColor = '';
            }
        }
        
        // Validate start_time when it changes (only for new schedules)
        startTimeInput.addEventListener('change', function() {
            const isEditMode = startTimeInput.getAttribute('data-is-edit') === 'true' || 
                              (startTimeInput.value && new Date(startTimeInput.value) < new Date(getCurrentDateTime()));
            
            if (!isEditMode) {
                const currentDateTime = getCurrentDateTime();
                if (startTimeInput.value < currentDateTime) {
                    showStartTimeError();
                } else {
                    hideStartTimeError();
                    // Update end_time min if start_time is valid
                    if (endTimeInput) {
                        updateEndTimeMin();
                    }
                }
            } else {
                // For edit mode, just update end_time min
                if (endTimeInput) {
                    updateEndTimeMin();
                }
            }
        });
        
        // Validate start_time on form submit (only for new schedules)
        if (scheduleForm) {
            scheduleForm.addEventListener('submit', function(e) {
                // Check if this is edit mode
                const isEditMode = startTimeInput.getAttribute('data-is-edit') === 'true' || 
                                  (startTimeInput.value && new Date(startTimeInput.value) < new Date(getCurrentDateTime()));
                
                if (!isEditMode) {
                    const currentDateTime = getCurrentDateTime();
                    if (startTimeInput.value < currentDateTime) {
                        e.preventDefault();
                        showStartTimeError();
                        startTimeInput.focus();
                        alert('Waktu mulai tidak boleh kurang dari waktu sekarang!');
                        return false;
                    }
                }
            });
        }
    }
    
    if (startTimeInput && endTimeInput) {
        // Set minimum end_time based on start_time
        function updateEndTimeMin() {
            if (startTimeInput.value) {
                endTimeInput.min = startTimeInput.value;
                
                // If end_time is set and less than start_time, clear it
                if (endTimeInput.value && endTimeInput.value < startTimeInput.value) {
                    endTimeInput.value = '';
                    showEndTimeError();
                }
            }
        }
        
        // Show error message for end_time
        function showEndTimeError() {
            if (endTimeError) {
                endTimeError.style.display = 'block';
                endTimeInput.style.borderColor = 'var(--danger-color)';
            }
        }
        
        // Hide error message for end_time
        function hideEndTimeError() {
            if (endTimeError) {
                endTimeError.style.display = 'none';
                endTimeInput.style.borderColor = '';
            }
        }
        
        // Update min when start_time changes
        startTimeInput.addEventListener('change', function() {
            updateEndTimeMin();
            
            // Validate end_time if it's already set
            if (endTimeInput.value && endTimeInput.value < startTimeInput.value) {
                showEndTimeError();
            } else {
                hideEndTimeError();
            }
        });
        
        // Validate end_time when it changes
        endTimeInput.addEventListener('change', function() {
            if (endTimeInput.value && startTimeInput.value) {
                if (endTimeInput.value < startTimeInput.value) {
                    showEndTimeError();
                } else {
                    hideEndTimeError();
                }
            } else {
                hideEndTimeError();
            }
        });
        
        // Validate on form submit
        if (scheduleForm) {
            scheduleForm.addEventListener('submit', function(e) {
                // Check start_time first
                const currentDateTime = getCurrentDateTime();
                if (startTimeInput.value < currentDateTime) {
                    e.preventDefault();
                    if (startTimeError) {
                        startTimeError.style.display = 'block';
                        startTimeInput.style.borderColor = 'var(--danger-color)';
                    }
                    startTimeInput.focus();
                    alert('Waktu mulai tidak boleh kurang dari waktu sekarang!');
                    return false;
                }
                
                // Then check end_time
                if (endTimeInput.value && startTimeInput.value) {
                    if (endTimeInput.value < startTimeInput.value) {
                        e.preventDefault();
                        showEndTimeError();
                        endTimeInput.focus();
                        alert('Waktu selesai tidak boleh kurang dari waktu mulai!');
                        return false;
                    }
                }
            });
        }
        
        // Initialize min value on page load
        updateEndTimeMin();
    }
});

