(function(){const q=document.getElementById('q'),v=document.getElementById('vf'),rows=[...document.querySelectorAll('#pt tbody tr[data-s]')],n=document.getElementById('count'),e=document.getElementById('none');
if(q){function f(){const s=q.value.trim().toLowerCase(),vv=v.value;let c=0;rows.forEach(r=>{const ok=r.dataset.s.includes(s)&&(!vv||r.dataset.v===vv);r.hidden=!ok;if(ok)c++});n.textContent=c;e.hidden=c>0}q.oninput=v.onchange=f}})();

/* "Running the models…" overlay: shown when a form with a [data-busy] button is submitted; hidden again if the user comes back. */
(function(){document.querySelectorAll('[data-busy]').forEach(function(b){var o=document.getElementById(b.dataset.busy);if(b.form&&o)b.form.addEventListener('submit',function(){o.classList.add('on')})});
window.addEventListener('pageshow',function(){document.querySelectorAll('.busy.on').forEach(function(x){x.classList.remove('on')})})})();
