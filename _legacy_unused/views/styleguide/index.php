<?php
declare(strict_types=1);
?>
<div class="styleguide-intro">
    <h1>Guia visual</h1>
    <p class="muted styleguide-intro__meta">Referència de components. URL: <code>public/styleguide.php</code></p>
</div>

<!-- A. PAGE HEADER -->
<section class="styleguide-block" id="sg-page-header">
    <h2 class="styleguide-block__title">A. Page header</h2>
    <p class="styleguide-block__note">Capçalera amb tornar, escut (sense icona decorativa), subtítol i ajuda.</p>
    <?php
    $pageHeader = page_header_with_escut([
        'title' => 'Exemple de títol de pantalla',
        'subtitle' => 'Subtítol descriptiu del mòdul o acció que es fa en aquesta vista.',
        'back_url' => app_url('dashboard.php'),
        'back_label' => 'Tornar',
        'help_url' => '#sg-page-header',
        'help_label' => 'Ajuda',
    ]);
    require APP_ROOT . '/views/partials/page_header.php';
    ?>
</section>

<!-- B. ACTION BAR -->
<section class="styleguide-block" id="sg-action-bar">
    <h2 class="styleguide-block__title">B. Action bar</h2>
    <p class="styleguide-block__note">Variants de botons i mides.</p>
    <?php
    ob_start();
    ?>
    <div class="action-bar__group">
        <button type="button" class="btn btn--primary">Primary</button>
        <button type="button" class="btn btn--secondary">Secondary</button>
        <button type="button" class="btn btn--danger">Danger</button>
        <button type="button" class="btn btn--ghost">Ghost</button>
        <button type="button" class="btn btn--outline">Outline</button>
    </div>
    <div class="action-bar__group styleguide-btn-row">
        <button type="button" class="btn btn--primary btn--sm">Primary sm</button>
        <button type="button" class="btn btn--secondary btn--sm">Secondary sm</button>
        <button type="button" class="btn btn--danger btn--sm">Danger sm</button>
        <button type="button" class="btn btn--ghost btn--sm">Ghost sm</button>
        <button type="button" class="btn btn--outline btn--sm">Outline sm</button>
    </div>
    <?php
    $actionBarInner = ob_get_clean();
    require APP_ROOT . '/views/partials/action_bar.php';
    ?>
</section>

<!-- C. FILTER CARD -->
<section class="styleguide-block" id="sg-filters">
    <h2 class="styleguide-block__title">C. Filter card</h2>
    <p class="styleguide-block__note">Primer bloc obert per defecte; el segon està plegat (obrir per veure camps).</p>
    <?php
    $filterSummaryLabel = 'Filtres (obert)';
    $filterExpanded = true;
    $filterCardInner = '
        <div class="filter-bar__field">
            <label class="form-label" for="sg_f1">Text</label>
            <input class="form-input" type="text" id="sg_f1" name="sg_f1" placeholder="Cerca…">
        </div>
        <div class="filter-bar__field">
            <label class="form-label" for="sg_f2">Select</label>
            <select class="form-select" id="sg_f2" name="sg_f2">
                <option value="">Tots</option>
                <option value="a">Opció A</option>
                <option value="b">Opció B</option>
            </select>
        </div>
        <div class="filter-bar__field">
            <label class="form-label" for="sg_f3">Data</label>
            <input class="form-input" type="date" id="sg_f3" name="sg_f3">
        </div>';
    require APP_ROOT . '/views/partials/filter_card.php';

    $filterSummaryLabel = 'Més filtres (plegat)';
    $filterExpanded = false;
    $filterCardInner = '
        <div class="filter-bar__field">
            <label class="form-label" for="sg_f4">Departament</label>
            <select class="form-select" id="sg_f4" name="sg_f4">
                <option value="">Qualsevol</option>
                <option value="1">RRHH</option>
            </select>
        </div>
        <div class="filter-bar__field">
            <label class="form-label" for="sg_f5">Estat</label>
            <select class="form-select" id="sg_f5" name="sg_f5">
                <option value="">Tots</option>
                <option value="1">Actiu</option>
            </select>
        </div>';
    require APP_ROOT . '/views/partials/filter_card.php';
    ?>
