<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\AlertaRegla;
use Cake\I18n\Date;
use Cake\ORM\TableRegistry;

/**
 * Reglas de negocio para alertas de vencimiento (umbrales configurables por documento).
 */
class AlertasReglasService
{
    /** @var list<int> Fallback cuando no hay reglas en BD. */
    public const UMBRALES_DIAS = [45, 30, 15, 7, 1];

    /** @var list<AlertaRegla>|null */
    private static ?array $cacheReglas = null;

    private static ?int $cacheEmpresaId = null;

    public static function diasMaximoPorVencer(
        ?int $empresaId = null,
        ?string $ambito = null,
        ?string $tipoDocumento = null,
    ): int {
        $umbrales = self::umbralesActivos($empresaId, $ambito, $tipoDocumento);

        return $umbrales[0] ?? self::UMBRALES_DIAS[0];
    }

    public static function diasRestantes(mixed $fecha): ?int
    {
        if ($fecha === null || $fecha === '') {
            return null;
        }
        try {
            if ($fecha instanceof \DateTimeInterface) {
                $f = new Date($fecha->format('Y-m-d'));
            } else {
                $f = new Date((string)$fecha);
            }
        } catch (\Throwable) {
            return null;
        }

        $hoy = Date::today();
        if ($f < $hoy) {
            return 0;
        }

        return (int)$hoy->diff($f)->days;
    }

    /** Umbral de recordatorio activo según días restantes y reglas del documento. */
    public static function umbralRecordatorio(
        ?int $diasRestantes,
        ?int $empresaId = null,
        ?string $ambito = null,
        ?string $tipoDocumento = null,
    ): ?int {
        if ($diasRestantes === null || $diasRestantes <= 0) {
            return null;
        }

        foreach (self::reglasActivas($empresaId, $ambito, $tipoDocumento) as $regla) {
            $diasAntes = (int)$regla->dias_antes;
            if ($diasRestantes <= $diasAntes && (int)($regla->enviar_alerta ?? 1) === 1) {
                return $diasAntes;
            }
        }

        foreach (self::umbralesActivos($empresaId, $ambito, $tipoDocumento) as $umbral) {
            if ($diasRestantes <= $umbral) {
                return $umbral;
            }
        }

        return null;
    }

    public static function enVentanaPorVencer(
        ?int $diasRestantes,
        ?int $empresaId = null,
        ?string $ambito = null,
        ?string $tipoDocumento = null,
    ): bool {
        return $diasRestantes !== null
            && $diasRestantes > 0
            && $diasRestantes <= self::diasMaximoPorVencer($empresaId, $ambito, $tipoDocumento);
    }

    public static function nivelUrgencia(
        ?int $diasRestantes,
        bool $vencido,
        ?int $empresaId = null,
        ?string $ambito = null,
        ?string $tipoDocumento = null,
    ): string {
        if ($vencido) {
            return 'critico';
        }

        return self::nivelDesdeRegla($diasRestantes, $empresaId, $ambito, $tipoDocumento);
    }

    public static function nivelDesdeRegla(
        ?int $diasRestantes,
        ?int $empresaId = null,
        ?string $ambito = null,
        ?string $tipoDocumento = null,
    ): string {
        if ($diasRestantes === null || $diasRestantes <= 0) {
            return 'critico';
        }

        $nivel = null;
        $diasCoincidentes = PHP_INT_MAX;
        foreach (self::reglasActivas($empresaId, $ambito, $tipoDocumento) as $regla) {
            $diasAntes = (int)$regla->dias_antes;
            if ($diasRestantes <= $diasAntes && $diasAntes < $diasCoincidentes) {
                $diasCoincidentes = $diasAntes;
                $candidato = (string)($regla->nivel_urgencia ?? 'info');
                if (in_array($candidato, ['info', 'advertencia', 'critico'], true)) {
                    $nivel = $candidato;
                }
            }
        }

        if ($nivel !== null) {
            return $nivel;
        }

        if ($diasRestantes <= 15) {
            return 'critico';
        }
        if ($diasRestantes <= 30) {
            return 'advertencia';
        }

        return 'info';
    }

    public static function textoRecordatorio(
        string $tipoLabel,
        ?int $diasRestantes,
        bool $vencido,
        ?string $referencia = null,
    ): string {
        $refTxt = $referencia !== null && $referencia !== '' ? " — {$referencia}" : '';

        if ($vencido || ($diasRestantes !== null && $diasRestantes <= 0)) {
            return "{$tipoLabel} — Vencido{$refTxt}";
        }

        if ($diasRestantes !== null && $diasRestantes > 0) {
            $d = $diasRestantes === 1 ? '1 día' : "{$diasRestantes} días";

            return "{$tipoLabel} — Faltan {$d}{$refTxt}";
        }

        return "{$tipoLabel} — Vencido{$refTxt}";
    }

