<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class VehiculosTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('vehiculos');
        $this->setPrimaryKey('id');
        $this->setEntityClass('App\Model\Entity\Vehiculo');

        $this->belongsTo('Empresas', ['foreignKey' => 'empresa_id']);

        $this->hasMany('DocumentosVehiculo', [
            'foreignKey' => 'vehiculo_id',
            'className'  => 'DocumentosVehiculo',
        ]);
        $this->hasMany('Mantenimientos', [
            'foreignKey' => 'vehiculo_id',
            'sort'       => ['Mantenimientos.fecha' => 'DESC'],
        ]);
        $this->hasMany('BajasVehiculares', [
            'foreignKey' => 'vehiculo_id',
        ]);

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created_at' => 'new',
                    'updated_at' => 'always',
                ],
            ],
        ]);
    }

    public function beforeSave(\Cake\Event\EventInterface $event, \Cake\Datasource\EntityInterface $entity, \ArrayObject $options): void
    {
        if ($entity instanceof \App\Model\Entity\Vehiculo) {
            self::normalizarCamposOperacion($entity);
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('empresa_id', 'Selecciona la empresa.')
            ->notEmptyString('placas', 'Ingresa las placas.')
            ->maxLength('placas', 20, 'Máximo 20 caracteres.')
            ->notEmptyString('marca', 'Ingresa la marca.')
            ->maxLength('marca', 60, 'Máximo 60 caracteres.')
            ->notEmptyString('modelo', 'Ingresa el modelo.')
            ->maxLength('modelo', 60, 'Máximo 60 caracteres.')
            ->notEmptyString('anio', 'Ingresa el año.')
            ->integer('anio', 'El año debe ser numérico.')
            ->range('anio', [1900, 2100], 'El año debe estar entre 1900 y 2100.')
            ->notEmptyString('numero_serie', 'Ingresa el número de serie.')
            ->maxLength('numero_serie', 80, 'Máximo 80 caracteres.')
            ->allowEmptyString('numero_motor')
            ->maxLength('numero_motor', 80, 'Máximo 80 caracteres.')
            ->notEmptyString('tipo_carga', 'Selecciona el tipo de carga.')
            ->inList('estatus', ['activo', 'inactivo'], 'Estatus inválido.');

        $validator
            ->allowEmptyString('color')
            ->maxLength('color', 40, 'Máximo 40 caracteres.')
            ->allowEmptyString('peso_bruto_vehicular')
            ->numeric('peso_bruto_vehicular', 'Debe ser numérico.')
            ->allowEmptyString('num_ejes')
            ->integer('num_ejes', 'Debe ser un entero.')
            ->add('num_ejes', 'operacionAplica', [
                'rule' => static function ($value, array $context): bool {
                    if (self::requiereCamposOperacion($context['data']['tipo_carga'] ?? null)) {
                        return true;
                    }

                    return $value === null || $value === '';
                },
                'message' => 'Número de ejes no aplica para este tipo de carga.',
            ])
            ->allowEmptyString('modalidad')
            ->maxLength('modalidad', 80, 'Máximo 80 caracteres.')
            ->inList('modalidad', array_keys(self::modalidadOptions()), 'Modalidad inválida.')
            ->add('modalidad', 'operacionAplica', [
                'rule' => static function ($value, array $context): bool {
                    if (self::requiereCamposOperacion($context['data']['tipo_carga'] ?? null)) {
                        return true;
                    }

                    return $value === null || $value === '';
                },
                'message' => 'Modalidad no aplica para este tipo de carga.',
            ])
            ->allowEmptyString('tipo_servicio')
            ->maxLength('tipo_servicio', 80, 'Máximo 80 caracteres.')
            ->add('tipo_servicio', 'operacionValida', [
                'rule' => static function ($value, array $context): bool {
                    $tipoCarga = $context['data']['tipo_carga'] ?? null;
                    if (!self::requiereCamposOperacion($tipoCarga)) {
                        return $value === null || $value === '';
                    }
                    if ($value === null || $value === '') {
                        return true;
                    }

                    $modalidad = $context['data']['modalidad'] ?? null;

                    return in_array(
                        (string)$value,
                        array_keys(self::tipoServicioOptionsPara(is_string($modalidad) ? $modalidad : null)),
                        true,
                    );
                },
                'message' => 'Tipo de servicio no aplica para la modalidad seleccionada.',
            ]);

        return $validator;
    }

    /** @return array<string, string> */
    public static function tipoCargaOptions(): array
    {
        return (new \App\Service\CatalogosOperacionService())->tipoCargaOptions();
    }

    /** Tipos de documento considerados obligatorios para cumplimiento básico. */
    public static function documentosRequeridos(): array
    {
        return TiposDocumentoTable::catalogo()->codigosRequeridosNormativoVehiculo();
    }

    public static function esMaterialPeligroso(?string $tipoCarga): bool
    {
        return (new \App\Service\CatalogosOperacionService())->esMaterialPeligroso($tipoCarga);
    }

    /** Campos Carta Porte (modalidad, tipo de servicio, ejes) solo aplican a carga comercial federal. */
    public static function requiereCamposOperacion(?string $tipoCarga): bool
    {
        return (new \App\Service\CatalogosOperacionService())->requiereModalidad($tipoCarga);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function sanitizarDatosOperacion(array $data): array
    {
        $tipoCarga = isset($data['tipo_carga']) ? (string)$data['tipo_carga'] : null;
        if (!self::requiereCamposOperacion($tipoCarga)) {
            $data['modalidad'] = null;
            $data['tipo_servicio'] = null;
            $data['num_ejes'] = null;

            return $data;
        }

        $modalidad = isset($data['modalidad']) ? (string)$data['modalidad'] : '';
        $permitidos = array_keys(self::tipoServicioOptionsPara($modalidad !== '' ? $modalidad : null));
        $tipoServicio = isset($data['tipo_servicio']) ? (string)$data['tipo_servicio'] : '';
        if ($tipoServicio === '' || !in_array($tipoServicio, $permitidos, true)) {
            $data['tipo_servicio'] = null;
        }

        if ($modalidad === 'TRANSPORTE PRIVADO') {
            $data['tipo_servicio'] = null;
        }

        if (isset($data['num_ejes']) && $data['num_ejes'] !== '' && $data['num_ejes'] !== null) {
            $data['num_ejes'] = (int)$data['num_ejes'];
        }

        return $data;
    }

    public static function normalizarCamposOperacion(\App\Model\Entity\Vehiculo $vehiculo): void
    {
        $data = self::sanitizarDatosOperacion([
            'tipo_carga' => $vehiculo->tipo_carga ?? null,
            'modalidad' => $vehiculo->modalidad ?? null,
            'tipo_servicio' => $vehiculo->tipo_servicio ?? null,
            'num_ejes' => $vehiculo->num_ejes ?? null,
        ]);

        $vehiculo->modalidad = $data['modalidad'];
        $vehiculo->tipo_servicio = $data['tipo_servicio'];
        $vehiculo->num_ejes = $data['num_ejes'];
    }

    /**
     * @param iterable<\App\Model\Entity\DocumentoVehiculo> $documentos
     */
    public static function peorEstatusDocumentos(iterable $documentos): string
    {
        $worst = 'vigente';
        foreach ($documentos as $doc) {
            if ($doc->estatus === 'vencido') {
                return 'vencido';
            }
            if ($doc->estatus === 'por_vencer') {
                $worst = 'por_vencer';
            }
        }

        return $worst;
    }

    /**
     * @param iterable<\App\Model\Entity\DocumentoVehiculo> $documentos
     */
    public static function resumenDocumentos(iterable $documentos): array
    {
        $vigente = $porVencer = $vencido = 0;
        foreach ($documentos as $doc) {
            match ($doc->estatus ?? '') {
                'vencido' => $vencido++,
                'por_vencer' => $porVencer++,
                default => $vigente++,
            };
        }
        $total = $vigente + $porVencer + $vencido;
        $pct = $total > 0
            ? (int)round((($vigente + $porVencer) / $total) * 100)
            : 0;

        return compact('total', 'vigente', 'porVencer', 'vencido', 'pct');
    }

    /** @return array<string, string> */
    public static function modalidadOptions(): array
    {
        return (new \App\Service\CatalogosOperacionService())->modalidadOptions();
    }

    /** @return array<string, string> */
    public static function tipoServicioOptions(): array
    {
        return (new \App\Service\CatalogosOperacionService())->tipoServicioOptions();
    }

    /** @return array<string, string> */
    public static function tipoServicioOptionsPara(?string $modalidad): array
    {
        return (new \App\Service\CatalogosOperacionService())->tipoServicioOptions($modalidad);
    }

    /** @return array<string, list<string>> */
    public static function tipoServicioPorModalidad(): array
    {
        $map = (new \App\Service\CatalogosOperacionService())->tipoServicioPorModalidad();
        $out = [];
        foreach ($map as $modalidad => $opts) {
            $out[$modalidad] = array_keys($opts);
        }

        return $out;
    }

    /**
     * Devuelve el peor estatus entre los documentos de un conjunto de IDs de vehículo.
     * Usado para calcular alertas del sidebar.
     */
    public function getEstatusDocumentos(int $vehiculoId): string
    {
        $docs = $this->DocumentosVehiculo->find()
            ->where(['vehiculo_id' => $vehiculoId])
            ->all();

        $worst = 'vigente';
        foreach ($docs as $doc) {
            if ($doc->estatus === 'vencido') {
                return 'vencido';
            }
            if ($doc->estatus === 'por_vencer') {
                $worst = 'por_vencer';
            }
        }

        return $worst;
    }
}
