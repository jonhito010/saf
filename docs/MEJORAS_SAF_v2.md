# SAF — Mejoras funcionales v2

**Fecha:** 2026-07-28  
**Stack:** CakePHP 5.3 · PHP ≥ 8.2 · MySQL · templates PHP + `saf.css` / `saf.js`  
**Estado:** Implementado en código (aplicar SQL antes de usar en producción)

## 1. Estado previo (resumen)

| Área | Situación previa | Resolución |
|------|------------------|------------|
| Superadmin (`rol_id=1`) | Existía parcial | Panel `/cuentas` (crear, suspender, activar) |
| Multiempresa | N:M vía `usuario_empresas` | Conservado; UI selector empresa activa |
| Buscador | LIKE parcial en `/buscar` | CONCAT nombre completo + listados con `q` |
| Campana | Panel estático | JSON `/notificaciones/panel`, badge, marcar leídas, auto-refresh 60s |
| Alertas | Umbrales fijos 45/30/15 | Catálogo `alertas_reglas` + agrupación + deep-links |
| Operadores | Soft-delete `inactivo` | Estados activo/inactivo/suspendido/baja + validaciones |
| Tipos documento | Catálogo BD | Solo lectura + import CSV |
| Reportes / export | No existía | `/reportes` + CSV/XLSX + export completo |
| Auditoría | Timestamps parciales | `sistema_logs` + `auditoria_cambios` |
| Verificaciones | No existía | `/verificaciones` historial permanente |

## 2. Diseño de base de datos

Script: `config/schema/patch_mejoras_v2.sql`

### Cómo aplicar

Desde la raíz del proyecto:

```bash
cd /var/www/html/saf
php bin/aplicar_mejoras_v2.php
```

Verificar después:

```bash
php bin/verificar_mejoras_v2.php
```

Alternativa manual (requiere cliente `mysql`):

```bash
mysql -u admin -p saf < config/schema/patch_mejoras_v2.sql
```

Tablas nuevas: `alertas_reglas`, `notificaciones_flujo`, `notificaciones`, `verificaciones_historial`, `auditoria_cambios`, `sistema_logs`.  
Alteraciones: usuarios (suspensión), operadores (baja_*), alertas_cumplimiento (leida, url_destino, labels), tipos_documento.editable=0.

CSV de ejemplo para catálogo: `config/schema/tipos_documento_import_ejemplo.csv`

## 3. Mapa objetivo → implementación

| # | Objetivo | Estado | Ubicación clave |
|---|----------|--------|-----------------|
| 1 | Super Administrador | ✅ | `CuentasController`, rol_id=1 |
| 2 | Empresas por cuenta | ✅ (ya N:M) | `UsuarioEmpresaService` |
| 3 | Buscador inteligente | ✅ | `BusquedaController` + `q` en listados |
| 4 | Campana notificaciones | ✅ | layout + `saf.js` + `NotificacionesController` |
| 5 | Nombres Dashboard = menú | ✅ | `$topbarTitles` alineado |
| 6 | Sistema de alertas | ✅ | `AlertasTable` + vista agrupada |
| 7 | Reglas de negocio | ✅ | `/reglas-alertas` |
| 8 | Flujo notificaciones | ✅ | `notificaciones_flujo` (Consultor primero) |
| 9 | Dashboard con enlaces | ✅ | `/alertas/ir/{id}` + urls en widgets |
| 10 | Agrupar alertas | ✅ | Expandir/Contraer en `Alertas/index` |
| 11 | Indicador “Faltan XX días” | ✅ | CSS `.alerta-dias` |
| 12 | Porcentajes generales | ✅ | `dash-global-pct` |
| 13 | Catálogo fijo tipos | ✅ | `/tipos-documento` + import |
| 14 | Reportes | ✅ | `/reportes` |
| 15 | Export Excel/CSV | ✅ | `ExportacionService` |
| 16 | Exportación completa | ✅ | `/reportes/exportar-completo` |
| 17 | Error crear operadores | ✅ | validaciones + Flash por campo |
| 18 | Validaciones FE/BE | ✅ | required + Table Validator |
| 19 | Campos obligatorios | ✅ | licencia, vigencia, empresa, nombre, estatus |
| 20 | Baja lógica | ✅ | 4 estados |
| 21 | Historial verificaciones | ✅ | `/verificaciones` |
| 22 | Auditoría modificaciones | ✅ | `auditoria_cambios` |
| 23 | Log del sistema | ✅ | `/auditoria` |

