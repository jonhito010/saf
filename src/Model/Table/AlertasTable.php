<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Table\DocumentosEmpresaTable;
use App\Model\Table\DocumentosOperadorTable;
use App\Model\Table\DocumentosVehiculoTable;
use App\Service\AlertasReglasService;
use App\Service\NotificacionesService;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class AlertasTable extends Table
{
    use LocatorAwareTrait;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('alertas_cumplimiento');
        $this->setPrimaryKey('id');
        $this->setEntityClass('App\Model\Entity\Alerta');

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created_at' => 'new',
                ],
            ],
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('entidad_tipo', 'Selecciona la entidad.')
            ->inList('entidad_tipo', ['empresa', 'vehiculo', 'operador'], 'Entidad inválida.')
            ->notEmptyString('entidad_id', 'Falta el ID de la entidad.')
            ->notEmptyString('empresa_id', 'Falta empresa.')
            ->notEmptyString('tipo_alerta', 'Selecciona el tipo de alerta.')
            ->inList('tipo_alerta', [
                'documento_vencido', 'documento_por_vencer', 'documento_faltante',
                'inspeccion_pendiente', 'verificacion_pendiente',
            ], 'Tipo de alerta inválido.')
            ->allowEmptyString('tipo_documento')
            ->maxLength('tipo_documento', 100, 'Máximo 100 caracteres.')
            ->allowEmptyString('descripcion')
            ->maxLength('descripcion', 500, 'Máximo 500 caracteres.')
            ->allowEmptyDate('fecha_vencimiento')
            ->allowEmptyString('dias_anticipacion')
            ->allowEmptyString('nivel_urgencia')
            ->inList('nivel_urgencia', ['info', 'advertencia', 'critico'], 'Nivel inválido.')
            ->allowEmptyString('notificado')
            ->boolean('notificado', 'Valor inválido para notificado.')
            ->allowEmptyString('resuelto')
            ->boolean('resuelto', 'Valor inválido para resuelto.');

        return $validator;
    }

    /**
     * Genera/actualiza alertas a partir de documentos vencidos o por vencer.
     */
    public function sincronizarDesdeDocumentos(?int $empresaId = null): int
    {
        if ($empresaId === null || $empresaId === 0) {
            // No sincronizar con la primera empresa del sistema (fuga multiempresa).
            return 0;
        }

        $cambios = 0;
        $activas = [];

        $docEmpresa = $this->fetchTable('DocumentosEmpresa');
        foreach ($docEmpresa->find()->where([
            'empresa_id' => $empresaId,
            'estatus IN' => ['vencido', 'por_vencer'],
        ])->all() as $doc) {
            $key = $this->claveAlerta('empresa', (int)$doc->empresa_id, (string)$doc->tipo_documento, (string)$doc->estatus);
            $activas[$key] = true;
            if ($this->guardarAlertaDocumento(
                'empresa',
                (int)$doc->empresa_id,
                $empresaId,
                $doc,
                DocumentosEmpresaTable::tipoLabel((string)$doc->tipo_documento),
                null,
            )) {
                $cambios++;
            }
        }

        $docVehiculo = $this->fetchTable('DocumentosVehiculo');
        foreach ($docVehiculo->find()
            ->contain(['Vehiculos'])
            ->where([
                'Vehiculos.empresa_id' => $empresaId,
                'DocumentosVehiculo.estatus IN' => ['vencido', 'por_vencer'],
            ])->all() as $doc) {
            $vid = (int)$doc->vehiculo_id;
            $key = $this->claveAlerta('vehiculo', $vid, (string)$doc->tipo_documento, (string)$doc->estatus);
            $activas[$key] = true;
            $ref = $doc->vehiculo->placas ?? ('#' . $vid);
            if ($this->guardarAlertaDocumento(
                'vehiculo',
                $vid,
                $empresaId,
                $doc,
                DocumentosVehiculoTable::tipoLabel((string)$doc->tipo_documento),
                $ref,
            )) {
                $cambios++;
            }
        }

        $docOperador = $this->fetchTable('DocumentosOperador');
        foreach ($docOperador->find()
            ->contain(['Operadores'])
            ->where([
                'Operadores.empresa_id' => $empresaId,
                'DocumentosOperador.estatus IN' => ['vencido', 'por_vencer'],
            ])->all() as $doc) {
            $oid = (int)$doc->operador_id;
            $key = $this->claveAlerta('operador', $oid, (string)$doc->tipo_documento, (string)$doc->estatus);
            $activas[$key] = true;
            $ref = $doc->operador->nombre_completo ?? ('#' . $oid);
            if ($this->guardarAlertaDocumento(
                'operador',
                $oid,
                $empresaId,
                $doc,
                DocumentosOperadorTable::tipoLabel((string)$doc->tipo_documento),
                $ref,
            )) {
                $cambios++;
            }
        }

        foreach ($this->find()->where(['empresa_id' => $empresaId, 'resuelto' => 0])->all() as $alerta) {
            $tipoAlerta = (string)$alerta->tipo_alerta;
            if (!in_array($tipoAlerta, ['documento_vencido', 'documento_por_vencer'], true)) {
                continue;
            }
            $estatusDoc = $tipoAlerta === 'documento_vencido' ? 'vencido' : 'por_vencer';
            $key = $this->claveAlerta(
                (string)$alerta->entidad_tipo,
                (int)$alerta->entidad_id,
                (string)$alerta->tipo_documento,
                $estatusDoc,
            );
            if (!isset($activas[$key])) {
                $alerta->resuelto = 1;
                if ($this->save($alerta)) {
                    $cambios++;
                }
            }
        }

        return $cambios;
    }

    private function claveAlerta(string $entidadTipo, int $entidadId, string $tipoDocumento, string $estatusDoc): string
    {
        return implode('|', [$entidadTipo, $entidadId, $tipoDocumento, $estatusDoc]);
    }

    private function guardarAlertaDocumento(
        string $entidadTipo,
        int $entidadId,
        int $empresaId,
        object $doc,
        string $tipoLabel,
        ?string $referencia,
    ): bool {
        $estatus = (string)$doc->estatus;
        $vencido = $estatus === 'vencido';
        $fechaVenc = $doc->fecha_vencimiento ?? null;
        $diasRestantes = AlertasReglasService::diasRestantes($fechaVenc);
        $tipoDoc = (string)$doc->tipo_documento;

        if (
            !$vencido
            && $estatus === 'por_vencer'
            && !AlertasReglasService::enVentanaPorVencer($diasRestantes, $empresaId, $entidadTipo, $tipoDoc)
        ) {
            $this->resolverAlertasDocumento($entidadTipo, $entidadId, $tipoDoc);

            return false;
        }

        $tipoAlerta = $vencido ? 'documento_vencido' : 'documento_por_vencer';
        $nivel = AlertasReglasService::nivelUrgencia($diasRestantes, $vencido, $empresaId, $entidadTipo, $tipoDoc);
        $umbral = $vencido ? null : AlertasReglasService::umbralRecordatorio($diasRestantes, $empresaId, $entidadTipo, $tipoDoc);
        $descripcion = AlertasReglasService::textoRecordatorio($tipoLabel, $diasRestantes, $vencido, $referencia);

        $existente = $this->find()->where([
            'entidad_tipo' => $entidadTipo,
            'entidad_id' => $entidadId,
            'tipo_documento' => $tipoDoc,
            'tipo_alerta IN' => ['documento_vencido', 'documento_por_vencer'],
            'resuelto' => 0,
        ])->first();

        $camposEnriquecidos = $this->camposEnriquecidosAlerta(
            $entidadTipo,
            $entidadId,
            $empresaId,
            $tipoLabel,
            $referencia,
            $diasRestantes,
        );

        if ($existente) {
            $umbralAnterior = (int)($existente->dias_anticipacion ?? 0);
            $nuevoUmbral = $umbral ?? 0;
            $reiniciarNotificacion = !$vencido && $nuevoUmbral > 0 && $nuevoUmbral < $umbralAnterior;

            $datos = array_merge([
                'tipo_alerta' => $tipoAlerta,
                'descripcion' => $descripcion,
                'fecha_vencimiento' => $fechaVenc,
                'dias_anticipacion' => $umbral,
                'nivel_urgencia' => $nivel,
                'empresa_id' => $empresaId,
                'notificado' => $reiniciarNotificacion ? 0 : (int)($existente->notificado ?? 0),
            ], $camposEnriquecidos);

            $guardada = $this->guardarEntidadConCompatibilidad($existente, $datos);

            return $guardada !== null && $this->notificarSiPendiente($guardada);
        }

        $datos = array_merge([
            'entidad_tipo' => $entidadTipo,
            'entidad_id' => $entidadId,
            'empresa_id' => $empresaId,
            'tipo_alerta' => $tipoAlerta,
            'tipo_documento' => (string)$doc->tipo_documento,
            'descripcion' => $descripcion,
            'fecha_vencimiento' => $fechaVenc,
            'dias_anticipacion' => $umbral,
            'nivel_urgencia' => $nivel,
            'notificado' => 0,
            'resuelto' => 0,
        ], $camposEnriquecidos);

        $guardada = $this->guardarEntidadConCompatibilidad($this->newEntity([]), $datos);

        return $guardada !== null && $this->notificarSiPendiente($guardada);
    }

    /**
     * @return array<string, mixed>
     */
    private function camposEnriquecidosAlerta(
        string $entidadTipo,
        int $entidadId,
        int $empresaId,
        string $tipoLabel,
        ?string $referencia,
        ?int $diasRestantes,
    ): array {
        return [
            'dias_restantes' => $diasRestantes,
            'tipo_documento_label' => $tipoLabel,
            'entidad_nombre' => $referencia ?? '',
            'url_destino' => self::urlDestinoEntidad($entidadTipo, $entidadId, $empresaId),
        ];
    }

    public static function urlDestinoEntidad(string $entidadTipo, int $entidadId, ?int $empresaId = null): string
    {
        // Rutas literales: evita MissingRouteException en CLI / sync sin contexto HTTP.
        $base = rtrim((string)(\Cake\Core\Configure::read('App.base') ?: ''), '/');
        try {
            $path = match ($entidadTipo) {
                'empresa' => ($empresaId !== null && $empresaId > 0)
                    ? '/empresa?empresa_id=' . $empresaId
                    : '/empresa',
                'vehiculo' => '/vehiculos/' . max(0, $entidadId),
                'operador' => '/operadores/edit/' . max(0, $entidadId),
                default => '/alertas',
            };
        } catch (\Throwable) {
            $path = '/alertas';
        }

        return ($base !== '' ? $base : '') . $path;
    }

    /**
     * @param array<string, mixed> $datos
     */
    private function guardarEntidadConCompatibilidad(object $entity, array $datos): ?object
    {
        $opcionales = ['dias_restantes', 'tipo_documento_label', 'entidad_nombre', 'url_destino', 'empresa_nombre'];

        try {
            $entity = $this->patchEntity($entity, $datos);
            if ($this->save($entity)) {
                return $entity;
            }
        } catch (\Throwable) {
            // Reintentar sin columnas nuevas.
        }

        foreach ($opcionales as $campo) {
            unset($datos[$campo]);
        }

        try {
            $entity = $this->patchEntity($entity, $datos);
            if ($this->save($entity)) {
                return $entity;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function notificarSiPendiente(object $alerta): bool
    {
        if ((int)($alerta->resuelto ?? 0) !== 0 || (int)($alerta->notificado ?? 0) !== 0) {
            return true;
        }

        if (!class_exists(NotificacionesService::class)) {
            return true;
        }

        try {
            $svc = new NotificacionesService();
            $empresaId = (int)($alerta->empresa_id ?? 0);
            $destinatarios = $svc->destinatariosParaEmpresa($empresaId);
            if ($destinatarios === []) {
                return true;
            }

            $creadas = $svc->crearDesdeAlerta([
                'id' => (int)$alerta->id,
                'empresa_id' => $empresaId,
                'titulo' => (string)($alerta->tipo_documento_label ?? $alerta->descripcion ?? 'Alerta'),
                'mensaje' => (string)($alerta->descripcion ?? ''),
                'descripcion' => (string)($alerta->descripcion ?? ''),
                'url_destino' => (string)($alerta->url_destino ?? self::urlDestinoEntidad(
                    (string)($alerta->entidad_tipo ?? ''),
                    (int)($alerta->entidad_id ?? 0),
                    $empresaId,
                )),
                'tipo' => (string)($alerta->tipo_alerta ?? 'alerta'),
            ], $destinatarios);

            if ($creadas > 0) {
                $alerta->notificado = 1;
                $this->save($alerta);
            }
        } catch (\Throwable) {
            // No bloquear sincronización.
        }

        return true;
    }

    private function resolverAlertasDocumento(string $entidadTipo, int $entidadId, string $tipoDocumento): void
    {
        foreach ($this->find()->where([
            'entidad_tipo' => $entidadTipo,
            'entidad_id' => $entidadId,
            'tipo_documento' => $tipoDocumento,
            'tipo_alerta IN' => ['documento_vencido', 'documento_por_vencer'],
            'resuelto' => 0,
        ])->all() as $alerta) {
            $alerta->resuelto = 1;
            $this->save($alerta);
        }
    }
}
