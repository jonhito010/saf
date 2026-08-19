<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Operador $operador
 * @var string $empresaNombre
 * @var array<string, \App\Model\Entity\DocumentoOperador> $docsPorTipo
 */
echo $this->element('operador_form', [
    'operador' => $operador,
    'empresaNombre' => $empresaNombre ?? '',
    'formTitulo' => 'Editar operador',
    'docsPorTipo' => $docsPorTipo ?? [],
    'modo' => 'edicion',
]);
