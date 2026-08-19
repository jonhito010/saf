// SAF — UI Helpers

// Tab switching (vehicle detail page)
function showTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tabId).classList.add('active');
    btn.classList.add('active');
}

// Sidebar móvil
function initSidebarMobile() {
    const app = document.getElementById('saf-app');
    const toggle = document.getElementById('sidebar-toggle');
    const backdrop = document.getElementById('sidebar-backdrop');
    const sidebar = document.getElementById('saf-sidebar');
    if (!app || !toggle || !backdrop || !sidebar) {
        return;
    }

    const mq = window.matchMedia('(max-width: 900px)');
    const setOpen = function (open) {
        app.classList.toggle('sidebar-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
        document.body.style.overflow = open && mq.matches ? 'hidden' : '';
    };

    toggle.addEventListener('click', function () {
        setOpen(!app.classList.contains('sidebar-open'));
    });
    backdrop.addEventListener('click', function () {
        setOpen(false);
    });
    sidebar.querySelectorAll('.nav-item').forEach(function (link) {
        link.addEventListener('click', function () {
            if (mq.matches) {
                setOpen(false);
            }
        });
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && app.classList.contains('sidebar-open')) {
            setOpen(false);
        }
    });
    mq.addEventListener('change', function () {
        if (!mq.matches) {
            setOpen(false);
        }
    });
}

function resolveSafAppBase() {
    const baseEl = document.querySelector('base');
    const baseHref = baseEl && baseEl.getAttribute('href');
    if (baseHref) {
        return baseHref.replace(/\/$/, '');
    }
    const script = document.querySelector('script[src*="/js/saf.js"], script[src$="saf.js"]');
    if (script) {
        const src = script.getAttribute('src') || '';
        const idx = src.indexOf('/js/');
        if (idx > 0) {
            return src.substring(0, idx);
        }
    }
    const path = window.location.pathname || '';
    if (path === '/saf' || path.startsWith('/saf/')) {
        return '/saf';
    }
    return '';
}

