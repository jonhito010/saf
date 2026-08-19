<?php
/**
 * @var \App\View\AppView $this
 */
?>

<div class="lf-wrap lf-wrap--register">

  <div class="lf-logo-ring">
    <div class="lf-logo-circle">
      <?= $this->Html->image('logo.png', ['alt' => 'SAF']) ?>
    </div>
  </div>

  <h1 class="lf-title">Crear <span>cuenta</span></h1>
  <p class="lf-sub">Administra una o varias empresas desde un solo acceso</p>

  <?= $this->Flash->render() ?>

  <div class="reg-card">
    <?= $this->Form->create(null, ['autocomplete' => 'off', 'id' => 'registroForm', 'class' => 'lf-form reg-form']) ?>

      <div class="reg-section">
        <div class="reg-section-label">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Datos personales
        </div>
        <div class="reg-grid">
          <div class="reg-field-block">
            <label class="reg-label" for="nombre">Nombre <span class="reg-req" aria-hidden="true">*</span></label>
            <div class="lf-field">
              <svg class="lf-field-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <?= $this->Form->control('nombre', [
                  'label' => false,
                  'required' => true,
                  'placeholder' => 'Ej. Juan',
                  'class' => 'lf-input',
                  'autofocus' => true,
                  'autocomplete' => 'given-name',
              ]) ?>
            </div>
          </div>
          <div class="reg-field-block">
            <label class="reg-label" for="apellidos">Apellidos <span class="reg-opt">(opcional)</span></label>
            <div class="lf-field">
              <svg class="lf-field-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              <?= $this->Form->control('apellidos', [
                  'label' => false,
                  'required' => false,
                  'placeholder' => 'Ej. Pérez García',
                  'class' => 'lf-input',
                  'autocomplete' => 'family-name',
              ]) ?>
            </div>
          </div>
        </div>
        <div class="reg-field-block reg-field-full">
          <label class="reg-label" for="email">Correo electrónico <span class="reg-req" aria-hidden="true">*</span></label>
          <div class="lf-field">
            <svg class="lf-field-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <?= $this->Form->control('email', [
                'label' => false,
                'type' => 'email',
                'required' => true,
                'placeholder' => 'tu@empresa.com',
                'class' => 'lf-input',
                'autocomplete' => 'email',
            ]) ?>
          </div>
        </div>
      </div>

      <div class="reg-section">
        <div class="reg-section-label">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          Contraseña de acceso
        </div>
        <div class="reg-field-block reg-field-full">
          <label class="reg-label" for="registroPassword">Contraseña <span class="reg-req" aria-hidden="true">*</span></label>
          <div class="lf-field lf-field-pwd">
            <svg class="lf-field-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <?= $this->Form->control('password', [
                'label' => false,
                'type' => 'password',
                'id' => 'registroPassword',
                'required' => true,
                'placeholder' => 'Mínimo 8 caracteres',
                'class' => 'lf-input',
                'autocomplete' => 'new-password',
            ]) ?>
            <button type="button" id="togglePwd" class="lf-eye" aria-label="Mostrar contraseña">
              <svg id="eyeIconPwd" xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>
        <div class="reg-pwd-meter" aria-hidden="true">
          <div class="reg-pwd-meter-bar" id="pwdMeterBar"></div>
        </div>
        <p class="reg-pwd-hint" id="pwdHint">Mínimo 8 caracteres</p>

        <div class="reg-field-block reg-field-full">
          <label class="reg-label" for="registroPasswordConfirm">Confirmar contraseña <span class="reg-req" aria-hidden="true">*</span></label>
          <div class="lf-field lf-field-pwd">
            <svg class="lf-field-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <?= $this->Form->control('password_confirm', [
                'label' => false,
                'type' => 'password',
                'id' => 'registroPasswordConfirm',
                'required' => true,
                'placeholder' => 'Repite tu contraseña',
                'class' => 'lf-input',
                'autocomplete' => 'new-password',
            ]) ?>
            <button type="button" id="togglePwdConfirm" class="lf-eye" aria-label="Mostrar contraseña">
              <svg id="eyeIconPwdConfirm" xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>
        <p class="reg-match-hint" id="matchHint"></p>
      </div>

      <button class="lf-btn" id="registroBtn" type="submit">
        <span id="registroBtnText">Crear cuenta</span>
        <svg id="registroArrow" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
        </svg>
        <svg id="registroSpinner" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24" style="display:none">
          <path d="M12 2a10 10 0 1 0 10 10"/>
        </svg>
      </button>
    <?= $this->Form->end() ?>
  </div>

  <p class="reg-footer">
    ¿Ya tienes cuenta?
    <a href="<?= $this->Url->build(['controller' => 'Sesiones', 'action' => 'login']) ?>">Inicia sesión</a>
  </p>

  <p class="lf-version">v1.0 · SAF</p>
</div>

