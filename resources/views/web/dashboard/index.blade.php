@extends('web.layouts.base')
@section('title', 'GameTicketHub')
@section('contents')
@push('custom-css')
@include('admin.includes.datatable-css-plugins')

<style>
    body{background-color:#f5f7fa!important;font-size:1.05rem!important;font-weight:500!important;color:#222!important}
    h2{font-weight:400!important;font-size:1rem!important;color:#2c3e50!important}
    .card{border:none!important;border-radius:12px!important;box-shadow:0 4px 12px rgba(0,0,0,0.08)!important;margin-bottom:20px!important}
    .card-header{font-weight:700!important;font-size:1.2rem!important;border-radius:12px 12px 0 0!important;padding:12px 15px!important}
    .card-body{font-size:1rem!important;font-weight:500!important;padding:5px 5px!important}

    table.dataTable{font-size:1.05rem!important;font-weight:600!important;text-align:center!important;width:100%!important}
    table.dataTable thead th{background-color:#2c3e50!important;color:#fff!important;text-transform:uppercase!important;font-size:1.1rem!important;padding:12px!important;font-weight:bold!important}
    table.dataTable tbody td{padding:10px!important;vertical-align:middle!important;font-weight:bold!important}

    .text-success,.bg-success{background-color:#198754!important;color:#fff!important;font-weight:700!important;border-radius:6px!important;padding:5px 10px!important}
    .text-danger,.bg-danger{background-color:#dc3545!important;color:#fff!important;font-weight:700!important;border-radius:6px!important;padding:5px 10px!important}

    tr.row-positive{background-color:#e6f4ea!important}
    tr.row-negative{background-color:#fdecea!important}

    .btn{font-size:1rem!important;font-weight:400!important;border-radius:8px!important;padding:6px 14px!important}
    .btn-warning{background-color:#ffc107!important;color:#222!important;font-weight:700!important;border:none!important}
    .btn-warning:hover{background-color:#e0a800!important;color:#fff!important}

    table.dataTable tbody tr:hover{background-color:#eef3f7!important;transition:.3s ease}
    .dataTables_scrollBody{max-height:500px!important;overflow-y:auto!important}

    div.dataTables_wrapper div.dataTables_paginate,
    div.dataTables_wrapper div.dataTables_info,
    div.dataTables_wrapper div.dataTables_length{display:none!important}

    .dataTables_scrollHead{position:sticky;top:0;z-index:100}

    .flash-green{background-color:#28a745!important;color:#fff!important;animation:flashFadeGreen 2s ease forwards}
    .flash-red{background-color:#dc3545!important;color:#fff!important;animation:flashFadeRed 2s ease forwards}
    @keyframes flashFadeGreen{0%{background-color:#28a745;color:#fff}100%{background-color:inherit;color:inherit}}
    @keyframes flashFadeRed{0%{background-color:#dc3545;color:#fff}100%{background-color:inherit;color:inherit}}

    th:nth-child(1),td:nth-child(1){white-space:nowrap}
    #totals-row{position:sticky;bottom:0;box-shadow:0 -2px 5px rgba(0,0,0,0.1)}
    #totals-row td{background:#2c3e50!important;color:#fff}
</style>
@endpush

<div class="card" style="background:linear-gradient(145deg,#0a192f,#172a45);color:#e6f1ff;">
    <div class="card-header" style="background-color:#0e76a8;">
        <h4>User Dashboard</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <div class="card" style="background:#112240;color:#e6f1ff;">
                    <div class="card-header" style="background-color:#39ff14;color:#000;">
                        <div class="col-12 mb-3">
                            <div class="d-flex justify-content-start">
                                <h5 class="me-auto">Draw List</h5>
                                @if ($total_available_draws > 0)
                                    <a href="{{ route('ticket.add') }}" class="btn btn-primary">
                                        Add A New Ticket <i class="fa fa-ticket"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-body" style="font-size:larger;color:#222;">
                        <x-date-range-picker-filter />
                        {{ $dataTable->table(['id' => 'draw-details-table']) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('custom-js')
@include('admin.includes.datatable-js-plugins')
{{ $dataTable->scripts() }}

<script>
document.addEventListener("DOMContentLoaded", function () {
    let serverTotals = @json($tableTotals ?? null);

    function parseNum(text){
        if(text===null||text===undefined) return 0;
        const n=String(text).replace(/[^0-9\.\-]/g,'');
        return parseFloat(n)||0;
    }
    function readCellValue($td){
        const $a=$td.find('a[data-value]');
        if($a.length) return parseNum($a.attr('data-value'));
        return parseNum($td.text());
    }

    const dtWrapper = window.LaravelDataTables && window.LaravelDataTables['draw-details-table']
        ? window.LaravelDataTables['draw-details-table']
        : (window.$ && $('#draw-details-table').DataTable ? $('#draw-details-table').DataTable() : null);

    if(!dtWrapper) return;
    const dtApi = dtWrapper.api ? dtWrapper.api() : dtWrapper;
    window.__drawsDtApi = window.__drawsDtApi || (dtWrapper && (dtWrapper.api ? dtWrapper.api() : dtWrapper)) || null;

    function getStartDate(){return document.querySelector('input[name="start_date"]')?.value||''}
    function getEndDate(){return document.querySelector('input[name="end_date"]')?.value||''}
    function getDayToken(){return document.querySelector('input[name="day"]')?.value||''}

    $('#draw-details-table').off('preXhr.dt.dateFilter').on('preXhr.dt.dateFilter', function(e,settings,data){
        data.start_date=getStartDate();
        data.end_date=getEndDate();
        data.day=getDayToken();
    });

    const dateForm=document.getElementById('date-range-picker-form');
    if(dateForm){
        dateForm.addEventListener('submit',function(ev){
            ev.preventDefault();
            if(dtApi&&dtApi.ajax) dtApi.ajax.reload(null,false);
            try{
                const params=new URLSearchParams(window.location.search);
                if(getStartDate()&&getEndDate()){
                    params.set('start_date',getStartDate());
                    params.set('end_date',getEndDate());
                    params.set('day',getDayToken()||'');
                }else{
                    params.delete('start_date');params.delete('end_date');params.delete('day');
                }
                history.replaceState({},'',window.location.pathname+(params.toString()?('?'+params.toString()):''));
            }catch(_){}
        });
    }

    document.querySelectorAll('#date-range-picker-form .reset-btn').forEach(function(btn){
        btn.addEventListener('click',function(ev){
            ev.preventDefault();
            document.querySelector('input[name="start_date"]').value='';
            document.querySelector('input[name="end_date"]').value='';
            document.querySelector('input[name="day"]').value='';
            const span=document.querySelector('.date-range-picker span');
            if(span) span.textContent='Today';
            if(dtApi&&dtApi.ajax) dtApi.ajax.reload(null,false);
            try{
                const params=new URLSearchParams(window.location.search);
                params.delete('start_date');params.delete('end_date');params.delete('day');
                history.replaceState({},'',window.location.pathname+(params.toString()?('?'+params.toString()):''));
            }catch(_){}
        });
    });

    function removeTotalsRow(){ $('#totals-row').remove(); }
    function calculateTotalsFromDom(){
        const totals={tq:0,t_amt:0,claim_amt:0,cross:0,cross_claim:0,p_and_l:0};
        dtApi.rows({page:'current'}).every(function(){
            const $tr=$(this.node());
            const $tds=$tr.find('td');
            const IDX_TQ=2, IDX_TAMT=3, IDX_CLAIM=4, IDX_CROSS=5, IDX_PL=6;
            totals.tq += readCellValue($tds.eq(IDX_TQ));
            totals.t_amt += readCellValue($tds.eq(IDX_TAMT));
            totals.claim_amt += readCellValue($tds.eq(IDX_CLAIM));
            totals.cross += readCellValue($tds.eq(IDX_CROSS));
            totals.p_and_l += parseNum($tds.eq(IDX_PL).text());
        });
        return totals;
    }
    function updateFooterFromTotals(obj){
        const $f=$(dtApi.table().footer());
        if(!$f.length) return;
        $f.find('th').eq(0).text('TOTALS');
        $f.find('th').eq(1).text('-');
        $f.find('th').eq(2).text((obj.tq??0).toLocaleString());
        $f.find('th').eq(3).text('--');
        $f.find('th').eq(4).text((obj.cross??0).toLocaleString());
        $f.find('th').eq(5).text((obj.cross_claim??0).toLocaleString());
        $f.find('th').eq(6).text((obj.p_and_l??0).toLocaleString());
    }
    function renderServerTotalsRow(t){
        const clientTotals=calculateTotalsFromDom();
        const tq=Number(t.tq??t.tq_total??0);
        const claim_amt=Number(t.claim_amt??t.claim_amount??0);
        const cross=Number(t.cross??t.cross_total??0);
        const cross_claim=Number(t.cross_claim??t.cross_claim_amount??0);
        const p_and_l=(t.p_and_l!==undefined && t.p_and_l!==null)?Number(t.p_and_l):clientTotals.p_and_l;
        const html = `
            <tr id="totals-row" class="totals-row">
                <td style="background:#2c3e50;color:#fff;font-weight:700">TOTALS</td>
                <td style="background:#2c3e50;color:#fff;font-weight:700">-</td>
                <td style="background:#2c3e50;color:#fff;font-weight:700">${tq.toLocaleString()}</td>
                <td style="background:#2c3e50;color:#fff;font-weight:700">--</td>
                <td style="background:#2c3e50;color:#fff;font-weight:700">${cross.toLocaleString()}</td>
                <td style="background:#2c3e50;color:#fff;font-weight:700">${cross_claim.toLocaleString()}</td>
                <td style="background:#2c3e50;color:#fff;font-weight:700">${p_and_l.toLocaleString()}</td>
                <td style="background:#2c3e50;color:#fff;font-weight:700">--</td>
            </tr>`;
        $(dtApi.table().body()).append(html);
        updateFooterFromTotals({tq,t_amt:null,claim_amt,cross,cross_claim,p_and_l});
    }
    function renderClientTotalsRow(){
        const totals=calculateTotalsFromDom();
        const html = `
            <tr id="totals-row" class="totals-row">
                <td style="background:#2c3e50;color:#fff;font-weight:700">TOTALS</td>
                <td style="background:#2c3e50;color:#fff;font-weight:700">-</td>
                <td style="background:#2c3e50;color:#fff;font-weight:700">${totals.tq.toLocaleString()}</td>
                <td style="background:#2c3e50;color:#fff;font-weight:700">--</td>
                <td style="background:#2c3e50;color:#fff;font-weight:700">${totals.cross.toLocaleString()}</td>
                <td style="background:#2c3e50;color:#fff;font-weight:700">${(totals.cross_claim??0).toLocaleString()}</td>
                <td style="background:#2c3e50;color:#fff;font-weight:700">${totals.p_and_l.toLocaleString()}</td>
                <td style="background:#2c3e50;color:#fff;font-weight:700">--</td>
            </tr>`;
        $(dtApi.table().body()).append(html);
        updateFooterFromTotals({
            tq:totals.tq,t_amt:null,claim_amt:totals.claim_amt,cross:totals.cross,cross_claim:totals.cross_claim,p_and_l:totals.p_and_l
        });
    }
    function addTotalsRow(){
        removeTotalsRow();
        if(serverTotals) renderServerTotalsRow(serverTotals);
        else renderClientTotalsRow();
    }

    $('#draw-details-table').off('xhr.dt.tableTotals').on('xhr.dt.tableTotals', function(e,settings,json){
        if(!json){ serverTotals=null; removeTotalsRow(); addTotalsRow(); return; }
        let newTotals=null;
        if(json.tableTotals) newTotals=json.tableTotals;
        else if(json.meta && json.meta.tableTotals) newTotals=json.meta.tableTotals;
        if(!newTotals){ serverTotals=null; removeTotalsRow(); addTotalsRow(); return; }
        Object.keys(newTotals).forEach(k=>{
            const v=newTotals[k];
            newTotals[k]=(v===null||v===undefined||v==='')?0:Number(v);
        });
        serverTotals=newTotals;
        removeTotalsRow();
        addTotalsRow();
    });

    if(dtApi.on){
        dtApi.on('draw',function(){
            dtApi.rows().every(function(){
                const $r=$(this.node());
                const plText=$r.find('td').eq(6).text().trim();
                const pl=parseNum(plText);
                $r.removeClass('row-positive row-negative');
                if(pl<0) $r.addClass('row-negative');
                else if(pl>0) $r.addClass('row-positive');
            });
            addTotalsRow();
        });
    }else{
        $(dtApi.table().node()).on('draw.dt',function(){ addTotalsRow(); });
    }

    setTimeout(addTotalsRow,200);

    (function robustRealtimeSetup(){
        window.__drawsDtApi = window.__drawsDtApi || (dtWrapper && (dtWrapper.api ? dtWrapper.api() : dtWrapper)) || null;

        function getDtApiForReload(){
            if(window.__drawsDtApi && window.__drawsDtApi.ajax) return window.__drawsDtApi;
            if(window.LaravelDataTables && window.LaravelDataTables['draw-details-table']){
                try{ return window.LaravelDataTables['draw-details-table'].api(); }catch(_){}
            }
            try{ return (window.$ && $('#draw-details-table').DataTable)?$('#draw-details-table').DataTable():null; }catch(_){}
            return null;
        }

        window.addEventListener('storage', function(ev){
            if(!ev) return;
            if(ev.key==='ticket-submitted'){
                try{
                    if(window.__drawsDtApi && window.__drawsDtApi.ajax){ window.__drawsDtApi.ajax.reload(null,false); return; }
                    if(window.LaravelDataTables && window.LaravelDataTables['draw-details-table']){
                        try{ window.LaravelDataTables['draw-details-table'].api().ajax.reload(null,false); return; }catch(_){}
                    }
                    try{
                        const dt=(window.$ && $('#draw-details-table').DataTable)?$('#draw-details-table').DataTable():null;
                        if(dt && dt.ajax) dt.ajax.reload(null,false);
                    }catch(_){}
                }catch(_){}
            }
        });

        let __lastDtReload=0;
        const __dtReloadDebounceMs=800;
        function reloadDt(){
            const now=Date.now();
            if(now-__lastDtReload<__dtReloadDebounceMs) return;
            __lastDtReload=now;
            const api=getDtApiForReload();
            if(!api||!api.ajax) return;
            api.ajax.reload(null,false);
        }

        if(typeof Livewire!=='undefined'){
            Livewire.on('ticketSubmitted',()=>reloadDt());
            Livewire.on('ticket-submitted',()=>reloadDt());
            try{ Livewire.hook && Livewire.hook('message.processed',()=>reloadDt()); }catch(_){}
        }

        window.addEventListener('ticket-submitted',function(){ reloadDt(); });

        if(typeof Livewire!=='undefined'){
            Livewire.on('ticketDeleted',()=>reloadDt());
            Livewire.on('ticket-deleted',()=>reloadDt());
        }
        window.addEventListener('ticket-deleted',function(){ reloadDt(); });
        window.addEventListener('storage',function(ev){
            if(!ev||!ev.key) return;
            if(ev.key==='ticket-deleted'){ reloadDt(); }
        });

        try{
            let totalsPanel=null;
            const possibleHeaders=Array.from(document.querySelectorAll('h4, h3, .card-header, .card-title, .panel-heading'));
            totalsPanel=possibleHeaders.find(h=>h.textContent && h.textContent.trim().toLowerCase().includes('total admin'));
            if(!totalsPanel) totalsPanel=possibleHeaders.find(h=>h.textContent && h.textContent.trim().toLowerCase().includes('total'));
            if(totalsPanel) totalsPanel=totalsPanel.closest('.card')||totalsPanel.parentElement;
            if(!totalsPanel) totalsPanel=document.querySelector('.col-4')||document.querySelector('.right-panel')||document.querySelector('.sidebar-right');
            if(totalsPanel){
                const observer=new MutationObserver(()=>{
                    reloadDt();
                });
                observer.observe(totalsPanel,{attributes:false,childList:true,subtree:true,characterData:true});
                window.__drawsTotalsObserver=observer;
            }
        }catch(_){}
    })();
});
</script>
@endpush
@endsection
