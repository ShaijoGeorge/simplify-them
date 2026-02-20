document.addEventListener('DOMContentLoaded', function () {
    // --- Configuration & Selectors ---
    // EasyAdmin field IDs follow the pattern: Policy_fieldName
    const selectors = {
        commencementDate: '#Policy_commencementDate',
        policyTerm: '#Policy_policyTerm',
        premiumMode: '#Policy_premiumMode',
        maturityDate: '#Policy_maturityDate',
        nextDueDate: '#Policy_nextDueDate',
        basicPremium: '#Policy_basicPremium',
        gst: '#Policy_gst',
        totalPremium: '#Policy_totalPremium'
    };

    // GST Reform Date: 22 Sept 2025 - after this date, GST is 0%
    const GST_REFORM_DATE = new Date('2025-09-22');
    // Default GST rate for old regime (Endowment/Traditional plans)
    // Term plans are 18%, but we default to 4.5% since most policies are traditional.
    // Backend will finalize the correct rate on save based on plan type.
    const OLD_REGIME_DEFAULT_GST_RATE = 4.5;

    // Helper: Get element by selector
    const getEl = (selector) => document.querySelector(selector);

    // --- Date Calculations ---

    function calculateMaturityDate() {
        const docInput = getEl(selectors.commencementDate);
        const termInput = getEl(selectors.policyTerm);
        const maturityInput = getEl(selectors.maturityDate);

        if (!docInput || !termInput || !maturityInput) return;

        const docValue = docInput.value; // YYYY-MM-DD
        const termValue = parseInt(termInput.value, 10);

        if (docValue && !isNaN(termValue)) {
            const date = new Date(docValue);
            date.setFullYear(date.getFullYear() + termValue);
            maturityInput.value = date.toISOString().split('T')[0];
        }
    }

    function calculateNextDueDate() {
        const docInput = getEl(selectors.commencementDate);
        const modeInput = getEl(selectors.premiumMode);
        const nextDueInput = getEl(selectors.nextDueDate);

        if (!docInput || !modeInput || !nextDueInput) return;

        const docValue = docInput.value;
        const modeValue = modeInput.value;

        if (docValue && modeValue) {
            let nextDueDate = new Date(docValue);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

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
                    nextDueDate = null;
                    break;
                default:
                    monthsToAdd = 0;
            }

            if (monthsToAdd > 0) {
                addMonths(nextDueDate, monthsToAdd);

                let safetyCounter = 0;
                while (nextDueDate < today && safetyCounter < 1000) {
                    addMonths(nextDueDate, monthsToAdd);
                    safetyCounter++;
                }

                nextDueInput.value = nextDueDate.toISOString().split('T')[0];
            } else if (modeValue === 'SINGLE') {
                nextDueInput.value = '';
            }
        }
    }

    // --- Financial Calculations (GST & Total Premium) ---

    function calculateFinancials() {
        const docInput = getEl(selectors.commencementDate);
        const basicInput = getEl(selectors.basicPremium);
        const gstInput = getEl(selectors.gst);
        const totalInput = getEl(selectors.totalPremium);

        if (!basicInput || !gstInput || !totalInput) return;

        const basicValue = parseFloat(basicInput.value);
        if (isNaN(basicValue) || basicValue === 0) return;

        // Determine GST rate based on Commencement Date
        let gstRate = 0.0;
        if (docInput && docInput.value) {
            const docDate = new Date(docInput.value);
            if (docDate < GST_REFORM_DATE) {
                // Old Tax Regime (before Sept 2025) - default 4.5%
                // Backend will adjust for Term plans (18%) on save
                gstRate = OLD_REGIME_DEFAULT_GST_RATE;
            } else {
                // New Tax Regime (after Sept 2025) - 0% GST
                gstRate = 0.0;
            }
        }

        // Calculate GST amount and Total
        const calculatedGst = (basicValue * gstRate) / 100;
        const total = basicValue + calculatedGst;

        // Update the disabled fields
        gstInput.value = Math.round(calculatedGst);
        totalInput.value = Math.round(total);
    }

    // --- Helpers ---

    // Add months handling end-of-month edge cases (e.g. Jan 31 + 1 month -> Feb 28)
    function addMonths(date, months) {
        const d = date.getDate();
        date.setMonth(date.getMonth() + months);
        if (date.getDate() != d) {
            date.setDate(0);
        }
        return date;
    }

    function recalculateAll() {
        calculateMaturityDate();
        calculateNextDueDate();
        calculateFinancials();
    }

    // --- Event Listeners ---

    // Date & term fields trigger date recalculations
    const dateInputs = [
        selectors.commencementDate,
        selectors.policyTerm,
        selectors.premiumMode
    ];

    dateInputs.forEach(selector => {
        const el = getEl(selector);
        if (el) {
            el.addEventListener('change', recalculateAll);
            if (el.tagName === 'INPUT') {
                el.addEventListener('input', recalculateAll);
            }
        }
    });

    // Basic Premium field triggers financial recalculation
    const basicEl = getEl(selectors.basicPremium);
    if (basicEl) {
        basicEl.addEventListener('change', calculateFinancials);
        basicEl.addEventListener('input', calculateFinancials);
    }

    // Run once on load to populate if data exists (e.g. edit mode)
    recalculateAll();

});
