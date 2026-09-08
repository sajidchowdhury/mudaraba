/* ======== FETCH & RENDER ======== */
document.addEventListener('DOMContentLoaded', function () {

     const month = $('#profit_month').val();
    
    fetchInvestorProfitDetails(month);


document.getElementById("profit_month").addEventListener("change", function () {
    const month = this.value;
    fetchInvestorProfitDetails(month);
});

});




function fetchInvestorProfitDetails(month) {
    if (!month) return;
    // optionally show a spinner...
    fetch('includes/fetchInvestorProfitDetails.inc.php?month=' + encodeURIComponent(month), {
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {

            // pass sector_balance (fallback to empty structure if not provided)
            renderInvestorProfitTable(month,data.details, data.receivablepayable || {
                receivable_from_investors: [],
                payable_to_investors: []
            });
            document.getElementById("MonthName").innerText = month;
        } else {
            alert(data.error || "No data found for selected month.");
            // clear table (implement as needed)
            renderInvestorProfitTable(month,{ estimatedprofit: null, actualprofit: null, investors: [] }, {
                receivable_from_investors: [],
                payable_to_investors: []
            });
        }
    })
    .catch(error => {
        console.error("Fetch error:", error);
        alert("Failed to fetch data. See console for details.");
    });
}


function renderInvestorProfitTable(month,details, sectorBalance = { receivable_from_investors: [], payable_to_investors: [] }) {
    const tbody = document.querySelector("#example tbody");
    const tfoot = document.querySelector("#example tfoot");
    const saveBtn = document.querySelector("button[onclick='saveProfitDistribution()']");
    tbody.innerHTML = "";
    tfoot.innerHTML = "";

    const est = details.estimatedprofit;
    const act = details.actualprofit;

    // update hidden fields / prints
    if (document.getElementById('estimatedprofit')) document.getElementById('estimatedprofit').value = est ?? '';
    if (document.getElementById('actualprofit')) document.getElementById('actualprofit').value = act ?? '';
    if (document.getElementById('PrintEst')) document.getElementById('PrintEst').innerHTML = est !== null ? 'Est. Profit: ' + formatNumber(est) + ' TK ' : '';
    if (document.getElementById('PrintAct')) document.getElementById('PrintAct').innerHTML = act !== null ? 'Actual Profit: ' + formatNumber(act) + ' TK ' : '';
    document.getElementById('LinkPD').innerHTML = '<a href="dynamic-page.php?page=Sector-Profit&id='+month+'" class="btn btn-outline-secondary ml-2">Sector Profit of '+month+' </a>' ;

    // if estimatedprofit is null -> show link to Sector-Profit and only investor list
    // Note: you used `est === 0` earlier; using null/undefined is safer to detect "not set"
    if (est === null || est === undefined || est === 0) {
        details.investors.forEach((inv, idx) => {
            const tr = document.createElement("tr");
            tr.dataset.investor_id = inv.investor_id;
            tr.innerHTML = `
                <input type="hidden" name="investor_id[]" value="${inv.investor_id}">
                <td>${idx+1}</td>
                <td>${escapeHtml(inv.investor_name)}<br><small>Inv = ${formatNumber(inv.investment)}</small></td>
                <td colspan="5" class="text-center">
                </td>
            `;
            tbody.appendChild(tr);
        });

        if (saveBtn) saveBtn.disabled = true;
        // print button adjustments
        if (document.getElementById('PrintEst')) document.getElementById('PrintEst').innerHTML = '<a href="dynamic-page.php?page=Sector-Profit&id='+month+'" class="btn btn-sm btn-outline-primary">Go to Sector-Profit to set Estimated Profit</a>';
        if (document.getElementById('PrintAct')) document.getElementById('PrintAct').innerHTML = '';
        return;
    }

    // build normal table when we have estimated profit
    let sumEst = 0, sumAct = 0, sumH = 0, sumInv = 0, sumRat = 0,sumAd = 0;
    details.investors.forEach((inv, idx) => {
        const investment = Number(inv.investment) || 0;
        const ratio = Number(inv.investment_ratio) || 0; // fraction like 0.123
        const e = Number(inv.estimated_disbursement_e) || 0;
        const f = Number(inv.actual_share_f) || 0;
        const g = Number(inv.deed_ratio_g) || 0;
        // Use profit_h if present (saved) else computed
        const h = (inv.profit_h !== null && inv.profit_h !== undefined) ? Number(inv.profit_h) : Math.round(f * g / 100);
        const i = Math.round(e - h);

        sumInv += investment;
        sumEst += Math.round(e);
        sumAct += Math.round(f);
        sumH += Math.round(h);
        sumAd += Math.round(i);
        sumRat += ratio;

        const tr = document.createElement("tr");
        tr.dataset.investor_id = inv.investor_id;

        // include hidden inputs for save
        tr.innerHTML = `
            <input type="hidden" name="saved_row_id[]" value="${inv.saved_row_id ?? ''}">
            <input type="hidden" name="investment[]" value="${Math.round(investment)}">
            <input type="hidden" name="investment_ratio[]" value="${Number(ratio).toFixed(6)}">
            <input type="hidden" name="estimated_profit[]" value="${Math.round(e)}">
            <input type="hidden" name="actual_profit[]" value="${Math.round(h)}">
            <input type="hidden" name="advance_paid[]" value="${Math.round(i)}">
            <input type="hidden" name="deed_ration[]" value="${Math.round(g)}">

            <td>${idx + 1}</td>
            <td>${escapeHtml(inv.investor_name)}<br>
              <b class="text-danger">Inv = ${formatNumber(investment)}</b><br>
              <b class="text-info">Inv Ratio = ${Number(ratio).toFixed(6)}</b>
            </td>
            <td>${formatNumber(Math.round(e))}</td>
            <td>${formatNumber(Math.round(f))}</td>
            <td>${formatNumber(Math.round(g))}</td>
            <td>${formatNumber(Math.round(h))}</td>
            <td>${formatNumber(Math.round(i))}</td>
        `;
        tbody.appendChild(tr);
    });

    // footer totals (main)
    const footerRow = document.createElement("tr");
    footerRow.innerHTML = `
        <th colspan="2">Total<br><small>Inv = ${formatNumber(sumInv)}, Ratio ≈ ${Number(sumRat).toFixed(3)}</small></th>
        <th>${formatNumber(Math.round(sumEst))}</th>
        <th>${formatNumber(Math.round(sumAct))}</th>
        <th></th>
        <th>${formatNumber(Math.round(sumH))}</th>
        <th>${formatNumber(Math.round(sumAd))}</th>
    `;
    tfoot.appendChild(footerRow);

    // ---- NEW: dynamic Receivable / Payable section (mirrors your PHP block) ----
    // myAmount = actualprofit - sumH
    const myAmount = (Number(act) || 0) - Math.round(sumH);

    // Receivable header
    const receHeader = document.createElement('tr');
    receHeader.innerHTML = `<th></th><th colspan="6" class="text-danger">*** Receivable From Investors ***</th>`;
    tfoot.appendChild(receHeader);
   


    // M/Y row (your label M/Y)
    const myRow = document.createElement('tr');
    myRow.innerHTML = `
    <th></th>
    <td>M/Y</td>
    <td colspan="5">${formatNumber(Math.round(myAmount))}<input type="hidden" id="MyAmount"  name="MyAmount" value="${Math.round(myAmount)}"></td>`;
    tfoot.appendChild(myRow);


    let TotalReceDiff = 0;
    let TotalPayDiff = 0;

    // Loop through receivable_from_investors
    const receivables = details.receivablepayable?.receivable_from_investors || [];
    receivables.forEach(item => {
        const diffAbs = Math.round(Math.abs(Number(item.difference) || 0));
        const tr = document.createElement('tr');
        tr.innerHTML = `<th></th><td>${escapeHtml(item.sector_name)}</td><td colspan="5">${formatNumber(diffAbs)}</td>`;
        tfoot.appendChild(tr);
        TotalReceDiff += diffAbs;
    });



    // Total Receivable row
    const totalReceivable = Math.round(myAmount) + TotalReceDiff;
    const receTotalRow = document.createElement('tr');
    receTotalRow.innerHTML = `<th></th><th>Total</th><th colspan="5">${formatNumber(totalReceivable)}</th>`;
    tfoot.appendChild(receTotalRow);

// Payable header
const payHeader = document.createElement('tr');
payHeader.innerHTML = `<th></th><th colspan="6" class="text-danger">*** Payable To Investors ***</th>`;
tfoot.appendChild(payHeader);

// Loop through payable_to_investors
const payables = details.receivablepayable?.payable_to_investors || [];
payables.forEach(item => {
    const diffAbs = Math.round(Math.abs(Number(item.difference) || 0));
    const tr = document.createElement('tr');
    tr.innerHTML = `<th></th><td>${escapeHtml(item.sector_name)}</td><td colspan="5">${formatNumber(diffAbs)}</td>`;
    tfoot.appendChild(tr);
    TotalPayDiff += diffAbs;
});

// Total Payable row
const payTotalRow = document.createElement('tr');
payTotalRow.innerHTML = `<th></th><th>Total</th><th colspan="5">${formatNumber(TotalPayDiff)}</th>`;
tfoot.appendChild(payTotalRow);



    // ---- end Receivable/Payable section ----

    // disable Save when actual profit is null/0
    if (act === 0 || act === null || act === undefined) {
        if (saveBtn) saveBtn.disabled = true;
        const warn = document.getElementById('splitWarning');
        //if (warn) warn.innerText = "Actual profit missing";
        if (document.getElementById('PrintAct')) document.getElementById('PrintAct').innerHTML = '<a href="dynamic-page.php?page=Sector-Profit&id='+month+'" class="btn btn-sm btn-outline-primary">Go to Sector-Profit to set Act Profit</a>';
    } else {
        if (saveBtn) saveBtn.disabled = false;
        const warn = document.getElementById('splitWarning');
        if (warn) warn.innerText = "";
    }

    // attach change listener to deed ratios to recalc h and advance live
    document.querySelectorAll('input[name="deed_ration[]"]').forEach((el, idx) => {
        el.addEventListener('input', function () {
            onDeedRatioChange(idx, details);
        });
    });
}



