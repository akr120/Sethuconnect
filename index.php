<?php
// index.php - simple SPA
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>DPI for Migrant Workers — PHP Demo</title>
<style>
  body{font-family:system-ui,Segoe UI,Roboto,Arial;margin:12px}
  .card{border:1px solid #ddd;padding:12px;border-radius:8px;margin-bottom:12px;max-width:900px}
  input,textarea,select{padding:8px;border-radius:6px;border:1px solid #ccc;width:100%}
  button{padding:8px 12px;border-radius:6px;border:none;background:#2563eb;color:white}
  .row{display:flex;gap:8px}
</style>
</head>
<body>
<div id="app"></div>

<script>
async function api(action, method='GET', body=null) {
  const url = 'api.php?action='+encodeURIComponent(action);
  const opts = { method, headers: {} };
  if (body) { opts.headers['Content-Type']='application/json'; opts.body = JSON.stringify(body); }
  const res = await fetch(url, opts);
  return res.json();
}

let state = { user: null, jobs: [] };

function el(tag,props={},children=[]) {
  const e = document.createElement(tag);
  for (const k in props) {
    if (k.startsWith('on')) e.addEventListener(k.slice(2), props[k]);
    else e.setAttribute(k, props[k]);
  }
  (Array.isArray(children)?children:[children]).flat().forEach(ch => {
    if (typeof ch === 'string') e.appendChild(document.createTextNode(ch));
    else if (ch) e.appendChild(ch);
  });
  return e;
}

async function init() {
  const res = await api('init');
  state.user = res.session;
  await loadJobs();
  render();
}

async function loadJobs() {
  const rows = await api('list_jobs');
  state.jobs = rows;
}

async function handleRegister(ev) {
  ev.preventDefault();
  const f = ev.target;
  const data = {
    name: f.name.value, email: f.email.value, password: f.password.value,
    phone: f.phone.value, aadhaar: f.aadhaar.value, language: 'en'
  };
  const res = await api('register','POST',data);
  if (res.ok) { state.user = res.user; await loadJobs(); render(); } else alert(JSON.stringify(res));
}

async function handleLogin(ev) {
  ev.preventDefault();
  const f = ev.target;
  const res = await api('login','POST',{ email: f.email.value, password: f.password.value });
  if (res.ok) { state.user = res.user; await loadJobs(); render(); } else alert(JSON.stringify(res));
}

async function handleLogout() {
  await api('logout','POST'); state.user = null; render();
}

async function handlePostJob(ev){
  ev.preventDefault();
  const f = ev.target;
  const res = await api('create_job','POST',{ title: f.title.value, description: f.description.value, employer: f.employer.value, state: f.state.value, city: f.city.value, min_wage: f.min_wage.value });
  alert(res.ok ? 'Posted job ' + res.id : JSON.stringify(res));
  await loadJobs(); render();
}

function render(){
  const root = document.getElementById('app'); root.innerHTML = '';
  const container = el('div',{});
  container.appendChild(el('h1',{},'DPI for Migrant Workers — PHP Demo'));

  if (!state.user) {
    // register + login
    const reg = el('form',{onsubmit:handleRegister,class:'card'},[
      el('h3',{},'Register'),
      el('div',{},[el('label',{},'Name'), el('input',{name:'name'})]),
      el('div',{},[el('label',{},'Email'), el('input',{name:'email',type:'email'})]),
      el('div',{},[el('label',{},'Password'), el('input',{name:'password',type:'password'})]),
      el('div',{},[el('label',{},'Aadhaar (mock)'), el('input',{name:'aadhaar'})]),
      el('div',{},[el('label',{},'Phone'), el('input',{name:'phone'})]),
      el('div',{},[el('button',{type:'submit'},'Register')])
    ]);
    const login = el('form',{onsubmit:handleLogin,class:'card'},[
      el('h3',{},'Login'),
      el('div',{},[el('label',{},'Email'), el('input',{name:'email',type:'email'})]),
      el('div',{},[el('label',{},'Password'), el('input',{name:'password',type:'password'})]),
      el('div',{},[el('button',{type:'submit'},'Login')])
    ]);
    container.appendChild(reg); container.appendChild(login);
  } else {
    container.appendChild(el('div',{class:'card'},[
      el('div',{},['Hello, ', el('strong',{},state.user.name), ' ']),
      el('div',{},[el('button',{onclick:handleLogout},'Logout')])
    ]));

    // post job
    container.appendChild(el('form',{onsubmit:handlePostJob,class:'card'},[
      el('h3',{},'Post Job (demo)'),
      el('div',{},[el('input',{name:'title',placeholder:'Job title'})]),
      el('div',{},[el('input',{name:'employer',placeholder:'Employer'})]),
      el('div',{},[el('input',{name:'state',placeholder:'State'})]),
      el('div',{},[el('input',{name:'city',placeholder:'City'})]),
      el('div',{},[el('input',{name:'min_wage',placeholder:'Min wage'})]),
      el('div',{},[el('textarea',{name:'description',placeholder:'Short description'})]),
      el('div',{},[el('button',{type:'submit'},'Post Job')])
    ]));
  }

  // Jobs list
  const jobsCard = el('div',{class:'card'},[el('h3',{},'Jobs near you')]);
  state.jobs.forEach(j=>{
    jobsCard.appendChild(el('div',{class:'card'},[
      el('strong',{},j.title),
      el('div',{},`${j.employer} — ${j.city}, ${j.state}`),
      el('div',{},`Wage: ₹${j.min_wage}`)
    ]));
  });
  container.appendChild(jobsCard);

  // Ration portability check
  container.appendChild(el('div',{class:'card'},[
    el('h3',{},'Check Ration Portability (mock)'),
    el('div',{},[el('input',{id:'ration_aad'}), el('button',{onclick:async ()=>{ const aad = document.getElementById('ration_aad').value; const res = await api('ration_check','GET'); }},'Check (enter aad in prompt)')]),
    el('div',{},[el('button',{onclick:async ()=>{
      const aad = prompt('Enter Aadhaar (mock):');
      if (!aad) return;
      const res = await fetch('api.php?action=ration_check&aadhaar='+encodeURIComponent(aad));
      const json = await res.json();
      alert(JSON.stringify(json));
    }},'Prompt-check Ration')])
  ]));

  root.appendChild(container);
}

init();
</script>
</body>
</html>
