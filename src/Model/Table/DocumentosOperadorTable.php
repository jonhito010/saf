<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class DocumentosOperadorTable extends Table
{
    public const RUTA_SIN_ARCHIVO = '—';

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('documentos_operador');
        $this->setPrimaryKey('id');
        $this->setEntityClass('App\Model\Entity\DocumentoOperador');

        $this->belongsTo('Operadores', [
            'foreignKey' => 'operador_id',
            // Inflector: Operadores → Operadore; forzamos el nombre esperado en entidades/vistas.
            'propertyName' => 'operador',
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
        $ruta = isset($data['ruta_archivo']) ? trim((string)$data['ruta_archivo']) : '';
        if ($ruta === '') {
            return;
        }
        $base = basename(str_replace('\\', '/', $ruta));
        $nom = isset($data['nombre_archivo']) ? trim((string)$data['nombre_archivo']) : '';
        if ($nom === '') {
            $data['nombre_archivo'] = $base !== '' ? $base : 'documento';
        }
    }

    public function beforeSave(EventInterface $event, EntityInterface $entity): void
    {
        $ruta = trim((string)($entity->get('ruta_archivo') ?? ''));
        $nom = trim((string)($entity->get('nombre_archivo') ?? ''));
        if ($ruta !== '' && $nom === '') {
            $base = basename(str_replace('\\', '/', $ruta));
            $entity->set('nombre_archivo', $base !== '' ? $base : 'documento');
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('operador_id', 'Selecciona el operador.')
            ->notEmptyString('tipo_documento', 'Ingresa el tipo de documento.')
            ->maxLength('tipo_documento', 60, 'Máximo 60 caracteres.')
            ->notEmptyString('estatus', 'Selecciona el estatus.')
            ->inList('estatus', ['vigente', 'por_vencer', 'vencido', 'faltante'], 'Estatus inválido.')
            ->allowEmptyDate('fecha_vencimiento')
            ->notEmptyString('nombre_archivo', 'Indica el nombre del archivo.', 'create')
            ->maxLength('nombre_archivo', 255, 'Máximo 255 caracteres.')
            ->notEmptyString('ruta_archivo', 'Adjunta el archivo del documento.', 'create')
            ->maxLength('ruta_archivo', 255, 'Máximo 255 caracteres.');

        return $validator;
    }

    /** @return array<string, string> */
    public static function tiposDocumento(): array
    {
        return TiposDocumentoTable::catalogo()->opcionesOperador();
    }

    public static function tipoLabel(string $tipo): string
    {
        return TiposDocumentoTable::catalogo()->etiqueta($tipo, 'operador');
    }

    /** @return list<string> */
    public static function tiposRequeridos(): array
    {
        return TiposDocumentoTable::catalogo()->codigosRequeridosOperador();
    }

    /** @return list<string> */
    public static function tiposRequeridosMaterialesPeligrosos(): array
    {
        return TiposDocumentoTable::catalogo()->codigosRequeridosMpOperador();
    }

    /** @return list<string> */
    public static function tiposFormularioOperador(): array
    {
        return TiposDocumentoTable::catalogo()->codigosFormularioOperador();
    }

    /**
     * Documentos obligatorios al registrar/editar (requeridos de cumplimiento visibles en el formulario).
     *
     * @return list<string>
     */
    public static function tiposObligatoriosFormulario(): array
    {
        return array_values(array_intersect(
            self::tiposRequeridos(),
            self::tiposFormularioOperador(),
        ));
    }
}
