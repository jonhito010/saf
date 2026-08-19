<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class EmpresasTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('empresas');
        $this->setPrimaryKey('id');
        $this->setEntityClass('App\Model\Entity\Empresa');

        $this->hasMany('DocumentosEmpresa', [
            'foreignKey' => 'empresa_id',
        ]);
        $this->hasMany('Vehiculos', [
            'foreignKey' => 'empresa_id',
        ]);
        $this->hasMany('Operadores', [
            'foreignKey' => 'empresa_id',
        ]);

        // Compatibilidad: BD nueva (created_at) vs Cake clásico (created/modified)
        $schema = $this->getSchema();
        if ($schema->hasColumn('created_at') && $schema->hasColumn('updated_at')) {
            $this->addBehavior('Timestamp', [
                'events' => [
                    'Model.beforeSave' => [
                        'created_at' => 'new',
                        'updated_at' => 'always',
                    ],
                ],
            ]);
        } elseif ($schema->hasColumn('created') && $schema->hasColumn('modified')) {
            $this->addBehavior('Timestamp');
        }

        $this->setDisplayField($this->razonSocialField());
    }

    /**
     * Formulario usa `nombre`; algunas BD tienen solo `razon_social`.
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        $schema = $this->getSchema();
        if ($schema->hasColumn('razon_social') && !$schema->hasColumn('nombre') && isset($data['nombre'])) {
            $data['razon_social'] = $data['nombre'];
            unset($data['nombre']);
        }
    }

    /**
     * Campo de razón social según columnas reales (validación alinear con beforeMarshal).
     */
    public function razonSocialField(): string
    {
        $schema = $this->getSchema();

        return $schema->hasColumn('nombre') ? 'nombre' : 'razon_social';
    }

    public function validationDefault(Validator $validator): Validator
    {
        $nombreCol = $this->razonSocialField();

        $validator
            // Identificación
            ->notEmptyString($nombreCol, 'La razón social es obligatoria.')
            ->maxLength($nombreCol, 255, 'Máximo 255 caracteres.')
            ->notEmptyString('rfc', 'El RFC es obligatorio.')
            ->maxLength('rfc', 13, 'El RFC no puede superar 13 caracteres.')
            ->regex('rfc',
                '/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/i',
                'RFC inválido. Formato: 3-4 letras + 6 dígitos + 3 alfanuméricos.')
            ->notEmptyString('estatus')
            ->inList('estatus', ['activa', 'suspendida', 'baja'], 'Estatus inválido.')

            // Contacto
            ->allowEmptyString('telefono')
            ->regex('telefono',
                '/^[\+]?[\d\s\(\)\-]{7,20}$/',
                'Teléfono inválido. Ej: +52 81 1234-5678.')
            ->allowEmptyString('email')
            ->email('email', false, 'Correo electrónico inválido.')
            ->maxLength('email', 150, 'Máximo 150 caracteres.')

            // Domicilio
            ->allowEmptyString('cp')
            ->regex('cp', '/^[0-9]{5}$/', 'El C.P. debe tener exactamente 5 dígitos.')
            ->allowEmptyString('colonia')
            ->maxLength('colonia', 100)
            ->allowEmptyString('municipio')
            ->maxLength('municipio', 100)
            ->allowEmptyString('estado')
            ->maxLength('estado', 100)

            // CFDI
            ->allowEmptyString('regimen_fiscal')
            ->maxLength('regimen_fiscal', 5)
            ->allowEmptyString('lugar_expedicion')
            ->regex('lugar_expedicion',
                '/^[0-9]{5}$/',
                'El lugar de expedición debe ser un C.P. de 5 dígitos.')
            ->allowEmptyString('serie_cfdi')
            ->maxLength('serie_cfdi', 45)
            ->allowEmptyString('folio_cfdi')
            ->maxLength('folio_cfdi', 45)
            ->allowEmptyString('folio_inicial')
            ->maxLength('folio_inicial', 15)
            ->allowEmptyString('folios_disponibles')
            ->integer('folios_disponibles', 'Debe ser un número entero.')
            ->allowEmptyString('pem_key')
            ->allowEmptyString('pem_cer')
            ->allowEmptyString('numero_certificado')
            ->maxLength('numero_certificado', 45)
            ->allowEmptyDate('fecha_inicio_cert')
            ->allowEmptyDate('fecha_fin_cert');

        return $validator;
    }
}