## 4. Roles

| ID | Nombre | Capacidades |
|----|--------|-------------|
| 1 | Super Administrador | Todas las cuentas/empresas; `/cuentas`; auditoría global |
| 2 | Consultor | N empresas; primer destinatario de alertas |
| 3 | Usuario | Empresas asignadas |

## 5. Errores corregidos

1. Campana sin contador / marcar leídas / auto-refresh.
2. Alta operador: `num_licencia` obligatorio; mensajes por campo; `apellido_materno` opcional.
3. Alertas con texto ambiguo “vence” → siempre tipo de documento + “Faltan X días”.
4. Soft-delete solo `inactivo` → `activo|inactivo|suspendido|baja`.
5. Login de cuentas suspendidas ahora rechazado.
6. Deprecación `fputcsv` escape en exportación.

## 6. Pruebas

```bash
cd /var/www/html/saf
php tests/functional_smoke_mejoras_v2.php
```

Checklist manual tras aplicar SQL:
- [ ] Login superadmin / consultor / usuario
- [ ] Crear cuenta y vincular 2+ empresas
- [ ] Suspender / reactivar cuenta
- [ ] Buscar “Juan” encuentra operador/licencia/empresa
- [ ] Campana: contador, abrir, marcar leídas, navegar
- [ ] Alertas agrupadas + “Faltan XX días”
- [ ] Reglas 45/30/15/7/1 editables
- [ ] Dashboard % + links
- [ ] Alta operador con obligatorios
- [ ] Baja lógica conserva historial
- [ ] Reporte → Excel / CSV
- [ ] Historial verificaciones
- [ ] Auditoría filtrable

## 9. Estado de despliegue (2026-07-28)

**Código:** completo (72/72 verificación estática OK).  
**BD local operativa:** MySQL en socket `/tmp/saf-mysqld.sock` (puerto 3307).  
**MySQL del host (3306):** inaccesible en este entorno (proceso zombie sin socket).

### Arranque rápido

```bash
# 1) MySQL local
/var/www/html/saf/bin/start_mysql_local.sh

# 2) Bootstrap + patch (solo primera vez o reset)
/var/www/html/saf/bin/bootstrap_local_db.sh

# 3) Verificar
cd /var/www/html/saf
SAF_DB_SOCKET=/tmp/saf-mysqld.sock SAF_DB_USER=root SAF_DB_PASS= SAF_DB_NAME=saf \
  php bin/verificar_mejoras_v2.php

# 4) Servidor de prueba
php -S 0.0.0.0:8085 -t webroot webroot/index.php
```

### Credenciales demo

| Usuario | Contraseña | Rol |
|---------|------------|-----|
| `superadmin@saf.local` | `Admin123!` | Super Administrador |
| `consultor@saf.local` | `Admin123!` | Consultor |

### Config app

`config/app_local.php` apunta a `unix_socket=/tmp/saf-mysqld.sock`.  
Cuando el MySQL del host vuelva, restaurar host `127.0.0.1` puerto `3306` y aplicar:

```bash
php bin/aplicar_mejoras_v2.php
```

### Datos demo cargados

- 2 operadores (JUAN PEREZ, MARIA GARCIA)
- Documentos por vencer / vencidos → **6 alertas** + **6 notificaciones**
- Textos tipo: `Licencia federal — Faltan 12 días — JUAN PEREZ`
