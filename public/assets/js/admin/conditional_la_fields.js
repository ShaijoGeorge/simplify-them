document.addEventListener('DOMContentLoaded', function() {
    // Find the actual checkbox inside our new wrapper class
    const toggleCheckbox = document.querySelector('.la-toggle-wrapper input[type="checkbox"]');
    
    // Find all rows that should be shown/hidden
    const conditionalFields = document.querySelectorAll('.conditional-la-field');
    
    // Find the Life Assured Name input (to check if it already has data during an Edit)
    const laNameInput = document.querySelector('.conditional-la-field input[name$="[lifeAssuredName]"]');

    if (!toggleCheckbox) {
        console.error('Conditional fields script: Toggle checkbox not found!');
        return;
    }

    // AUTO-CHECK ON EDIT: If there's already a name saved in the database, check the box automatically
    if (laNameInput && laNameInput.value.trim() !== '') {
        toggleCheckbox.checked = true;
    }

    // Logic to show/hide the fields
    function toggleFields() {
        const isChecked = toggleCheckbox.checked;
        
        conditionalFields.forEach(function(fieldRow) {
            if (isChecked) {
                fieldRow.style.display = 'block'; // Show the field row
            } else {
                fieldRow.style.display = 'none';  // Hide the field row
                
                // Clear the inputs if hidden so old data isn't accidentally submitted
                const inputs = fieldRow.querySelectorAll('input, select');
                inputs.forEach(input => {
                    // Only clear if it's not a select, or set select to empty string
                    if(input.tagName === 'SELECT') {
                        input.selectedIndex = 0;
                    } else {
                        input.value = '';
                    }
                });
            }
        });
    }

    // Run immediately on page load
    toggleFields();

    // Listen for clicks on the checkbox to toggle in real-time
    toggleCheckbox.addEventListener('change', toggleFields);
});