<style>
.lf-wrap--register {
  width: 440px;
  max-width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  z-index: 1;
}

.lf-logo-ring {
  width: 88px;
  height: 88px;
  border-radius: 50%;
  background: linear-gradient(135deg, #1B3A8C 0%, #0E6E7A 100%);
  padding: 3px;
  box-shadow: 0 10px 32px rgba(14, 28, 64, .22), 0 2px 8px rgba(14, 28, 64, .12);
  margin-bottom: 18px;
  flex: 0 0 auto;
}

.lf-logo-circle {
  width: 82px;
  height: 82px;
  border-radius: 50%;
  overflow: hidden;
  background: #fff;
}

.lf-logo-circle img {
  width: 82px;
  height: 82px;
  object-fit: cover;
  display: block;
}

.lf-title {
  font-family: Syne, "DM Sans", sans-serif;
  font-size: 26px;
  font-weight: 800;
  color: #0E1C40;
  margin: 0 0 4px;
  letter-spacing: -.6px;
  text-align: center;
}

.lf-title span { color: #0E6E7A; }

.lf-sub {
  font-size: 11px;
  letter-spacing: 1.4px;
  text-transform: uppercase;
  color: #94A3B8;
  font-family: "DM Mono", monospace;
  margin: 0 0 18px;
  text-align: center;
  max-width: 320px;
  line-height: 1.5;
}

.lf-wrap .flash-message { width: 100%; margin-bottom: 12px; }

.reg-card {
  width: 100%;
  background: #fff;
  border: 1px solid #E8EDF5;
  border-radius: 16px;
  padding: 22px 20px 20px;
  box-shadow: 0 8px 32px rgba(14, 28, 64, .06), 0 1px 3px rgba(14, 28, 64, .04);
}

.reg-section + .reg-section {
  margin-top: 18px;
  padding-top: 18px;
  border-top: 1px solid #EEF2F8;
}

.reg-section-label {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 10px;
  font-weight: 600;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: #64748B;
  margin-bottom: 12px;
}

.reg-section-label svg { color: #0E6E7A; flex-shrink: 0; }

.reg-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px 10px;
}

.reg-field-block {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.reg-field-block.reg-field-full,
.reg-grid .reg-field-block { min-width: 0; }

.reg-field-full { grid-column: 1 / -1; }

.reg-label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #334155;
  line-height: 1.3;
  cursor: pointer;
}

.reg-req {
  color: #DC2626;
  font-weight: 700;
}

.reg-opt {
  font-size: 11px;
  font-weight: 500;
  color: #94A3B8;
}

.reg-grid + .reg-field-block {
  margin-top: 12px;
}

.lf-form { width: 100%; display: flex; flex-direction: column; gap: 0; }

.lf-field {
  position: relative;
  display: flex;
  align-items: center;
}

.lf-field-icon {
  position: absolute;
  left: 14px;
  color: #94A3B8;
  pointer-events: none;
  z-index: 2;
}

.lf-field .input { margin: 0; width: 100%; }

.lf-field .lf-input,
.lf-field input {
  height: 46px !important;
  padding-left: 42px !important;
  padding-right: 14px !important;
  background: #F1F5FB !important;
  border: 1.5px solid #DDE3EE !important;
  border-radius: 10px !important;
  font-size: 13.5px !important;
  color: #0E1C40 !important;
  width: 100%;
  transition: border-color .15s, background .15s, box-shadow .15s;
}

.lf-field input:focus {
  background: #fff !important;
  border-color: #1B3A8C !important;
  outline: none;
  box-shadow: 0 0 0 3px rgba(27, 58, 140, .08);
}

.lf-field input::placeholder { color: #94A3B8; }

.lf-field-pwd input { padding-right: 46px !important; }

.lf-eye {
  position: absolute;
  right: 13px;
  background: none;
  border: none;
  cursor: pointer;
  color: #94A3B8;
  line-height: 0;
  padding: 2px;
  transition: color .15s;
  z-index: 2;
}

.lf-eye:hover { color: #0E1C40; }

.reg-pwd-meter {
  height: 4px;
  background: #E8EDF5;
  border-radius: 99px;
  margin: 8px 0 4px;
  overflow: hidden;
}

.reg-pwd-meter-bar {
  height: 100%;
  width: 0;
  border-radius: 99px;
  background: #CBD5E1;
  transition: width .2s ease, background .2s ease;
}

.reg-pwd-meter-bar.weak { width: 33%; background: #EF4444; }
.reg-pwd-meter-bar.fair { width: 66%; background: #F59E0B; }
.reg-pwd-meter-bar.good { width: 100%; background: #10B981; }

.reg-pwd-hint,
.reg-match-hint {
  font-size: 11px;
  color: #94A3B8;
  margin: 0 0 10px;
  min-height: 16px;
}

.reg-match-hint.ok { color: #059669; }
.reg-match-hint.bad { color: #DC2626; }

.lf-btn {
  margin-top: 16px;
  width: 100%;
  height: 50px;
  background: linear-gradient(135deg, #0E1C40 0%, #1B3A8C 100%);
  color: white;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  font-family: "DM Sans", sans-serif;
  font-size: 14px;
  font-weight: 600;
  letter-spacing: .2px;
  transition: opacity .18s, transform .1s, box-shadow .18s;
  box-shadow: 0 4px 14px rgba(14, 28, 64, .2);
}

.lf-btn:hover {
  opacity: .95;
  box-shadow: 0 6px 20px rgba(14, 28, 64, .28);
}

.lf-btn:active { transform: scale(.98); }
.lf-btn:disabled { opacity: .6; cursor: not-allowed; }

#registroSpinner { animation: lspin .7s linear infinite; }

.reg-footer {
  margin: 18px 0 0;
  font-size: 13px;
  color: #64748B;
  text-align: center;
}

.reg-footer a {
  color: #0E6E7A;
  font-weight: 600;
  text-decoration: none;
  margin-left: 4px;
}

.reg-footer a:hover { text-decoration: underline; }

.lf-version {
  margin: 14px 0 0;
  font-size: 11px;
  color: #C0CCDB;
  font-family: "DM Mono", monospace;
  letter-spacing: .5px;
  text-align: center;
}

@keyframes lspin { to { transform: rotate(360deg); } }

@media (max-width: 520px) {
  .lf-wrap--register { width: 100%; }
  .reg-grid { grid-template-columns: 1fr; }
  .reg-card { padding: 18px 16px 16px; border-radius: 14px; }
  .auth-right { padding: 28px 16px !important; }
}
</style>

<script>
(function () {
  var eyeOpen = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
  var eyeClosed = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';

  function bindToggle(btnId, inputId, iconId) {
    var btn = document.getElementById(btnId);
    var input = document.getElementById(inputId);
    var icon = document.getElementById(iconId);
    if (!btn || !input || !icon) return;

    btn.addEventListener('click', function () {
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      icon.innerHTML = show ? eyeClosed : eyeOpen;
      btn.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
  }

  bindToggle('togglePwd', 'registroPassword', 'eyeIconPwd');
  bindToggle('togglePwdConfirm', 'registroPasswordConfirm', 'eyeIconPwdConfirm');

  var pwd = document.getElementById('registroPassword');
  var pwdConfirm = document.getElementById('registroPasswordConfirm');
  var meterBar = document.getElementById('pwdMeterBar');
  var pwdHint = document.getElementById('pwdHint');
  var matchHint = document.getElementById('matchHint');

  function scorePassword(value) {
    if (!value) return 0;
    var score = 0;
    if (value.length >= 8) score++;
    if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score++;
    if (/\d/.test(value)) score++;
    if (/[^A-Za-z0-9]/.test(value)) score++;
    return score;
  }

  function updatePwdMeter() {
    if (!pwd || !meterBar || !pwdHint) return;
    var val = pwd.value || '';
    var score = scorePassword(val);
    meterBar.className = 'reg-pwd-meter-bar';
    if (val.length === 0) {
      pwdHint.textContent = 'Mínimo 8 caracteres';
      return;
    }
    if (val.length < 8) {
      meterBar.classList.add('weak');
      pwdHint.textContent = 'Usa al menos 8 caracteres';
      return;
    }
    if (score <= 2) {
      meterBar.classList.add('weak');
      pwdHint.textContent = 'Contraseña débil — agrega números o símbolos';
    } else if (score === 3) {
      meterBar.classList.add('fair');
      pwdHint.textContent = 'Contraseña aceptable';
    } else {
      meterBar.classList.add('good');
      pwdHint.textContent = 'Contraseña segura';
    }
  }

  function updateMatchHint() {
    if (!pwd || !pwdConfirm || !matchHint) return;
    if (!pwdConfirm.value) {
      matchHint.textContent = '';
      matchHint.className = 'reg-match-hint';
      return;
    }
    if (pwd.value === pwdConfirm.value) {
      matchHint.textContent = 'Las contraseñas coinciden';
      matchHint.className = 'reg-match-hint ok';
    } else {
      matchHint.textContent = 'Las contraseñas no coinciden';
      matchHint.className = 'reg-match-hint bad';
    }
  }

  if (pwd) pwd.addEventListener('input', function () { updatePwdMeter(); updateMatchHint(); });
  if (pwdConfirm) pwdConfirm.addEventListener('input', updateMatchHint);

  var form = document.getElementById('registroForm');
  var btn = document.getElementById('registroBtn');
  if (form && btn) {
    form.addEventListener('submit', function () {
      btn.disabled = true;
      document.getElementById('registroBtnText').textContent = 'Creando cuenta…';
      document.getElementById('registroArrow').style.display = 'none';
      document.getElementById('registroSpinner').style.display = 'inline';
    });
  }
})();
</script>
