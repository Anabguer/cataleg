(function () {
    'use strict';
    var currentModalReadonly = false;
    var currentMaintenanceModalMode = 'closed';
    var copyManagementPositionSourceId = '';
    function cfg() { return window.APP_MAINTENANCE || {}; }
    function $(s, r) { return (r || document).querySelector(s); }
    function overlay() { return document.getElementById('maintenance-modal-overlay'); }
    function lock() { if (typeof window.lockModalBodyScroll === 'function') window.lockModalBodyScroll(); }
    function unlock() { if (typeof window.unlockModalBodyScroll === 'function') window.unlockModalBodyScroll(); }
    function openModal() { var el = overlay(); if (!el) return; el.removeAttribute('hidden'); el.setAttribute('aria-hidden', 'false'); requestAnimationFrame(function(){ el.classList.add('is-visible'); }); lock(); var f = el.querySelector('input:not([type="hidden"])'); if (f) f.focus(); }
    function closeModal() { var el = overlay(); if (!el) return; var ae = document.activeElement; if (ae && el.contains(ae) && ae.blur) ae.blur(); setTimeout(function(){ el.classList.remove('is-visible'); el.setAttribute('hidden','hidden'); el.setAttribute('aria-hidden','true'); unlock(); },0); }
    function clearErrors(form){ form.querySelectorAll('[data-error-for]').forEach(function(p){p.hidden=true;p.textContent='';}); var w=form.querySelector('.js-maintenance-msg'); if(w) w.hidden=true; var g=form.querySelector('[data-maintenance-form-error]'); if(g) g.textContent=''; }
    /** Errors API de desar: people + subprogram_people es mostren amb modal (showAlert), no només text inferior. */
    function showMaintenanceSaveErrorResponse(form, errs, httpStatus) {
        if (!errs) return;
        httpStatus = httpStatus || 0;
        if (module() === 'people' && errs.subprogram_people && window.showAlert) {
            window.showAlert('warning', 'Avís', errs.subprogram_people);
            var rest = {};
            Object.keys(errs).forEach(function (k) {
                if (k !== 'subprogram_people') rest[k] = errs[k];
            });
            if (Object.keys(rest).length) showErrors(form, rest);
            else clearErrors(form);
            return;
        }
        if (module() === 'people' && errs._general && window.showAlert) {
            var isServer = httpStatus >= 500;
            window.showAlert(isServer ? 'error' : 'warning', isServer ? 'Error' : 'Avís', errs._general);
            var restG = {};
            Object.keys(errs).forEach(function (k) {
                if (k !== '_general') restG[k] = errs[k];
            });
            if (Object.keys(restG).length) showErrors(form, restG);
            else clearErrors(form);
            return;
        }
        showErrors(form, errs);
    }
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
        if(mod==='people') return row && row.person_id !== undefined ? row.person_id : '';
        if(mod==='management_positions') return row && row.position_id !== undefined ? row.position_id : '';
        if(mod==='job_positions') return row && row.job_position_id !== undefined ? String(row.job_position_id) : '';
        if(mod==='maintenance_subprograms') return row && row.subprogram_id !== undefined ? row.subprogram_id : '';
        if(mod==='parameters') return row && (row.catalog_year != null && String(row.catalog_year).trim() !== '') ? String(row.catalog_year) : (row && row.id != null ? String(row.id) : '');
        if(mod==='reports') return row && row.id != null ? String(row.id) : '';
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
        if(mod==='people') return row && row.first_name !== undefined ? row.first_name : '';
        if(mod==='management_positions') return row && row.position_name !== undefined ? row.position_name : '';
        if(mod==='job_positions') return row && row.job_title !== undefined ? row.job_title : '';
        if(mod==='maintenance_social_security_coefficients') return '';
        if(mod==='maintenance_subprograms') return row && row.subprogram_name !== undefined ? row.subprogram_name : '';
        if(mod==='reports') return row && row.report_name !== undefined ? row.report_name : '';
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
        var code = id;
        if (!/^\d+$/.test(code)) return code + ' - ' + name;
        var p = code.length > 6 ? code.slice(0, 6) : ('000000' + code).slice(-6);
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
        return parts[0] + ',' + parts[1] + ' €';
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
    function formatPositionCode4(v) {
        var s = String(v == null ? '' : v).trim();
        if (!/^\d+$/.test(s)) return s;
        return ('0000' + String(parseInt(s, 10))).slice(-4);
    }
    function formatPeopleCode5(v) {
        var s = String(v == null ? '' : v).trim();
        if (!/^\d+$/.test(s)) return s;
        return ('00000' + String(parseInt(s, 10))).slice(-5);
    }
    function formatIsoOrDbDateForDisplay(v) {
        if (v === null || v === undefined) return '';
        var s = String(v).trim();
        if (s === '' || s.indexOf('0000-00-00') === 0) return '';
        if (/^\d{4}-\d{2}-\d{2}/.test(s)) {
            var p = s.slice(0, 10).split('-');
            return p[2] + '/' + p[1] + '/' + p[0];
        }
        return s;
    }
    function jobPositionLegalModeFromSelect() {
        var sel = document.querySelector('#jp_legal_relation_id');
        var id = sel ? String(sel.value || '').trim() : '';
        var map = cfg().jobPositionLegalModes || {};
        return map[id] || 'none';
    }
    function syncJobPositionCategoryCascade(resetCat) {
        if (module() !== 'job_positions') return;
        var scale = document.querySelector('#jp_civil_service_scale_id');
        var sub = document.querySelector('#jp_civil_service_subscale_id');
        var cls = document.querySelector('#jp_civil_service_class_id');
        var cat = document.querySelector('#jp_civil_service_category_id');
        if (!scale || !sub || !cls || !cat) return;
        var sid = String(scale.value || '');
        var ssid = String(sub.value || '');
        var cid = String(cls.value || '');
        Array.prototype.forEach.call(sub.options, function (o) {
            if (!o.value) return;
            o.hidden = sid !== '' && o.getAttribute('data-scale-id') !== sid;
        });
        if (sub.selectedOptions[0] && sub.selectedOptions[0].hidden) sub.value = '';
        ssid = String(sub.value || '');
        Array.prototype.forEach.call(cls.options, function (o) {
            if (!o.value) return;
            var okScale = sid === '' || o.getAttribute('data-scale-id') === sid;
            var okSub = ssid === '' || o.getAttribute('data-subscale-id') === ssid;
            o.hidden = !(okScale && okSub);
        });
        if (cls.selectedOptions[0] && cls.selectedOptions[0].hidden) cls.value = '';
        cid = String(cls.value || '');
        if (resetCat) cat.value = '';
        Array.prototype.forEach.call(cat.options, function (o) {
            if (!o.value) return;
            var okScale = sid === '' || o.getAttribute('data-scale-id') === sid;
            var okClass = cid === '' || o.getAttribute('data-class-id') === cid;
            o.hidden = !(okScale && okClass);
        });
        if (cat.selectedOptions[0] && cat.selectedOptions[0].hidden) cat.value = '';
    }
    /**
     * Ordena la càrrega escala → subescala → classe → categoria (la categoria depèn de la classe).
     * Cal cridar-la després d’omplir `legal_relation_id` i abans de `setupJobPositionFields` o just després
     * d’assignar els valors del registre, sense assignar els quatre selects de cop sense passar la cascada.
     */
    function applyJobPositionCascades(r) {
        if (module() !== 'job_positions' || !r) return;
        var scale = document.querySelector('#jp_civil_service_scale_id');
        var sub = document.querySelector('#jp_civil_service_subscale_id');
        var cls = document.querySelector('#jp_civil_service_class_id');
        var cat = document.querySelector('#jp_civil_service_category_id');
        if (!scale || !sub || !cls || !cat) return;
        var sv = String(r.civil_service_scale_id != null ? r.civil_service_scale_id : '').trim();
        var subv = String(r.civil_service_subscale_id != null ? r.civil_service_subscale_id : '').trim();
        var clv = String(r.civil_service_class_id != null ? r.civil_service_class_id : '').trim();
        var catv = String(r.civil_service_category_id != null ? r.civil_service_category_id : '').trim();
        if (sv !== '') ensureSelectOption(scale, sv, sv);
        scale.value = sv;
        syncJobPositionCategoryCascade(false);
        if (subv !== '') {
            ensureSelectOption(sub, subv, subv);
            sub.value = subv;
        } else {
            sub.value = '';
        }
        syncJobPositionCategoryCascade(false);
        if (clv !== '') {
            ensureSelectOption(cls, clv, clv);
            cls.value = clv;
        } else {
            cls.value = '';
        }
        syncJobPositionCategoryCascade(false);
        if (catv !== '') {
            ensureSelectOption(cat, catv, catv);
            cat.value = catv;
        } else {
            cat.value = '';
        }
        syncJobPositionCategoryCascade(false);
    }
    function syncJobPositionLegalRelationFields() {
        if (module() !== 'job_positions') return;
        var mode = jobPositionLegalModeFromSelect();
        var scale = document.querySelector('#jp_civil_service_scale_id');
        var sub = document.querySelector('#jp_civil_service_subscale_id');
        var cls = document.querySelector('#jp_civil_service_class_id');
        var cat = document.querySelector('#jp_civil_service_category_id');
        var lab = document.querySelector('#jp_labor_category');
        var civilOn = mode === 'civil';
        var laborOn = mode === 'labor';
        [scale, sub, cls, cat].forEach(function (el) {
            if (!el) return;
            el.disabled = !!currentModalReadonly || !civilOn;
            if (!civilOn) el.value = '';
        });
        if (lab) {
            lab.disabled = !!currentModalReadonly || !laborOn;
            if (!laborOn) lab.value = '';
        }
        if (civilOn) syncJobPositionCategoryCascade(false);
    }
    function syncJobPositionActiveDerived() {
        if (module() !== 'job_positions') return;
        var del = document.querySelector('[data-field="deleted_at"]');
        var chk = document.querySelector('[data-field="jp_is_active_derived"]');
        if (!chk) return;
        var raw = del ? String(del.value || '').trim() : '';
        var hasEnd = false;
        if (raw !== '') {
            if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) hasEnd = true;
            else if (/^\d{2}\/\d{2}\/\d{4}$/.test(raw)) hasEnd = true;
        }
        chk.checked = !hasEnd;
    }
    /** Codi visible NNNN.NN i, en alta, hidden compacte NNNNNN a partir de Departament + Número. */
    function syncJobPositionCatalogCode() {
        if (module() !== 'job_positions') return;
        var dept = document.querySelector('[data-job-positions-dept]');
        var num = document.querySelector('[data-job-positions-num]');
        var codeEl = document.querySelector('[data-job-positions-full-code]');
        if (!codeEl) return;
        var dRaw = dept ? String(dept.value || '').trim() : '';
        var nRaw = num ? String(num.value || '').trim() : '';
        if (dRaw === '' || nRaw === '') {
            codeEl.value = '';
            return;
        }
        var dDigits = dRaw.replace(/\D/g, '');
        var nDigits = nRaw.replace(/\D/g, '');
        if (dDigits === '' || nDigits === '') {
            codeEl.value = '';
            return;
        }
        var d4 = dDigits.length > 4 ? dDigits.slice(-4) : dDigits.padStart(4, '0');
        var n2 = nDigits.length >= 2 ? nDigits.slice(-2) : nDigits.padStart(2, '0');
        if (!/^\d{2}$/.test(n2)) {
            codeEl.value = '';
            return;
        }
        codeEl.value = d4 + '.' + n2;
        enforceJobPositionFullCodeReadonly();
    }
    function syncJobPositionHiddenIdWithCatalog() {
        if (module() !== 'job_positions') return;
        var orig = document.querySelector('[data-field="original_id"]');
        if (orig && String(orig.value || '').trim() !== '') return;
        var cat = document.querySelector('[data-job-positions-full-code]');
        var hid = document.querySelector('#jp_job_position_id');
        if (!hid || !cat) return;
        var parts = String(cat.value || '').trim().split('.');
        if (parts.length !== 2) {
            hid.value = '';
            return;
        }
        var d4 = (parts[0] || '').replace(/\D/g, '').padStart(4, '0');
        var n2 = (parts[1] || '').replace(/\D/g, '').padStart(2, '0');
        if (d4.length > 4) d4 = d4.slice(-4);
        if (n2.length > 2) n2 = n2.slice(-2);
        hid.value = d4 + n2;
    }
    function syncJobPositionSalaryGroupAmountPair(sel, inp) {
        if (module() !== 'job_positions') return;
        var s = typeof sel === 'string' ? document.querySelector(sel) : sel;
        var i = typeof inp === 'string' ? document.querySelector(inp) : inp;
        if (!s || !i) return;
        var map = cfg().jobPositionSalaryGroupAmounts || {};
        var id = String(s.value || '').trim();
        if (id === '') {
            i.value = '';
            return;
        }
        var raw = map[id];
        if (raw === undefined || raw === null || String(raw).trim() === '') {
            i.value = '';
            return;
        }
        i.value = formatMoneyForInput(raw);
    }
    function syncJobPositionOrganicLevelAmount() {
        if (module() !== 'job_positions') return;
        var sel = document.querySelector('#jp_organic_level');
        var inp = document.querySelector('[data-job-positions-organic-amount]');
        if (!sel || !inp) return;
        var map = cfg().jobPositionOrganicLevelAmounts || {};
        var id = String(sel.value || '').trim();
        if (id === '') {
            inp.value = '';
            return;
        }
        var raw = map[id];
        if (raw === undefined || raw === null || String(raw).trim() === '') {
            inp.value = '';
            return;
        }
        inp.value = formatMoneyForInput(raw);
    }
    function syncJobPositionSalaryAmountFields() {
        if (module() !== 'job_positions') return;
        syncJobPositionSalaryGroupAmountPair('#jp_classification_group', '[data-job-positions-classification-group-amount]');
        syncJobPositionSalaryGroupAmountPair('#jp_classification_group_slash', '[data-job-positions-classification-slash-amount]');
        syncJobPositionSalaryGroupAmountPair('#jp_classification_group_new', '[data-job-positions-classification-new-amount]');
        syncJobPositionOrganicLevelAmount();
    }
    function syncJobPositionSpecialCompAmount() {
        if (module() !== 'job_positions') return;
        var sel = document.querySelector('[data-job-positions-special-select]');
        var inp = document.querySelector('[data-job-positions-special-amount]');
        if (!sel || !inp) return;
        var map = cfg().jobPositionSpecialCompAmounts || {};
        var id = String(sel.value || '').trim();
        if (id === '') {
            inp.value = '';
            return;
        }
        var raw = map[id];
        if (raw === undefined || raw === null || String(raw).trim() === '') {
            return;
        }
        inp.value = formatMoneyForInput(raw);
    }
    function syncJobPositionGeneralCompAmount() {
        if (module() !== 'job_positions') return;
        var sel = document.querySelector('#jp_general_specific_compensation_id');
        var inp = document.querySelector('[data-job-positions-general-amount]');
        if (!sel || !inp) return;
        var map = cfg().jobPositionGeneralCompAmounts || {};
        var id = String(sel.value || '').trim();
        if (id === '') {
            inp.value = '';
            return;
        }
        var raw = map[id];
        if (raw === undefined || raw === null || String(raw).trim() === '') {
            return;
        }
        inp.value = formatMoneyForInput(raw);
    }
    function setJobPositionIdentificationLocked(locked) {
        if (module() !== 'job_positions') return;
        var dept = document.querySelector('[data-job-positions-dept]');
        var num = document.querySelector('[data-job-positions-num]');
        if (dept && !currentModalReadonly) dept.disabled = false;
        if (num && !currentModalReadonly) num.disabled = false;
    }
    function updateJobPositionTitleMirrors() {
        if (module() !== 'job_positions') return;
        var src = document.querySelector('[data-job-positions-title-source]');
        var t = src ? String(src.value || '').trim() : '';
        document.querySelectorAll('[data-job-positions-title-mirror]').forEach(function (el) {
            el.value = t;
        });
    }
    function resetJobPositionTabsToFirst() {
        if (module() !== 'job_positions') return;
        var root = document.querySelector('[data-job-positions-modal="1"]');
        if (!root) return;
        var firstKey = 'ident';
        root.querySelectorAll('[data-job-positions-tab]').forEach(function (b) {
            var key = b.getAttribute('data-job-positions-tab');
            var on = key === firstKey;
            b.classList.toggle('is-active', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        root.querySelectorAll('[data-job-positions-panel]').forEach(function (panel) {
            var key = panel.getAttribute('data-job-positions-panel');
            var on = key === firstKey;
            panel.classList.toggle('is-active', on);
            panel.hidden = !on;
        });
    }
    function setupJobPositionTabs() {
        if (module() !== 'job_positions') return;
        var root = document.querySelector('[data-job-positions-modal="1"]');
        if (!root || root.getAttribute('data-tabs-bound') === '1') return;
        root.setAttribute('data-tabs-bound', '1');
        root.querySelectorAll('[data-job-positions-tab]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var key = btn.getAttribute('data-job-positions-tab');
                root.querySelectorAll('[data-job-positions-tab]').forEach(function (b) {
                    var on = b.getAttribute('data-job-positions-tab') === key;
                    b.classList.toggle('is-active', on);
                    b.setAttribute('aria-selected', on ? 'true' : 'false');
                });
                root.querySelectorAll('[data-job-positions-panel]').forEach(function (panel) {
                    var on = panel.getAttribute('data-job-positions-panel') === key;
                    panel.classList.toggle('is-active', on);
                    panel.hidden = !on;
                });
            });
        });
    }
    function jpOcupantPersonCode(pid) {
        var s = String(pid || '').trim();
        if (!s) return '';
        return ('00000' + s).slice(-5);
    }
    function jpOcupantFullName(r) {
        var parts = [r.last_name_1, r.last_name_2, r.first_name].filter(function (x) { return x; });
        return parts.join(' ').trim();
    }
    function jpOcupantSituation(r) {
        if (r.situation_label != null && String(r.situation_label).trim() !== '') return String(r.situation_label).trim();
        var a = (r.administrative_status_name || '').trim();
        if (a) return a;
        return (r.status_text || '').trim();
    }
    function jobPositionsAssignedGetRows() {
        var tb = document.querySelector('[data-job-positions-assigned-rows]');
        if (!tb) return [];
        var out = [];
        tb.querySelectorAll('tr[data-person-id]').forEach(function (tr) {
            var pid = tr.getAttribute('data-person-id');
            if (!pid) return;
            out.push({
                person_id: pid,
                label: tr.getAttribute('data-person-label') || '',
                last_name_1: tr.getAttribute('data-jp-ln1') || '',
                last_name_2: tr.getAttribute('data-jp-ln2') || '',
                first_name: tr.getAttribute('data-jp-fn') || '',
                dedication: tr.getAttribute('data-jp-dedication') || '',
                budgeted_amount: tr.getAttribute('data-jp-budgeted') || '',
                social_security_contribution_coefficient: tr.getAttribute('data-jp-coeff') || '',
                situation_label: tr.getAttribute('data-jp-situation') || '',
                administrative_status_name: tr.getAttribute('data-jp-admst') || '',
                status_text: tr.getAttribute('data-jp-sttxt') || ''
            });
        });
        return out;
    }
    function jobPositionsAssignedSetRows(rows, readOnly) {
        var tb = document.querySelector('[data-job-positions-assigned-rows]');
        if (!tb) return;
        tb.innerHTML = '';
        (rows || []).forEach(function (r) {
            var pid = String(r.person_id != null ? r.person_id : '');
            var tr = document.createElement('tr');
            tr.setAttribute('data-person-id', pid);
            var parts = [r.last_name_1, r.last_name_2, r.first_name].filter(function (x) { return x; });
            var lab = jpOcupantPersonCode(pid) + (parts.length ? (' — ' + parts.join(' ')) : '');
            tr.setAttribute('data-person-label', String(r.label || lab));
            tr.setAttribute('data-jp-ln1', String(r.last_name_1 != null ? r.last_name_1 : ''));
            tr.setAttribute('data-jp-ln2', String(r.last_name_2 != null ? r.last_name_2 : ''));
            tr.setAttribute('data-jp-fn', String(r.first_name != null ? r.first_name : ''));
            tr.setAttribute('data-jp-dedication', r.dedication != null && String(r.dedication).trim() !== '' ? String(r.dedication) : '');
            tr.setAttribute('data-jp-budgeted', r.budgeted_amount != null && String(r.budgeted_amount).trim() !== '' ? String(r.budgeted_amount) : '');
            tr.setAttribute('data-jp-coeff', r.social_security_contribution_coefficient != null && String(r.social_security_contribution_coefficient).trim() !== '' ? String(r.social_security_contribution_coefficient) : '');
            var sit = jpOcupantSituation(r);
            tr.setAttribute('data-jp-situation', sit);
            tr.setAttribute('data-jp-admst', String(r.administrative_status_name != null ? r.administrative_status_name : ''));
            tr.setAttribute('data-jp-sttxt', String(r.status_text != null ? r.status_text : ''));
            var tdC = document.createElement('td');
            tdC.textContent = jpOcupantPersonCode(pid);
            var tdN = document.createElement('td');
            var nm = jpOcupantFullName(r);
            if (!nm && r.label) {
                var lx = String(r.label);
                var ix = lx.indexOf('—');
                nm = (ix >= 0 ? lx.slice(ix + 1) : lx).trim();
            }
            tdN.textContent = nm;
            var tdDed = document.createElement('td');
            tdDed.textContent = formatFractionPercent(r.dedication, 2);
            var tdBud = document.createElement('td');
            tdBud.textContent = formatVisualPercentFromFraction(r.budgeted_amount);
            var tdSit = document.createElement('td');
            tdSit.textContent = sit;
            var tdCoef = document.createElement('td');
            tdCoef.textContent = formatFractionPercent(r.social_security_contribution_coefficient, 4);
            var tdAct = document.createElement('td');
            tdAct.className = 'job-positions-ocupants__actions';
            if (!readOnly) {
                var rm = document.createElement('button');
                rm.type = 'button';
                rm.className = 'btn btn--ghost btn--sm';
                rm.textContent = 'Eliminar';
                rm.setAttribute('data-job-positions-assigned-remove', '');
                tdAct.appendChild(rm);
            }
            tr.appendChild(tdC);
            tr.appendChild(tdN);
            tr.appendChild(tdDed);
            tr.appendChild(tdBud);
            tr.appendChild(tdSit);
            tr.appendChild(tdCoef);
            tr.appendChild(tdAct);
            tb.appendChild(tr);
        });
    }
    function setJobPositionAssignedPeopleReadOnly(readOnly) {
        if (module() !== 'job_positions') return;
        var addBtn = document.querySelector('[data-job-positions-assigned-add]');
        if (addBtn) {
            addBtn.hidden = !!readOnly;
            addBtn.disabled = !!readOnly;
            if (readOnly) addBtn.setAttribute('aria-disabled', 'true');
            else addBtn.removeAttribute('aria-disabled');
        }
        var rows = jobPositionsAssignedGetRows();
        jobPositionsAssignedSetRows(rows, readOnly);
    }
    function openJobPositionAddOccupantModal() {
        if (module() !== 'job_positions') return;
        if (currentMaintenanceModalMode === 'view' || currentModalReadonly) return;
        var picker = cfg().jobPositionsPeoplePicker || [];
        var existing = {};
        jobPositionsAssignedGetRows().forEach(function (r) { existing[String(r.person_id)] = true; });
        var opts = picker.filter(function (p) { return !existing[String(p.person_id)]; });
        if (typeof window.showActionModal !== 'function') return;
        var html = '<div class="form-group" style="margin-top:8px;"><label class="form-label" for="jp_pick_person">Persona</label><select class="form-select" id="jp_pick_person"><option value="">—</option>';
        opts.forEach(function (p) {
            var lab = String(p.label || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
            html += '<option value="' + String(p.person_id) + '">' + lab + '</option>';
        });
        html += '</select><p class="form-error" id="jp_pick_person_err" hidden></p></div>';
        window.showActionModal({
            title: 'Afegir ocupant',
            message: 'Selecciona una persona',
            type: 'confirm',
            size: 'md',
            contentHtml: html,
            buttons: [
                { label: 'Cancel·lar', className: 'modal__btn--no', dataClose: true },
                {
                    label: 'Afegir',
                    className: 'modal__btn--si',
                    closeOnClick: false,
                    onClick: function (close, btn) {
                        var root = btn && btn.closest ? btn.closest('.js-app-modal') : document.querySelector('.js-app-modal');
                        var sel = root ? root.querySelector('#jp_pick_person') : null;
                        var err = root ? root.querySelector('#jp_pick_person_err') : null;
                        var v = sel ? String(sel.value || '').trim() : '';
                        if (v === '') {
                            if (err) { err.hidden = false; err.textContent = 'Cal seleccionar una persona.'; }
                            return;
                        }
                        if (existing[v]) {
                            if (err) { err.hidden = false; err.textContent = 'Aquesta persona ja és ocupant.'; }
                            return;
                        }
                        var pick = opts.filter(function (p) { return String(p.person_id) === v; })[0] || {};
                        var label = '';
                        if (sel && sel.selectedOptions[0]) label = sel.selectedOptions[0].textContent || '';
                        var rows = jobPositionsAssignedGetRows();
                        var row = { person_id: v, label: label };
                        Object.keys(pick).forEach(function (k) { row[k] = pick[k]; });
                        rows.push(row);
                        jobPositionsAssignedSetRows(rows, currentModalReadonly || currentMaintenanceModalMode === 'view');
                        close();
                    }
                }
            ]
        });
    }
    function setupJobPositionFields() {
        if (module() !== 'job_positions') return;
        setupJobPositionTabs();
        var shell = document.querySelector('.job-positions-ocupants');
        if (shell && shell.getAttribute('data-jp-ocupants-delegation') !== '1') {
            shell.setAttribute('data-jp-ocupants-delegation', '1');
            shell.addEventListener('click', function (e) {
                if (module() !== 'job_positions' || currentModalReadonly || currentMaintenanceModalMode === 'view') return;
                var t = e.target;
                if (!t || !t.closest) return;
                var rm = t.closest('[data-job-positions-assigned-remove]');
                if (!rm) return;
                var tr = rm.closest('tr[data-person-id]');
                if (tr && tr.parentNode) tr.parentNode.removeChild(tr);
            });
        }
        var legal = document.querySelector('#jp_legal_relation_id');
        if (legal && legal.getAttribute('data-jp-legal-bound') !== '1') {
            legal.setAttribute('data-jp-legal-bound', '1');
            legal.addEventListener('change', function () {
                syncJobPositionLegalRelationFields();
            });
        }
        var scale = document.querySelector('#jp_civil_service_scale_id');
        var sub = document.querySelector('#jp_civil_service_subscale_id');
        var cls = document.querySelector('#jp_civil_service_class_id');
        if (scale && scale.getAttribute('data-jp-cascade-bound') !== '1') {
            scale.setAttribute('data-jp-cascade-bound', '1');
            scale.addEventListener('change', function () {
                syncJobPositionCategoryCascade(true);
                syncJobPositionLegalRelationFields();
            });
        }
        if (sub && sub.getAttribute('data-jp-cascade-bound') !== '1') {
            sub.setAttribute('data-jp-cascade-bound', '1');
            sub.addEventListener('change', function () { syncJobPositionCategoryCascade(true); });
        }
        if (cls && cls.getAttribute('data-jp-cascade-bound') !== '1') {
            cls.setAttribute('data-jp-cascade-bound', '1');
            cls.addEventListener('change', function () { syncJobPositionCategoryCascade(true); });
        }
        var delAt = document.querySelector('[data-job-positions-deleted-at]');
        if (delAt && delAt.getAttribute('data-jp-del-bound') !== '1') {
            delAt.setAttribute('data-jp-del-bound', '1');
            delAt.addEventListener('change', syncJobPositionActiveDerived);
            delAt.addEventListener('input', syncJobPositionActiveDerived);
        }
        var titleSrc = document.querySelector('[data-job-positions-title-source]');
        if (titleSrc && titleSrc.getAttribute('data-jp-title-bound') !== '1') {
            titleSrc.setAttribute('data-jp-title-bound', '1');
            titleSrc.addEventListener('input', updateJobPositionTitleMirrors);
            titleSrc.addEventListener('change', updateJobPositionTitleMirrors);
        }
        var addBtn = document.querySelector('[data-job-positions-assigned-add]');
        if (addBtn && addBtn.getAttribute('data-jp-add-bound') !== '1') {
            addBtn.setAttribute('data-jp-add-bound', '1');
            addBtn.addEventListener('click', function () {
                if (currentMaintenanceModalMode === 'view' || currentModalReadonly) return;
                openJobPositionAddOccupantModal();
            });
        }
        var deptJp = document.querySelector('[data-job-positions-dept]');
        if (deptJp && deptJp.getAttribute('data-jp-dept-bound') !== '1') {
            deptJp.setAttribute('data-jp-dept-bound', '1');
            deptJp.addEventListener('change', function () {
                syncJobPositionCatalogCode();
                syncJobPositionHiddenIdWithCatalog();
            });
        }
        var numJp = document.querySelector('[data-job-positions-num]');
        if (numJp && numJp.getAttribute('data-jp-num-bound') !== '1') {
            numJp.setAttribute('data-jp-num-bound', '1');
            numJp.addEventListener('input', function () {
                syncJobPositionCatalogCode();
                syncJobPositionHiddenIdWithCatalog();
            });
            numJp.addEventListener('change', function () {
                syncJobPositionCatalogCode();
                syncJobPositionHiddenIdWithCatalog();
            });
        }
        var specSelJp = document.querySelector('[data-job-positions-special-select]');
        if (specSelJp && specSelJp.getAttribute('data-jp-spec-bound') !== '1') {
            specSelJp.setAttribute('data-jp-spec-bound', '1');
            specSelJp.addEventListener('change', syncJobPositionSpecialCompAmount);
        }
        var genSelJp = document.querySelector('#jp_general_specific_compensation_id');
        if (genSelJp && genSelJp.getAttribute('data-jp-gen-bound') !== '1') {
            genSelJp.setAttribute('data-jp-gen-bound', '1');
            genSelJp.addEventListener('change', syncJobPositionGeneralCompAmount);
        }
        var jpCg = document.querySelector('#jp_classification_group');
        if (jpCg && jpCg.getAttribute('data-jp-sal-bound') !== '1') {
            jpCg.setAttribute('data-jp-sal-bound', '1');
            jpCg.addEventListener('change', function () { syncJobPositionSalaryGroupAmountPair('#jp_classification_group', '[data-job-positions-classification-group-amount]'); });
        }
        var jpCgs = document.querySelector('#jp_classification_group_slash');
        if (jpCgs && jpCgs.getAttribute('data-jp-sal-bound') !== '1') {
            jpCgs.setAttribute('data-jp-sal-bound', '1');
            jpCgs.addEventListener('change', function () { syncJobPositionSalaryGroupAmountPair('#jp_classification_group_slash', '[data-job-positions-classification-slash-amount]'); });
        }
        var jpCgn = document.querySelector('#jp_classification_group_new');
        if (jpCgn && jpCgn.getAttribute('data-jp-sal-bound') !== '1') {
            jpCgn.setAttribute('data-jp-sal-bound', '1');
            jpCgn.addEventListener('change', function () { syncJobPositionSalaryGroupAmountPair('#jp_classification_group_new', '[data-job-positions-classification-new-amount]'); });
        }
        var jpOl = document.querySelector('#jp_organic_level');
        if (jpOl && jpOl.getAttribute('data-jp-sal-bound') !== '1') {
            jpOl.setAttribute('data-jp-sal-bound', '1');
            jpOl.addEventListener('change', syncJobPositionOrganicLevelAmount);
        }
        syncJobPositionLegalRelationFields();
        syncJobPositionCategoryCascade(false);
        syncJobPositionActiveDerived();
        updateJobPositionTitleMirrors();
        syncJobPositionSpecialCompAmount();
        syncJobPositionGeneralCompAmount();
        syncJobPositionSalaryAmountFields();
        syncJobPositionCatalogCode();
        syncJobPositionHiddenIdWithCatalog();
    }
    function parseVisualPercent100(value) {
        var t = String(value || '').trim();
        if (t === '') return { ok: true, value: null };
        t = t.replace(/\u00A0/g, '').replace(/%/g, '').replace(/\s+/g, '').replace(',', '.');
        if (!/^\d+(?:\.\d{1,4})?$/.test(t)) return { ok: false, error: 'Percentatge invàlid.' };
        var n = parseFloat(t);
        if (!isFinite(n) || n < 0 || n > 100) return { ok: false, error: 'El percentatge ha d’estar entre 0 i 100.' };
        return { ok: true, value: n.toFixed(4) };
    }
    function formatVisualPercent100(value) {
        if (value === null || value === undefined || String(value).trim() === '') return '';
        var n = parseFloat(String(value).replace(',', '.'));
        if (!isFinite(n)) return '';
        return n.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' %';
    }
    /** subprogram_people.dedication: BBDD 0..100 o llegat 0..1 (fracció); UI sempre 0..100. */
    function formatSubprogramPeopleDedicationDisplay(raw) {
        if (raw === null || raw === undefined || String(raw).trim() === '') return '';
        var n = parseFloat(String(raw).replace(/\s/g, '').replace(',', '.'));
        if (!isFinite(n)) return '';
        if (n > 1 && n <= 100) return formatVisualPercent100(n);
        if (n > 0 && n <= 1) return formatVisualPercent100(n * 100);
        if (n === 0) return formatVisualPercent100(0);
        if (n > 100) return formatVisualPercent100(Math.min(n, 100));
        return formatVisualPercent100(n);
    }
    function syncPeoplePersonalGradeAmount() {
        if (module() !== 'people') return;
        var sel = document.getElementById('maintenance_personal_grade');
        var out = document.querySelector('[data-people-personal-grade-amount]');
        if (!out) return;
        var grade = sel ? String(sel.value || '').trim() : '';
        var map = cfg().peoplePersonalGradeAmounts || {};
        var raw = grade === '' ? undefined : map[grade];
        if (raw === undefined && grade !== '') {
            var n = parseInt(grade, 10);
            if (!isNaN(n) && Object.prototype.hasOwnProperty.call(map, String(n))) {
                raw = map[String(n)];
            }
        }
        if (raw === undefined || raw === null || String(raw).trim() === '') {
            out.value = '';
            return;
        }
        out.value = formatMoneyForInput(raw);
    }
    function formatFractionPercent(value, decimals) {
        var d = typeof decimals === 'number' ? decimals : 2;
        if (value === null || value === undefined || String(value).trim() === '') return '';
        var n = Number(String(value).replace(',', '.'));
        if (Number.isNaN(n)) return '';
        return (n * 100).toLocaleString('es-ES', { minimumFractionDigits: d, maximumFractionDigits: d }) + ' %';
    }
    function parseFractionPercent(value) {
        if (value === null || value === undefined) return { ok: true, value: null };
        var raw = String(value).replace('%', '').trim();
        if (raw === '') return { ok: true, value: null };
        raw = raw.replace(/\u00A0/g, '').replace(/\s+/g, '').replace(',', '.');
        if (!/^\d+(?:\.\d{1,4})?$/.test(raw)) return { ok: false, error: 'Percentatge invàlid.' };
        var visualPercent = Number(raw);
        if (Number.isNaN(visualPercent) || visualPercent < 0 || visualPercent > 100) return { ok: false, error: 'El percentatge ha d’estar entre 0 i 100.' };
        return { ok: true, value: (visualPercent / 100).toFixed(6) };
    }
    /** MEI (paràmetres): decimal real emmagatzemat (0,75 = 0,75 %), sense ×100. */
    function parseParametersMeiFraction(value) {
        if (value === null || value === undefined) return { ok: true, value: null, error: '' };
        var raw = String(value).replace('%', '').trim();
        if (raw === '') return { ok: true, value: null, error: '' };
        raw = raw.replace(/\u00A0/g, '').replace(/\s+/g, '').replace(',', '.');
        if (!/^\d+(?:\.\d{1,4})?$/.test(raw)) return { ok: false, value: null, error: 'Valor MEI invàlid.' };
        var n = parseFloat(raw);
        if (!isFinite(n) || n < 0 || n > 9.9999) return { ok: false, value: null, error: 'El MEI ha d’estar entre 0 i 9,9999.' };
        return { ok: true, value: n.toFixed(4), error: '' };
    }
    function formatMeiFractionForInput(raw) {
        if (raw === null || raw === undefined || String(raw).trim() === '') return '';
        var n = parseFloat(String(raw).replace(/\s/g, '').replace(',', '.'));
        if (!isFinite(n)) return '';
        return String(n).replace('.', ',');
    }
    function syncPeopleActive() {
        if (module() !== 'people') return;
        var term = document.querySelector('[data-field="terminated_at"]');
        var active = document.querySelector('[data-field="is_active"]');
        if (!term || !active) return;
        active.checked = String(term.value || '').trim() === '';
        active.disabled = true;
    }
    function peopleSubprogramRowsEl() {
        return document.querySelector('[data-people-subprogram-rows]');
    }
    function peopleSubprogramSetRows(rows, readOnly) {
        var tbody = peopleSubprogramRowsEl();
        if (!tbody) return;
        tbody.innerHTML = '';
        var subOpts = cfg().subprogramsForPeople || [];
        (rows || []).forEach(function (it) {
            var tr = document.createElement('tr');
            var td1 = document.createElement('td');
            var sel = document.createElement('select');
            sel.className = 'form-select';
            sel.setAttribute('data-people-subprogram-id', '1');
            var o0 = document.createElement('option');
            o0.value = '';
            o0.textContent = 'Selecciona…';
            sel.appendChild(o0);
            subOpts.forEach(function (sp) {
                var o = document.createElement('option');
                o.value = String(sp.id || '');
                o.textContent = String(sp.id || '') + ' - ' + String(sp.name || '');
                sel.appendChild(o);
            });
            sel.value = String(it.subprogram_id || '');
            sel.disabled = !!readOnly;
            td1.appendChild(sel);
            var td2 = document.createElement('td');
            var inp = document.createElement('input');
            inp.type = 'text';
            inp.className = 'form-input';
            inp.setAttribute('data-people-subprogram-dedication', '1');
            inp.value = formatSubprogramPeopleDedicationDisplay(it.dedication);
            inp.disabled = !!readOnly;
            td2.appendChild(inp);
            var td3 = document.createElement('td');
            var del = document.createElement('button');
            del.type = 'button';
            del.className = 'btn btn--ghost btn--sm';
            del.textContent = 'Eliminar';
            del.disabled = !!readOnly;
            del.addEventListener('click', function () { tr.remove(); });
            td3.appendChild(del);
            tr.appendChild(td1);
            tr.appendChild(td2);
            tr.appendChild(td3);
            tbody.appendChild(tr);
        });
    }
    function peopleSubprogramGetRows() {
        var tbody = peopleSubprogramRowsEl();
        if (!tbody) return [];
        var out = [];
        Array.prototype.forEach.call(tbody.querySelectorAll('tr'), function (tr) {
            var sid = tr.querySelector('[data-people-subprogram-id]');
            var ded = tr.querySelector('[data-people-subprogram-dedication]');
            out.push({
                subprogram_id: String(sid && sid.value ? sid.value : '').trim(),
                dedication: String(ded && ded.value ? ded.value : '').trim()
            });
        });
        return out;
    }
    function setPeopleSubprogramsReadOnly(readOnly) {
        if (module() !== 'people') return;
        var tbody = peopleSubprogramRowsEl();
        if (tbody) {
            tbody.querySelectorAll('input,select,button').forEach(function (el) {
                el.disabled = !!readOnly;
            });
            tbody.querySelectorAll('button').forEach(function (btn) {
                btn.classList.toggle('d-none', !!readOnly);
            });
        }
        var addBtn = document.querySelector('[data-people-subprogram-add]');
        if (addBtn) {
            addBtn.disabled = !!readOnly;
            addBtn.classList.toggle('d-none', !!readOnly);
        }
    }
    function loadPeopleSeniorityPayByGroup() {
        var src = cfg().peopleSeniorityPayByGroup || {};
        var out = {};
        Object.keys(src).forEach(function (k) {
            var key = String(k || '').trim().toUpperCase();
            if (!key) return;
            var item = src[k] || {};
            out[key] = {
                monthly_amount: Number(item.monthly_amount || 0) || 0,
                extra_pay_amount: Number(item.extra_pay_amount || 0) || 0
            };
        });
        return out;
    }
    function parsePeopleNumeric(v) {
        var s = String(v == null ? '' : v).trim();
        if (s === '') return null;
        s = s.replace('%', '').replace(/\s+/g, '').replace(',', '.');
        var n = Number(s);
        return isFinite(n) ? n : null;
    }
    function setPeopleSeniorityComputedReadOnly() {
        if (module() !== 'people') return;
        var f = document.getElementById('maintenance-modal-form');
        if (!f) return;
        f.querySelectorAll('[data-people-seniority-monthly],[data-people-seniority-extra],[data-people-seniority-total],[data-people-seniority-accum],[data-people-seniority-db]').forEach(function (el) {
            el.readOnly = true;
            el.disabled = true;
        });
        ['a1', 'a2', 'c1', 'c2', 'e'].forEach(function (g) {
            var tr = f.querySelector('[data-field="group_' + g + '_current_year_triennia"]');
            if (tr) {
                tr.readOnly = true;
                tr.disabled = currentModalReadonly;
            }
        });
    }
    function calculatePeopleSeniority() {
        if (module() !== 'people') return;
        var f = document.getElementById('maintenance-modal-form');
        if (!f) return;
        var byGroup = loadPeopleSeniorityPayByGroup();
        var defs = [
            { key: 'A1', prev: 'group_a1_previous_triennia', pct: 'group_a1_current_year_percentage', cur: 'group_a1_current_year_triennia' },
            { key: 'A2', prev: 'group_a2_previous_triennia', pct: 'group_a2_current_year_percentage', cur: 'group_a2_current_year_triennia' },
            { key: 'C1', prev: 'group_c1_previous_triennia', pct: 'group_c1_current_year_percentage', cur: 'group_c1_current_year_triennia' },
            { key: 'C2', prev: 'group_c2_previous_triennia', pct: 'group_c2_current_year_percentage', cur: 'group_c2_current_year_triennia' },
            { key: 'E', prev: 'group_e_previous_triennia', pct: 'group_e_current_year_percentage', cur: 'group_e_current_year_triennia' }
        ];
        var sumMonthly = 0, sumExtra = 0, sumTotal = 0;
        defs.forEach(function (d) {
            var monthlyAmount = Number((byGroup[d.key] && byGroup[d.key].monthly_amount) || 0) || 0;
            var extraAmount = Number((byGroup[d.key] && byGroup[d.key].extra_pay_amount) || 0) || 0;
            var prevEl = $('[data-field="' + d.prev + '"]', f);
            var pctEl = $('[data-field="' + d.pct + '"]', f);
            var curEl = $('[data-field="' + d.cur + '"]', f);
            var prev = prevEl ? parsePeopleNumeric(prevEl.value) : null;
            var pct = pctEl ? parsePeopleNumeric(pctEl.value) : null;
            var monthly = prev === null ? null : (prev * monthlyAmount);
            var extra = prev === null ? null : (prev * extraAmount);
            var trienniaAny = (pct !== null && pct > 0) ? 1 : null;
            var total = (pct !== null && pct > 0) ? ((pct * monthlyAmount) / 100) : null;
            if (curEl) curEl.value = trienniaAny === null ? '' : String(trienniaAny);
            var mEl = f.querySelector('[data-people-seniority-monthly="' + d.key + '"]');
            var pEl = f.querySelector('[data-people-seniority-extra="' + d.key + '"]');
            var tEl = f.querySelector('[data-people-seniority-total="' + d.key + '"]');
            if (mEl) mEl.value = monthly === null ? '' : formatMoneyForInput(monthly);
            if (pEl) pEl.value = extra === null ? '' : formatMoneyForInput(extra);
            if (tEl) tEl.value = total === null ? '' : formatMoneyForInput(total);
            sumMonthly += monthly || 0;
            sumExtra += extra || 0;
            sumTotal += total || 0;
        });
        var budgetCalc = sumMonthly + sumTotal;
        var extraCalc = sumExtra + sumTotal;
        var annualCalc = (sumMonthly * 12) + (sumExtra * 2) + (sumTotal * 14);
        var budgetEl = f.querySelector('[data-people-seniority-accum="budget"]');
        var extraEl = f.querySelector('[data-people-seniority-accum="extra"]');
        var monthlyEl = f.querySelector('[data-people-seniority-accum="monthly"]');
        var annualEl = f.querySelector('[data-people-seniority-accum="annual"]');
        if (budgetEl) budgetEl.value = formatMoneyForInput(budgetCalc);
        if (extraEl) extraEl.value = formatMoneyForInput(extraCalc);
        if (monthlyEl) monthlyEl.value = formatMoneyForInput(sumMonthly);
        if (annualEl) annualEl.value = formatMoneyForInput(annualCalc);
        setPeopleSeniorityComputedReadOnly();
    }
    function setupPeopleSeniorityFields() {
        if (module() !== 'people') return;
        var f = document.getElementById('maintenance-modal-form');
        if (!f || f.getAttribute('data-people-seniority-bound') === '1') return;
        f.setAttribute('data-people-seniority-bound', '1');
        f.querySelectorAll('[data-people-seniority-edit="1"]').forEach(function (el) {
            el.addEventListener('input', calculatePeopleSeniority);
            el.addEventListener('change', calculatePeopleSeniority);
        });
    }
    function syncPeopleAdministrativeStatusByLegalRelation() {
        if (module() !== 'people') return;
        var f = document.getElementById('maintenance-modal-form');
        if (!f) return;
        var legalEl = $('[data-field="legal_relation_id"]', f);
        var adminEl = $('[data-field="administrative_status_id"]', f);
        if (!legalEl || !adminEl) return;
        var legal = String(legalEl.value || '').trim();
        var allowAdmin = legal !== '' && legal.charAt(0) === '1';
        if (!allowAdmin) adminEl.value = '';
        adminEl.disabled = currentModalReadonly ? true : !allowAdmin;
    }
    function normalizeVisualPercentInput(value) {
        var t = String(value || '').trim();
        if (t === '') return '';
        t = t.replace(/\u00A0/g, '').replace(/%/g, '').replace(/\s+/g, '').replace(',', '.');
        if (/[^0-9.]/.test(t)) return null;
        if (!/^\d+(?:\.\d{1,2})?$/.test(t)) return null;
        return t;
    }
    function formatVisualPercentFromFraction(value) {
        if (value === null || value === undefined) return '';
        var s = String(value).trim();
        if (s === '') return '';
        var n = parseFloat(s.replace(',', '.'));
        if (!isFinite(n)) return '';
        return (n * 100).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' %';
    }
    function loadSalaryGroups() {
        if (module() !== 'management_positions') return;
        var select = document.querySelector('[data-field="classification_group"]');
        if (!select) return;
        fetch(apiUrl() + '&action=get_salary_groups', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d || !d.ok || !Array.isArray(d.groups)) return;
                var current = String(select.value || '');
                select.innerHTML = '<option value="">Selecciona...</option>';
                d.groups.forEach(function (g) {
                    var option = document.createElement('option');
                    option.value = String(g);
                    option.textContent = String(g);
                    select.appendChild(option);
                });
                if (current !== '') select.value = current;
            })
            .catch(function () {});
    }
    function syncManagementPositionActive() {
        if (module() !== 'management_positions') return;
        var deletedWrap = document.querySelector('[data-maintenance-field="deleted_at"]');
        var activeWrap = document.querySelector('[data-maintenance-field="is_active"]');
        var deletedAtInput = deletedWrap ? (deletedWrap.querySelector('[data-field="deleted_at"]') || deletedWrap.querySelector('input,select,textarea')) : document.querySelector('[data-field="deleted_at"]');
        var activeInput = activeWrap ? (activeWrap.querySelector('[data-field="is_active"]') || activeWrap.querySelector('input[type="checkbox"]')) : document.querySelector('[data-field="is_active"]');
        if (!deletedAtInput || !activeInput) return;
        var hasDeletedAt = String(deletedAtInput.value || '').trim() !== '';
        activeInput.checked = !hasDeletedAt;
        activeInput.disabled = true;
    }
    function syncManagementPositionClassFields() {
        if (module() !== 'management_positions') return;
        var getFieldInput = function (fieldName) {
            var wrap = document.querySelector('[data-maintenance-field="' + fieldName + '"]');
            if (!wrap) return null;
            return wrap.querySelector('[data-field="' + fieldName + '"]') || wrap.querySelector('input,select,textarea');
        };
        var positionClassInput = getFieldInput('position_class_id');
        if (!positionClassInput) return;
        var scaleInput = getFieldInput('scale_id');
        var subscaleInput = getFieldInput('subscale_id');
        var classInput = getFieldInput('class_id');
        var categoryInput = getFieldInput('category_id');
        var laborCategoryInput = getFieldInput('labor_category_id') || getFieldInput('labor_category');
        var value = String(positionClassInput.value || '').trim();
        var funcionarioFields = [scaleInput, subscaleInput, classInput, categoryInput];
        var laboralFields = [laborCategoryInput];
        var isViewMode = currentMaintenanceModalMode === 'view' || currentModalReadonly;
        var setDisabled = function (fields, disabled, clearOnDisable) {
            fields.forEach(function (field) {
                if (!field) return;
                field.disabled = !!disabled;
                if (disabled && clearOnDisable) field.value = '';
            });
        };
        if (isViewMode) {
            setDisabled(funcionarioFields, true, false);
            setDisabled(laboralFields, true, false);
            return;
        }
        if (value === '1') {
            setDisabled(funcionarioFields, false, false);
            setDisabled(laboralFields, true, true);
        } else if (value === '2') {
            setDisabled(funcionarioFields, true, true);
            setDisabled(laboralFields, false, false);
        } else {
            setDisabled(funcionarioFields, true, true);
            setDisabled(laboralFields, true, true);
        }
    }
    function syncManagementPositionCategoryCascade(forceClearClass) {
        if (module() !== 'management_positions') return;
        var scale = $('#maintenance_scale_id');
        var sub = $('#maintenance_subscale_id');
        var cls = $('#maintenance_class_id');
        var cat = $('#maintenance_category_id');
        if (!scale || !sub || !cls || !cat) return;
        var sid = String(scale.value || '');
        var ssid = String(sub.value || '');
        var cid = String(cls.value || '');
        if (forceClearClass) {
            cls.value = '';
            cid = '';
            cat.value = '';
        }
        Array.prototype.forEach.call(sub.options, function (o) {
            if (!o.value) return;
            o.hidden = sid !== '' && o.getAttribute('data-scale-id') !== sid;
        });
        if (sub.selectedOptions[0] && sub.selectedOptions[0].hidden) {
            sub.value = '';
            ssid = '';
            cls.value = '';
            cid = '';
            cat.value = '';
        }
        Array.prototype.forEach.call(cls.options, function (o) {
            if (!o.value) return;
            var okScale = sid === '' || o.getAttribute('data-scale-id') === sid;
            var okSub = ssid === '' || o.getAttribute('data-subscale-id') === ssid;
            o.hidden = !(okScale && okSub);
        });
        if (cls.selectedOptions[0] && cls.selectedOptions[0].hidden) {
            cls.value = '';
            cid = '';
            cat.value = '';
        }
        Array.prototype.forEach.call(cat.options, function (o) {
            if (!o.value) return;
            var okScale = sid === '' || o.getAttribute('data-scale-id') === sid;
            var okSub = ssid === '' || o.getAttribute('data-subscale-id') === ssid;
            var okClass = cid !== '' && o.getAttribute('data-class-id') === cid;
            o.hidden = !(okScale && okSub && okClass);
        });
        if (cid === '') {
            cat.value = '';
        } else if (cat.selectedOptions[0] && cat.selectedOptions[0].hidden) {
            cat.value = '';
        }
    }
    function toggleCopyManagementPositionButton() {
        var btn = document.getElementById('btn-copy-management-position');
        if (!btn) return;
        if (module() !== 'management_positions') {
            btn.classList.add('d-none');
            btn.hidden = true;
            btn.style.display = 'none';
            btn.disabled = true;
            return;
        }
        var originalIdEl = document.querySelector('[data-field="original_id"]');
        var sourceId = String(originalIdEl && originalIdEl.value ? originalIdEl.value : '').trim();
        copyManagementPositionSourceId = sourceId;
        var isViewMode = currentMaintenanceModalMode === 'view';
        var show = isViewMode && sourceId !== '';
        if (show) {
            btn.classList.remove('d-none');
            btn.hidden = false;
            btn.style.display = '';
            btn.disabled = false;
            return;
        }
        btn.classList.add('d-none');
        btn.hidden = true;
        btn.style.display = 'none';
        btn.disabled = true;
    }
    function copyManagementPositionValidateInputs(rawId, rawName) {
        var newId = String(rawId || '').trim();
        var newName = String(rawName || '').trim();
        var errors = {};
        if (newId === '') errors.new_position_id = 'El codi és obligatori.';
        else if (!/^\d{1,4}$/.test(newId)) errors.new_position_id = 'El codi ha de ser numèric i de màxim 4 dígits.';
        if (newName === '') errors.new_position_name = 'La denominació és obligatòria.';
        return { errors: errors, newId: newId, newName: newName };
    }
    function executeCopyManagementPosition(newPositionId, newPositionName) {
        var sourceId = String(copyManagementPositionSourceId || '').trim();
        if (sourceId === '') {
            if (window.showAlert) window.showAlert('error', 'Error', 'No s’ha pogut identificar la plaça origen.');
            return;
        }
        var csrf = cfg().csrfToken || '';
        fetch(apiUrl(), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({
                action: 'copy_management_position',
                module: module(),
                csrf_token: csrf,
                source_position_id: sourceId,
                new_position_id: newPositionId,
                new_position_name: newPositionName
            })
        })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d && d.ok) {
                    closeModal();
                    if (window.showAlert) {
                        window.showAlert('success', 'Èxit', d.message || 'Plaça copiada correctament.');
                        setTimeout(function () { window.location.reload(); }, 650);
                    } else {
                        window.location.reload();
                    }
                    return;
                }
                var msg = (d && d.errors && d.errors._general) ? d.errors._general : 'No s’ha pogut copiar la plaça.';
                if (window.showAlert) window.showAlert('error', 'Error', msg);
            })
            .catch(function () {
                if (window.showAlert) window.showAlert('error', 'Error', 'Error de xarxa.');
            });
    }
    function openCopyManagementPositionModal() {
        if (module() !== 'management_positions') return;
        if (typeof window.showActionModal !== 'function') {
            if (window.showAlert) window.showAlert('error', 'Error', 'No s’ha pogut obrir la finestra de còpia.');
            return;
        }
        window.showActionModal({
            title: 'COPIAR PLAÇA',
            message: 'Introdueix el codi i la denominació de la nova plaça.',
            type: 'confirm',
            size: 'md',
            contentHtml:
                '<div class="form-group" style="margin-top:8px;">' +
                    '<label class="form-label" for="copy_new_position_id">Codi nova plaça</label>' +
                    '<input class="form-input" id="copy_new_position_id" type="text" inputmode="numeric" maxlength="4" autocomplete="off" placeholder="p. ex. 0001">' +
                    '<p class="form-error" id="copy_new_position_id_error" hidden></p>' +
                '</div>' +
                '<div class="form-group" style="margin-top:8px;">' +
                    '<label class="form-label" for="copy_new_position_name">Denominació nova plaça</label>' +
                    '<input class="form-input" id="copy_new_position_name" type="text" autocomplete="off">' +
                    '<p class="form-error" id="copy_new_position_name_error" hidden></p>' +
                '</div>',
            onOpen: function (el) {
                var idInput = el.querySelector('#copy_new_position_id');
                if (idInput) idInput.focus();
            },
            buttons: [
                { label: 'Cancel·lar', className: 'modal__btn--no', dataClose: true },
                {
                    label: 'Continuar',
                    className: 'modal__btn--si',
                    closeOnClick: false,
                    autofocus: true,
                    onClick: function (close, btn) {
                        var root = btn && btn.closest('.js-app-modal');
                        if (!root) return;
                        var idInput = root.querySelector('#copy_new_position_id');
                        var nameInput = root.querySelector('#copy_new_position_name');
                        var idErr = root.querySelector('#copy_new_position_id_error');
                        var nameErr = root.querySelector('#copy_new_position_name_error');
                        if (idErr) { idErr.hidden = true; idErr.textContent = ''; }
                        if (nameErr) { nameErr.hidden = true; nameErr.textContent = ''; }
                        var validated = copyManagementPositionValidateInputs(idInput ? idInput.value : '', nameInput ? nameInput.value : '');
                        if (validated.errors.new_position_id && idErr) {
                            idErr.hidden = false;
                            idErr.textContent = validated.errors.new_position_id;
                        }
                        if (validated.errors.new_position_name && nameErr) {
                            nameErr.hidden = false;
                            nameErr.textContent = validated.errors.new_position_name;
                        }
                        if (validated.errors.new_position_id || validated.errors.new_position_name) return;
                        var proceed = function () {
                            close();
                            executeCopyManagementPosition(validated.newId, validated.newName);
                        };
                        if (window.showConfirm) {
                            window.showConfirm('Confirmació', 'Es crearà una nova plaça copiant les dades de la plaça actual. Vols continuar?', proceed, { confirmLabel: 'Sí', cancelLabel: 'No' });
                        } else {
                            proceed();
                        }
                    }
                }
            ]
        });
    }
    function setupCopyManagementPositionButton() {
        var btn = document.getElementById('btn-copy-management-position');
        if (!btn) return;
        if (btn.getAttribute('data-copy-bound') === '1') return;
        btn.setAttribute('data-copy-bound', '1');
        btn.addEventListener('click', function () {
            openCopyManagementPositionModal();
        });
    }
    function setupFields() {
        var mod = module();
        var show = function (name, on) { var el = document.querySelector('[data-maintenance-field="'+name+'"]'); if (el) el.hidden = !on; };
        var idInput = $('#maintenance_id');
        var nameInput = $('#maintenance_name');
        var idLabel = $('[data-maintenance-label-id]');
        var nameLabel = $('[data-maintenance-label-name]');
        if (idLabel) {
            idLabel.innerHTML = (mod === 'parameters' ? 'Any catàleg' : (mod === 'maintenance_social_security_coefficients' ? 'Epígraf' : (mod === 'maintenance_social_security_base_limits' ? 'Grup. Cot.' : ((mod === 'maintenance_salary_base_by_group' || mod === 'maintenance_seniority_pay_by_group') ? 'Grup classificació' : (mod === 'maintenance_destination_allowances' ? 'Nivell orgànic' : 'Codi'))))) + ' <span class="users-modal-form__req">*</span>';
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
            } else if (mod === 'maintenance_availability_types' || mod === 'maintenance_provision_forms' || mod === 'maintenance_social_security_companies' || mod === 'maintenance_social_security_coefficients' || mod === 'maintenance_social_security_base_limits' || mod === 'maintenance_salary_base_by_group' || mod === 'maintenance_destination_allowances' || mod === 'maintenance_seniority_pay_by_group' || mod === 'maintenance_specific_compensation_special_prices' || mod === 'maintenance_specific_compensation_general' || mod === 'management_positions') {
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
                } else if (mod === 'management_positions') {
                    idInput.setAttribute('maxlength', '4');
                    idInput.setAttribute('inputmode', 'numeric');
                    idInput.setAttribute('pattern', '[0-9]{1,4}');
                } else if (mod === 'parameters') {
                    idInput.setAttribute('min', '2000');
                    idInput.setAttribute('max', '2100');
                    idInput.setAttribute('step', '1');
                    idInput.removeAttribute('pattern');
                    idInput.setAttribute('inputmode', 'numeric');
                } else if (mod === 'people') {
                    idInput.setAttribute('maxlength', '5');
                    idInput.setAttribute('inputmode', 'numeric');
                    idInput.setAttribute('pattern', '[0-9]{1,5}');
                }
            } else {
                idInput.type = 'number';
                idInput.setAttribute('min', '1');
            }
        }
        show('id', mod !== 'maintenance_programs' && mod !== 'maintenance_subprograms' && mod !== 'parameters' && mod !== 'reports');
        show('parameters_fields', mod === 'parameters');
        show('reports_fields', mod === 'reports');
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
        show('notes', mod === 'maintenance_subprograms' || mod === 'management_positions');
        show('position_name', mod === 'management_positions');
        show('short_name', mod === 'maintenance_scales' || mod === 'maintenance_subscales' || mod === 'maintenance_classes' || mod === 'maintenance_categories');
        show('full_name', mod === 'maintenance_scales');
        show('scale_id', mod === 'maintenance_subscales' || mod === 'maintenance_categories' || mod === 'maintenance_classes' || mod === 'management_positions');
        show('subscale_id', mod === 'maintenance_categories' || mod === 'maintenance_classes' || mod === 'management_positions');
        show('class_id', mod === 'maintenance_categories' || mod === 'management_positions');
        show('position_class_id', mod === 'management_positions');
        show('category_id', mod === 'management_positions');
        show('labor_category', mod === 'management_positions');
        show('classification_group', mod === 'management_positions');
        show('access_type_id', mod === 'management_positions');
        show('access_system_id', mod === 'management_positions');
        show('budgeted_amount', mod === 'management_positions');
        show('is_offerable', mod === 'management_positions');
        show('opo_year', mod === 'management_positions');
        show('is_to_be_amortized', mod === 'management_positions');
        show('is_internal_promotion', mod === 'management_positions');
        show('created_at', mod === 'management_positions');
        show('creation_file_reference', mod === 'management_positions');
        show('call_for_applications_date', mod === 'management_positions');
        show('deleted_at', mod === 'management_positions');
        show('deletion_file_reference', mod === 'management_positions');
        show('is_active', mod === 'management_positions');
        show('legacy_person_id', mod === 'people');
        show('last_name_1', mod === 'people');
        show('last_name_2', mod === 'people');
        show('first_name', mod === 'people');
        show('birth_date', mod === 'people');
        show('national_id_number', mod === 'people');
        show('email', mod === 'people');
        show('social_security_number', mod === 'people');
        show('job_position_id', mod === 'people');
        show('people_position_id', mod === 'people');
        show('dedication', mod === 'people');
        show('people_budgeted_amount', mod === 'people');
        show('legal_relation_id', mod === 'people');
        show('administrative_status_id', mod === 'people');
        show('status_text', mod === 'people');
        show('company_id', mod === 'people');
        show('social_security_contribution_coefficient', mod === 'people');
        show('productivity_bonus', mod === 'people');
        show('legacy_social_security', mod === 'people');
        show('hired_at', mod === 'people');
        show('terminated_at', mod === 'people');
        show('personal_grade', mod === 'people');
        show('group_a1_previous_triennia', mod === 'people');
        show('group_a1_current_year_percentage', mod === 'people');
        show('group_a1_current_year_triennia', mod === 'people');
        show('group_a2_previous_triennia', mod === 'people');
        show('group_a2_current_year_percentage', mod === 'people');
        show('group_a2_current_year_triennia', mod === 'people');
        show('group_c1_previous_triennia', mod === 'people');
        show('group_c1_current_year_percentage', mod === 'people');
        show('group_c1_current_year_triennia', mod === 'people');
        show('group_c2_previous_triennia', mod === 'people');
        show('group_c2_current_year_percentage', mod === 'people');
        show('group_c2_current_year_triennia', mod === 'people');
        show('group_e_previous_triennia', mod === 'people');
        show('group_e_current_year_percentage', mod === 'people');
        show('group_e_current_year_triennia', mod === 'people');
        show('people_seniority_block', mod === 'people');
        show('subprogram_people', mod === 'people');
        show('notes', mod === 'maintenance_subprograms' || mod === 'management_positions' || mod === 'people');
        show('is_active', mod === 'management_positions' || mod === 'people');
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
        show('name', mod !== 'maintenance_social_security_coefficients' && mod !== 'maintenance_salary_base_by_group' && mod !== 'maintenance_destination_allowances' && mod !== 'maintenance_seniority_pay_by_group' && mod !== 'maintenance_specific_compensation_special_prices' && mod !== 'maintenance_specific_compensation_general' && mod !== 'management_positions' && mod !== 'people' && mod !== 'parameters' && mod !== 'reports');
        if (nameInput) {
            if (mod === 'maintenance_salary_base_by_group' || mod === 'maintenance_destination_allowances' || mod === 'maintenance_seniority_pay_by_group' || mod === 'maintenance_specific_compensation_special_prices' || mod === 'maintenance_specific_compensation_general') nameInput.removeAttribute('required');
            else nameInput.setAttribute('required', 'required');
        }
        if (mod === 'management_positions') {
            setupCopyManagementPositionButton();
            var posClassSel = document.querySelector('[data-field="position_class_id"]');
            if (posClassSel) {
                posClassSel.addEventListener('change', function () {
                    syncManagementPositionClassFields();
                    syncManagementPositionCategoryCascade(false);
                });
            }
            var subSel = document.querySelector('[data-field="subscale_id"]');
            if (subSel) {
                subSel.addEventListener('change', function () {
                    syncManagementPositionCategoryCascade(true);
                });
            }
            var classSel = document.querySelector('[data-field="class_id"]');
            if (classSel) {
                classSel.addEventListener('change', function () {
                    var catSel = document.querySelector('[data-field="category_id"]');
                    if (catSel) catSel.value = '';
                    syncManagementPositionCategoryCascade(false);
                });
            }
            var deletedAtEl = document.querySelector('[data-field="deleted_at"]');
            if (deletedAtEl) deletedAtEl.addEventListener('change', syncManagementPositionActive);
            syncManagementPositionClassFields();
            syncManagementPositionCategoryCascade(false);
            syncManagementPositionActive();
            toggleCopyManagementPositionButton();
        }
        if (mod === 'people') {
            fillSelect($('#maintenance_job_position_id'), cfg().jobPositions || [], 'id', formatJobPosSelectLabel);
            fillSelect($('#maintenance_people_position_id'), cfg().positionsForPeople || [], 'id', function(it){ return String(it.id)+' - '+String(it.name); });
            fillSelect($('#maintenance_legal_relation_id'), cfg().legalRelations || [], 'id', function(it){ return String(it.id)+' - '+String(it.name); });
            fillSelect($('#maintenance_administrative_status_id'), cfg().administrativeStatuses || [], 'id', function(it){ return String(it.id)+' - '+String(it.name); });
            fillSelect($('#maintenance_company_id'), cfg().socialSecurityCompanies || [], 'id', function(it){ return String(it.id)+' - '+String(it.name); });
            fillSelect($('#maintenance_personal_grade'), cfg().peoplePersonalGrades || [], 'id', function(it){ return String(it.id); });
            var pgSel = document.getElementById('maintenance_personal_grade');
            if (pgSel && pgSel.getAttribute('data-pg-amt-bound') !== '1') {
                pgSel.setAttribute('data-pg-amt-bound', '1');
                pgSel.addEventListener('change', syncPeoplePersonalGradeAmount);
            }
            var legal = document.querySelector('[data-field="legal_relation_id"]');
            if (legal && legal.getAttribute('data-people-legal-bound') !== '1') {
                legal.setAttribute('data-people-legal-bound', '1');
                legal.addEventListener('change', syncPeopleAdministrativeStatusByLegalRelation);
            }
            setupPeopleSeniorityFields();
            var term = document.querySelector('[data-field="terminated_at"]');
            if (term) term.addEventListener('change', syncPeopleActive);
            var addSp = document.querySelector('[data-people-subprogram-add]');
            if (addSp && addSp.getAttribute('data-people-subprogram-bound') !== '1') {
                addSp.setAttribute('data-people-subprogram-bound', '1');
                addSp.addEventListener('click', function () {
                    var rows = peopleSubprogramGetRows();
                    rows.push({ subprogram_id: '', dedication: '' });
                    peopleSubprogramSetRows(rows, currentModalReadonly);
                });
            }
            syncPeopleActive();
            syncPeopleAdministrativeStatusByLegalRelation();
            calculatePeopleSeniority();
            syncPeoplePersonalGradeAmount();
        }
        if (mod === 'job_positions') {
            setupJobPositionFields();
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
            if (it.class_id !== undefined) o.setAttribute('data-class-id', String(it.class_id));
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
        if(mod !== 'maintenance_categories' && mod !== 'management_positions') return;
        if(!cls) return;
        var ssid=sub.value;
        Array.prototype.forEach.call(sub.options,function(o){ if(!o.value) return; o.hidden = sid!=='' && o.getAttribute('data-scale-id')!==sid; });
        if(sub.selectedOptions[0] && sub.selectedOptions[0].hidden) sub.value='';
        ssid=sub.value;
        Array.prototype.forEach.call(cls.options,function(o){ if(!o.value) return; var okScale = sid==='' || o.getAttribute('data-scale-id')===sid; var okSub = ssid==='' || o.getAttribute('data-subscale-id')===ssid; o.hidden = !(okScale && okSub); });
        if(cls.selectedOptions[0] && cls.selectedOptions[0].hidden) cls.value='';
        if(mod === 'management_positions') {
            var cat = $('#maintenance_category_id');
            if(!cat) return;
            var cid = cls.value;
            Array.prototype.forEach.call(cat.options,function(o){ if(!o.value) return; var okScale = sid==='' || o.getAttribute('data-scale-id')===sid; var okSub = ssid==='' || o.getAttribute('data-subscale-id')===ssid; var okClass = cid==='' || o.getAttribute('data-class-id')===cid; o.hidden = !(okScale && okSub && okClass); });
            if(cat.selectedOptions[0] && cat.selectedOptions[0].hidden) cat.value='';
        }
    }
    function reset(form){
        form.reset();
        copyManagementPositionSourceId = '';
        $('[data-field="original_id"]',form).value='';
        clearErrors(form);
        fillSelect($('#maintenance_scale_id'), cfg().scales || [], 'id', function(it){ return String(it.id)+' - '+String(it.name); });
        fillSelect($('#maintenance_subscale_id'), cfg().subscales || [], 'id', function(it){ return String(it.id)+' - '+String(it.name); });
        if (module() !== 'maintenance_classes') {
            fillSelect($('#maintenance_class_id'), cfg().classes || [], 'id', function(it){ return String(it.id)+' - '+String(it.name); });
        }
        fillSelect($('#maintenance_category_id'), cfg().categories || [], 'id', function(it){ return String(it.id)+' - '+String(it.name); });
        fillSelect($('#maintenance_position_class_id'), cfg().positionClasses || [], 'id', function(it){ return String(it.id)+' - '+String(it.name); });
        fillSelect($('#maintenance_access_type_id'), cfg().accessTypes || [], 'id', function(it){ return String(it.id)+' - '+String(it.name); });
        fillSelect($('#maintenance_access_system_id'), cfg().accessSystems || [], 'id', function(it){ return String(it.id)+' - '+String(it.name); });
        loadSalaryGroups();
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
        if (module() === 'management_positions') {
            syncManagementPositionClassFields();
            syncManagementPositionCategoryCascade(false);
            syncManagementPositionActive();
            toggleCopyManagementPositionButton();
        }
        if (module() === 'people') {
            peopleSubprogramSetRows([], false);
            syncPeopleActive();
            syncPeopleAdministrativeStatusByLegalRelation();
            calculatePeopleSeniority();
            syncPeoplePersonalGradeAmount();
        }
        if (module() === 'job_positions') {
            jobPositionsAssignedSetRows([], false);
            resetJobPositionTabsToFirst();
            var jid0 = document.querySelector('#jp_job_position_id');
            if (jid0) jid0.value = '';
            setupJobPositionFields();
            setJobPositionIdentificationLocked(false);
        }
        if (module() === 'reports') {
            var idRs = $('[data-field="id"]', form);
            if (idRs) idRs.value = '';
            var chRs1 = form.querySelector('[data-field="show_in_general_selector"]');
            var chRs2 = form.querySelector('[data-field="is_active"]');
            if (chRs1) chRs1.checked = true;
            if (chRs2) chRs2.checked = true;
            var rgo0 = $('[data-field="report_group_order"]', form);
            if (rgo0) rgo0.value = '0';
        }
        applyCascades();
    }
    function enforceJobPositionFullCodeReadonly() {
        if (module() !== 'job_positions') return;
        var fullCode = document.querySelector('[data-job-positions-full-code]');
        if (fullCode) {
            fullCode.readOnly = true;
            fullCode.setAttribute('readonly', 'readonly');
            fullCode.tabIndex = -1;
        }
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
            if (currentModalReadonly) {
                if (el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement || el instanceof HTMLSelectElement) {
                    el.disabled = true;
                }
                if (el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement) {
                    el.readOnly = true;
                }
                return;
            }
            if (el instanceof HTMLSelectElement) {
                el.disabled = false;
                return;
            }
            if (el instanceof HTMLTextAreaElement) {
                el.readOnly = false;
                el.disabled = false;
                return;
            }
            if (el instanceof HTMLInputElement) {
                if (el.type === 'checkbox' || el.type === 'radio') {
                    el.disabled = false;
                } else {
                    el.readOnly = false;
                    el.disabled = false;
                }
            }
        });
        if (module() === 'management_positions') {
            var activeEl = f.querySelector('[data-field="is_active"]');
            if (activeEl) activeEl.disabled = true;
            if (!currentModalReadonly) syncManagementPositionClassFields();
            syncManagementPositionActive();
            toggleCopyManagementPositionButton();
        }
        if (module() === 'people') {
            syncPeopleActive();
            peopleSubprogramSetRows(peopleSubprogramGetRows(), currentModalReadonly);
            setPeopleSubprogramsReadOnly(currentModalReadonly);
            syncPeopleAdministrativeStatusByLegalRelation();
            setPeopleSeniorityComputedReadOnly();
            var pgAmt = f.querySelector('[data-people-personal-grade-amount]');
            if (pgAmt) {
                pgAmt.readOnly = true;
                pgAmt.disabled = !!currentModalReadonly;
            }
            syncPeoplePersonalGradeAmount();
        }
        if (module() === 'job_positions') {
            var jpAct = f.querySelector('[data-field="jp_is_active_derived"]');
            if (jpAct) {
                jpAct.disabled = true;
            }
            var jpSpecAmt = f.querySelector('[data-job-positions-special-amount]');
            if (jpSpecAmt) {
                jpSpecAmt.readOnly = true;
                jpSpecAmt.disabled = !!currentModalReadonly;
            }
            var jpGenAmt = f.querySelector('[data-job-positions-general-amount]');
            if (jpGenAmt) {
                jpGenAmt.readOnly = true;
                jpGenAmt.disabled = !!currentModalReadonly;
            }
            ['data-job-positions-classification-group-amount', 'data-job-positions-classification-slash-amount', 'data-job-positions-classification-new-amount', 'data-job-positions-organic-amount'].forEach(function (attr) {
                var el = f.querySelector('[' + attr + ']');
                if (el) {
                    el.readOnly = true;
                    el.disabled = !!currentModalReadonly;
                }
            });
            syncJobPositionLegalRelationFields();
            syncJobPositionActiveDerived();
            setJobPositionAssignedPeopleReadOnly(currentModalReadonly);
            var jpOrig = $('[data-field="original_id"]', f);
            setJobPositionIdentificationLocked(!!(jpOrig && String(jpOrig.value || '').trim() !== ''));
            enforceJobPositionFullCodeReadonly();
        }
    }
    function setMode(create, readOnly){
        currentMaintenanceModalMode = readOnly ? 'view' : (create ? 'new' : 'edit');
        var h=$('[data-maintenance-modal-heading]'), s=$('[data-maintenance-modal-subheading]');
        if(h) h.textContent=readOnly?'Consultar registre':(create?'Nou registre':'Actualització');
        if(s) s.textContent=readOnly?'Consulta en mode només lectura':(create?'Introdueix les dades del nou registre':'Modifica la informació del registre');
        if (module() === 'management_positions') toggleCopyManagementPositionButton();
    }
    function openCreate(){ if(!cfg().canCreate || !cfg().implemented) return; var f=$('#maintenance-modal-form'); if(!f) return; reset(f); setMode(true,false); setReadOnlyMode(false,f); if(module()==='maintenance_programs') updateProgramComputedCode(); if(module()==='maintenance_subprograms') updateSubprogramComputedCode(); if(module()==='job_positions'){ setupJobPositionFields(); setJobPositionIdentificationLocked(false); } openModal(); }
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
            if (module() === 'parameters') {
                $('[data-field="original_id"]', f).value = String(r.catalog_year != null ? r.catalog_year : '');
                $('[data-field="id"]', f).value = String(r.catalog_year != null ? r.catalog_year : '');
                var meiEl = $('[data-field="mei_percentage"]', f);
                if (meiEl) meiEl.value = formatMeiFractionForInput(r.mei_percentage);
                setReadOnlyMode(!!readOnly, f);
                applyCascades();
                openModal();
                return;
            }
            if (module() === 'reports') {
                var pkRs = String(r.id != null ? r.id : '');
                $('[data-field="original_id"]', f).value = pkRs;
                $('[data-field="id"]', f).value = pkRs;
                $('[data-field="report_group"]', f).value = String(r.report_group || '');
                $('[data-field="report_group_order"]', f).value = String(r.report_group_order != null ? r.report_group_order : '0');
                $('[data-field="report_code"]', f).value = String(r.report_code || '');
                $('[data-field="report_name"]', f).value = String(r.report_name || '');
                $('[data-field="report_version"]', f).value = String(r.report_version != null ? r.report_version : '');
                $('[data-field="report_description"]', f).value = String(r.report_description != null ? r.report_description : '');
                $('[data-field="report_explanation"]', f).value = String(r.report_explanation != null ? r.report_explanation : '');
                var chkShow = $('[data-field="show_in_general_selector"]', f);
                if (chkShow) chkShow.checked = Number(r.show_in_general_selector) === 1;
                var chkAct = $('[data-field="is_active"]', f);
                if (chkAct) chkAct.checked = Number(r.is_active) === 1;
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
            if (module() === 'management_positions') {
                $('[data-field="original_id"]', f).value = String(r.position_id != null ? r.position_id : '');
                $('[data-field="id"]', f).value = formatPositionCode4(r.position_id);
                $('[data-field="position_name"]', f).value = String(r.position_name || '');
                $('[data-field="position_class_id"]', f).value = String(r.position_class_id || '');
                $('[data-field="scale_id"]', f).value = String(r.scale_id || '');
                $('[data-field="subscale_id"]', f).value = String(r.subscale_id || '');
                $('[data-field="class_id"]', f).value = String(r.class_id || '');
                $('[data-field="category_id"]', f).value = String(r.category_id || '');
                $('[data-field="labor_category"]', f).value = String(r.labor_category || '');
                $('[data-field="classification_group"]', f).value = String(r.classification_group || '');
                $('[data-field="access_type_id"]', f).value = String(r.access_type_id || '');
                $('[data-field="access_system_id"]', f).value = String(r.access_system_id || '');
                $('[data-field="budgeted_amount"]', f).value = formatVisualPercentFromFraction(r.budgeted_amount);
                $('[data-field="opo_year"]', f).value = String(r.opo_year || '');
                $('[data-field="created_at"]', f).value = String(r.created_at || '');
                $('[data-field="creation_file_reference"]', f).value = String(r.creation_file_reference || '');
                $('[data-field="call_for_applications_date"]', f).value = String(r.call_for_applications_date || '');
                $('[data-field="deleted_at"]', f).value = String(r.deleted_at || '');
                $('[data-field="deletion_file_reference"]', f).value = String(r.deletion_file_reference || '');
                $('[data-field="notes"]', f).value = String(r.notes || '');
                var b1 = $('[data-field="is_offerable"]', f); if (b1) b1.checked = String(r.is_offerable || '0') === '1';
                var b2 = $('[data-field="is_to_be_amortized"]', f); if (b2) b2.checked = String(r.is_to_be_amortized || '0') === '1';
                var b3 = $('[data-field="is_internal_promotion"]', f); if (b3) b3.checked = String(r.is_internal_promotion || '0') === '1';
                syncManagementPositionClassFields();
                syncManagementPositionCategoryCascade(false);
                syncManagementPositionActive();
                applyCascades();
                setReadOnlyMode(!!readOnly, f);
                toggleCopyManagementPositionButton();
                openModal();
                return;
            }
            if (module() === 'people') {
                $('[data-field="original_id"]', f).value = String(r.person_id != null ? r.person_id : '');
                $('[data-field="id"]', f).value = formatPeopleCode5(r.person_id);
                $('[data-field="legacy_person_id"]', f).value = String(r.legacy_person_id || '');
                $('[data-field="last_name_1"]', f).value = String(r.last_name_1 || '');
                $('[data-field="last_name_2"]', f).value = String(r.last_name_2 || '');
                $('[data-field="first_name"]', f).value = String(r.first_name || '');
                $('[data-field="birth_date"]', f).value = String(r.birth_date || '');
                $('[data-field="national_id_number"]', f).value = String(r.national_id_number || '');
                $('[data-field="email"]', f).value = String(r.email || '');
                $('[data-field="social_security_number"]', f).value = String(r.social_security_number || '');
                $('[data-field="job_position_id"]', f).value = String(r.job_position_id || '');
                $('[data-field="people_position_id"]', f).value = String(r.position_id || '');
                $('[data-field="dedication"]', f).value = formatFractionPercent(r.dedication, 2);
                $('[data-field="people_budgeted_amount"]', f).value = formatFractionPercent(r.budgeted_amount, 2);
                $('[data-field="legal_relation_id"]', f).value = String(r.legal_relation_id || '');
                $('[data-field="administrative_status_id"]', f).value = String(r.administrative_status_id || '');
                $('[data-field="status_text"]', f).value = String(r.status_text || '');
                $('[data-field="company_id"]', f).value = String(r.company_id || '');
                $('[data-field="social_security_contribution_coefficient"]', f).value = formatFractionPercent(r.social_security_contribution_coefficient, 4);
                $('[data-field="productivity_bonus"]', f).value = formatMoneyForInput(r.productivity_bonus);
                $('[data-field="legacy_social_security"]', f).value = formatMoneyForInput(r.legacy_social_security);
                $('[data-field="hired_at"]', f).value = String(r.hired_at || '');
                $('[data-field="terminated_at"]', f).value = String(r.terminated_at || '');
                $('[data-field="personal_grade"]', f).value = String(r.personal_grade || '');
                syncPeoplePersonalGradeAmount();
                requestAnimationFrame(function () {
                    syncPeoplePersonalGradeAmount();
                });
                $('[data-field="group_a1_previous_triennia"]', f).value = r.group_a1_previous_triennia == null ? '' : String(r.group_a1_previous_triennia);
                $('[data-field="group_a1_current_year_percentage"]', f).value = r.group_a1_current_year_percentage == null ? '' : String(r.group_a1_current_year_percentage);
                $('[data-field="group_a1_current_year_triennia"]', f).value = r.group_a1_current_year_triennia == null ? '' : String(r.group_a1_current_year_triennia);
                $('[data-field="group_a2_previous_triennia"]', f).value = r.group_a2_previous_triennia == null ? '' : String(r.group_a2_previous_triennia);
                $('[data-field="group_a2_current_year_percentage"]', f).value = r.group_a2_current_year_percentage == null ? '' : String(r.group_a2_current_year_percentage);
                $('[data-field="group_a2_current_year_triennia"]', f).value = r.group_a2_current_year_triennia == null ? '' : String(r.group_a2_current_year_triennia);
                $('[data-field="group_c1_previous_triennia"]', f).value = r.group_c1_previous_triennia == null ? '' : String(r.group_c1_previous_triennia);
                $('[data-field="group_c1_current_year_percentage"]', f).value = r.group_c1_current_year_percentage == null ? '' : String(r.group_c1_current_year_percentage);
                $('[data-field="group_c1_current_year_triennia"]', f).value = r.group_c1_current_year_triennia == null ? '' : String(r.group_c1_current_year_triennia);
                $('[data-field="group_c2_previous_triennia"]', f).value = r.group_c2_previous_triennia == null ? '' : String(r.group_c2_previous_triennia);
                $('[data-field="group_c2_current_year_percentage"]', f).value = r.group_c2_current_year_percentage == null ? '' : String(r.group_c2_current_year_percentage);
                $('[data-field="group_c2_current_year_triennia"]', f).value = r.group_c2_current_year_triennia == null ? '' : String(r.group_c2_current_year_triennia);
                $('[data-field="group_e_previous_triennia"]', f).value = r.group_e_previous_triennia == null ? '' : String(r.group_e_previous_triennia);
                $('[data-field="group_e_current_year_percentage"]', f).value = r.group_e_current_year_percentage == null ? '' : String(r.group_e_current_year_percentage);
                $('[data-field="group_e_current_year_triennia"]', f).value = r.group_e_current_year_triennia == null ? '' : String(r.group_e_current_year_triennia);
                var dbSeniority = f.querySelector('[data-people-seniority-db="seniority_amount"]');
                var dbAnnual = f.querySelector('[data-people-seniority-db="annual_budgeted_seniority"]');
                if (dbSeniority) dbSeniority.value = formatMoneyForInput(r.seniority_amount);
                if (dbAnnual) dbAnnual.value = formatMoneyForInput(r.annual_budgeted_seniority);
                $('[data-field="notes"]', f).value = String(r.notes || '');
                var act = $('[data-field="is_active"]', f); if (act) act.checked = String(r.is_active || '0') === '1';
                peopleSubprogramSetRows(Array.isArray(r.subprogram_people) ? r.subprogram_people : [], !!readOnly);
                syncPeopleActive();
                setReadOnlyMode(!!readOnly, f);
                setPeopleSubprogramsReadOnly(!!readOnly);
                syncPeopleAdministrativeStatusByLegalRelation();
                calculatePeopleSeniority();
                openModal();
                return;
            }
            if (module() === 'job_positions') {
                var jpShell = f.querySelector('[data-job-positions-modal="1"]');
                function jobPosField(dataField) {
                    return jpShell ? jpShell.querySelector('[data-field="' + dataField + '"]') : null;
                }
                $('[data-field="original_id"]', f).value = String(r.job_position_id != null ? r.job_position_id : '');
                var jpid = String(r.job_position_id || '').trim();
                var idElJp = document.querySelector('#jp_job_position_id');
                if (idElJp) idElJp.value = jpid;
                $('[data-field="job_title"]', f).value = String(r.job_title || '');
                $('[data-field="org_unit_level_3_id"]', f).value = String(r.org_unit_level_3_id != null ? r.org_unit_level_3_id : '');
                $('[data-field="job_number"]', f).value = String(r.job_number || '');
                syncJobPositionCatalogCode();
                var catType = String(r.catalog_code || '');
                var catSelJp = jobPosField('catalog_code');
                if (catSelJp) {
                    ensureSelectOption(catSelJp, catType, catType);
                    catSelJp.value = catType;
                }
                var orgDepSel = jobPosField('org_dependency_id');
                var orgDepVal = String(r.org_dependency_id || '').trim();
                if (orgDepSel) {
                    ensureSelectOption(orgDepSel, orgDepVal, orgDepVal);
                    orgDepSel.value = orgDepVal;
                }
                var legalJp = jobPosField('legal_relation_id');
                if (legalJp) legalJp.value = String(r.legal_relation_id != null ? r.legal_relation_id : '');
                applyJobPositionCascades(r);
                var labJp = jobPosField('labor_category');
                if (labJp) labJp.value = String(r.labor_category || '');
                var grpMain = String(r.classification_group || '').trim();
                var grpSlash = String(r.classification_group_slash || '').trim();
                var grpNew = String(r.classification_group_new || '').trim();
                var olvRaw = String(r.organic_level || '').trim();
                var selCg = jobPosField('classification_group');
                var selCgs = jobPosField('classification_group_slash');
                var selCgn = jobPosField('classification_group_new');
                var selOl = jobPosField('organic_level');
                if (selCg) {
                    ensureSelectOption(selCg, grpMain, grpMain);
                    selCg.value = grpMain;
                }
                if (selCgs) {
                    ensureSelectOption(selCgs, grpSlash, grpSlash);
                    selCgs.value = grpSlash;
                }
                if (selCgn) {
                    ensureSelectOption(selCgn, grpNew, grpNew);
                    selCgn.value = grpNew;
                }
                if (selOl) {
                    ensureSelectOption(selOl, olvRaw, olvRaw);
                    selOl.value = olvRaw;
                }
                $('[data-field="special_specific_compensation_id"]', f).value = String(r.special_specific_compensation_id != null ? r.special_specific_compensation_id : '');
                $('[data-field="general_specific_compensation_id"]', f).value = String(r.general_specific_compensation_id != null ? r.general_specific_compensation_id : '');
                $('[data-field="general_specific_compensation_amount"]', f).value = formatMoneyForInput(r.general_specific_compensation_amount);
                $('[data-field="special_specific_compensation_amount"]', f).value = formatMoneyForInput(r.special_specific_compensation_amount);
                $('[data-field="job_type_id"]', f).value = String(r.job_type_id || '');
                $('[data-field="contribution_epigraph_id"]', f).value = String(r.contribution_epigraph_id != null ? r.contribution_epigraph_id : '');
                $('[data-field="contribution_group_id"]', f).value = String(r.contribution_group_id != null ? r.contribution_group_id : '');
                $('[data-field="created_at"]', f).value = formatIsoOrDbDateForDisplay(r.created_at);
                $('[data-field="creation_reason"]', f).value = String(r.creation_reason || '');
                $('[data-field="deleted_at"]', f).value = formatIsoOrDbDateForDisplay(r.deleted_at);
                $('[data-field="deletion_reason"]', f).value = String(r.deletion_reason || '');
                $('[data-field="deletion_file_reference"]', f).value = String(r.deletion_file_reference || '');
                $('[data-field="creation_file_reference"]', f).value = String(r.creation_file_reference || '');
                $('[data-field="job_evaluation"]', f).value = r.job_evaluation == null ? '' : String(r.job_evaluation);
                var bAmJp = $('[data-field="is_to_be_amortized"]', f);
                if (bAmJp) bAmJp.checked = String(r.is_to_be_amortized || '0') === '1';
                $('[data-field="workday_type"]', f).value = String(r.workday_type || '');
                $('[data-field="working_time_dedication"]', f).value = String(r.working_time_dedication || '');
                $('[data-field="schedule_text"]', f).value = String(r.schedule_text || '');
                var bn1 = $('[data-field="has_night_schedule"]', f); if (bn1) bn1.checked = String(r.has_night_schedule || '0') === '1';
                var bh1 = $('[data-field="has_holiday_schedule"]', f); if (bh1) bh1.checked = String(r.has_holiday_schedule || '0') === '1';
                var bs1 = $('[data-field="has_shift_schedule"]', f); if (bs1) bs1.checked = String(r.has_shift_schedule || '0') === '1';
                var bsp = $('[data-field="has_special_dedication"]', f); if (bsp) bsp.checked = String(r.has_special_dedication || '0') === '1';
                $('[data-field="special_dedication_type"]', f).value = String(r.special_dedication_type || '');
                $('[data-field="availability_id"]', f).value = String(r.availability_id != null ? r.availability_id : '');
                $('[data-field="mission"]', f).value = String(r.mission || '');
                $('[data-field="generic_functions"]', f).value = String(r.generic_functions || '');
                $('[data-field="specific_functions"]', f).value = String(r.specific_functions || '');
                $('[data-field="qualification_requirements"]', f).value = String(r.qualification_requirements || '');
                $('[data-field="other_requirements"]', f).value = String(r.other_requirements || '');
                $('[data-field="training_requirements"]', f).value = String(r.training_requirements || '');
                $('[data-field="experience_requirements"]', f).value = String(r.experience_requirements || '');
                $('[data-field="other_merits"]', f).value = String(r.other_merits || '');
                $('[data-field="provision_method_id"]', f).value = String(r.provision_method_id != null ? r.provision_method_id : '');
                $('[data-field="effort"]', f).value = String(r.effort || '');
                $('[data-field="hardship"]', f).value = String(r.hardship || '');
                $('[data-field="danger"]', f).value = String(r.danger || '');
                $('[data-field="incompatibilities"]', f).value = String(r.incompatibilities || '');
                $('[data-field="provincial_notes"]', f).value = String(r.provincial_notes || '');
                $('[data-field="work_center_id"]', f).value = String(r.work_center_id != null ? r.work_center_id : '');
                $('[data-field="notes"]', f).value = String(r.notes || '');
                var ap = Array.isArray(r.assigned_people) ? r.assigned_people : [];
                var apRows = ap.map(function (p) {
                    var pid = String(p.person_id != null ? p.person_id : '');
                    var parts = [p.last_name_1, p.last_name_2, p.first_name].filter(function (x) { return x; });
                    var lab = jpOcupantPersonCode(pid) + (parts.length ? (' — ' + parts.join(' ')) : '');
                    return {
                        person_id: pid,
                        label: lab,
                        last_name_1: p.last_name_1,
                        last_name_2: p.last_name_2,
                        first_name: p.first_name,
                        dedication: p.dedication,
                        budgeted_amount: p.budgeted_amount,
                        social_security_contribution_coefficient: p.social_security_contribution_coefficient,
                        administrative_status_name: p.administrative_status_name,
                        status_text: p.status_text,
                        situation_label: p.situation_label
                    };
                });
                jobPositionsAssignedSetRows(apRows, !!readOnly);
                setupJobPositionFields();
                syncJobPositionActiveDerived();
                updateJobPositionTitleMirrors();
                resetJobPositionTabsToFirst();
                setReadOnlyMode(!!readOnly, f);
                setJobPositionAssignedPeopleReadOnly(!!readOnly);
                syncJobPositionSpecialCompAmount();
                syncJobPositionGeneralCompAmount();
                syncJobPositionSalaryAmountFields();
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
            if(r.general_specific_compensation_id!==undefined) { var gci=$('[data-field="id"]',f); if(gci) gci.value=String(r.general_specific_compensation_id||''); }
            if(r.special_specific_compensation_id!==undefined) { var sid=$('[data-field="id"]',f); if(sid) sid.value=String(r.special_specific_compensation_id ?? ''); }
            $('[data-field="special_specific_compensation_name"]',f).value=String(r.special_specific_compensation_name ?? '');
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
        var jpDeptEl = null;
        var jpNumEl = null;
        var jpDeptWasDis = false;
        var jpNumWasDis = false;
        if (module() === 'job_positions') {
            jpDeptEl = f.querySelector('[data-job-positions-dept]');
            jpNumEl = f.querySelector('[data-job-positions-num]');
            if (jpDeptEl && jpDeptEl.disabled) {
                jpDeptEl.disabled = false;
                jpDeptWasDis = true;
            }
            if (jpNumEl && jpNumEl.disabled) {
                jpNumEl.disabled = false;
                jpNumWasDis = true;
            }
            syncJobPositionCatalogCode();
            syncJobPositionHiddenIdWithCatalog();
        }
        clearErrors(f);
        var csrf=cfg().csrfToken||'';
        var fd=new FormData(f);
        if (module() === 'job_positions') {
            if (jpDeptWasDis && jpDeptEl) jpDeptEl.disabled = true;
            if (jpNumWasDis && jpNumEl) jpNumEl.disabled = true;
        }
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
        if (module() === 'management_positions') {
            var pid2 = (fd.get('id') || '').toString().trim();
            var pname2 = (fd.get('position_name') || '').toString().trim();
            var pclass2 = (fd.get('position_class_id') || '').toString().trim();
            if (!/^\d{1,4}$/.test(pid2)) { showErrors(f, { id: 'El codi és obligatori, numèric i de màxim 4 dígits.' }); return; }
            if (pname2 === '') { showErrors(f, { position_name: 'La denominació és obligatòria.' }); return; }
            if (pclass2 === '') { showErrors(f, { position_class_id: 'La classe de plaça és obligatòria.' }); return; }
            if (pclass2 === '1') {
                if ((fd.get('scale_id') || '').toString().trim() === '') { showErrors(f, { scale_id: 'L’escala és obligatòria.' }); return; }
                if ((fd.get('subscale_id') || '').toString().trim() === '') { showErrors(f, { subscale_id: 'La subescala és obligatòria.' }); return; }
                if ((fd.get('class_id') || '').toString().trim() === '') { showErrors(f, { class_id: 'La classe és obligatòria.' }); return; }
                if ((fd.get('category_id') || '').toString().trim() === '') { showErrors(f, { category_id: 'La categoria és obligatòria.' }); return; }
            } else if (pclass2 === '2' && (fd.get('labor_category') || '').toString().trim() === '') {
                showErrors(f, { labor_category: 'La categoria laboral és obligatòria.' }); return;
            }
            var delAt = (fd.get('deleted_at') || '').toString().trim();
            if (delAt !== '' && !/^\d{4}-\d{2}-\d{2}$/.test(delAt) && !/^\d{2}\/\d{2}\/\d{4}$/.test(delAt)) { showErrors(f, { deleted_at: 'La data baixa no és vàlida.' }); return; }
            var bp = normalizeVisualPercentInput((fd.get('budgeted_amount') || '').toString());
            if (bp === '') { showErrors(f, { budgeted_amount: 'El camp Pressupostat és obligatori.' }); return; }
            if (bp === null) { showErrors(f, { budgeted_amount: 'Percentatge invàlid (màxim 2 decimals).' }); return; }
            if (parseFloat(bp) < 0 || parseFloat(bp) > 100) { showErrors(f, { budgeted_amount: 'El percentatge ha d\'estar entre 0 i 100.' }); return; }
        }
        if (module() === 'people') {
            var pplId = (fd.get('id') || '').toString().trim();
            if (!/^\d{1,5}$/.test(pplId)) { showErrors(f, { id: 'El codi és obligatori, numèric i de màxim 5 dígits.' }); return; }
            if ((fd.get('last_name_1') || '').toString().trim() === '') { showErrors(f, { last_name_1: 'El primer cognom és obligatori.' }); return; }
            if ((fd.get('first_name') || '').toString().trim() === '') { showErrors(f, { first_name: 'El nom és obligatori.' }); return; }
            var em = (fd.get('email') || '').toString().trim();
            if (em !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) { showErrors(f, { email: 'L\'adreça electrònica no és vàlida.' }); return; }
            var d1 = parseFractionPercent((fd.get('dedication') || '').toString());
            if (!d1.ok) { showErrors(f, { dedication: d1.error }); return; }
            var d2 = parseFractionPercent((fd.get('budgeted_amount') || '').toString());
            if (!d2.ok) { showErrors(f, { people_budgeted_amount: d2.error }); return; }
            var d3 = parseFractionPercent((fd.get('social_security_contribution_coefficient') || '').toString());
            if (!d3.ok) { showErrors(f, { social_security_contribution_coefficient: d3.error }); return; }
            var triPctKeys = ['group_a1_current_year_percentage','group_a2_current_year_percentage','group_c1_current_year_percentage','group_c2_current_year_percentage','group_e_current_year_percentage'];
            triPctKeys.forEach(function (k) {
                var val = parsePeopleNumeric(fd.get(k));
                fd.set(k, val === null ? '' : String(val));
                var triKey = k.replace('_current_year_percentage', '_current_year_triennia');
                fd.set(triKey, (val !== null && val > 0) ? '1' : '');
            });
            var prodNorm = normalizeMoneyInput((fd.get('productivity_bonus') || '').toString());
            if (prodNorm === null) { showErrors(f, { productivity_bonus: 'Import invàlid (màxim 2 decimals).' }); return; }
            var compNorm = normalizeMoneyInput((fd.get('legacy_social_security') || '').toString());
            if (compNorm === null) { showErrors(f, { legacy_social_security: 'Import invàlid (màxim 2 decimals).' }); return; }
            fd.set('productivity_bonus', prodNorm || '');
            fd.set('legacy_social_security', compNorm || '');
            var subRows = peopleSubprogramGetRows();
            for (var spi = 0; spi < subRows.length; spi += 1) {
                var rr = subRows[spi];
                if (rr.subprogram_id === '' && rr.dedication === '') continue;
                if (rr.subprogram_id === '') { showErrors(f, { subprogram_people: 'Cal indicar subprograma a totes les files.' }); return; }
                var pd = parseVisualPercent100(rr.dedication);
                if (!pd.ok || pd.value === null) { showErrors(f, { subprogram_people: 'La dedicació dels subprogrames ha d\'estar entre 0 i 100.' }); return; }
            }
        }
        if (module() === 'job_positions') {
            var deptJpV = (fd.get('org_unit_level_3_id') || '').toString().trim();
            var numJpV = (fd.get('job_number') || '').toString().trim();
            var codeDispJpV = (fd.get('job_position_code_display') || '').toString().trim();
            var catJp = (fd.get('job_position_id') || '').toString().trim();
            var jtitle = (fd.get('job_title') || '').toString().trim();
            if (deptJpV === '') { showErrors(f, { org_unit_level_3_id: 'El departament és obligatori.' }); return; }
            if (numJpV === '') { showErrors(f, { job_number: 'El número és obligatori.' }); return; }
            if (codeDispJpV === '') { showErrors(f, { job_position_code_display: 'El camp Codi complet del lloc és obligatori.' }); return; }
            if (catJp === '') { showErrors(f, { job_position_code_display: 'El camp Codi complet del lloc és obligatori.' }); return; }
            if (jtitle === '') { showErrors(f, { job_title: 'La denominació és obligatòria.' }); return; }
            var modeJp = jobPositionLegalModeFromSelect();
            if (modeJp === 'civil') {
                if ((fd.get('civil_service_scale_id') || '').toString().trim() === '') { showErrors(f, { civil_service_scale_id: 'L’escala és obligatòria.' }); return; }
                if ((fd.get('civil_service_subscale_id') || '').toString().trim() === '') { showErrors(f, { civil_service_subscale_id: 'La subescala és obligatòria.' }); return; }
                if ((fd.get('civil_service_class_id') || '').toString().trim() === '') { showErrors(f, { civil_service_class_id: 'La classe és obligatòria.' }); return; }
                if ((fd.get('civil_service_category_id') || '').toString().trim() === '') { showErrors(f, { civil_service_category_id: 'La categoria és obligatòria.' }); return; }
            } else if (modeJp === 'labor') {
                if ((fd.get('labor_category') || '').toString().trim() === '') { showErrors(f, { labor_category: 'La categoria laboral és obligatòria.' }); return; }
            }
            var specJp = normalizeMoneyInput((fd.get('special_specific_compensation_amount') || '').toString());
            if (specJp === null) { showErrors(f, { special_specific_compensation_amount: 'Import invàlid.' }); return; }
            var caJp = (fd.get('created_at') || '').toString().trim();
            var daJp = (fd.get('deleted_at') || '').toString().trim();
            if (caJp !== '' && !/^\d{2}\/\d{2}\/\d{4}$/.test(caJp) && !/^\d{4}-\d{2}-\d{2}$/.test(caJp)) { showErrors(f, { created_at: 'La data no és vàlida.' }); return; }
            if (daJp !== '' && !/^\d{2}\/\d{2}\/\d{4}$/.test(daJp) && !/^\d{4}-\d{2}-\d{2}$/.test(daJp)) { showErrors(f, { deleted_at: 'La data no és vàlida.' }); return; }
        }
        if (module() === 'parameters') {
            var cyP = (fd.get('id') || '').toString().trim();
            if (!/^\d{4}$/.test(cyP)) { showErrors(f, { id: 'L’any de catàleg ha de ser de 4 xifres.' }); return; }
            var pm = parseParametersMeiFraction((fd.get('mei_percentage') || '').toString());
            if (!pm.ok) { showErrors(f, { mei_percentage: pm.error }); return; }
            fd.set('mei_percentage', pm.value === null ? '' : String(pm.value));
        }
        if (module() === 'reports') {
            if ((fd.get('report_group') || '').toString().trim() === '') { showErrors(f, { report_group: 'El grup és obligatori.' }); return; }
            if ((fd.get('report_code') || '').toString().trim() === '') { showErrors(f, { report_code: 'El codi de l’informe és obligatori.' }); return; }
            if ((fd.get('report_name') || '').toString().trim() === '') { showErrors(f, { report_name: 'El nom és obligatori.' }); return; }
        }
        var payload={
            action:'save', module:module(), csrf_token:csrf,
            original_id:(fd.get('original_id')||'').toString(),
            id:(fd.get('id')||'').toString(),
            name:(module()==='management_positions' ? (fd.get('position_name')||'').toString() : (module()==='people' ? (fd.get('first_name')||'').toString() : (fd.get('name')||'').toString())),
            position_name:(fd.get('position_name')||'').toString(),
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
            ,position_class_id:(fd.get('position_class_id')||'').toString()
            ,category_id:(fd.get('category_id')||'').toString()
            ,labor_category:(fd.get('labor_category')||'').toString()
            ,classification_group:(fd.get('classification_group')||'').toString()
            ,access_type_id:(fd.get('access_type_id')||'').toString()
            ,access_system_id:(fd.get('access_system_id')||'').toString()
            ,budgeted_amount:(fd.get('budgeted_amount')||'').toString()
            ,is_offerable:(function(){ var el=document.querySelector('[data-field="is_offerable"]'); return el && el.checked ? 1 : 0; })()
            ,opo_year:(fd.get('opo_year')||'').toString()
            ,is_to_be_amortized:(function(){ var el=document.querySelector('[data-field="is_to_be_amortized"]'); return el && el.checked ? 1 : 0; })()
            ,is_internal_promotion:(function(){ var el=document.querySelector('[data-field="is_internal_promotion"]'); return el && el.checked ? 1 : 0; })()
            ,created_at:(fd.get('created_at')||'').toString()
            ,creation_file_reference:(fd.get('creation_file_reference')||'').toString()
            ,call_for_applications_date:(fd.get('call_for_applications_date')||'').toString()
            ,deleted_at:(fd.get('deleted_at')||'').toString()
            ,deletion_file_reference:(fd.get('deletion_file_reference')||'').toString()
            ,legacy_person_id:(fd.get('legacy_person_id')||'').toString()
            ,last_name_1:(fd.get('last_name_1')||'').toString()
            ,last_name_2:(fd.get('last_name_2')||'').toString()
            ,first_name:(fd.get('first_name')||'').toString()
            ,birth_date:(fd.get('birth_date')||'').toString()
            ,national_id_number:(fd.get('national_id_number')||'').toString()
            ,email:(fd.get('email')||'').toString()
            ,social_security_number:(fd.get('social_security_number')||'').toString()
            ,job_position_id:(fd.get('job_position_id')||'').toString()
            ,position_id:(fd.get('people_position_id')||'').toString()
            ,dedication:(fd.get('dedication')||'').toString()
            ,legal_relation_id:(fd.get('legal_relation_id')||'').toString()
            ,administrative_status_id:(fd.get('administrative_status_id')||'').toString()
            ,status_text:(fd.get('status_text')||'').toString()
            ,company_id:(fd.get('company_id')||'').toString()
            ,social_security_contribution_coefficient:(fd.get('social_security_contribution_coefficient')||'').toString()
            ,productivity_bonus:(fd.get('productivity_bonus')||'').toString()
            ,legacy_social_security:(fd.get('legacy_social_security')||'').toString()
            ,hired_at:(fd.get('hired_at')||'').toString()
            ,terminated_at:(fd.get('terminated_at')||'').toString()
            ,personal_grade:(fd.get('personal_grade')||'').toString()
            ,group_a1_previous_triennia:(fd.get('group_a1_previous_triennia')||'').toString()
            ,group_a1_current_year_percentage:(fd.get('group_a1_current_year_percentage')||'').toString()
            ,group_a1_current_year_triennia:(fd.get('group_a1_current_year_triennia')||'').toString()
            ,group_a2_previous_triennia:(fd.get('group_a2_previous_triennia')||'').toString()
            ,group_a2_current_year_percentage:(fd.get('group_a2_current_year_percentage')||'').toString()
            ,group_a2_current_year_triennia:(fd.get('group_a2_current_year_triennia')||'').toString()
            ,group_c1_previous_triennia:(fd.get('group_c1_previous_triennia')||'').toString()
            ,group_c1_current_year_percentage:(fd.get('group_c1_current_year_percentage')||'').toString()
            ,group_c1_current_year_triennia:(fd.get('group_c1_current_year_triennia')||'').toString()
            ,group_c2_previous_triennia:(fd.get('group_c2_previous_triennia')||'').toString()
            ,group_c2_current_year_percentage:(fd.get('group_c2_current_year_percentage')||'').toString()
            ,group_c2_current_year_triennia:(fd.get('group_c2_current_year_triennia')||'').toString()
            ,group_e_previous_triennia:(fd.get('group_e_previous_triennia')||'').toString()
            ,group_e_current_year_percentage:(fd.get('group_e_current_year_percentage')||'').toString()
            ,group_e_current_year_triennia:(fd.get('group_e_current_year_triennia')||'').toString()
            ,subprogram_people:peopleSubprogramGetRows()
            ,mei_percentage:(fd.get('mei_percentage')||'').toString()
            ,report_group:(fd.get('report_group')||'').toString()
            ,report_group_order:(fd.get('report_group_order')||'').toString()
            ,report_code:(fd.get('report_code')||'').toString()
            ,report_name:(fd.get('report_name')||'').toString()
            ,report_description:(fd.get('report_description')||'').toString()
            ,report_explanation:(fd.get('report_explanation')||'').toString()
            ,report_version:(fd.get('report_version')||'').toString()
            ,show_in_general_selector:(function(){ var el=f.querySelector('[data-field="show_in_general_selector"]'); return el && el.checked ? 1 : 0; })()
            ,is_active:(function(){ var el=f.querySelector('[data-field="is_active"]'); return el && el.checked ? 1 : 0; })()
        };
        if (module() === 'job_positions') {
            var specSave = normalizeMoneyInput((fd.get('special_specific_compensation_amount') || '').toString());
            payload.id = (fd.get('job_position_id') || '').toString();
            payload.job_position_id = payload.id;
            payload.name = (fd.get('job_title') || '').toString();
            payload.job_title = payload.name;
            payload.org_unit_level_3_id = (fd.get('org_unit_level_3_id') || '').toString();
            payload.job_number = (fd.get('job_number') || '').toString();
            payload.catalog_code = (fd.get('catalog_code') || '').toString();
            payload.org_dependency_id = (fd.get('org_dependency_id') || '').toString();
            payload.legal_relation_id = (fd.get('legal_relation_id') || '').toString();
            payload.civil_service_scale_id = (fd.get('civil_service_scale_id') || '').toString();
            payload.civil_service_subscale_id = (fd.get('civil_service_subscale_id') || '').toString();
            payload.civil_service_class_id = (fd.get('civil_service_class_id') || '').toString();
            payload.civil_service_category_id = (fd.get('civil_service_category_id') || '').toString();
            payload.labor_category = (fd.get('labor_category') || '').toString();
            payload.classification_group = (fd.get('classification_group') || '').toString();
            payload.classification_group_slash = (fd.get('classification_group_slash') || '').toString();
            payload.organic_level = (fd.get('organic_level') || '').toString();
            payload.classification_group_new = (fd.get('classification_group_new') || '').toString();
            payload.special_specific_compensation_id = (fd.get('special_specific_compensation_id') || '').toString();
            payload.general_specific_compensation_id = (fd.get('general_specific_compensation_id') || '').toString();
            payload.special_specific_compensation_amount = specSave || '';
            payload.job_type_id = (fd.get('job_type_id') || '').toString();
            payload.contribution_epigraph_id = (fd.get('contribution_epigraph_id') || '').toString();
            payload.contribution_group_id = (fd.get('contribution_group_id') || '').toString();
            payload.created_at = (fd.get('created_at') || '').toString();
            payload.creation_reason = (fd.get('creation_reason') || '').toString();
            payload.creation_file_reference = (fd.get('creation_file_reference') || '').toString();
            payload.deleted_at = (fd.get('deleted_at') || '').toString();
            payload.deletion_reason = (fd.get('deletion_reason') || '').toString();
            payload.deletion_file_reference = (fd.get('deletion_file_reference') || '').toString();
            payload.job_evaluation = (fd.get('job_evaluation') || '').toString();
            payload.is_to_be_amortized = (function () { var el = document.querySelector('#jp_is_to_be_amortized'); return el && el.checked ? 1 : 0; })();
            payload.workday_type = (fd.get('workday_type') || '').toString();
            payload.working_time_dedication = (fd.get('working_time_dedication') || '').toString();
            payload.schedule_text = (fd.get('schedule_text') || '').toString();
            payload.has_night_schedule = (function () { var el = document.querySelector('[data-field="has_night_schedule"]'); return el && el.checked ? 1 : 0; })();
            payload.has_holiday_schedule = (function () { var el = document.querySelector('[data-field="has_holiday_schedule"]'); return el && el.checked ? 1 : 0; })();
            payload.has_shift_schedule = (function () { var el = document.querySelector('[data-field="has_shift_schedule"]'); return el && el.checked ? 1 : 0; })();
            payload.has_special_dedication = (function () { var el = document.querySelector('[data-field="has_special_dedication"]'); return el && el.checked ? 1 : 0; })();
            payload.special_dedication_type = (fd.get('special_dedication_type') || '').toString();
            payload.availability_id = (fd.get('availability_id') || '').toString();
            payload.mission = (fd.get('mission') || '').toString();
            payload.generic_functions = (fd.get('generic_functions') || '').toString();
            payload.specific_functions = (fd.get('specific_functions') || '').toString();
            payload.qualification_requirements = (fd.get('qualification_requirements') || '').toString();
            payload.other_requirements = (fd.get('other_requirements') || '').toString();
            payload.training_requirements = (fd.get('training_requirements') || '').toString();
            payload.experience_requirements = (fd.get('experience_requirements') || '').toString();
            payload.other_merits = (fd.get('other_merits') || '').toString();
            payload.provision_method_id = (fd.get('provision_method_id') || '').toString();
            payload.effort = (fd.get('effort') || '').toString();
            payload.hardship = (fd.get('hardship') || '').toString();
            payload.danger = (fd.get('danger') || '').toString();
            payload.incompatibilities = (fd.get('incompatibilities') || '').toString();
            payload.provincial_notes = (fd.get('provincial_notes') || '').toString();
            payload.work_center_id = (fd.get('work_center_id') || '').toString();
            payload.notes = (fd.get('notes') || '').toString();
            payload.assigned_person_ids = jobPositionsAssignedGetRows().map(function (x) { return x.person_id; });
        }
        fetch(apiUrl(),{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},body:JSON.stringify(payload)})
            .then(function (r) {
                var st = r.status;
                return r.json().then(function (d) { return { status: st, d: d }; });
            })
            .then(function (x) {
                var d = x.d;
                if (d.ok) {
                    closeModal();
                    if (window.showAlert) {
                        window.showAlert('success', 'Èxit', d.message || 'Desat.');
                        setTimeout(function () { window.location.reload(); }, 650);
                    } else {
                        window.location.reload();
                    }
                } else if (d.errors) {
                    showMaintenanceSaveErrorResponse(f, d.errors, x.status);
                }
            })
            .catch(function () { if (window.showAlert) window.showAlert('error', 'Error', 'Error de xarxa.'); });
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
    function formatPtbEditableValue(raw) {
        var parsed = parsePtbClientInput(raw);
        if (parsed.kind === 'empty') return '';
        if (parsed.kind !== 'amount') return String(raw || '').trim();
        var n = parseFloat(parsed.norm);
        if (!isFinite(n)) return String(raw || '').trim();
        return new Intl.NumberFormat('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
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
                    var vd = d.value_display !== undefined && d.value_display !== null ? String(d.value_display) : '';
                    inp.value = vd;
                    inp.setAttribute('data-ptb-snapshot', formatPtbEditableValue(vd));
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
            var editVal = formatPtbEditableValue(t.value);
            t.value = editVal;
            t.setAttribute('data-ptb-snapshot', editVal);
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
