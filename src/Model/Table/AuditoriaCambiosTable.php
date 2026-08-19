<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class AuditoriaCambiosTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('auditoria_cambios');
        $this->setPrimaryKey('id');
        $this->setEntityClass('App\Model\Entity\AuditoriaCambio');
        $this->setDisplayField('campo');

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
            ->allowEmptyString('empresa_id')
            ->integer('empresa_id')
            ->allowEmptyString('usuario_id')
            ->integer('usuario_id')
            ->notEmptyString('entidad_tipo')
            ->maxLength('entidad_tipo', 60)
            ->integer('entidad_id')
            ->requirePresence('entidad_id', 'create')
            ->notEmptyString('entidad_id')
            ->notEmptyString('campo')
            ->maxLength('campo', 80)
            ->allowEmptyString('valor_anterior')
            ->allowEmptyString('valor_nuevo')
            ->allowEmptyString('ip')
            ->maxLength('ip', 45);

        return $validator;
    }
}
