<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class CatalogoMercanciasTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('catalogo_mercancias');
        $this->setPrimaryKey('id');
        $this->setEntityClass('App\Model\Entity\CatalogoMercancia');
        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('bienes_transp', 'La clave BienesTransp es obligatoria.')
            ->maxLength('bienes_transp', 8)

            ->notEmptyString('descripcion', 'La descripción es obligatoria.')
            ->maxLength('descripcion', 255)

            ->notEmptyString('clave_unidad', 'La clave de unidad es obligatoria.')
            ->maxLength('clave_unidad', 3)

            ->allowEmptyString('peso_en_kg')
            ->decimal('peso_en_kg', null, 'Debe ser un número decimal.')

            ->boolean('material_peligroso')

            ->allowEmptyString('cve_material_peligroso')
            ->maxLength('cve_material_peligroso', 4)

            ->allowEmptyString('embalaje')
            ->maxLength('embalaje', 4);

        return $validator;
    }
}
