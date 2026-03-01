<style>
/* ═══════════════════════════════════════════════════════════
   Terra Aventura — Formulaire quête (create / edit)
═══════════════════════════════════════════════════════════ */

/* ── Design tokens ─────────────────────────────────────── */
:root {
    --f-indigo:  #4f46e5;
    --f-indigo2: #6366f1;
    --f-green:   #16a34a;
    --f-red:     #dc2626;
    --f-border:  #e2e8f0;
    --f-surface: #f8fafc;
    --f-card:    #ffffff;
    --f-muted:   #94a3b8;
    --f-radius:  1.125rem;
}

/* ── Sections ───────────────────────────────────────────── */
.form-section {
    background: var(--f-card);
    border: 1.5px solid var(--f-border);
    border-radius: var(--f-radius);
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
    overflow: hidden;
}
.form-section-header {
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border-bottom: 1.5px solid var(--f-border);
    display: flex; align-items: center; gap: .625rem;
}
.form-section-body { padding: 1.25rem; }

/* ── Inputs ─────────────────────────────────────────────── */
.field-label {
    display: block;
    font-size: .72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .06em;
    color: #64748b; margin-bottom: .4rem;
}
.field-input {
    width: 100%; padding: .625rem .875rem;
    border: 1.5px solid var(--f-border);
    border-radius: .75rem; font-size: .9rem;
    background: #fff; color: #1e293b;
    transition: border-color .15s, box-shadow .15s;
    outline: none;
}
.field-input:focus {
    border-color: var(--f-indigo);
    box-shadow: 0 0 0 3px rgba(79,70,229,.1);
}
.field-hint { font-size: .73rem; color: var(--f-muted); margin-top: .3rem; }

/* ── Objet card ─────────────────────────────────────────── */
.objet-card {
    background: var(--f-card);
    border: 1.5px solid var(--f-border);
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
    transition: box-shadow .2s;
}
.objet-card:hover { box-shadow: 0 3px 12px rgba(0,0,0,.08); }

