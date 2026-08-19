<?php
/**
 * @var \App\View\AppView $this
 */
?>

<div class="lf-wrap">

  <!-- Logo circular -->
  <div class="lf-logo-ring">
    <div class="lf-logo-circle">
      <?= $this->Html->image('logo.png', ['alt' => 'SAF']) ?>
    </div>
  </div>

  <!-- Título -->
  <h1 class="lf-title">Sistema <span>SAF</span></h1>
  <p class="lf-sub">Ingresa a tu cuenta</p>

  <!-- Flash messages -->
  <?= $this->Flash->render() ?>

  <!-- Formulario -->
  <?= $this->Form->create(null, ['autocomplete' => 'off', 'id' => 'loginForm', 'class' => 'lf-form']) ?>
    <?= $this->Form->hidden('next', ['value' => $next ?? '']) ?>

    <div class="lf-field">
      <svg class="lf-field-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
      <?= $this->Form->control('usuario', [
        'label'        => false,
        'required'     => true,
        'autocomplete' => 'username',
        'placeholder'  => 'Usuario',
        'autofocus'    => true,
        'class'        => 'lf-input',
      ]) ?>
    </div>

    <div class="lf-field">
      <svg class="lf-field-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
      </svg>
      <?= $this->Form->control('password', [
        'label'        => false,
        'required'     => true,
        'type'         => 'password',
        'id'           => 'loginPassword',
        'autocomplete' => 'current-password',
        'placeholder'  => 'Contraseña',
        'class'        => 'lf-input',
      ]) ?>
      <button type="button" id="togglePwd" class="lf-eye" aria-label="Mostrar contraseña">
        <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
        </svg>
      </button>
    </div>

    <button class="lf-btn" id="loginBtn" type="submit">
      <span id="loginBtnText">Iniciar sesión</span>
      <svg id="loginArrow" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
      </svg>
      <svg id="loginSpinner" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24"
        style="display:none;animation:lspin .7s linear infinite">
        <path d="M12 2a10 10 0 1 0 10 10"/>
      </svg>
    </button>
  <?= $this->Form->end() ?>

  <p class="lf-version">v1.0 · SAF</p>
  <p style="margin-top:12px;font-size:13px;color:#64748B;text-align:center">
    ¿No tienes cuenta?
    <a href="<?= $this->Url->build(['controller' => 'Sesiones', 'action' => 'registro']) ?>" style="color:#0E6E7A;font-weight:600;text-decoration:none">Regístrate</a>
  </p>
</div>

<style>
/* ── Form wrapper ── */
.lf-wrap{
  width:380px;max-width:100%;
  display:flex;flex-direction:column;align-items:center;
  position:relative;z-index:1;
}

/* ── Logo ── */
.lf-logo-ring{
  width:96px;height:96px;border-radius:50%;
  background:linear-gradient(135deg,#1B3A8C 0%,#0E6E7A 100%);
  padding:3px;
  box-shadow:0 10px 32px rgba(14,28,64,.25),0 2px 8px rgba(14,28,64,.15);
  margin-bottom:22px;flex:0 0 auto;
}
.lf-logo-circle{
  width:90px;height:90px;border-radius:50%;
  overflow:hidden;background:#fff;
}
.lf-logo-circle img{
  width:90px;height:90px;object-fit:cover;display:block;
}

/* ── Title ── */
.lf-title{
  font-family:Syne,"DM Sans",sans-serif;
  font-size:26px;font-weight:800;
  color:#0E1C40;margin:0 0 4px;letter-spacing:-.6px;text-align:center;
}
.lf-title span{color:#0E6E7A}
.lf-sub{
  font-size:11px;letter-spacing:1.6px;text-transform:uppercase;
  color:#94A3B8;font-family:"DM Mono",monospace;margin:0 0 22px;text-align:center;
}

/* ── Flash ── */
.lf-wrap .flash-message{width:100%}

/* ── Form ── */
.lf-form{width:100%;display:flex;flex-direction:column;gap:12px;margin-top:4px}

/* ── Field with icon ── */
.lf-field{position:relative;display:flex;align-items:center}
.lf-field-icon{
  position:absolute;left:14px;color:#94A3B8;pointer-events:none;z-index:2;flex:0 0 auto;
}
.lf-field .input{margin:0;width:100%}
.lf-field .lf-input,
.lf-field input{
  height:48px !important;
  padding-left:42px !important;
  padding-right:14px !important;
  background:#F1F5FB !important;
  border:1.5px solid #DDE3EE !important;
  border-radius:10px !important;
  font-size:13.5px !important;
  color:#0E1C40 !important;
  width:100%;
  transition:border-color .15s, background .15s;
}
.lf-field input:focus{
  background:#fff !important;
  border-color:#1B3A8C !important;
  outline:none;
}
.lf-field input::placeholder{color:#94A3B8}

/* Password field: extra padding right for eye button */
.lf-field:has(#loginPassword) input{padding-right:46px !important}

/* ── Eye toggle ── */
.lf-eye{
  position:absolute;right:13px;background:none;border:none;cursor:pointer;
  color:#94A3B8;line-height:0;padding:2px;transition:color .15s;z-index:2;
}
.lf-eye:hover{color:#0E1C40}

/* ── Submit button ── */
.lf-btn{
  margin-top:6px;width:100%;height:50px;
  background:#0E1C40;color:white;border:none;
  border-radius:10px;cursor:pointer;
  display:flex;align-items:center;justify-content:center;gap:9px;
  font-family:"DM Sans",sans-serif;font-size:14px;font-weight:600;
  letter-spacing:.2px;transition:background .18s,transform .1s;
}
.lf-btn:hover{background:#1B3A8C}
.lf-btn:active{transform:scale(.98)}
.lf-btn:disabled{opacity:.6;cursor:not-allowed}

/* ── Version ── */
.lf-version{
  margin:20px 0 0;font-size:11px;color:#C0CCDB;
  font-family:"DM Mono",monospace;letter-spacing:.5px;text-align:center;
}

@keyframes lspin{to{transform:rotate(360deg)}}

@media(max-width:600px){
  .lf-wrap{width:100%}
  .auth-right{padding:32px 20px}
}
</style>

<script>
(function(){
  var btn   = document.getElementById('togglePwd');
  var input = document.getElementById('loginPassword');
  var icon  = document.getElementById('eyeIcon');
  var eyeOpen   = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
  var eyeClosed = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
  if (btn && input) {
    btn.addEventListener('click', function(){
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      icon.innerHTML = show ? eyeClosed : eyeOpen;
      btn.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
  }

  var form     = document.getElementById('loginForm');
  var loginBtn = document.getElementById('loginBtn');
  if (form && loginBtn) {
    form.addEventListener('submit', function(){
      loginBtn.disabled = true;
      document.getElementById('loginBtnText').textContent = 'Verificando…';
      document.getElementById('loginArrow').style.display = 'none';
      document.getElementById('loginSpinner').style.display = 'inline';
    });
  }
})();
</script>
