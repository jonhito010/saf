<?php
/**
 * Layout para login — estilo FacturaLo adaptado a SAF
 * @var \App\View\AppView $this
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SAF — Sistema de cumplimiento normativo</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <?= $this->Html->css('saf') ?>
  <style>
    *,*::before,*::after{box-sizing:border-box}
    body{margin:0;min-height:100vh;font-family:"DM Sans",system-ui,sans-serif;background:#F0F4FB}

    /* ── Shell ── */
    .auth-shell{min-height:100vh;display:flex;align-items:stretch}

    /* ══════════════════════════════════════
       LEFT PANEL — dark navy
    ══════════════════════════════════════ */
    .auth-left{
      display:none;
      flex:0 0 52%;
      background:linear-gradient(160deg,#060E1F 0%,#0E1C40 38%,#0A3356 70%,#083244 100%);
      position:relative;overflow:hidden;
      padding:40px 52px;
      flex-direction:column;justify-content:space-between;
    }
    @media(min-width:920px){.auth-left{display:flex}}

    /* Decorative rings */
    .auth-left::before{content:'';position:absolute;border-radius:50%;pointer-events:none;
      width:560px;height:560px;border:1px solid rgba(255,255,255,.05);top:-180px;right:-180px}
    .auth-left::after{content:'';position:absolute;border-radius:50%;pointer-events:none;
      width:360px;height:360px;border:1px solid rgba(43,191,179,.07);bottom:-120px;left:-80px}
    .al-ring2{position:absolute;width:240px;height:240px;border-radius:50%;
      border:1px solid rgba(255,255,255,.04);bottom:200px;right:-60px;pointer-events:none}

    /* Top brand */
    .al-top{display:flex;align-items:center;gap:14px;position:relative;z-index:1}
    .al-logo-box{
      width:44px;height:44px;border-radius:12px;overflow:hidden;
      background:white;
      box-shadow:0 4px 14px rgba(0,0,0,.3);
      flex:0 0 auto;display:flex;align-items:center;justify-content:center;
    }
    .al-logo-box img{width:38px;height:38px;object-fit:cover;border-radius:9px}
    .al-brand{font-family:Syne,sans-serif;font-size:18px;font-weight:800;
      color:white;letter-spacing:-.3px}
    .al-brand span{color:#2EC4B6;font-weight:400;font-size:13px;
      display:block;letter-spacing:.5px;font-family:"DM Mono",monospace;margin-top:-2px}

    /* Badge */
    .al-badge{
      display:inline-flex;align-items:center;gap:7px;
      background:rgba(46,196,182,.12);border:1px solid rgba(46,196,182,.25);
      border-radius:20px;padding:5px 14px 5px 10px;
      font-size:11px;font-weight:600;color:#2EC4B6;
      font-family:"DM Mono",monospace;letter-spacing:.8px;
      text-transform:uppercase;margin-bottom:20px;
    }
    .al-badge-dot{width:7px;height:7px;border-radius:50%;background:#2EC4B6;
      box-shadow:0 0 0 2px rgba(46,196,182,.3)}

    /* Hero text */
    .al-center{position:relative;z-index:1}
    .al-headline{
      font-family:Syne,sans-serif;
      font-size:42px;font-weight:800;line-height:1.12;
      color:white;margin:0 0 6px;letter-spacing:-1px;
    }
    .al-headline-accent{color:#2EC4B6;display:block}
    .al-desc{font-size:13.5px;line-height:1.7;color:rgba(255,255,255,.5);
      max-width:320px;margin:14px 0 32px}

    /* Feature cards */
    .al-cards{display:flex;flex-direction:column;gap:10px}
    .al-card{
      display:flex;align-items:flex-start;gap:14px;
      background:rgba(255,255,255,.05);
      border:1px solid rgba(255,255,255,.08);
      border-radius:14px;padding:14px 18px;
      backdrop-filter:blur(4px);
    }
    .al-card-icon{
      width:36px;height:36px;border-radius:10px;
      background:rgba(46,196,182,.15);
      border:1px solid rgba(46,196,182,.2);
      display:flex;align-items:center;justify-content:center;
      color:#2EC4B6;flex:0 0 auto;
    }
    .al-card-title{font-size:13px;font-weight:600;color:white;margin:0 0 2px}
    .al-card-desc{font-size:12px;color:rgba(255,255,255,.45);margin:0;line-height:1.5}

    /* Footer */
    .al-footer{position:relative;z-index:1;display:flex;gap:20px;align-items:center;flex-wrap:wrap}
    .al-footer-chip{
      background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);
      border-radius:20px;padding:5px 14px;
      font-size:11px;color:rgba(255,255,255,.4);
      font-family:"DM Mono",monospace;letter-spacing:.3px;
    }

    /* ══════════════════════════════════════
       RIGHT PANEL — white
    ══════════════════════════════════════ */
    .auth-right{
      flex:1;background:#fff;
      display:flex;align-items:center;justify-content:center;
      padding:40px 24px;
      position:relative;
    }
    .auth-right::before{
      content:'';position:absolute;inset:0;pointer-events:none;
      background:radial-gradient(500px at 50% 0%,rgba(27,58,140,.04),transparent 60%);
    }
  </style>
</head>
<body>
  <div class="auth-shell">

    <!-- ── Panel izquierdo ── -->
    <aside class="auth-left" aria-hidden="true">
      <div class="al-ring2"></div>

      <!-- Top brand row -->
      <div class="al-top">
        <div class="al-logo-box">
          <?= $this->Html->image('logo.png', ['alt' => 'SAF', 'style' => 'width:38px;height:38px;object-fit:cover;border-radius:9px']) ?>
        </div>
        <div class="al-brand">
          Sistema SAF
          <span>Cumplimiento normativo</span>
        </div>
      </div>

      <!-- Hero -->
      <div class="al-center">
        <div class="al-badge">
          <div class="al-badge-dot"></div>
          Sistema de cumplimiento normativo
        </div>
        <h2 class="al-headline">
          Documentación de empresa<br>
          <span class="al-headline-accent">y vehículos al día.</span>
        </h2>
        <p class="al-desc">
          Controla permisos, pólizas, inspecciones FM/E y expediente corporativo. Carta Porte y CFDI quedan como módulo extra.
        </p>

        <!-- Feature cards -->
        <div class="al-cards">
          <div class="al-card">
            <div class="al-card-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
              </svg>
            </div>
            <div>
              <p class="al-card-title">Expediente de empresa</p>
              <p class="al-card-desc">Actas, permiso SICT, RNAF, opinión SAT y más en un solo lugar</p>
            </div>
          </div>

          <div class="al-card">
            <div class="al-card-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
              </svg>
            </div>
            <div>
              <p class="al-card-title">Documentos por vehículo</p>
              <p class="al-card-desc">Circulación, seguro, verificación e inspección con alertas de vencimiento</p>
            </div>
          </div>

          <div class="al-card">
            <div class="al-card-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
              </svg>
            </div>
            <div>
              <p class="al-card-title">Carta Porte (extra)</p>
              <p class="al-card-desc">Complemento de transporte CFDI 4.0 cuando lo necesites timbrar</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="al-footer">
        <div class="al-footer-chip">v1.0 · SAF</div>
        <div class="al-footer-chip">Sistema de cumplimiento normativo</div>
      </div>
    </aside>

    <!-- ── Panel derecho ── -->
    <section class="auth-right">
      <?= $this->fetch('content') ?>
    </section>

  </div>
</body>
</html>
