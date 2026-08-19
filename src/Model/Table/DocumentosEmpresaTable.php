<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Utility\DocumentoUtil;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class DocumentosEmpresaTable extends Table
{
    public const RUTA_SIN_ARCHIVO = '—';

    /** @return array<string, string> */
    public static function tiposDocumentoCorporativos(): array
    {
        return TiposDocumentoTable::catalogo()->opcionesEmpresaCorporativos();
    }

    /** @return array<string, string> */
    public static function tiposDocumentoAdicionales(): array
    {
        return TiposDocumentoTable::catalogo()->opcionesEmpresaAdicionales();
    }

    /** @return array<string, string> */
    public static function tiposDocumento(): array
    {
        return TiposDocumentoTable::catalogo()->opcionesEmpresaTodos();
    }

    public static function esTipoAdicional(string $tipo): bool
    {
        return TiposDocumentoTable::catalogo()->esAdicional($tipo);
    }

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('documentos_empresa');
        $this->setPrimaryKey('id');
        $this->setEntityClass('App\Model\Entity\DocumentoEmpresa');

        $this->belongsTo('Empresas', ['foreignKey' => 'empresa_id']);

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
        $fecha = $entity->get('fecha_vencimiento');
        if ($fecha !== null && $fecha !== '') {
            $entity->set('estatus', DocumentoUtil::estatusDesdeFecha($fecha));
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('empresa_id', 'Selecciona la empresa.')
            ->notEmptyString('tipo_documento', 'Ingresa el tipo de documento.')
            ->maxLength('tipo_documento', 60, 'Máximo 60 caracteres.')
            ->notEmptyString('estatus', 'Selecciona el estatus.')
            ->inList('estatus', ['vigente', 'por_vencer', 'vencido'], 'Estatus inválido.')
            ->allowEmptyDate('fecha_vencimiento')
            ->notEmptyString('nombre_archivo', 'Indica el nombre del archivo.', 'create')
            ->maxLength('nombre_archivo', 255, 'Máximo 255 caracteres.')
            ->notEmptyString('ruta_archivo', 'Adjunta el archivo del documento.', 'create')
            ->maxLength('ruta_archivo', 255, 'Máximo 255 caracteres.');

        return $validator;
    }

    public static function tipoLabel(string $tipo): string
    {
        return TiposDocumentoTable::catalogo()->etiqueta($tipo, 'empresa');
    }
}