function initTopbarNotificaciones() {
    const wrap = document.getElementById('saf-notif-wrap');
    const btn = document.getElementById('saf-notif-btn');
    const panel = document.getElementById('saf-notif-panel');
    const badge = document.getElementById('saf-notif-count');
    const itemsWrap = document.getElementById('saf-notif-items');
    const headText = document.getElementById('saf-notif-head-text');
    const markAllBtn = document.getElementById('saf-notif-mark-all');
    if (!wrap || !btn || !panel || !itemsWrap) {
        return;
    }

    // Preferir URLs generadas por Cake (/saf/...). Sin <base>, el path absoluto
    // /notificaciones/... salía del app y devolvía HTML → "No se pudieron cargar".
    const appBase = resolveSafAppBase();
    const panelUrl = wrap.getAttribute('data-panel-url')
        || ((appBase || '') + '/notificaciones/panel');
    const marcarUrl = wrap.getAttribute('data-marcar-url')
        || ((appBase || '') + '/notificaciones/marcar-leida');
    const marcarTodasUrl = wrap.getAttribute('data-marcar-todas-url')
        || ((appBase || '') + '/notificaciones/marcar-todas');
    let pollTimer = null;

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrfToken"]');
        if (meta && meta.getAttribute('content')) {
            return meta.getAttribute('content');
        }
        const match = document.cookie.match(/(?:^|;\s*)csrfToken=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : '';
    }

    function postJson(url, body) {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };
        const csrf = getCsrfToken();
        if (csrf) {
            headers['X-CSRF-Token'] = csrf;
        }
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: headers,
            body: JSON.stringify(body || {}),
        });
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatCreated(iso) {
        if (!iso) return '';
        try {
            const d = new Date(iso.replace(' ', 'T'));
            if (Number.isNaN(d.getTime())) return iso;
            return d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
        } catch (e) {
            return iso;
        }
    }

    function updateBadge(total) {
        if (!badge) return;
        const n = parseInt(total, 10) || 0;
        badge.textContent = n > 99 ? '99+' : String(n);
        badge.classList.toggle('hidden', n <= 0);
    }

    function renderItems(items) {
        if (!items || items.length === 0) {
            itemsWrap.innerHTML = '<div class="topbar-notif-empty">Sin notificaciones pendientes</div>';
            return;
        }
        itemsWrap.innerHTML = items.map(function (item) {
            const leida = item.leida ? ' is-read' : '';
            let href = item.url || '#';
            if (href.charAt(0) === '/' && appBase && href.indexOf(appBase + '/') !== 0 && href !== appBase) {
                href = appBase + href;
            }
            return (
                '<a class="topbar-notif-item' + leida + '" href="' + escapeHtml(href) + '" role="menuitem" data-id="' + escapeHtml(item.id) + '">' +
                '<div class="topbar-notif-title">' + escapeHtml(item.titulo || item.mensaje || 'Notificación') + '</div>' +
                (item.mensaje && item.mensaje !== item.titulo
                    ? '<div class="topbar-notif-msg">' + escapeHtml(item.mensaje) + '</div>'
                    : '') +
                '<div class="topbar-notif-meta">' + escapeHtml(formatCreated(item.created)) + '</div>' +
                '</a>'
            );
        }).join('');

        itemsWrap.querySelectorAll('.topbar-notif-item[data-id]').forEach(function (link) {
            link.addEventListener('click', function (ev) {
                const id = parseInt(link.getAttribute('data-id'), 10);
                if (!id || link.classList.contains('is-read')) {
                    return;
                }
                postJson(marcarUrl, { id: id }).then(function () {
                    link.classList.add('is-read');
                    const current = parseInt(badge.textContent, 10) || 0;
                    updateBadge(Math.max(0, current - 1));
                }).catch(function () { /* navegar igual */ });
            });
        });
    }

    function loadPanel() {
        return fetch(panelUrl, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (r) {
                const ctype = (r.headers.get('content-type') || '').toLowerCase();
                if (!r.ok || ctype.indexOf('application/json') === -1) {
                    throw new Error('Respuesta no JSON (' + r.status + ')');
                }
                return r.json();
            })
            .then(function (data) {
                const total = data.total || 0;
                updateBadge(total);
                if (headText) {
                    headText.textContent = total > 0
                        ? 'Notificaciones · ' + total + ' sin leer'
                        : 'Notificaciones';
                }
                renderItems(data.items || []);
            })
            .catch(function () {
                itemsWrap.innerHTML = '<div class="topbar-notif-empty">No se pudieron cargar las notificaciones</div>';
            });
    }

    const cerrar = function () {
        panel.classList.add('hidden');
        btn.setAttribute('aria-expanded', 'false');
    };

    btn.addEventListener('click', function (ev) {
        ev.stopPropagation();
        const abierto = !panel.classList.contains('hidden');
        if (abierto) {
            cerrar();
        } else {
            panel.classList.remove('hidden');
            btn.setAttribute('aria-expanded', 'true');
            loadPanel();
        }
    });

    document.addEventListener('click', function (ev) {
        if (!wrap.contains(ev.target)) {
            cerrar();
        }
    });

    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') {
            cerrar();
        }
    });

    if (markAllBtn) {
        markAllBtn.addEventListener('click', function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
            postJson(marcarTodasUrl, {})
                .then(function () { return loadPanel(); })
                .catch(function () { /* silencioso */ });
        });
    }

    loadPanel();
    pollTimer = window.setInterval(loadPanel, 60000);
    window.addEventListener('beforeunload', function () {
        if (pollTimer) {
            clearInterval(pollTimer);
        }
    });
}

