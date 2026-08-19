<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class VerificacionesHistorialTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('verificaciones_historial');
        $this->setPrimaryKey('id');
        $this->setEntityClass('App\Model\Entity\VerificacionHistorial');
        $this->setDisplayField('tipo');

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
            ->integer('empresa_id')
            ->requirePresence('empresa_id', 'create')
            ->notEmptyString('empresa_id')
            ->allowEmptyString('operador_id')
            ->integer('operador_id')
            ->allowEmptyString('vehiculo_id')
            ->integer('vehiculo_id')
            ->integer('usuario_id')
            ->requirePresence('usuario_id', 'create')
            ->notEmptyString('usuario_id')
            ->notEmptyString('tipo')
            ->maxLength('tipo', 60)
            ->notEmptyString('resultado')
            ->maxLength('resultado', 40)
            ->allowEmptyString('detalle')
            ->dateTime('fecha')
            ->requirePresence('fecha', 'create')
            ->notEmptyDateTime('fecha');

        return $validator;
    }
}
