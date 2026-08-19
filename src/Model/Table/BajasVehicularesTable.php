<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class BajasVehicularesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('bajas_vehiculares');
        $this->setPrimaryKey('id');
        $this->setEntityClass('App\Model\Entity\BajaVehicular');

        $this->belongsTo('Vehiculos', ['foreignKey' => 'vehiculo_id']);
        $this->belongsTo('Empresas',  ['foreignKey' => 'empresa_id']);

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
            ->notEmptyString('vehiculo_id', 'Selecciona el vehículo.')
            ->notEmptyString('motivo_baja', 'Selecciona el motivo.')
            ->inList('motivo_baja', ['siniestro', 'reemplazo', 'venta', 'robo', 'otro'], 'Motivo inválido.')
            ->notEmptyString('estatus_tramite', 'Selecciona el estatus del trámite.')
            ->inList('estatus_tramite', ['iniciado', 'pendiente_placas', 'placas_devueltas', 'tramite_baja', 'completado'], 'Estatus inválido.')
            ->allowEmptyDate('fecha_baja')
            ->allowEmptyString('numero_folio_baja')
            ->maxLength('numero_folio_baja', 60, 'Máximo 60 caracteres.')
            ->allowEmptyString('notas')
            ->maxLength('notas', 500, 'Máximo 500 caracteres.');

        return $validator;
    }

    /** Pasos del trámite en orden */
    public static function pasos(): array
    {
        return [
            1 => ['key' => 'iniciado',          'label' => 'Registro de baja'],
            2 => ['key' => 'pendiente_placas',   'label' => "Carga de\ndocumentos"],
            3 => ['key' => 'placas_devueltas',   'label' => "Devolución\nde placas"],
            4 => ['key' => 'tramite_baja',       'label' => 'Trámite SICT'],
            5 => ['key' => 'completado',          'label' => 'Completado'],
        ];
    }

    /** Número de paso a partir del estatus_tramite */
    public static function pasoActual(string $estatus): int
    {
        return array_search($estatus, array_column(static::pasos(), 'key', null)) ?: 1;
    }
}
