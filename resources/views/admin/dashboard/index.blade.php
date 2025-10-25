@extends('admin.layouts.base')
@section('title', 'Test Title')
@section('contents')

@push('custom-css')
<style>
    body {
        background-color: #faf7f5 !important;
        font-size: 1.05rem !important;
        font-weight: 500 !important;
        color: #222 !important;
    }
    h2 { font-weight: 400 !important; font-size: 1rem !important; color: #2c3e50 !important; }
    .card { border: none !important; border-radius: 12px !important; box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important; margin-bottom: 20px !important; }
    .card-header { font-weight: 700 !important; font-size: 1.2rem !important; border-radius: 12px 12px 0 0 !important; padding: 12px 15px !important; }
    .card-body { font-size: 1rem !important; font-weight: 500 !important; padding: 5px 5px !important; }

    table.dataTable { font-size: 1.05rem !important; font-weight: 600 !important; text-align: center !important; width: 100% !important; }
    table.dataTable thead th { background-color: #2c3e50 !important; color: #fff !important; text-transform: uppercase !important; font-size: 1.1rem !important; padding: 12px !important; font-weight: bold !important; }
    table.dataTable tbody td { padding: 10px !important; vertical-align: middle !important; font-weight: bold !important; }

    .text-success, .bg-success { background-color: #198754 !important; color: #fff !important; font-weight: 700 !important; border-radius: 6px !important; padding: 5px 10px !important; }
    .text-danger, .bg-danger { background-color: #dc3545 !important; color: #fff !important; font-weight: 700 !important; border-radius: 6px !important; padding: 5px 10px !important; }

    tr.row-positive { background-color: #e6f4ea !important; }
    tr.row-negative { background-color: #fdecea !important; }

    .btn { font-size: 1rem !important; font-weight: 400 !important; border-radius: 8px !important; padding: 6px 14px !important; }
    .btn-warning { background-color: #ffc107 !important; color: #222 !important; font-weight: 700 !important; border: none !important; }
    .btn-warning:hover { background-color: #e0a800 !important; color: #fff !important; }

    table.dataTable tbody tr:hover { background-color: #eef3f7 !important; transition: 0.3s ease; }

    .dataTables_scrollBody { max-height: 500px !important; overflow-y: auto !important; }

    div.dataTables_wrapper div.dataTables_paginate,
    div.dataTables_wrapper div.dataTables_info,
    div.dataTables_wrapper div.dataTables_length { display: none !important; }

    .dataTables_scrollHead { position: sticky; top: 0; z-index: 100; }

    .flash-green { background-color: #28a745 !important; color: #fff !important; animation: flashFadeGreen 2s ease forwards; }
    .flash-red { background-color: #dc3545 !important; color: #fff !important; animation: flashFadeRed 2s ease forwards; }
    @keyframes flashFadeGreen { 0% { background-color: #28a745; color: #fff; } 100% { background-color: inherit; color: inherit; } }
    @keyframes flashFadeRed   { 0% { background-color: #dc3545; color: #fff; } 100% { background-color: inherit; color: inherit; } }

    th:nth-child(1), td:nth-child(1) { white-space: nowrap; }

    #totals-row { position: sticky; bottom: 0; box-shadow: 0 -2px 5px rgba(0,0,0,0.1); }
    #totals-row td { background: #2c3e50 !important; color: white; }

    table.dataTable tbody td a.claim-open {
  display: inline-block;
  min-width: 34px;
  padding: 4px 7px;
  border-radius: 6px;
  font-weight: 700;
  text-align: center;
  text-decoration: none !important;
  color: #fd2d0d !important;               /* Bootstrap primary */
  background: rgba(13,110,253,0.06);       /* subtle blue tint */
  border: 1px solid rgba(13,110,253,0.18);
}
/* stronger hover affordance */
table.dataTable tbody td a.claim-open:hover,
table.dataTable tbody td a.claim-open:focus {
  background: rgba(13,110,253,0.12);
  color: #0b5ed7 !important;
  box-shadow: 0 0 0 3px rgba(13,110,253,0.06);
}
table.dataTable tbody tr.dark-row td a.claim-open,
.table-dark table.dataTable tbody td a.claim-open {
  color: #fff !important;
  background: rgba(255,255,255,0.12);
  border-color: rgba(255,255,255,0.18);
}
</style>
@endpush

@push('custom-js')
    @include('admin.includes.datatable-js-plugins')
    {{ $dataTable->scripts() }}

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        let serverTotals = @json($tableTotals ?? null);

        const dtWrapper = window.LaravelDataTables && window.LaravelDataTables['draw-details-table']
            ? window.LaravelDataTables['draw-details-table']
            : (window.$ && $('#draw-details-table').DataTable ? $('#draw-details-table').DataTable() : null);

        if (!dtWrapper) return;

        const dtApi = dtWrapper.api ? dtWrapper.api() : dtWrapper;

        window.__drawsDtApi = (dtWrapper && (dtWrapper.api ? dtWrapper.api() : dtWrapper)) || window.__drawsDtApi || null;

        (function attachMutationObserverForTotals() {
            function getDtApiForReload() {
                if (window.__drawsDtApi && window.__drawsDtApi.ajax) return window.__drawsDtApi;
                if (window.LaravelDataTables && window.LaravelDataTables['draw-details-table']) {
                    try { return window.LaravelDataTables['draw-details-table'].api(); } catch(e) {}
                }
                try { return (window.$ && $('#draw-details-table').DataTable) ? $('#draw-details-table').DataTable() : null; } catch(e) {}
                return null;
            }

            let __lastDtReload = 0;
            const __dtReloadDebounceMs = 800;
            function reloadDt(source) {
                const now = Date.now();
                if (now - __lastDtReload < __dtReloadDebounceMs) return;
                __lastDtReload = now;

                const api = getDtApiForReload();
                if (!api || !api.ajax) return;
                api.ajax.reload(null, false);
            }

            let totalsPanel = null;
            try {
                const possibleHeaders = Array.from(document.querySelectorAll('h4, h3, .card-header, .card-title, .panel-heading'));
                totalsPanel = possibleHeaders.find(h => h.textContent && h.textContent.trim().toLowerCase().includes('total admin'));
                if (totalsPanel) totalsPanel = totalsPanel.closest('.card') || totalsPanel.parentElement;
                else totalsPanel = document.querySelector('.col-4') || document.querySelector('.right-panel') || document.querySelector('.sidebar-right');
            } catch (e) { totalsPanel = null; }

            if (!totalsPanel) return;

            const observer = new MutationObserver((mutationsList) => {
                let changed = false;
                for (const m of mutationsList) {
                    if (m.type === 'childList' && (m.addedNodes.length || m.removedNodes.length)) { changed = true; break; }
                    if (m.type === 'characterData' || m.type === 'subtree') { changed = true; break; }
                }
                if (changed) reloadDt('mutation-totals-panel');
            });

            observer.observe(totalsPanel, { attributes: false, childList: true, subtree: true, characterData: true });

            window.__drawsTotalsObserver = observer;
            window.__reloadDtNow = () => reloadDt('__manual_reload__');
        })();

        $('#draw-details-table').off('xhr.dt.tableTotals').on('xhr.dt.tableTotals', function (e, settings, json) {
            if (!json) {
                serverTotals = null;
                removeTotalsRow();
                addTotalsRow();
                return;
            }

            let newTotals = null;
            if (json.tableTotals) newTotals = json.tableTotals;
            else if (json.meta && json.meta.tableTotals) newTotals = json.meta.tableTotals;
            else if (json.data && json.tableTotals) newTotals = json.tableTotals;

            if (!newTotals) {
                serverTotals = null;
                removeTotalsRow();
                addTotalsRow();
                return;
            }

            Object.keys(newTotals).forEach(k => {
                const v = newTotals[k];
                newTotals[k] = (v === null || v === undefined || v === '') ? 0 : Number(v);
            });

            serverTotals = newTotals;
            removeTotalsRow();
            addTotalsRow();
        });

        (function attachDateFilterHandlers() {
            function getStartDate() { return document.querySelector('input[name="start_date"]')?.value || ''; }
            function getEndDate() { return document.querySelector('input[name="end_date"]')?.value || ''; }
            function getDayToken() { return document.querySelector('input[name="day"]')?.value || ''; }

            $('#draw-details-table').off('preXhr.dt.dateFilter').on('preXhr.dt.dateFilter', function (e, settings, data) {
                data.start_date = getStartDate();
                data.end_date = getEndDate();
                data.day = getDayToken();
            });

            const dateForm = document.getElementById('date-range-picker-form');
            if (dateForm) {
                dateForm.addEventListener('submit', function (ev) {
                    ev.preventDefault();
                    if (dtApi && dtApi.ajax) dtApi.ajax.reload(null, false);
                    try {
                        const params = new URLSearchParams(window.location.search);
                        if (getStartDate() && getEndDate()) {
                            params.set('start_date', getStartDate());
                            params.set('end_date', getEndDate());
                            params.set('day', getDayToken() || '');
                        } else {
                            params.delete('start_date');
                            params.delete('end_date');
                            params.delete('day');
                        }
                        history.replaceState({}, '', window.location.pathname + '?' + params.toString());
                    } catch (err) {}
                });
            }

            document.querySelectorAll('#date-range-picker-form .reset-btn').forEach(function(btn) {
                btn.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    const sd = document.querySelector('input[name="start_date"]');
                    const ed = document.querySelector('input[name="end_date"]');
                    const d = document.querySelector('input[name="day"]');
                    if (sd) sd.value = '';
                    if (ed) ed.value = '';
                    if (d) d.value = '';
                    const span = document.querySelector('.date-range-picker span');
                    if (span) span.textContent = 'Today';
                    if (dtApi && dtApi.ajax) dtApi.ajax.reload(null, false);
                    try {
                        const params = new URLSearchParams(window.location.search);
                        params.delete('start_date'); params.delete('end_date'); params.delete('day');
                        history.replaceState({}, '', window.location.pathname + (params.toString() ? '?' + params.toString() : ''));
                    } catch (err) {}
                });
            });

            (function hydrateFromUrlAndReload() {
                const params = new URLSearchParams(window.location.search);
                const sd = params.get('start_date');
                const ed = params.get('end_date');
                const day = params.get('day');

                if ((sd && ed) || day) {
                    if (sd) document.querySelector('input[name="start_date"]').value = sd;
                    if (ed) document.querySelector('input[name="end_date"]').value = ed;
                    if (day) document.querySelector('input[name="day"]').value = day;

                    const span = document.querySelector('.date-range-picker span');
                    if (span) {
                        if (day) span.textContent = day + (sd && ed ? ` (${sd} — ${ed})` : '');
                        else if (sd && ed) span.textContent = `Custom (${sd} — ${ed})`;
                    }

                    if (dtApi && dtApi.ajax) dtApi.ajax.reload(null, false);
                }
            })();
        })();

        $('.dataTables_scrollBody').css({ 'max-height': '70vh', 'overflow-y': 'auto' });
        $(".dataTables_scrollHead").css({ "position": "sticky", "top": "0", "z-index": "10", "background": "#343a40" });
        $(".dataTables_paginate, .dataTables_info, .dataTables_length").hide();

        function removeTotalsRow() { $('#totals-row').remove(); }

        function parseNum(text) {
            if (text === null || text === undefined) return 0;
            const n = String(text).replace(/[^0-9\.\-]/g, '');
            return parseFloat(n) || 0;
        }

        function readCellValue($td) {
            const $a = $td.find('a[data-value]');
            if ($a.length) return parseNum($a.attr('data-value'));
            return parseNum($td.text());
        }

function calculateTotalsFromDom() {
    // same logic as console above but using dtApi.rows...
    const totals = { tq:0, t_amt:0, claim_amt:0, cross:0, cross_claim:0, p_and_l:0 };
    const IDX_TQ = 2, IDX_CLAIM = 3, IDX_CROSS = 4, IDX_CROSS_CLAIM = 5, IDX_PL = 6;
    const LIMIT_COUNT = 999; // counts must be <= LIMIT_COUNT to be included

    function parseNumbersFromString(s){
        if (!s) return [];
        const matches = String(s).match(/-?\d{1,3}(?:[,.\d]*\d)?/g);
        if (!matches) return [];
        return matches.map(m => parseFloat(m.replace(/,/g,''))).filter(n => !isNaN(n));
    }
    function chooseSmallestUnder(nums, limit){
        if (!nums || !nums.length) return 0;
        const pos = nums.filter(n => isFinite(n) && n > 0 && n <= limit);
        if (!pos.length) return 0;
        const ints = pos.filter(n => Math.abs(n - Math.round(n)) < 1e-9);
        if (ints.length) return Math.min(...ints);
        return Math.min(...pos);
    }

    dtApi.rows({ page: 'current' }).every(function() {
        const $tds = $(this.node()).find('td');

        totals.tq += chooseSmallestUnder(parseNumbersFromString($tds.eq(IDX_TQ).text()), LIMIT_COUNT);

        // CLAIM: visible numbers only and must be <= LIMIT_COUNT
        totals.claim_amt += chooseSmallestUnder(parseNumbersFromString($tds.eq(IDX_CLAIM).text()), LIMIT_COUNT);

        // CROSS AMT: prefer data-value then visible
        const dv = $tds.eq(IDX_CROSS).find('[data-value]').attr('data-value') || $tds.eq(IDX_CROSS).attr('data-value') || '';
        const crossNums = (dv ? parseNumbersFromString(dv) : []).concat(parseNumbersFromString($tds.eq(IDX_CROSS).text()));
        totals.cross += chooseSmallestUnder(crossNums, Number.POSITIVE_INFINITY);

        // CROSS CLAIM: visible numbers only and must be <= LIMIT_COUNT
        totals.cross_claim += chooseSmallestUnder(parseNumbersFromString($tds.eq(IDX_CROSS_CLAIM).text()), LIMIT_COUNT);

        totals.p_and_l += (parseNumbersFromString($tds.eq(IDX_PL).text()).length ? parseNumbersFromString($tds.eq(IDX_PL).text())[0] : 0);
    });

    return totals;
}




function renderServerTotalsRow(serverTotals, clientTotals) {
    // ensure clientTotals is available
    clientTotals = clientTotals || calculateTotalsFromDom();

    // normalize server keys (money totals)
    const tq = Number(serverTotals.tq ?? serverTotals.tq_total ?? clientTotals.tq ?? 0);
    const cross = Number(serverTotals.cross ?? serverTotals.cross_total ?? serverTotals.cross_amt ?? clientTotals.cross ?? 0);
    const p_and_l = (serverTotals.p_and_l !== undefined && serverTotals.p_and_l !== null)
        ? Number(serverTotals.p_and_l)
        : clientTotals.p_and_l;

    // IMPORTANT: use clientTotals for CLAIM and CROSS_CLAIM (visible counts)
    const claim_amt = Number(clientTotals.claim_amt ?? 0);
    const cross_claim = Number(clientTotals.cross_claim ?? 0);

    const html = `
    <tr id="totals-row" class="totals-row">
        <td style="background:#2c3e50;color:#fff;font-weight:700">TOTALS</td>
        <td style="background:#2c3e50;color:#fff;font-weight:700">-</td>
        <td style="background:#2c3e50;color:#fff;font-weight:700">${(tq ?? 0).toLocaleString()}</td>
        <td style="background:#2c3e50;color:#fff;font-weight:700">${(claim_amt ?? 0).toLocaleString()}</td>
        <td style="background:#2c3e50;color:#fff;font-weight:700">${(cross ?? 0).toLocaleString()}</td>
        <td style="background:#2c3e50;color:#fff;font-weight:700">${(cross_claim ?? 0).toLocaleString()}</td>
        <td style="background:#2c3e50;color:#fff;font-weight:700">${(p_and_l ?? 0).toLocaleString()}</td>
        <td style="background:#2c3e50;color:#fff;font-weight:700">--</td>
        <td style="background:#2c3e50;color:#fff;font-weight:700">--</td>
    </tr>`;

    // append into DataTable body area
    $(dtApi.table().body()).append(html);

    // write footer: prefer server for some keys but override claim & cross_claim with clientCounts
    updateFooterFromTotals({
        tq: tq,
        t_amt: null,
        claim_amt: claim_amt,            // client count
        cross: cross,                    // server money total (or client fallback)
        cross_claim: cross_claim,        // client count
        p_and_l: p_and_l
    });
}


     function renderClientTotalsRow() {
    const totals = calculateTotalsFromDom();
    const html = `
    <tr id="totals-row" class="totals-row">
        <td style="background:#2c3e50;color:#fff;font-weight:700">TOTALS</td>
        <td style="background:#2c3e50;color:#fff;font-weight:700">-</td>
        <td style="background:#2c3e50;color:#fff;font-weight:700">${totals.tq.toLocaleString()}</td>
        <td style="background:#2c3e50;color:#fff;font-weight:700">${(totals.claim_amt ?? 0).toLocaleString()}</td>
        <td style="background:#2c3e50;color:#fff;font-weight:700">${totals.cross.toLocaleString()}</td>
        <td style="background:#2c3e50;color:#fff;font-weight:700">${(totals.cross_claim ?? 0).toLocaleString()}</td>
        <td style="background:#2c3e50;color:#fff;font-weight:700">${totals.p_and_l.toLocaleString()}</td>
        <td style="background:#2c3e50;color:#fff;font-weight:700">--</td>
        <td style="background:#2c3e50;color:#fff;font-weight:700">--</td>
    </tr>`;
    $(dtApi.table().body()).append(html);

    updateFooterFromTotals({
        tq: totals.tq,
        t_amt: null,
        claim_amt: totals.claim_amt,
        cross: totals.cross,
        cross_claim: totals.cross_claim,
        p_and_l: totals.p_and_l
    });
}


       function updateFooterFromTotals(obj) {
    const $f = $(dtApi.table().footer());
    if (!$f.length) return;
    $f.find('th').eq(0).text('TOTALS');
    $f.find('th').eq(1).text('-');
    $f.find('th').eq(2).text((obj.tq ?? 0).toLocaleString());
    $f.find('th').eq(3).text((obj.claim_amt ?? 0).toLocaleString());    // CLAIM now written properly
    $f.find('th').eq(4).text((obj.cross ?? 0).toLocaleString());
    $f.find('th').eq(5).text((obj.cross_claim ?? 0).toLocaleString());
    $f.find('th').eq(6).text((obj.p_and_l ?? 0).toLocaleString());
}


        function addTotalsRow() {
            removeTotalsRow();
            if (serverTotals) renderServerTotalsRow(serverTotals);
            else renderClientTotalsRow();
        }

        if (dtApi.on) {
            dtApi.on('draw', function () {
                dtApi.rows().every(function () {
                    const $r = $(this.node());
                    const plText = $r.find('td').eq(6).text().trim();
                    const pl = parseNum(plText);
                    $r.removeClass('row-positive row-negative');
                    if (pl < 0) $r.addClass('row-negative');
                    else if (pl > 0) $r.addClass('row-positive');
                });
                addTotalsRow();
            });
        } else {
            $(dtApi.table().node()).on('draw.dt', function () { addTotalsRow(); });
        }

        if (typeof Livewire !== 'undefined') {
            Livewire.on("ticketSubmitted", () => {
                dtApi.ajax.reload(null, false);
                const el = document.getElementById("drawDetailsUpdatedAt");
                if (el) el.innerText = new Date().toLocaleTimeString();
            });
        }
        window.addEventListener("ticket-submitted", () => {
            dtApi.ajax.reload(null, false);
            const el = document.getElementById("drawDetailsUpdatedAt");
            if (el) el.innerText = new Date().toLocaleTimeString();
        });

        setTimeout(addTotalsRow, 200);
    });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        $(document).off('click', '.addClaim');

        $(document).on('click', '.addClaim', function (ev) {
            ev.preventDefault();
            const btn = $(this);
            const drawDetailId = btn.data('draw-detail-id') || btn.attr('data-draw-detail-id');

            btn.prop('disabled', true);
            setTimeout(() => btn.prop('disabled', false), 300);

            if (!drawDetailId) return;

            try {
                if (typeof Livewire !== 'undefined' && typeof Livewire.emit === 'function') {
                    Livewire.emit('openClaimModal', { draw_details_id: drawDetailId });
                    return;
                }
            } catch (err) {}

            try {
                if (typeof Livewire !== 'undefined' && typeof Livewire.dispatch === 'function') {
                    Livewire.dispatch('claim-event', { draw_details_id: drawDetailId });
                    return;
                }
            } catch (err) {}

            const domEvent = new CustomEvent('claim-event', { detail: { draw_details_id: drawDetailId } });
            window.dispatchEvent(domEvent);

            try {
                const modalEl = document.getElementById('claimModal');
                if (modalEl) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        const hidden = modalEl.querySelector('input[name="draw_details_id"]');
                        if (hidden) hidden.value = drawDetailId;
                        modal.show();
                        return;
                    } else {
                        $(modalEl).modal ? $(modalEl).modal('show') : $(modalEl).show();
                        return;
                    }
                }
            } catch (err) {}
        });

        window.addEventListener('claim-event', function (e) {
            // no-op listener kept for backward compatibility
        });
    });
    </script>

    <script>
document.addEventListener('DOMContentLoaded', function () {
  const claimDetailsBase = "{{ url('admin/draw/draw-details') }}";

  $(document).on('click', 'a.claim-open', function (e) {
    e.preventDefault();
    const $el = $(this);
    const drawDetailId = $el.data('draw-detail-id') || '';
    const drawTime = $el.data('draw-time') || '';
    const type = $el.data('type') || 'claim';
    const value = $el.data('value') || ''; // numeric clicked value

    $('#claimModalHeader').text((type === 'claim' ? 'Claim' : 'Cross Claim') + ' of draw ' + (drawTime || ('#' + drawDetailId)));
    $('#claimDetailsTable tbody').html('<tr><td colspan="8" class="text-center">Loading...</td></tr>');
    $('#tot-tq, #tot-claim, #tot-cross-amt, #tot-cross-claim, #tot-pl').text('0');
    $('#claimDetailsModal').modal('show');

    const url = `${claimDetailsBase}/${encodeURIComponent(drawDetailId)}/claim-details?type=${encodeURIComponent(type)}&value=${encodeURIComponent(value)}`;

    $.ajax({
      url: url,
      method: 'GET',
      dataType: 'json',
      success: function (res) {
        if (res?.error) {
          $('#claimDetailsTable tbody').html('<tr><td colspan="8" class="text-center text-danger">' + escapeHtml(res.error) + '</td></tr>');
          return;
        }
        if (!res || !Array.isArray(res.data) || res.data.length === 0) {
          $('#claimDetailsTable tbody').html('<tr><td colspan="8" class="text-center">No tickets found</td></tr>');
          return;
        }
        const rows = res.data;
        let html = '', totTq = 0, totClaim = 0, totCrossAmt = 0, totCrossClaim = 0, totPL = 0;
     rows.forEach(function (r, idx) {
  const username = r.username || 'N/A';

  // prefer ticket_number, fallback to ticket_no or id
  const ticketId = r.ticket_id ?? r.id ?? '';
  const userId   = r.user_id ?? r.user_id ?? '';
  const drawId   = r.draw_detail_id ?? r.drawDetailId ?? '';
  const ticketNo = r.ticket_number ?? r.ticket_no ?? '';

  const tq = Number(r.tq || 0);
  const claim = Number(r.claim || 0);
  const crossAmt = Number(r.cross_amt || 0);
  const crossClaim = Number(r.cross_claim || 0);
  const pl = Number(r.p_and_l || 0);

  totTq += tq; totClaim += claim; totCrossAmt += crossAmt; totCrossClaim += crossClaim; totPL += pl;

  // build clickable ticket link with data attributes
  const ticketLink = `<a href="#" class="open-ticket" ` +
      `data-draw="${escapeHtml(drawId)}" ` +
      `data-ticket="${escapeHtml(ticketId)}" ` +
      `data-user="${escapeHtml(userId)}">` +
      `${escapeHtml(ticketNo)}` +
    `</a>`;

  html += '<tr>' +
            '<td>' + (idx + 1) + '</td>' +
            '<td>' + escapeHtml(username) + '</td>' +
            '<td>' + ticketLink + '</td>' +
            '<td>' + formatNumber(tq) + '</td>' +
            '<td>' + formatNumber(claim) + '</td>' +
            '<td>' + formatNumber(crossAmt) + '</td>' +
            '<td>' + formatNumber(crossClaim) + '</td>' +
            '<td>' + formatNumber(pl) + '</td>' +
          '</tr>';
});


        $('#claimDetailsTable tbody').html(html);
        $('#tot-tq').text(formatNumber(totTq));
        $('#tot-claim').text(formatNumber(totClaim));
        $('#tot-cross-amt').text(formatNumber(totCrossAmt));
        $('#tot-cross-claim').text(formatNumber(totCrossClaim));
        $('#tot-pl').text(formatNumber(totPL));
      },
      error: function (xhr) {
        let msg = 'Error fetching data';
        try {
          const data = xhr.responseJSON;
          if (data && data.error) msg = data.error;
          else if (xhr.status === 404) msg = 'Not found (404) - check route';
          else if (xhr.status === 500) msg = 'Server error (500) - check laravel logs';
        } catch (e) {}
        $('#claimDetailsTable tbody').html('<tr><td colspan="8" class="text-center text-danger">' + escapeHtml(msg) + '</td></tr>');
        console.error('Claim details request failed', xhr);
      }
    });
  });

  function formatNumber(n) {
    if (n === null || n === undefined) return '-';
    const num = Number(n);
    if (Number.isNaN(num)) return n;
    return num.toLocaleString();
  }
  function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
  }
});
</script>

<script>
(function () {
  if (window.__claim_modal_reliable_init) return;
  window.__claim_modal_reliable_init = true;

  let lastTrigger = null;

  function dbg(...args){ if (console && console.log) console.log('[claim-modal]', ...args); }

  // Delegated click handler (single binding)
  $(document).on('click', '.open-ticket', function (e) {
    e.preventDefault();
    lastTrigger = this;
    const $el = $(this);

    const drawId   = $el.data('draw');
    const ticketId = $el.data('ticket');
    const userId   = $el.data('user');

    dbg('open-ticket clicked', { drawId, ticketId, userId });

    // spinner
    $('#claimDetailsModal .modal-body').html('<div class="text-center p-4"><div class="spinner-border" role="status"></div></div>');

    // Always create a new Modal instance to avoid stale instances
    const modalEl = document.getElementById('claimDetailsModal');
    try {
      let modal;
      if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        modal = new bootstrap.Modal(modalEl, { backdrop: true, keyboard: true });
        modal.show();
      } else if (typeof $ !== 'undefined' && $(modalEl).modal) {
        $(modalEl).modal('show');
      } else {
        modalEl.style.display = 'block';
        modalEl.classList.add('show');
        modalEl.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
      }
    } catch (err) {
      console.warn('modal show error', err);
    }

    const url = `/admin/draw/ticket-modal/${encodeURIComponent(drawId)}/${encodeURIComponent(ticketId)}/${encodeURIComponent(userId)}`;
    dbg('fetch', url);
    fetch(url, { credentials: 'same-origin' })
      .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.text(); })
      .then(html => {
        $('#claimDetailsModal .modal-body').html(html);
        dbg('modal content loaded');
      })
      .catch(err => {
        console.error('Ticket modal load failed', err);
        $('#claimDetailsModal .modal-body').html('<div class="alert alert-danger">Failed to load ticket details</div>');
      });
  });

  // Prevent focus issues before hide
  const modalEl = document.getElementById('claimDetailsModal');
  if (!modalEl) return;

  modalEl.addEventListener('hide.bs.modal', function () {
    try {
      const active = document.activeElement;
      if (active && modalEl.contains(active)) active.blur();
    } catch (e) {}
  });

  // Cleanup after hidden: only remove backdrops & restore body classes/padding; do not change modal display
  modalEl.addEventListener('hidden.bs.modal', function () {
    setTimeout(function () {
      try {
        // remove any leftover backdrops
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        // clear body state
        document.body.classList.remove('modal-open');
        document.body.style.paddingRight = '';
        document.body.style.overflow = '';
        // restore focus
        if (lastTrigger && typeof lastTrigger.focus === 'function') {
          lastTrigger.focus({ preventScroll: false });
        } else {
          try { document.body.focus(); } catch(e){}
        }
      } catch (err) {
        console.warn('Modal cleanup error', err);
      } finally {
        lastTrigger = null;
      }
      dbg('modal hidden -> cleaned up');
    }, 60);
  });

  // Also listen jQuery hidden (BS4) for safety
  $(modalEl).on('hidden.bs.modal', function () {
    setTimeout(function () {
      try {
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.paddingRight = '';
        document.body.style.overflow = '';
      } catch (e) {}
      lastTrigger = null;
      dbg('jQuery hidden cleanup executed');
    }, 60);
  });

  dbg('claim modal reliable init done');
})();
</script>





@endpush

<div class="container-fluid" style="font-size: larger; color: #222;">
    <h2>Dashboard</h2>
    <x-date-range-picker-filter />

    <div class="row mb-3">
        <div class="col-8">
            <div class="card">
                <div class="card-header text-center bg-info">
                    <h4 class="text-white">Draw's Details</h4>
                </div>
                <div class="card-body">
                    {{ $dataTable->table() }}

                    <div class="text-muted small text-end mt-2">
                        Last updated: <span id="drawDetailsUpdatedAt">{{ now()->format('H:i:s') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-4">
            <livewire:dashboard-overview />
        </div>
    </div>

    @livewire('claim-add')
</div>
<!-- Claim Details Modal -->
<div id="claimDetailsModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="claimDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document" style="max-width: 1000px;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 id="claimDetailsModalLabel" class="modal-title">Claim Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><strong id="claimModalHeader">Claim Details</strong></div>

        <div class="table-responsive">
          <table id="claimDetailsTable" class="table table-striped table-sm">
            <thead>
              <tr>
                <th>#</th>
                <th>Username</th>
                <th>Ticket No</th>
                <th>TQ</th>
                <th>Claim</th>
                <th>Cross Amt</th>
                <th>Cross Claim</th>
                <th>P & L</th>
              </tr>
            </thead>
            <tbody>
              <tr><td colspan="8" class="text-center">No records</td></tr>
            </tbody>
            <tfoot>
              <tr>
                <th colspan="3">Totals</th>
                <th id="tot-tq">0</th>
                <th id="tot-claim">0</th>
                <th id="tot-cross-amt">0</th>
                <th id="tot-cross-claim">0</th>
                <th id="tot-pl">0</th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@endsection
