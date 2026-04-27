(function () {
    'use strict';
    var currentModalReadonly = false;
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
        if(mod==='maintenance_salary_base_by_group') return row && row.classification_group !== undefined ? row.classification_group : '';
        if(mod==='maintenance_destination_allowances') return row && row.organic_level !== undefined ? row.organic_level : '';
        if(mod==='maintenance_seniority_pay_by_group') return row && row.classification_group !== undefined ? row.classification_group : '';
        if(mod==='maintenance_specific_compensation_special_prices') return row && row.special_specific_compensation_id != null ? String(row.special_specific_compensation_id) : '';
        if(mod==='maintenance_specific_compensation_general') return row && row.general_specific_compensation_id !== undefined ? row.general_specific_compensation_id : '';
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
        if(mod==='maintenance_salary_base_by_group') return row && row.classification_group !== undefined ? row.classification_group : '';
        if(mod==='maintenance_destination_allowances') return row && row.organic_level !== undefined ? row.organic_level : '';
        if(mod==='maintenance_seniority_pay_by_group') return row && row.classification_group !== undefined ? row.classification_group : '';
        if(mod==='maintenance_specific_compensation_special_prices') return row && row.special_specific_compensation_name !== undefined ? row.special_specific_compensation_name : '';
        if(mod==='maintenance_specific_compensation_general') return row && row.general_specific_compensation_name !== undefined ? row.general_specific_compensation_name : '';
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
        var nameInput = $('#maintenance_name');
        var idLabel = $('[data-maintenance-label-id]');
        var nameLabel = $('[data-maintenance-label-name]');
        if (idLabel) {
            idLabel.innerHTML = (mod === 'maintenance_social_security_coefficients' ? 'Epígraf' : (mod === 'maintenance_social_security_base_limits' ? 'Grup. Cot.' : ((mod === 'maintenance_salary_base_by_group' || mod === 'maintenance_seniority_pay_by_group') ? 'Grup classificació' : (mod === 'maintenance_destination_allowances' ? 'Nivell orgànic' : 'Codi')))) + ' <span class="users-modal-form__req">*</span>';
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
            } else if (mod === 'maintenance_availability_types' || mod === 'maintenance_provision_forms' || mod === 'maintenance_social_security_companies' || mod === 'maintenance_social_security_coefficients' || mod === 'maintenance_social_security_base_limits' || mod === 'maintenance_salary_base_by_group' || mod === 'maintenance_destination_allowances' || mod === 'maintenance_seniority_pay_by_group' || mod === 'maintenance_specific_compensation_special_prices' || mod === 'maintenance_specific_compensation_general') {
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
                } else if (mod === 'maintenance_salary_base_by_group') {
                    idInput.setAttribute('maxlength', '20');
                    idInput.removeAttribute('pattern');
                    idInput.setAttribute('inputmode', 'text');
                } else if (mod === 'maintenance_destination_allowances') {
                    idInput.setAttribute('maxlength', '20');
                    idInput.removeAttribute('pattern');
                    idInput.setAttribute('inputmode', 'text');
                } else if (mod === 'maintenance_seniority_pay_by_group') {
                    idInput.setAttribute('maxlength', '20');
                    idInput.removeAttribute('pattern');
                    idInput.setAttribute('inputmode', 'text');
                } else if (mod === 'maintenance_specific_compensation_special_prices') {
                    idInput.setAttribute('maxlength', '10');
                    idInput.setAttribute('inputmode', 'numeric');
                    idInput.setAttribute('pattern', '[0-9]+');
                } else if (mod === 'maintenance_specific_compensation_general') {
                    idInput.setAttribute('maxlength', '10');
                    idInput.setAttribute('inputmode', 'numeric');
                    idInput.setAttribute('pattern', '[0-9]+');
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
        show('base_salary', mod === 'maintenance_salary_base_by_group');
        show('base_salary_extra_pay', mod === 'maintenance_salary_base_by_group');
        show('base_salary_new', mod === 'maintenance_salary_base_by_group');
        show('base_salary_extra_pay_new', mod === 'maintenance_salary_base_by_group');
        show('destination_allowance', mod === 'maintenance_destination_allowances');
        show('destination_allowance_new', mod === 'maintenance_destination_allowances');
        show('seniority_amount', mod === 'maintenance_seniority_pay_by_group');
        show('seniority_extra_pay_amount', mod === 'maintenance_seniority_pay_by_group');
        show('seniority_amount_new', mod === 'maintenance_seniority_pay_by_group');
        show('seniority_extra_pay_amount_new', mod === 'maintenance_seniority_pay_by_group');
        show('special_specific_compensation_name', mod === 'maintenance_specific_compensation_special_prices');
        show('amount', mod === 'maintenance_specific_compensation_special_prices' || mod === 'maintenance_specific_compensation_general');
        show('amount_new', mod === 'maintenance_specific_compensation_special_prices' || mod === 'maintenance_specific_compensation_general');
        show('general_specific_compensation_name', mod === 'maintenance_specific_compensation_general');
        show('decrease_amount', mod === 'maintenance_specific_compensation_general');
        show('decrease_amount_new', mod === 'maintenance_specific_compensation_general');
        show('name', mod !== 'maintenance_social_security_coefficients' && mod !== 'maintenance_salary_base_by_group' && mod !== 'maintenance_destination_allowances' && mod !== 'maintenance_seniority_pay_by_group' && mod !== 'maintenance_specific_compensation_special_prices' && mod !== 'maintenance_specific_compensation_general');
        if (nameInput) {
            if (mod === 'maintenance_salary_base_by_group' || mod === 'maintenance_destination_allowances' || mod === 'maintenance_seniority_pay_by_group' || mod === 'maintenance_specific_compensation_special_prices' || mod === 'maintenance_specific_compensation_general') nameInput.removeAttribute('required');
            else nameInput.setAttribute('required', 'required');
        }
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
    function setReadOnlyMode(readOnly, form) {
        currentModalReadonly = !!readOnly;
        var f = form || $('#maintenance-modal-form');
        var overlay = document.getElementById('maintenance-modal-overlay');
        var saveBtn = overlay ? overlay.querySelector('[data-maintenance-modal-submit]') : null;
        var closeBtn = overlay ? overlay.querySelector('[data-maintenance-modal-cancel]') : null;
        if (saveBtn) {
            if (currentModalReadonly) {
                saveBtn.hidden = true;
                saveBtn.style.display = 'none';
                saveBtn.setAttribute('aria-hidden', 'true');
            } else {
                saveBtn.hidden = false;
                saveBtn.style.display = '';
                saveBtn.removeAttribute('aria-hidden');
            }
        }
        if (closeBtn) closeBtn.textContent = currentModalReadonly ? 'Tancar' : 'Cancel·lar';
        if (!f) return;
        f.querySelectorAll('input, select, textarea, button').forEach(function (el) {
            if (!(el instanceof HTMLElement)) return;
            if (el.matches('[type="hidden"]')) return;
            if (el.matches('[data-maintenance-modal-close]') || el.matches('[data-maintenance-modal-cancel]') || el.matches('[data-maintenance-modal-submit]')) return;
            if (el instanceof HTMLSelectElement) {
                el.disabled = currentModalReadonly;
                return;
            }
            if (el instanceof HTMLTextAreaElement) {
                el.readOnly = currentModalReadonly;
                el.disabled = false;
                return;
            }
            if (el instanceof HTMLInputElement) {
                if (el.type === 'checkbox' || el.type === 'radio') {
                    el.disabled = currentModalReadonly;
                } else {
                    el.readOnly = currentModalReadonly;
                    el.disabled = false;
                }
            }
        });
    }
    function setMode(create, readOnly){
        var h=$('[data-maintenance-modal-heading]'), s=$('[data-maintenance-modal-subheading]');
        if(h) h.textContent=readOnly?'Consultar registre':(create?'Nou registre':'Actualització');
        if(s) s.textContent=readOnly?'Consulta en mode només lectura':(create?'Introdueix les dades del nou registre':'Modifica la informació del registre');
    }
    function openCreate(){ if(!cfg().canCreate || !cfg().implemented) return; var f=$('#maintenance-modal-form'); if(!f) return; reset(f); setMode(true,false); setReadOnlyMode(false,f); if(module()==='maintenance_programs') updateProgramComputedCode(); if(module()==='maintenance_subprograms') updateSubprogramComputedCode(); openModal(); }
    function openRecord(id, readOnly){
        if(!cfg().implemented) return;
        if(readOnly){ if(cfg().canView===false) return; } else { if(!cfg().canEdit) return; }
        var f=$('#maintenance-modal-form'); if(!f) return; reset(f); setMode(false,!!readOnly); setReadOnlyMode(!!readOnly,f);
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
                setReadOnlyMode(!!readOnly, f);
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
                setReadOnlyMode(!!readOnly, f);
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
                setReadOnlyMode(!!readOnly, f);
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
                setReadOnlyMode(!!readOnly, f);
                applyCascades();
                openModal();
                return;
            }
            if (module() === 'maintenance_salary_base_by_group') {
                $('[data-field="original_id"]', f).value = String(r.classification_group != null ? r.classification_group : '');
                $('[data-field="id"]', f).value = String(r.classification_group || '');
                $('[data-field="base_salary"]', f).value = formatMoneyForInput(r.base_salary);
                $('[data-field="base_salary_extra_pay"]', f).value = formatMoneyForInput(r.base_salary_extra_pay);
                $('[data-field="base_salary_new"]', f).value = formatMoneyForInput(r.base_salary_new);
                $('[data-field="base_salary_extra_pay_new"]', f).value = formatMoneyForInput(r.base_salary_extra_pay_new);
                setReadOnlyMode(!!readOnly, f);
                applyCascades();
                openModal();
                return;
            }
            if (module() === 'maintenance_destination_allowances') {
                $('[data-field="original_id"]', f).value = String(r.organic_level != null ? r.organic_level : '');
                $('[data-field="id"]', f).value = String(r.organic_level || '');
                $('[data-field="destination_allowance"]', f).value = formatMoneyForInput(r.destination_allowance);
                $('[data-field="destination_allowance_new"]', f).value = formatMoneyForInput(r.destination_allowance_new);
                setReadOnlyMode(!!readOnly, f);
                applyCascades();
                openModal();
                return;
            }
            if (module() === 'maintenance_seniority_pay_by_group') {
                $('[data-field="original_id"]', f).value = String(r.classification_group != null ? r.classification_group : '');
                $('[data-field="id"]', f).value = String(r.classification_group || '');
                $('[data-field="seniority_amount"]', f).value = formatMoneyForInput(r.seniority_amount);
                $('[data-field="seniority_extra_pay_amount"]', f).value = formatMoneyForInput(r.seniority_extra_pay_amount);
                $('[data-field="seniority_amount_new"]', f).value = formatMoneyForInput(r.seniority_amount_new);
                $('[data-field="seniority_extra_pay_amount_new"]', f).value = formatMoneyForInput(r.seniority_extra_pay_amount_new);
                setReadOnlyMode(!!readOnly, f);
                applyCascades();
                openModal();
                return;
            }
            if (module() === 'maintenance_specific_compensation_special_prices') {
                $('[data-field="original_id"]', f).value = String(r.special_specific_compensation_id != null ? r.special_specific_compensation_id : '');
                $('[data-field="id"]', f).value = String(r.special_specific_compensation_id ?? '');
                $('[data-field="special_specific_compensation_name"]', f).value = String(r.special_specific_compensation_name ?? '');
                $('[data-field="amount"]', f).value = formatMoneyForInput(r.amount);
                $('[data-field="amount_new"]', f).value = formatMoneyForInput(r.amount_new);
                setReadOnlyMode(!!readOnly, f);
                var idEl = $('[data-field="id"]', f);
                if (idEl && !readOnly) {
                    idEl.readOnly = String($('[data-field="original_id"]', f).value || '').trim() !== '';
                }
                if (!readOnly && String($('[data-field="original_id"]', f).value || '').trim() === '0') {
                    var ov0 = document.getElementById('maintenance-modal-overlay');
                    var sb0 = ov0 ? ov0.querySelector('[data-maintenance-modal-submit]') : null;
                    if (sb0) {
                        sb0.hidden = true;
                        sb0.style.display = 'none';
                        sb0.setAttribute('aria-hidden', 'true');
                    }
                    f.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach(function (el) {
                        if (!(el instanceof HTMLElement)) return;
                        if (el instanceof HTMLSelectElement) {
                            el.disabled = true;
                            return;
                        }
                        if (el instanceof HTMLTextAreaElement) {
                            el.readOnly = true;
                            return;
                        }
                        if (el instanceof HTMLInputElement) {
                            if (el.type !== 'checkbox' && el.type !== 'radio') {
                                el.readOnly = true;
                            } else {
                                el.disabled = true;
                            }
                        }
                    });
                }
                applyCascades();
                openModal();
                return;
            }
            if (module() === 'maintenance_specific_compensation_general') {
                $('[data-field="original_id"]', f).value = String(r.general_specific_compensation_id != null ? r.general_specific_compensation_id : '');
                $('[data-field="id"]', f).value = String(r.general_specific_compensation_id || '');
                $('[data-field="general_specific_compensation_name"]', f).value = String(r.general_specific_compensation_name || '');
                $('[data-field="amount"]', f).value = formatMoneyForInput(r.amount);
                $('[data-field="decrease_amount"]', f).value = formatMoneyForInput(r.decrease_amount);
                $('[data-field="amount_new"]', f).value = formatMoneyForInput(r.amount_new);
                $('[data-field="decrease_amount_new"]', f).value = formatMoneyForInput(r.decrease_amount_new);
                setReadOnlyMode(!!readOnly, f);
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
            $('[data-field="base_salary"]',f).value=String(r.base_salary ?? '');
            $('[data-field="base_salary_extra_pay"]',f).value=String(r.base_salary_extra_pay ?? '');
            $('[data-field="base_salary_new"]',f).value=String(r.base_salary_new ?? '');
            $('[data-field="base_salary_extra_pay_new"]',f).value=String(r.base_salary_extra_pay_new ?? '');
            $('[data-field="destination_allowance"]',f).value=String(r.destination_allowance ?? '');
            $('[data-field="destination_allowance_new"]',f).value=String(r.destination_allowance_new ?? '');
            $('[data-field="seniority_amount"]',f).value=String(r.seniority_amount ?? '');
            $('[data-field="seniority_extra_pay_amount"]',f).value=String(r.seniority_extra_pay_amount ?? '');
            $('[data-field="seniority_amount_new"]',f).value=String(r.seniority_amount_new ?? '');
            $('[data-field="seniority_extra_pay_amount_new"]',f).value=String(r.seniority_extra_pay_amount_new ?? '');
            $('[data-field="amount"]',f).value=String(r.amount ?? '');
            $('[data-field="amount_new"]',f).value=String(r.amount_new ?? '');
            $('[data-field="general_specific_compensation_name"]',f).value=String(r.general_specific_compensation_name ?? '');
            $('[data-field="decrease_amount"]',f).value=String(r.decrease_amount ?? '');
            $('[data-field="decrease_amount_new"]',f).value=String(r.decrease_amount_new ?? '');
            if(r.special_specific_compensation_id!==undefined) { var sid=$('[data-field="id"]',f); if(sid) sid.value=String(r.special_specific_compensation_id ?? ''); }
            $('[data-field="special_specific_compensation_name"]',f).value=String(r.special_specific_compensation_name ?? '');
            if(r.general_specific_compensation_id!==undefined) { var gci=$('[data-field="id"]',f); if(gci) gci.value=String(r.general_specific_compensation_id||''); }
            if(r.scale_id!==undefined) $('[data-field="scale_id"]',f).value=String(r.scale_id||'');
            if(r.subscale_id!==undefined) $('[data-field="subscale_id"]',f).value=String(r.subscale_id||'');
            if(r.class_id!==undefined) $('[data-field="class_id"]',f).value=String(r.class_id||'');
            if(r.org_unit_level_1_id!==undefined) { var o1f=$('[data-field="org_unit_level_1_id"]',f); if(o1f) o1f.value=String(r.org_unit_level_1_id); }
            if(r.org_unit_level_2_id!==undefined) { var o2f=$('[data-field="org_unit_level_2_id"]',f); if(o2f) o2f.value=String(r.org_unit_level_2_id); }
            setReadOnlyMode(!!readOnly, f);
            applyCascades();
            openModal();
        }).catch(function(){ if(window.showAlert) window.showAlert('error','Error','Error de xarxa.');});
    }
    function openEdit(id){ openRecord(id, false); }
    function openView(id){ openRecord(id, true); }
    function submit(ev){
        ev.preventDefault();
        if (currentModalReadonly) { return; }
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
        if (module() === 'maintenance_salary_base_by_group') {
            var grp = (fd.get('id') || '').toString().trim();
            if (grp === '') {
                showErrors(f, { id: 'El grup de classificació és obligatori.' });
                return;
            }
            var baseNorm = normalizeMoneyInput((fd.get('base_salary') || '').toString());
            if (baseNorm === null || baseNorm === '') {
                showErrors(f, { base_salary: baseNorm === null ? 'Import invàlid (màxim 2 decimals).' : 'El sou base és obligatori.' });
                return;
            }
            var baseExtraNorm = normalizeMoneyInput((fd.get('base_salary_extra_pay') || '').toString());
            if (baseExtraNorm === null || baseExtraNorm === '') {
                showErrors(f, { base_salary_extra_pay: baseExtraNorm === null ? 'Import invàlid (màxim 2 decimals).' : 'El sou base afectació pagues és obligatori.' });
                return;
            }
            var baseNewNorm = normalizeMoneyInput((fd.get('base_salary_new') || '').toString());
            if (baseNewNorm === null) {
                showErrors(f, { base_salary_new: 'Import invàlid (màxim 2 decimals).' });
                return;
            }
            var baseExtraNewNorm = normalizeMoneyInput((fd.get('base_salary_extra_pay_new') || '').toString());
            if (baseExtraNewNorm === null) {
                showErrors(f, { base_salary_extra_pay_new: 'Import invàlid (màxim 2 decimals).' });
                return;
            }
        }
        if (module() === 'maintenance_destination_allowances') {
            var lvl = (fd.get('id') || '').toString().trim();
            if (lvl === '') {
                showErrors(f, { id: 'El nivell orgànic és obligatori.' });
                return;
            }
            var destNorm = normalizeMoneyInput((fd.get('destination_allowance') || '').toString());
            if (destNorm === null || destNorm === '') {
                showErrors(f, { destination_allowance: destNorm === null ? 'Import invàlid (màxim 2 decimals).' : 'El complement destinació és obligatori.' });
                return;
            }
            var destNewNorm = normalizeMoneyInput((fd.get('destination_allowance_new') || '').toString());
            if (destNewNorm === null) {
                showErrors(f, { destination_allowance_new: 'Import invàlid (màxim 2 decimals).' });
                return;
            }
        }
        if (module() === 'maintenance_seniority_pay_by_group') {
            var grp2 = (fd.get('id') || '').toString().trim();
            if (grp2 === '') {
                showErrors(f, { id: 'El grup de classificació és obligatori.' });
                return;
            }
            var sNorm = normalizeMoneyInput((fd.get('seniority_amount') || '').toString());
            if (sNorm === null || sNorm === '') {
                showErrors(f, { seniority_amount: sNorm === null ? 'Import invàlid (màxim 2 decimals).' : 'El trienni és obligatori.' });
                return;
            }
            var seNorm = normalizeMoneyInput((fd.get('seniority_extra_pay_amount') || '').toString());
            if (seNorm === null || seNorm === '') {
                showErrors(f, { seniority_extra_pay_amount: seNorm === null ? 'Import invàlid (màxim 2 decimals).' : 'El trienni afectació pagues és obligatori.' });
                return;
            }
            var snNorm = normalizeMoneyInput((fd.get('seniority_amount_new') || '').toString());
            if (snNorm === null) {
                showErrors(f, { seniority_amount_new: 'Import invàlid (màxim 2 decimals).' });
                return;
            }
            var senNorm = normalizeMoneyInput((fd.get('seniority_extra_pay_amount_new') || '').toString());
            if (senNorm === null) {
                showErrors(f, { seniority_extra_pay_amount_new: 'Import invàlid (màxim 2 decimals).' });
                return;
            }
        }
        if (module() === 'maintenance_specific_compensation_special_prices') {
            var sid = (fd.get('id') || '').toString().trim();
            if (sid === '') {
                showErrors(f, { id: 'El codi és obligatori.' });
                return;
            }
            if (!/^\d+$/.test(sid)) {
                showErrors(f, { id: 'El codi ha de ser numèric.' });
                return;
            }
            if (sid === '0') {
                showErrors(f, { id: 'El codi 0 està reservat i no es pot donar d\'alta des d\'aquest formulari.' });
                return;
            }
            var origSp = (fd.get('original_id') || '').toString().trim();
            if (origSp === '0') {
                showErrors(f, { _general: 'El codi 0 està reservat i no es pot modificar des d\'aquest formulari.' });
                return;
            }
            var sname = (fd.get('special_specific_compensation_name') || '').toString().trim();
            if (sname === '') {
                showErrors(f, { special_specific_compensation_name: 'La denominació és obligatòria.' });
                return;
            }
            var amountNorm = normalizeMoneyInput((fd.get('amount') || '').toString());
            if (amountNorm === null || amountNorm === '') {
                showErrors(f, { amount: amountNorm === null ? 'Import invàlid (màxim 2 decimals).' : 'El complement específic especial és obligatori.' });
                return;
            }
            var amountNewNorm = normalizeMoneyInput((fd.get('amount_new') || '').toString());
            if (amountNewNorm === null) {
                showErrors(f, { amount_new: 'Import invàlid (màxim 2 decimals).' });
                return;
            }
            fd.set('special_specific_compensation_id', sid);
        }
        if (module() === 'maintenance_specific_compensation_general') {
            var gid = (fd.get('id') || '').toString().trim();
            if (!/^\d+$/.test(gid)) {
                showErrors(f, { id: gid === '' ? 'El codi és obligatori.' : 'El codi ha de ser numèric.' });
                return;
            }
            var gname = (fd.get('general_specific_compensation_name') || '').toString().trim();
            if (gname === '') {
                showErrors(f, { general_specific_compensation_name: 'La descripció del complement és obligatòria.' });
                return;
            }
            var amountNorm2 = normalizeMoneyInput((fd.get('amount') || '').toString());
            if (amountNorm2 === null || amountNorm2 === '') {
                showErrors(f, { amount: amountNorm2 === null ? 'Import invàlid (màxim 2 decimals).' : 'L’import complement és obligatori.' });
                return;
            }
            var decreaseNorm = normalizeMoneyInput((fd.get('decrease_amount') || '').toString());
            if (decreaseNorm === null || decreaseNorm === '') {
                showErrors(f, { decrease_amount: decreaseNorm === null ? 'Import invàlid (màxim 2 decimals).' : 'L’import de la disminució és obligatori.' });
                return;
            }
            var amountNewNorm2 = normalizeMoneyInput((fd.get('amount_new') || '').toString());
            if (amountNewNorm2 === null) {
                showErrors(f, { amount_new: 'Import invàlid (màxim 2 decimals).' });
                return;
            }
            var decreaseNewNorm = normalizeMoneyInput((fd.get('decrease_amount_new') || '').toString());
            if (decreaseNewNorm === null) {
                showErrors(f, { decrease_amount_new: 'Import invàlid (màxim 2 decimals).' });
                return;
            }
            fd.set('general_specific_compensation_id', gid);
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
            ,base_salary:(fd.get('base_salary')||'').toString()
            ,base_salary_extra_pay:(fd.get('base_salary_extra_pay')||'').toString()
            ,base_salary_new:(fd.get('base_salary_new')||'').toString()
            ,base_salary_extra_pay_new:(fd.get('base_salary_extra_pay_new')||'').toString()
            ,destination_allowance:(fd.get('destination_allowance')||'').toString()
            ,destination_allowance_new:(fd.get('destination_allowance_new')||'').toString()
            ,seniority_amount:(fd.get('seniority_amount')||'').toString()
            ,seniority_extra_pay_amount:(fd.get('seniority_extra_pay_amount')||'').toString()
            ,seniority_amount_new:(fd.get('seniority_amount_new')||'').toString()
            ,seniority_extra_pay_amount_new:(fd.get('seniority_extra_pay_amount_new')||'').toString()
            ,special_specific_compensation_id:(fd.get('special_specific_compensation_id')||'').toString()
            ,special_specific_compensation_name:(fd.get('special_specific_compensation_name')||'').toString()
            ,amount:(fd.get('amount')||'').toString()
            ,amount_new:(fd.get('amount_new')||'').toString()
            ,general_specific_compensation_id:(fd.get('general_specific_compensation_id')||'').toString()
            ,general_specific_compensation_name:(fd.get('general_specific_compensation_name')||'').toString()
            ,decrease_amount:(fd.get('decrease_amount')||'').toString()
            ,decrease_amount_new:(fd.get('decrease_amount_new')||'').toString()
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
    function salaryBulkAction(action, payload, successMsg){
        if (module() !== 'maintenance_salary_base_by_group' && module() !== 'maintenance_destination_allowances' && module() !== 'maintenance_seniority_pay_by_group' && module() !== 'maintenance_specific_compensation_special_prices' && module() !== 'maintenance_specific_compensation_general' && module() !== 'maintenance_personal_transitory_bonus') return;
        if (!cfg().canEdit || !cfg().implemented) return;
        var csrf = cfg().csrfToken || '';
        var body = Object.assign({ action: action, module: module(), csrf_token: csrf }, payload || {});
        fetch(apiUrl(), { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json','X-CSRF-Token':csrf}, body: JSON.stringify(body)})
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (d.ok) {
                    if (window.showAlert) {
                        window.showAlert('success','Èxit', successMsg || d.message || 'Operació completada.');
                        setTimeout(function(){ window.location.reload(); }, 650);
                    } else {
                        window.location.reload();
                    }
                    return;
                }
                if (window.showAlert) {
                    var msg = (d.errors && d.errors._general) ? d.errors._general : 'No s’ha pogut completar l’operació.';
                    window.showAlert('error','Error', msg);
                }
            })
            .catch(function(){ if(window.showAlert) window.showAlert('error','Error','Error de xarxa.'); });
    }
    function askIncrementPercent(onApply){
        if (typeof window.showActionModal !== 'function') {
            if (window.showAlert) window.showAlert('error', 'Error', 'No s’ha pogut obrir la finestra d’increment.');
            return;
        }
        window.showActionModal({
            title: 'INCREMENT IMPORTS',
            message: "Introdueix el percentatge d'increment",
            type: 'confirm',
            size: 'md',
            contentHtml:
                '<div class="form-group" style="margin-top:8px;">' +
                    '<input class="form-input" id="maintenance_inline_percent" type="text" inputmode="decimal" autocomplete="off" placeholder="p. ex. 2,5">' +
                    '<p class="form-error" id="maintenance_inline_percent_error" hidden></p>' +
                '</div>',
            onOpen: function (el) {
                var inputEl = el.querySelector('#maintenance_inline_percent');
                if (inputEl) inputEl.focus();
            },
            buttons: [
                { label: 'Cancel·lar', className: 'modal__btn--no', dataClose: true, autofocus: false },
                {
                    label: 'Aplicar increment',
                    className: 'modal__btn--si',
                    closeOnClick: false,
                    autofocus: true,
                    onClick: function (close, btn) {
                        var root = btn && btn.closest('.js-app-modal');
                        var inp = root ? root.querySelector('#maintenance_inline_percent') : null;
                        var err = root ? root.querySelector('#maintenance_inline_percent_error') : null;
                        var raw = String(inp && inp.value ? inp.value : '').trim();
                        var norm = raw.replace(',', '.');
                        var valid = /^\d+(?:\.\d+)?$/.test(norm);
                        if (!valid) {
                            if (err) {
                                err.hidden = false;
                                err.textContent = raw === '' ? 'Cal indicar un percentatge.' : 'El percentatge indicat no és vàlid.';
                            }
                            return;
                        }
                        close();
                        if (typeof onApply === 'function') onApply(raw);
                    }
                }
            ]
        });
    }
    function askSeniorityScope(onSelect){
        if (typeof window.showActionModal !== 'function') {
            if (window.showAlert) window.showAlert('error', 'Error', 'No s’ha pogut obrir la finestra de selecció.');
            return;
        }
        window.showActionModal({
            title: 'ACTUALITZAR TRIENNIS PERSONA',
            message: 'Vols actualitzar només les persones actives o totes les persones?',
            type: 'confirm',
            size: 'md',
            buttons: [
                { label: 'Cancel·lar', className: 'modal__btn--no', dataClose: true },
                { label: 'Totes', className: 'modal__btn--classic', onClick: function(){ if (typeof onSelect === 'function') onSelect('all'); } },
                { label: 'Només actives', className: 'modal__btn--si', onClick: function(){ if (typeof onSelect === 'function') onSelect('active'); }, autofocus: true }
            ]
        });
    }
    function updatePeopleSeniority(scope){
        if (module() !== 'maintenance_seniority_pay_by_group') return;
        if (!cfg().canEdit || !cfg().implemented) return;
        var csrf = cfg().csrfToken || '';
        fetch(apiUrl(), {
            method:'POST',
            credentials:'same-origin',
            headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},
            body: JSON.stringify({ action: 'update_people_seniority', module: module(), csrf_token: csrf, scope: scope })
        })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (d.ok) {
                    if (window.showAlert) window.showAlert('success','Èxit', d.message || 'Operació completada.');
                    return;
                }
                if (window.showAlert) {
                    var msg = (d.errors && d.errors._general) ? d.errors._general : 'No s’ha pogut completar l’operació.';
                    window.showAlert('error','Error', msg);
                }
            })
            .catch(function(){ if(window.showAlert) window.showAlert('error','Error','Error de xarxa.'); });
    }
    function showPtbHint(input, msg, kind) {
        var cell = input && input.closest ? input.closest('.maintenance-ptb-new__cell') : null;
        var h = cell ? cell.querySelector('.maintenance-ptb-new__hint') : null;
        if (!h) return;
        if (!msg) {
            h.hidden = true;
            h.textContent = '';
            h.classList.remove('maintenance-ptb-new__hint--ok', 'maintenance-ptb-new__hint--err');
            return;
        }
        h.hidden = false;
        h.textContent = msg;
        h.classList.remove('maintenance-ptb-new__hint--ok', 'maintenance-ptb-new__hint--err');
        h.classList.add(kind === 'ok' ? 'maintenance-ptb-new__hint--ok' : 'maintenance-ptb-new__hint--err');
        if (kind === 'ok') {
            var msgCopy = msg;
            setTimeout(function () {
                if (h.textContent === msgCopy) {
                    showPtbHint(input, '', '');
                }
            }, 2000);
        }
    }
    function parsePtbClientInput(raw) {
        var t = String(raw || '').trim().replace(/\u00A0/g, '').replace(/€/g, '').replace(/\s+/g, '').trim();
        if (t === '') return { kind: 'empty' };
        var norm = normalizeMoneyInput(String(raw || '').trim());
        if (norm === null) return { kind: 'invalid' };
        if (norm === '') return { kind: 'empty' };
        return { kind: 'amount', norm: norm };
    }
    function commitPtbInput(inp) {
        if (!inp || inp.getAttribute('data-ptb-saving') === '1') return;
        var snap = inp.getAttribute('data-ptb-snapshot');
        if (snap === null || snap === void 0) snap = '';
        var rawTrim = String(inp.value || '').trim();
        if (rawTrim === String(snap).trim()) {
            showPtbHint(inp, '', '');
            return;
        }
        var parsed = parsePtbClientInput(inp.value);
        if (parsed.kind === 'invalid') {
            showPtbHint(inp, 'Import no vàlid.', 'err');
            inp.value = snap;
            return;
        }
        inp.setAttribute('data-ptb-saving', '1');
        var csrf = cfg().csrfToken || '';
        var pid = parseInt(inp.getAttribute('data-person-id') || '0', 10);
        fetch(apiUrl(), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({
                action: 'update_personal_transitory_bonus_new',
                module: module(),
                csrf_token: csrf,
                person_id: pid,
                value: inp.value
            })
        })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                inp.removeAttribute('data-ptb-saving');
                if (d.ok) {
                    var vi = d.value_for_input !== undefined && d.value_for_input !== null ? String(d.value_for_input) : '';
                    inp.value = vi;
                    inp.setAttribute('data-ptb-snapshot', vi);
                    showPtbHint(inp, 'Desat.', 'ok');
                    return;
                }
                var msg = (d.errors && d.errors._general) ? d.errors._general : 'Error en desar.';
                showPtbHint(inp, msg, 'err');
                inp.value = snap;
            })
            .catch(function () {
                inp.removeAttribute('data-ptb-saving');
                showPtbHint(inp, 'Error de xarxa.', 'err');
                inp.value = snap;
            });
    }
    function initPersonalTransitoryBonusInline() {
        document.body.addEventListener('focusin', function (e) {
            var t = e.target;
            if (!t || !t.matches || !t.matches('[data-ptb-new-input]')) return;
            t.setAttribute('data-ptb-snapshot', t.value);
        });
        document.body.addEventListener('keydown', function (e) {
            var inp = e.target;
            if (!inp || !inp.matches || !inp.matches('[data-ptb-new-input]')) return;
            if (e.key === 'Escape') {
                var s = inp.getAttribute('data-ptb-snapshot');
                inp.value = s !== null && s !== void 0 ? s : '';
                showPtbHint(inp, '', '');
                e.preventDefault();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                commitPtbInput(inp);
            }
        });
        document.body.addEventListener('blur', function (e) {
            var inp = e.target;
            if (!inp || !inp.matches || !inp.matches('[data-ptb-new-input]')) return;
            commitPtbInput(inp);
        }, true);
    }
    document.addEventListener('DOMContentLoaded',function(){
        setupFields();
        if (module() === 'maintenance_personal_transitory_bonus') {
            initPersonalTransitoryBonusInline();
        }
        var scale=$('#maintenance_scale_id'), sub=$('#maintenance_subscale_id');
        if(scale) scale.addEventListener('change', applyCascades);
        if(sub) sub.addEventListener('change', applyCascades);
        document.body.addEventListener('click',function(e){
            if(e.target.closest('[data-maintenance-open-create]')){ e.preventDefault(); openCreate(); }
            if(e.target.closest('[data-maintenance-view]')){ e.preventDefault(); var vid=(e.target.closest('[data-maintenance-view]').getAttribute('data-maintenance-view')||'').toString().trim(); if(vid!=='') openView(vid); }
            if(e.target.closest('[data-maintenance-edit]')){ e.preventDefault(); var id=(e.target.closest('[data-maintenance-edit]').getAttribute('data-maintenance-edit')||'').toString().trim(); if(id!=='') openEdit(id); }
            if(e.target.closest('[data-maintenance-delete]')){ e.preventDefault(); var idd=(e.target.closest('[data-maintenance-delete]').getAttribute('data-maintenance-delete')||'').toString().trim(); if(idd!=='') confirmDelete(idd); }
            if(e.target.closest('[data-maintenance-modal-close]')){ e.preventDefault(); closeModal(); }
            if(e.target.closest('[data-salary-increment]')){
                e.preventDefault();
                askIncrementPercent(function(percentRaw){
                    var goInc = function(){ salaryBulkAction('increment_imports', { percent: percentRaw }, 'Imports incrementats correctament.'); };
                    if(window.showConfirm){ window.showConfirm('Confirmació','Aplicar increment global d\'imports?',goInc,{confirmLabel:'Si',cancelLabel:'No'}); } else { goInc(); }
                });
            }
            if(e.target.closest('[data-salary-cancel]')){
                e.preventDefault();
                var goCancel = function(){ salaryBulkAction('cancel_increment', {}, 'Increment anul·lat correctament.'); };
                if(window.showConfirm){ window.showConfirm('Confirmació','Anul·lar l\'increment global?',goCancel,{confirmLabel:'Si',cancelLabel:'No'}); } else { if(confirm('Anul·lar l\'increment global?')) goCancel(); }
            }
            if(e.target.closest('[data-salary-apply]')){
                e.preventDefault();
                var goApply = function(){ salaryBulkAction('apply_imports', {}, 'Imports actualitzats correctament.'); };
                if(window.showConfirm){ window.showConfirm('Confirmació','Actualitzar imports base amb els imports incrementats?',goApply,{confirmLabel:'Si',cancelLabel:'No'}); } else { if(confirm('Actualitzar imports base amb els imports incrementats?')) goApply(); }
            }
            if(e.target.closest('[data-seniority-people-update]')){
                e.preventDefault();
                askSeniorityScope(function(scope){
                    var go = function(){ updatePeopleSeniority(scope); };
                    if (window.showConfirm) {
                        window.showConfirm(
                            'Confirmació',
                            "Aquesta acció recalcularà l'antiguitat anual pressupostada de totes les persones de l'any actiu. Vols continuar?",
                            go,
                            { confirmLabel: 'Si', cancelLabel: 'No' }
                        );
                    } else {
                        go();
                    }
                });
            }
            if(e.target.closest('[data-special-prices-update]') || e.target.closest('[data-specific-workplace-update]')){
                e.preventDefault();
                var goUpdateSpecialPrices = function(){
                    var csrf = cfg().csrfToken || '';
                    fetch(apiUrl(), {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {'Content-Type':'application/json','X-CSRF-Token':csrf},
                        body: JSON.stringify({
                            action: 'update_job_positions_special_prices',
                            module: module(),
                            csrf_token: csrf
                        })
                    })
                        .then(function(r){ return r.json(); })
                        .then(function(d){
                            if (d.ok) {
                                if (window.showAlert) window.showAlert('success', 'Èxit', d.message || "S'han actualitzat els preus de 0 llocs de treball.");
                                return;
                            }
                            if (window.showAlert) window.showAlert('error', 'Error', d.message || 'No s’ha pogut actualitzar els preus dels llocs de treball.');
                        })
                        .catch(function(){
                            if (window.showAlert) window.showAlert('error', 'Error', 'No s’ha pogut actualitzar els preus dels llocs de treball.');
                        });
                };
                if (window.showConfirm) {
                    window.showConfirm(
                        'ACTUALITZAR PREUS LLOC',
                        "Aquesta acció actualitzarà el complement específic especial en tots els llocs de treball amb els imports actuals. Vols continuar?",
                        goUpdateSpecialPrices,
                        { confirmLabel: 'Sí', cancelLabel: 'No' }
                    );
                } else {
                    goUpdateSpecialPrices();
                }
            }
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