</section>

<!-- D. FORM CARD -->
<section class="styleguide-block" id="sg-form">
    <h2 class="styleguide-block__title">D. Form card</h2>
    <p class="styleguide-block__note">Formulari d’exemple (no envia dades). Inclou error simulat i checkbox.</p>
    <div class="form-card">
        <div class="form-card__header">
            <h3 class="form-card__title">Alta / edició</h3>
            <p class="form-card__desc">Descripció opcional del formulari.</p>
        </div>
        <div class="form-card__body">
            <form class="form-grid" method="get" action="#sg-form">
                <div class="form-group">
                    <label class="form-label" for="sg_name">Nom</label>
                    <input class="form-input" id="sg_name" name="name" type="text" value="Valor d’exemple">
                </div>
                <div class="form-group">
                    <label class="form-label" for="sg_email">Correu</label>
                    <input class="form-input" id="sg_email" name="email" type="email" placeholder="correu@exemple.cat">
                    <p class="form-error">Aquest camp és obligatori (exemple d’error).</p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="sg_area">Observacions</label>
                    <textarea class="form-input" id="sg_area" name="area" rows="3" placeholder="Textarea…"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="sg_sel">Categoria</label>
                    <select class="form-select" id="sg_sel" name="sel">
                        <option value="">Trieu…</option>
                        <option value="1">Una</option>
                        <option value="2">Dos</option>
                    </select>
                </div>
                <div class="form-group form-grid__full">
                    <div class="form-check">
                        <input class="form-check__input" type="checkbox" id="sg_chk" name="chk" checked>
                        <label class="form-check__label" for="sg_chk">Accepto les condicions (checkbox)</label>
                    </div>
                </div>
            </form>
        </div>
        <div class="form-card__footer">
            <button type="button" class="btn btn--secondary">Cancel·lar</button>
            <button type="button" class="btn btn--danger">Eliminar</button>
            <button type="button" class="btn btn--primary">Guardar</button>
        </div>
    </div>
</section>

<!-- E. DATA TABLE -->
<section class="styleguide-block" id="sg-table">
    <h2 class="styleguide-block__title">E. Data table</h2>
    <?php
    $dataTableCaption = 'Taula de demostració';
    $dataTableToolbar = '3 registres';
    ob_start();
    ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Codi</th>
                <th>Nom</th>
                <th>Estat</th>
                <th class="data-table__actions">Accions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>001</strong></td>
                <td>Exemple actiu</td>
                <td><span class="badge badge--success">Actiu</span></td>
                <td class="data-table__actions">
                    <button type="button" class="btn btn--secondary btn--sm">Editar</button>
                </td>
            </tr>
            <tr>
                <td><strong>002</strong></td>
                <td>Exemple pendent</td>
                <td><span class="badge badge--warning">Pendent</span></td>
                <td class="data-table__actions">
                    <button type="button" class="btn btn--outline btn--sm">Veure</button>
                </td>
            </tr>
            <tr>
                <td><strong>003</strong></td>
                <td>Exemple inactiu</td>
                <td><span class="badge badge--neutral">Inactiu</span></td>
                <td class="data-table__actions">
                    <button type="button" class="btn btn--danger btn--sm">Eliminar</button>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
    $dataTableInner = ob_get_clean();
    require APP_ROOT . '/views/partials/data_table.php';
    ?>
</section>

<!-- F. BADGES -->
<section class="styleguide-block" id="sg-badges">
    <h2 class="styleguide-block__title">F. Badges / estats</h2>
    <p class="styleguide-block__note">
        <span class="badge badge--success">success</span>
        <span class="badge badge--warning">warning</span>
        <span class="badge badge--danger">danger</span>
        <span class="badge badge--neutral">neutral</span>
        <span class="badge badge--info">info</span>
    </p>
</section>

