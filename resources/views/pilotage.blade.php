<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>Pilotage ANBG - CIQ</title>
    <style>
        :root {
            --ink: #102230;
            --soft: #4f6372;
            --line: #d2dce4;
            --bg: #f3f7fa;
            --panel: #ffffff;
            --primary: #005e8a;
            --ok: #2e8b57;
            --warn: #ca8a04;
            --late: #b91c1c;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: radial-gradient(circle at 20% 0, #e3eef8, #f6fafc 40%, #ecf3f8);
            color: var(--ink);
            font-family: "Segoe UI", "Trebuchet MS", sans-serif;
        }
        .topbar { background:#2596be; color:#fff; padding:10px 16px; display:flex; align-items:center; gap:12px; }
        .topbar img { height:38px; width:auto; display:block; }
        .wrap {
            max-width: 1320px;
            margin: 0 auto;
            padding: 20px;
        }
        .hero {
            background: linear-gradient(110deg, #0e4d73, #0a6d90);
            color: #fff;
            border-radius: 14px;
            padding: 18px 22px;
            box-shadow: 0 10px 30px #08334d30;
        }
        .hero h1 { margin: 0 0 8px; font-size: 24px; }
        .hero p { margin: 0; opacity: 0.92; }
        .filters {
            margin-top: 12px;
            display: grid;
            grid-template-columns: repeat(6, minmax(140px, 1fr));
            gap: 8px;
            align-items: end;
        }
        .filters input, .filters select, .filters button {
            width: 100%;
            border: 1px solid #c7d6e2;
            border-radius: 8px;
            padding: 8px;
            font: inherit;
        }
        .filters button {
            border: 0;
            background: #fff;
            color: #0b5f87;
            font-weight: 600;
            cursor: pointer;
        }
        .filters .link-reset {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border: 1px solid #c7d6e2;
            border-radius: 8px;
            padding: 8px;
            background: #ffffff22;
            color: #fff;
            font-size: 14px;
        }
        .exports {
            margin-top: 10px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .export-btn {
            border: 1px solid #c7d6e2;
            background: #ffffff;
            color: #0b5f87;
            border-radius: 8px;
            padding: 8px 10px;
            font: inherit;
            font-size: 13px;
            cursor: pointer;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }
        .card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 14px;
        }
        .kpi-title { color: var(--soft); font-size: 13px; }
        .kpi-value { font-size: 30px; font-weight: 700; margin-top: 4px; }
        .section {
            margin-top: 16px;
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 14px;
        }
        .section h2 { margin: 0 0 12px; font-size: 18px; }
        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }
        .section-head h2 { margin: 0; }
        .table-wrap { overflow: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 780px;
        }
        th, td {
            border-bottom: 1px solid var(--line);
            padding: 8px;
            text-align: left;
            font-size: 14px;
            vertical-align: top;
        }
        th { color: var(--soft); font-weight: 600; }
        .badge {
            display: inline-block;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
            color: #fff;
        }
        .b-ok { background: var(--ok); }
        .b-warn { background: var(--warn); }
        .b-late { background: var(--late); }
        .cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .muted { color: var(--soft); font-size: 13px; }
        .error { color: var(--late); font-weight: 600; }
        .annexe2-info-head th { background: #ead2d2; color: #2b1a1a; }
        .annexe2-rec-head th { background: #c8d6a8; color: #253013; }
        .annexe2-total-info td { background: #f4ece0; font-weight: 700; }
        .annexe2-total-rec td { background: #f0f54a; font-weight: 700; }
        .trace-list { margin: 0; padding-left: 16px; }
        .trace-list li { margin-bottom: 6px; }
        .trace-action { font-weight: 600; }
        .chart-card {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px;
            background: #fbfdff;
        }
        .bar-wrap, .pie-wrap {
            height: 320px;
            min-height: 320px;
            position: relative;
        }
        .bar-wrap canvas, .pie-wrap canvas {
            width: 100% !important;
            height: 100% !important;
            display: block;
        }
        @media (max-width: 980px) {
            .cols { grid-template-columns: 1fr; }
            .filters { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="topbar">
    <img src="/Logo_anbg.png" alt="ANBG">
    <strong>ANBG - Pilotage</strong>
</div>
<div class="wrap">
    <div class="hero">
        <h1>ANBG - Pilotage CIQ</h1>
        <p>Tableaux de bord et reporting multi-acteurs (accueil, chefs de direction, chefs de service, CIQ)</p>
        <p><a href="/admin" style="color:#fff;">Administration</a> | <a href="/espace" style="color:#fff;">Mon espace</a></p>
        <form method="post" action="/logout" style="margin-top:8px;">@csrf <button style="width:auto;background:#fff;color:#0b5f87;border:0;border-radius:8px;padding:6px 10px;">Deconnexion</button></form>

        <form method="get" class="filters">
            <select id="periode" name="periode">
                <option value="all">Toute periode</option>
                <option value="today">Aujourd hui</option>
                <option value="week">Cette semaine</option>
                <option value="month">Ce mois</option>
                <option value="quarter">Ce trimestre</option>
                <option value="year">Cette annee</option>
                <option value="custom">Personnalisee</option>
            </select>
            <input type="date" id="date_from" name="date_from" title="Date debut">
            <input type="date" id="date_to" name="date_to" title="Date fin">
            <select id="direction_id" name="direction_id"><option value="">Toutes directions</option></select>
            <select id="statut_code" name="statut_code"><option value="">Tous statuts</option></select>
            <select id="type_code" name="type_code"><option value="">Tous types</option></select>
            <button type="submit">Appliquer</button>
            <a class="link-reset" id="resetFilters" href="/pilotage">Reinitialiser</a>
        </form>

        <p class="muted" id="generatedAt">Chargement...</p>
        <div class="exports">
            <button type="button" class="export-btn" data-export="global">Exporter tout en PDF</button>
            <button type="button" class="export-btn" data-export="performance-section">Exporter performance directions (PDF)</button>
            <button type="button" class="export-btn" data-export="service-kpi-section">Exporter retards services (PDF)</button>
            <button type="button" class="export-btn" data-export="agent-kpi-section">Exporter indicateurs agents (PDF)</button>
            <button type="button" class="export-btn" data-export="annexe-section">Exporter tableau annexe (PDF)</button>
            <button type="button" class="export-btn" data-export="annexe2-section">Exporter annexe repartition (PDF)</button>
            <button type="button" class="export-btn" data-export="trace-global-section">Exporter tracabilite globale (PDF)</button>
            <button type="button" class="export-btn" data-export="usager-history-section">Exporter historique usagers (PDF)</button>
            <button type="button" class="export-btn" data-export="actions-section">Exporter actions recentes (PDF)</button>
        </div>
    </div>

    <div class="grid" id="kpis"></div>

    <section class="section" id="performance-section">
        <div class="section-head">
            <h2>Indicateurs par direction (performance)</h2>
            <button type="button" class="export-btn" data-export="performance-section">PDF</button>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Rang</th>
                    <th>Direction</th>
                    <th>Total demandes</th>
                    <th>Taux reponse dans delais (%)</th>
                    <th>Delai moyen (h)</th>
                    <th>Retards chef</th>
                    <th>Retards agent</th>
                    <th>Total retards</th>
                </tr>
                </thead>
                <tbody id="directionPerfBody">
                <tr><td colspan="8" class="muted">Chargement...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="section" id="service-kpi-section">
        <div class="section-head">
            <h2>Indicateurs par service (retards)</h2>
            <button type="button" class="export-btn" data-export="service-kpi-section">PDF</button>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Direction</th>
                    <th>Service</th>
                    <th>Total demandes</th>
                    <th>Retards chef</th>
                    <th>Retards agent</th>
                    <th>Total retards</th>
                </tr>
                </thead>
                <tbody id="serviceKpiBody">
                <tr><td colspan="6" class="muted">Chargement...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="section" id="agent-kpi-section">
        <div class="section-head">
            <h2>Indicateurs par agent</h2>
            <button type="button" class="export-btn" data-export="agent-kpi-section">PDF</button>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Agent</th>
                    <th>Service</th>
                    <th>Total demandes</th>
                    <th>Vert</th>
                    <th>Orange</th>
                    <th>Rouge</th>
                </tr>
                </thead>
                <tbody id="agentKpiBody">
                <tr><td colspan="6" class="muted">Chargement...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <div class="cols">
        <section class="section">
            <h2>Repartition par statut</h2>
            <div class="chart-card">
                <div class="muted" style="margin-bottom:6px;">Histogramme par statut</div>
                <div class="bar-wrap"><canvas id="statusBarChart" aria-label="Histogramme repartition par statut"></canvas></div>
                <div id="statusBarEmpty" class="muted">Chargement...</div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Statut</th><th>Total</th></tr></thead>
                    <tbody id="statusBody"><tr><td colspan="2" class="muted">Chargement...</td></tr></tbody>
                </table>
            </div>
        </section>
        <section class="section">
            <h2>Repartition par type</h2>
            <div class="chart-card">
                <div class="muted" style="margin-bottom:6px;">Vue graphique (mise a jour automatique dans le temps)</div>
                <div class="pie-wrap"><canvas id="typePieChart" aria-label="Camembert repartition par type"></canvas></div>
                <div id="typePieEmpty" class="muted">Chargement...</div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Type</th><th>Total</th></tr></thead>
                    <tbody id="typeBody"><tr><td colspan="2" class="muted">Chargement...</td></tr></tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="section" id="annexe-section">
        <div class="section-head">
            <h2> tableau actuel du suivi des reclamations</h2>
            <button type="button" class="export-btn" data-export="annexe-section">PDF</button>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>No</th>
                    <th>Date de reception</th>
                    <th>Expediteur</th>
                    <th>Objet</th>
                    <th>Date de dispatching</th>
                    <th>Delais transmission (O/H)</th>
                    <th>Service/Direction</th>
                    <th>Realisation</th>
                    <th>Statut traitement</th>
                    <th>Respect delais</th>
                    <th>Nombre de jours d attente</th>
                    <th>Q/C/S</th>
                </tr>
                </thead>
                <tbody id="annexeBody"><tr><td colspan="12" class="muted">Chargement...</td></tr></tbody>
            </table>
        </div>
    </section>

    <section class="section" id="annexe2-section">
        <div class="section-head">
            <h2> repartition des demandes les plus recurrentes</h2>
            <button type="button" class="export-btn" data-export="annexe2-section">PDF</button>
        </div>
        <div class="table-wrap">
            <table>
                <thead class="annexe2-info-head">
                <tr>
                    <th>Demande d informations (eBourse, bourses et accessoires)</th>
                    <th>Nombre de mails</th>
                    <th>%</th>
                </tr>
                </thead>
                <tbody id="annexe2InfoBody"><tr><td colspan="3" class="muted">Chargement...</td></tr></tbody>
                <tfoot>
                <tr class="annexe2-total-info">
                    <td>TOTAL DEMANDE D INFORMATIONS</td>
                    <td id="annexe2InfoTotal">0</td>
                    <td>100%</td>
                </tr>
                </tfoot>
            </table>
        </div>
        <div class="table-wrap" style="margin-top:12px;">
            <table>
                <thead class="annexe2-rec-head">
                <tr>
                    <th>RECLAMATIONS</th>
                    <th>Nombre de mails</th>
                    <th>%</th>
                </tr>
                </thead>
                <tbody id="annexe2RecBody"><tr><td colspan="3" class="muted">Chargement...</td></tr></tbody>
                <tfoot>
                <tr class="annexe2-total-rec">
                    <td>TOTAL RECLAMATIONS</td>
                    <td id="annexe2RecTotal">0</td>
                    <td>100%</td>
                </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <div class="cols">
        <section class="section">
            <h2>Usagers les plus actifs</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Usager</th>
                        <th>Email</th>
                        <th>Total</th>
                        <th>Reclamations</th>
                        <th>Taux reclamation (%)</th>
                    </tr>
                    </thead>
                    <tbody id="usagerTopBody"><tr><td colspan="5" class="muted">Chargement...</td></tr></tbody>
                </table>
            </div>
        </section>
        <section class="section">
            <h2>Demandes en cours (délai)</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>No suivi</th>
                        <th>Usager</th>
                        <th>Objet</th>
                        <th>Service</th>
                        <th>Statut</th>
                        <th>Heures ouvrees</th>
                        <th>Alerte accueil</th>
                        <th>Alerte chef</th>
                        <th>Alerte agent</th>
                        <th>Alerte globale</th>
                    </tr>
                    </thead>
                    <tbody id="demandsBody"><tr><td colspan="10" class="muted">Chargement...</td></tr></tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="section" id="trace-global-section">
        <div class="section-head">
            <h2>CIQ - tracabilite globale (soumission a reponse finale)</h2>
            <button type="button" class="export-btn" data-export="trace-global-section">PDF</button>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>No suivi</th>
                    <th>Usager</th>
                    <th>Type</th>
                    <th>Statut courant</th>
                    <th>Direction / Service</th>
                    <th>Objet</th>
                    <th>Timeline des actions (avec agent)</th>
                </tr>
                </thead>
                <tbody id="traceGlobalBody"><tr><td colspan="7" class="muted">Chargement...</td></tr></tbody>
            </table>
        </div>
    </section>

    <section class="section" id="usager-history-section">
        <div class="section-head">
            <h2>Historique complet par usager</h2>
            <button type="button" class="export-btn" data-export="usager-history-section">PDF</button>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Date</th>
                    <th>No suivi</th>
                    <th>Usager</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Statut</th>
                    <th>Objet</th>
                </tr>
                </thead>
                <tbody id="usagerHistoryBody"><tr><td colspan="7" class="muted">Chargement...</td></tr></tbody>
            </table>
        </div>
    </section>

    <section class="section" id="actions-section">
        <div class="section-head">
            <h2>Tracabilite - actions recentes</h2>
            <button type="button" class="export-btn" data-export="actions-section">PDF</button>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Date action</th>
                    <th>No suivi</th>
                    <th>Type action</th>
                    <th>Acteur</th>
                    <th>Direction</th>
                    <th>Service</th>
                    <th>Commentaire</th>
                </tr>
                </thead>
                <tbody id="actionsBody"><tr><td colspan="7" class="muted">Chargement...</td></tr></tbody>
            </table>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const params = new URLSearchParams(window.location.search);

    const setInputFromParams = () => {
        document.getElementById('periode').value = params.get('periode') || 'month';
        document.getElementById('date_from').value = params.get('date_from') || '';
        document.getElementById('date_to').value = params.get('date_to') || '';
    };

    const fillSelect = (id, rows, selectedValue, valueKey, labelBuilder) => {
        const select = document.getElementById(id);
        const first = select.options[0];
        select.innerHTML = '';
        select.appendChild(first);
        rows.forEach((row) => {
            const option = document.createElement('option');
            option.value = row[valueKey];
            option.textContent = labelBuilder(row);
            if (String(option.value) === String(selectedValue || '')) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    };

    const alertBadge = (value) => {
        if (value === 'rouge' || value === 'en_retard') return '<span class="badge b-late">En retard</span>';
        if (value === 'orange' || value === 'a_risque') return '<span class="badge b-warn">A risque</span>';
        return '<span class="badge b-ok">Dans les delais</span>';
    };

    const chartPalette = ['#0a6d90', '#1f7a5a', '#d97706', '#b91c1c', '#6b21a8', '#0f766e', '#1d4ed8', '#9a3412'];
    let statusBarChart = null;
    let typePieChart = null;

    const renderRows = (targetId, rows, emptyCols, renderFn) => {
        const target = document.getElementById(targetId);
        if (!rows || rows.length === 0) {
            target.innerHTML = `<tr><td colspan="${emptyCols}" class="muted">Aucune donnee.</td></tr>`;
            return;
        }
        target.innerHTML = rows.map(renderFn).join('');
    };

    const formatDateTime = (value) => {
        if (!value) return '-';
        const parsed = new Date(value);
        if (Number.isNaN(parsed.getTime())) return '-';
        return parsed.toLocaleDateString('fr-FR');
    };

    const formatDateTimeFull = (value) => {
        if (!value) return '-';
        const parsed = new Date(value);
        if (Number.isNaN(parsed.getTime())) return '-';
        return parsed.toLocaleString('fr-FR');
    };

    const renderStatusBar = (statuts) => {
        const canvas = document.getElementById('statusBarChart');
        const empty = document.getElementById('statusBarEmpty');
        if (!canvas || !empty) return;

        if (statusBarChart) {
            statusBarChart.destroy();
            statusBarChart = null;
        }

        if (!statuts || statuts.length === 0) {
            canvas.style.display = 'none';
            empty.style.display = 'block';
            empty.textContent = 'Aucune donnee.';
            return;
        }

        if (typeof Chart === 'undefined') {
            canvas.style.display = 'none';
            empty.style.display = 'block';
            empty.textContent = 'Chart.js indisponible.';
            return;
        }

        const labels = statuts.map((row) => row.libelle);
        const values = statuts.map((row) => Number(row.total) || 0);
        const total = values.reduce((sum, value) => sum + value, 0);

        if (total <= 0) {
            canvas.style.display = 'none';
            empty.style.display = 'block';
            empty.textContent = 'Aucune donnee.';
            return;
        }

        canvas.style.display = 'block';
        empty.style.display = 'none';
        try {
            statusBarChart = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Nombre de demandes',
                        data: values,
                        backgroundColor: labels.map((_, idx) => chartPalette[idx % chartPalette.length]),
                        borderRadius: 6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => ` ${context.parsed.y} demande(s)`,
                            },
                        },
                    },
                    scales: {
                        x: {
                            ticks: { color: '#4f6372', maxRotation: 30, minRotation: 0 },
                            grid: { display: false },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, color: '#4f6372' },
                            grid: { color: '#e2e8f0' },
                        },
                    },
                },
            });
        } catch (error) {
            canvas.style.display = 'none';
            empty.style.display = 'block';
            empty.textContent = `Erreur graphique statut: ${error?.message || 'inconnue'}`;
        }
    };

    const renderTypePie = (types) => {
        const canvas = document.getElementById('typePieChart');
        const empty = document.getElementById('typePieEmpty');
        if (!canvas || !empty) {
            return;
        }

        if (typePieChart) {
            typePieChart.destroy();
            typePieChart = null;
        }

        if (!types || types.length === 0) {
            canvas.style.display = 'none';
            empty.style.display = 'block';
            empty.textContent = 'Aucune donnee.';
            return;
        }

        if (typeof Chart === 'undefined') {
            canvas.style.display = 'none';
            empty.style.display = 'block';
            empty.textContent = 'Chart.js indisponible.';
            return;
        }

        const labels = types.map((row) => row.libelle);
        const values = types.map((row) => Number(row.total) || 0);
        const total = values.reduce((sum, value) => sum + value, 0);

        if (total <= 0) {
            canvas.style.display = 'none';
            empty.style.display = 'block';
            empty.textContent = 'Aucune donnee.';
            return;
        }
        canvas.style.display = 'block';
        empty.style.display = 'none';
        const piePercentPlugin = {
            id: 'piePercentPlugin',
            afterDatasetsDraw: (chart) => {
                const dataset = chart.data.datasets?.[0];
                const meta = chart.getDatasetMeta(0);
                if (!dataset || !meta || !Array.isArray(meta.data)) return;

                const datasetValues = (dataset.data || []).map((v) => Number(v || 0));
                const localTotal = datasetValues.reduce((sum, v) => sum + v, 0);
                if (localTotal <= 0) return;

                const { ctx } = chart;
                ctx.save();
                ctx.fillStyle = '#ffffff';
                ctx.font = '700 12px "Segoe UI", sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                meta.data.forEach((arc, index) => {
                    const value = datasetValues[index] || 0;
                    if (value <= 0) return;

                    const percent = `${((value / localTotal) * 100).toFixed(1)}%`;
                    const startAngle = arc.startAngle;
                    const endAngle = arc.endAngle;
                    const angle = (startAngle + endAngle) / 2;
                    const radius = (arc.innerRadius + arc.outerRadius) / 2;
                    const x = arc.x + Math.cos(angle) * radius;
                    const y = arc.y + Math.sin(angle) * radius;

                    ctx.fillText(percent, x, y);
                });

                ctx.restore();
            },
        };

        try {
            typePieChart = new Chart(canvas.getContext('2d'), {
                type: 'pie',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: labels.map((_, idx) => chartPalette[idx % chartPalette.length]),
                        borderColor: '#ffffff',
                        borderWidth: 2,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const value = Number(context.parsed || 0);
                                    const pct = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
                                    return `${context.label}: ${value} (${pct}%)`;
                                },
                            },
                        },
                    },
                },
                plugins: [piePercentPlugin],
            });
        } catch (error) {
            canvas.style.display = 'none';
            empty.style.display = 'block';
            empty.textContent = `Erreur graphique type: ${error?.message || 'inconnue'}`;
        }
    };

    const exportToPdf = (mode) => {
        const generatedAt = document.getElementById('generatedAt')?.textContent || '';
        const title = 'ANBG - Export Pilotage CIQ';
        let content = '';

        if (mode === 'global') {
            content = document.querySelector('.wrap')?.innerHTML || '';
        } else {
            const section = document.getElementById(mode);
            if (!section) {
                alert('Section introuvable pour export.');
                return;
            }
            content = section.outerHTML;
        }

        const printWindow = window.open('', '_blank', 'width=1200,height=900');
        if (!printWindow) {
            alert('Popup bloquee. Autorisez les popups pour exporter en PDF.');
            return;
        }

        printWindow.document.write(`
            <!doctype html>
            <html lang="fr">
            <head>
                <meta charset="utf-8">
                <title>${title}</title>
                <style>
                    body { font-family: Arial, sans-serif; color: #111; margin: 16px; }
                    h1 { margin: 0 0 8px 0; font-size: 20px; }
                    .meta { color: #555; font-size: 12px; margin-bottom: 12px; }
                    .export-btn, .filters, form, script { display: none !important; }
                    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
                    th, td { border: 1px solid #cfcfcf; padding: 6px; font-size: 12px; text-align: left; vertical-align: top; }
                    .card, .section { border: 1px solid #d8d8d8; border-radius: 8px; padding: 8px; margin-top: 10px; }
                    .kpi-value { font-size: 20px; font-weight: bold; }
                    .badge { border: 1px solid #888; border-radius: 8px; padding: 2px 6px; font-size: 11px; }
                    .hero { border: 1px solid #d8d8d8; border-radius: 8px; padding: 10px; }
                    .cols { display: block; }
                    .grid { display: block; }
                </style>
            </head>
            <body>
                <h1>${title}</h1>
                <div class="meta">${generatedAt}</div>
                ${content}
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => {
            printWindow.print();
        }, 300);
    };

    const resetBtn = document.getElementById('resetFilters');
    if (resetBtn) {
        resetBtn.addEventListener('click', (e) => {
            e.preventDefault();
            window.location.href = '/pilotage';
        });
    }

    const query = params.toString();
    setInputFromParams();

    document.querySelectorAll('[data-export]').forEach((btn) => {
        btn.addEventListener('click', () => exportToPdf(btn.getAttribute('data-export') || 'global'));
    });

    fetch('/pilotage/data' + (query ? `?${query}` : ''))
        .then((r) => {
            if (!r.ok) throw new Error('Acces refuse ou erreur API');
            return r.json();
        })
        .then((data) => {
            document.getElementById('generatedAt').textContent =
                `Genere le ${new Date(data.generated_at).toLocaleString('fr-FR')} | Periode: ${data.filters_appliques.periode}`;

            fillSelect(
                'direction_id',
                data.catalogues.directions || [],
                params.get('direction_id'),
                'id_direction',
                (row) => `${row.code} - ${row.libelle}`,
            );
            fillSelect(
                'statut_code',
                data.catalogues.statuts || [],
                params.get('statut_code'),
                'code',
                (row) => row.libelle,
            );
            fillSelect(
                'type_code',
                data.catalogues.types || [],
                params.get('type_code'),
                'code',
                (row) => row.libelle,
            );

            const kpis = [
                ['Total demandes recues', data.kpis.total_demandes],
                ['Demandes traitees', data.kpis.total_traitees],
                ['Demandes en cours', data.kpis.total_ouvertes],
                ['Demandes en retard (global)', data.kpis.total_en_retard],
                ['Accueil - dans delais', data.kpis.accueil_verts],
                ['Accueil - a risque', data.kpis.accueil_oranges],
                ['Accueil - en retard', data.kpis.accueil_rouges],
                ['Retards chef', data.kpis.chef_rouges],
                ['Retards agent', data.kpis.agent_rouges],
                ['Taux traitement dans delais (%)', data.kpis.taux_traitement_dans_delais],
                ['Délai actif', data.sla_active ? `${data.sla_active.delai_max_heures}h` : 'N/A'],
            ];

            document.getElementById('kpis').innerHTML = kpis.map(([label, value]) => `
                <article class="card">
                    <div class="kpi-title">${label}</div>
                    <div class="kpi-value">${value}</div>
                </article>
            `).join('');

            renderRows('directionPerfBody', data.performance_directions, 8, (row) => `
                <tr>
                    <td>${row.rang}</td>
                    <td>${row.direction}</td>
                    <td>${row.total_demandes}</td>
                    <td>${row.taux_reponse_dans_delais}</td>
                    <td>${row.delai_moyen_heures ?? '-'}</td>
                    <td>${row.retards_chef ?? 0}</td>
                    <td>${row.retards_agent ?? 0}</td>
                    <td>${row.retards_total ?? 0}</td>
                </tr>
            `);

            renderRows('serviceKpiBody', data.kpi_services, 6, (row) => `
                <tr>
                    <td>${row.direction || '-'}</td>
                    <td>${row.service || '-'}</td>
                    <td>${row.total_demandes ?? 0}</td>
                    <td>${row.retards_chef ?? 0}</td>
                    <td>${row.retards_agent ?? 0}</td>
                    <td>${row.retards_total ?? 0}</td>
                </tr>
            `);

            renderRows('agentKpiBody', data.kpi_agents, 6, (row) => `
                <tr>
                    <td>${row.agent || '-'}</td>
                    <td>${row.service || '-'}</td>
                    <td>${row.total_demandes ?? 0}</td>
                    <td>${row.verts ?? 0}</td>
                    <td>${row.oranges ?? 0}</td>
                    <td>${row.rouges ?? 0}</td>
                </tr>
            `);

            renderRows('statusBody', data.statuts, 2, (row) => `
                <tr><td>${row.libelle}</td><td>${row.total}</td></tr>
            `);
            renderStatusBar(data.statuts || []);

            renderRows('typeBody', data.par_type, 2, (row) => `
                <tr><td>${row.libelle}</td><td>${row.total}</td></tr>
            `);
            renderTypePie(data.par_type || []);

            renderRows('usagerTopBody', data.usagers_plus_actifs, 5, (row) => `
                <tr>
                    <td>${row.usager || '-'}</td>
                    <td>${row.email || '-'}</td>
                    <td>${row.total_demandes}</td>
                    <td>${row.total_reclamations}</td>
                    <td>${row.taux_reclamation}</td>
                </tr>
            `);

            renderRows('annexeBody', data.tableau_suivi_annexe, 12, (row) => `
                <tr>
                    <td>${row.rang}</td>
                    <td>${formatDateTime(row.date_reception)}</td>
                    <td>${row.expediteur || '-'}</td>
                    <td>${row.objet || '-'}</td>
                    <td>${formatDateTime(row.date_dispatching)}</td>
                    <td>${row.delai_transmission_oh || '-'}</td>
                    <td>${row.service_direction || '-'}</td>
                    <td>${formatDateTime(row.realisation)}</td>
                    <td>${row.statut_traitement || '-'}</td>
                    <td>${row.respect_delais || '-'}</td>
                    <td>${row.jours_attente ?? 0}</td>
                    <td>${row.qcs || '-'}</td>
                </tr>
            `);

            const annexeRepartition = data.annexe_repartition || {};
            const infoRows = annexeRepartition.informations || [];
            const reclamationRows = annexeRepartition.reclamations || [];
            renderRows('annexe2RecBody', reclamationRows, 3, (row) => `
                <tr>
                    <td>${row.objet || '-'}</td>
                    <td>${row.nombre_mails ?? 0}</td>
                    <td>${row.pourcentage ?? 0}%</td>
                </tr>
            `);
            document.getElementById('annexe2RecTotal').textContent = annexeRepartition.total_reclamations ?? 0;

            renderRows('traceGlobalBody', data.tracabilite_globale, 7, (row) => `
                <tr>
                    <td>${row.numero_suivi || '-'}</td>
                    <td>${row.usager || '-'}</td>
                    <td>${row.type_demande || '-'}</td>
                    <td>${row.statut_courant || '-'}</td>
                    <td>${row.direction || '-'} / ${row.service || '-'}</td>
                    <td>${row.objet || '-'}</td>
                    <td>
                        <ul class="trace-list">
                            ${(row.actions || []).map((action) => `
                                <li>
                                    <span class="trace-action">${action.action || '-'}</span>
                                    | ${action.acteur || '-'}
                                    | ${formatDateTimeFull(action.date_action)}
                                    ${action.transition ? `| ${action.transition}` : ''}
                                    ${action.commentaire ? `| ${action.commentaire}` : ''}
                                </li>
                            `).join('')}
                        </ul>
                    </td>
                </tr>
            `);

            renderRows('demandsBody', data.demandes_en_cours, 10, (d) => `
                <tr>
                    <td>${d.numero_suivi}</td>
                    <td>${d.usager || '-'}</td>
                    <td>${d.objet}</td>
                    <td>${d.service || '-'}</td>
                    <td>${d.statut}</td>
                    <td>${d.heures_ouvrees}</td>
                    <td>${alertBadge(d.alerte_accueil)}</td>
                    <td>${alertBadge(d.alerte_chef)}</td>
                    <td>${alertBadge(d.alerte_agent)}</td>
                    <td>${alertBadge(d.alerte)}</td>
                </tr>
            `);

            renderRows('usagerHistoryBody', data.historique_usagers, 7, (row) => `
                <tr>
                    <td>${new Date(row.date_soumission).toLocaleString('fr-FR')}</td>
                    <td>${row.numero_suivi}</td>
                    <td>${row.usager || '-'}</td>
                    <td>${row.email || '-'}</td>
                    <td>${row.type_demande}</td>
                    <td>${row.statut_demande}</td>
                    <td>${row.objet}</td>
                </tr>
            `);

            renderRows('actionsBody', data.actions_recentes, 7, (row) => `
                <tr>
                    <td>${new Date(row.date_action).toLocaleString('fr-FR')}</td>
                    <td>${row.numero_suivi}</td>
                    <td>${row.type_action}</td>
                    <td>${row.acteur || '-'}</td>
                    <td>${row.direction || '-'}</td>
                    <td>${row.service || '-'}</td>
                    <td>${row.commentaire || '-'}</td>
                </tr>
            `);
        })
        .catch((err) => {
            document.getElementById('kpis').innerHTML = `<article class="card"><div class="error">${err.message}</div></article>`;
            document.getElementById('directionPerfBody').innerHTML = '<tr><td colspan="8" class="error">Erreur de chargement.</td></tr>';
            document.getElementById('serviceKpiBody').innerHTML = '<tr><td colspan="6" class="error">Erreur de chargement.</td></tr>';
            document.getElementById('agentKpiBody').innerHTML = '<tr><td colspan="6" class="error">Erreur de chargement.</td></tr>';
            document.getElementById('statusBody').innerHTML = '<tr><td colspan="2" class="error">Erreur de chargement.</td></tr>';
            document.getElementById('statusBarChart').style.display = 'none';
            document.getElementById('statusBarEmpty').style.display = 'block';
            document.getElementById('statusBarEmpty').textContent = 'Erreur de chargement.';
            document.getElementById('typeBody').innerHTML = '<tr><td colspan="2" class="error">Erreur de chargement.</td></tr>';
            document.getElementById('typePieChart').style.display = 'none';
            document.getElementById('typePieEmpty').style.display = 'block';
            document.getElementById('typePieEmpty').textContent = 'Erreur de chargement.';
            document.getElementById('usagerTopBody').innerHTML = '<tr><td colspan="5" class="error">Erreur de chargement.</td></tr>';
            document.getElementById('annexeBody').innerHTML = '<tr><td colspan="12" class="error">Erreur de chargement.</td></tr>';
            document.getElementById('annexe2InfoBody').innerHTML = '<tr><td colspan="3" class="error">Erreur de chargement.</td></tr>';
            document.getElementById('annexe2RecBody').innerHTML = '<tr><td colspan="3" class="error">Erreur de chargement.</td></tr>';
            document.getElementById('traceGlobalBody').innerHTML = '<tr><td colspan="7" class="error">Erreur de chargement.</td></tr>';
            document.getElementById('demandsBody').innerHTML = '<tr><td colspan="10" class="error">Erreur de chargement.</td></tr>';
            document.getElementById('usagerHistoryBody').innerHTML = '<tr><td colspan="7" class="error">Erreur de chargement.</td></tr>';
            document.getElementById('actionsBody').innerHTML = '<tr><td colspan="7" class="error">Erreur de chargement.</td></tr>';
        });
</script>
</body>
</html>
