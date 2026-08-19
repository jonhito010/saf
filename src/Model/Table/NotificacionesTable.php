<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class NotificacionesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('notificaciones');
        $this->setPrimaryKey('id');
        $this->setEntityClass('App\Model\Entity\Notificacion');
        $this->setDisplayField('titulo');

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
            ->integer('usuario_id')
            ->requirePresence('usuario_id', 'create')
            ->notEmptyString('usuario_id')
            ->allowEmptyString('empresa_id')
            ->integer('empresa_id')
            ->allowEmptyString('alerta_id')
            ->integer('alerta_id')
            ->notEmptyString('titulo')
            ->maxLength('titulo', 200)
            ->notEmptyString('mensaje')
            ->maxLength('mensaje', 500)
            ->allowEmptyString('url_destino')
            ->maxLength('url_destino', 255)
            ->notEmptyString('tipo')
            ->maxLength('tipo', 40)
            ->allowEmptyString('leida')
            ->boolean('leida')
            ->allowEmptyDateTime('leida_at');

        return $validator;
    }
}
