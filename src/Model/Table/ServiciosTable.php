<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Productos y servicios (clave y unidad SAT) para CFDI.
 *
 * @method \App\Model\Entity\Servicio newEmptyEntity()
 * @method \App\Model\Entity\Servicio newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Servicio get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Servicio|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class ServiciosTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('servicios');
        $this->setPrimaryKey('id');
        $this->setDisplayField('descripcion');
        $this->setEntityClass('App\Model\Entity\Servicio');

        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('descripcion')
            ->maxLength('descripcion', 512)
            ->requirePresence('descripcion', 'create')
            ->notEmptyString('descripcion');

        $validator
            ->scalar('numeroIdentificacion')
            ->maxLength('numeroIdentificacion', 120)
            ->allowEmptyString('numeroIdentificacion');

        $validator
            ->scalar('claveSAT')
            ->maxLength('claveSAT', 16)
            ->allowEmptyString('claveSAT');

        $validator
            ->decimal('precio')
            ->allowEmptyString('precio');

        $validator
            ->scalar('unidadSAT')
            ->maxLength('unidadSAT', 10)
            ->allowEmptyString('unidadSAT');

        $validator
            ->scalar('objetoImp')
            ->maxLength('objetoImp', 4)
            ->allowEmptyString('objetoImp');

        $validator
            ->integer('eliminado')
            ->allowEmptyString('eliminado');

        return $validator;
    }
}
