<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\DocumentoOperador;
use App\Model\Entity\Operador;
use App\Model\Table\DocumentosOperadorTable;
use App\Utility\DocumentoUtil;
use Cake\Http\ServerRequest;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use Psr\Http\Message\UploadedFileInterface;

class OperadorArchivosService
{
    use LocatorAwareTrait;

    private const MAX_BYTES = 0; // usa DocumentoUtil::maxUploadBytes()

    /** @var list<string> */
    private const EXT_IMAGEN = ['jpg', 'jpeg', 'png', 'webp'];

    /** @var list<string> */
    private const EXT_DOC = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx'];

    /** @var array<string, array{tipo: string, fecha: string}> */
    private const MAPA_DOCS = [
        'archivo_ine' => [
            'tipo' => 'ine',
            'fecha' => 'fecha_vencimiento_ine',
        ],
        'archivo_licencia' => [
            'tipo' => 'licencia',
            'fecha' => 'fecha_vencimiento_licencia',
        ],
        'archivo_capacitacion' => [
            'tipo' => 'curso_capacitacion',
            'fecha' => 'fecha_vencimiento_capacitacion',
        ],
        'archivo_comprobante_domicilio' => [
            'tipo' => 'comprobante_domicilio',
            'fecha' => 'fecha_vencimiento_comprobante_domicilio',
        ],
    ];

    /**
     * Valida que los documentos obligatorios del formulario estén presentes (archivo nuevo o ya cargado).
     *
     * @param array<string, DocumentoOperador> $docsPorTipo
     * @return list<string>
     */
    public function validarObligatorios(ServerRequest $request, array $docsPorTipo = []): array
    {
        $errores = [];
        $data = $request->getData();
        $campoPorTipo = [];
        foreach (self::MAPA_DOCS as $campo => $cfg) {
            $campoPorTipo[$cfg['tipo']] = $campo;
        }

        foreach (DocumentosOperadorTable::tiposObligatoriosFormulario() as $tipo) {
            $campo = $campoPorTipo[$tipo] ?? null;
            if ($campo === null) {
                continue;
            }
            $file = $request->getUploadedFile($campo);
            $tieneUpload = $file !== null && $file->getError() !== UPLOAD_ERR_NO_FILE;
            $tieneExistente = isset($docsPorTipo[$tipo])
                && trim((string)($docsPorTipo[$tipo]->ruta_archivo ?? '')) !== ''
                && (string)$docsPorTipo[$tipo]->ruta_archivo !== DocumentosOperadorTable::RUTA_SIN_ARCHIVO;

            if (!$tieneUpload && !$tieneExistente) {
                $errores[] = DocumentosOperadorTable::tipoLabel($tipo) . ' es obligatorio.';
            }
        }

        $fechaLic = trim((string)($data['fecha_vencimiento_licencia'] ?? ''));
        $docLic = $docsPorTipo['licencia'] ?? null;
        $tieneFechaLic = $fechaLic !== ''
            || ($docLic instanceof DocumentoOperador && $docLic->fecha_vencimiento !== null);
        if (!$tieneFechaLic && in_array('licencia', DocumentosOperadorTable::tiposObligatoriosFormulario(), true)) {
            $errores[] = 'La fecha de vencimiento de la licencia federal es obligatoria.';
        }

        return $errores;
    }

    /**
     * @return list<string> Errores (vacío si todo bien)
     */
    public function procesarDesdeRequest(Operador $operador, ServerRequest $request): array
    {
        $errores = [];
        $operadoresTable = $this->fetchTable('Operadores');
        $docsTable = $this->fetchTable('DocumentosOperador');
        $operadorId = (int)$operador->id;
        if ($operadorId <= 0) {
            return ['No se pudo identificar al operador.'];
        }

        $foto = $request->getUploadedFile('foto');
        if ($foto !== null && $foto->getError() !== UPLOAD_ERR_NO_FILE) {
            $ruta = $this->moverArchivo($foto, 'operador' . DIRECTORY_SEPARATOR . 'fotos', self::EXT_IMAGEN);
            if ($ruta === null) {
                $limite = DocumentoUtil::limiteUploadHumano();
                $errores[] = 'La foto no es válida (usa JPG o PNG, máx. ' . $limite . ').';
            } else {
                $operador->foto_ruta = $ruta;
                if (!$operadoresTable->save($operador)) {
                    $errores[] = 'No se pudo guardar la foto del operador.';
                }
            }
        }

        $data = $request->getData();
        foreach (self::MAPA_DOCS as $campo => $cfg) {
            $fechaRaw = trim((string)($data[$cfg['fecha']] ?? ''));
            $fechaVenc = $fechaRaw !== '' ? $fechaRaw : null;

            $file = $request->getUploadedFile($campo);
            if ($file !== null && $file->getError() !== UPLOAD_ERR_NO_FILE) {
                $err = $this->guardarDocumento($docsTable, $operadorId, $cfg['tipo'], $file, $fechaVenc);
                if ($err !== null) {
                    $errores[] = $err;
                }
                continue;
            }

            if ($fechaVenc !== null) {
                $err = $this->actualizarFechaVencimiento($docsTable, $operadorId, $cfg['tipo'], $fechaVenc);
                if ($err !== null) {
                    $errores[] = $err;
                }
            }
        }

        return $errores;
    }

