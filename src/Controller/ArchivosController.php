<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

class ArchivosController extends AppController
{
    public function ver(): Response
    {
        return $this->servirArchivo(false);
    }

    public function descargar(): Response
    {
        return $this->servirArchivo(true);
    }

    private function servirArchivo(bool $forzarDescarga): Response
    {
        $ruta = (string)$this->request->getQuery('r', '');
        if ($ruta === '' || !str_starts_with($ruta, '/uploads/') || str_contains($ruta, '..')) {
            throw new NotFoundException('Archivo no encontrado.');
        }
        if (!$this->rutaUploadEnAlcance($ruta)) {
            throw new NotFoundException('Archivo no encontrado.');
        }

        $rel = ltrim(str_replace('/', DIRECTORY_SEPARATOR, substr($ruta, 1)), DIRECTORY_SEPARATOR);
        $full = WWW_ROOT . $rel;
        if (!is_file($full)) {
            throw new NotFoundException('Archivo no encontrado.');
        }

        return $this->response->withFile($full, [
            'download' => $forzarDescarga,
            'name' => basename($full),
        ]);
    }

    private function rutaUploadEnAlcance(string $ruta): bool
    {
        $empresaIds = $this->empresaIdsConsulta();
        if ($empresaIds === []) {
            return false;
        }

        // Documentos de empresa
        $docEmp = $this->fetchTable('DocumentosEmpresa')->find()
            ->select(['id', 'empresa_id'])
            ->where(['ruta_archivo' => $ruta, 'empresa_id IN' => $empresaIds])
            ->first();
        if ($docEmp) {
            return true;
        }

        // Documentos de vehículo
        $docVeh = $this->fetchTable('DocumentosVehiculo')->find()
            ->select(['DocumentosVehiculo.id'])
            ->innerJoinWith('Vehiculos', fn($q) => $q->where(['Vehiculos.empresa_id IN' => $empresaIds]))
            ->where(['DocumentosVehiculo.ruta_archivo' => $ruta])
            ->first();
        if ($docVeh) {
            return true;
        }

        // Documentos de operador
        $docOp = $this->fetchTable('DocumentosOperador')->find()
            ->select(['DocumentosOperador.id'])
            ->innerJoinWith('Operadores', fn($q) => $q->where(['Operadores.empresa_id IN' => $empresaIds]))
            ->where(['DocumentosOperador.ruta_archivo' => $ruta])
            ->first();
        if ($docOp) {
            return true;
        }

        // Fotos de operador u otros paths bajo uploads ligados a operador
        $opFoto = $this->fetchTable('Operadores')->find()
            ->select(['id'])
            ->where([
                'empresa_id IN' => $empresaIds,
                'OR' => [
                    'foto' => $ruta,
                    'foto_ruta' => $ruta,
                ],
            ]);
        try {
            if ($opFoto->first()) {
                return true;
            }
        } catch (\Throwable) {
            // Columnas de foto pueden no existir.
        }

        return false;
    }
}
