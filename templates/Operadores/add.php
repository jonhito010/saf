<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Operador $operador
 * @var string $empresaNombre
 */
echo $this->element('operador_form', [
    'operador' => $operador,
    'empresaNombre' => $empresaNombre ?? '',
    'empresas' => $empresas ?? [],
    'empresaFija' => $empresaFija ?? true,
    'formTitulo' => 'Registrar operador',
    'docsPorTipo' => [],
    'modo' => 'alta',
]);
