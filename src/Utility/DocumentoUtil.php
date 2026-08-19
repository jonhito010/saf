<?php
declare(strict_types=1);

namespace App\Utility;

use App\Service\AlertasReglasService;
use Cake\I18n\Date;
use Psr\Http\Message\UploadedFileInterface;

class DocumentoUtil
{
    public static function estatusDesdeFecha(mixed $fecha): string
    {
        if ($fecha === null || $fecha === '') {
            return 'vigente';
        }
        try {
            if ($fecha instanceof \DateTimeInterface) {
                $f = new Date($fecha->format('Y-m-d'));
            } else {
                $f = new Date((string)$fecha);
            }
        } catch (\Throwable) {
            return 'vigente';
        }
        $hoy = Date::today();
        if ($f < $hoy) {
            return 'vencido';
        }
        if ($f <= $hoy->addDays(AlertasReglasService::diasMaximoPorVencer())) {
            return 'por_vencer';
        }

        return 'vigente';
    }

    public static function mensajeErrorUpload(int $code): string
    {
        $limite = self::limiteUploadHumano();

        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo permitido (' . $limite . ').',
            UPLOAD_ERR_PARTIAL => 'La carga del archivo se interrumpió. Intenta de nuevo.',
            UPLOAD_ERR_NO_FILE => 'Debes adjuntar un archivo.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'Error del servidor al recibir el archivo.',
            default => 'No se pudo subir el archivo.',
        };
    }

    /** Tamaño máximo efectivo (menor entre app 15 MB y límites PHP). */
    public static function maxUploadBytes(): int
    {
        $appMax = 15 * 1024 * 1024;
        $iniMax = self::parseIniBytes(ini_get('upload_max_filesize'));
        $postMax = self::parseIniBytes(ini_get('post_max_size'));
        $phpMax = min($iniMax ?: $appMax, $postMax ?: $appMax);

        return min($appMax, $phpMax > 0 ? $phpMax : $appMax);
    }

    public static function limiteUploadHumano(): string
    {
        $bytes = self::maxUploadBytes();
        if ($bytes >= 1024 * 1024) {
            return (int)round($bytes / (1024 * 1024)) . ' MB';
        }

        return (int)round($bytes / 1024) . ' KB';
    }

    private static function parseIniBytes(string|false $val): int
    {
        if ($val === false || $val === '') {
            return 0;
        }
        $val = trim($val);
        $n = (int)$val;
        $u = strtolower(substr($val, -1));
        if ($u === 'g') {
            return $n * 1024 * 1024 * 1024;
        }
        if ($u === 'm') {
            return $n * 1024 * 1024;
        }
        if ($u === 'k') {
            return $n * 1024;
        }

        return $n;
    }

    /**
     * @param list<string> $allowedExt
     * @return array{error?: string, ruta?: string, nombre?: string}
     */
    public static function procesarUpload(
        ?UploadedFileInterface $uploaded,
        string $uploadDir,
        string $urlPrefix,
        array $allowedExt,
        int $maxBytes = 0,
        bool $requerido = true,
        ?string $rutaExistente = null,
    ): array {
        if ($maxBytes <= 0) {
            $maxBytes = self::maxUploadBytes();
        }
        $limiteTxt = self::limiteUploadHumano();
        if ($uploaded === null || $uploaded->getError() === UPLOAD_ERR_NO_FILE) {
            if ($requerido && ($rutaExistente === null || trim($rutaExistente) === '')) {
                return ['error' => 'Debes adjuntar un archivo.'];
            }

            return [];
        }
        if ($uploaded->getError() !== UPLOAD_ERR_OK) {
            return ['error' => self::mensajeErrorUpload($uploaded->getError())];
        }
        if ($uploaded->getSize() <= 0) {
            return ['error' => 'El archivo está vacío. Selecciona un archivo válido.'];
        }
        if ($uploaded->getSize() > $maxBytes) {
            return ['error' => 'El archivo supera el tamaño máximo permitido (' . $limiteTxt . ').'];
        }
        $clientName = $uploaded->getClientFilename() ?? 'documento';
        $ext = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));
        if ($ext === '' || !in_array($ext, $allowedExt, true)) {
            return ['error' => 'Tipo de archivo no permitido.'];
        }
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        $safeBase = bin2hex(random_bytes(8)) . '.' . $ext;
        $uploaded->moveTo($uploadDir . DIRECTORY_SEPARATOR . $safeBase);

        return [
            'ruta' => $urlPrefix . $safeBase,
            'nombre' => $clientName !== '' ? $clientName : $safeBase,
        ];
    }

    public static function eliminarArchivoFisico(?string $rutaWeb): void
    {
        $rutaWeb = trim((string)$rutaWeb);
        if ($rutaWeb === '' || $rutaWeb === '—' || !str_starts_with($rutaWeb, '/uploads/')) {
            return;
        }
        $full = WWW_ROOT . ltrim(str_replace('/', DIRECTORY_SEPARATOR, substr($rutaWeb, 1)), DIRECTORY_SEPARATOR);
        if (is_file($full)) {
            @unlink($full);
        }
    }
}
