<?php
/**
 * Generic report page — renders a report spec (see libraries/Excel_report.php).
 * The Excel download uses the very same spec, so screen and sheet always match.
 */
$spec    = $spec ?? [];
$cols    = $spec['columns'] ?? [];
$rows    = $spec['rows'] ?? [];
$filters = $spec['filters'] ?? [];
$query   = $_GET;
unset($query['export']);
$xls_url = current_url() . '?' . http_build_query(array_merge($query, ['export' => 'excel']));

if (!function_exists('_rv_cell')) {
    function _rv_cell($row, $col) {
        $row = (array) $row;
        $v = isset($col['fn']) && is_callable($col['fn']) ? call_user_func($col['fn'], $row) : ($row[$col['key']] ?? '');
        $t = $col['type'] ?? 'text';
        if ($v === '' || $v === null) return ['', ''];
        switch ($t) {
            case 'money':  return [format_amount((float) $v), (float) $v];
            case 'int':    return [number_format((int) $v), (int) $v];
            case 'number': return [number_format((float) $v, 2), (float) $v];
            case 'pct':    return [number_format((float) $v, 2) . '%', (float) $v];
            case 'date':   return [format_date($v), substr((string) $v, 0, 10)];
            case 'datetime': return [format_date_time($v), (string) $v];
            case 'html':   return [(string) $v, null];   // trusted, built by the controller (action buttons)
            default:       return [html_escape((string) $v), null];
        }
    }
    function _rv_kpi($k) {
        $t = $k['type'] ?? 'text';
        if ($t === 'money') return format_amount((float) $k['value']);
        if ($t === 'int')   return number_format((int) $k['value']);
        if ($t === 'pct')   return number_format((float) $k['value'], 2) . '%';
        return html_escape((string) $k['value']);
    }
}
$tables = array_merge([['title' => $spec['table_title'] ?? 'Details', 'columns' => $cols, 'rows' => $rows, 'main' => true]], $spec['sheets'] ?? []);
?>
<style>
.rv-kpi{border-left:4px solid #2E5C8A;background:#fff;border-radius:6px;padding:10px 14px;box-shadow:0 1px 2px rgba(0,0,0,.06);height:100%}
.rv-kpi .l{font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:#6c7a89;font-weight:600}
.rv-kpi .v{font-size:1.25rem;font-weight:700;color:#1F3A5F;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rv-kpi.success{border-color:#28a745}.rv-kpi.danger{border-color:#dc3545}.rv-kpi.warning{border-color:#ffc107}.rv-kpi.info{border-color:#17a2b8}
.rv-table thead th{background:#2E5C8A;color:#fff;font-weight:600;font-size:.8rem;vertical-align:middle;white-space:nowrap}
.rv-table td{font-size:.85rem;vertical-align:middle}
.rv-table tfoot th{background:#DCE6F1;color:#1F3A5F}
.rv-table tr.rv-group td{background:#EAEFF5;font-weight:700}
.rv-table tr.rv-subtotal td,.rv-table tr.rv-total td{background:#DCE6F1;font-weight:700}
@media print{.rv-noprint,.main-sidebar,.main-header,.main-footer,.dataTables_filter,.dataTables_length,.dataTables_paginate,.dataTables_info{display:none!important}.content-wrapper{margin:0!important}}
</style>

<div class="card card-outline card-primary">
    <div class="card-header d-flex flex-wrap align-items-center">
        <h3 class="card-title mb-0 mr-auto">
            <i class="fas <?= $spec['icon'] ?? 'fa-table' ?> mr-1"></i> <?= html_escape($spec['title'] ?? 'Report') ?>
            <?php if (!empty($spec['subtitle'])): ?><small class="text-muted ml-2"><?= html_escape($spec['subtitle']) ?></small><?php endif; ?>
        </h3>
        <div class="rv-noprint mt-2 mt-md-0">
            <a href="<?= site_url('admin/reports') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-th-large"></i> All reports</a>
            <a href="<?= html_escape($xls_url) ?>" class="btn btn-sm btn-success"><i class="fas fa-file-excel"></i> Excel</a>
            <button type="button" onclick="window.print()" class="btn btn-sm btn-secondary"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>

    <?php if ($filters): ?>
    <div class="card-body border-bottom py-2 rv-noprint">
        <form method="get" class="form-row align-items-end">
            <?php foreach ($filters as $f): ?>
            <div class="col-6 col-md-auto mb-2">
                <label class="small text-muted mb-0"><?= html_escape($f['label']) ?></label>
                <?php if (($f['type'] ?? 'text') === 'select'): ?>
                    <select name="<?= $f['name'] ?>" class="form-control form-control-sm">
                        <?php foreach ($f['options'] as $ov => $ol): ?>
                        <option value="<?= html_escape($ov) ?>" <?= (string) $ov === (string) ($f['value'] ?? '') ? 'selected' : '' ?>><?= html_escape($ol) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="<?= $f['type'] ?? 'text' ?>" name="<?= $f['name'] ?>" value="<?= html_escape($f['value'] ?? '') ?>" class="form-control form-control-sm" <?= !empty($f['placeholder']) ? 'placeholder="' . html_escape($f['placeholder']) . '"' : '' ?>>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <div class="col-auto mb-2">
                <button class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> Apply</button>
                <a href="<?= current_url() ?>" class="btn btn-sm btn-light">Reset</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="card-body">
        <?php if (!empty($spec['kpis'])): ?>
        <div class="row">
            <?php foreach ($spec['kpis'] as $k): ?>
            <div class="col-6 col-md-4 col-xl-2 mb-3">
                <div class="rv-kpi <?= $k['color'] ?? '' ?>" title="<?= html_escape($k['hint'] ?? '') ?>">
                    <div class="l"><?= html_escape($k['label']) ?></div>
                    <div class="v"><?= _rv_kpi($k) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($spec['toolbar_html'])): ?>
        <div class="mb-3 rv-noprint"><?= $spec['toolbar_html'] ?></div>
        <?php endif; ?>

        <?php if (!empty($spec['chart'])): ?>
        <div class="mb-4" style="position:relative;height:260px"><canvas id="rvChart"></canvas></div>
        <?php endif; ?>

        <?php foreach ($tables as $ti => $tb):
            $tcols = $tb['columns'] ?? []; $trows = $tb['rows'] ?? [];
            $styled = false; foreach ($trows as $tr) { if (!empty(((array) $tr)['_style'])) { $styled = true; break; } }
            $totals = []; ?>
        <?php if ($ti > 0 || count($tables) > 1): ?><h5 class="mt-<?= $ti ? 4 : 0 ?> mb-2 text-primary"><?= html_escape($tb['title'] ?? '') ?></h5><?php endif; ?>
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-striped table-hover rv-table <?= $styled ? '' : 'rv-dt' ?>" style="width:100%">
                <thead><tr>
                    <?php foreach ($tcols as $c): $num = in_array($c['type'] ?? 'text', ['money', 'int', 'number', 'pct']); ?>
                    <th class="<?= $num ? 'text-right' : '' ?>"><?= html_escape($c['label']) ?></th>
                    <?php endforeach; ?>
                </tr></thead>
                <tbody>
                <?php foreach ($trows as $r): $ra = (array) $r; $cls = !empty($ra['_style']) ? 'rv-' . $ra['_style'] : ''; ?>
                    <tr class="<?= $cls ?>">
                    <?php foreach ($tcols as $ci => $c):
                        [$txt, $sort] = _rv_cell($ra, $c);
                        $num = in_array($c['type'] ?? 'text', ['money', 'int', 'number', 'pct']);
                        if (!empty($c['total']) && empty($ra['_style'])) $totals[$ci] = ($totals[$ci] ?? 0) + (float) $sort;
                        $link = isset($c['link']) && is_callable($c['link']) ? call_user_func($c['link'], $ra) : null; ?>
                        <td class="<?= $num ? 'text-right' : '' ?> <?= $num && $sort < 0 ? 'text-danger' : '' ?>" <?= $sort !== null ? 'data-order="' . html_escape((string) $sort) . '"' : '' ?>>
                            <?= $link ? '<a href="' . html_escape($link) . '">' . $txt . '</a>' : $txt ?>
                        </td>
                    <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <?php if ($totals && $trows && empty($tb['no_total_row'])): ?>
                <tfoot><tr>
                    <?php foreach ($tcols as $ci => $c): ?>
                    <th class="text-right"><?php
                        if ($ci === 0) echo 'TOTAL';
                        elseif (isset($totals[$ci])) echo ($c['type'] ?? '') === 'int' ? number_format($totals[$ci]) : format_amount($totals[$ci]);
                    ?></th>
                    <?php endforeach; ?>
                </tr></tfoot>
                <?php endif; ?>
            </table>
        </div>
        <?php if (!$trows): ?><p class="text-center text-muted py-3 mb-0"><i class="fas fa-inbox mr-1"></i> No records for the selected filters.</p><?php endif; ?>
        <?php endforeach; ?>

        <?php if (!empty($spec['note'])): ?>
        <p class="small text-muted mt-3 mb-0"><i class="fas fa-info-circle mr-1"></i> <?= html_escape($spec['note']) ?></p>
        <?php endif; ?>
    </div>
</div>
<?= $spec['after_html'] ?? '' ?>

<script>
$(function () {
    if ($.fn.DataTable) {
        $('.rv-dt').each(function () {
            if ($(this).find('tbody tr').length > 0) {
                $(this).DataTable({ pageLength: 50, order: [], lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']], autoWidth: false });
            }
        });
    }
    <?php if (!empty($spec['chart'])): ?>
    if (window.Chart) {
        var cfg = <?= json_encode($spec['chart']) ?>;
        var palette = ['#2E5C8A', '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6f42c1'];
        (cfg.datasets || []).forEach(function (d, i) { d.backgroundColor = d.backgroundColor || palette[i % palette.length]; d.borderColor = d.borderColor || palette[i % palette.length]; });
        new Chart(document.getElementById('rvChart'), {
            type: cfg.type || 'bar',
            data: { labels: cfg.labels, datasets: cfg.datasets },
            options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } },
                       scales: (cfg.type === 'doughnut' || cfg.type === 'pie') ? {} : { y: { beginAtZero: true, ticks: { callback: function (v) { return '₹' + Number(v).toLocaleString('en-IN'); } } } } }
        });
    }
    <?php endif; ?>
});
</script>
