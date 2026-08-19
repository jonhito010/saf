<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SistemaLogsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('sistema_logs');
        $this->setPrimaryKey('id');
        $this->setEntityClass('App\Model\Entity\SistemaLog');
        $this->setDisplayField('descripcion');

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
            ->allowEmptyString('usuario_id')
            ->integer('usuario_id')
            ->allowEmptyString('empresa_id')
            ->integer('empresa_id')
            ->notEmptyString('accion')
            ->maxLength('accion', 60)
            ->allowEmptyString('modulo')
            ->maxLength('modulo', 60)
            ->notEmptyString('descripcion')
            ->maxLength('descripcion', 500)
            ->allowEmptyString('detalle_json')
            ->allowEmptyString('ip')
            ->maxLength('ip', 45)
            ->allowEmptyString('user_agent')
            ->maxLength('user_agent', 255)
            ->allowEmptyString('nivel')
            ->inList('nivel', ['info', 'warning', 'error']);

        return $validator;
    }
}