// Auto-dismiss flash messages
document.addEventListener('DOMContentLoaded', function () {
    initSidebarMobile();
    initTopbarNotificaciones();
    const flashes = document.querySelectorAll('.flash-message');
    flashes.forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 4000);
    });

    function cerrarSafModal(modal) {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        if (!document.querySelector('.saf-modal.is-open')) {
            document.body.style.overflow = '';
        }
    }

    function abrirSafModal(modal) {
        if (!modal) return;
        document.querySelectorAll('.saf-modal.is-open').forEach(function (m) {
            if (m !== modal) cerrarSafModal(m);
        });
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    document.querySelectorAll('[data-saf-modal-open]').forEach(function (btn) {
        btn.addEventListener('click', function (ev) {
            ev.preventDefault();
            const id = btn.getAttribute('data-saf-modal-open');
            if (!id) return;
            abrirSafModal(document.getElementById(id));
        });
    });

    document.querySelectorAll('.saf-modal').forEach(function (modal) {
        modal.querySelectorAll('[data-saf-modal-close]').forEach(function (el) {
            el.addEventListener('click', function () {
                cerrarSafModal(modal);
            });
        });
    });

    document.addEventListener('keydown', function (ev) {
        if (ev.key !== 'Escape') return;
        const abierto = document.querySelector('.saf-modal.is-open');
        if (abierto) cerrarSafModal(abierto);
    });

    const modalVencidos = document.getElementById('saf-modal-vencidos');
    if (modalVencidos) {
        abrirSafModal(modalVencidos);
    }

    function normalizarTelefonoWa(raw) {
        let digits = String(raw || '').replace(/\D+/g, '');
        if (!digits) return '';
        if (digits.length === 10) {
            digits = '52' + digits;
        } else if (digits.length === 11 && digits.charAt(0) === '1') {
            digits = '52' + digits.slice(1);
        } else if (digits.length === 12 && digits.indexOf('52') === 0) {
            // ya con lada MX
        } else if (digits.length === 13 && digits.indexOf('521') === 0) {
            // móvil MX con 521
        }
        return digits;
    }

    function mensajeVencimiento(btn) {
        const empresa = btn.getAttribute('data-empresa') || 'su empresa';
        const base = btn.getAttribute('data-mensaje')
            || 'Hola, le contactamos desde SAF respecto a {empresa}. Tiene documentos próximos a vencer o vencidos. Por favor revise su expediente a la brevedad.';
        return base.replace(/\{empresa\}/g, empresa);
    }

    document.querySelectorAll('[data-dash-notify-wa]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (btn.disabled) return;
            const tel = normalizarTelefonoWa(btn.getAttribute('data-telefono'));
            if (!tel) {
                window.alert('La empresa no tiene teléfono registrado.');
                return;
            }
            const texto = encodeURIComponent(mensajeVencimiento(btn));
            window.open('https://wa.me/' + tel + '?text=' + texto, '_blank', 'noopener,noreferrer');
        });
    });

    const mailModal = document.getElementById('dash-modal-correo');
    const mailPrimary = document.getElementById('dash-mail-primary');
    const mailFields = document.getElementById('dash-mail-fields');
    const mailAdd = document.getElementById('dash-mail-add');
    const mailSend = document.getElementById('dash-mail-send');
    const mailError = document.getElementById('dash-mail-error');
    const mailEmpresaLabel = document.getElementById('dash-mail-empresa-label');
    let mailMensaje = '';

    function limpiarCorreosExtra() {
        if (!mailFields) return;
        mailFields.querySelectorAll('.dash-mail-field--extra').forEach(function (el) {
            el.remove();
        });
    }

    function mostrarErrorMail(msg) {
        if (!mailError) return;
        if (!msg) {
            mailError.hidden = true;
            mailError.textContent = '';
            return;
        }
        mailError.hidden = false;
        mailError.textContent = msg;
    }

    function emailValido(v) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(v || '').trim());
    }

    document.querySelectorAll('[data-dash-notify-mail]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (btn.disabled || !mailModal || !mailPrimary) return;
            const email = (btn.getAttribute('data-email') || '').trim();
            if (!email) {
                window.alert('La empresa no tiene correo registrado.');
                return;
            }
            limpiarCorreosExtra();
            mostrarErrorMail('');
            mailPrimary.value = email;
            mailMensaje = mensajeVencimiento(btn);
            if (mailEmpresaLabel) {
                mailEmpresaLabel.textContent = btn.getAttribute('data-empresa') || 'Empresa';
            }
            abrirSafModal(mailModal);
        });
    });

    if (mailAdd && mailFields) {
        mailAdd.addEventListener('click', function () {
            const wrap = document.createElement('div');
            wrap.className = 'dash-mail-field dash-mail-field--extra';
            const id = 'dash-mail-extra-' + Date.now();
            wrap.innerHTML =
                '<label for="' + id + '">Otro correo</label>' +
                '<div class="dash-mail-extra-row">' +
                '<input type="email" id="' + id + '" class="dash-mail-input dash-mail-extra" placeholder="correo@ejemplo.com">' +
                '<button type="button" class="btn btn-ghost btn-sm dash-mail-remove" aria-label="Quitar correo">×</button>' +
                '</div>';
            mailFields.appendChild(wrap);
            const removeBtn = wrap.querySelector('.dash-mail-remove');
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    wrap.remove();
                });
            }
            const input = wrap.querySelector('input');
            if (input) input.focus();
        });
    }

    if (mailSend) {
        mailSend.addEventListener('click', function () {
            const correos = [];
            if (mailPrimary && mailPrimary.value.trim()) {
                correos.push(mailPrimary.value.trim());
            }
            document.querySelectorAll('.dash-mail-extra').forEach(function (inp) {
                const v = inp.value.trim();
                if (v) correos.push(v);
            });
            if (correos.length === 0) {
                mostrarErrorMail('Indica al menos un correo.');
                return;
            }
            for (let i = 0; i < correos.length; i++) {
                if (!emailValido(correos[i])) {
                    mostrarErrorMail('Revisa el correo: ' + correos[i]);
                    return;
                }
            }
            mostrarErrorMail('');
            const empresa = mailEmpresaLabel ? mailEmpresaLabel.textContent : 'empresa';
            const subject = encodeURIComponent('Aviso de vencimientos — ' + empresa);
            const body = encodeURIComponent(mailMensaje || '');
            const to = correos.map(function (c) { return encodeURIComponent(c); }).join(',');
            window.location.href = 'mailto:' + to + '?subject=' + subject + '&body=' + body;
            cerrarSafModal(mailModal);
        });
    }
});
