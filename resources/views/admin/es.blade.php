@extends('layouts.app', [
    'title' => 'Dashboard Eksekutif (ES)',
    'sidebar' => true,
    'activeRoute' => 'es',
    'dashboardRoute' => 'admin.dashboard',
])

@section('content')
<div id="esDashboard" data-es-base="{{ url('/admin/es/data') }}" data-es-target-url="{{ route('admin.es.target.store') }}">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h3 class="mb-1"><i class="bi bi-speedometer2 me-2"></i>Dashboard Eksekutif</h3>
            <p class="text-muted mb-0 small">Integrasi TPS, OAS, KMS, MIS, DSS. Omzet hanya transaksi <strong>completed</strong>.</p>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap" id="esFilter">
            <div class="btn-group btn-group-sm" role="group" aria-label="Periode">
                <button type="button" class="btn btn-outline-primary" data-preset="7">7 hari</button>
                <button type="button" class="btn btn-primary" data-preset="30">30 hari</button>
                <button type="button" class="btn btn-outline-primary" data-preset="90">90 hari</button>
                <button type="button" class="btn btn-outline-primary" data-preset="bulan_ini">Bulan ini</button>
            </div>
            <input type="date" class="form-control form-control-sm" id="esFrom" aria-label="Dari tanggal">
            <input type="date" class="form-control form-control-sm" id="esTo" aria-label="Sampai tanggal">
            <button type="button" class="btn btn-sm btn-secondary" id="esApplyCustom">Terapkan</button>
            <span class="small text-muted" id="esPeriodLabel"></span>
        </div>
    </div>

    <div id="esAlerts" class="mb-3"></div>

    <div class="row g-3 mb-3" id="esKpis">
        @foreach([['Omzet','revenue','currency'],['Transaksi','transactions'],['Produk Terlaris','top_product'],['Stok Kritis','critical_stock'],['Restock Pending','pending_restock'],['Supplier Terbaik','best_supplier']] as [$label,$key])
        <div class="col-6 col-xl-2"><div class="card shadow-sm h-100 border-0"><div class="card-body py-2">
            <div class="text-muted small">{{$label}}</div>
            <div class="fw-bold" data-kpi="{{$key}}" data-kpi-wrap>Memuat...</div>
            <div class="small" data-kpi-sub="{{$key}}"></div>
        </div></div></div>
        @endforeach
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-8"><div class="card shadow-sm border-0 h-100"><div class="card-header bg-white fw-semibold">Omzet (TPS)</div><div class="card-body"><div class="es-chart-state small text-muted" data-state="revenue-trend">Memuat...</div><canvas id="chRevenueTrend" height="110"></canvas></div></div></div>
        <div class="col-12 col-lg-4"><div class="card shadow-sm border-0 h-100"><div class="card-header bg-white fw-semibold">Target Omzet Bulan Ini</div><div class="card-body" id="esTargetCard"><div class="small text-muted">Memuat...</div></div></div></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6"><div class="card shadow-sm border-0"><div class="card-header bg-white fw-semibold">Penjualan per Kategori (TPS)</div><div class="card-body"><div class="es-chart-state small text-muted" data-state="sales-by-category">Memuat...</div><canvas id="chSalesCategory" height="140"></canvas></div></div></div>
        <div class="col-12 col-md-6"><div class="card shadow-sm border-0"><div class="card-header bg-white fw-semibold">5 Produk Terlaris (TPS)</div><div class="card-body"><div class="es-chart-state small text-muted" data-state="top-products">Memuat...</div><canvas id="chTopProducts" height="140"></canvas></div></div></div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4"><div class="card shadow-sm border-0"><div class="card-header bg-white fw-semibold">Metode Pembayaran (TPS)</div><div class="card-body"><div class="es-chart-state small text-muted" data-state="payment-methods">Memuat...</div><canvas id="chPayment" height="180"></canvas></div></div></div>
        <div class="col-12 col-md-4"><div class="card shadow-sm border-0"><div class="card-header bg-white fw-semibold">Status Transaksi</div><div class="card-body"><div class="es-chart-state small text-muted" data-state="order-status">Memuat...</div><canvas id="chOrderStatus" height="180"></canvas></div></div></div>
        <div class="col-12 col-md-4"><div class="card shadow-sm border-0"><div class="card-header bg-white fw-semibold">Skor Q Ranking Supplier (DSS)</div><div class="card-body"><div class="es-chart-state small text-muted" data-state="supplier-ranking">Memuat...</div><canvas id="chSupplier" height="180"></canvas></div></div></div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-12"><div class="card shadow-sm border-0"><div class="card-header bg-white fw-semibold">Stok vs Reorder Point (OAS)</div><div class="card-body"><div class="es-chart-state small text-muted" data-state="stock-vs-reorder">Memuat...</div><canvas id="chStock" height="90"></canvas></div></div></div>
    </div>

    <div class="card shadow-sm border-0 mb-3"><div class="card-header bg-white fw-semibold">Integrasi Sistem</div><div class="card-body">
        <div class="d-flex flex-wrap gap-2 mb-3 align-items-center" id="esFlow"></div>
        <div class="row g-3" id="esIntegration"></div>
    </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function(){
  const root = document.getElementById('esDashboard');
  if(!root) return;
  const base = root.dataset.esBase;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  let range = {preset:'30'};
  const charts = {};
  function token(name, fallback){ return getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback; }
  function colors(){ return [token('--lm-chart-1','#2563eb'),token('--lm-chart-2','#16a34a'),token('--lm-chart-3','#f59e0b'),token('--lm-chart-4','#ef4444'),token('--lm-chart-5','#8b5cf6')]; }
  function applyChartTheme(){ if(typeof Chart==='undefined') return; Chart.defaults.color=token('--lm-muted','#64748b'); Chart.defaults.borderColor=token('--lm-border','#e2e8f0'); }
  function qs(){ const p=new URLSearchParams(range); return p.toString(); }
  async function get(path){ const r=await fetch(base+'/'+path+'?'+qs(),{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}}); if(!r.ok){ let m='Gagal memuat data'; try{m=(await r.json()).message||m;}catch(e){} throw new Error(m);} return r.json(); }
  function setState(name,msg,show){ const el=document.querySelector('[data-state="'+name+'"]'); if(el){el.textContent=msg||''; el.style.display=show===false?'none':'block'; if(msg==='' ) el.style.display='none';} }
  function renderChart(id,name,type,data,opts){
    const ctx=document.getElementById(id); if(!ctx||typeof Chart==='undefined') return;
    applyChartTheme();
    const c=colors(); const empty=!data.labels||data.labels.length===0;
    if(empty){ setState(name, data.meta?.message||'Tidak ada data pada periode ini', true); if(charts[id]){charts[id].destroy(); delete charts[id];} return; }
    setState(name,'',false);
    if(charts[id]) charts[id].destroy();
    const isPie=type==='pie'||type==='doughnut';
    const ds=(data.datasets||[]).map((d,i)=>{const bg=isPie?(data.labels||[]).map((_,idx)=>c[idx%c.length]):c[i%c.length];return Object.assign({borderWidth:1,backgroundColor:bg,borderColor:bg}, d, type==='line'?{fill:false,tension:.25}:{});});
    charts[id]=new Chart(ctx,{type,data:{labels:data.labels,datasets:ds},options:Object.assign({responsive:true,maintainAspectRatio:true,plugins:{legend:{display:isPie||ds.length>1}}}, (type==='bar'&&opts?.horizontal)?{indexAxis:'y'}:{})});
  }
  function fmtMoney(v){ return 'Rp '+Number(v||0).toLocaleString('id-ID'); }
  async function loadSummary(){ try{ const d=await get('summary'); document.querySelector('[data-kpi="revenue"]').textContent=fmtMoney(d.revenue.value);
    const sub=document.querySelector('[data-kpi-sub="revenue"]'); const up=d.revenue.direction==='up'; sub.innerHTML='<span class="'+(up?'text-success':'text-danger')+'">'+(up?'▲':'▼')+' '+Math.abs(d.revenue.change_percent)+'%</span> vs periode sebelumnya';
    document.querySelector('[data-kpi="transactions"]').textContent=d.transactions.value;
    document.querySelector('[data-kpi="top_product"]').textContent=d.top_product.name||'-'; document.querySelector('[data-kpi-sub="top_product"]').textContent=(d.top_product.sold||0)+' terjual';
    document.querySelector('[data-kpi="critical_stock"]').textContent=d.critical_stock.value;
    document.querySelector('[data-kpi="pending_restock"]').textContent=d.pending_restock.value;
    document.querySelector('[data-kpi="best_supplier"]').textContent=d.best_supplier?.name||'-'; document.querySelector('[data-kpi-sub="best_supplier"]').textContent=d.best_supplier?('Q='+d.best_supplier.q):'';
    document.getElementById('esPeriodLabel').textContent=d.period.label;
  }catch(e){ document.querySelectorAll('[data-kpi-wrap]').forEach(el=>el.textContent='-'); }}
  async function loadCharts(){
    const defs=[['revenue-trend','chRevenueTrend','line'],['sales-by-category','chSalesCategory','bar'],['top-products','chTopProducts','bar',{horizontal:true}],['payment-methods','chPayment','pie'],['order-status','chOrderStatus','doughnut'],['stock-vs-reorder','chStock','bar'],['supplier-ranking','chSupplier','bar']];
    for(const [name,id,type,opt] of defs){ try{ renderChart(id,name,type,await get('charts/'+name),opt); }catch(e){ setState(name,'Gagal memuat: '+e.message,true); } }
  }
  async function loadAlerts(){ try{ const d=await get('alerts'); const box=document.getElementById('esAlerts');
    if(!d.items||!d.items.length){ box.innerHTML=''; return; }
    box.innerHTML=d.items.map(a=>'<div class="alert alert-'+(a.level==='danger'?'danger':a.level==='warning'?'warning':'info')+' py-2 mb-2"><strong>['+a.source+'] '+a.title+':</strong> '+a.message+'</div>').join('');
  }catch(e){ document.getElementById('esAlerts').innerHTML='<div class="alert alert-warning py-2">Gagal memuat peringatan.</div>'; } }
  async function loadIntegration(){ try{ const d=await get('integration'); document.getElementById('esFlow').innerHTML=(d.flow||[]).map((s,i)=>'<span class="badge text-bg-secondary">'+s+'</span>'+((i<d.flow.length-1)?'<i class="bi bi-arrow-right text-muted"></i>':'')).join('');
    document.getElementById('esIntegration').innerHTML=(d.systems||[]).map(s=>'<div class="col-12 col-md-6 col-xl-4"><div class="border rounded p-3 h-100"><div class="d-flex justify-content-between"><strong>'+s.name+'</strong><a class="small" href="'+s.link+'">Buka</a></div><div class="text-muted small mb-2">'+s.desc+'</div>'+(s.metrics||[]).map(m=>'<div class="small"><span class="text-muted">'+m.label+':</span> <strong>'+renderMetric(m)+'</strong></div>').join('')+'</div></div>').join('');
  }catch(e){ document.getElementById('esIntegration').innerHTML='<div class="text-danger small">Gagal memuat integrasi.</div>'; } }
  function renderMetric(m){ if(m.format==='currency') return fmtMoney(m.value); if(m.format==='hours') return (m.value==null?'-':m.value+' jam'); if(m.format==='list') return (m.value||[]).join('; '); if(m.format==='map') return Object.entries(m.value||{}).map(([k,v])=>k+': '+v).join(', ')||'-'; return m.value; }
  async function loadTarget(){ try{ const n=new Date(); const p=n.getFullYear()+'-'+String(n.getMonth()+1).padStart(2,'0'); const r=await fetch(base+'/target?period='+p,{headers:{'Accept':'application/json'}}); const d=await r.json(); const card=document.getElementById('esTargetCard');
    card.innerHTML='<div class="small text-muted mb-1">Periode '+d.period+'</div><div class="progress mb-2" style="height:10px"><div class="progress-bar bg-success" style="width:'+Math.min(100,d.percent_raw||0)+'%"></div></div><div class="small"><strong>'+Math.round(d.percent_raw||0)+'%</strong> — '+fmtMoney(d.actual)+' / '+fmtMoney(d.target)+' (sisa '+fmtMoney(d.remaining)+')</div>'+
      '<form class="mt-3 d-flex gap-2 flex-wrap" id="esTargetForm"><input type="hidden" name="period" value="'+d.period+'"><input type="number" step="0.01" min="1" name="revenue_target" class="form-control form-control-sm" style="max-width:180px" value="'+(d.target||'')+'" placeholder="Target omzet" required><button class="btn btn-sm btn-primary">Simpan Target</button></form>';
    document.getElementById('esTargetForm').onsubmit=async (e)=>{ e.preventDefault(); const fd=new FormData(e.target); const res=await fetch(root.dataset.esTargetUrl,{method:'POST',headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf},body:fd}); if(res.ok) loadTarget(); else alert('Gagal menyimpan target.'); };
  }catch(e){ document.getElementById('esTargetCard').innerHTML='<div class="text-danger small">Gagal memuat target.</div>'; } }
  function reload(){ loadSummary(); loadCharts(); loadAlerts(); loadIntegration(); loadTarget(); }
  document.querySelectorAll('#esFilter [data-preset]').forEach(btn=>btn.addEventListener('click',()=>{ range={preset:btn.dataset.preset}; document.querySelectorAll('#esFilter [data-preset]').forEach(b=>b.classList.toggle('btn-primary',b===btn)); document.querySelectorAll('#esFilter [data-preset]').forEach(b=>b.classList.toggle('btn-outline-primary',b!==btn)); document.getElementById('esFrom').value=''; document.getElementById('esTo').value=''; reload(); }));
  document.getElementById('esApplyCustom').addEventListener('click',()=>{ const f=document.getElementById('esFrom').value,t=document.getElementById('esTo').value; if(f&&t){ range={from:f,to:t}; document.querySelectorAll('#esFilter [data-preset]').forEach(b=>{b.classList.remove('btn-primary'); b.classList.add('btn-outline-primary');}); reload(); }});
  document.addEventListener('lm:themechange', ()=>{ loadCharts(); });
  reload();
})();
</script>
@endsection