    /**
     * Reglas activas aplicables a un documento (o globales si no se filtra).
     *
     * @return list<AlertaRegla>
     */
    public static function reglasActivas(
        ?int $empresaId = null,
        ?string $ambito = null,
        ?string $tipoDocumento = null,
    ): array {
        $todas = self::cargarReglasEmpresa($empresaId);

        if ($ambito === null && ($tipoDocumento === null || $tipoDocumento === '')) {
            return $todas;
        }

        $ambitoNorm = self::normalizarAmbito($ambito);
        $tipoNorm = trim((string)$tipoDocumento);

        $filtradas = [];
        foreach ($todas as $regla) {
            if (!self::reglaAplicaADocumento($regla, $ambitoNorm, $tipoNorm)) {
                continue;
            }
            $filtradas[] = $regla;
        }

        // Si hay reglas específicas del documento, usamos solo esas (+ las del mismo ámbito sin tipo).
        // Si no hay ninguna aplicable, caer al fallback.
        if ($filtradas === []) {
            return self::reglasFallback();
        }

        return $filtradas;
    }

    /**
     * @return list<AlertaRegla>
     */
    private static function cargarReglasEmpresa(?int $empresaId): array
    {
        if (self::$cacheReglas !== null && self::$cacheEmpresaId === $empresaId) {
            return self::$cacheReglas;
        }

        try {
            $table = TableRegistry::getTableLocator()->get('AlertasReglas');
            $query = $table->find()
                ->where(['activo' => 1])
                ->orderBy(['dias_antes' => 'DESC', 'orden' => 'ASC']);

            if ($empresaId !== null && $empresaId > 0) {
                $query->where([
                    'OR' => [
                        'AlertasReglas.empresa_id IS' => null,
                        'AlertasReglas.empresa_id' => $empresaId,
                    ],
                ]);
            } else {
                $query->where(['AlertasReglas.empresa_id IS' => null]);
            }

            $reglas = $query->all()->toList();
        } catch (\Throwable) {
            $reglas = [];
        }

        if ($reglas === []) {
            $reglas = self::reglasFallback();
        }

        self::$cacheReglas = $reglas;
        self::$cacheEmpresaId = $empresaId;

        return $reglas;
    }

    private static function reglaAplicaADocumento(AlertaRegla $regla, string $ambitoDoc, string $tipoDoc): bool
    {
        $ambitoRegla = self::normalizarAmbito((string)($regla->ambito ?? 'todos'));
        $tipoRegla = trim((string)($regla->tipo_documento ?? ''));

        // Ambito: "todos" aplica a cualquier entidad; si no, debe coincidir.
        if ($ambitoRegla !== 'todos' && $ambitoDoc !== '' && $ambitoRegla !== $ambitoDoc) {
            return false;
        }

        // Tipo vacío = todos los documentos del ámbito.
        if ($tipoRegla === '') {
            return true;
        }

        // Regla de un tipo concreto: solo ese documento.
        return $tipoDoc !== '' && $tipoRegla === $tipoDoc;
    }

    public static function normalizarAmbito(?string $ambito): string
    {
        $a = strtolower(trim((string)$ambito));
        if (in_array($a, ['empresa', 'vehiculo', 'operador', 'todos'], true)) {
            return $a;
        }

        return 'todos';
    }

    /** @return list<int> */
    private static function umbralesActivos(
        ?int $empresaId = null,
        ?string $ambito = null,
        ?string $tipoDocumento = null,
    ): array {
        $umbrales = array_values(array_unique(array_map(
            static fn(AlertaRegla $regla): int => (int)$regla->dias_antes,
            self::reglasActivas($empresaId, $ambito, $tipoDocumento),
        )));
        rsort($umbrales, SORT_NUMERIC);

        if ($umbrales === []) {
            $umbrales = self::UMBRALES_DIAS;
        }

        return $umbrales;
    }

    /** @return list<AlertaRegla> */
    private static function reglasFallback(): array
    {
        $niveles = [
            45 => 'info',
            30 => 'advertencia',
            15 => 'advertencia',
            7 => 'critico',
            1 => 'critico',
        ];
        $reglas = [];
        $orden = 1;
        foreach (self::UMBRALES_DIAS as $dias) {
            $regla = new AlertaRegla([
                'dias_antes' => $dias,
                'nombre' => "Aviso {$dias} días",
                'ambito' => 'todos',
                'tipo_documento' => '',
                'enviar_alerta' => 1,
                'ocultar_despues' => 0,
                'mantener_visible' => 1,
                'nivel_urgencia' => $niveles[$dias] ?? 'info',
                'activo' => 1,
                'orden' => $orden++,
                'empresa_id' => null,
            ], ['markNew' => false, 'source' => 'AlertasReglas']);
            $reglas[] = $regla;
        }

        return $reglas;
    }

    public static function limpiarCache(): void
    {
        self::$cacheReglas = null;
        self::$cacheEmpresaId = null;
    }

    /**
     * Etiqueta legible del alcance de una regla.
     */
    public static function etiquetaAlcance(?string $ambito, ?string $tipoDocumento, ?callable $labelTipo = null): string
    {
        $ambito = self::normalizarAmbito($ambito);
        $tipo = trim((string)$tipoDocumento);

        $ambitoLabel = match ($ambito) {
            'empresa' => 'Empresa',
            'vehiculo' => 'Vehículo',
            'operador' => 'Operador',
            default => 'Todos',
        };

        if ($tipo === '') {
            return $ambito === 'todos'
                ? 'Todos los documentos'
                : "Todos · {$ambitoLabel}";
        }

        $nombreTipo = $labelTipo !== null ? (string)$labelTipo($tipo, $ambito) : $tipo;

        return "{$ambitoLabel} · {$nombreTipo}";
    }
}
