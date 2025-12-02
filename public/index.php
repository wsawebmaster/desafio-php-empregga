<?php
declare(strict_types=1);

use App\Autoloader;
use App\Infrastructure\Database;
use App\Http\Router;
use App\Http\JsonResponse;
use App\Http\Controller\ContactController;

require __DIR__ . '/../src/Autoloader.php';
(new Autoloader(__DIR__ . '/../src'))->register();

// Initialize database
$dbOk = true;
try {
    Database::migrate();
} catch (Throwable) {
    $dbOk = false;
}

// Setup router
$router = new Router();
$controller = new ContactController();

// API Routes
$router->add('GET', '/api/contacts', fn() => $controller->list());
$router->add('GET', '/api/contacts/{id}', fn($p) => $controller->get($p));
$router->add('POST', '/api/contacts', fn() => $controller->create());
$router->add('PUT', '/api/contacts/{id}', fn($p) => $controller->update($p));
$router->add('DELETE', '/api/contacts/{id}', fn($p) => $controller->delete($p));
$router->add('POST', '/api/contacts/{id}/phones', fn($p) => $controller->addPhone($p));
$router->add('DELETE', '/api/contacts/{id}/phones/{phoneId}', fn($p) => $controller->deletePhone($p));

// Dispatch API requests
if (str_starts_with(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/', '/api')) {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
    return;
}

// Serve web UI below
?><!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Contacts – Vue</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    :root { --bg:#0f172a; --card:#111827; --muted:#9ca3af; --accent:#22c55e; --danger:#ef4444; }
    * { box-sizing: border-box; }
    body { font-family: Inter, system-ui, sans-serif; margin: 0; background: var(--bg); color: #fff; }
    [v-cloak] { display: none; }
    .container { max-width: 960px; margin: 0 auto; padding: 24px; }
    .card { background: var(--card); border: 1px solid #1f2937; border-radius: 12px; padding: 16px; }
    h1 { font-size: 24px; margin: 0; }
    input, button { padding: 10px 12px; border-radius: 8px; border: 1px solid #334155; background: #0b1324; color: #fff; }
    input { flex: 1; }
    .row { display:flex; gap:8px; align-items:center; }
    .toolbar { display:flex; gap:8px; margin-top: 8px; }
    ul { list-style:none; padding:0; margin: 12px 0 0; }
    li { padding: 10px; border-bottom: 1px solid #1f2937; display:flex; justify-content:space-between; align-items:center; }
    .muted { color: var(--muted); }
    .pill { font-size:12px; color:#fff; background:#1f2937; padding:2px 8px; border-radius:999px; }
    .danger { background: var(--danger); border-color: var(--danger); }
    .accent { background: var(--accent); border-color: var(--accent); }
    .grid { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
    .section-title { font-size: 16px; margin: 12px 0 4px; }
    .header { display:flex; align-items:center; justify-content:space-between; margin-bottom: 12px; }
    .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.6); display:flex; align-items:center; justify-content:center; padding: 16px; }
    .modal-backdrop { z-index: 1000; }
    .modal-backdrop.confirm { z-index: 2000; }
    .modal-backdrop.alert { z-index: 3000; }
    .modal { background: var(--card); border: 1px solid #1f2937; border-radius: 12px; width: 100%; max-width: 720px; padding: 16px; }
    .modal-title { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
  </style>
  <script>
    (function(){
      var s=document.createElement('script'); s.src='https://unpkg.com/vue@3/dist/vue.global.prod.js';
      s.onerror=function(){ var f=document.createElement('script'); f.src='https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js'; document.head.appendChild(f); };
      document.head.appendChild(s);
    })();
  </script>
  <script>
    const api = {
      async req(method, url, body){
        const res = await fetch(url, {method, headers:{'Content-Type':'application/json'}, body: body?JSON.stringify(body):undefined});
        const text = await res.text(); let data={}; try{ data = text? JSON.parse(text): {}; }catch{}
        return {status: res.status, data};
      },
      list(page, per, search){ return this.req('GET', `/api/contacts?page=${page}&per_page=${per}&search=${encodeURIComponent(search)}`); },
      create(payload){ return this.req('POST','/api/contacts', payload); },
      remove(id){ return this.req('DELETE', `/api/contacts/${id}`); },
    };
  </script>
</head>
<body>
  <div id="app" class="container" v-cloak>
    <div class="card">
      <div class="header">
        <h1>Contacts</h1>
        <button class="accent" @click="openCreate">Create New User</button>
      </div>
  <?php if(!$dbOk){ ?>
  <p class="muted">Database not configured. Enable PDO SQLite in your PHP environment.</p>
  <?php } ?>
      <div class="row">
        <input v-model="search" placeholder="Search by name or email" />
        <button @click="load" class="accent">Search</button>
      </div>
      <ul>
        <li v-for="c in items" :key="c.id">
          <div style="flex:1">
            <div><strong>{{ c.name }}</strong> <span class="muted">&lt;{{ c.email }}&gt;</span></div>
            <div class="muted" v-if="c.address">{{ c.address }}</div>
          </div>
          <div class="row">
            <button @click="view(c.id)">Phones</button>
            <button @click="openEdit(c)">Edit</button>
            <button class="danger" @click="confirmDeleteContact(c.id)">Delete</button>
          </div>
        </li>
      </ul>
      <div class="grid" style="margin-top:12px">
        <div>
          <div class="section-title">List Settings</div>
          <div class="row" style="justify-content:space-between; align-items:center;">
            <input v-model.number="per" type="number" min="1" max="50" placeholder="Page size" />
            <span class="pill">Page {{ page }}</span>
            <span style="flex:1"></span>
            <div class="row" style="margin-left:auto" v-if="pager.visible">
              <button v-if="pager.showPrev" @click="prev">Prev</button>
              <button v-if="pager.showNext" @click="next">Next</button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div v-if="editModal" class="modal-backdrop">
      <div class="modal">
        <div class="modal-title">
          <div class="section-title">Edit Contact</div>
          <button @click="closeEdit">Close</button>
        </div>
        <div class="row">
          <input v-model="editForm.name" placeholder="Name" />
          <input v-model="editForm.email" placeholder="Email" />
          <input v-model="editForm.address" placeholder="Address" />
        </div>
        <div class="section-title">Phones</div>
        <div v-for="(p,i) in editForm.phones" :key="p.id ?? i" class="row" style="margin-bottom:8px">
          <input v-model="p.number" placeholder="Phone number" inputmode="numeric" pattern="[0-9]*" @input="p.number = (p.number||'').replace(/\D/g,'')" />
          <input v-model="p.label" placeholder="Label" />
          <button class="danger" :disabled="editForm.phones.length<=1" @click="removeEditPhone(i)">Remove</button>
        </div>
        <div class="toolbar"><button @click="addEditPhone">Add phone</button></div>
        <div class="toolbar">
          <button class="accent" @click="saveEdit">Save</button>
          <span class="pill" v-if="messageEdit">{{ messageEdit }}</span>
        </div>
      </div>
    </div>
    <div v-if="showCreate" class="modal-backdrop">
      <div class="modal">
        <div class="modal-title">
          <div class="section-title">Create New User</div>
          <button @click="closeCreate">Close</button>
        </div>
        <div class="row">
          <input v-model="form.name" placeholder="Name" />
          <input v-model="form.email" placeholder="Email" />
          <input v-model="form.address" placeholder="Address" />
        </div>
        <div class="section-title">Phones (optional)</div>
        <div v-for="(p,i) in form.phones" :key="i" class="row" style="margin-bottom:8px">
          <input v-model="p.number" placeholder="Phone number" inputmode="numeric" pattern="[0-9]*" @input="p.number = (p.number||'').replace(/\D/g,'')" />
          <input v-model="p.label" placeholder="Label" />
          <button class="danger" @click="removePhone(i)">Remove</button>
        </div>
        <div class="toolbar"><button @click="addPhone">Add phone</button></div>
        <div class="toolbar">
          <button class="accent" @click="create">Create</button>
          <span class="pill" v-if="message">{{ message }}</span>
        </div>
      </div>
    </div>
    <div v-if="confirm.visible" class="modal-backdrop confirm">
      <div class="modal">
        <div class="modal-title">
          <div class="section-title">Confirm Action</div>
          <button @click="confirmCancel">Close</button>
        </div>
        <p class="muted">{{ confirm.text }}</p>
        <div class="toolbar">
          <button @click="confirmCancel">Cancel</button>
          <button class="danger" @click="confirmProceed">Confirm</button>
        </div>
      </div>
    </div>
    <div v-if="alert.visible" class="modal-backdrop alert">
      <div class="modal">
        <div class="modal-title">
          <div class="section-title">Error</div>
          <button @click="alertClose">Close</button>
        </div>
        <p class="muted">{{ alert.text }}</p>
        <div class="toolbar">
          <button class="accent" @click="alertClose">OK</button>
        </div>
      </div>
    </div>
    <div v-if="phonesModal" class="modal-backdrop">
      <div class="modal">
        <div class="modal-title">
          <div class="section-title">Phones</div>
          <button @click="closePhones">Close</button>
        </div>
        <ul>
          <li v-for="p in phones" :key="p.id">
            <div style="flex:1">
              <strong>{{ p.number }}</strong>
              <span class="muted" v-if="p.label">({{ p.label }})</span>
            </div>
            <button class="danger" @click="confirmDeletePhone(p.id)">Remove</button>
          </li>
        </ul>
        <div class="row" style="margin-top:8px">
          <input v-model="newPhone.number" placeholder="Phone number" inputmode="numeric" pattern="[0-9]*" @input="newPhone.number = (newPhone.number||'').replace(/\D/g,'')" />
          <input v-model="newPhone.label" placeholder="Label (home, work)" />
          <button class="accent" @click="addPhoneToView">Add</button>
        </div>
      </div>
    </div>
  </div>
  <script>
    function initVue(){
      const { createApp, ref, watch } = Vue;
      createApp({
        setup(){
          const search = ref('');
          const items = ref([]);
          const page = ref(1);
          const per = ref(5);
          const form = ref({name:'', email:'', address:'', phones:[{number:'', label:''}]});
          const message = ref('');
          const activeId = ref(null);
          const phones = ref([]);
          const newPhone = ref({number:'', label:''});
          const showCreate = ref(false);
          const alert = ref({ visible:false, text:'' });
          const phonesModal = ref(false);
          const confirm = ref({ visible:false, text:'', onConfirm:null });
          const pager = ref({ visible:false, showPrev:false, showNext:false });
          const editModal = ref(false);
          const editForm = ref({ id:null, name:'', email:'', address:'', phones:[] });
          const editOriginalPhones = ref([]);
          const messageEdit = ref('');
          const load = async () => {
            const r = await api.list(page.value, per.value, search.value);
            items.value = (r.data.data||[]);
            const total = r.data.total ?? items.value.length;
            const hasNext = (page.value * per.value) < total;
            const hasPrev = page.value > 1;
            pager.value = {
              visible: total > per.value,
              showPrev: hasPrev,
              showNext: hasNext,
            };
          };
          watch(per, () => { page.value = 1; load(); });
          let searchTimer;
          watch(search, () => { page.value = 1; if (searchTimer) clearTimeout(searchTimer); searchTimer = setTimeout(load, 300); });
          const next = () => { page.value++; load(); };
          const prev = () => { page.value = Math.max(1, page.value-1); load(); };
          const create = async () => {
            const payload = { name: form.value.name, email: form.value.email, address: form.value.address, phones: form.value.phones };
            const r = await api.create(payload);
            if (r.status === 201) { message.value = 'Created'; form.value={name:'',email:'',address:'',phones:[{number:'',label:''}]}; showCreate.value=false; load(); }
            else {
              if (r.status === 409 || (r.data && r.data.error === 'Email already exists')) { alert.value = { visible:true, text: `Unable to complete registration: email '${form.value.email}' already exists.` }; return; }
              const errs = r.data && r.data.errors ? r.data.errors : r.data;
              if (errs && errs.phones) message.value = errs.phones;
              else if (errs && errs.email === 'invalid') message.value = 'Invalid email';
              else if (errs && (errs.name === 'required' || errs.email === 'required' || errs.address === 'required')) message.value = 'Name, email and address are required';
              else message.value = 'Please fix form errors';
            }
            setTimeout(()=> message.value='', 2500);
          };
          const openCreate = () => { showCreate.value = true; };
          const closeCreate = () => { showCreate.value = false; };
          const openEdit = async (c) => {
            editForm.value = { id:c.id, name:c.name, email:c.email, address:c.address||'', phones:[] };
            const r = await api.req('GET', `/api/contacts/${c.id}`);
            const ph = (r.data && r.data.phones) ? r.data.phones : [];
            editForm.value.phones = ph.map(p=> ({ id:p.id, number:p.number, label:p.label||'' }));
            editOriginalPhones.value = ph.map(p=> ({ id:p.id, number:p.number, label:p.label||'' }));
            editModal.value = true;
          };
          const closeEdit = () => { editModal.value = false; };
          const addEditPhone = () => { editForm.value.phones.push({ id:null, number:'', label:'' }); };
          const removeEditPhone = (i) => {
            if ((editForm.value.phones||[]).length <= 1) {
              alert.value = { visible:true, text: 'Please add another phone before removing the last one.' };
              return;
            }
            editForm.value.phones.splice(i,1);
          };
          const saveEdit = async () => {
            const payload = { name: editForm.value.name, email: editForm.value.email, address: editForm.value.address };
            const r = await api.req('PUT', `/api/contacts/${editForm.value.id}`, payload);
            if (r.status !== 200) {
              if (r.status === 409 || (r.data && r.data.error === 'Email already exists')) { messageEdit.value = `Unable to update: email '${editForm.value.email}' already exists.`; }
              else if (r.data && r.data.errors && r.data.errors.address === 'required') { messageEdit.value = 'Address is required'; }
              else if (r.data && r.data.errors && r.data.errors.email === 'invalid') { messageEdit.value = 'Invalid email'; }
              else { messageEdit.value = 'Please fix form errors'; }
              setTimeout(()=> messageEdit.value='', 2500);
              return;
            }
            const current = editForm.value.phones;
            if (current.length < 1) { alert.value = { visible:true, text: 'At least one phone number is required' }; return; }
            const original = editOriginalPhones.value;
            const byId = (arr)=>{ const m=new Map(); arr.forEach(p=>{ if(p.id) m.set(p.id, p); }); return m; };
            const origMap = byId(original);
            const currMap = byId(current);
            const newPhones = current.filter(p=> !p.id || !origMap.has(p.id));
            const updatedPhones = current.filter(p=> p.id && origMap.has(p.id) && (p.number !== origMap.get(p.id).number || (p.label||'') !== (origMap.get(p.id).label||'')));
            const removedIds = original.filter(p=> !currMap.has(p.id)).map(p=> p.id);
            for (const np of newPhones) {
              await api.req('POST', `/api/contacts/${editForm.value.id}/phones`, { number: np.number, label: np.label });
            }
            for (const up of updatedPhones) {
              await api.req('POST', `/api/contacts/${editForm.value.id}/phones`, { number: up.number, label: up.label });
              await api.req('DELETE', `/api/contacts/${editForm.value.id}/phones/${up.id}`);
            }
            for (const rid of removedIds) {
              const dr = await api.req('DELETE', `/api/contacts/${editForm.value.id}/phones/${rid}`);
              if (dr.status === 422) { alert.value = { visible:true, text: 'Please add another phone before removing the last one.' }; break; }
            }
            messageEdit.value = 'Updated';
            editModal.value = false;
            await load();
          };
          const addPhone = () => { form.value.phones.push({number:'', label:''}); };
          const removePhone = (i) => { form.value.phones.splice(i,1); };
          const del = async (id) => { await api.remove(id); await load(); };
          const view = async (id) => {
            activeId.value = id;
            phonesModal.value = true;
            const r = await api.req('GET', `/api/contacts/${id}`);
            phones.value = (r.data.phones || []);
          };
          const addPhoneToView = async () => {
            if (!activeId.value) return;
            const payload = { number: newPhone.value.number, label: newPhone.value.label };
            const r = await api.req('POST', `/api/contacts/${activeId.value}/phones`, payload);
            if (r.status === 201) { newPhone.value = {number:'',label:''}; await view(activeId.value); }
          };
          const delPhone = async (pid) => {
            if (!activeId.value) return;
            const r = await api.req('DELETE', `/api/contacts/${activeId.value}/phones/${pid}`);
            if (r.status === 204) {
              await view(activeId.value);
            } else if (r.status === 422 && r.data && r.data.errors && r.data.errors.phones === 'at_least_one_required') {
              alert.value = { visible:true, text: 'Please add another phone before removing the last one.' };
            }
          };
          const confirmDeleteContact = (id) => {
            confirm.value = { visible:true, text:'Are you sure you want to delete this contact?', onConfirm: async () => { await del(id); } };
          };
          const confirmDeletePhone = (pid) => {
            if ((phones.value||[]).length <= 1) {
              alert.value = { visible:true, text: 'Please add another phone before removing the last one.' };
              return;
            }
            confirm.value = { visible:true, text:'Are you sure you want to remove this phone?', onConfirm: async () => { await delPhone(pid); } };
          };
          const confirmProceed = async () => { if (confirm.value.onConfirm) { await confirm.value.onConfirm(); } confirm.value.visible = false; };
          const confirmCancel = () => { confirm.value.visible = false; };
          const alertClose = () => { alert.value.visible = false; };
          const closePhones = () => { phonesModal.value = false; activeId.value = null; };
          load();
          return { search, items, page, per, form, message, activeId, phones, newPhone, showCreate, pager, confirm, alert, phonesModal, editModal, editForm, messageEdit, load, next, prev, create, del, view, addPhone, removePhone, addPhoneToView, openCreate, closeCreate, openEdit, closeEdit, addEditPhone, removeEditPhone, saveEdit, confirmDeleteContact, confirmDeletePhone, confirmProceed, confirmCancel, alertClose, closePhones };
        }
      }).mount('#app');
    }
    document.addEventListener('DOMContentLoaded', function(){
      (function wait(){ if (window.Vue && window.Vue.createApp) { initVue(); } else { setTimeout(wait, 100); } })();
    });
  </script>
</body>
</html>
