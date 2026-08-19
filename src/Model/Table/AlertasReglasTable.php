<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class AlertasReglasTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('alertas_reglas');
        $this->setPrimaryKey('id');
        $this->setEntityClass('App\Model\Entity\AlertaRegla');
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
            ->integer('dias_antes')
            ->requirePresence('dias_antes', 'create')
            ->notEmptyString('dias_antes')
            ->notEmptyString('nombre')
            ->maxLength('nombre', 80)
            ->allowEmptyString('ambito')
            ->inList('ambito', ['todos', 'empresa', 'vehiculo', 'operador'])
            ->allowEmptyString('tipo_documento')
            ->maxLength('tipo_documento', 60)
            ->allowEmptyString('enviar_alerta')
            ->boolean('enviar_alerta')
            ->allowEmptyString('ocultar_despues')
            ->boolean('ocultar_despues')
            ->allowEmptyString('mantener_visible')
            ->boolean('mantener_visible')
            ->allowEmptyString('nivel_urgencia')
            ->inList('nivel_urgencia', ['info', 'advertencia', 'critico'])
            ->allowEmptyString('activo')
            ->boolean('activo')
            ->allowEmptyString('orden')
            ->integer('orden')
            ->allowEmptyString('empresa_id')
            ->integer('empresa_id');

        return $validator;
    }
}
