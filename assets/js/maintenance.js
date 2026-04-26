(function () {
    'use strict';
    function cfg() { return window.APP_MAINTENANCE || {}; }
    function $(s, r) { return (r || document).querySelector(s); }
    function overlay() { return document.getElementById('maintenance-modal-overlay'); }
    function lock() { if (typeof window.lockModalBodyScroll === 'function') window.lockModalBodyScroll(); }
    function unlock() { if (typeof window.unlockModalBodyScroll === 'function') window.unlockModalBodyScroll(); }
    function openModal() { var el = overlay(); if (!el) return; el.removeAttribute('hidden'); el.setAttribute('aria-hidden', 'false'); requestAnimationFrame(function(){ el.classList.add('is-visible'); }); lock(); var f = el.querySelector('input:not([type="hidden"])'); if (f) f.focus(); }
    function closeModal() { var el = overlay(); if (!el) return; var ae = document.activeElement; if (ae && el.contains(ae) && ae.blur) ae.blur(); setTimeout(function(){ el.classList.remove('is-visible'); el.setAttribute('hidden','hidden'); el.setAttribute('aria-hidden','true'); unlock(); },0); }
    function clearErrors(form){ form.querySelectorAll('[data-error-for]').forEach(function(p){p.hidden=true;p.textContent='';}); var w=form.querySelector('.js-maintenance-msg'); if(w) w.hidden=true; var g=form.querySelector('[data-maintenance-form-error]'); if(g) g.textContent=''; }
    function showErrors(form,e){ clearErrors(form); Object.keys(e||{}).forEach(function(k){ if(k==='_general'){ var w=form.querySelector('.js-maintenance-msg'), g=form.querySelector('[data-maintenance-form-error]'); if(w&&g){w.hidden=false; g.textContent=e[k];} return; } var p=form.querySelector('[data-error-for="'+k+'"]'); if(p){ p.hidden=false; p.textContent=e[k]; }}); }
    function module(){ return String(cfg().module || '').trim(); }
    function apiUrl(){ return (cfg().apiUrl || '') + '?module=' + encodeURIComponent(module()); }
    function rowIdFromData(row){
        var mod = module();
        if(mod==='maintenance_scales') return row && row.scale_id !== undefined ? row.scale_id : '';
        if(mod==='maintenance_subscales') return row && row.subscale_id !== undefined ? row.subscale_id : '';
        if(mod==='maintenance_classes') return row && row.class_id !== undefined ? row.class_id : '';
        if(mod==='maintenance_categories') return row && row.category_id !== undefined ? row.category_id : '';
        if(mod==='maintenance_administrative_statuses') return row && row.administrative_status_id !== undefined ? row.administrative_status_id : '';
        if(mod==='maintenance_position_classes') return row && row.position_class_id !== undefined ? row.position_class_id : '';
        if(mod==='maintenance_legal_relationships') return row && row.legal_relation_id !== undefined ? row.legal_relation_id : '';
        if(mod==='maintenance_access_types') return row && row.access_type_id !== undefined ? row.access_type_id : '';
        if(mod==='maintenance_access_systems') return row && row.access_system_id !== undefined ? row.access_system_id : '';
        if(mod==='maintenance_work_centers') return row && row.work_center_id !== undefined ? row.work_center_id : '';
        if(mod==='maintenance_availability_types') return row && row.availability_id !== undefined ? row.availability_id : '';
        if(mod==='maintenance_provision_forms') return row && row.provision_method_id !== undefined ? row.provision_method_id : '';
        if(mod==='maintenance_organic_level_1') return row && row.org_unit_level_1_id !== undefined ? row.org_unit_level_1_id : '';
        if(mod==='maintenance_organic_level_2') return row && row.org_unit_level_2_id !== undefined ? row.org_unit_level_2_id : '';
        if(mod==='maintenance_organic_level_3') return row && row.org_unit_level_3_id !== undefined ? row.org_unit_level_3_id : '';
        if(mod==='maintenance_programs') return row && row.program_id !== undefined ? row.program_id : '';
        if(mod==='maintenance_social_security_companies') return row && row.company_id !== undefined ? row.company_id : '';
        if(mod==='maintenance_social_security_coefficients') return row && row.contribution_epigraph_id !== undefined ? row.contribution_epigraph_id : '';
        if(mod==='maintenance_social_security_base_limits') return row && row.contribution_group_id !== undefined ? row.contribution_group_id : '';
        if(mod==='maintenance_subprograms') return row && row.subprogram_id !== undefined ? row.subprogram_id : '';
        return row && (row.scale_id || row.subscale_id || row.category_id || '');
    }
    function rowNameFromData(row){
        var mod = module();
        if(mod==='maintenance_scales') return row && row.scale_name !== undefined ? row.scale_name : '';
        if(mod==='maintenance_subscales') return row && row.subscale_name !== undefined ? row.subscale_name : '';
        if(mod==='maintenance_classes') return row && row.class_name !== undefined ? row.class_name : '';
        if(mod==='maintenance_categories') return row && row.category_name !== undefined ? row.category_name : '';
        if(mod==='maintenance_administrative_statuses') return row && row.administrative_status_name !== undefined ? row.administrative_status_name : '';
        if(mod==='maintenance_position_classes') return row && row.position_class_name !== undefined ? row.position_class_name : '';
        if(mod==='maintenance_legal_relationships') return row && row.legal_relation_name !== undefined ? row.legal_relation_name : '';
        if(mod==='maintenance_access_types') return row && row.access_type_name !== undefined ? row.access_type_name : '';
        if(mod==='maintenance_access_systems') return row && row.access_system_name !== undefined ? row.access_system_name : '';
        if(mod==='maintenance_work_centers') return row && row.work_center_name !== undefined ? row.work_center_name : '';
        if(mod==='maintenance_availability_types') return row && row.availability_name !== undefined ? row.availability_name : '';
        if(mod==='maintenance_provision_forms') return row && row.provision_method_name !== undefined ? row.provision_method_name : '';
        if(mod==='maintenance_organic_level_1') return row && row.org_unit_level_1_name !== undefined ? row.org_unit_level_1_name : '';
        if(mod==='maintenance_organic_level_2') return row && row.org_unit_level_2_name !== undefined ? row.org_unit_level_2_name : '';
        if(mod==='maintenance_organic_level_3') return row && row.org_unit_level_3_name !== undefined ? row.org_unit_level_3_name : '';
        if(mod==='maintenance_programs') return row && row.program_name !== undefined ? row.program_name : '';
        if(mod==='maintenance_social_security_companies') return row && row.company_description !== undefined ? row.company_description : '';
        if(mod==='maintenance_social_security_base_limits') return row && row.contribution_group_description !== undefined ? row.contribution_group_description : '';
        if(mod==='maintenance_social_security_coefficients') return '';
        if(mod==='maintenance_subprograms') return row && row.subprogram_name !== undefined ? row.subprogram_name : '';
        return row && (row.scale_name || row.subscale_name || row.category_name || '');
    }
    function rowShortNameFromData(row){
        var mod = module();
        if(mod==='maintenance_scales') return row && row.scale_short_name !== undefined ? row.scale_short_name : '';
        if(mod==='maintenance_subscales') return row && row.subscale_short_name !== undefined ? row.subscale_short_name : '';
        if(mod==='maintenance_classes') return row && row.class_short_name !== undefined ? row.class_short_name : '';
        if(mod==='maintenance_categories') return row && row.category_short_name !== undefined ? row.category_short_name : '';
        if(mod==='maintenance_administrative_statuses') return '';
        if(mod==='maintenance_position_classes') return '';
        if(mod==='maintenance_legal_relationships') return '';
        if(mod==='maintenance_access_types') return '';
        if(mod==='maintenance_access_systems') return '';
        if(mod==='maintenance_work_centers') return '';
        if(mod==='maintenance_availability_types') return '';
        if(mod==='maintenance_provision_forms') return '';
        if(mod==='maintenance_organic_level_1') return '';
        if(mod==='maintenance_organic_level_2') return '';
        if(mod==='maintenance_organic_level_3') return '';
        if(mod==='maintenance_programs') return '';
        if(mod==='maintenance_subprograms') return '';
        return row && (row.scale_short_name || row.subscale_short_name || row.category_short_name || '');
    }
    function formatJobPosSelectLabel(it) {
        var id = String(it.id || '').trim();
        var name = String(it.name || '');
        if (!/^\d+$/.test(id)) return id + ' - ' + name;
        var p = id.length > 6 ? id.slice(-6) : ('000000' + id).slice(-6);
        var disp = p.slice(0, 4) + '.' + p.slice(4, 6);
        return disp + ' - ' + name;
    }
    function formatProgramSelectLabel(it) {
        return String(it.id || '').trim() + ' - ' + String(it.name || '');
    }
    function ensureSelectOption(sel, val, label) {
        if (!sel || val === '' || val === null || val === undefined) return;
        var vs = String(val).trim();
        if (vs === '') return;
        var ok = false;
        Array.prototype.forEach.call(sel.options, function (o) { if (o.value === vs) ok = true; });
        if (!ok) {
            var o = document.createElement('option');
            o.value = vs;
            o.textContent = label && String(label).trim() !== '' ? String(label) : vs;
            sel.appendChild(o);
        }
    }
    function updateSubprogramComputedCode() {
        if (module() !== 'maintenance_subprograms') return;
        var progEl = document.querySelector('[data-field="subprogram_parent_program"]');
        var numEl = document.querySelector('[data-field="subprogram_number"]');
        var o = document.querySelector('[data-field="subprogram_computed_code"]');
        if (!o) return;
        var pid = progEl ? String(progEl.value || '').trim() : '';
        var num = numEl ? String(numEl.value || '').trim() : '';
        if (pid !== '' && /^\d{1,2}$/.test(num)) {
            var pad = num.length < 2 ? ('0' + num).slice(-2) : num;
            o.value = pid + pad;
        } else {
            o.value = '';
        }
    }
    function updateProgramComputedCode() {
        if (module() !== 'maintenance_programs') return;
        var sfEl = document.querySelector('[data-field="subfunction_id"]');
        var prEl = document.querySelector('[data-field="program_number"]');
        var o = document.querySelector('[data-field="program_computed_code"]');
        if (!o) return;
        var sf = sfEl ? String(sfEl.value || '').trim() : '';
        var pr = prEl ? String(prEl.value || '').trim() : '';
        if (/^\d{1,3}$/.test(sf) && /^\d$/.test(pr)) {
            var pad = sf.length < 3 ? ('000' + sf).slice(-3) : sf;
            o.value = pad + pr;
        } else {
            o.value = '';
        }
    }
    function formatCompanyCccInput(value) {
        var digits = String(value || '').replace(/\D+/g, '').slice(0, 11);
        if (!digits) return '';
        if (digits.length <= 2) return digits;
        if (digits.length <= 9) return digits.slice(0, 2) + ' ' + digits.slice(2);
        return digits.slice(0, 2) + ' ' + digits.slice(2, 9) + ' ' + digits.slice(9, 11);
    }
    function normalizeDecimalInput(value) {
        var s = String(value || '').trim();
        if (s === '') return '';
        return s.replace(',', '.');
    }
    /** Coeficients SS: entrada com a percentatge visible (mateix criteri que PHP: treu % i espais, coma→punt). */
    function normalizeSsCoeffVisiblePercentInput(value) {
        var s = String(value || '').trim();
        if (s === '') return '';
        s = s.replace(/%/g, '').replace(/\s+/g, '');
        return s.replace(',', '.');
    }
    /** Converteix decimal BBDD a percentatge visible amb 4 decimals i coma (sense símbol % al camp). */
    function formatSsCoeffPercentFromDbForInput(v) {
        if (v === null || v === undefined) return '';
        var s = String(v).trim();
        if (s === '') return '';
        var n = parseFloat(s.replace(',', '.'));
        if (!isFinite(n)) return '';
        var pct = Math.round(n * 100 * 10000) / 10000;
        return pct.toFixed(4).replace('.', ',');
    }
    function formatMoneyForInput(value) {
        if (value === null || value === undefined) return '';
        var s = String(value).trim();
        if (s === '') return '';
        var n = parseFloat(s.replace(',', '.'));
        if (!isFinite(n)) return '';
        var fixed = n.toFixed(2);
        var parts = fixed.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return parts[0] + ',' + parts[1];
    }
    function normalizeMoneyInput(value) {
        var t = String(value || '').trim();
        if (t === '') return '';
        t = t.replace(/\u00A0/g, '').replace(/€/g, '').replace(/\s+/g, '');
        if (t === '') return '';
        if (/[^0-9.,]/.test(t)) return null;
        var lastDot = t.lastIndexOf('.');
        var lastComma = t.lastIndexOf(',');
        var decSep = '';
        if (lastDot !== -1 && lastComma !== -1) decSep = lastDot > lastComma ? '.' : ',';
        else if (lastComma !== -1) decSep = ',';
        else if (lastDot !== -1) decSep = ((t.length - lastDot - 1) >= 1 && (t.length - lastDot - 1) <= 2) ? '.' : '';
        var norm = t;
        if (decSep === ',') {
            norm = norm.replace(/\./g, '').replace(',', '.');
        } else if (decSep === '.') {
            norm = norm.replace(/,/g, '');
        } else {
            norm = norm.replace(/[.,]/g, '');
        }
        if (!/^\d+(?:\.\d{1,2})?$/.test(norm)) return null;
        return norm;
    }
    function setupFields() {
        var mod = module();
        var show = function (name, on) { var el = document.querySelector('[data-maintenance-field="'+name+'"]'); if (el) el.hidden = !on; };
        var idInput = $('#maintenance_id');
        var idLabel = $('[data-maintenance-label-id]');
        var nameLabel = $('[data-maintenance-label-name]');
        if (idLabel) {
            idLabel.innerHTML = (mod === 'maintenance_social_security_coefficients' ? 'Epígraf' : (mod === 'maintenance_social_security_base_limits' ? 'Grup. Cot.' : 'Codi')) + ' <span class="users-modal-form__req">*</span>';
        }
        if (nameLabel) {
            var lbl = 'Nom';
            if (mod === 'maintenance_programs') lbl = 'Nom programa';
            else if (mod === 'maintenance_subprograms') lbl = 'Nom subprograma';
            else if (mod === 'maintenance_work_centers' || mod === 'maintenance_administrative_statuses' || mod === 'maintenance_position_classes' || mod === 'maintenance_legal_relationships' || mod === 'maintenance_access_types' || mod === 'maintenance_access_systems' || mod === 'maintenance_availability_types' || mod === 'maintenance_provision_forms' || mod === 'maintenance_organic_level_1' || mod === 'maintenance_organic_level_2' || mod === 'maintenance_organic_level_3' || mod === 'maintenance_social_security_companies' || mod === 'maintenance_social_security_base_limits') lbl = 'Denominació';
            nameLabel.innerHTML = lbl + ' <span class="users-modal-form__req">*</span>';
        }
        if (idInput) {
            if (mod === 'maintenance_programs' || mod === 'maintenance_subprograms') {
                idInput.type = 'hidden';
            } else if (mod === 'maintenance_availability_types' || mod === 'maintenance_provision_forms' || mod === 'maintenance_social_security_companies' || mod === 'maintenance_social_security_coefficients' || mod === 'maintenance_social_security_base_limits') {
                idInput.type = 'text';
                idInput.removeAttribute('min');
                if (mod === 'maintenance_social_security_coefficients') {
                    idInput.setAttribute('maxlength', '3');
                    idInput.setAttribute('inputmode', 'numeric');
                    idInput.setAttribute('pattern', '[0-9]{1,3}');
                } else if (mod === 'maintenance_social_security_base_limits') {
                    idInput.setAttribute('maxlength', '2');
                    idInput.setAttribute('inputmode', 'numeric');
                    idInput.setAttribute('pattern', '[0-9]{1,2}');
                }
            } else {
                idInput.type = 'number';
                idInput.setAttribute('min', '1');
            }
        }
        show('id', mod !== 'maintenance_programs' && mod !== 'maintenance_subprograms');
        show('subfunction_id', mod === 'maintenance_programs');
        show('program_number', mod === 'maintenance_programs');
        show('program_computed_code', mod === 'maintenance_programs');
        show('description', mod === 'maintenance_programs');
        show('responsible_person_code', mod === 'maintenance_programs');
        show('subprogram_parent_program', mod === 'maintenance_subprograms');
        show('subprogram_number', mod === 'maintenance_subprograms');
        show('subprogram_computed_code', mod === 'maintenance_subprograms');
        show('technical_manager_code', mod === 'maintenance_subprograms');
        show('elected_manager_code', mod === 'maintenance_subprograms');
        show('nature', mod === 'maintenance_subprograms');
        show('is_mandatory_service', mod === 'maintenance_subprograms');
        show('has_corporate_agreements', mod === 'maintenance_subprograms');
        show('objectives', mod === 'maintenance_subprograms');
        show('activities', mod === 'maintenance_subprograms');
        show('notes', mod === 'maintenance_subprograms');
        show('short_name', mod === 'maintenance_scales' || mod === 'maintenance_subscales' || mod === 'maintenance_classes' || mod === 'maintenance_categories');
        show('full_name', mod === 'maintenance_scales');
        show('scale_id', mod === 'maintenance_subscales' || mod === 'maintenance_categories' || mod === 'maintenance_classes');
        show('subscale_id', mod === 'maintenance_categories' || mod === 'maintenance_classes');
        show('class_id', mod === 'maintenance_categories');
        show('org_unit_level_1_id', mod === 'maintenance_organic_level_2');
        show('org_unit_level_2_id', mod === 'maintenance_organic_level_3');
        show('address', mod === 'maintenance_work_centers');
        show('postal_code', mod === 'maintenance_work_centers');
        show('city', mod === 'maintenance_work_centers');
        show('phone', mod === 'maintenance_work_centers');
        show('fax', mod === 'maintenance_work_centers');
        show('sort_order', mod === 'maintenance_availability_types' || mod === 'maintenance_provision_forms');
        show('contribution_account_code', mod === 'maintenance_social_security_companies');
        show('company_1', mod === 'maintenance_social_security_coefficients');
        show('company_2', mod === 'maintenance_social_security_coefficients');
        show('company_3', mod === 'maintenance_social_security_coefficients');
        show('company_4', mod === 'maintenance_social_security_coefficients');
        show('company_5a', mod === 'maintenance_social_security_coefficients');
        show('company_5b', mod === 'maintenance_social_security_coefficients');
        show('company_5c', mod === 'maintenance_social_security_coefficients');
        show('company_5d', mod === 'maintenance_social_security_coefficients');
        show('company_5e', mod === 'maintenance_social_security_coefficients');
        show('temporary_employment_company', mod === 'maintenance_social_security_coefficients');
        show('minimum_base', mod === 'maintenance_social_security_base_limits');
        show('maximum_base', mod === 'maintenance_social_security_base_limits');
        show('period_label', mod === 'maintenance_social_security_base_limits');
        show('name', mod !== 'maintenance_social_security_coefficients');
    }
    function fillSelect(sel, items, valKey, txtFn){
        if(!sel) return;
        while(sel.options.length>1){ sel.remove(1); }
        (items||[]).forEach(function(it){
            var o=document.createElement('option');
            o.value=String(it[valKey]);
            o.textContent=txtFn(it);
            if (it.scale_id !== undefined) o.setAttribute('data-scale-id', String(it.scale_id));
            if (it.subscale_id !== undefined) o.setAttribute('data-subscale-id', String(it.subscale_id));
            sel.appendChild(o);
        });
    }
    function applyCascades(){
        var mod = module();
        var scale = $('#maintenance_scale_id'), sub = $('#maintenance_subscale_id'), cls = $('#maintenance_class_id');
        if(!scale||!sub) return;
        var sid = scale.value;
        if(mod === 'maintenance_classes') {
            Array.prototype.forEach.call(sub.options,function(o){ if(!o.value) return; o.hidden = sid!=='' && o.getAttribute('data-scale-id')!==sid; });
            if(sub.selectedOptions[0] && sub.selectedOptions[0].hidden) sub.value='';
            return;
        }
        if(mod !== 'maintenance_categories') return;
        if(!cls) return;
        var ssid=sub.value;
        Array.prototype.forEach.call(sub.options,function(o){ if(!o.value) return; o.hidden = sid!=='' && o.getAttribute('data-scale-id')!==sid; });
        if(sub.selectedOptions[0] && sub.selectedOptions[0].hidden) sub.value='';
        ssid=sub.value;
        Array.prototype.forEach.call(cls.options,function(o){ if(!o.value) return; var okScale = sid==='' || o.getAttribute('data-scale-id')===sid; var okSub = ssid==='' || o.getAttribute('data-subscale-id')===ssid; o.hidden = !(okScale && okSub); });
        if(cls.selectedOptions[0] && cls.selectedOptions[0].hidden) cls.value='';
    }
    function reset(form){
        form.reset();
        $('[data-field="original_id"]',form).value='';
        clearErrors(form);
        fillSelect($('#maintenance_scale_id'), cfg().scales || [], 'id', function(it){ return String(it.id)+' - '+String(it.name); });
        fillSelect($('#maintenance_subscale_id'), cfg().subscales || [], 'id', function(it){ return String(it.id)+' - '+String(it.name); });
        if (module() !== 'maintenance_classes') {
            fillSelect($('#maintenance_class_id'), cfg().classes || [], 'id', function(it){ return String(it.id)+' - '+String(it.name); });
        }
        fillSelect($('#maintenance_org_unit_level_1_id'), cfg().organicLevel1 || [], 'id', function(it){ return String(it.id)+' - '+String(it.name); });
        fillSelect($('#maintenance_org_unit_level_2_id'), cfg().organicLevel2 || [], 'id', function(it){
            var sid = String(it.id);
            var n = parseInt(sid, 10);
            var code = isNaN(n) ? sid : String(n).padStart(2, '0');
            return code + ' - ' + String(it.name);
        });
        if (module() === 'maintenance_programs') {
            fillSelect($('#maintenance_responsible_person_code'), cfg().jobPositions || [], 'id', formatJobPosSelectLabel);
            var sfc = $('[data-field="program_computed_code"]', form);
            if (sfc) sfc.value = '';
        }
        if (module() === 'maintenance_subprograms') {
            fillSelect($('#maintenance_subprogram_program_id'), cfg().programsForSelect || [], 'id', formatProgramSelectLabel);
            fillSelect($('#maintenance_technical_manager_code'), cfg().jobPositionsCm || [], 'id', formatJobPosSelectLabel);
            fillSelect($('#maintenance_elected_manager_code'), cfg().jobPositionsCm || [], 'id', formatJobPosSelectLabel);
            var scc = $('[data-field="subprogram_computed_code"]', form);
            if (scc) scc.value = '';
            var m1 = $('#maintenance_is_mandatory_service', form);
            var m2 = $('#maintenance_has_corporate_agreements', form);
            if (m1) m1.checked = false;
            if (m2) m2.checked = false;
        }
        applyCascades();
    }
    function setMode(create){
        var h=$('[data-maintenance-modal-heading]'), s=$('[data-maintenance-modal-subheading]');
        if(h) h.textContent=create?'Nou registre':'Actualització';
        if(s) s.textContent=create?'Introdueix les dades del nou registre':'Modifica la informació del registre';
    }
    function openCreate(){ if(!cfg().canCreate || !cfg().implemented) return; var f=$('#maintenance-modal-form'); if(!f) return; reset(f); setMode(true); if(module()==='maintenance_programs') updateProgramComputedCode(); if(module()==='maintenance_subprograms') updateSubprogramComputedCode(); openModal(); }
    function openEdit(id){
        if(!cfg().canEdit || !cfg().implemented) return;
        var f=$('#maintenance-modal-form'); if(!f) return; reset(f); setMode(false);
        fetch(apiUrl()+'&action=get&id='+encodeURIComponent(String(id)),{credentials:'same-origin'}).then(function(r){return r.json();}).then(function(d){
            if(!d.ok||!d.row){ if(window.showAlert) window.showAlert('error','Error','No s’ha pogut carregar el registre.'); return; }
            var r=d.row;
            if (module() === 'maintenance_programs') {
                $('[data-field="original_id"]',f).value=String(r.program_id||'');
                var sfi = $('[data-field="subfunction_id"]',f);
                if (sfi) {
                    var subDisp = String(r.subfunction_id != null ? r.subfunction_id : '').trim();
                    if (/^\d{1,3}$/.test(subDisp)) {
                        sfi.value = subDisp.length < 3 ? ('000' + subDisp).slice(-3) : subDisp;
                    } else {
                        sfi.value = subDisp;
                    }
                }
                var pni = $('[data-field="program_number"]',f); if(pni) pni.value=(r.program_number===undefined||r.program_number===null)?'':String(r.program_number);
                var nm = $('[data-field="name"]',f); if(nm) nm.value=String(r.program_name||'');
                var desc = $('[data-field="description"]',f); if(desc) desc.value=String(r.description||'');
                var rsp = $('[data-field="responsible_person_code"]',f); if(rsp) rsp.value=String(r.responsible_person_code!=null?r.responsible_person_code:'').trim();
                updateProgramComputedCode();
                applyCascades();
                openModal();
                return;
            }
            if (module() === 'maintenance_subprograms') {
                $('[data-field="original_id"]', f).value = String(r.subprogram_id || '');
                fillSelect($('#maintenance_subprogram_program_id'), cfg().programsForSelect || [], 'id', formatProgramSelectLabel);
                fillSelect($('#maintenance_technical_manager_code'), cfg().jobPositionsCm || [], 'id', formatJobPosSelectLabel);
                fillSelect($('#maintenance_elected_manager_code'), cfg().jobPositionsCm || [], 'id', formatJobPosSelectLabel);
                var progSel = $('[data-field="subprogram_parent_program"]', f);
                if (progSel) progSel.value = String(r.program_id != null ? r.program_id : '').trim();
                var sni = $('[data-field="subprogram_number"]', f);
                if (sni) {
                    var sn = String(r.subprogram_number != null ? r.subprogram_number : '').trim();
                    if (/^\d{1,2}$/.test(sn)) {
                        sni.value = sn.length < 2 ? ('0' + sn).slice(-2) : sn;
                    } else {
                        sni.value = sn;
                    }
                }
                var nm = $('[data-field="name"]', f); if (nm) nm.value = String(r.subprogram_name || '');
                var nat = $('[data-field="nature"]', f); if (nat) nat.value = String(r.nature || '');
                var tech = $('#maintenance_technical_manager_code', f);
                var elect = $('#maintenance_elected_manager_code', f);
                var techId = String(r.technical_manager_code != null ? r.technical_manager_code : '').trim();
                var electId = String(r.elected_manager_code != null ? r.elected_manager_code : '').trim();
                ensureSelectOption(tech, techId, formatJobPosSelectLabel({ id: techId, name: r.technical_job_title || '' }));
                ensureSelectOption(elect, electId, formatJobPosSelectLabel({ id: electId, name: r.elected_job_title || '' }));
                if (tech) tech.value = techId;
                if (elect) elect.value = electId;
                var m1 = $('#maintenance_is_mandatory_service', f);
                var m2 = $('#maintenance_has_corporate_agreements', f);
                if (m1) m1.checked = Number(r.is_mandatory_service) === 1;
                if (m2) m2.checked = Number(r.has_corporate_agreements) === 1;
                var ob = $('[data-field="objectives"]', f); if (ob) ob.value = String(r.objectives || '');
                var ac = $('[data-field="activities"]', f); if (ac) ac.value = String(r.activities || '');
                var nt = $('[data-field="notes"]', f); if (nt) nt.value = String(r.notes || '');
                updateSubprogramComputedCode();
                applyCascades();
                openModal();
                return;
            }
            if (module() === 'maintenance_social_security_coefficients') {
                $('[data-field="original_id"]', f).value = String(r.contribution_epigraph_id != null ? r.contribution_epigraph_id : '');
                var epi = String(r.contribution_epigraph_id != null ? r.contribution_epigraph_id : '').trim();
                if (/^\d{1,3}$/.test(epi)) {
                    epi = ('000' + epi).slice(-3);
                }
                $('[data-field="id"]', f).value = epi;
                $('[data-field="company_1"]', f).value = formatSsCoeffPercentFromDbForInput(r.company_1);
                $('[data-field="company_2"]', f).value = formatSsCoeffPercentFromDbForInput(r.company_2);
                $('[data-field="company_3"]', f).value = formatSsCoeffPercentFromDbForInput(r.company_3);
                $('[data-field="company_4"]', f).value = formatSsCoeffPercentFromDbForInput(r.company_4);
                $('[data-field="company_5a"]', f).value = formatSsCoeffPercentFromDbForInput(r.company_5a);
                $('[data-field="company_5b"]', f).value = formatSsCoeffPercentFromDbForInput(r.company_5b);
                $('[data-field="company_5c"]', f).value = formatSsCoeffPercentFromDbForInput(r.company_5c);
                $('[data-field="company_5d"]', f).value = formatSsCoeffPercentFromDbForInput(r.company_5d);
                $('[data-field="company_5e"]', f).value = formatSsCoeffPercentFromDbForInput(r.company_5e);
                $('[data-field="temporary_employment_company"]', f).value = formatSsCoeffPercentFromDbForInput(r.temporary_employment_company);
                applyCascades();
                openModal();
                return;
            }
            if (module() === 'maintenance_social_security_base_limits') {
                $('[data-field="original_id"]', f).value = String(r.contribution_group_id != null ? r.contribution_group_id : '');
                var gid = String(r.contribution_group_id != null ? r.contribution_group_id : '').trim();
                if (/^\d{1,2}$/.test(gid)) gid = ('00' + gid).slice(-2);
                $('[data-field="id"]', f).value = gid;
                $('[data-field="name"]', f).value = String(r.contribution_group_description || '');
                $('[data-field="minimum_base"]', f).value = formatMoneyForInput(r.minimum_base);
                $('[data-field="maximum_base"]', f).value = formatMoneyForInput(r.maximum_base);
                $('[data-field="period_label"]', f).value = String(r.period_label || '');
                applyCascades();
                openModal();
                return;
            }
            var idCell = rowIdFromData(r);
            var nameCell = rowNameFromData(r);
            if (Object.prototype.hasOwnProperty.call(r, 'org_unit_level_3_id')) {
                idCell = r.org_unit_level_3_id;
                nameCell = r.org_unit_level_3_name;
            } else if (Object.prototype.hasOwnProperty.call(r, 'legal_relation_id') && Object.prototype.hasOwnProperty.call(r, 'legal_relation_name')) {
                idCell = r.legal_relation_id;
                nameCell = r.legal_relation_name;
            } else if (Object.prototype.hasOwnProperty.call(r, 'position_class_id') && Object.prototype.hasOwnProperty.call(r, 'position_class_name')) {
                idCell = r.position_class_id;
                nameCell = r.position_class_name;
            } else if (Object.prototype.hasOwnProperty.call(r, 'org_unit_level_2_id') && Object.prototype.hasOwnProperty.call(r, 'org_unit_level_2_name')) {
                idCell = r.org_unit_level_2_id;
                nameCell = r.org_unit_level_2_name;
            } else if (Object.prototype.hasOwnProperty.call(r, 'access_system_id') && Object.prototype.hasOwnProperty.call(r, 'access_system_name')) {
                idCell = r.access_system_id;
                nameCell = r.access_system_name;
            } else if (Object.prototype.hasOwnProperty.call(r, 'administrative_status_id') && Object.prototype.hasOwnProperty.call(r, 'administrative_status_name')) {
                idCell = r.administrative_status_id;
                nameCell = r.administrative_status_name;
            } else if (Object.prototype.hasOwnProperty.call(r, 'org_unit_level_1_id') && Object.prototype.hasOwnProperty.call(r, 'org_unit_level_1_name')) {
                idCell = r.org_unit_level_1_id;
                nameCell = r.org_unit_level_1_name;
            }
            $('[data-field="original_id"]',f).value=String(id);
            $('[data-field="id"]',f).value=(idCell === undefined || idCell === null) ? '' : String(idCell);
            $('[data-field="name"]',f).value=(nameCell === undefined || nameCell === null) ? '' : String(nameCell);
            $('[data-field="short_name"]',f).value=String(rowShortNameFromData(r) || '');
            $('[data-field="full_name"]',f).value=String(r.scale_full_name||'');
            $('[data-field="address"]',f).value=String(r.address||'');
            $('[data-field="postal_code"]',f).value=String(r.postal_code||'');
            $('[data-field="city"]',f).value=String(r.city||'');
            $('[data-field="phone"]',f).value=String(r.phone||'');
            $('[data-field="fax"]',f).value=String(r.fax||'');
            $('[data-field="sort_order"]',f).value=String(r.sort_order ?? '');
            $('[data-field="contribution_account_code"]',f).value=String(r.contribution_account_code||'');
            $('[data-field="company_1"]',f).value=String(r.company_1 ?? '');
            $('[data-field="company_2"]',f).value=String(r.company_2 ?? '');
            $('[data-field="company_3"]',f).value=String(r.company_3 ?? '');
            $('[data-field="company_4"]',f).value=String(r.company_4 ?? '');
            $('[data-field="company_5a"]',f).value=String(r.company_5a ?? '');
            $('[data-field="company_5b"]',f).value=String(r.company_5b ?? '');
            $('[data-field="company_5c"]',f).value=String(r.company_5c ?? '');
            $('[data-field="company_5d"]',f).value=String(r.company_5d ?? '');
            $('[data-field="company_5e"]',f).value=String(r.company_5e ?? '');
            $('[data-field="temporary_employment_company"]',f).value=String(r.temporary_employment_company ?? '');
            $('[data-field="minimum_base"]',f).value=String(r.minimum_base ?? '');
            $('[data-field="maximum_base"]',f).value=String(r.maximum_base ?? '');
            $('[data-field="period_label"]',f).value=String(r.period_label ?? '');
            if(r.scale_id!==undefined) $('[data-field="scale_id"]',f).value=String(r.scale_id||'');
            if(r.subscale_id!==undefined) $('[data-field="subscale_id"]',f).value=String(r.subscale_id||'');
            if(r.class_id!==undefined) $('[data-field="class_id"]',f).value=String(r.class_id||'');
            if(r.org_unit_level_1_id!==undefined) { var o1f=$('[data-field="org_unit_level_1_id"]',f); if(o1f) o1f.value=String(r.org_unit_level_1_id); }
            if(r.org_unit_level_2_id!==undefined) { var o2f=$('[data-field="org_unit_level_2_id"]',f); if(o2f) o2f.value=String(r.org_unit_level_2_id); }
            applyCascades();
            openModal();
        }).catch(function(){ if(window.showAlert) window.showAlert('error','Error','Error de xarxa.');});
    }
    function submit(ev){
        ev.preventDefault();
        var f=$('#maintenance-modal-form'); if(!f) return;
        clearErrors(f);
        var csrf=cfg().csrfToken||'';
        var fd=new FormData(f);
        if (module() === 'maintenance_programs') {
            var sff = (fd.get('subfunction_id') || '').toString().trim();
            var pnn = (fd.get('program_number') || '').toString().trim();
            if (!/^\d{1,3}$/.test(sff)) {
                showErrors(f, { subfunction_id: 'La subfunció ha de tenir entre 1 i 3 dígits numèrics.' });
                return;
            }
            if (!/^\d$/.test(pnn)) {
                showErrors(f, { program_number: 'El número ha de ser exactament un dígit (0-9).' });
                return;
            }
        }
        if (module() === 'maintenance_social_security_companies') {
            var ccc = (fd.get('contribution_account_code') || '').toString().trim();
            if (ccc !== '') {
                if (!/^[0-9 ]+$/.test(ccc)) {
                    showErrors(f, { contribution_account_code: 'El CCC només pot contenir dígits i espais.' });
                    return;
                }
                var cccDigits = ccc.replace(/\D+/g, '');
                if (cccDigits.length !== 11) {
                    showErrors(f, { contribution_account_code: 'El CCC ha de tenir exactament 11 dígits.' });
                    return;
                }
            }
        }
        if (module() === 'maintenance_social_security_coefficients') {
            var epi = (fd.get('id') || '').toString().trim();
            if (!/^\d{1,3}$/.test(epi)) {
                showErrors(f, { id: 'L’epígraf ha de ser numèric i tenir com a màxim 3 dígits.' });
                return;
            }
            var pctFields = ['company_1', 'company_2', 'company_3', 'company_4', 'company_5a', 'company_5b', 'company_5c', 'company_5d', 'company_5e', 'temporary_employment_company'];
            for (var i = 0; i < pctFields.length; i += 1) {
                var field = pctFields[i];
                var val = normalizeSsCoeffVisiblePercentInput((fd.get(field) || '').toString());
                if (val !== '') {
                    if (/[^0-9.]/.test(val)) {
                        var err1 = {};
                        err1[field] = 'Només es permeten xifres, coma o punt i, opcionalment, el símbol %.';
                        showErrors(f, err1);
                        return;
                    }
                    if (!/^\d+$/.test(val) && !/^\d+\.\d{1,4}$/.test(val)) {
                        var err2 = {};
                        err2[field] = 'Valor numèric invàlid (màxim 4 decimals al percentatge visible).';
                        showErrors(f, err2);
                        return;
                    }
                    var pctNum = parseFloat(val);
                    if (!isFinite(pctNum) || pctNum < 0 || pctNum > 100) {
                        var err3 = {};
                        err3[field] = 'El percentatge ha d’estar entre 0 i 100.';
                        showErrors(f, err3);
                        return;
                    }
                }
            }
        }
        if (module() === 'maintenance_social_security_base_limits') {
            var gid2 = (fd.get('id') || '').toString().trim();
            var den = (fd.get('name') || '').toString().trim();
            if (!/^\d{1,2}$/.test(gid2)) {
                showErrors(f, { id: 'El grup de cotització ha de ser numèric i tenir com a màxim 2 dígits.' });
                return;
            }
            if (den === '') {
                showErrors(f, { name: 'La denominació és obligatòria.' });
                return;
            }
            var minNorm = normalizeMoneyInput((fd.get('minimum_base') || '').toString());
            if (minNorm === null) {
                showErrors(f, { minimum_base: 'Import invàlid (màxim 2 decimals).' });
                return;
            }
            var maxNorm = normalizeMoneyInput((fd.get('maximum_base') || '').toString());
            if (maxNorm === null) {
                showErrors(f, { maximum_base: 'Import invàlid (màxim 2 decimals).' });
                return;
            }
        }
        if (module() === 'maintenance_subprograms') {
            var pid = (fd.get('program_id') || '').toString().trim();
            var snn = (fd.get('subprogram_number') || '').toString().trim();
            if (pid === '') {
                showErrors(f, { program_id: 'Cal seleccionar un programa.' });
                return;
            }
            if (!/^\d{1,2}$/.test(snn)) {
                showErrors(f, { subprogram_number: 'El número de subprograma ha de tenir entre 1 i 2 dígits numèrics.' });
                return;
            }
        }
        var payload={
            action:'save', module:module(), csrf_token:csrf,
            original_id:(fd.get('original_id')||'').toString(),
            id:(fd.get('id')||'').toString(),
            name:(fd.get('name')||'').toString(),
            sort_order:(fd.get('sort_order')||'').toString(),
            short_name:(fd.get('short_name')||'').toString(),
            full_name:(fd.get('full_name')||'').toString(),
            address:(fd.get('address')||'').toString(),
            postal_code:(fd.get('postal_code')||'').toString(),
            city:(fd.get('city')||'').toString(),
            phone:(fd.get('phone')||'').toString(),
            fax:(fd.get('fax')||'').toString(),
            scale_id:(fd.get('scale_id')||'').toString(),
            subscale_id:(fd.get('subscale_id')||'').toString(),
            class_id:(fd.get('class_id')||'').toString(),
            org_unit_level_1_id:(fd.get('org_unit_level_1_id')||'').toString(),
            org_unit_level_2_id:(fd.get('org_unit_level_2_id')||'').toString(),
            subfunction_id:(fd.get('subfunction_id')||'').toString(),
            program_number:(fd.get('program_number')||'').toString(),
            responsible_person_code:(fd.get('responsible_person_code')||'').toString(),
            description:(fd.get('description')||'').toString(),
            program_id:(fd.get('program_id')||'').toString(),
            subprogram_number:(fd.get('subprogram_number')||'').toString(),
            subprogram_name:(fd.get('name')||'').toString(),
            technical_manager_code:(fd.get('technical_manager_code')||'').toString(),
            elected_manager_code:(fd.get('elected_manager_code')||'').toString(),
            nature:(fd.get('nature')||'').toString(),
            is_mandatory_service:(function(){ var el=document.querySelector('[data-field="is_mandatory_service"]'); return el && el.checked ? 1 : 0; })(),
            has_corporate_agreements:(function(){ var el=document.querySelector('[data-field="has_corporate_agreements"]'); return el && el.checked ? 1 : 0; })(),
            objectives:(fd.get('objectives')||'').toString(),
            activities:(fd.get('activities')||'').toString(),
            notes:(fd.get('notes')||'').toString()
            ,contribution_account_code:(fd.get('contribution_account_code')||'').toString()
            ,company_1:(fd.get('company_1')||'').toString()
            ,company_2:(fd.get('company_2')||'').toString()
            ,company_3:(fd.get('company_3')||'').toString()
            ,company_4:(fd.get('company_4')||'').toString()
            ,company_5a:(fd.get('company_5a')||'').toString()
            ,company_5b:(fd.get('company_5b')||'').toString()
            ,company_5c:(fd.get('company_5c')||'').toString()
            ,company_5d:(fd.get('company_5d')||'').toString()
            ,company_5e:(fd.get('company_5e')||'').toString()
            ,temporary_employment_company:(fd.get('temporary_employment_company')||'').toString()
            ,minimum_base:(fd.get('minimum_base')||'').toString()
            ,maximum_base:(fd.get('maximum_base')||'').toString()
            ,period_label:(fd.get('period_label')||'').toString()
        };
        fetch(apiUrl(),{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},body:JSON.stringify(payload)})
            .then(function(r){return r.json();})
            .then(function(d){ if(d.ok){ closeModal(); if(window.showAlert){ window.showAlert('success','Èxit',d.message||'Desat.'); setTimeout(function(){window.location.reload();},650);} else { window.location.reload(); } } else if(d.errors){ showErrors(f,d.errors); } })
            .catch(function(){ if(window.showAlert) window.showAlert('error','Error','Error de xarxa.'); });
    }
    function confirmDelete(id){
        if(!cfg().canDelete || !cfg().implemented) return;
        var csrf=cfg().csrfToken||'';
        var go=function(){ fetch(apiUrl(),{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},body:JSON.stringify({action:'delete',module:module(),id:id,csrf_token:csrf})}).then(function(r){return r.json();}).then(function(d){ if(d.ok){ if(window.showAlert){ window.showAlert('success','Èxit',d.message||'Eliminat.'); setTimeout(function(){window.location.reload();},650);} else {window.location.reload();} } else if(d.errors&&d.errors._general&&window.showAlert){ window.showAlert('error','Error',d.errors._general);} }); };
        if(window.showConfirm){ window.showConfirm('Registre actiu','Desitja eliminar aquest registre?',go,{confirmLabel:'Si',cancelLabel:'No'}); } else { if(confirm('Vols eliminar aquest registre?')) go(); }
    }
    document.addEventListener('DOMContentLoaded',function(){
        setupFields();
        var scale=$('#maintenance_scale_id'), sub=$('#maintenance_subscale_id');
        if(scale) scale.addEventListener('change', applyCascades);
        if(sub) sub.addEventListener('change', applyCascades);
        document.body.addEventListener('click',function(e){
            if(e.target.closest('[data-maintenance-open-create]')){ e.preventDefault(); openCreate(); }
            if(e.target.closest('[data-maintenance-edit]')){ e.preventDefault(); var id=(e.target.closest('[data-maintenance-edit]').getAttribute('data-maintenance-edit')||'').toString().trim(); if(id!=='') openEdit(id); }
            if(e.target.closest('[data-maintenance-delete]')){ e.preventDefault(); var idd=(e.target.closest('[data-maintenance-delete]').getAttribute('data-maintenance-delete')||'').toString().trim(); if(idd!=='') confirmDelete(idd); }
            if(e.target.closest('[data-maintenance-modal-close]')){ e.preventDefault(); closeModal(); }
        });
        var mf=$('#maintenance-modal-form');
        if(mf){
            mf.addEventListener('submit',submit);
            mf.addEventListener('input',function(ev){
                if(module()==='maintenance_programs'){
                    var t=ev.target;
                    if(t&&t.getAttribute&&(t.getAttribute('data-field')==='subfunction_id'||t.getAttribute('data-field')==='program_number')) updateProgramComputedCode();
                }
                if(module()==='maintenance_subprograms'){
                    var t2=ev.target;
                    if(t2&&t2.getAttribute&&(t2.getAttribute('data-field')==='subprogram_parent_program'||t2.getAttribute('data-field')==='subprogram_number')) updateSubprogramComputedCode();
                }
                if(module()==='maintenance_social_security_companies'){
                    var t4=ev.target;
                    if(t4&&t4.getAttribute&&t4.getAttribute('data-field')==='contribution_account_code'){
                        t4.value = formatCompanyCccInput(t4.value);
                    }
                }
            });
            mf.addEventListener('change',function(ev){
                if(module()!=='maintenance_subprograms') return;
                var t3=ev.target;
                if(t3&&t3.getAttribute&&(t3.getAttribute('data-field')==='subprogram_parent_program')) updateSubprogramComputedCode();
            });
        }
    });
})();
