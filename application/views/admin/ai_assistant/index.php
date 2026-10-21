<?php $cfg = $ai_config;
$cur_model = ['local' => $cfg['local_model'], 'openai' => $cfg['openai_model'], 'gemini' => $cfg['gemini_model'], 'deepseek' => $cfg['deepseek_model'], 'openrouter' => $cfg['openrouter_model'], 'aicredits' => $cfg['aicredits_model']][$cfg['provider']] ?? '';
$chat_only = false;
foreach ($model_catalog[$cfg['provider']] ?? [] as $mc) if ($mc['id'] === $cur_model && isset($mc['tools']) && $mc['tools'] === false) $chat_only = true;
$model_label = (['local' => 'Local', 'openai' => 'ChatGPT', 'gemini' => 'Gemini', 'deepseek' => 'DeepSeek', 'openrouter' => 'OpenRouter', 'aicredits' => 'AICredits'][$cfg['provider']] ?? $cfg['provider']) . ' · ' . $cur_model;
?>
<style>
:root{--ai-bg:#f7f7f8;--ai-card:#fff;--ai-line:#e5e7eb;--ai-text:#1f2937;--ai-muted:#6b7280;--ai-brand:#1a3c6e;--ai-brand2:#2c5f9e;--ai-ok:#16a34a;--ai-warn:#b45309;--ai-err:#dc2626}
.ai-shell{display:flex;flex-direction:column;height:calc(100vh - 150px);min-height:520px;background:var(--ai-card);border-radius:14px;border:1px solid var(--ai-line);overflow:hidden}
.ai-top{display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--ai-line);flex-wrap:wrap}
.ai-top .ttl{font-weight:600;color:var(--ai-text)}
.ai-model{font-size:12px;color:var(--ai-muted);background:var(--ai-bg);border-radius:999px;padding:2px 10px}
.ai-usage{font-size:12px;color:var(--ai-muted)}
.ai-iconbtn{border:0;background:transparent;color:var(--ai-muted);width:34px;height:34px;border-radius:8px;cursor:pointer}
.ai-iconbtn:hover{background:var(--ai-bg);color:var(--ai-text)}
.ai-scroll{flex:1;overflow-y:auto}
.ai-col{max-width:820px;margin:0 auto;padding:18px 16px 8px}
.ai-row{display:flex;gap:12px;margin-bottom:18px}
.ai-row.user{justify-content:flex-end}
.ai-av{width:30px;height:30px;border-radius:50%;flex:0 0 30px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--ai-brand),var(--ai-brand2));color:#fff;font-size:14px}
.ai-bubble{color:var(--ai-text);font-size:15px;line-height:1.6;white-space:pre-wrap;word-wrap:break-word;max-width:100%}
.ai-row.user .ai-bubble{background:var(--ai-bg);border-radius:18px;padding:9px 16px;max-width:75%}
.ai-row.bot .ai-body{flex:1;min-width:0}
.ai-bubble img{max-width:240px;border-radius:10px;display:block;margin-top:8px}
.ai-err{color:var(--ai-err)}
.ai-empty{text-align:center;padding:7vh 16px 0}
.ai-empty .logo{width:54px;height:54px;border-radius:16px;margin:0 auto 12px;display:flex;align-items:center;justify-content:center;font-size:24px;color:#fff;background:linear-gradient(135deg,var(--ai-brand),var(--ai-brand2))}
.ai-empty h3{font-weight:600;color:var(--ai-text);margin-bottom:4px}
.ai-empty p{color:var(--ai-muted)}
.ai-tiles{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;max-width:640px;margin:22px auto 0;text-align:left}
.ai-tile{border:1px solid var(--ai-line);border-radius:12px;padding:12px 14px;cursor:pointer;transition:.15s}
.ai-tile:hover{background:var(--ai-bg)}
.ai-tile b{display:block;font-size:14px;color:var(--ai-text)}
.ai-tile span{font-size:12px;color:var(--ai-muted)}
.ai-entry{border:1px solid var(--ai-line);border-radius:12px;padding:10px 12px;margin-top:10px;font-size:13px;background:var(--ai-card)}
.ai-entry.saved{border-color:#bbf7d0;background:#f0fdf4}
.ai-entry.undone{background:var(--ai-bg);opacity:.8}
.ai-entry.undone .msg{text-decoration:line-through}
.ai-entry.danger{border-color:#fecaca;background:#fef2f2}
.ai-entry .head{display:flex;align-items:center;gap:8px;font-weight:600;margin-bottom:6px}
.ai-entry .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:6px 10px}
.ai-entry label{font-size:11px;color:var(--ai-muted);margin:0}
.ai-entry .form-control{height:32px;padding:3px 8px;font-size:13px;border-radius:8px}
.ai-entry .warn{color:var(--ai-warn);font-size:12px;margin-top:6px}
.ai-entry .acts{margin-top:8px;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.ai-entry .res{font-size:12px;margin-top:6px}
.ai-chipbtn{border:1px solid var(--ai-line);background:var(--ai-card);border-radius:999px;padding:3px 12px;font-size:12px;color:var(--ai-text);text-decoration:none!important;cursor:pointer}
.ai-chipbtn:hover{background:var(--ai-bg)}
.ai-chipbtn.red{color:var(--ai-err);border-color:#fecaca}
.ai-dock{padding:8px 16px 14px}
.ai-composer{max-width:820px;margin:0 auto;position:relative}
.ai-box{border:1px solid #d1d5db;border-radius:24px;padding:6px 8px 6px 6px;display:flex;align-items:flex-end;gap:4px;box-shadow:0 2px 10px rgba(0,0,0,.05);background:var(--ai-card)}
.ai-box:focus-within{border-color:var(--ai-brand2)}
.ai-box textarea{flex:1;border:0;outline:0;resize:none;font-size:15px;line-height:1.5;max-height:180px;padding:7px 4px;background:transparent;color:var(--ai-text)}
.ai-send{width:36px;height:36px;border-radius:50%;border:0;background:var(--ai-brand);color:#fff}
.ai-send:disabled{background:#cbd5e1}
.ai-rec{color:var(--ai-err)!important;animation:aiPulse 1s infinite}
@keyframes aiPulse{50%{opacity:.35}}
.ai-meta{display:flex;justify-content:space-between;align-items:center;font-size:11px;color:var(--ai-muted);margin-top:6px;padding:0 10px;gap:8px}
.ai-chips{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:6px}
.ai-chip{background:#eef3fb;color:var(--ai-brand);border-radius:999px;padding:2px 10px;font-size:12px}
.ai-chip .fa-times{cursor:pointer;margin-left:6px}
.ai-suggest{position:absolute;bottom:100%;left:0;right:0;margin-bottom:6px;background:var(--ai-card);border:1px solid var(--ai-line);border-radius:12px;box-shadow:0 -6px 20px rgba(0,0,0,.08);max-height:280px;overflow-y:auto;display:none;z-index:20}
.ai-suggest .it{padding:8px 14px;cursor:pointer;display:flex;justify-content:space-between;gap:10px;font-size:13px}
.ai-suggest .it.on,.ai-suggest .it:hover{background:var(--ai-bg)}
.ai-suggest .it small{color:var(--ai-muted)}
.ai-typing span{display:inline-block;width:7px;height:7px;margin:0 2px;background:#9ca3af;border-radius:50%;animation:aiBlink 1.2s infinite}
.ai-typing span:nth-child(2){animation-delay:.2s}.ai-typing span:nth-child(3){animation-delay:.4s}
@keyframes aiBlink{50%{opacity:.2}}
.ai-meter{height:6px;background:var(--ai-line);border-radius:3px;overflow:hidden}
.ai-meter i{display:block;height:100%;background:var(--ai-brand2)}
.ai-price{font-size:12px;color:var(--ai-muted)}
.ai-row.bot .ai-bubble.ai-md{white-space:normal}
.ai-md p{margin:0 0 .6em}.ai-md p:last-child{margin-bottom:0}
.ai-md ul,.ai-md ol{margin:0 0 .6em;padding-left:1.4em}
.ai-md h1,.ai-md h2,.ai-md h3,.ai-md h4{font-size:15px;font-weight:600;margin:.8em 0 .4em}
.ai-md code{background:var(--ai-bg);border-radius:4px;padding:1px 5px;font-size:13px;color:#be185d}
.ai-md pre{background:#0f172a;color:#e2e8f0;border-radius:8px;padding:10px 12px;overflow-x:auto;font-size:12px}
.ai-md pre code{background:transparent;color:inherit;padding:0}
.ai-md .tbl{overflow-x:auto;margin:.4em 0 .8em;border:1px solid var(--ai-line);border-radius:10px}
.ai-md table{border-collapse:collapse;width:100%;font-size:13px}
.ai-md th,.ai-md td{padding:6px 10px;border-bottom:1px solid var(--ai-line);text-align:left;white-space:nowrap}
.ai-md th{background:var(--ai-bg);font-weight:600}
.ai-md tr:last-child td{border-bottom:0}
.ai-steps{font-size:12px;color:var(--ai-muted);margin-bottom:8px}
.ai-steps summary{cursor:pointer;user-select:none;outline:0}
.ai-steps summary:hover{color:var(--ai-text)}
.ai-steps .st{display:flex;gap:8px;padding:3px 0 3px 4px}
.ai-steps .st i{width:14px;margin-top:3px;color:var(--ai-brand2)}
.ai-steps pre{background:var(--ai-bg);border-radius:6px;padding:6px 8px;margin:3px 0 0;font-size:11px;white-space:pre-wrap;color:var(--ai-text);max-height:160px;overflow:auto}
.ai-entry .kv{width:100%;font-size:13px;margin-bottom:4px}
.ai-entry .kv td{padding:3px 6px;border-bottom:1px dashed var(--ai-line);vertical-align:top}
.ai-entry .kv td:first-child{color:var(--ai-muted);width:42%}
.ai-entry .mailbody{white-space:pre-wrap;background:var(--ai-bg);border-radius:8px;padding:8px 10px;max-height:260px;overflow:auto;margin:6px 0}
.ai-entry.stale{opacity:.6}
.ai-live{display:flex;align-items:center;gap:10px;color:var(--ai-muted);font-size:14px}
.ai-live .sec{font-size:11px;background:var(--ai-bg);border-radius:999px;padding:1px 8px}
.ai-live-steps{font-size:12px;color:var(--ai-muted);margin-top:6px}
.ai-live-steps .st{padding:2px 0}.ai-live-steps i{width:16px;color:var(--ai-brand2)}
.ai-send.stop{background:var(--ai-err)}
@media (max-width:600px){.ai-tiles{grid-template-columns:1fr}.ai-row.user .ai-bubble{max-width:90%}.ai-shell{height:calc(100vh - 120px)}.ai-meta span:last-child{display:none}}
</style>

<section class="content">
<div class="container-fluid pt-2">
<div class="ai-shell">
    <div class="ai-top">
        <span class="ttl"><i class="fas fa-robot mr-1" style="color:var(--ai-brand2)"></i> AI Assistant</span>
        <span class="ai-model"><?= htmlspecialchars($model_label) ?></span>
        <?php if ($chat_only): ?><span class="badge badge-danger" title="This model cannot query the database or make entries"><i class="fas fa-exclamation-triangle mr-1"></i>Chat-only model — choose GPT-4o Mini in ⚙️ Settings</span><?php endif; ?>
        <div class="ml-auto d-flex align-items-center" style="gap:6px">
            <div class="custom-control custom-switch mr-2" title="On: clear entries are saved straight away. Off: every entry waits for Confirm.">
                <input type="checkbox" class="custom-control-input" id="aiAuto" checked>
                <label class="custom-control-label small" for="aiAuto">Auto-save</label>
            </div>
            <button class="ai-iconbtn" id="aiNew" title="New chat"><i class="fas fa-edit"></i></button>
            <?php if ($is_super): ?>
            <button class="ai-iconbtn" data-toggle="modal" data-target="#aiSettings" title="AI settings"><i class="fas fa-cog"></i></button>
            <?php endif; ?>
        </div>
    </div>

    <div class="ai-scroll" id="aiScroll"><div class="ai-col" id="aiLog"></div></div>

    <div class="ai-dock">
        <div class="ai-composer">
            <div class="alert alert-warning d-none mb-2" id="aiRecharge" style="border-radius:12px">
                <i class="fas fa-battery-empty mr-2"></i><b>Recharge required.</b> <span></span>
            </div>
            <div class="ai-suggest" id="aiSuggest"></div>
            <div class="ai-chips" id="aiChips"></div>
            <div class="ai-box">
                <label class="ai-iconbtn mb-0 d-flex align-items-center justify-content-center" title="Passbook Excel (.xlsx / .csv), PDF (statement / passbook) or photo">
                    <i class="fas fa-paperclip"></i><input type="file" id="aiImage" accept="image/*,.xlsx,.xls,.csv,.pdf,application/pdf" hidden>
                </label>
                <textarea id="aiText" rows="1" placeholder="काय करायचे ते लिहा… (उदा. योगेश औचरे बचत २००० रोख)"></textarea>
                <button class="ai-iconbtn" id="aiMic" title="Speak in Marathi"><i class="fas fa-microphone"></i></button>
                <button class="ai-send" id="aiSend" title="Send"><i class="fas fa-arrow-up"></i></button>
            </div>
            <div class="ai-meta">
                <span><a href="<?= site_url('admin/ai_assistant/passbook_template') ?>" class="mr-2"><i class="fas fa-file-excel mr-1"></i>Passbook Excel template</a><span id="aiImgPreview"></span></span>
                <span>@ किंवा नाव टाइप करा → सभासद सुचवले जातील · Enter = पाठवा · Shift+Enter = नवी ओळ</span>
            </div>
        </div>
    </div>
</div>
</div>
</section>

<?php if ($is_super): ?>
<div class="modal fade" id="aiSettings" tabindex="-1">
<div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title"><i class="fas fa-cog mr-2"></i>AI Settings</h5><button class="close" data-dismiss="modal">&times;</button></div>
    <form id="aiSettingsForm">
    <div class="modal-body">
        <ul class="nav nav-tabs mb-3">
            <?php if ($is_owner): ?>
            <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#aiTabProv">Integration</a></li>
            <?php endif; ?>
        </ul>
        <div class="tab-content">
        <?php if ($is_owner): ?>
        <div class="tab-pane active" id="aiTabProv">
            <div class="form-group">
                <label>Provider</label>
                <select name="ai_provider" id="aiProv" class="form-control" data-no-search="1">
                    <option value="openai" <?= $cfg['provider'] === 'openai' ? 'selected' : '' ?>>OpenAI — ChatGPT</option>
                    <option value="gemini" <?= $cfg['provider'] === 'gemini' ? 'selected' : '' ?>>Google — Gemini</option>
                    <option value="deepseek" <?= $cfg['provider'] === 'deepseek' ? 'selected' : '' ?>>DeepSeek — Very cheap</option>
                    <option value="openrouter" <?= $cfg['provider'] === 'openrouter' ? 'selected' : '' ?>>OpenRouter — Cheapest (DeepSeek, Llama, Qwen)</option>
                    <option value="aicredits" <?= $cfg['provider'] === 'aicredits' ? 'selected' : '' ?>>AICredits — Best price for India (Aion models)</option>
                    <option value="local"  <?= $cfg['provider'] === 'local'  ? 'selected' : '' ?>>Self-hosted (OpenAI-compatible, e.g. Gemma) — free</option>
                </select>
            </div>

            <div class="prov-box" data-prov="openai">
                <div class="form-group"><label>Model</label>
                    <select name="ai_openai_model" class="form-control model-sel" data-prov="openai" data-cur="<?= htmlspecialchars($cfg['openai_model']) ?>" data-no-search="1"></select>
                    <div class="ai-price mt-1"></div></div>
                <div class="form-group"><label>API key</label>
                    <input name="ai_openai_key" type="password" class="form-control" autocomplete="new-password" placeholder="<?= $cfg['openai_key_set'] ? '•••••• saved — leave blank to keep' : 'sk-…' ?>">
                    <small class="text-muted">platform.openai.com → API keys. Set a usage limit there too.</small></div>
            </div>
            <div class="prov-box" data-prov="gemini">
                <div class="form-group"><label>Model</label>
                    <select name="ai_gemini_model" class="form-control model-sel" data-prov="gemini" data-cur="<?= htmlspecialchars($cfg['gemini_model']) ?>" data-no-search="1"></select>
                    <div class="ai-price mt-1"></div></div>
                <div class="form-group"><label>API key</label>
                    <input name="ai_gemini_key" type="password" class="form-control" autocomplete="new-password" placeholder="<?= $cfg['gemini_key_set'] ? '•••••• saved — leave blank to keep' : 'AIza…' ?>">
                    <small class="text-muted">aistudio.google.com → Get API key.</small></div>
            </div>
            <div class="prov-box" data-prov="deepseek">
                <div class="form-group"><label>Model</label>
                    <select name="ai_deepseek_model" class="form-control model-sel" data-prov="deepseek" data-cur="<?= htmlspecialchars($cfg['deepseek_model']) ?>" data-no-search="1"></select>
                    <div class="ai-price mt-1"></div></div>
                <div class="form-group"><label>API key</label>
                    <input name="ai_deepseek_key" type="password" class="form-control" autocomplete="new-password" placeholder="<?= $cfg['deepseek_key_set'] ? '•••••• saved — leave blank to keep' : 'sk-…' ?>">
                    <small class="text-muted">platform.deepseek.com → API keys. Text only — passbook <b>photos</b> need ChatGPT or Gemini.</small></div>
            </div>
            <div class="prov-box" data-prov="openrouter">
                <div class="form-group"><label>Model</label>
                    <select name="ai_openrouter_model" class="form-control model-sel" data-prov="openrouter" data-cur="<?= htmlspecialchars($cfg['openrouter_model']) ?>" data-no-search="1"></select>
                    <div class="ai-price mt-1"></div></div>
                <div class="form-group"><label>API key</label>
                    <input name="ai_openrouter_key" type="password" class="form-control" autocomplete="new-password" placeholder="<?= $cfg['openrouter_key_set'] ? '•••••• saved — leave blank to keep' : 'sk-…' ?>">
                    <small class="text-muted">openrouter.ai → Dashboard → API keys. Access multiple models via one API.</small></div>
            </div>
            <div class="prov-box" data-prov="aicredits">
                <div class="form-group"><label>Model</label>
                    <select name="ai_aicredits_model" class="form-control model-sel" data-prov="aicredits" data-cur="<?= htmlspecialchars($cfg['aicredits_model']) ?>" data-no-search="1"></select>
                    <div class="ai-price mt-1"></div></div>
                <div class="form-group"><label>API key</label>
                    <input name="ai_aicredits_key" type="password" class="form-control" autocomplete="new-password" placeholder="<?= $cfg['aicredits_key_set'] ? '•••••• saved — leave blank to keep' : 'sk-…' ?>">
                    <small class="text-muted">aicredits.in → Dashboard → API keys. Aion models optimized for India with pricing in INR. <b>Best rates: ₹0.71/M input.</b></small></div>
            </div>
            <div class="prov-box" data-prov="local">
                <div class="form-group"><label>Server URL</label>
                    <input name="ai_local_url" class="form-control" value="<?= htmlspecialchars($cfg['local_url']) ?>" placeholder="https://xxxx.trycloudflare.com">
                    <small class="text-muted">/v1 is added automatically. The web server must be able to reach it.</small></div>
                <div class="form-group"><label>Model name</label>
                    <input name="ai_local_model" class="form-control" value="<?= htmlspecialchars($cfg['local_model']) ?>"></div>
            </div>
            <div id="aiTestResult" class="small"></div>
        </div>
        <?php endif; ?>

        </div>
    </div>
    <div class="modal-footer">
        <?php if ($is_owner): ?><button type="button" class="btn btn-outline-secondary" id="aiTest">Test connection</button><?php endif; ?>
        <button type="submit" class="btn btn-primary">Save</button>
    </div>
    </form>
</div></div>
</div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/marked/12.0.2/marked.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.1.6/purify.min.js"></script>
<script>
$(function () {
    var URL = '<?= site_url('admin/ai_assistant') ?>';
    var TODAY = '<?= date('Y-m-d') ?>';
    var CATALOG = <?= json_encode($model_catalog, JSON_UNESCAPED_UNICODE) ?>;
    var USD_INR = <?= (float) $cfg['usd_inr'] ?>;
    var mentions = {}, imageData = '', pdfDoc = null, busy = false;   // pdfDoc = {name, pages, text, images[]}
    var $log = $('#aiLog'), $scroll = $('#aiScroll'), $txt = $('#aiText'), $sug = $('#aiSuggest');
    var MODES = {cash: 'Cash / रोख', upi: 'UPI', bank_transfer: 'Bank transfer', cheque: 'Cheque'};
    var TITLES = {loan_payment: '💰 Loan EMI payment', savings_deposit: '🏦 Savings deposit', fine: '⚠️ Fine',
                  member_status: '👤 Member status', reverse_entry: '🗑️ Delete entry', update_member: '✏️ Update member',
                  foreclose_loan: '🔒 ', send_email: '✉️ ', correct_record: '🛠️ '};
    var TILES = [
        ['थकबाकी कर्जे', 'कोणाचे हप्ते थकले आहेत, किती रक्कम', 'थकबाकी असलेल्या सर्व सभासदांची यादी रकमेसह दाखवा'],
        ['आजचा सारांश', 'सभासद, कर्जे, बचत, दंड', 'आजचा संपूर्ण सारांश सांगा: सभासद, कर्जे, बचत, दंड आणि या महिन्याची वसुली'],
        ['Force close / foreclose', 'quote + close a loan', 'Show the foreclosure and force-close amount for loan '],
        ['Email a member', 'reminder, statement, notice', 'Send an EMI reminder email to '],
        ['बचत जमा करा', 'उदा. योगेश औचरे बचत २००० रोख', 'योगेश औचरे यांच्या बचत खात्यात २००० रोख जमा करा'],
        ['बँक नोंदी map करा', 'unmapped बँक जमा नोंदी सभासदांशी जोडा', 'बँकेतील unmapped जमा नोंदी सभासदांशी map करा'],
        ['पासबुक Excel अपलोड', '📎 Excel निवडा — template खाली आहे', '__upload__'],
        ['सभासद थकबाकी (Member dues)', 'EMI + बचत + दंड — प्रत्येक सभासद', 'Member dues report दाखवा: प्रत्येक सभासदाची EMI, बचत आणि दंड थकबाकी'],
        ['दंड Preview (dry run)', 'आज auto fine चालवले तर किती दंड लागेल', 'Fine preview दाखवा: आज auto fine job चालवला तर कोणते दंड लागतील (काहीही save करू नका)'],
        ['Day Book / Portfolio', 'आजची रोकड, PAR 30/90', 'या महिन्याचे day book आणि loan portfolio (PAR 30 / 90) दाखवा'],
        ['सर्व Reports', 'Report Center — Excel सह', 'Report Center page उघडा']
    ];

    function csrf(d) { if (window.CSRF_NAME) d[window.CSRF_NAME] = window.CSRF_TOKEN; return d; }
    function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
    function md(s) {
        s = s == null ? '' : String(s);
        if (window.marked && window.DOMPurify) {
            var html = DOMPurify.sanitize(marked.parse(s, {breaks: true, gfm: true}));
            return '<div class="ai-mdwrap">' + html.replace(/<table>/g, '<div class="tbl"><table>').replace(/<\/table>/g, '</table></div>') + '</div>';
        }
        return esc(s).replace(/\*\*(.+?)\*\*/g, '<b>$1</b>').replace(/`([^`]+)`/g, '<code>$1</code>').replace(/\n/g, '<br>');
    }
    function stepsHtml(steps) {
        if (!steps || !steps.length) return '';
        return '<details class="ai-steps"><summary><i class="fas fa-cogs mr-1"></i>' + steps.length + ' step' + (steps.length > 1 ? 's' : '') + ' — ' +
            esc(steps[steps.length - 1].label) + '</summary>' + steps.map(function (s) {
                return '<div class="st"><i class="fas fa-' + esc(s.icon || 'circle') + '"></i><div>' + esc(s.label) +
                       (s.detail ? '<pre>' + esc(s.detail) + '</pre>' : '') + '</div></div>';
            }).join('') + '</details>';
    }
    function down() { $scroll.stop().animate({scrollTop: $scroll[0].scrollHeight}, 200); }
    function inr(n) { return '₹' + Number(n || 0).toLocaleString('en-IN', {maximumFractionDigits: 2}); }

    // ── Rendering ─────────────────────────────────────────────────
    function empty() {
        $log.html('<div class="ai-empty"><div class="logo"><i class="fas fa-robot"></i></div>' +
            '<h3>नमस्कार! मी कशी मदत करू?</h3><p>मराठी, हिंदी किंवा इंग्रजीत लिहा किंवा 🎤 बोला. मला संपूर्ण डेटाबेस माहीत आहे — कोणताही प्रश्न विचारा, नोंदी करा, कर्ज बंद (foreclose / force close) करा, ईमेल पाठवा किंवा चुकीची माहिती दुरुस्त करा. बदल करण्याआधी मी तुमची Confirm घेतो.</p>' +
            '<div class="ai-tiles">' + TILES.map(function (t, i) {
                return '<div class="ai-tile" data-i="' + i + '"><b>' + esc(t[0]) + '</b><span>' + esc(t[1]) + '</span></div>';
            }).join('') + '</div></div>');
    }
    function userRow(text, hasImg, img) {
        $log.find('.ai-empty').remove();
        var $r = $('<div class="ai-row user"><div class="ai-bubble"></div></div>');
        $r.find('.ai-bubble').html(esc(text) || (hasImg ? '📎 image' : ''));
        if (img) $r.find('.ai-bubble').append('<img src="' + img + '">');
        $log.append($r); down();
    }
    function botRow(html, cls, steps) {
        $log.find('.ai-empty').remove();
        var $r = $('<div class="ai-row bot"><div class="ai-av"><i class="fas fa-robot"></i></div><div class="ai-body">' + stepsHtml(steps) +
                   '<div class="ai-bubble ' + (cls || 'ai-md') + '"></div></div></div>');
        $r.find('.ai-bubble').html(html);
        $log.append($r); down();
        return $r;
    }
    function renderCards($row, cards) {
        var $body = $row.find('.ai-body'), pending = 0, links = '';
        (cards || []).forEach(function (c) {
            if (c.type === 'navigate') { links += '<a class="ai-chipbtn mr-1" href="' + esc(c.url) + '"><i class="fas fa-arrow-right mr-1"></i>' + esc(c.label) + '</a>'; return; }
            if (c.type === 'data_table') { $body.append(tableCardHtml(c)); return; }
            if (c.executed) { $body.append(savedHtml(c)); return; }
            if (c.stale) { $body.append('<div class="ai-entry stale"><div class="msg text-muted"><i class="fas fa-history mr-1"></i>' +
                esc((TITLES[c.type] || '') + (c.title || '')) + ' — already handled.</div></div>'); return; }
            pending++; $body.append($(cardHtml(c)).attr('data-token', c.token || ''));
        });
        if (links) $body.append('<div class="mt-2">' + links + '</div>');
        if (pending > 1) $body.append('<button class="btn btn-success btn-sm mt-2 ai-confirm-all"><i class="fas fa-check-double mr-1"></i>Confirm all (' + pending + ')</button>');
        down();
    }
    // Query / report result: rendered here so the model doesn't have to retype it (saves output tokens)
    function tableCardHtml(c) {
        var num = /amount|due|balance|outstanding|collected|total|principal|interest|paid|fine|payments|deposits|unmapped/i;
        var cols = c.columns || [];
        var head = cols.map(function (h) { return '<th>' + esc(String(h).replace(/_/g, ' ')) + '</th>'; }).join('');
        var body = (c.rows || []).map(function (r) {
            return '<tr>' + r.map(function (v, i) {
                var isNum = v !== null && v !== '' && !isNaN(v) && num.test(cols[i] || '');
                return '<td' + (isNum ? ' style="text-align:right"' : '') + '>' + (v === null ? '<span class="text-muted">—</span>' : esc(isNum ? Number(v).toLocaleString('en-IN', {maximumFractionDigits: 2}) : v)) + '</td>';
            }).join('') + '</tr>';
        }).join('');
        // A single summary row reads better as label → value
        if ((c.rows || []).length === 1 && cols.length > 4) {
            body = cols.map(function (h, i) {
                var v = c.rows[0][i];
                return '<tr><td class="text-muted">' + esc(String(h).replace(/_/g, ' ')) + '</td><td style="text-align:right"><b>' +
                       esc(v !== null && !isNaN(v) && v !== '' ? Number(v).toLocaleString('en-IN', {maximumFractionDigits: 2}) : (v == null ? '—' : v)) + '</b></td></tr>';
            }).join('');
            head = '<th>Item</th><th style="text-align:right">Value</th>';
        }
        return '<div class="ai-entry"><div class="head"><i class="fas fa-table" style="color:var(--ai-brand2)"></i> ' + esc(c.title) +
            '<small class="text-muted font-weight-normal ml-1">' + (c.rows || []).length + (c.more ? '+' : '') + ' row(s)</small>' +
            (c.open_url ? '<a class="ai-chipbtn ml-auto" target="_blank" href="' + esc(c.open_url) + '"><i class="fas fa-external-link-alt mr-1"></i>Open report</a>' : '') +
            (c.export ? '<a class="ai-chipbtn ml-auto" href="' + URL + '/export?t=' + encodeURIComponent(c.export) + '"><i class="fas fa-file-excel mr-1" style="color:#16a34a"></i>Excel</a>' : '') +
            (c.export_url ? '<a class="ai-chipbtn' + (c.open_url ? ' ml-1' : ' ml-auto') + '" href="' + esc(c.export_url) + '"><i class="fas fa-file-excel mr-1" style="color:#16a34a"></i>Excel</a>' : '') +
            '</div><div class="ai-md"><div class="tbl" style="max-height:340px;overflow:auto;margin:0"><table><thead><tr>' + head + '</tr></thead><tbody>' + body +
            '</tbody></table></div></div>' + (c.more ? '<div class="small text-muted mt-1">Showing the first rows — use Excel for the full list.</div>' : '') + '</div>';
    }
    function savedHtml(c) {
        var r = c.result || {}, d = r.data || {};
        var undo = (d.kind === 'savings_deposit' || d.kind === 'fine')
            ? '<button class="ai-chipbtn red ai-undo" data-kind="' + d.kind + '" data-id="' + d.target_id + '"><i class="fas fa-undo mr-1"></i>Undo</button>' : '';
        var results = '';
        if (d.results && d.results.length) {
            var icon = {saved: '✅', failed: '❌', skipped: '⏭️'};
            results = '<div class="ai-md"><div class="tbl" style="max-height:320px;overflow:auto"><table><thead><tr><th>Row</th><th>Member</th><th>Amount</th><th>Result</th></tr></thead><tbody>' +
                d.results.map(function (x) {
                    return '<tr><td>' + x.row + '</td><td>' + esc(x.member) + '</td><td>' + inr(x.amount) + '</td><td>' + (icon[x.status] || '') + ' ' + esc(x.message) + '</td></tr>';
                }).join('') + '</tbody></table></div></div>';
        }
        return '<div class="ai-entry saved"><div class="msg"><i class="fas fa-check-circle mr-1" style="color:var(--ai-ok)"></i>' + esc(r.message) + '</div>' + results +
            '<div class="acts">' + (d.link ? '<a class="ai-chipbtn" target="_blank" href="' + esc(d.link) + '">' + esc(d.link_text) + '</a>' : '') + undo + '</div></div>';
    }
    function opt(v, label, sel) { return '<option value="' + esc(v) + '"' + (String(v) === String(sel) ? ' selected' : '') + '>' + esc(label) + '</option>'; }
    function loanOpts(loans, sel) { return (loans.length === 1 ? '' : '<option value="">— choose loan —</option>') + loans.map(function (l) { return opt(l.id, l.loan_number + ' · EMI ₹' + l.emi, sel); }).join(''); }
    function accOpts(a, sel) { return (a.length === 1 ? '' : '<option value="">— choose account —</option>') + a.map(function (x) { return opt(x.id, x.account_number + ' · ₹' + x.monthly + '/m · dues ₹' + x.dues, sel); }).join(''); }
    function warns(a) { return (a.warnings || []).map(function (w) { return '<div class="warn"><i class="fas fa-exclamation-triangle mr-1"></i>' + esc(w) + '</div>'; }).join(''); }
    function actBtns(label, cls) {
        return '<div class="acts"><button class="btn btn-' + (cls || 'primary') + ' btn-sm ai-confirm"><i class="fas fa-check mr-1"></i>' + label + '</button>' +
               '<button class="btn btn-link btn-sm text-muted ai-skip">Skip</button></div><div class="res"></div>';
    }
    function cardHtml(a) {
        if (a.type === 'passbook_import') {
            var s = a.summary || {}, st = {ready: ['✅', 'Ready'], review: ['⚠️', 'Check'], duplicate: ['♻️', 'Duplicate']};
            var body = (a.entries || []).map(function (e) {
                return '<tr' + (e.status !== 'ready' ? ' style="background:#fff7ed"' : '') + '><td>' + (a.bank ? '#' : '') + e.row + '</td><td>' + esc(e.date || '') + '</td><td>' + esc(e.member) +
                    (e.payer ? '<div class="small text-muted" style="white-space:normal">bank: ' + esc(e.payer) + '</div>' : '') +
                    '</td><td>' + inr(e.amount) + '</td><td>' + esc(e.mode) + '</td><td>' + esc(e.alloc || '—') + '</td><td>' + (st[e.status] || ['', e.status])[0] + ' ' +
                    (st[e.status] || ['', e.status])[1] + (e.issues && e.issues.length ? '<div class="small text-muted" style="white-space:normal;min-width:180px">' +
                    e.issues.map(esc).join('<br>') + '</div>' : '') + '</td></tr>';
            }).join('');
            return '<div class="ai-entry" data-type="passbook_import"><div class="head">📄 ' + esc(a.title) + '</div>' +
                '<div class="mb-2"><span class="badge badge-success mr-1">' + (s.ready || 0) + ' ready · ' + inr(s.amount_ready) + '</span>' +
                (s.review ? '<span class="badge badge-warning mr-1">' + s.review + ' need attention</span>' : '') +
                (s.duplicate ? '<span class="badge badge-secondary">' + s.duplicate + ' duplicate (skipped)</span>' : '') + '</div>' +
                '<div class="ai-md"><div class="tbl" style="max-height:360px;overflow:auto"><table><thead><tr><th>Row</th><th>Date</th><th>Member</th><th>Amount</th><th>Mode</th><th>Allocation</th><th>Status</th></tr></thead><tbody>' +
                body + '</tbody></table></div></div>' +
                (a.token ? actBtns((a.bank ? 'Map ' : 'Save ') + (s.ready || 0) + ' entries', 'success') : '<div class="res text-muted small">Nothing ready to save — fix the rows and upload again.</div>') + '</div>';
        }
        if (a.type === 'foreclose_loan' || a.type === 'send_email' || a.type === 'correct_record') {
            var kv = (a.rows || []).map(function (r) { return '<tr><td>' + esc(r[0]) + '</td><td>' + esc(r[1]) + '</td></tr>'; }).join('');
            var extra = '';
            if (a.type === 'send_email') extra = '<div class="mailbody">' + esc(a.body) + '</div>';
            if (a.type === 'correct_record') {
                extra = '<table class="table table-sm mb-1"><thead><tr><th>Field</th><th>Now</th><th>New</th></tr></thead><tbody>' +
                    (a.changes || []).map(function (c) {
                        return '<tr><td class="text-muted">' + esc(c.field) + '</td><td><s class="text-muted">' + esc(c.old == null ? 'NULL' : c.old) +
                               '</s></td><td><b>' + esc(c.new == null ? 'NULL' : c.new) + '</b></td></tr>';
                    }).join('') + '</tbody></table>';
            }
            var btn = {foreclose_loan: a.title, send_email: 'Send email', correct_record: 'Save correction'}[a.type];
            return '<div class="ai-entry' + (a.danger ? ' danger' : '') + '" data-type="' + a.type + '"><div class="head">' + TITLES[a.type] + esc(a.title) + '</div>' +
                '<table class="kv">' + kv + '</table>' + extra + warns(a) + actBtns(esc(btn), a.danger ? 'danger' : 'primary') + '</div>';
        }
        if (a.type === 'update_member') {
            var rows = (a.changes || []).map(function (c, i) {
                return '<tr><td class="text-muted">' + esc(c.field.replace(/_/g, ' ')) + '</td><td><s class="text-muted">' + esc(c.old || '—') + '</s></td>' +
                       '<td><input class="form-control f-chg" data-field="' + esc(c.field) + '" value="' + esc(c.new) + '"></td></tr>';
            }).join('');
            return '<div class="ai-entry" data-type="update_member"><div class="head">' + TITLES.update_member + '</div>' +
                '<label>Member</label><select class="form-control f-member mb-2" data-no-search="1">' +
                ((a.member_candidates || []).length === 1 ? '' : '<option value="">— choose —</option>') +
                (a.member_candidates || []).map(function (c) { return opt(c.id, c.label, a.member_id); }).join('') + '</select>' +
                (rows ? '<table class="table table-sm mb-0"><thead><tr><th>Field</th><th>Now</th><th>New</th></tr></thead><tbody>' + rows + '</tbody></table>' : '') +
                warns(a) + (rows ? actBtns('Save changes') : '<div class="res"></div>') + '</div>';
        }
        if (a.type === 'reverse_entry') {
            var ok = a.target_id && a.kind !== 'loan_payment';
            return '<div class="ai-entry danger" data-type="reverse_entry" data-kind="' + esc(a.kind) + '" data-id="' + esc(a.target_id || '') + '">' +
                '<div class="head">' + TITLES.reverse_entry + '</div>' + (a.label ? '<div class="mb-2">' + esc(a.label) + '</div>' : '') +
                '<label>Reason</label><input class="form-control f-remarks" value="' + esc(a.remarks || '') + '" placeholder="why delete?">' +
                warns(a) + (ok ? actBtns('Delete entry', 'danger') : '<div class="res"></div>') + '</div>';
        }
        var h = '<div class="ai-entry" data-type="' + a.type + '"><div class="head">' + TITLES[a.type] +
                (a.member_text ? '<small class="text-muted font-weight-normal">“' + esc(a.member_text) + '”</small>' : '') + '</div><div class="grid">';
        h += '<div style="grid-column:span 2"><label>Member</label><select class="form-control f-member" data-no-search="1">' +
             ((a.member_candidates || []).length === 1 ? '' : '<option value="">— choose —</option>') +
             (a.member_candidates || []).map(function (c) { return opt(c.id, c.label, a.member_id); }).join('') +
             '</select><input class="form-control f-member-search mt-1" placeholder="search another member…"></div>';
        if (a.type === 'member_status') {
            h += '<div><label>New status' + (a.current_status ? ' (now ' + esc(a.current_status) + ')' : '') + '</label><select class="form-control f-status" data-no-search="1"><option value="">— choose —</option>' +
                 ['active', 'inactive', 'blocked', 'suspended'].map(function (x) { return opt(x, x, a.status); }).join('') + '</select></div>';
            h += '<div style="grid-column:span 2"><label>Reason</label><input class="form-control f-remarks" value="' + esc(a.remarks) + '"></div></div>';
            return h + warns(a) + actBtns('Confirm') + '</div>';
        }
        if (a.type === 'loan_payment') {
            h += '<div><label>Loan</label><select class="form-control f-loan" data-no-search="1">' + loanOpts(a.loans || [], a.loan_id) + '</select></div>';
            h += '<div><label>Type</label><select class="form-control f-ptype" data-no-search="1">' + opt('emi', 'EMI', a.payment_type) + opt('interest_only', 'Interest only', a.payment_type) + '</select></div>';
        }
        if (a.type === 'savings_deposit') {
            h += '<div><label>Account</label><select class="form-control f-acc" data-no-search="1">' + accOpts(a.accounts || [], a.savings_account_id) + '</select></div>';
            h += '<div><label>Deposit</label><select class="form-control f-dtype" data-no-search="1">' + opt('regular', 'Monthly (clears dues)', a.deposit_type) + opt('onetime', 'One-time / extra', a.deposit_type) + '</select></div>';
        }
        h += '<div><label>Amount ₹</label><input type="number" step="0.01" class="form-control f-amount" value="' + (a.amount || '') + '"></div>';
        h += '<div><label>Date</label><input type="date" class="form-control f-date" max="' + TODAY + '" value="' + esc(a.date) + '"></div>';
        if (a.type !== 'fine') {
            h += '<div><label>Mode</label><select class="form-control f-mode" data-no-search="1">' + Object.keys(MODES).map(function (k) { return opt(k, MODES[k], a.mode); }).join('') + '</select></div>';
            h += '<div><label>Reference</label><input class="form-control f-ref" value="' + esc(a.reference) + '"></div>';
        }
        h += '<div style="grid-column:span 2"><label>' + (a.type === 'fine' ? 'Reason *' : 'Remarks') + '</label><input class="form-control f-remarks" value="' + esc(a.remarks) + '"></div></div>';
        return h + warns(a) + actBtns('Confirm') + '</div>';
    }

    // ── Conversation ──────────────────────────────────────────────
    function loadHistory() {
        $.getJSON(URL + '/history', function (r) {
            $log.empty();
            if (!r.messages || !r.messages.length) { empty(); return; }
            r.messages.forEach(function (m) {
                if (m.role === 'user') userRow(m.text, m.image);
                else renderCards(botRow(md(m.text), null, m.steps), m.cards);
            });
            $scroll.scrollTop($scroll[0].scrollHeight);
        }).fail(empty);
    }
    loadHistory();

    $('#aiNew').on('click', function () {
        $.post(URL + '/reset', csrf({}), function () { mentions = {}; renderChips(); empty(); $txt.focus(); }, 'json');
    });
    $log.on('click', '.ai-tile', function () {
        var t = TILES[$(this).data('i')][2];
        if (t === '__upload__') { $('#aiImage').trigger('click'); return; }
        $txt.val(t).trigger('input').focus();
        if (!/[\n ]$/.test(t)) send();
    });

    // ── Composer ──────────────────────────────────────────────────
    $txt.on('input', function () { this.style.height = 'auto'; this.style.height = Math.min(this.scrollHeight, 180) + 'px'; });
    var sugTimer, sugItems = [], sugIdx = -1;
    function currentWord() {
        var pos = $txt[0].selectionStart, before = $txt.val().slice(0, pos);
        var m = before.match(/(@?[A-Za-z0-9]{2,}(?: [A-Za-z]{1,})?)$/);
        return m ? {text: m[1], start: pos - m[1].length, end: pos} : null;
    }
    $txt.on('input click', function () {
        clearTimeout(sugTimer);
        var w = currentWord();
        if (!w || (w.text[0] !== '@' && w.text.length < 3)) { $sug.hide(); return; }
        sugTimer = setTimeout(function () {
            $.getJSON(URL + '/suggest', {q: w.text.replace(/^@/, '')}, function (r) {
                sugItems = r.items || []; sugIdx = -1;
                if (!sugItems.length) { $sug.hide(); return; }
                $sug.html(sugItems.map(function (it, i) {
                    return '<div class="it" data-i="' + i + '"><span><i class="fas fa-' + (it.kind === 'loan' ? 'file-invoice' : 'user') + ' mr-2 text-muted"></i>' + esc(it.label) + '</span><small>' + esc(it.sub) + '</small></div>';
                }).join('')).show();
            });
        }, 200);
    });
    function pick(i) {
        var it = sugItems[i], w = currentWord(); if (!it || !w) return;
        var v = $txt.val();
        $txt.val(v.slice(0, w.start) + it.token + ' ' + v.slice(w.end)).focus();
        if (it.kind === 'member') { mentions[it.id] = it.label; renderChips(); }
        $sug.hide();
    }
    $sug.on('mousedown', '.it', function (e) { e.preventDefault(); pick($(this).data('i')); });
    $txt.on('keydown', function (e) {
        if ($sug.is(':visible') && sugItems.length) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault(); sugIdx = (sugIdx + (e.key === 'ArrowDown' ? 1 : -1) + sugItems.length) % sugItems.length;
                $sug.find('.it').removeClass('on').eq(sugIdx).addClass('on'); return;
            }
            if ((e.key === 'Enter' || e.key === 'Tab') && sugIdx >= 0) { e.preventDefault(); pick(sugIdx); return; }
            if (e.key === 'Escape') { $sug.hide(); return; }
        }
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
    });
    $txt.on('blur', function () { setTimeout(function () { $sug.hide(); }, 150); });
    function renderChips() {
        $('#aiChips').html(Object.keys(mentions).map(function (id) {
            return '<span class="ai-chip"><i class="fas fa-user mr-1"></i>' + esc(mentions[id]) + '<i class="fas fa-times" data-id="' + id + '"></i></span>';
        }).join(''));
    }
    $('#aiChips').on('click', '.fa-times', function () { delete mentions[$(this).data('id')]; renderChips(); });

    // Passbook Excel: checked on the server, shown as a preview card (nothing saved until "Save entries")
    function uploadPassbook(f) {
        if (busy) return;
        if (f.size > 5 * 1024 * 1024) { toastr.error('File too large (max 5 MB).'); return; }
        setBusy(true);
        userRow('📄 ' + f.name);
        var $t = botRow('<div class="ai-live"><span class="ai-typing"><span></span><span></span><span></span></span><span class="txt">Reading the Excel and checking every row…</span></div>');
        var fd = new FormData(); fd.append('file', f);
        if (window.CSRF_NAME) fd.append(window.CSRF_NAME, window.CSRF_TOKEN);
        $.ajax({url: URL + '/passbook_upload', type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json'})
            .done(function (r) {
                $t.remove();
                if (!r.success) { botRow(esc(r.message || 'Error'), 'ai-err'); return; }
                renderCards(botRow(md(r.reply)), r.actions);
            })
            .fail(function (x) { $t.remove(); botRow(esc((x.responseJSON && x.responseJSON.message) || ('Upload failed (HTTP ' + x.status + ').')), 'ai-err'); })
            .always(function () { setBusy(false); });
    }

    $('#aiImage').on('change', function () {
        var f = this.files[0]; if (!f) return;
        if (/\.(xlsx|xls|csv)$/i.test(f.name)) { this.value = ''; uploadPassbook(f); return; }
        if (/\.pdf$/i.test(f.name) || f.type === 'application/pdf') { this.value = ''; readPdf(f); return; }
        if (f.size > 8 * 1024 * 1024) { toastr.error('Image too large (max 8 MB).'); return; }
        var rd = new FileReader();
        rd.onload = function () {
            var img = new Image();
            img.onload = function () {
                var s = Math.min(1, 1600 / Math.max(img.width, img.height));
                var c = document.createElement('canvas'); c.width = img.width * s; c.height = img.height * s;
                c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
                imageData = c.toDataURL('image/jpeg', 0.85);
                $('#aiImgPreview').html('📎 ' + esc(f.name) + ' <a href="#" id="aiImgRm">remove</a>');
            };
            img.src = rd.result;
        };
        rd.readAsDataURL(f); this.value = '';
    });
    $(document).on('click', '#aiImgRm', function (e) { e.preventDefault(); imageData = ''; pdfDoc = null; $('#aiImgPreview').empty(); });

    // ── PDF: read in the browser (pdf.js, loaded on first use). A digital PDF sends its text
    // (accurate, few tokens); a scanned PDF sends its first 4 pages as images for the model to read.
    var PDFJS = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/';
    function loadPdfJs() {
        if (window.pdfjsLib) return Promise.resolve(window.pdfjsLib);
        return new Promise(function (ok, fail) {
            var s = document.createElement('script');
            s.src = PDFJS + 'pdf.min.js';
            s.onload = function () { window.pdfjsLib.GlobalWorkerOptions.workerSrc = PDFJS + 'pdf.worker.min.js'; ok(window.pdfjsLib); };
            s.onerror = function () { fail(new Error('Could not load the PDF reader — check the internet connection.')); };
            document.head.appendChild(s);
        });
    }
    function readPdf(f) {
        if (f.size > 15 * 1024 * 1024) { toastr.error('PDF too large (max 15 MB).'); return; }
        imageData = ''; pdfDoc = null;
        $('#aiImgPreview').html('<i class="fas fa-spinner fa-spin mr-1"></i>Reading ' + esc(f.name) + '…');
        loadPdfJs().then(function (lib) {
            return f.arrayBuffer().then(function (buf) { return lib.getDocument({data: buf}).promise; });
        }).then(function (pdf) {
            var maxText = Math.min(pdf.numPages, 30), texts = [], chain = Promise.resolve();
            for (var p = 1; p <= maxText; p++) (function (p) {
                chain = chain.then(function () { return pdf.getPage(p); }).then(function (page) {
                    return page.getTextContent().then(function (tc) {
                        // keep line breaks: a new line starts when the y position changes
                        var out = '', lastY = null;
                        tc.items.forEach(function (it) {
                            var y = it.transform ? Math.round(it.transform[5]) : null;
                            if (lastY !== null && y !== lastY) out += '\n'; else if (out && !/\s$/.test(out)) out += ' ';
                            out += it.str; lastY = y;
                        });
                        texts.push('--- page ' + p + ' ---\n' + out.replace(/[ \t]+/g, ' ').trim());
                    });
                });
            })(p);
            return chain.then(function () {
                var text = texts.join('\n\n');
                var letters = text.replace(/--- page \d+ ---|\s/g, '').length;
                if (letters >= 80 * Math.min(pdf.numPages, 3)) return {name: f.name, pages: pdf.numPages, text: text.slice(0, 15000), cut: text.length > 15000, images: []};
                // Scanned PDF: render the first pages as images
                var imgs = [], c2 = Promise.resolve();
                for (var q = 1; q <= Math.min(pdf.numPages, 4); q++) (function (q) {
                    c2 = c2.then(function () { return pdf.getPage(q); }).then(function (page) {
                        var vp1 = page.getViewport({scale: 1}), sc = Math.min(2, 1600 / Math.max(vp1.width, vp1.height));
                        var vp = page.getViewport({scale: sc}), cv = document.createElement('canvas');
                        cv.width = vp.width; cv.height = vp.height;
                        return page.render({canvasContext: cv.getContext('2d'), viewport: vp}).promise.then(function () { imgs.push(cv.toDataURL('image/jpeg', 0.8)); });
                    });
                })(q);
                return c2.then(function () { return {name: f.name, pages: pdf.numPages, text: '', images: imgs}; });
            });
        }).then(function (d) {
            pdfDoc = d;
            var how = d.text ? 'text of ' + Math.min(d.pages, 30) + ' page(s)' + (d.cut ? ', first 15,000 characters' : '') : 'scanned — ' + d.images.length + ' page image(s)';
            $('#aiImgPreview').html('📄 ' + esc(f.name) + ' <small class="text-muted">(' + d.pages + ' page' + (d.pages > 1 ? 's' : '') + ', ' + how + ')</small> <a href="#" id="aiImgRm">remove</a>');
            $txt.focus();
        }).catch(function (e) {
            pdfDoc = null; $('#aiImgPreview').empty();
            toastr.error((e && e.name === 'PasswordException') ? 'This PDF is password protected — save an unprotected copy and attach that.' : ('Could not read the PDF: ' + (e && e.message ? e.message : e)));
        });
    }

    var Rec = window.SpeechRecognition || window.webkitSpeechRecognition, rec = null;
    if (!Rec) $('#aiMic').hide();
    $('#aiMic').on('click', function () {
        if (rec) { rec.stop(); return; }
        rec = new Rec(); rec.lang = 'mr-IN'; rec.interimResults = true;
        var base = $txt.val();
        rec.onresult = function (e) { var t = ''; for (var i = 0; i < e.results.length; i++) t += e.results[i][0].transcript; $txt.val((base ? base + ' ' : '') + t).trigger('input'); };
        rec.onend = function () { rec = null; $('#aiMic i').removeClass('ai-rec'); };
        rec.onerror = function (e) { toastr.error('Mic: ' + e.error); };
        rec.start(); $('#aiMic i').addClass('ai-rec');
    });

    var aborter = null;
    function setBusy(on) {
        busy = on;
        $('#aiSend').prop('disabled', false).toggleClass('stop', on).attr('title', on ? 'Stop' : 'Send')
            .html(on ? '<i class="fas fa-stop"></i>' : '<i class="fas fa-arrow-up"></i>');
    }
    function send() {
        if (busy) { if (aborter) aborter.abort(); return; }   // the send button is "Stop" while working
        var text = $.trim($txt.val());
        if (!text && !imageData && !pdfDoc) return;
        setBusy(true); $sug.hide();
        var sentImage = imageData, sentPdf = pdfDoc, sentMentions = Object.keys(mentions).map(Number);
        $txt.val('').trigger('input'); imageData = ''; pdfDoc = null; $('#aiImgPreview').empty(); mentions = {}; renderChips();
        userRow((text ? text + (sentPdf ? '\n' : '') : '') + (sentPdf ? '📄 ' + sentPdf.name : ''), !!sentImage, sentImage);

        // Live progress bubble: current status + elapsed time + the steps as they happen
        var $t = botRow('<div class="ai-live"><span class="ai-typing"><span></span><span></span><span></span></span>' +
                        '<span class="txt">Thinking…</span><span class="sec">0s</span></div><div class="ai-live-steps"></div>');
        var t0 = Date.now(), steps = [];
        var tick = setInterval(function () { $t.find('.sec').text(Math.round((Date.now() - t0) / 1000) + 's'); }, 1000);
        aborter = window.AbortController ? new AbortController() : null;
        var timeout = setTimeout(function () { if (aborter) { aborter.timedOut = true; aborter.abort(); } }, 240000);

        function finish() { clearInterval(tick); clearTimeout(timeout); aborter = null; setBusy(false); $txt.focus(); }
        function showError(msg, r) {
            $t.remove();
            if (r && r.recharge) { setRecharge(msg); botRow('🔋 ' + esc(msg), 'ai-err'); return; }
            botRow(esc(msg), 'ai-err', (r && r.steps) || steps);
        }
        function handle(ev) {
            if (ev.type === 'ping') return false;   // keep-alive while the AI is thinking
            if (ev.type === 'status') { $t.find('.txt').text(ev.text); down(); }
            else if (ev.type === 'step') {
                steps.push(ev.step);
                $t.find('.ai-live-steps').append('<div class="st"><i class="fas fa-' + esc(ev.step.icon || 'circle') + '"></i> ' + esc(ev.step.label) + '</div>');
                down();
            }
            else if (ev.type === 'done') {
                $t.remove();
                renderCards(botRow(md(ev.reply || ''), null, ev.steps), ev.actions);
                if (ev.usage) showUsage(ev.usage);
                return true;
            }
            else if (ev.type === 'error') { showError(ev.message || 'Error', ev); return true; }
        }

        var payload = {message: text, image: sentImage, auto: $('#aiAuto').is(':checked') ? '1' : '0',
                       mentions: JSON.stringify(sentMentions), stream: '1'};
        if (sentPdf) {
            payload.doc_name = sentPdf.name; payload.doc_pages = sentPdf.pages;
            if (sentPdf.text) payload.doc_text = sentPdf.text; else payload.images = JSON.stringify(sentPdf.images);
        }
        var body = new URLSearchParams(csrf(payload));
        fetch(URL + '/chat', {method: 'POST', body: body, credentials: 'same-origin', signal: aborter ? aborter.signal : undefined,
                              headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(function (res) {
            var ct = res.headers.get('content-type') || '';
            if (ct.indexOf('ndjson') === -1) {   // login expired / PHP error page → normal JSON or HTML
                return res.text().then(function (t) {
                    var j = null; try { j = JSON.parse(t); } catch (e) {}
                    showError((j && j.message) || ('Server error (HTTP ' + res.status + '). Please reload the page.'), j);
                });
            }
            var reader = res.body.getReader(), dec = new TextDecoder(), buf = '', ended = false;
            function pump() {
                return reader.read().then(function (r) {
                    buf += dec.decode(r.value || new Uint8Array(), {stream: !r.done});
                    var lines = buf.split('\n'); buf = r.done ? '' : lines.pop();
                    lines.forEach(function (l) {
                        l = $.trim(l); if (!l || ended) return;
                        var ev; try { ev = JSON.parse(l); } catch (e) { ev = {type: 'error', message: 'Server error: ' + l.replace(/<[^>]+>/g, ' ').slice(0, 300)}; }
                        if (handle(ev)) ended = true;
                    });
                    if (!r.done) return pump();
                    if (!ended) showError('The server stopped before answering. Please try again.');
                });
            }
            return pump();
        })
        .catch(function (e) {
            if (e && e.name === 'AbortError') {
                $t.remove();
                botRow(aborter && aborter.timedOut ? '⏱️ Stopped after 4 minutes without an answer. Please try again.' : '⏹️ Stopped.', 'ai-err', steps);
            } else showError('Could not reach the server. Check your connection and try again.');
        })
        .then(finish, finish);
    }
    $('#aiSend').on('click', send);

    // ── Cards ─────────────────────────────────────────────────────
    var cardTimer;
    $log.on('input', '.f-member-search', function () {
        var $card = $(this).closest('.ai-entry'), q = $.trim($(this).val());
        clearTimeout(cardTimer); if (q.length < 2) return;
        cardTimer = setTimeout(function () {
            $.getJSON(URL + '/suggest', {q: q}, function (r) {
                var ms = (r.items || []).filter(function (x) { return x.kind === 'member'; });
                $card.find('.f-member').html('<option value="">— choose —</option>' + ms.map(function (m) { return opt(m.id, m.label + ' (' + m.sub + ')', ''); }).join(''));
            });
        }, 250);
    });
    $log.on('change', '.f-member', function () {
        var $card = $(this).closest('.ai-entry'), id = $(this).val(); if (!id) return;
        $.getJSON(URL + '/member_options', {member_id: id}, function (r) {
            if (!r.success) return;
            $card.find('.f-loan').html(loanOpts(r.loans, r.loans.length === 1 ? r.loans[0].id : ''));
            $card.find('.f-acc').html(accOpts(r.accounts, r.accounts.length === 1 ? r.accounts[0].id : ''));
        });
    });
    function post(d) { return $.post(URL + '/execute', csrf(d), null, 'json'); }
    function confirmCard($card) {
        if ($card.hasClass('saved') || $card.data('busy')) return $.Deferred().resolve().promise();
        $card.data('busy', 1);
        var $btn = $card.find('.ai-confirm').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        var type = $card.data('type');
        var d = /^(foreclose_loan|send_email|correct_record|passbook_import)$/.test(type) ? {type: type}
            : type === 'reverse_entry'
            ? {type: 'reverse_entry', kind: $card.data('kind'), target_id: $card.data('id'), remarks: $card.find('.f-remarks').val()}
            : type === 'update_member'
            ? {type: 'update_member', member_id: $card.find('.f-member').val(),
               changes: JSON.stringify($card.find('.f-chg').map(function () { return {field: $(this).data('field'), new: $(this).val()}; }).get())}
            : {type: type, member_id: $card.find('.f-member').val(), loan_id: $card.find('.f-loan').val(), payment_type: $card.find('.f-ptype').val(),
               savings_account_id: $card.find('.f-acc').val(), deposit_type: $card.find('.f-dtype').val(), amount: $card.find('.f-amount').val(),
               date: $card.find('.f-date').val(), mode: $card.find('.f-mode').val(), reference: $card.find('.f-ref').val(),
               remarks: $card.find('.f-remarks').val(), status: $card.find('.f-status').val()};
        if ($card.attr('data-token')) d.token = $card.attr('data-token');
        return post(d).done(function (r) {
            if (r.success) $card.replaceWith(savedHtml({result: {message: r.message, data: r.data}}));
            else fail(r.message);
        }).fail(function (x) {
            fail((x.responseJSON && x.responseJSON.message) ||
                 ('Server error (HTTP ' + x.status + ') — nothing was saved. ' + ($('<div>').html(x.responseText || '').text().replace(/\s+/g, ' ').trim().slice(0, 200))));
        })
          .always(function () { $card.removeData('busy'); });
        function fail(m) {
            $btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i>Retry');
            $card.find('.res').html('<span class="ai-err"><i class="fas fa-times-circle mr-1"></i>' + esc(m) + '</span>');
        }
    }
    $log.on('click', '.ai-confirm', function () { confirmCard($(this).closest('.ai-entry')); });
    $log.on('click', '.ai-skip', function () {
        var $card = $(this).closest('.ai-entry'), token = $card.attr('data-token');
        if (token) $.post(URL + '/cancel', csrf({token: token}), null, 'json');
        $card.slideUp(150, function () { $(this).remove(); });
    });
    $log.on('click', '.ai-confirm-all', function () {
        var $b = $(this).prop('disabled', true), cards = $b.closest('.ai-body').find('.ai-entry:not(.saved):not(.undone)').toArray();
        (function next() { if (!cards.length) { $b.remove(); return; } confirmCard($(cards.shift())).always(next); })();
    });
    $log.on('click', '.ai-undo', function () {
        var $b = $(this), $e = $b.closest('.ai-entry');
        if (!confirm('Delete this entry? / ही नोंद काढून टाकायची?')) return;
        $b.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        post({type: 'reverse_entry', kind: $b.data('kind'), target_id: $b.data('id'), remarks: 'Undo from AI chat'}).done(function (r) {
            if (r.success) {
                $e.removeClass('saved').addClass('undone');
                $b.replaceWith('<span class="small text-muted"><i class="fas fa-undo mr-1"></i>' + esc(r.message) + '</span>');
            } else { toastr.error(r.message); $b.prop('disabled', false).html('<i class="fas fa-undo mr-1"></i>Undo'); }
        }).fail(function (x) { toastr.error((x.responseJSON && x.responseJSON.message) || 'Failed'); $b.prop('disabled', false).html('<i class="fas fa-undo mr-1"></i>Undo'); });
    });

    // ── Usage + settings ──────────────────────────────────────────
    function setRecharge(msg) {
        $('#aiRecharge').toggleClass('d-none', !msg).find('span').text(msg || '');
        $('#aiText, #aiSend, #aiMic, #aiImage').prop('disabled', !!msg);
        $txt.attr('placeholder', msg ? 'AI recharge required' : 'काय करायचे ते लिहा… (उदा. योगेश औचरे बचत २००० रोख)');
    }
    function showUsage(u) {
        if (!u) return;
        setRecharge(u.exhausted ? u.recharge_message : '');
    }
    function loadUsage() {
        // Usage figures are not shown on screen any more; only the budget check (recharge lock) stays
        $.getJSON(URL + '/usage', function (u) {
            showUsage(u);
            return;
            var pct = u.cap_inr > 0 ? Math.min(100, u.month_cost_inr / u.cap_inr * 100) : 0;
            var h = '<div class="mb-2"><b>AI usage this month:</b> ' + inr(u.month_cost_inr) + (u.cap_inr > 0 ? ' of ' + inr(u.cap_inr) + ' budget' : ' (no limit)') + '</div>' +
                (u.cap_inr > 0 ? '<div class="ai-meter mb-3"><i style="width:' + pct + '%"></i></div>' : '') +
                '<div>Requests this month: <b>' + u.month_requests + '</b> · Today (you): <b>' + u.today_requests + '</b>' + (u.daily_limit > 0 ? ' / ' + u.daily_limit : '') + '</div>' +
                '<div>Tokens this month: <b>' + Number(u.month_tokens_in).toLocaleString() + '</b> in · <b>' + Number(u.month_tokens_out).toLocaleString() + '</b> out</div>';
            if (u.by_model && u.by_model.length) {
                h += '<table class="table table-sm mt-3 mb-0"><thead><tr><th>Model</th><th class="text-right">Requests</th><th class="text-right">Usage</th></tr></thead><tbody>' +
                    u.by_model.map(function (m) { return '<tr><td>' + esc(m.model) + '</td><td class="text-right">' + m.n + '</td><td class="text-right">' + inr(m.cost_inr) + '</td></tr>'; }).join('') + '</tbody></table>';
            }
            if (!u.tracking) h += '<div class="text-warning mt-2">Run migration 033 to record usage.</div>';
            $('#aiUsageBox').html(h);
        });
    }
    loadUsage();

    function fillModels() {
        $('.model-sel').each(function () {
            var $s = $(this), prov = $s.data('prov'), cur = $s.data('cur'), list = CATALOG[prov] || [];
            var found = list.some(function (m) { return m.id === cur; });
            $s.html(list.map(function (m) {
                return opt(m.id, m.label + (m.tag ? ' — ' + m.tag : ''), cur);
            }).join('') + (!found && cur ? opt(cur, cur + ' (custom)', cur) : ''));
            showPrice($s);
        });
    }
    function showPrice($s) {
        var prov = $s.data('prov'), id = $s.val(), m = (CATALOG[prov] || []).filter(function (x) { return x.id === id; })[0];
        $s.siblings('.ai-price').html(m ? 'List price: $' + m.in + ' in / $' + m.out + ' out per 1M tokens ' +
            '(≈ ' + inr(((1500 * m.in + 400 * m.out) / 1e6) * USD_INR) + ' per typical message)' : 'Custom model — cost not estimated.');
    }
    $(document).on('change', '.model-sel', function () { showPrice($(this)); });
    function toggleProv() { var p = $('#aiProv').val(); $('.prov-box').hide().filter('[data-prov="' + p + '"]').show(); }
    $('#aiProv').on('change', toggleProv);
    if ($('.model-sel').length) { fillModels(); toggleProv(); }

    function formData() { var d = {}; $('#aiSettingsForm').serializeArray().forEach(function (f) { d[f.name] = f.value; }); return d; }
    $('#aiSettingsForm').on('submit', function (e) {
        e.preventDefault();
        $.post(URL + '/save_settings', csrf(formData()), null, 'json').done(function (r) {
            toastr[r.success ? 'success' : 'error'](r.message); if (r.success) setTimeout(function () { location.reload(); }, 600);
        }).fail(function (x) { toastr.error((x.responseJSON && x.responseJSON.message) || 'Failed'); });
    });
    $('#aiTest').on('click', function () {
        $('.nav-tabs a[href="#aiTabProv"]').tab('show');
        $('#aiTestResult').html('<i class="fas fa-spinner fa-spin"></i> testing…');
        $.post(URL + '/test_connection', csrf(formData()), null, 'json')
            .done(function (r) { $('#aiTestResult').html('<span class="text-' + (r.success ? 'success' : 'danger') + '">' + esc(r.message) + '</span>'); })
            .fail(function (x) { $('#aiTestResult').html('<span class="text-danger">' + esc((x.responseJSON && x.responseJSON.message) || 'Failed') + '</span>'); });
    });

    try { if (localStorage.getItem('aiAuto') === '0') $('#aiAuto').prop('checked', false); } catch (e) {}
    $('#aiAuto').on('change', function () { try { localStorage.setItem('aiAuto', this.checked ? '1' : '0'); } catch (e) {} });
    $txt.focus();
});
</script>
