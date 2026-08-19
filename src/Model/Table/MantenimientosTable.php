<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class MantenimientosTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('mantenimiento_bitacora');
        $this->setPrimaryKey('id');
        $this->setEntityClass('App\Model\Entity\Mantenimiento');

        $this->belongsTo('Vehiculos', ['foreignKey' => 'vehiculo_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('vehiculo_id', 'Selecciona el vehículo.')
            ->notEmptyDate('fecha', 'La fecha es obligatoria.')
            ->notEmptyString('tipo_servicio', 'Selecciona el tipo de servicio.')
            ->maxLength('tipo_servicio', 150, 'Máximo 150 caracteres.')
            ->allowEmptyString('descripcion')
            ->maxLength('descripcion', 5000, 'Máximo 5000 caracteres.')
            ->allowEmptyString('taller')
            ->maxLength('taller', 200, 'Máximo 200 caracteres.')
            ->allowEmptyString('responsable')
            ->maxLength('responsable', 200, 'Máximo 200 caracteres.')
            ->allowEmptyString('kilometraje')
            ->decimal('kilometraje', null, 'El kilometraje debe ser numérico.')
            ->allowEmptyString('costo')
            ->decimal('costo', null, 'El costo debe ser numérico.')
            ->allowEmptyString('adjunto')
            ->maxLength('adjunto', 500)
            ->allowEmptyString('notas');

        return $validator;
    }
}
