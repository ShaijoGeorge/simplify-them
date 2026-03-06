/**
 * policy_dates.js — EasyAdmin Policy Form
 *
 * Responsibilities:
 *  1. Live GST + Total preview as agent types Basic Premium / DOC / Mode
 *  2. Auto-calculate Next Due Date from DOC + Mode
 *  3. Fetch LicPlan flags (isSinglePremium, isLimitedPremium) when plan changes
 *  4. Lock / unlock fields based on plan flags
 *  5. Show a UI hint for Limited Premium plans
 *
 * GST Matrix (old regime, DOC < 22 Sep 2025):
 *   Single Premium plan  → 1.25 %
 *   Term plan            → 18 %
 *   Traditional (Yr 1)   → 4.5 %
 *   Traditional (Yr 2+)  → 2.25 %
 * New regime (DOC ≥ 22 Sep 2025) → 0 %
 */

(function () {
    'use strict';

    // ── Constants ─────────────────────────────────────────────────────────────
    var GST_REFORM_DATE = new Date('2025-09-22');

    // Plan flags cache (keyed by planId)
    var planFlagsCache = {};

    // Currently active plan flags (set after fetch)
    var currentPlanFlags = {
        isSinglePremium: false,
        isLimitedPremium: false,
        planTypeName: ''
    };

    // Modal rebate factors (must match Policy.php calculateModalPremium)
    var MODAL_FACTORS = {
        'YLY': 1.0, 'YEARLY': 1.0,
        'HLY': 0.51, 'HALF-YEARLY': 0.51,
        'QLY': 0.26, 'QUARTERLY': 0.26,
        'NACH': 0.0875, 'MLY': 0.0875, 'MONTHLY': 0.0875,
        'SINGLE': 1.0
    };

    // DOM field references (resolved once on DOMContentLoaded) ─────────────
    var $annualPremium, $basicPremium, $modalRebate, $gst, $totalPremium, $doc, $mode, $nextDue, $plan;

    // 1. PLAN FLAGS - fetch from AJAX endpoint

    function fetchPlanFlags(planId, callback) {
        if (!planId) {
            callback({});
            return;
        }

        if (planFlagsCache[planId]) {
            callback(planFlagsCache[planId]);
            return;
        }

        fetch('/admin/api/lic-plan/' + planId + '/flags', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (flags) {
                planFlagsCache[planId] = flags;
                callback(flags);
            })
            .catch(function () {
                // Silently fall back to empty flags
                callback({});
            });
    }

    // 2. MODAL PREMIUM CALCULATION

    /**
     * Live-calculate basicPremium = annualPremium × modal factor.
     * Mirrors Policy.php calculateModalPremium().
     */
    function recalculateModalPremium() {
        if (!$annualPremium || !$basicPremium) return;

        var annual = parseFloat($annualPremium.value) || 0;
        var mode = $mode ? $mode.value : '';

        if (annual <= 0 || !mode) return;

        var factor = MODAL_FACTORS[mode];
        if (factor === undefined) factor = 1.0;

        var modal = Math.round(annual * factor * 100) / 100;  // round to 2 dp
        $basicPremium.value = modal.toFixed(2);

        // Update Rebate Factor display
        if ($modalRebate) {
            $modalRebate.value = factor.toFixed(4);
        }

        // Chain: recalculate GST with the new basic premium
        recalculateGst();
    }

    // 3. GST CALCULATION

    /**
     * Resolve the applicable GST percentage.
     *
     * @param {Date|null} docDate
     * @param {string}    mode           premiumMode value (YLY, HLY, SINGLE …)
     * @param {boolean}   isSinglePlan   LicPlan.isSinglePremium flag
     * @param {string}    planTypeName   LicPlan type name (used to detect TERM)
     * @returns {number}  GST rate as a percentage (e.g. 4.5, 18, 0)
     */
    function resolveGstRate(docDate, mode, isSinglePlan, planTypeName) {
        if (!docDate || isNaN(docDate.getTime())) {
            return 0;
        }

        // New regime: always 0 %
        if (docDate >= GST_REFORM_DATE) {
            return 0;
        }

        // Determine "is single" from EITHER the mode or the plan flag
        var isSingle = (mode === 'SINGLE') || isSinglePlan;

        if (isSingle) {
            return 1.25;
        }

        var isTerm = planTypeName && planTypeName.toUpperCase().indexOf('TERM') !== -1;

        if (isTerm) {
            return 18;
        }

        // Traditional plan — year-aware rate
        var today = new Date();
        today.setHours(0, 0, 0, 0);

        var diffMs = today - docDate;
        var diffYears = diffMs / (1000 * 60 * 60 * 24 * 365.25);
        var policyYear = Math.floor(diffYears) + 1;

        return policyYear === 1 ? 4.5 : 2.25;
    }

    // Update the GST and Total fields in the form.
    function recalculateGst() {
        if (!$basicPremium || !$gst || !$totalPremium) return;

        var basic = parseFloat($basicPremium.value) || 0;

        // Parse DOC
        var docVal = $doc ? $doc.value : '';
        var docDate = docVal ? new Date(docVal) : null;

        var mode = $mode ? $mode.value : '';

        var rate = resolveGstRate(
            docDate,
            mode,
            currentPlanFlags.isSinglePremium,
            currentPlanFlags.planTypeName
        );

        var gstAmt = Math.round(basic * rate / 100 * 100) / 100;  // matches PHP: round(basic*rate/100, 2)
        var totalAmt = basic + gstAmt;

        $gst.value = gstAmt.toFixed(2);
        $totalPremium.value = totalAmt.toFixed(2);
    }

    // 3. NEXT DUE DATE AUTO-CALCULATION

    function recalculateNextDue() {
        if (!$nextDue || !$doc) return;

        // Single premium → clear and lock next due
        if (currentPlanFlags.isSinglePremium || ($mode && $mode.value === 'SINGLE')) {
            $nextDue.value = '';
            $nextDue.readOnly = true;
            $nextDue.style.opacity = '0.5';
            return;
        }

        // Unlock if it was previously locked
        $nextDue.readOnly = false;
        $nextDue.style.opacity = '';

        var docVal = $doc.value;
        if (!docVal) return;

        var docDate = new Date(docVal);
        if (isNaN(docDate.getTime())) return;

        var mode = $mode ? $mode.value : '';

        var addMonths = 0;
        switch (mode) {
            case 'YLY': case 'YEARLY': addMonths = 12; break;
            case 'HLY': case 'HALF-YEARLY': addMonths = 6; break;
            case 'QLY': case 'QUARTERLY': addMonths = 3; break;
            case 'MLY': case 'MONTHLY':
            case 'NACH': addMonths = 1; break;
            default: return;
        }

        var today = new Date();
        today.setHours(0, 0, 0, 0);

        var nextDue = new Date(docDate);
        nextDue.setMonth(nextDue.getMonth() + addMonths);

        while (nextDue < today) {
            nextDue.setMonth(nextDue.getMonth() + addMonths);
        }

        var yyyy = nextDue.getFullYear();
        var mm = String(nextDue.getMonth() + 1).padStart(2, '0');
        var dd = String(nextDue.getDate()).padStart(2, '0');
        $nextDue.value = yyyy + '-' + mm + '-' + dd;
    }

    // 4. APPLY PLAN FLAG BEHAVIOURS

    function removeLimitedPremiumHint() {
        var old = document.getElementById('ea-lp-hint');
        if (old) old.parentNode.removeChild(old);
    }

    function applyPlanFlagBehaviours() {
        // Single Premium
        if (currentPlanFlags.isSinglePremium) {
            // Lock mode to SINGLE
            if ($mode) {
                $mode.value = 'SINGLE';
                $mode.disabled = true;
                ensureHiddenMirror($mode);
            }
        } else {
            // Unlock mode (was locked by a previous single-premium selection)
            if ($mode && $mode.disabled) {
                $mode.disabled = false;
                removeHiddenMirror($mode);
            }
        }

        // Limited Premium hint
        removeLimitedPremiumHint();

        if (currentPlanFlags.isLimitedPremium) {
            var hint = document.createElement('div');
            hint.id = 'ea-lp-hint';
            hint.style.cssText =
                'margin-top:6px;padding:8px 12px;background:#fff3cd;border:1px solid #ffc107;' +
                'border-radius:4px;font-size:0.875rem;color:#664d03;';
            hint.innerHTML =
                '<i class="fa fa-info-circle" style="margin-right:6px;"></i>' +
                '<strong>Limited Premium Plan</strong> — PPT should be <em>less than</em> the Policy Term. ' +
                'Please enter the correct Paying Term.';

            // Insert after the plan field
            if ($plan) {
                var wrapper = $plan.closest('.field-association') || $plan.parentNode;
                wrapper.insertAdjacentElement('afterend', hint);
            }
        }

        // Recalculate everything with updated flags
        recalculateNextDue();
        recalculateGst();
    }

    // 5. HIDDEN MIRROR helper (for disabled selects that don't submit)

    function ensureHiddenMirror(select) {
        var mirrorId = 'hidden-mirror-' + select.id;
        if (document.getElementById(mirrorId)) return;

        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.id = mirrorId;
        hidden.name = select.name;
        hidden.value = select.value;
        select.parentNode.insertBefore(hidden, select.nextSibling);

        // Keep mirror in sync if value changes programmatically
        select.addEventListener('change', function () {
            hidden.value = select.value;
        });
    }

    function removeHiddenMirror(select) {
        var mirrorId = 'hidden-mirror-' + select.id;
        var old = document.getElementById(mirrorId);
        if (old) old.parentNode.removeChild(old);
    }

    // 6. PLAN CHANGE HANDLER

    function onPlanChange() {
        // EasyAdmin 4 renders Association fields as a Tom-Select / Symfony autocomplete
        // widget.  The underlying <select> may carry a data-ea-autocomplete attribute.
        // The selected option value is the entity ID.
        var planId = $plan ? $plan.value : null;

        if (!planId) {
            // Reset flags if no plan selected
            currentPlanFlags = { isSinglePremium: false, isLimitedPremium: false, planTypeName: '' };
            removeLimitedPremiumHint();
            return;
        }

        fetchPlanFlags(planId, function (flags) {
            currentPlanFlags = {
                isSinglePremium: !!flags.isSinglePremium,
                isLimitedPremium: !!flags.isLimitedPremium,
                planTypeName: flags.planTypeName || ''
            };
            applyPlanFlagBehaviours();
        });
    }

    // 7. FIELD RESOLUTION — handles EasyAdmin's dynamic DOM

    function resolveField(selector) {
        return document.querySelector(selector);
    }

    function resolveFields() {
        $annualPremium = resolveField('[id$="_annualPremium"]');
        $basicPremium = resolveField('[id$="_basicPremium"]');
        $modalRebate = resolveField('[id$="_modalRebate"]');
        $gst = resolveField('[id$="_gst"]');
        $totalPremium = resolveField('[id$="_totalPremium"]');
        $doc = resolveField('[id$="_commencementDate"]');
        $mode = resolveField('[id$="_premiumMode"]');
        $nextDue = resolveField('[id$="_nextDueDate"]');
        $plan = resolveField('[id$="_licPlan"]');
    }

    // 8. BOOT

    document.addEventListener('DOMContentLoaded', function () {
        resolveFields();

        if (!$basicPremium && !$doc) {
            // Not a Policy form — bail out early
            return;
        }

        // Attach listeners
        if ($annualPremium) {
            $annualPremium.addEventListener('input', recalculateModalPremium);
            $annualPremium.addEventListener('change', recalculateModalPremium);
        }

        if ($basicPremium) {
            $basicPremium.addEventListener('input', recalculateGst);
            $basicPremium.addEventListener('change', recalculateGst);
        }

        if ($doc) {
            $doc.addEventListener('change', function () {
                recalculateGst();
                recalculateNextDue();
            });
        }

        if ($mode) {
            $mode.addEventListener('change', function () {
                recalculateModalPremium();
                recalculateGst();
                recalculateNextDue();
            });
        }

        // Plan field — listen for both native change and Tom-Select's custom event
        if ($plan) {
            $plan.addEventListener('change', onPlanChange);

            // Tom-Select fires a custom 'change' that bubbles, but also listen
            // for the EA autocomplete item-add event just in case
            $plan.addEventListener('ea-autocomplete:selected', onPlanChange);
        }

        // If editing an existing policy, fetch flags immediately
        if ($plan && $plan.value) {
            onPlanChange();
        } else {
            // Still run initial calc in case values are pre-filled
            recalculateModalPremium();
            recalculateGst();
            recalculateNextDue();
        }
    });

    // Re-init if EA reloads parts of the page (modal forms, etc.)
    document.addEventListener('ea.collection.item-added', function () {
        resolveFields();
    });

})();