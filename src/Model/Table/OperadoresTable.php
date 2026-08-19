<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class OperadoresTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('operadores');
        $this->setPrimaryKey('id');
        $this->setEntityClass('App\Model\Entity\Operador');

        $this->belongsTo('Empresas', ['foreignKey' => 'empresa_id']);

        $this->hasMany('DocumentosOperador', [
            'foreignKey' => 'operador_id',
            'className'  => 'DocumentosOperador',
        ]);

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created_at' => 'new',
                    'updated_at' => 'always',
                ],
            ],
        ]);
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        foreach (['nombre', 'apellido_paterno', 'apellido_materno'] as $campo) {
            if (!isset($data[$campo])) {
                continue;
            }
            $val = preg_replace('/[^A-ZÁÉÍÓÚÑÜ\s]/iu', '', (string)$data[$campo]);
            $data[$campo] = mb_strtoupper(trim($val), 'UTF-8');
        }
        if (isset($data['curp'])) {
            $data['curp'] = mb_strtoupper(preg_replace('/\s+/', '', (string)$data['curp']), 'UTF-8');
        }
        if (isset($data['rfc'])) {
            $data['rfc'] = mb_strtoupper(preg_replace('/\s+/', '', (string)$data['rfc']), 'UTF-8');
        }
        if (isset($data['num_licencia'])) {
            $data['num_licencia'] = mb_strtoupper(preg_replace('/\s+/', '', (string)$data['num_licencia']), 'UTF-8');
        }
        if (isset($data['telefono'])) {
            $data['telefono'] = preg_replace('/\D+/', '', (string)$data['telefono']);
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('empresa_id', 'Selecciona la empresa.')
            ->notEmptyString('nombre', 'Ingresa el nombre.')
            ->maxLength('nombre', 80, 'Máximo 80 caracteres.')
            ->regex('nombre', '/^[A-ZÁÉÍÓÚÑÜ\s]+$/u', 'Solo letras mayúsculas.')
            ->notEmptyString('apellido_paterno', 'Ingresa el apellido paterno.')
            ->maxLength('apellido_paterno', 80, 'Máximo 80 caracteres.')
            ->regex('apellido_paterno', '/^[A-ZÁÉÍÓÚÑÜ\s]+$/u', 'Solo letras mayúsculas.')
            ->allowEmptyString('apellido_materno')
            ->maxLength('apellido_materno', 80, 'Máximo 80 caracteres.')
            ->regex('apellido_materno', '/^[A-ZÁÉÍÓÚÑÜ\s]+$/u', 'Solo letras mayúsculas.')
            ->allowEmptyString('curp')
            ->lengthBetween('curp', [18, 18], 'La CURP debe tener exactamente 18 caracteres.')
            ->regex(
                'curp',
                '/^[A-Z][AEIOUX][A-Z]{2}\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[HM](AS|B[CS]|C[CLMSH]|D[FG]|G[TR]|HG|JC|M[CNS]|N[ETL]|OC|PL|Q[TR]|S[PLR]|T[CSL]|VZ|YN|ZS)[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9]\d$/',
                'CURP con formato inválido. Debe ser de 18 caracteres (ej. GARC850101HDFRRN09).'
            )
            ->allowEmptyString('telefono')
            ->lengthBetween('telefono', [10, 10], 'El teléfono debe tener exactamente 10 dígitos.')
            ->regex(
                'telefono',
                '/^[0-9]{10}$/',
                'Teléfono con formato inválido. Debe ser de 10 dígitos (ej. 8112345678).'
            )
            ->allowEmptyString('email')
            ->email('email', false, 'Email inválido.')
            ->allowEmptyString('foto_ruta')
            ->maxLength('foto_ruta', 255, 'Máximo 255 caracteres.')
            ->notEmptyString('estatus', 'Selecciona el estatus del operador.')
            ->inList('estatus', ['activo', 'inactivo', 'suspendido', 'baja'], 'Estatus inválido. Use activo, inactivo, suspendido o baja.')
            ->allowEmptyDate('fecha_ingreso');

        // Carta Porte 3.1 (Operador / FiguraTransporte)
        $validator
            ->allowEmptyString('rfc')
            ->maxLength('rfc', 13, 'Máximo 13 caracteres.')
            ->regex(
                'rfc',
                '/^[A-Z&Ñ]{3,4}\d{6}[A-Z0-9]{3}$/',
                'RFC inválido. Formato: 3-4 letras + 6 dígitos + 3 alfanuméricos (ej. GARC850101XXX).'
            )
            ->notEmptyString('num_licencia', 'Ingresa el número de licencia federal.')
            ->maxLength('num_licencia', 30, 'Máximo 30 caracteres.')
            ->regex(
                'num_licencia',
                '/^[A-Z0-9]{6,30}$/',
                'Número de licencia inválido. Solo letras y números (6 a 30 caracteres).'
            )
            ->allowEmptyString('num_reg_id_trib_figura')
            ->maxLength('num_reg_id_trib_figura', 40, 'Máximo 40 caracteres.')
            ->allowEmptyString('residencia_fiscal_figura')
            ->maxLength('residencia_fiscal_figura', 3, 'Máximo 3 caracteres.');

        // Domicilio (FiguraTransporte/Domicilio) — requerido en XSLT cuando se registra
        $validator
            ->allowEmptyString('domicilio_calle')
            ->maxLength('domicilio_calle', 255, 'Máximo 255 caracteres.')
            ->allowEmptyString('domicilio_numero_exterior')
            ->maxLength('domicilio_numero_exterior', 50, 'Máximo 50 caracteres.')
            ->allowEmptyString('domicilio_numero_interior')
            ->maxLength('domicilio_numero_interior', 50, 'Máximo 50 caracteres.')
            ->allowEmptyString('domicilio_colonia')
            ->maxLength('domicilio_colonia', 120, 'Máximo 120 caracteres.')
            ->allowEmptyString('domicilio_localidad')
            ->maxLength('domicilio_localidad', 120, 'Máximo 120 caracteres.')
            ->allowEmptyString('domicilio_referencia')
            ->maxLength('domicilio_referencia', 255, 'Máximo 255 caracteres.')
            ->allowEmptyString('domicilio_municipio')
            ->maxLength('domicilio_municipio', 120, 'Máximo 120 caracteres.')
            ->allowEmptyString('domicilio_estado')
            ->maxLength('domicilio_estado', 3, 'Máximo 3 caracteres.')
            ->allowEmptyString('domicilio_pais')
            ->maxLength('domicilio_pais', 3, 'Máximo 3 caracteres.')
            ->allowEmptyString('domicilio_codigo_postal')
            ->maxLength('domicilio_codigo_postal', 10, 'Máximo 10 caracteres.');

        return $validator;
    }
}
