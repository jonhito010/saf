<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class TiposDocumentoTable extends Table
{
    /** @var list<\App\Model\Entity\TipoDocumento>|null */
    private static ?array $cacheTodos = null;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('tipos_documento');
        $this->setPrimaryKey('id');
        $this->setEntityClass('App\Model\Entity\TipoDocumento');
        $this->setDisplayField('nombre');

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created_at' => 'new',
                    'updated_at' => 'always',
                ],
            ],
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('codigo')
            ->maxLength('codigo', 60)
            ->notEmptyString('nombre')
            ->maxLength('nombre', 150)
            ->notEmptyString('ambito')
            ->inList('ambito', ['empresa', 'vehiculo', 'operador']);

        return $validator;
    }

    public static function limpiarCache(): void
    {
        self::$cacheTodos = null;
    }

    /** @return \App\Model\Table\TiposDocumentoTable */
    public static function catalogo(): self
    {
        return TableRegistry::getTableLocator()->get('TiposDocumento');
    }

    /** @return list<\App\Model\Entity\TipoDocumento> */
    private function todosActivos(): array
    {
        if (self::$cacheTodos !== null) {
            return self::$cacheTodos;
        }

        try {
            self::$cacheTodos = $this->find()
                ->where(['activo' => 1])
                ->orderBy(['ambito' => 'ASC', 'orden' => 'ASC', 'nombre' => 'ASC'])
                ->all()
                ->toList();
        } catch (\Throwable) {
            self::$cacheTodos = [];
        }

        return self::$cacheTodos;
    }

    /**
     * @return list<\App\Model\Entity\TipoDocumento>
     */
    private function filtrar(string $ambito, ?string $categoria = null, ?callable $extra = null): array
    {
        return array_values(array_filter(
            $this->todosActivos(),
            static function ($row) use ($ambito, $categoria, $extra): bool {
                if ($row->ambito !== $ambito) {
                    return false;
                }
                if ($categoria !== null && $row->categoria !== $categoria) {
                    return false;
                }
                if ($extra !== null && !$extra($row)) {
                    return false;
                }

                return true;
            },
        ));
    }

    /** @return array<string, string> */
    private function aOpciones(array $filas): array
    {
        $out = [];
        foreach ($filas as $row) {
            $out[(string)$row->codigo] = (string)$row->nombre;
        }

        return $out;
    }

    /** @return array<string, string> */
    public function opcionesEmpresaCorporativos(): array
    {
        return $this->aOpciones($this->filtrar('empresa', 'corporativo'));
    }

    /** @return array<string, string> */
    public function opcionesEmpresaAdicionales(): array
    {
        return $this->aOpciones($this->filtrar('empresa', 'adicional'));
    }

    /** @return array<string, string> */
    public function opcionesEmpresaTodos(): array
    {
        return $this->aOpciones($this->filtrar('empresa'));
    }

    /** @return array<string, string> */
    public function opcionesVehiculo(): array
    {
        return $this->aOpciones($this->filtrar('vehiculo'));
    }

    /** @return array<string, string> */
    public function opcionesOperador(): array
    {
        return $this->aOpciones($this->filtrar('operador'));
    }

    public function etiqueta(string $codigo, ?string $ambito = null): string
    {
        foreach ($this->todosActivos() as $row) {
            if ($row->codigo === $codigo && ($ambito === null || $row->ambito === $ambito)) {
                return (string)$row->nombre;
            }
        }

        return $codigo;
    }

    public function esAdicional(string $codigo): bool
    {
        foreach ($this->filtrar('empresa', 'adicional') as $row) {
            if ($row->codigo === $codigo) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    public function codigosRequeridosNormativoVehiculo(): array
    {
        return array_map(
            static fn($r) => (string)$r->codigo,
            $this->filtrar('vehiculo', null, static fn($r) => (bool)$r->requerido_normativo),
        );
    }

    /** @return list<string> */
    public function codigosRequeridosMpVehiculo(): array
    {
        return array_map(
            static fn($r) => (string)$r->codigo,
            $this->filtrar('vehiculo', 'materiales_peligrosos', static fn($r) => (bool)$r->requerido_mp),
        );
    }

    /**
     * @return array<string, array{label: string, descripcion: string, retencion: string, icon: string}>
     */
    public function catalogoMpVehiculo(): array
    {
        $out = [];
        foreach ($this->filtrar('vehiculo', 'materiales_peligrosos') as $row) {
            $out[(string)$row->codigo] = [
                'label' => (string)$row->nombre,
                'descripcion' => (string)($row->descripcion ?? ''),
                'retencion' => (string)($row->retencion ?? ''),
                'icon' => (string)($row->icono ?? '📎'),
            ];
        }

        return $out;
    }

    /**
     * @return array<string, array{label: string, descripcion: string, retencion: string}>
     */
    public function catalogoNormativoFlota(): array
    {
        $out = [];
        foreach ($this->filtrar('vehiculo', 'normativo') as $row) {
            $out[(string)$row->codigo] = [
                'label' => (string)$row->nombre,
                'descripcion' => (string)($row->descripcion ?? ''),
                'retencion' => (string)($row->retencion ?? ''),
            ];
        }

        return $out;
    }

    /** @return array<string, string> */
    public function opcionesMpVehiculo(): array
    {
        return $this->aOpciones($this->filtrar('vehiculo', 'materiales_peligrosos'));
    }

    /** @return list<string> */
    public function codigosRequeridosEmpresa(): array
    {
        return array_map(
            static fn($r) => (string)$r->codigo,
            $this->filtrar('empresa', 'corporativo', static fn($r) => (bool)$r->requerido_cumplimiento),
        );
    }

    /** @return list<string> */
    public function codigosRequeridosMpEmpresa(): array
    {
        return array_map(
            static fn($r) => (string)$r->codigo,
            $this->filtrar('empresa', 'adicional', static fn($r) => (bool)$r->requerido_mp),
        );
    }

    /** @return list<string> */
    public function codigosRequeridosOperador(): array
    {
        return array_map(
            static fn($r) => (string)$r->codigo,
            $this->filtrar('operador', null, static fn($r) => (bool)$r->requerido_cumplimiento),
        );
    }

    /** @return list<string> */
    public function codigosRequeridosMpOperador(): array
    {
        return array_map(
            static fn($r) => (string)$r->codigo,
            $this->filtrar('operador', null, static fn($r) => (bool)$r->requerido_mp),
        );
    }

    /** @return list<string> */
    public function codigosFormularioOperador(): array
    {
        return array_map(
            static fn($r) => (string)$r->codigo,
            $this->filtrar('operador', null, static fn($r) => (bool)$r->visible_formulario),
        );
    }

    public function codigoExiste(string $codigo, string $ambito, ?string $categoria = null): bool
    {
        foreach ($this->filtrar($ambito, $categoria) as $row) {
            if ($row->codigo === $codigo) {
                return true;
            }
        }

        return false;
    }

    public function notaRetencionFlotaPorcentaje(): string
    {
        return 'Retención documental al 4% de los vehículos — por confirmar con el cliente.';
    }
}
