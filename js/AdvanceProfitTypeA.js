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
    '<li>Deducting From Inv Profit with Sectors Adv Profit</li>' +
    '<li> From Investors </li>' +
    '</ul>'

      })
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


/**
 * SAVE handler - collects investors and sectors using unique IDs and posts JSON.
 * Runs only on save button click.
 */
function saveHandler() {
        const adjustInput = document.getElementById('adv_adjust');
        const sectorRows = document.querySelectorAll('#sectorTableBody tr');
        const investorRows = document.querySelectorAll('#investorTableBody tr');
        const type = document.getElementById('type').value;
        const RemainingFund = document.getElementById('RemainingFund').value;
        const advAdjust = parseFloat(adjustInput.value) || 0;
        const AllocatedFUnd = parseFloat(document.getElementById('AllocatedFUnd').value) || 0;
        const adv_profit_adjusting_fund = parseFloat(document.getElementById('adv_profit_adjusting_fund').value) || 0;

    // ✅ Validations

        if(advAdjust > '0.00' ){

        if (type == '') {
        Swal.fire('warning', '⚠️ Select Invest Ratio');
        return;
        }


        }
 
  
    if (RemainingFund > adv_profit_adjusting_fund ) {
        Swal.fire('warning', '⚠️ not enough Adv Profit Adjusting Fund');
        return;
    }


    if (!(AllocatedFUnd > 0 || adv_profit_adjusting_fund > 0)) {
    Swal.fire('warning', '⚠️ Either Adv Profit Adjusting or Allocated Fund must be greater than 0');
    return;
    }



    // ✅ Collect investor data
    const investors = [];
    investorRows.forEach(row => {
        const investor_id = row.dataset.investor_id;
        const amountEl = document.getElementById('investor_amount_' + investor_id);
        const amount = parseFloat(amountEl?.textContent) || 0;
        investors.push({ investor_id, amount });
    });
    // ✅ Collect sector data
    const sectors = [];
    sectorRows.forEach(row => {
        const sector_id = row.dataset.sector_id;
        const input = document.getElementById('sector_adjust_' + sector_id);
        const amount = parseFloat(input.value) || 0;
        if (amount > 0) sectors.push({ sector_id, amount });
    });
    // ✅ Prepare payload
 const payload = {
    adv_adjust: advAdjust,
    investors,
    sectors,
    AllocatedFUnd,
    adv_profit_adjusting_fund,
    RemainingFund,
    type
};
    // ✅ Ajax Request
    fetch('includes/AdvanceProfitAdjustmentTypeA.inc.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
        Swal.fire('✅ Saved!', res.message, 'success');
        }else {
        Swal.fire('❌ Error', res.message || 'Something went wrong.', 'error');
        }
    })
    .catch(err => Swal.fire('⚠️ AJAX Error', err.message || 'Network error', 'error'));
}