.objet-card-header {
    display: flex; align-items: center; gap: .625rem;
    padding: .75rem 1rem;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border-bottom: 1.5px solid var(--f-border);
    flex-wrap: wrap;
}
.objet-number {
    width: 1.5rem; height: 1.5rem; flex-shrink: 0;
    border-radius: 50%; background: var(--f-indigo);
    color: #fff; font-size: .72rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
}
.objet-name-input {
    flex: 1; min-width: 120px;
    padding: .4rem .75rem;
    border: 1.5px solid var(--f-border);
    border-radius: .6rem; font-size: .875rem;
    background: #fff; color: #1e293b;
    outline: none; transition: border-color .15s, box-shadow .15s;
}
.objet-name-input:focus {
    border-color: var(--f-indigo);
    box-shadow: 0 0 0 3px rgba(79,70,229,.1);
}
.badge-parcours {
    display: inline-flex; align-items: center;
    padding: .2rem .6rem; border-radius: 99px;
    font-size: .7rem; font-weight: 600;
    background: #f1f5f9; color: #64748b;
    transition: background .2s, color .2s;
    white-space: nowrap;
}
.badge-parcours.badge-has { background: #e0e7ff; color: #3730a3; }

.objet-delete-btn {
    flex-shrink: 0; padding: .3rem .5rem;
    border-radius: .5rem; border: none;
    background: transparent; cursor: pointer;
    font-size: .9rem; color: #94a3b8;
    transition: color .15s, background .15s;
}
.objet-delete-btn:hover { color: var(--f-red); background: #fee2e2; }

/* ── Parcours list ──────────────────────────────────────── */
.parcours-list { padding: .625rem .875rem; display: flex; flex-direction: column; gap: .375rem; }
.parcours-list:empty { padding: 0; }

.parcours-item-row {
    display: flex; align-items: center; gap: .625rem;
    padding: .5rem .75rem;
    border: 1.5px solid #e2e8f0;
    border-radius: .625rem; background: var(--f-surface);
    transition: border-color .15s;
}
.parcours-item-row:hover { border-color: #c7d2fe; }

.parcours-item-logo {
    width: 2rem; height: 2rem; object-fit: contain;
    border-radius: .375rem; flex-shrink: 0;
}
.parcours-item-logo-ph {
    width: 2rem; height: 2rem; flex-shrink: 0;
    border-radius: .375rem; background: #f1f5f9;
    display: flex; align-items: center; justify-content: center;
    font-size: .875rem;
}
.parcours-remove-btn {
    flex-shrink: 0; width: 1.5rem; height: 1.5rem;
    border-radius: 50%; border: none;
    background: #f1f5f9; color: #94a3b8;
    font-size: .65rem; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s, color .15s;
}
.parcours-remove-btn:hover { background: #fee2e2; color: var(--f-red); }

/* ── Add parcours button ────────────────────────────────── */
.add-parcours-btn {
    display: flex; align-items: center; gap: .375rem;
    padding: .5rem .875rem;
    margin: 0 .875rem .875rem;
    border-radius: .625rem;
    border: 1.5px dashed #c7d2fe;
    background: #eef2ff;
    color: var(--f-indigo); font-size: .8rem; font-weight: 600;
    cursor: pointer; transition: all .15s;
    width: calc(100% - 1.75rem);
}
.add-parcours-btn:hover { background: #e0e7ff; border-color: var(--f-indigo); }

/* ── Buttons ────────────────────────────────────────────── */
.btn-primary {
    display: inline-flex; align-items: center; gap: .5rem;
    padding: .65rem 1.4rem;
    border-radius: .875rem; border: none;
    background: var(--f-green); color: #fff;
    font-size: .875rem; font-weight: 700;
    cursor: pointer; box-shadow: 0 2px 8px rgba(22,163,74,.25);
    transition: all .15s; text-decoration: none;
}
.btn-primary:hover { background: #15803d; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(22,163,74,.3); }
.btn-primary:disabled { opacity: .4; cursor: not-allowed; transform: none; box-shadow: none; }

.btn-primary-blue {
    background: var(--f-indigo);
    box-shadow: 0 2px 8px rgba(79,70,229,.25);
}
.btn-primary-blue:hover { background: #4338ca; box-shadow: 0 4px 12px rgba(79,70,229,.3); }

.btn-secondary {
    display: inline-flex; align-items: center; gap: .5rem;
    padding: .6rem 1.2rem;
    border-radius: .875rem;
    border: 1.5px solid var(--f-border);
    background: #f8fafc; color: #475569;
    font-size: .875rem; font-weight: 600;
    cursor: pointer; transition: all .15s; text-decoration: none;
}
.btn-secondary:hover { background: #f1f5f9; border-color: #cbd5e1; }

.btn-add-objet {
    display: inline-flex; align-items: center; gap: .5rem;
    padding: .55rem 1.1rem;
    border-radius: .875rem;
    border: 1.5px solid #c7d2fe; background: #eef2ff;
    color: var(--f-indigo); font-size: .8rem; font-weight: 700;
    cursor: pointer; transition: all .15s;
}
.btn-add-objet:hover { background: #e0e7ff; border-color: var(--f-indigo); }

/* ── Modals ─────────────────────────────────────────────── */
.modal-box {
    background: #fff; border-radius: 1.25rem;
    box-shadow: 0 25px 60px rgba(0,0,0,.2);
    width: 100%; margin: 1rem;
    animation: modalIn .18s ease;
    display: flex; flex-direction: column;
    max-height: 90vh; overflow: hidden;
}
@keyframes modalIn {
    from { opacity:0; transform:scale(.95) translateY(8px); }
    to   { opacity:1; transform:scale(1)   translateY(0); }
}
.modal-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1.5px solid var(--f-border);
    display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;
    flex-shrink: 0;
}
.modal-close-btn {
    width: 1.75rem; height: 1.75rem; flex-shrink: 0;
    border-radius: 50%; border: none; background: #f1f5f9;
    color: #64748b; font-size: .8rem; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s, color .15s;
}
.modal-close-btn:hover { background: #fee2e2; color: var(--f-red); }
.modal-body { padding: 1.25rem 1.5rem; flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: .875rem; }
.modal-footer { padding: .875rem 1.5rem; border-top: 1.5px solid var(--f-border); display: flex; justify-content: flex-end; gap: .625rem; flex-shrink: 0; }

/* Modal search */
.modal-search-input {
    width: 100%; padding: .6rem .875rem .6rem 2.25rem;
    border: 1.5px solid var(--f-border);
    border-radius: .75rem; font-size: .875rem;
    outline: none; transition: border-color .15s, box-shadow .15s;
}
.modal-search-input:focus { border-color: var(--f-indigo); box-shadow: 0 0 0 3px rgba(79,70,229,.1); }

.parcours-results-container {
    border: 1.5px solid var(--f-border);
    border-radius: .75rem; overflow-y: auto;
    max-height: 260px; min-height: 64px;
    background: var(--f-surface);
}
.parcours-result-item {
    display: flex; align-items: center; gap: .75rem;
    padding: .75rem 1rem;
    border-bottom: 1px solid var(--f-border);
    transition: background .12s;
}
.parcours-result-item:last-child { border-bottom: none; }
.parcours-result-item:hover:not(.already) { background: #f0f9ff; }
.parcours-result-item.already { opacity: .55; }
.parcours-result-logo { width: 2.25rem; height: 2.25rem; object-fit: contain; border-radius: .375rem; flex-shrink: 0; }
.parcours-result-logo-ph { width: 2.25rem; height: 2.25rem; border-radius: .375rem; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: .9rem; flex-shrink: 0; }
.parcours-add-btn {
    flex-shrink: 0; padding: .3rem .75rem; border-radius: .5rem; border: none;
    background: #dcfce7; color: #15803d; font-size: .75rem; font-weight: 700;
    cursor: pointer; transition: background .12s;
}
.parcours-add-btn:hover { background: #bbf7d0; }
.parcours-empty { padding: 1.25rem; text-align: center; font-size: .85rem; color: var(--f-muted); }

/* Modal confirm / delete */
.summary-item {
    display: flex; align-items: flex-start; gap: .75rem;
    padding: .75rem; border-radius: .75rem;
    border: 1.5px solid #e2e8f0; background: var(--f-surface);
}
.summary-item.new     { border-color: #bbf7d0; background: #f0fdf4; }
.summary-item.deleted { border-color: #fecaca; background: #fff5f5; }
.summary-icon { font-size: 1.1rem; flex-shrink: 0; }

/* ── Animations page ────────────────────────────────────── */
@keyframes fadeUp {
    from { opacity:0; transform:translateY(10px); }
    to   { opacity:1; transform:translateY(0); }
}
.form-section { animation: fadeUp .3s ease both; }
.form-section:nth-child(2) { animation-delay: 80ms; }
</style>
