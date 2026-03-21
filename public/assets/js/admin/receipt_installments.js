document.addEventListener('DOMContentLoaded', function () {
    var policySelect = document.querySelector('select[name*="[policy]"]');
    if (!policySelect) return;

    var instCollected = document.querySelector('[data-installments-collected]');
    var instPaid = document.querySelector('[data-installments-paid]');
    var collectedInput = document.querySelector('[data-collected-field]');
    var amountInput = document.querySelector('[data-amount-field]');

    var policyData = {
        amountPerInstallment: 0,
        lateFee: 0
    };

    function calcTotal(count) {
        return (policyData.amountPerInstallment * count) + policyData.lateFee;
    }

    function updateCollected() {
        if (!collectedInput || !instCollected) return;
        var count = parseInt(instCollected.value) || 1;
        collectedInput.value = calcTotal(count).toFixed(2);
    }

    function updateAmount() {
        if (!amountInput || !instPaid) return;
        var count = parseInt(instPaid.value) || 1;
        amountInput.value = calcTotal(count).toFixed(2);
    }

    policySelect.addEventListener('change', function () {
        var policyId = this.value;
        if (!policyId) {
            policyData.amountPerInstallment = 0;
            policyData.lateFee = 0;
            if (instCollected) instCollected.value = 1;
            if (instPaid) instPaid.value = 1;
            if (collectedInput) collectedInput.value = '';
            if (amountInput) amountInput.value = '';
            return;
        }

        fetch('/admin/api/policy/' + policyId + '/pending-installments')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                policyData.amountPerInstallment = data.amount_per_installment || 0;
                policyData.lateFee = data.late_fee || 0;
                var pending = data.installments || 1;

                // Both default to full pending count
                if (instCollected) instCollected.value = pending;
                if (instPaid) instPaid.value = pending;

                updateCollected();
                updateAmount();
            })
            .catch(function () {
                policyData.amountPerInstallment = 0;
                policyData.lateFee = 0;
            });
    });

    // Recalculate independently
    if (instCollected) {
        instCollected.addEventListener('input', updateCollected);
    }
    if (instPaid) {
        instPaid.addEventListener('input', updateAmount);
    }
});
