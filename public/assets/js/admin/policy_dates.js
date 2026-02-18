document.addEventListener('DOMContentLoaded', function () {
    // --- Configuration & Selectors ---
    // EasyAdmin field IDs often follow the pattern: Policy_fieldName
    const selectors = {
        commencementDate: '#Policy_commencementDate',
        policyTerm: '#Policy_policyTerm',
        premiumMode: '#Policy_premiumMode',
        maturityDate: '#Policy_maturityDate',
        nextDueDate: '#Policy_nextDueDate'
    };

    // Helper: Get element by selector
    const getEl = (selector) => document.querySelector(selector);

    // --- Core Logic ---

    function calculateMaturityDate() {
        const docInput = getEl(selectors.commencementDate);
        const termInput = getEl(selectors.policyTerm);
        const maturityInput = getEl(selectors.maturityDate);

        if (!docInput || !termInput || !maturityInput) return;

        const docValue = docInput.value; // YYYY-MM-DD
        const termValue = parseInt(termInput.value, 10);

        if (docValue && !isNaN(termValue)) {
            const date = new Date(docValue);
            // Add years
            date.setFullYear(date.getFullYear() + termValue);

            // Format to YYYY-MM-DD for input[type="date"]
            maturityInput.value = date.toISOString().split('T')[0];
        }
    }

    function calculateNextDueDate() {
        const docInput = getEl(selectors.commencementDate);
        const modeInput = getEl(selectors.premiumMode); // Ensure this is the SELECT element
        const nextDueInput = getEl(selectors.nextDueDate);

        if (!docInput || !modeInput || !nextDueInput) return;

        const docValue = docInput.value;
        const modeValue = modeInput.value; // YYYY-MM-DD

        if (docValue && modeValue) {
            let nextDueDate = new Date(docValue);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Determine interval in months based on mode
            // Mode values based on Policy Entity constraints/choices
            let monthsToAdd = 0;
            switch (modeValue) {
                case 'YLY':
                case 'YEARLY':
                    monthsToAdd = 12;
                    break;
                case 'HLY':
                case 'HALF-YEARLY':
                    monthsToAdd = 6;
                    break;
                case 'QLY':
                case 'QUARTERLY':
                    monthsToAdd = 3;
                    break;
                case 'NACH':
                case 'MLY':
                case 'MONTHLY':
                    monthsToAdd = 1;
                    break;
                case 'SINGLE':
                    nextDueDate = null; // No next due date for single premium
                    break;
                default:
                    monthsToAdd = 0;
            }

            if (monthsToAdd > 0) {
                // Loop to find the next due date >= Today
                // Logic mirrors PHP: 
                // 1. Start at DOC
                // 2. Add interval until >= Today

                // Initial increment from DOC (Review: usually first premium is at DOC, so next is DOC + Interval)
                addMonths(nextDueDate, monthsToAdd);

                // If the calculated date is already in the past, keep adding
                // Safety break to prevent infinite loops (e.g. if logic fails)
                let safetyCounter = 0;
                while (nextDueDate < today && safetyCounter < 1000) {
                    addMonths(nextDueDate, monthsToAdd);

                    // Optimization: If year is far behind, jump years? 
                    // JS Date handling is fast enough for typical policy durations (e.g. 50 years = 600 loops for monthly)
                    safetyCounter++;
                }

                nextDueInput.value = nextDueDate.toISOString().split('T')[0];

            } else if (modeValue === 'SINGLE') {
                nextDueInput.value = ''; // Clear if single
            }
        }
    }

    // Helper: Add months to a date object correctly handling end-of-month changes
    // e.g. Jan 31 + 1 month -> Feb 28
    function addMonths(date, months) {
        const d = date.getDate();
        date.setMonth(date.getMonth() + months);
        if (date.getDate() != d) {
            date.setDate(0);
        }
        return date;
    }


    // --- Event Listeners ---

    // Attach listeners to inputs
    const inputsToWatch = [
        selectors.commencementDate,
        selectors.policyTerm,
        selectors.premiumMode
    ];

    inputsToWatch.forEach(selector => {
        const el = getEl(selector);
        if (el) {
            el.addEventListener('change', () => {
                calculateMaturityDate();
                calculateNextDueDate();
            });

            // Also listen for keyup/input for immediate feedback on text inputs
            if (el.tagName === 'INPUT') {
                el.addEventListener('input', () => {
                    // Debounce could be added here if needed, but simple calculations are fast
                    calculateMaturityDate();
                    calculateNextDueDate();
                });
            }
        }
    });

    // Run once on load to populate if data exists (e.g. edit mode)
    calculateMaturityDate();
    calculateNextDueDate();

});