    private function guardarDocumento(
        DocumentosOperadorTable $docsTable,
        int $operadorId,
        string $tipo,
        UploadedFileInterface $file,
        ?string $fechaVencimiento,
    ): ?string {
        $ruta = $this->moverArchivo($file, 'operador' . DIRECTORY_SEPARATOR . 'documentos', self::EXT_DOC);
        if ($ruta === null) {
            $limite = DocumentoUtil::limiteUploadHumano();

            return DocumentosOperadorTable::tipoLabel($tipo) . ': archivo no válido (PDF, imagen o Word, máx. ' . $limite . ').';
        }

        $clientName = $file->getClientFilename() ?? 'documento';
        $estatus = $this->estatusDesdeFecha($fechaVencimiento, $tipo);

        $existente = $docsTable->find()
            ->where([
                'operador_id' => $operadorId,
                'tipo_documento' => $tipo,
            ])
            ->first();

        if ($existente instanceof DocumentoOperador) {
            $doc = $existente;
        } else {
            $doc = $docsTable->newEmptyEntity();
            $doc->operador_id = $operadorId;
            $doc->tipo_documento = $tipo;
        }

        $doc->nombre_archivo = $clientName !== '' ? $clientName : basename($ruta);
        $doc->ruta_archivo = $ruta;
        $doc->fecha_vencimiento = $fechaVencimiento;
        $doc->estatus = $estatus;

        if (!$docsTable->save($doc)) {
            return DocumentosOperadorTable::tipoLabel($tipo) . ': no se pudo guardar en el expediente.';
        }

        return null;
    }

    private function actualizarFechaVencimiento(
        DocumentosOperadorTable $docsTable,
        int $operadorId,
        string $tipo,
        string $fechaVencimiento,
    ): ?string {
        $existente = $docsTable->find()
            ->where([
                'operador_id' => $operadorId,
                'tipo_documento' => $tipo,
            ])
            ->first();

        if (!$existente instanceof DocumentoOperador) {
            return null;
        }

        $existente->fecha_vencimiento = $fechaVencimiento;
        $existente->estatus = $this->estatusDesdeFecha($fechaVencimiento, $tipo);

        if (!$docsTable->save($existente)) {
            return DocumentosOperadorTable::tipoLabel($tipo) . ': no se pudo actualizar la fecha de vencimiento.';
        }

        return null;
    }

    private function estatusDesdeFecha(?string $fecha, ?string $tipoDocumento = null): string
    {
        if ($tipoDocumento === 'comprobante_domicilio' || $tipoDocumento === 'ine') {
            return 'vigente';
        }
        if ($fecha === null || $fecha === '') {
            return 'vigente';
        }
        try {
            $f = new Date($fecha);
        } catch (\Throwable $e) {
            return 'vigente';
        }
        $hoy = Date::today();
        if ($f < $hoy) {
            return 'vencido';
        }
        if ($f <= $hoy->addDays(30)) {
            return 'por_vencer';
        }

        return 'vigente';
    }

    /**
     * @param list<string> $allowedExt
     */
    private function moverArchivo(UploadedFileInterface $file, string $subdir, array $allowedExt): ?string
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            return null;
        }
        if ($file->getSize() > \App\Utility\DocumentoUtil::maxUploadBytes()) {
            return null;
        }
        $clientName = $file->getClientFilename() ?? 'archivo';
        $ext = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));
        if ($ext === '' || !in_array($ext, $allowedExt, true)) {
            return null;
        }

        $uploadDir = WWW_ROOT . 'uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subdir);
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        $safeBase = bin2hex(random_bytes(8)) . '.' . $ext;
        $file->moveTo($uploadDir . DIRECTORY_SEPARATOR . $safeBase);

        return '/uploads/' . str_replace('\\', '/', $subdir) . '/' . $safeBase;
    }
}
