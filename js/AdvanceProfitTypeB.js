  $(function() {
    const Toast = Swal.mixin({
      toast: true,
      position: 'bottomLeft',
      showConfirmButton: false,
      timer: 3000
    });

        $('.toastsDefaultBottomLeft').click(function() {
      $(document).Toasts('create', {
        title: 'New Sector',
        position: 'bottomLeft',
       body: '<ul class="mb-0">' +
    '<li>Sectors Extra Profit Adjusting with Previously Shared Adv Inv Profit </li>' +
    '<li> From Sectors </li>' +
    '</ul>'

      })
    });
  });





document.addEventListener('DOMContentLoaded', function () {
    const sectorInputs = document.querySelectorAll('.sector_adjust');
    const footerTotalEl = document.getElementById('footer_total2');
    const remainingFundEl = document.getElementById('remaining_fund');
    const allocatedFundInput = document.getElementById('AllocatedFUnd');
    const remainingFundInput = document.getElementById('RemainingFund');
    const advFundInput = document.getElementById('adv_profit_adjusting_fund');

    function updateTotals() {
        let totalAdjust = 0;

        sectorInputs.forEach(input => {
            const val = parseFloat(input.value) || 0;
            totalAdjust += val;
        });

        // Format numbers to 2 decimal places
        const totalAdjustFixed = totalAdjust.toFixed(2);
        const advFund = parseFloat(advFundInput.value.replace(/,/g, '')) || 0;
        const remainingFund = (advFund + totalAdjust).toFixed(2);

        // Update footer total and fund fields
        footerTotalEl.textContent = totalAdjustFixed;
        allocatedFundInput.value = totalAdjustFixed;
        remainingFundEl.textContent = remainingFund;
        remainingFundInput.value = remainingFund;
    }

    // Attach event listener to each sector input
    sectorInputs.forEach(input => {
        input.addEventListener('keyup', updateTotals);
        input.addEventListener('change', updateTotals);
    });
});


/**
 * Calculator() - triggered when user types in #adv_adjust
 * It calculates investor amounts, updates the total fund (adv_profit_adjusting_fund),
 * and recalculates remaining fund using updateSectorTotals().
 */
function Calculator() {
    const balanceInput = document.getElementById('adjustable_balance');
    const adjustInput = document.getElementById('adv_adjust');
    const investorRows = document.querySelectorAll('#investorTableBody tr');
    const investorTotalEl = document.getElementById('footer_total');
    const fundInput = document.getElementById('adv_profit_adjusting_fund');
    const Balance = parseFloat(fundInput.value) || 0;
    if (!balanceInput || !adjustInput || !fundInput) return;

    const adjustableBalance = parseFloat(balanceInput.value) || 0;

    let advAdjust = parseFloat(adjustInput.value) || 0;

    // --- Validation checks ---
    if (advAdjust > adjustableBalance) {
        alert('⚠️ Adv Profit Adjusting cannot exceed Adjustable Balance!');
        advAdjust = adjustableBalance;
        adjustInput.value = adjustableBalance.toFixed(2);
    } else if (advAdjust < 0) {
        alert('⚠️ Adjusting amount cannot be negative.');
        advAdjust = 0;
        adjustInput.value = '0.00';
    }

    // --- Calculate investor-wise amounts ---
    let totalInvestor = 0;
    investorRows.forEach(row => {
        const investorId = row.dataset.investor_id;
        const rule = parseFloat(row.dataset.rules) || 0;
        const amount = rule * advAdjust;
        const investorAmountEl = document.getElementById('investor_amount_' + investorId);
        if (investorAmountEl) investorAmountEl.textContent = amount.toFixed(2);
        totalInvestor += amount;
    });

    investorTotalEl.textContent = totalInvestor.toFixed(2);

    // --- Update Fund value ---
    // Fund should directly reflect the current adjusting amount, not cumulative
fundInput.value = (Balance + advAdjust).toFixed(2);

    // --- Recalculate sector totals & remaining ---
    updateSectorTotals();
}




/**
 * updateSectorTotals() - recalculates total allocated to sectors and remaining fund.
 */
function updateSectorTotals(event = null) {
    const fundInput = document.getElementById('adv_profit_adjusting_fund');
    const sectorRows = document.querySelectorAll('#sectorTableBody tr');
    const sectorTotalEl = document.getElementById('footer_total2');
    const AllocatedFUnd = document.getElementById('AllocatedFUnd');
    const remainingEl = document.getElementById('remaining_fund');
    const RemainingFund = document.getElementById('RemainingFund');


    if (!fundInput || !sectorRows.length || !sectorTotalEl || !remainingEl) return;

    const totalFund = parseFloat(fundInput.value) || 0;
    let totalUsed = 0;

    // --- Sum up all sector allocations ---
    sectorRows.forEach(row => {
        const sid = row.dataset.sector_id;
        const input = document.getElementById('sector_adjust_' + sid);
        const val = parseFloat(input.value) || 0;
        totalUsed += val;
    });

    // --- Validation: prevent overflow ---
    if (totalUsed > totalFund) {
        alert('⚠️ Total sector allocation exceeded available fund. All values have been reset.');

        // Reset all sector inputs
        sectorRows.forEach(row => {
            const sid = row.dataset.sector_id;
            const input = document.getElementById('sector_adjust_' + sid);
            if (input) input.value = "0.00";
        });

        // Reset totals
        totalUsed = 0;
        sectorTotalEl.textContent = "0.00";
        AllocatedFUnd.value = 0.00
        remainingEl.textContent = totalFund.toFixed(2);
        RemainingFund.value = totalFund.toFixed(2);
        return;
    }

    // --- Update totals normally ---
    sectorTotalEl.textContent = totalUsed.toFixed(2);
    AllocatedFUnd.value = totalUsed.toFixed(2);
    const remaining = Math.max(0, totalFund - totalUsed);
    remainingEl.textContent = remaining.toFixed(2);
    RemainingFund.value = remaining.toFixed(2);

}

