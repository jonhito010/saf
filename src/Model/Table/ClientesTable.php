<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Clientes (receptores) para CFDI.
 *
 * @method \App\Model\Entity\Cliente newEmptyEntity()
 * @method \App\Model\Entity\Cliente newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Cliente get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Cliente|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class ClientesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('clientes');
        $this->setPrimaryKey('id');
        $this->setDisplayField('nombre');
        $this->setEntityClass('App\Model\Entity\Cliente');

        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('nombre')
            ->maxLength('nombre', 255)
            ->requirePresence('nombre', 'create')
            ->notEmptyString('nombre');

        $validator
            ->scalar('rfc')
            ->maxLength('rfc', 13)
            ->allowEmptyString('rfc');

        $validator
            ->scalar('regimenFiscal')
            ->maxLength('regimenFiscal', 5)
            ->allowEmptyString('regimenFiscal');

        $validator
            ->scalar('usoCFDI')
            ->maxLength('usoCFDI', 5)
            ->allowEmptyString('usoCFDI');

        $validator
            ->scalar('domicilioFiscal')
            ->maxLength('domicilioFiscal', 10)
            ->allowEmptyString('domicilioFiscal');

        $validator
            ->email('correo')
            ->maxLength('correo', 255)
            ->allowEmptyString('correo');

        $validator
            ->scalar('direccionCompleta')
            ->maxLength('direccionCompleta', 512)
            ->allowEmptyString('direccionCompleta');

        $validator
            ->integer('eliminado')
            ->allowEmptyString('eliminado');

        return $validator;
    }
}

