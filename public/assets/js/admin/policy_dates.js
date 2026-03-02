/**
 * policy_dates.js
 *
 * Auto-calculates Maturity Date, Next Due Date, GST and Total Premium
 * inside the EasyAdmin Policy create/edit form.
 *
 * GST Rate Matrix (mirrors Policy::calculateTotals()):
 *
 *  Regime       │ Plan category          │ Policy Year 1 │ Year 2+
 * ──────────────┼────────────────────────┼───────────────┼────────
 *  Old regime   │ Traditional*           │     4.50 %    │ 2.25 %
 *  (DOC before  │ Term                   │    18.00 %    │ 18.00 %
 *  22 Sep 2025) │ Single Premium         │     1.25 %    │  N/A
 * ──────────────┼────────────────────────┼───────────────┼────────
 *  New regime   │ All categories         │     0.00 %    │  0.00 %
 *  (DOC on/after│                        │               │
 *  22 Sep 2025) │                        │               │
 *
 * * Traditional = Endowment, Whole Life, Money Back, etc.
 *   (plan-type name does NOT contain the word "TERM").
 *
 * "Policy year" is 1-based: year 1 = 0 full years since DOC, year 2 = 1
 * full year since DOC, etc. — identical to Policy::calculateTotals().
 */
document.addEventListener('DOMContentLoaded', function () {

    // ── Field selectors (EasyAdmin uses entity name prefix) ──────────────────
    var selectors = {
        commencementDate : '#Policy_commencementDate',
        policyTerm       : '#Policy_policyTerm',
        premiumMode      : '#Policy_premiumMode',
        maturityDate     : '#Policy_maturityDate',
        nextDueDate      : '#Policy_nextDueDate',
        basicPremium     : '#Policy_basicPremium',
        gst              : '#Policy_gst',
        totalPremium     : '#Policy_totalPremium',
    };

    /** GST Reform Date: from this date onwards the rate is 0 %. */
    var GST_REFORM_DATE = new Date('2025-09-22');

    // ── Helper: query a single element ───────────────────────────────────────
    function getEl(selector) {
        return document.querySelector(selector);
    }

    // ── Helper: read the selected plan-type name from the LicPlan dropdown ───
    // EasyAdmin renders an <option> whose text contains "tableNumber – planName".
    // We piggyback on a data attribute if available, otherwise we attempt to
    // read the currently selected plan's type from a hidden field injected by
    // the server.  Fallback: empty string (treated as Traditional).
    function getPlanTypeName() {
        // The most reliable approach: EasyAdmin can expose a data attribute on
        // the plan select if you add it via configureFields().  Until then, we
        // look for a hidden input that the server may render as
        // <input type="hidden" id="Policy_planTypeName" value="TERM">.
        var hidden = document.querySelector('#Policy_planTypeName');
        if (hidden) {
            return hidden.value.toUpperCase();
        }

        // Secondary fallback: read from a data attribute on the select element.
        var planSelect = document.querySelector('#Policy_licPlan');
        if (planSelect) {
            var selected = planSelect.options[planSelect.selectedIndex];
            if (selected && selected.dataset && selected.dataset.planType) {
                return selected.dataset.planType.toUpperCase();
            }
        }

        return '';
    }

    // ── Determine current policy year (1-based) ───────────────────────────────
    // Mirrors the PHP logic in calculateTotals():
    //   fullYearsElapsed = floor(diff in years between DOC and today)
    //   policyYear = fullYearsElapsed + 1
    function getPolicyYear(docDateStr) {
        if (!docDateStr) return 1;

        var doc   = new Date(docDateStr);
        var today = new Date();
        today.setHours(0, 0, 0, 0);

        if (isNaN(doc.getTime())) return 1;

        // Compute full years elapsed (same algorithm as PHP's DateInterval->y)
        var years = today.getFullYear() - doc.getFullYear();
        var monthDiff = today.getMonth() - doc.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < doc.getDate())) {
            years--;
        }
        if (years < 0) years = 0;  // future DOC → still year 1

        return years + 1;  // 1-based
    }

    // ── Resolve GST rate from all relevant inputs ─────────────────────────────
    function resolveGstRate(docDateStr, premiumMode, planTypeName) {
        if (!docDateStr) return 0.0;

        var doc = new Date(docDateStr);
        if (isNaN(doc.getTime())) return 0.0;

        // New regime (on/after 22 Sep 2025) → always 0 %
        if (doc >= GST_REFORM_DATE) {
            return 0.0;
        }

        // Old regime logic
        var isTerm   = planTypeName.indexOf('TERM') !== -1;
        var isSingle = premiumMode === 'SINGLE';
        var policyYear = getPolicyYear(docDateStr);

        if (isTerm) {
            return 18.0;                              // Term: flat 18 % all years
        }

        if (isSingle) {
            return 1.25;                              // Single-premium endowment: 1.25 %
        }

        // Traditional (Endowment, Money Back, Whole Life …)
        return policyYear === 1 ? 4.5 : 2.25;
    }

    // ── Date Calculations ─────────────────────────────────────────────────────

    function calculateMaturityDate() {
        var docInput      = getEl(selectors.commencementDate);
        var termInput     = getEl(selectors.policyTerm);
        var maturityInput = getEl(selectors.maturityDate);

        if (!docInput || !termInput || !maturityInput) return;

        var docValue  = docInput.value;           // YYYY-MM-DD
        var termValue = parseInt(termInput.value, 10);

        if (docValue && !isNaN(termValue) && termValue > 0) {
            var date = new Date(docValue);
            date.setFullYear(date.getFullYear() + termValue);
            maturityInput.value = date.toISOString().split('T')[0];
        }
    }

    function calculateNextDueDate() {
        var docInput     = getEl(selectors.commencementDate);
        var modeInput    = getEl(selectors.premiumMode);
        var nextDueInput = getEl(selectors.nextDueDate);

        if (!docInput || !modeInput || !nextDueInput) return;

        var docValue  = docInput.value;
        var modeValue = modeInput.value;

        if (!docValue || !modeValue) return;

        var nextDueDate  = new Date(docValue);
        var today        = new Date();
        today.setHours(0, 0, 0, 0);

        var monthsToAdd = 0;
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
                nextDueInput.value = '';
                return;
            default:
                monthsToAdd = 0;
        }

        if (monthsToAdd > 0) {
            addMonths(nextDueDate, monthsToAdd);

            var safetyCounter = 0;
            while (nextDueDate < today && safetyCounter < 1000) {
                addMonths(nextDueDate, monthsToAdd);
                safetyCounter++;
            }

            nextDueInput.value = nextDueDate.toISOString().split('T')[0];
        }
    }

    // ── Financial Calculations (GST & Total Premium) ──────────────────────────

    function calculateFinancials() {
        var docInput   = getEl(selectors.commencementDate);
        var modeInput  = getEl(selectors.premiumMode);
        var basicInput = getEl(selectors.basicPremium);
        var gstInput   = getEl(selectors.gst);
        var totalInput = getEl(selectors.totalPremium);

        if (!basicInput || !gstInput || !totalInput) return;

        var basicValue = parseFloat(basicInput.value);
        if (isNaN(basicValue) || basicValue === 0) {
            gstInput.value  = '';
            totalInput.value = '';
            return;
        }

        var docDateStr   = docInput  ? docInput.value  : '';
        var premiumMode  = modeInput ? modeInput.value : '';
        var planTypeName = getPlanTypeName();

        var gstRate        = resolveGstRate(docDateStr, premiumMode, planTypeName);
        var calculatedGst  = (basicValue * gstRate) / 100;
        var total          = basicValue + calculatedGst;

        gstInput.value   = Math.round(calculatedGst);
        totalInput.value = Math.round(total);

        // Update the GST rate hint label if present (optional UI element)
        var rateHint = document.querySelector('#gst-rate-hint');
        if (rateHint) {
            var policyYear   = getPolicyYear(docDateStr);
            var docDate      = docDateStr ? new Date(docDateStr) : null;
            var isNewRegime  = docDate && docDate >= GST_REFORM_DATE;

            if (isNewRegime) {
                rateHint.textContent = 'New regime (post Sep-2025): 0% GST';
            } else {
                rateHint.textContent = 'Old regime — Policy Year ' + policyYear + ': ' + gstRate + '% GST';
            }
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Add months, clamping to end-of-month when needed (e.g. Jan 31 + 1 month → Feb 28). */
    function addMonths(date, months) {
        var d = date.getDate();
        date.setMonth(date.getMonth() + months);
        if (date.getDate() !== d) {
            date.setDate(0);  // roll back to last day of previous month
        }
        return date;
    }

    function recalculateAll() {
        calculateMaturityDate();
        calculateNextDueDate();
        calculateFinancials();
    }

    // ── Event Listeners ───────────────────────────────────────────────────────

    // Fields that affect date calculations AND GST (DOC also drives the regime check)
    [
        selectors.commencementDate,
        selectors.policyTerm,
        selectors.premiumMode,
    ].forEach(function (selector) {
        var el = getEl(selector);
        if (!el) return;
        el.addEventListener('change', recalculateAll);
        if (el.tagName === 'INPUT') {
            el.addEventListener('input', recalculateAll);
        }
    });

    // Basic Premium only affects financials
    var basicEl = getEl(selectors.basicPremium);
    if (basicEl) {
        basicEl.addEventListener('change', calculateFinancials);
        basicEl.addEventListener('input',  calculateFinancials);
    }

    // If the LIC Plan select exists, re-evaluate financials whenever the plan
    // changes (plan type may switch between TERM and Traditional).
    var planEl = document.querySelector('#Policy_licPlan');
    if (planEl) {
        planEl.addEventListener('change', calculateFinancials);
    }

    // Run once on page load to populate fields in edit mode
    recalculateAll();
});