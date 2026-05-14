<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Product Manager</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<style>
  *{box-sizing:border-box}
  body{font-family:Arial,sans-serif;background:#f0f4f8;margin:0;padding:30px}
  .box{background:#fff;max-width:800px;margin:40px auto;padding:35px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,.12)}
  h2{color:#1F4E79;margin-top:0}
  h3{color:#2E75B6;margin:0 0 15px}
  label{font-weight:bold;display:block;margin:15px 0 5px;color:#333}
  input[type=file]{border:2px dashed #2E75B6;padding:12px;width:100%;border-radius:6px;background:#f7fbff;cursor:pointer}

  /* Tab buttons */
  .tabs{display:flex;gap:10px;margin-bottom:25px}
  .tab{flex:1;padding:14px;border:2px solid #1F4E79;border-radius:8px;background:#fff;
       color:#1F4E79;font-size:15px;font-weight:bold;cursor:pointer;text-align:center;transition:.2s}
  .tab:hover{background:#e8f0fe}
  .tab.active{background:#1F4E79;color:#fff}
  .tab-create.active{background:#1F4E79;border-color:#1F4E79}
  .tab-update.active{background:#2E75B6;border-color:#2E75B6;color:#fff}
  .tab-export.active{background:#70AD47;border-color:#70AD47;color:#fff}
  .tab-update{border-color:#2E75B6;color:#2E75B6}
  .tab-export{border-color:#70AD47;color:#70AD47}

  .panel{display:none} .panel.active{display:block}

  /* Multiselect */
  .ms-wrap{position:relative}
  .ms-trigger{border:1px solid #ccc;border-radius:6px;padding:10px 14px;cursor:pointer;background:#fff;
              display:flex;justify-content:space-between;align-items:center;min-height:42px;flex-wrap:wrap;gap:5px}
  .ms-trigger:hover{border-color:#2E75B6}
  .ms-dropdown{display:none;position:absolute;z-index:999;background:#fff;border:1px solid #ccc;
               border-radius:6px;width:100%;max-height:240px;overflow-y:auto;box-shadow:0 4px 12px rgba(0,0,0,.15)}
  .ms-dropdown.open{display:block}
  .ms-search{padding:8px;border-bottom:1px solid #eee}
  .ms-search input{width:100%;border:1px solid #ddd;border-radius:4px;padding:6px 10px;font-size:13px}
  .ms-option{padding:8px 14px;cursor:pointer;display:flex;align-items:center;gap:8px;font-size:14px}
  .ms-option:hover{background:#f0f4f8}
  .ms-option input[type=checkbox]{accent-color:#1F4E79;width:15px;height:15px}
  .ms-option.selected{background:#e8f0fe}
  .tag{background:#1F4E79;color:#fff;padding:3px 10px;border-radius:20px;font-size:12px;
       display:inline-flex;align-items:center;gap:5px}
  .ms-placeholder{color:#aaa;font-size:14px}

  /* Buttons */
  .btn{border:none;padding:13px 30px;border-radius:6px;font-size:15px;cursor:pointer;margin-top:12px;width:100%;font-weight:bold}
  .btn-create{background:#1F4E79;color:#fff} .btn-create:hover{background:#16396e}
  .btn-update{background:#2E75B6;color:#fff} .btn-update:hover{background:#1a5c94}
  .btn-export{background:#70AD47;color:#fff} .btn-export:hover{background:#548235}
  .btn-sm{width:auto;padding:8px 20px;font-size:13px;margin-top:0}
  .btn:disabled{background:#aaa;cursor:not-allowed}

  /* Info/warn boxes */
  .info-box{background:#e8f4fd;border-left:4px solid #2E75B6;padding:10px 15px;border-radius:4px;font-size:13px;margin-top:8px}
  .warn-box{background:#fff8e1;border-left:4px solid #f9a825;padding:10px 15px;border-radius:4px;font-size:13px;margin-top:8px}
  .err-box{background:#fdecea;border-left:4px solid #e53935;padding:10px 15px;border-radius:4px;font-size:13px;margin-top:8px;display:none}

  /* Progress + log */
  .progress-wrap{margin-top:20px;display:none}
  .progress-bar-bg{background:#e0e0e0;border-radius:20px;height:20px;overflow:hidden;margin-bottom:8px}
  .progress-bar{height:100%;width:0%;transition:width .3s;border-radius:20px}
  .bar-create{background:#1F4E79} .bar-update{background:#2E75B6} .bar-export{background:#70AD47}
  .progress-label{font-size:13px;color:#555;margin-bottom:8px;text-align:center}
  .log{background:#f9f9f9;border:1px solid #ddd;border-radius:6px;padding:10px;font-size:12px;
       max-height:280px;overflow-y:auto;font-family:monospace}
  .log-ok{color:green} .log-err{color:red} .log-skip{color:orange} .log-info{color:#555}
  .summary{margin-top:12px;padding:15px;background:#e8f5e9;border-radius:6px;display:none}

  hr{border:none;border-top:1px solid #eee;margin:20px 0}
  select{width:100%;padding:10px;border:1px solid #ccc;border-radius:6px;font-size:14px}
</style>
</head>
<body>
<div class="box">
  <h2>Product Manager</h2>

  <div class="tabs">
    <div class="tab tab-create active" onclick="switchTab('create')">➕ Create Products</div>
    <div class="tab tab-update" onclick="switchTab('update')">✏️ Update Products</div>
    <div class="tab tab-export" onclick="switchTab('export')">⬇️ Export Products</div>
  </div>

  <!-- ══════════════ CREATE PANEL ══════════════ -->
  <div class="panel active" id="panel-create">
    <h3>➕ Create New Products</h3>

    <label>1. Select Categories</label>
    <div class="ms-wrap" id="ms-wrap-create">
      <div class="ms-trigger" onclick="toggleDropdown('create')">
        <span id="ms-tags-create"><span class="ms-placeholder">Choose categories...</span></span>
        <span id="ms-arrow-create">▼</span>
      </div>
      <div class="ms-dropdown" id="ms-dropdown-create">
        <div class="ms-search"><input type="text" placeholder="Search..." oninput="filterOptions(this.value,'create')" id="ms-search-create"></div>
        <div id="ms-list-create"><div style="padding:12px;color:#888">Loading...</div></div>
      </div>
    </div>
    <div class="info-box">💡 All products in this file will be assigned to the selected categories.</div>

    <label style="margin-top:18px">2. Upload File <span style="font-weight:normal;color:#888">(.xlsx or .csv)</span></label>
    <input type="file" id="file-create" accept=".csv,.xlsx,.xls" onchange="onFileChange('create')">
    <div class="err-box" id="err-create"></div>

    <button class="btn btn-create" id="btn-create" onclick="startImport('create')" disabled>➕ Create Products</button>

    <div class="progress-wrap" id="prog-create">
      <div class="progress-bar-bg"><div class="progress-bar bar-create" id="bar-create"></div></div>
      <div class="progress-label" id="label-create">Starting...</div>
      <div class="log" id="log-create"></div>
      <div class="summary" id="summary-create"></div>
    </div>
  </div>

  <!-- ══════════════ UPDATE PANEL ══════════════ -->
  <div class="panel" id="panel-update">
    <h3>✏️ Update Existing Products</h3>

    <!--div class="info-box" style="margin-bottom:15px">
      💡 <strong>How it works:</strong> Upload a file with the same columns as the create sheet.
      Products are matched by <strong>SKU</strong>. Existing products will be updated — prices, variations, images, initials add-on. No duplicates will be created.
    </div-->

    <label>1. Upload File <span style="font-weight:normal;color:#888">(.xlsx or .csv)</span></label>
    <input type="file" id="file-update" accept=".csv,.xlsx,.xls" onchange="onFileChange('update')">
    <div class="err-box" id="err-update"></div>

   

    <button class="btn btn-update" id="btn-update" onclick="startImport('update')" disabled>✏️ Update Products</button>

    <div class="progress-wrap" id="prog-update">
      <div class="progress-bar-bg"><div class="progress-bar bar-update" id="bar-update"></div></div>
      <div class="progress-label" id="label-update">Starting...</div>
      <div class="log" id="log-update"></div>
      <div class="summary" id="summary-update"></div>
    </div>
  </div>

  <!-- ══════════════ EXPORT PANEL ══════════════ -->
  <div class="panel" id="panel-export">
    <h3>⬇️ Export Products to Excel</h3>

    <label>Filter by Category <span style="font-weight:normal;color:#888">(optional)</span></label>
    <div class="ms-wrap" id="ms-wrap-export">
      <div class="ms-trigger" onclick="toggleDropdown('export')">
        <span id="ms-tags-export"><span class="ms-placeholder">All categories (or choose to filter)...</span></span>
        <span id="ms-arrow-export">▼</span>
      </div>
      <div class="ms-dropdown" id="ms-dropdown-export">
        <div class="ms-search"><input type="text" placeholder="Search..." oninput="filterOptions(this.value,'export')" id="ms-search-export"></div>
        <div id="ms-list-export"><div style="padding:12px;color:#888">Loading...</div></div>
      </div>
    </div>

    <label style="margin-top:15px">Per page (max products to export)</label>
    <select id="export-limit">
      <option value="20">20 products</option>
      <option value="50" selected>50 products</option>
      <option value="100">100 products</option>
      <option value="all">All products</option>
    </select>

    <button class="btn btn-export" id="btn-export" onclick="startExport()">⬇️ Download Excel</button>

    <div class="progress-wrap" id="prog-export">
      <div class="progress-bar-bg"><div class="progress-bar bar-export" id="bar-export"></div></div>
      <div class="progress-label" id="label-export">Fetching products...</div>
      <div class="log" id="log-export"></div>
    </div>
  </div>
</div>

<script>
const IMPORT_PHP = 'manager.php';
let allCats = [];
let selectedCats = { create: [], export: [] };
let parsedData   = { create: null, update: null };

// ── Tab switching ─────────────────────────────────
function switchTab(tab) {
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
  document.querySelectorAll('.panel').forEach(p=>p.classList.remove('active'));
  document.querySelector(`.tab-${tab}`).classList.add('active');
  document.getElementById(`panel-${tab}`).classList.add('active');
}

// ── Load categories ───────────────────────────────
window.onload = async function() {
  try {
    const res  = await fetch(`${IMPORT_PHP}?action=get_categories`);
    allCats    = await res.json();
    renderOptions(allCats,'create');
    renderOptions(allCats,'export');
  } catch(e) {
    ['create','export'].forEach(t=>{
      document.getElementById(`ms-list-${t}`).innerHTML='<div style="padding:12px;color:red">Failed to load categories.</div>';
    });
  }
};

// ── Multiselect helpers ───────────────────────────
function renderOptions(cats, panel) {
  const sel  = selectedCats[panel] || [];
  const list = document.getElementById(`ms-list-${panel}`);
  if (!cats.length) { list.innerHTML='<div style="padding:12px;color:#888">No categories</div>'; return; }
  list.innerHTML = cats.map(c=>`
    <div class="ms-option ${sel.find(s=>s.id===c.id)?'selected':''}"
         onclick="toggleCat(${c.id},'${c.name.replace(/'/g,"\\'")}','${panel}')">
      <input type="checkbox" ${sel.find(s=>s.id===c.id)?'checked':''} onclick="event.stopPropagation()">
      ${c.name} <span style="color:#aaa;font-size:11px">(${c.count})</span>
    </div>`).join('');
}
function filterOptions(q, panel) {
  renderOptions(allCats.filter(c=>c.name.toLowerCase().includes(q.toLowerCase())), panel);
}
function toggleCat(id, name, panel) {
  if (!selectedCats[panel]) selectedCats[panel]=[];
  const idx = selectedCats[panel].findIndex(s=>s.id===id);
  if (idx>-1) selectedCats[panel].splice(idx,1); else selectedCats[panel].push({id,name});
  filterOptions(document.getElementById(`ms-search-${panel}`).value, panel);
  renderTags(panel);
  if (panel==='create') checkReady('create');
}
function renderTags(panel) {
  const wrap = document.getElementById(`ms-tags-${panel}`);
  const sel  = selectedCats[panel] || [];
  if (!sel.length) {
    wrap.innerHTML = panel==='export'
      ? '<span class="ms-placeholder">All categories (or choose to filter)...</span>'
      : '<span class="ms-placeholder">Choose categories...</span>';
    return;
  }
  wrap.innerHTML = sel.map(c=>
    `<span class="tag">${c.name} <span onclick="event.stopPropagation();toggleCat(${c.id},'${c.name.replace(/'/g,"\\'")}','${panel}')">×</span></span>`
  ).join('');
}
function toggleDropdown(panel) {
  const dd = document.getElementById(`ms-dropdown-${panel}`);
  dd.classList.toggle('open');
  document.getElementById(`ms-arrow-${panel}`).textContent = dd.classList.contains('open')?'▲':'▼';
  if(dd.classList.contains('open')) document.getElementById(`ms-search-${panel}`).focus();
}
document.addEventListener('click',function(e){
  ['create','export'].forEach(panel=>{
    const wrap = document.getElementById(`ms-wrap-${panel}`);
    if(wrap && !wrap.contains(e.target)){
      document.getElementById(`ms-dropdown-${panel}`)?.classList.remove('open');
      const arr = document.getElementById(`ms-arrow-${panel}`);
      if(arr) arr.textContent='▼';
    }
  });
});

// ── normKey: must match PHP normKey() exactly ─────
function normKey(h) {
  return h.toLowerCase().trim().replace(/[^a-z0-9]+/g,'_').replace(/^_|_$/g,'');
}

// ── Parse file (xlsx or csv) ──────────────────────
async function parseFile(file) {
  const ext = file.name.split('.').pop().toLowerCase();
  let headers, rows;

  if (ext==='xlsx'||ext==='xls') {
    const buf = await file.arrayBuffer();
    const wb  = XLSX.read(buf,{type:'array'});
    const ws  = wb.Sheets[wb.SheetNames[0]];
    const raw = XLSX.utils.sheet_to_json(ws,{header:1,defval:''});
    let hi=-1;
    for(let i=0;i<raw.length;i++){
      if(raw[i].map(c=>String(c).toLowerCase()).includes('product_name')){hi=i;break;}
    }
    if(hi===-1) throw new Error('Cannot find header row with "product_name".');
    headers = raw[hi].map(c=>normKey(String(c)));
    rows=[];
    for(let i=hi+1;i<raw.length;i++){
      const row=raw[i].map(c=>String(c===undefined?'':c).trim());
      while(row.length<headers.length) row.push('');
      const name=row[headers.indexOf('product_name')]||'';
      if(!name||isHint(name)) continue;
      rows.push(row.slice(0,headers.length));
    }
  } else {
    const text=(await file.text()).replace(/^\uFEFF/,'').replace(/\r\n/g,'\n').replace(/\r/g,'\n');
    const firstLine=text.split('\n')[0];
    const delim=firstLine.split(';').length>firstLine.split(',').length?';':',';
    const lines=text.split('\n').filter(l=>l.trim());
    let hi=-1;
    for(let i=0;i<lines.length;i++){
      if(lines[i].toLowerCase().includes('product_name')){hi=i;break;}
    }
    if(hi===-1) throw new Error('Cannot find header row with "product_name".');
    function split(line){
      const res=[];let cur='';let inQ=false;
      for(let c of line){if(c==='"')inQ=!inQ;else if(c===delim&&!inQ){res.push(cur.trim());cur='';}else cur+=c;}
      res.push(cur.trim());return res;
    }
    headers=split(lines[hi]).map(h=>normKey(h));
    rows=[];
    for(let i=hi+1;i<lines.length;i++){
      const row=split(lines[i]);
      while(row.length<headers.length) row.push('');
      const name=row[headers.indexOf('product_name')]||'';
      if(!name||isHint(name)) continue;
      rows.push(row.slice(0,headers.length));
    }
  }
  if(!rows.length) throw new Error('No product rows found.');
  return {headers,rows};
}

function isHint(v){
  return ['e.g.','leave blank','yes / no','product name','filename','customer message','title text']
    .some(k=>v.toLowerCase().includes(k));
}

// ── File change handler ───────────────────────────
async function onFileChange(mode) {
  const file    = document.getElementById(`file-${mode}`).files[0];
  const errBox  = document.getElementById(`err-${mode}`);
  parsedData[mode]=null; errBox.style.display='none';
  if(!file){checkReady(mode);return;}
  try {
    parsedData[mode] = await parseFile(file);
    log(mode,`📋 <strong>${parsedData[mode].rows.length} product(s)</strong> loaded from file.`,'ok');
    document.getElementById(`prog-${mode}`).style.display='block';
  } catch(e) {
    errBox.style.display='block';
    errBox.innerHTML=`❌ ${e.message}`;
  }
  checkReady(mode);
}

function checkReady(mode) {
  const hasFile = parsedData[mode]!==null;
  const hasCats = mode==='update' ? true : (selectedCats[mode]||[]).length>0;
  document.getElementById(`btn-${mode}`).disabled=!(hasFile&&hasCats);
}

// ── Log helper ────────────────────────────────────
function log(mode,msg,type='info'){
  const div=document.getElementById(`log-${mode}`);
  div.innerHTML+=`<div class="log-${type}">${msg}</div>`;
  div.scrollTop=div.scrollHeight;
}

// ── Import (create or update) ─────────────────────
async function startImport(mode) {
  if(!parsedData[mode]) return;
  const {headers,rows}=parsedData[mode];
  const cats = mode==='create' ? (selectedCats['create']||[]) : [];

  document.getElementById(`btn-${mode}`).disabled=true;
  document.getElementById(`prog-${mode}`).style.display='block';
  document.getElementById(`summary-${mode}`).style.display='none';

  let created=0,updated=0,failed=0,skipped=0;

  for(let i=0;i<rows.length;i++){
    const pct=Math.round((i/rows.length)*100);
    document.getElementById(`bar-${mode}`).style.width=pct+'%';
    document.getElementById(`label-${mode}`).textContent=`Processing ${i+1} of ${rows.length}...`;

    const nameIdx=headers.indexOf('product_name');
    const name=rows[i][nameIdx]||`Row ${i+1}`;
    log(mode,`⏳ <strong>${name}</strong>...`);

    try {
      const fd=new FormData();
      fd.append('action',    mode==='create'?'import_row':'update_row');
      fd.append('rows',      JSON.stringify(rows));
      fd.append('headers',   JSON.stringify(headers));
      fd.append('row_index', i);
      fd.append('categories',JSON.stringify(cats));

      const res  = await fetch(IMPORT_PHP,{method:'POST',body:fd});
      const text = await res.text();
      let data;
      try{data=JSON.parse(text);}
      catch(e){throw new Error('Server error: '+text.substring(0,300));}

      if(data.status==='created'){
        const link=data.edit_url?` <a href="${data.edit_url}" target="_blank">View →</a>`:'';
        log(mode,`✅ ${data.msg}${link}`,'ok'); created++;
      } else if(data.status==='ok'||data.status==='updated'){
        const link=data.edit_url?` <a href="${data.edit_url}" target="_blank">View →</a>`:'';
        log(mode,`🔄 ${data.msg}${link}`,'ok'); updated++;
      } else if(data.status==='skipped'){
        log(mode,`⚠️ ${data.msg}`,'skip'); skipped++;
      } else {
        log(mode,`❌ ${data.msg}`,'err'); failed++;
      }
    } catch(e){
      log(mode,`❌ ${name}: ${e.message}`,'err'); failed++;
    }
    await new Promise(r=>setTimeout(r,300));
  }

  document.getElementById(`bar-${mode}`).style.width='100%';
  document.getElementById(`label-${mode}`).textContent='Done!';
  const sum=document.getElementById(`summary-${mode}`);
  sum.style.display='block';
  const countLine = mode==='update'
    ? `🔄 Updated: <strong>${updated}</strong> &nbsp; ❌ Failed: <strong>${failed}</strong> &nbsp; ⚠️ Skipped: <strong>${skipped}</strong>`
    : `✅ Created: <strong>${created}</strong> &nbsp; 🔄 Auto-updated: <strong>${updated}</strong> &nbsp; ❌ Failed: <strong>${failed}</strong> &nbsp; ⚠️ Skipped: <strong>${skipped}</strong>`;
  sum.innerHTML=`<strong>📊 Complete</strong><br>${countLine}<br><br>
    <button class="btn btn-${mode} btn-sm" onclick="location.reload()">Start Again</button>`;
  document.getElementById(`btn-${mode}`).disabled=false;
}

// ── Export ────────────────────────────────────────
async function startExport() {
  document.getElementById('btn-export').disabled=true;
  document.getElementById('prog-export').style.display='block';
  document.getElementById('log-export').innerHTML='';

  const catIds = (selectedCats['export']||[]).map(c=>c.id);
  const limit  = document.getElementById('export-limit').value;

  log('export','⏳ Fetching products from WooCommerce...','info');
  document.getElementById('bar-export').style.width='20%';

  try {
    const fd=new FormData();
    fd.append('action',   'export_products');
    fd.append('cat_ids',  JSON.stringify(catIds));
    fd.append('limit',    limit);

    const res  = await fetch(IMPORT_PHP,{method:'POST',body:fd});
    const data = await res.json();

    if(!data.products||!data.products.length){
      log('export','⚠️ No products found.','skip');
      document.getElementById('btn-export').disabled=false;
      return;
    }

    document.getElementById('bar-export').style.width='70%';
    log('export',`📦 ${data.products.length} product(s) fetched. Building Excel...`,'ok');

    buildExcel(data.products);
    document.getElementById('bar-export').style.width='100%';
    log('export',`✅ Excel downloaded successfully.`,'ok');
  } catch(e) {
    log('export',`❌ Export failed: ${e.message}`,'err');
  }
  document.getElementById('btn-export').disabled=false;
}

// ── Build Excel from exported products ────────────
function buildExcel(products) {
  const sizes = [
    ["Youth XX Small (Age 3-4)","YXX"],["Youth X Small (Age 5-6)","YXS"],
    ["Youth Small (Age 7-8)","YS"],["Youth Medium (Age 9-10)","YM"],
    ["Youth Large (Age 11-12)","YL"],["Youth X Large (Age 13-14)","YXL"],
    ["X Small (Age 15-16)","XS"],["Small","S"],["Medium","M"],
    ["Large","L"],["X Large","XL"],["XX Large","XXL"],["XXX Large","XXXL"]
  ];

  // Build header row
  const headers = ['product_id','product_name','sku_base','description','image_url','categories'];
  for(const [name,slug] of sizes){ headers.push(name); headers.push(`sku_${slug}`); }
  headers.push('initials_enable','initials_title','initials_description','initials_fee');

  const wsData = [headers];

  for(const p of products){
    const row = [
      p.id,
      p.name,
      p.sku||'',
      p.description ? p.description.replace(/<[^>]+>/g,'') : '',
      p.images?.[0]?.src||'',
      p.categories?.map(c=>c.name).join(', ')||'',
    ];

    // Map variations to sizes
    const varMap={};
    for(const v of (p.variations_detail||[])){
      const sizeAttr = v.attributes?.find(a=>a.name==='Size'||a.name==='size');
      if(sizeAttr){ varMap[sizeAttr.option]={price:v.regular_price,sku:v.sku}; }
    }

    for(const [name] of sizes){
      const vd = varMap[name];
      row.push(vd?vd.price:'');
      row.push(vd?vd.sku:'');
    }

    // Initials addon
    const addon = p.meta_data?.find(m=>m.key==='_product_addons');
    const ai    = addon?.value?.[0];
    row.push(ai?'yes':'no');
    row.push(ai?.name||'');
    row.push(ai?.description||'');
    row.push(ai?.price||'');

    wsData.push(row);
  }

  const wb = XLSX.utils.book_new();
  const ws = XLSX.utils.aoa_to_sheet(wsData);
  // Column widths
  ws['!cols'] = [{wch:10},{wch:30},{wch:16},{wch:30},{wch:30},{wch:20},
    ...Array(sizes.length*2).fill({wch:14}),
    {wch:12},{wch:28},{wch:50},{wch:10}];
  XLSX.utils.book_append_sheet(wb,ws,'Products');
  XLSX.writeFile(wb,`products-export-${new Date().toISOString().slice(0,10)}.xlsx`);
}
</script>
</body>
</html>