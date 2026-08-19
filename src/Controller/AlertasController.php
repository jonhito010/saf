<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\AlertasTable;
use App\Model\Table\DocumentosEmpresaTable;
use App\Model\Table\DocumentosOperadorTable;
use App\Model\Table\DocumentosVehiculoTable;
use App\Service\AlertasReglasService;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;

class AlertasController extends AppController
{
    public function index(): void
    {
        $table = $this->fetchTable('Alertas');
        $this->sincronizarAlertasEmpresasAccesibles();

        $query = $table
            ->find()
            ->where(['resuelto' => 0])
            ->orderBy(['Alertas.nivel_urgencia' => 'DESC', 'Alertas.fecha_vencimiento' => 'ASC', 'Alertas.id' => 'DESC']);
        $this->aplicarAlcanceEmpresa($query, 'Alertas.empresa_id');

        $alertas = $query->all();
        $empresasMap = $this->empresasAccesiblesSesion();
        $this->set(compact('alertas', 'empresasMap') + $this->datosFiltroEmpresaVista());
    }

    public function ir(int $id): Response
    {
        $table = $this->fetchTable('Alertas');
        try {
            $alerta = $table->get($id);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Alerta no encontrada.');
            return $this->redirect(['action' => 'index']);
        }

        if (!$this->usuarioTieneAccesoEmpresa((int)$alerta->empresa_id)) {
            $this->Flash->error('No tienes acceso a esta alerta.');
            return $this->redirect(['action' => 'index']);
        }

        try {
            $alerta->set('leida', 1);
            $alerta->set('leida_at', new \Cake\I18n\DateTime());
            $table->save($alerta);
        } catch (\Throwable) {
            // Columna leida puede no existir aún.
        }

        $destino = (string)($alerta->url_destino ?? '');
        if ($destino === '') {
            $destino = AlertasTable::urlDestinoEntidad(
                (string)($alerta->entidad_tipo ?? ''),
                (int)($alerta->entidad_id ?? 0),
                (int)($alerta->empresa_id ?? 0),
            );
        }

        if ($destino !== '' && $destino[0] === '/') {
            return $this->redirect($destino);
        }

        return $this->redirect($destino !== '' ? $destino : ['action' => 'index']);
    }

    public function add(): ?Response
    {
        $table = $this->fetchTable('Alertas');
        $alerta = $table->newEmptyEntity();
        $empresaId = $this->empresaIdSesion();
        if ($empresaId !== null) {
            $alerta->empresa_id = $empresaId;
        }

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $data['empresa_id'] = (int)($data['empresa_id'] ?? $empresaId ?? 0);
            if (!$this->entidadEnAlcanceEmpresa((int)$data['empresa_id'])) {
                $this->Flash->error('Debes asignar la alerta a una empresa a la que tengas acceso.');
                $this->set(compact('alerta'));

                return null;
            }
            $alerta = $table->patchEntity($alerta, $data);
            if ($table->save($alerta)) {
                $this->Flash->success('Alerta registrada.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('No se pudo registrar la alerta.');
        }

        $this->set(compact('alerta'));
        return null;
    }

    public function edit(int $id): ?Response
    {
        $table = $this->fetchTable('Alertas');
        try {
            $alerta = $table->get($id);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Alerta no encontrada.');
            return $this->redirect(['action' => 'index']);
        }

        if (!$this->usuarioTieneAccesoEmpresa((int)$alerta->empresa_id)) {
            $this->Flash->error('No tienes acceso a esta alerta.');
            return $this->redirect(['action' => 'index']);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $alerta = $table->patchEntity($alerta, $this->request->getData());
            if ($table->save($alerta)) {
                $this->Flash->success('Alerta actualizada.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('No se pudo actualizar la alerta.');
        }

        $this->set(compact('alerta'));
        return null;
    }

    public function delete(int $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $table = $this->fetchTable('Alertas');
        try {
            $alerta = $table->get($id);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Alerta no encontrada.');
            return $this->redirect(['action' => 'index']);
        }

        if (!$this->usuarioTieneAccesoEmpresa((int)$alerta->empresa_id)) {
            $this->Flash->error('No tienes acceso a esta alerta.');
            return $this->redirect(['action' => 'index']);
        }

        if ($table->delete($alerta)) {
            $this->Flash->success('Alerta eliminada.');
        } else {
            $this->Flash->error('No se pudo eliminar la alerta.');
        }

        return $this->redirect(['action' => 'index']);
    }

    public static function etiquetaTipoDocumento(string $codigo, string $ambito = ''): string
    {
        return match ($ambito) {
            'empresa' => DocumentosEmpresaTable::tipoLabel($codigo),
            'vehiculo' => DocumentosVehiculoTable::tipoLabel($codigo),
            'operador' => DocumentosOperadorTable::tipoLabel($codigo),
            default => $codigo,
        };
    }

    public static function diasRestantesAlerta(mixed $fecha): ?int
    {
        return AlertasReglasService::diasRestantes($fecha);
    }

    public static function claseDiasRestantes(?int $dias, bool $vencido): string
    {
        if ($vencido || $dias === null || $dias <= 0) {
            return 'alerta-dias--critico';
        }
        if ($dias <= 15) {
            return 'alerta-dias--critico';
        }
        if ($dias <= 30) {
            return 'alerta-dias--warn';
        }

        return 'alerta-dias--ok';
    }

    public static function textoDiasRestantes(?int $dias, bool $vencido): string
    {
        if ($vencido || ($dias !== null && $dias <= 0)) {
            return 'Vencido';
        }
        if ($dias === null) {
            return 'Sin fecha';
        }
        if ($dias === 1) {
            return 'Falta 1 día';
        }

        return 'Faltan ' . $dias . ' días';
    }

    public static function urlAlerta(object $alerta): string
    {
        $url = (string)($alerta->url_destino ?? '');
        if ($url !== '') {
            return $url;
        }

        return AlertasTable::urlDestinoEntidad(
            (string)($alerta->entidad_tipo ?? ''),
            (int)($alerta->entidad_id ?? 0),
            (int)($alerta->empresa_id ?? 0),
        );
    }

    public static function etiquetaGrupoAlerta(object $alerta): string
    {
        $label = trim((string)($alerta->tipo_documento_label ?? ''));
        if ($label !== '') {
            return $label;
        }

        return self::etiquetaTipoDocumento(
            (string)($alerta->tipo_documento ?? ''),
            (string)($alerta->entidad_tipo ?? ''),
        );
    }
}