<!-- G. ALERTES INLINE -->
<section class="styleguide-block" id="sg-alerts">
    <h2 class="styleguide-block__title">G. Alertes inline</h2>
    <div class="alert alert--success" role="status">Missatge d’èxit (inline, no modal).</div>
    <div class="alert alert--error" role="alert">Missatge d’error.</div>
    <div class="alert alert--warning" role="status">Advertència.</div>
    <div class="alert alert--info" role="status">Informació.</div>
</section>

<!-- H. MODALS (barra d’estat + caixa blava + icona + Si/No) -->
<section class="styleguide-block" id="sg-modals">
    <h2 class="styleguide-block__title">H. Modals</h2>
    <p class="styleguide-block__note">
        Disseny únic: barra superior (text d’estat, personalitzable), cos fosc amb missatge en caixa blava i icona circular segons el tipus (pregunta, èxit, error, avís, info).
        Confirmació amb <strong>Si</strong> / <strong>No</strong> (enllaç amb <code>showConfirm</code> a <code>app.js</code>).
    </p>
    <div class="action-bar card">
        <div class="action-bar__inner">
            <button type="button" class="btn btn--secondary btn--sm" data-sg-alert="info" data-sg-title="Informació" data-sg-msg="Contingut del modal informatiu.">Info</button>
            <button type="button" class="btn btn--secondary btn--sm" data-sg-alert="success" data-sg-title="Èxit" data-sg-msg="Operació completada correctament.">Èxit</button>
            <button type="button" class="btn btn--secondary btn--sm" data-sg-alert="error" data-sg-title="Error crític" data-sg-msg="No s’ha pogut completar l’operació. S’ha produït un error greu.">Error / crític</button>
            <button type="button" class="btn btn--secondary btn--sm" data-sg-alert="warning" data-sg-title="Avís" data-sg-msg="Revisa les dades abans de continuar.">Avís</button>
            <button type="button" class="btn btn--primary btn--sm" data-sg-confirm data-sg-title="Registre actiu" data-sg-msg="Desitja eliminar el registre ?">Pregunta (Si / No)</button>
        </div>
    </div>
</section>

<!-- I. JS API -->
<section class="styleguide-block" id="sg-js">
    <h2 class="styleguide-block__title">I. Alertes i confirmacions (JS)</h2>
    <p class="styleguide-block__note"><code>showAlert</code> i <code>showConfirm</code> (veure <code>assets/js/app.js</code>).</p>
    <div class="action-bar card">
        <div class="action-bar__inner">
            <button type="button" class="btn btn--outline btn--sm" data-sg-alert="success" data-sg-msg="showAlert amb dos paràmetres (cos sol).">showAlert(success, msg)</button>
            <button type="button" class="btn btn--outline btn--sm" data-sg-alert="error" data-sg-title="Títol" data-sg-msg="Cos del missatge amb títol explícit.">showAlert(error, title, msg)</button>
            <button type="button" class="btn btn--outline btn--sm" data-sg-confirm data-sg-title="Pregunta" data-sg-msg="Confirma aquesta acció de prova?">showConfirm</button>
        </div>
    </div>
</section>