/* Helper: recalculate h and advance client-side when deed ratio changes.
   idx refers to nth investor in details.investors order. We find the row in table and update h & advance cells and hidden inputs.
*/
function onDeedRatioChange(index, details) {
    const rows = document.querySelectorAll('#example tbody tr');
    const row = rows[index];
    if (!row) return;
    const deedInput = row.querySelector('input[name="deed_ration[]"]');
    const deed = Number(deedInput.value) || 0;

    const e = Number(row.querySelector('input[name="estimated_profit[]"]').value) || 0;
    const f = Number(details.investors[index].actual_share_f) || 0; // f = actual share before deed applied
    // h = f × g ÷ 100
    const h = Math.round(f * deed / 100);
    // advance = e - h
    const adv = Math.round(e - h);

    // update display (6th and 7th td)
    row.cells[5].innerText = h;
    row.cells[6].innerText = adv;

    // update hidden actual_profit and advance_paid
    row.querySelector('input[name="actual_profit[]"]').value = h;
    row.querySelector('input[name="advance_paid[]"]').value = adv;
}

/* ======== SAVE ======== */
// Replace existing saveProfitDistribution() with this
async function saveProfitDistribution() {
  try {
    const form = document.getElementById('myForm');
    if (!form) return alert('Form not found (id="myForm").');

    // read month/summary/CSRF from your hidden fields
    const month = (form.querySelector('input[name="profit_month"]')?.value || '').trim();
    const estimatedProfit = Number(document.getElementById('estimatedprofit')?.value || 0);
    const actualProfit = Number(document.getElementById('actualprofit')?.value || 0);
    const csrf = form.querySelector('input[name="csrf_token"]')?.value || '';
    const MyAmount = form.querySelector('input[name="MyAmount"]')?.value || '';
 

    // Quick client-side checks
    if (!/^\d{4}-\d{2}$/.test(month)) return alert('Invalid month format. Expected YYYY-MM.');
    if (!actualProfit || actualProfit <= 0) return alert('Actual profit must be set and > 0.');

    // helper: convert visible string -> number (remove commas, currency symbols)
    const parseNumber = (s) => {
      if (s === undefined || s === null) return 0;
      if (typeof s === 'number') return s;
      const cleaned = String(s).replace(/[^0-9\.\-]+/g, '');
      return cleaned === '' ? 0 : Number(cleaned);
    };

    // helper: read value from input[name] or fallback to a cell index
    const getNumberFromRow = (row, inputName, fallbackCellIndex) => {
      const el = row.querySelector(`input[name="${inputName}"]`);
      if (el) return parseNumber(el.value);
      const cell = row.cells[fallbackCellIndex];
      return cell ? parseNumber(cell.innerText) : 0;
    };

    const rows = Array.from(document.querySelectorAll('#example tbody tr'));
    if (!rows.length) return alert('No investors found in the table.');

    const payload = {
      month,
      estimatedprofit: Number(estimatedProfit) || 0,
      MyAmount,
      actualprofit: Number(actualProfit) || 0,
      csrf,
      investors: []
    };

    const errors = [];

    rows.forEach((row, idx) => {
      // investor id: prefer hidden input, fallback to data attribute
      const invIdInput = row.querySelector('input[name="investor_id[]"]');
      const investor_id = invIdInput ? parseInt(invIdInput.value) : (row.dataset.investor_id ? parseInt(row.dataset.investor_id) : null);

      const saved_row_id = row.querySelector('input[name="saved_row_id[]"]')?.value || null;
      const investment = getNumberFromRow(row, 'investment[]', 1); // fallback to investor cell (index 1)
      const investment_ratio = getNumberFromRow(row, 'investment_ratio[]', 1);
      const estimated_profit = getNumberFromRow(row, 'estimated_profit[]', 2); // col 2
      const actual_profit_before_deed = getNumberFromRow(row, 'actual_profit_before_deed[]', 3); // col 3
      // deed ratio input on col 4 (name may be 'deed_ration[]' in your html)
      const deedInput = row.querySelector('input[name="deed_ration[]"], input[name="deed_ratio[]"]');
      const deed_ratio = deedInput ? parseNumber(deedInput.value) : getNumberFromRow(row, 'deed_ration[]', 4);
      // final profit and advance
      const final_profit = getNumberFromRow(row, 'actual_profit[]', 5); // col 5 (h)
      const advance_paid = getNumberFromRow(row, 'advance_paid[]', 6); // col 6 (i)

      // Basic validation per-row
      if (!investor_id || isNaN(investor_id)) {
        errors.push(`Row ${idx + 1}: missing investor id.`);
      }
      if (investment < 0) errors.push(`Row ${idx + 1}: investment < 0.`);
      if (deed_ratio < 0 || deed_ratio > 100) errors.push(`Row ${idx + 1}: deed ratio must be between 0 and 100.`);
     // if (estimated_profit < 0 || final_profit < 0 || advance_paid < 0) {
      //  errors.push(`Row ${idx + 1}: can not pay nagative as advance `);
     // }

      payload.investors.push({
        investor_id: investor_id ? Number(investor_id) : null,
        saved_row_id: saved_row_id ? Number(saved_row_id) : null,
        investment: Number(investment) || 0,
        investment_ratio: Number(investment_ratio) || 0,
        estimated_profit: Number(estimated_profit) || 0,
        actual_profit_before_deed: Number(actual_profit_before_deed) || 0,
        deed_ratio: Number(deed_ratio) || 0,          // normalized for DB field
        actual_profit: Number(final_profit) || 0,
        advance_paid: Number(advance_paid) || 0
      });
    });

    if (errors.length) {
      console.error('validation errors: ', errors);
      return alert('Fix table errors before saving:\n' + errors.slice(0, 6).join('\n'));
    }

    // Optional business check: sum of final_profit should not exceed actualProfit (warn only)
    const sumFinal = payload.investors.reduce((s, it) => s + (Number(it.actual_profit) || 0), 0);
    if (sumFinal > payload.actualprofit + 0.0001) {
      const ok = confirm(`Sum of final profits (${sumFinal}) exceeds Actual Profit (${payload.actualprofit}). Save anyway?`);
      if (!ok) return;
    }

    // disable UI while saving
    const saveBtn = document.querySelector('button[onclick="saveProfitDistribution()"], button.save-profit');
    if (saveBtn) saveBtn.disabled = true;

    const resp = await fetch('includes/InvestorProfit.inc.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
      credentials: 'same-origin'
    });

    const json = await resp.json();
    if (!resp.ok || json.success === false) {
      console.error('Save failed:', json);
      alert('Save failed: ' + (json.error || json.message || 'Unknown error. Check console.'));
      if (saveBtn) saveBtn.disabled = false;
      return;
    }


    // Refresh the table UI (you already have a function for that)
    if (typeof fetchInvestorProfitDetails === 'function') fetchInvestorProfitDetails(payload.month);

    if (saveBtn) saveBtn.disabled = false;

   Swal.fire({
        icon: 'success',
        title: json.message,
        showConfirmButton: false,
        timer: 2000
    });


  } catch (err) {
    console.log(err.message);
    alert('Unexpected error while saving. See console for details.');
  }
}

/* ======== UTIL ======== */

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
}