<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Comprobantes (CFDI) emitidos.
 *
 * Nota: el controlador usa `contain => ['Users']` pero el proyecto no tiene módulo Users.
 * Dejamos la tabla sin asociaciones para evitar más errores.
 *
 * @method \App\Model\Entity\Comprobante newEmptyEntity()
 * @method \App\Model\Entity\Comprobante newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Comprobante get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Comprobante|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class ComprobantesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('comprobantes');
        $this->setPrimaryKey('id');
        $this->setDisplayField('uuid');
        $this->setEntityClass('App\Model\Entity\Comprobante');

        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->allowEmptyString('uuid')
            ->maxLength('uuid', 45);

        $validator
            ->allowEmptyString('serie')
            ->maxLength('serie', 45);

        $validator
            ->allowEmptyString('folio')
            ->maxLength('folio', 45);

        $validator
            ->allowEmptyString('rfcEmisor')
            ->maxLength('rfcEmisor', 13);

        $validator
            ->allowEmptyString('nombreEmisor')
            ->maxLength('nombreEmisor', 255);

        $validator
            ->allowEmptyString('rfcReceptor')
            ->maxLength('rfcReceptor', 13);

        $validator
            ->allowEmptyString('nombreReceptor')
            ->maxLength('nombreReceptor', 255);

        $validator
            ->allowEmptyString('subtotal')
            ->maxLength('subtotal', 30);

        $validator
            ->allowEmptyString('descuento')
            ->maxLength('descuento', 30);

        $validator
            ->allowEmptyString('total')
            ->maxLength('total', 30);

        $validator
            ->allowEmptyString('formaPago')
            ->maxLength('formaPago', 5);

        $validator
            ->boolean('llevaCartaPorte')
            ->allowEmptyString('llevaCartaPorte');

        $validator
            ->integer('estatus')
            ->allowEmptyString('estatus');

        $validator
            ->allowEmptyString('xml');

        $validator
            ->allowEmptyString('pdf');

        $validator
            ->integer('enviado')
            ->allowEmptyString('enviado');

        $validator
            ->integer('user_id')
            ->allowEmptyString('user_id');

        return $validator;
    }

    /**
     * Detecta Carta Porte en los datos del formulario de timbrado.
     *
     * @param array<string, mixed> $data
     */
    public static function datosIncluyenCartaPorte(array $data): bool
    {
        $complemento = $data['complemento'] ?? [];
        if (!is_array($complemento)) {
            return false;
        }

        foreach (['CartaPorte31', 'CartaPorte30', 'CartaPorte'] as $clave) {
            if (!empty($complemento[$clave]) && (int)$complemento[$clave] > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detecta Carta Porte en el XML ya timbrado (array de Cake Xml::toArray).
     *
     * @param array<string, mixed> $xmlArray
     */
    public static function xmlTieneCartaPorte(array $xmlArray): bool
    {
        $comp = $xmlArray['Comprobante']['cfdi:Complemento']
            ?? $xmlArray['Comprobante']['Complemento']
            ?? null;
        if (!is_array($comp)) {
            return false;
        }
        foreach (array_keys($comp) as $key) {
            if (is_string($key) && stripos($key, 'CartaPorte') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extrae los campos del comprobante desde un XML CFDI ya timbrado.
     *
     * @return array<string, mixed>
     * @throws \RuntimeException
     */
    public static function datosDesdeXmlTimbrado(string $xmlContent): array
    {
        $xmlContent = trim($xmlContent);
        if ($xmlContent === '') {
            throw new \RuntimeException('El archivo XML está vacío.');
        }

        try {
            $xmlArray = \Cake\Utility\Xml::toArray(\Cake\Utility\Xml::build($xmlContent));
        } catch (\Throwable $e) {
            throw new \RuntimeException('No se pudo leer el XML: ' . $e->getMessage());
        }

        $comp = $xmlArray['Comprobante'] ?? null;
        if (!is_array($comp)) {
            throw new \RuntimeException('El archivo no contiene un comprobante CFDI válido.');
        }

        $tfd = $comp['cfdi:Complemento']['tfd:TimbreFiscalDigital']
            ?? $comp['Complemento']['TimbreFiscalDigital']
            ?? $comp['cfdi:Complemento']['TimbreFiscalDigital']
            ?? null;
        $uuid = is_array($tfd) ? (string)($tfd['@UUID'] ?? '') : '';
        if ($uuid === '') {
            throw new \RuntimeException('El XML no está timbrado (falta el Timbre Fiscal Digital).');
        }

        $emisor = $comp['cfdi:Emisor'] ?? $comp['Emisor'] ?? [];
        $receptor = $comp['cfdi:Receptor'] ?? $comp['Receptor'] ?? [];

        $attr = static function (array $node, string $key): string {
            return (string)($node['@' . $key] ?? $node[$key] ?? '');
        };

        $fechaEmision = $attr($comp, 'Fecha');
        if ($fechaEmision !== '') {
            $fechaEmision = str_replace('T', ' ', $fechaEmision);
        }

        $mes = $fechaEmision !== '' ? substr($fechaEmision, 5, 2) : '';
        $anio = $fechaEmision !== '' ? substr($fechaEmision, 0, 4) : '';

        return [
            'uuid' => $uuid,
            'rfcEmisor' => $attr($emisor, 'Rfc'),
            'nombreEmisor' => $attr($emisor, 'Nombre'),
            'rfcReceptor' => $attr($receptor, 'Rfc'),
            'nombreReceptor' => $attr($receptor, 'Nombre'),
            'serie' => $attr($comp, 'Serie'),
            'folio' => $attr($comp, 'Folio'),
            'fechaEmision' => $fechaEmision,
            'formaPago' => $attr($comp, 'FormaPago'),
            'subtotal' => $attr($comp, 'SubTotal') ?: '0',
            'descuento' => $attr($comp, 'Descuento') ?: '0',
            'total' => $attr($comp, 'Total') ?: '0',
            'tipoComprobante' => $attr($comp, 'TipoDeComprobante'),
            'llevaCartaPorte' => self::xmlTieneCartaPorte($xmlArray) ? 1 : 0,
            'anio' => $anio,
            'mes' => $mes,
            'xmlArray' => $xmlArray,
        ];
    }
}