<!-- J. Referència visual «Persones operatives» (només guia, sense persistència) -->
<section class="styleguide-block" id="sg-module-users">
    <h2 class="styleguide-block__title">J. Referència visual «Persones operatives»</h2>
    <p class="styleguide-block__note">
        Mostra inspirada en la referència (taronja, filtres crema, taula compacta, formulari d’alta / modificació).
        Els botons «Anar a planificació» i «Veure càrrecs» comparteixen estil amb «Nova». A la barra «Vista» es commuta entre alta i modificació (mateix formulari).
        Col·loca <code>Escut_alta.png</code> a <code>assets/img/logos/</code> per veure l’escut.
    </p>

    <div class="module-users">
        <?php
        $pageHeader = page_header_with_escut([
            'title' => 'Persones operatives',
            'subtitle' => 'Gestiona el catàleg operatiu de tècnics i perfils de l’equip',
            'back_url' => '#sg-module-users',
            'back_label' => 'Tornar',
            'back_variant' => 'accent',
            'help_url' => '#sg-module-users',
            'help_label' => 'Ajuda',
            'help_class' => 'page-header__help--icon-only',
        ]);
        require APP_ROOT . '/views/partials/page_header.php';

        ob_start();
        ?>
        <div class="action-bar__group">
            <button type="button" class="btn btn--outline btn--module-accent-outline">
                <?= ui_icon('plus') ?>
                Nova
            </button>
        </div>
        <div class="action-bar__spacer" aria-hidden="true"></div>
        <div class="action-bar__group">
            <button type="button" class="btn btn--outline btn--module-accent-outline">Anar a planificació</button>
            <button type="button" class="btn btn--outline btn--module-accent-outline">Veure càrrecs</button>
        </div>
        <?php
        $actionBarInner = ob_get_clean();
        require APP_ROOT . '/views/partials/action_bar.php';
        ?>

        <div class="users-filter-stack">
            <?php
            $filterSummaryLabel = 'Filtres de cerca';
            $filterExpanded = true;
            $filterShowClear = false;
            $filterCardInner = '
        <div class="filter-bar__field">
            <label class="form-label" for="pv_q">Cercar</label>
            <input class="form-input" type="search" id="pv_q" name="pv_q" placeholder="Nom, correu…" autocomplete="off">
        </div>
        <div class="filter-bar__field">
            <label class="form-label" for="pv_cargo">Càrrec</label>
            <select class="form-select" id="pv_cargo" name="pv_cargo">
                <option value="">Tots</option>
                <option value="1">Tècnic</option>
                <option value="2">Coordinador</option>
            </select>
        </div>
        <div class="filter-bar__field">
            <label class="form-label" for="pv_pais">País</label>
            <select class="form-select" id="pv_pais" name="pv_pais">
                <option value="">Tots</option>
                <option value="es">Espanya</option>
                <option value="ad">Andorra</option>
            </select>
        </div>
        <div class="filter-bar__field">
            <label class="form-label" for="pv_cal">Calendari</label>
            <input class="form-input" type="month" id="pv_cal" name="pv_cal" value="2026-03">
        </div>';
            require APP_ROOT . '/views/partials/filter_card.php';
            ?>
            <div class="users-filter-stack__footer">
                <div class="users-toolbar-left">
                    <span aria-hidden="true">✓</span>
                    <span>7 registres trobats</span>
                </div>
                <label class="users-switch">
                    <input type="checkbox" name="pv_inactius" value="1">
                    <span>Mostrar inactius</span>
                </label>
                <button type="button" class="btn btn--export btn--sm btn--module-export">
                    <?= ui_icon('download') ?>
                    Exportar Excel
                </button>
            </div>
        </div>

        <?php
        $dataTableCaption = 'Taula de demostració';
        $dataTableToolbar = null;
        ob_start();
        ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Cognoms <span class="data-table__sort" aria-hidden="true">↕</span></th>
                    <th>Nom <span class="data-table__sort" aria-hidden="true">↕</span></th>
                    <th>Càrrec <span class="data-table__sort" aria-hidden="true">↕</span></th>
                    <th>Estat</th>
                    <th class="data-table__actions">Accions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>García</strong></td>
                    <td>Ana</td>
                    <td>Tècnic</td>
                    <td><span class="badge badge--success badge--dot-success">Actiu</span></td>
                    <td class="data-table__actions">
                        <button type="button" class="btn btn--sm btn--icon-edit" title="Editar"><?= ui_icon('pencil-square') ?></button>
                        <button type="button" class="btn btn--sm btn--icon-del" title="Eliminar"><?= ui_icon('trash') ?></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Martí</strong></td>
                    <td>Joan</td>
                    <td>Coordinador</td>
                    <td><span class="badge badge--success badge--dot-success">Actiu</span></td>
                    <td class="data-table__actions">
                        <button type="button" class="btn btn--sm btn--icon-edit" title="Editar"><?= ui_icon('pencil-square') ?></button>
                        <button type="button" class="btn btn--sm btn--icon-del" title="Eliminar"><?= ui_icon('trash') ?></button>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
        $dataTableInner = ob_get_clean();
        require APP_ROOT . '/views/partials/data_table.php';
        ?>

        <div class="users-modal-form" id="styleguide-users-modal-shell" role="region" aria-label="Formulari de mostra (guia visual)" data-styleguide-users-modal data-mode="create">
            <div class="users-modal-form__mode-bar" role="group" aria-label="Mode de demostració (només guia visual)">
                <span class="users-modal-form__mode-bar-label">Vista:</span>
                <button type="button" class="btn btn--secondary btn--sm is-active" data-modal-mode="create" aria-pressed="true">Alta nova</button>
                <button type="button" class="btn btn--secondary btn--sm" data-modal-mode="edit" aria-pressed="false">Modificació</button>
            </div>
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start">
                    <span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('pencil-square') ?></span>
                    <div>
                        <h2 class="users-modal-form__title" data-modal-title>Nou registre</h2>
                        <p class="users-modal-form__subtitle" data-modal-subtitle>Actualitza la informació</p>
                    </div>
                </div>
                <div class="users-modal-form__header-actions">
                    <button type="button" class="btn btn--ghost btn--sm">Cancel·lar</button>
                    <button type="button" class="btn btn--primary btn--sm" data-modal-submit>Crear</button>
                </div>
            </div>
            <form class="users-modal-form__body form-grid form-grid--modal" method="get" action="#sg-module-users" id="pv_modal_demo">
                <div class="form-group form-grid__full">
                    <label class="form-label" for="pv_estat">Estat <span class="users-modal-form__req" aria-hidden="true">*</span></label>
                    <select class="form-select" id="pv_estat" name="pv_estat">
                        <option value="1" selected>Actiu</option>
                        <option value="0">Inactiu</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="pv_ref">Referència <span class="users-modal-form__req" aria-hidden="true">*</span></label>
                    <input class="form-input" id="pv_ref" name="pv_ref" type="text" placeholder="Introdueix referència…" autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="form-label" for="pv_nom_m">Nom <span class="users-modal-form__req" aria-hidden="true">*</span></label>
                    <input class="form-input" id="pv_nom_m" name="pv_nom_m" type="text" placeholder="Introdueix nom…" autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="form-label" for="pv_fam">Família <span class="users-modal-form__req" aria-hidden="true">*</span></label>
                    <select class="form-select" id="pv_fam" name="pv_fam">
                        <option value="">Seleccionar…</option>
                        <option value="a">Família A</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="pv_prov">Proveïdor <span class="users-modal-form__req" aria-hidden="true">*</span></label>
                    <select class="form-select" id="pv_prov" name="pv_prov">
                        <option value="">Seleccionar…</option>
                        <option value="1">Proveïdor 1</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="pv_preu">Preu (sense IVA)</label>
                    <input class="form-input" id="pv_preu" name="pv_preu" type="text" inputmode="decimal" placeholder="Introdueix preu (sense IVA)…" autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="form-label" for="pv_marca">Marca</label>
                    <select class="form-select" id="pv_marca" name="pv_marca">
                        <option value="">Seleccionar…</option>
                        <option value="m">Marca X</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="pv_model">Model</label>
                    <input class="form-input" id="pv_model" name="pv_model" type="text" placeholder="Introdueix model…" autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="form-label" for="pv_web">Enllaç web</label>
                    <input class="form-input" id="pv_web" name="pv_web" type="url" placeholder="Introdueix enllaç web…" autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="form-label" for="pv_fitxer">Imatge</label>
                    <input class="visually-hidden" type="file" id="pv_fitxer" name="pv_fitxer" tabindex="-1" accept="image/*">
                    <label for="pv_fitxer" class="users-modal-form__file-btn">Seleccionar arxiu</label>
                    <div class="users-modal-form__drop">Sense imatge seleccionada</div>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="pv_desc">Descripció</label>
                    <textarea class="form-input" id="pv_desc" name="pv_desc" rows="3" placeholder="Descripció opcional"></textarea>
                </div>
            </form>
        </div>
    </div>
</section>
