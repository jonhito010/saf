<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Empresa;
use App\Model\Table\ComprobantesTable;
use SoapFault;
use SoapClient;
use App\Utility\Ppdf;
use App\Utility\Cfdi;
use App\Utility\CfdiReutilizar;
use Cake\Utility\Xml;
use Cake\I18n\FrozenTime;
use Cake\I18n\FrozenDate;
use Cake\I18n\Time;
use Cake\Routing\Router;
use Cake\Log\Log;
/**
 * Comprobantes Controller
 *
 * @property \App\Model\Table\ComprobantesTable $Comprobantes
 * @method \App\Model\Entity\Comprobante[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ComprobantesController extends AppController
{
    /** @var array<string, mixed> */
    protected array $paginate = [
        'order' => [
            'fechaEmision' => 'DESC',
        ],
        'sortableFields' => ['fechaEmision'],
    ];

    /**
     * Before filter callback
     *
     * @param \Cake\Event\EventInterface $event
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);
    }

    /**
     * Búsqueda de clientes para autocompletar (JSON)
     * GET /comprobantes/search-clientes?q=texto
     */
    public function searchClientes()
    {
        $this->request->allowMethod(['get']);
        $this->autoRender = false;
        
        $q = (string)($this->request->getQuery('q') ?? '');

        $results = [];
        if ($q !== '') {
            $clientesTable = $this->fetchTable('Clientes');
            $clientesQ = $clientesTable->find()
                ->select(['id', 'nombre', 'rfc', 'regimenFiscal', 'usoCFDI', 'correo', 'domicilioFiscal', 'direccionCompleta'])
                ->where([
                    'Clientes.eliminado' => 0,
                    'OR' => [
                        'Clientes.nombre LIKE' => "%{$q}%",
                        'Clientes.rfc LIKE' => "%{$q}%",
                    ],
                ])
                ->orderByAsc('Clientes.nombre')
                ->limit(10);
            if ($clientesTable->getSchema()->hasColumn('empresa_id')) {
                $this->aplicarAlcanceEmpresa($clientesQ, 'Clientes.empresa_id');
            }
            $clientes = $clientesQ->all();

            foreach ($clientes as $c) {
                $label = $c->nombre ?? '';
                if (!empty($c->rfc)) {
                    $label .= " (RFC: {$c->rfc})";
                }
                
                $results[] = [
                    'id' => $c->id ?? '',
                    'label' => trim($label),
                    'nombre' => $c->nombre ?? '',
                    'rfc' => $c->rfc ?? '',
                    'regimenFiscal' => $c->regimenFiscal ?? '',
                    'usoCFDI' => $c->usoCFDI ?? '',
                    'correo' => $c->correo ?? '',
                    'domicilioFiscal' => $c->domicilioFiscal ?? '',
                    'direccionCompleta' => $c->direccionCompleta ?? '',
                ];
            }
        }

        $response = $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['results' => $results], JSON_UNESCAPED_UNICODE));

        return $response;
    }

    /**
     * Búsqueda de servicios para autocompletar (JSON)
     * GET /comprobantes/search-servicios?q=texto
     */
    public function searchServicios()
    {
        $this->request->allowMethod(['get']);
        $this->autoRender = false;
        
        $q = (string)($this->request->getQuery('q') ?? '');

        $results = [];
        if ($q !== '') {
            $serviciosTable = $this->fetchTable('Servicios');
            $serviciosQ = $serviciosTable->find()
                ->select(['id', 'descripcion', 'numeroIdentificacion', 'claveSAT', 'precio', 'unidadSAT', 'objetoImp'])
                ->where([
                    'Servicios.eliminado' => 0,
                    'OR' => [
                        'Servicios.descripcion LIKE' => "%{$q}%",
                        'Servicios.numeroIdentificacion LIKE' => "%{$q}%",
                        'Servicios.claveSAT LIKE' => "%{$q}%",
                    ],
                ])
                ->orderByAsc('Servicios.descripcion')
                ->limit(10);
            if ($serviciosTable->getSchema()->hasColumn('empresa_id')) {
                $this->aplicarAlcanceEmpresa($serviciosQ, 'Servicios.empresa_id');
            }
            $servicios = $serviciosQ->all();

            foreach ($servicios as $s) {
                $label = $s->descripcion ?? '';
                if (!empty($s->claveSAT)) {
                    $label .= " (Clave: {$s->claveSAT})";
                }
                if (!empty($s->precio)) {
                    $label .= " - $" . number_format((float)$s->precio, 2, '.', ',');
                }
                
                $results[] = [
                    'id' => $s->id ?? '',
                    'label' => trim($label),
                    'descripcion' => $s->descripcion ?? '',
                    'numeroIdentificacion' => $s->numeroIdentificacion ?? '',
                    'claveSAT' => $s->claveSAT ?? '',
                    'precio' => $s->precio ?? '',
                    'unidadSAT' => $s->unidadSAT ?? '',
                    'objetoImp' => $s->objetoImp ?? '',
                ];
            }
        }

        $response = $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['results' => $results], JSON_UNESCAPED_UNICODE));

        return $response;
    }

    /**
     * Búsqueda de vehículos para autocompletar Carta Porte (JSON)
     * GET /comprobantes/search-vehiculos?q=texto
     */
    public function searchVehiculos()
    {
        $this->request->allowMethod(['get']);
        $this->autoRender = false;

        $q = (string)($this->request->getQuery('q') ?? '');
        $results = [];

        if ($q !== '') {
            $vehiculosTable = $this->fetchTable('Vehiculos');
            $vehiculosQ = $vehiculosTable->find()
                ->select([
                    'id', 'marca', 'modelo', 'anio', 'placas',
                    'peso_bruto_vehicular', 'num_ejes', 'tipo_carga',
                ])
                ->where([
                    'Vehiculos.estatus' => 'activo',
                    'OR' => [
                        'Vehiculos.placas LIKE' => "%{$q}%",
                        'Vehiculos.marca LIKE' => "%{$q}%",
                        'Vehiculos.modelo LIKE' => "%{$q}%",
                        'Vehiculos.numero_serie LIKE' => "%{$q}%",
                    ],
                ])
                ->orderByAsc('Vehiculos.placas')
                ->limit(10);
            $this->aplicarAlcanceEmpresa($vehiculosQ, 'Vehiculos.empresa_id');
            $vehiculos = $vehiculosQ->all();

            foreach ($vehiculos as $v) {
                $label = trim((string)($v->placas ?? ''));
                $nombre = trim("{$v->marca} {$v->modelo} {$v->anio}");
                if ($nombre !== '') {
                    $label .= ($label !== '' ? ' — ' : '') . $nombre;
                }
                $results[] = [
                    'id' => $v->id ?? '',
                    'label' => $label !== '' ? $label : ('Vehículo #' . ($v->id ?? '')),
                    'placas' => $v->placas ?? '',
                    'anio' => $v->anio ?? '',
                    'peso_bruto_vehicular' => $v->peso_bruto_vehicular ?? '',
                    'marca' => $v->marca ?? '',
                    'modelo' => $v->modelo ?? '',
                ];
            }
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['results' => $results], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Búsqueda de operadores para autocompletar figura de transporte (JSON)
     * GET /comprobantes/search-operadores?q=texto
     */
    public function searchOperadores()
    {
        $this->request->allowMethod(['get']);
        $this->autoRender = false;

        $q = (string)($this->request->getQuery('q') ?? '');
        $results = [];

        if ($q !== '') {
            $operadoresTable = $this->fetchTable('Operadores');
            $operadoresQ = $operadoresTable->find()
                ->select([
                    'id', 'nombre', 'apellido_paterno', 'apellido_materno',
                    'rfc', 'num_licencia',
                ])
                ->where([
                    'OR' => [
                        'Operadores.nombre LIKE' => "%{$q}%",
                        'Operadores.apellido_paterno LIKE' => "%{$q}%",
                        'Operadores.rfc LIKE' => "%{$q}%",
                        'Operadores.num_licencia LIKE' => "%{$q}%",
                    ],
                ])
                ->orderByAsc('Operadores.nombre')
                ->limit(10);
            $this->aplicarAlcanceEmpresa($operadoresQ, 'Operadores.empresa_id');
            $operadores = $operadoresQ->all();

            foreach ($operadores as $o) {
                $nombre = trim(
                    (string)($o->nombre ?? '') . ' '
                    . (string)($o->apellido_paterno ?? '') . ' '
                    . (string)($o->apellido_materno ?? '')
                );
                $rfc = (string)($o->rfc ?? '');
                $label = $nombre;
                if ($rfc !== '') {
                    $label .= " (RFC: {$rfc})";
                }
                $results[] = [
                    'id' => $o->id ?? '',
                    'label' => trim($label),
                    'nombre' => $nombre,
                    'rfc' => $rfc,
                    'num_licencia' => $o->num_licencia ?? '',
                ];
            }
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['results' => $results], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $filtroDesde = $this->parseFechaFiltroQuery('desde');
        $filtroHasta = $this->parseFechaFiltroQuery('hasta');

        if ($filtroDesde !== null && $filtroHasta !== null && $filtroDesde > $filtroHasta) {
            [$filtroDesde, $filtroHasta] = [$filtroHasta, $filtroDesde];
        }

        $fechasDisponibles = $this->obtenerRangoFechasDisponibles();
        $query = $this->buildComprobantesIndexQuery($filtroDesde, $filtroHasta);

        $comprobantes = $this->paginate($query, [
            'limit' => 20,
            'maxLimit' => 100,
            'order' => ['fechaEmision' => 'DESC'],
            'sortableFields' => ['fechaEmision'],
        ]);

        $stats = $this->calcularStatsComprobantes($filtroDesde, $filtroHasta);
        $totalFiltrado = $stats['total'];
        $totalEnSistema = $this->calcularStatsComprobantes(null, null)['total'];
        $filtroActivo = $filtroDesde !== null || $filtroHasta !== null;

        $this->set(compact(
            'comprobantes',
            'stats',
            'filtroDesde',
            'filtroHasta',
            'filtroActivo',
            'fechasDisponibles',
            'totalEnSistema',
            'totalFiltrado',
        ));
    }

    /**
     * @return string|null Fecha Y-m-d válida o null
     */
    private function parseFechaFiltroQuery(string $param): ?string
    {
        $raw = trim((string)($this->request->getQuery($param) ?? ''));
        if ($raw === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return null;
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $raw);
        if (!$dt || $dt->format('Y-m-d') !== $raw) {
            return null;
        }

        return $raw;
    }

    /**
     * @return array{min: ?string, max: ?string, minFmt: ?string, maxFmt: ?string}
     */
    private function obtenerRangoFechasDisponibles(): array
    {
        $query = $this->Comprobantes->find()
            ->select([
                'min_fecha' => $this->Comprobantes->find()->func()->min('fechaEmision'),
                'max_fecha' => $this->Comprobantes->find()->func()->max('fechaEmision'),
            ])
            ->enableHydration(false);
        $this->aplicarFiltroComprobantesEmpresa($query);
        $row = $query->first();

        $min = $row['min_fecha'] ?? null;
        $max = $row['max_fecha'] ?? null;

        return [
            'min' => $this->formatearFechaIso($min),
            'max' => $this->formatearFechaIso($max),
            'minFmt' => $this->formatearFechaHumana($min),
            'maxFmt' => $this->formatearFechaHumana($max),
        ];
    }

    private function formatearFechaIso(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        $str = substr((string)$valor, 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
            return null;
        }

        return $str;
    }

    private function formatearFechaHumana(mixed $valor): ?string
    {
        $iso = $this->formatearFechaIso($valor);
        if ($iso === null) {
            return null;
        }
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $iso);

        return $dt ? $dt->format('d/m/Y') : null;
    }

    private function buildComprobantesIndexQuery(?string $desde, ?string $hasta)
    {
        $query = $this->Comprobantes->find();
        $this->aplicarFiltroComprobantesEmpresa($query);

        if ($desde !== null) {
            $query->where(['Comprobantes.fechaEmision >=' => $desde . ' 00:00:00']);
        }
        if ($hasta !== null) {
            $query->where(['Comprobantes.fechaEmision <=' => $hasta . ' 23:59:59']);
        }

        return $query;
    }

    /** @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query */
    private function aplicarFiltroComprobantesEmpresa($query): void
    {
        $schema = $this->Comprobantes->getSchema();
        if ($schema->hasColumn('empresa_id')) {
            $this->aplicarAlcanceEmpresa($query, 'Comprobantes.empresa_id');

            return;
        }
        if ($schema->hasColumn('rfcEmisor')) {
            $rfcs = $this->rfcsEmpresasAlcance();
            if ($rfcs === []) {
                $query->where(['0 = 1']);
            } else {
                $query->where(['Comprobantes.rfcEmisor IN' => $rfcs]);
            }

            return;
        }
        $query->where(['0 = 1']);
    }

    /**
     * @return array{total:int,timbradas:int,canceladas:int,conCartaPorte:int}
     */
    private function calcularStatsComprobantes(?string $desde, ?string $hasta): array
    {
        $schema = $this->Comprobantes->getSchema();
        $conCartaPorte = 0;
        if ($schema->hasColumn('llevaCartaPorte')) {
            $conCartaPorte = $this->buildComprobantesIndexQuery($desde, $hasta)
                ->where(['llevaCartaPorte' => 1])
                ->count();
        }

        return [
            'total' => $this->buildComprobantesIndexQuery($desde, $hasta)->count(),
            'timbradas' => $this->buildComprobantesIndexQuery($desde, $hasta)->where(['estatus' => 1])->count(),
            'canceladas' => $this->buildComprobantesIndexQuery($desde, $hasta)->where(['estatus' => 2])->count(),
            'conCartaPorte' => $conCartaPorte,
        ];
    }

    /**
     * View method
     *
     * @param string|null $id Comprobante id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $comprobante = $this->Comprobantes->get($id);
        if (!$this->comprobanteEnAlcance($comprobante)) {
            $this->Flash->error('No tienes acceso a este comprobante.');
            return $this->redirect(['action' => 'index']);
        }

        $this->set(compact('comprobante'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $comprobante = $this->Comprobantes->newEmptyEntity();
        if ($this->request->is('post')) {
            $comprobante = $this->Comprobantes->patchEntity($comprobante, $this->request->getData());
            if ($this->Comprobantes->save($comprobante)) {
                $this->Flash->success('Comprobante guardado correctamente.');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('No se pudo guardar el comprobante. Revisa los datos.');
        }
        $this->set(compact('comprobante'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Comprobante id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $comprobante = $this->Comprobantes->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $comprobante = $this->Comprobantes->patchEntity($comprobante, $this->request->getData());
            if ($this->Comprobantes->save($comprobante)) {
                $this->Flash->success('Comprobante guardado correctamente.');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('No se pudo guardar el comprobante. Revisa los datos.');
        }
        $this->set(compact('comprobante'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Comprobante id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $comprobante = $this->Comprobantes->get($id);
        if ($this->Comprobantes->delete($comprobante)) {
            $this->Flash->success('Comprobante eliminado correctamente.');
        } else {
            $this->Flash->error('No se pudo eliminar el comprobante.');
        }

        return $this->redirect(['action' => 'index']);
    }


    /**
     * Sirve un PDF temporal generado por vistaPrevia y lo elimina tras enviarlo.
     * Ruta: GET /comprobantes/tmp-pdf/{token}
     */
    public function tmpPdf(string $token): \Cake\Http\Response
    {
        $this->autoRender = false;
        if (!preg_match('/^[a-zA-Z0-9_.]+$/', $token)) {
            return $this->response->withStatus(404)->withStringBody('Not found');
        }
        $dir = WWW_ROOT . 'comprobantes' . DS . 'pdf' . DS . 'tmp';
        $this->limpiarPdfsTemporales($dir);
        $path = $dir . DS . $token . '.pdf';
        if (!is_file($path)) {
            return $this->response->withStatus(404)
                ->withType('text/html')
                ->withStringBody('<html><body style="font-family:sans-serif;padding:2rem"><h2>Vista previa no encontrada</h2><p>El archivo temporal expiró. Vuelve a la factura y pulsa «Vista previa» de nuevo.</p></body></html>');
        }
        $data = (string)file_get_contents($path);

        return $this->response
            ->withType('application/pdf')
            ->withHeader('Content-Disposition', 'inline; filename="vista-previa.pdf"')
            ->withStringBody($data);
    }

    /**
     * Vista previa del PDF sin timbrar: genera XML sellado (sin SOAP) y devuelve el PDF inline
     * para abrirlo en una pestaña nueva.
     *
     * @return \Cake\Http\Response|null
     */
  public function vistaPrevia()
{
    $this->request->allowMethod(['post']);

    $sendError = function (string $msg): \Cake\Http\Response {
        if (ob_get_length()) {
            ob_end_clean();
        }
        return $this->response
            ->withType('text/html')
            ->withStringBody('<html><body style="font-family:sans-serif;padding:2rem"><h2 style="color:#c0392b">Error — Vista previa</h2><p>' . h($msg) . '</p></body></html>');
    };

    $raw = $this->request->getData();
    if (empty($raw['data']) || !is_array($raw['data'])) {
        return $sendError('No se recibieron los datos del CFDI.');
    }

    $empresa = $this->obtenerEmpresaActiva();
    if (!$empresa) {
        return $sendError('Selecciona o registra una empresa activa para facturar.');
    }

    $emisor = $this->buildEmisorFacturaDto($empresa);
    $data = $raw['data'];

    $data['Certificado']['pemKEY'] = $emisor->pemKEY;
    $data['Certificado']['numero'] = $emisor->numero;
    $data['Certificado']['pemCER'] = $emisor->pemCER;
    $data['Emisor']['Nombre'] = str_replace('&quot;', '"', $data['Emisor']['Nombre'] ?? '');

    if (($data['Cfdi']['TipoDeComprobante'] ?? '') === 'T') {
        $data['Cfdi']['SubTotal'] = '0';
        $data['Cfdi']['Total']    = '0';
        $data['Cfdi']['MetodoPago'] = '';
        $data['Cfdi']['FormaPago']  = '';
        if (($data['Cfdi']['Moneda'] ?? '') === 'XXX') {
            $data['Cfdi']['TipoCambio'] = '';
        }
    }

    $pdfOpciones = $this->pdfOpcionesFromRequest($raw);

    try {
        $cfdi = new Cfdi();
        $cfdi->setData($data);
        $xmlString = $cfdi->getXMLSellado($data);

        \Cake\Log\Log::write('debug', "=== XML VISTA PREVIA ===\n" . $xmlString . "\n=== FIN XML ===");

        } catch (\Throwable $e) {
        return $sendError('Error al construir el XML: ' . $e->getMessage());
    }

    $tmpXmlDir = WWW_ROOT . 'comprobantes' . DS . 'xml' . DS . 'tmp';
    $tmpPdfDir = WWW_ROOT . 'comprobantes' . DS . 'pdf' . DS . 'tmp';

    if (!is_dir($tmpXmlDir)) @mkdir($tmpXmlDir, 0755, true);
    if (!is_dir($tmpPdfDir)) @mkdir($tmpPdfDir, 0755, true);

    $uid    = uniqid('vp_', true);
    $absXml = $tmpXmlDir . DS . $uid . '.xml';
    $relXml = '/comprobantes/xml/tmp/' . $uid . '.xml';
    $relPdf = '/comprobantes/pdf/tmp/' . $uid . '.pdf';
    $absPdf = $tmpPdfDir . DS . $uid . '.pdf';

    file_put_contents($absXml, $xmlString);

    if (!class_exists(\Dompdf\Dompdf::class)) {
        @unlink($absXml);
        return $sendError('Dompdf no está instalado.');
    }

    $_pdf = new Ppdf();
    $ok   = $_pdf->generate($relXml, $relPdf, 1, 'V', $pdfOpciones);

    @unlink($absXml);

    if ($ok !== 1 || !is_file($absPdf)) {
        @unlink($absPdf);
        return $sendError('No se pudo generar el PDF.');
    }

    $this->limpiarPdfsTemporales($tmpPdfDir);

    $pdfSrc = Router::url([
        'controller' => 'Comprobantes',
        'action' => 'tmpPdf',
        $uid,
    ], false);

    return $this->renderVisorPdf($pdfSrc, 'Vista previa CFDI', true);
}

    public function generar()
    {   	date_default_timezone_set('America/Mexico_City');
        $empresasTable = $this->fetchTable('Empresas');
        $empresa = $this->obtenerEmpresaActiva();
        if (!$empresa) {
            $this->Flash->error(__('Selecciona o registra una empresa activa para poder facturar.'));
            return $this->redirect(['controller' => 'Empresas', 'action' => 'index']);
        }

        $emisor = $this->buildEmisorFacturaDto($empresa);
        $preparacionCp = $this->evaluarPreparacionCartaPorte($empresa);
        $permisoSictEmpresa = trim((string)($empresa->numero_permiso_sict ?? ''));

        $comprobante = $this->Comprobantes->newEmptyEntity();
        if ($this->request->is('post')) {
            $empresa = $empresasTable->get($empresa->id);
            $emisor = $this->buildEmisorFacturaDto($empresa);
            $ajax = strcasecmp($this->request->getHeaderLine('X-Requested-With'), 'XMLHttpRequest') === 0;

            $jsonError = function (string $message, int $status = 200) use ($ajax) {
                if (!$ajax) {
                    $this->Flash->error($message);

                    return $this->redirect(['action' => 'generar']);
                }

                return $this->response
                    ->withStatus($status >= 400 ? $status : 200)
                    ->withType('application/json')
                    ->withStringBody(json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE));
            };

            if (!extension_loaded('soap')) {
                $msg = __(
                    'La extensión PHP SOAP no está cargada. En php.ini del PHP que usa el servidor activa extension=soap y reinicia Apache/Nginx (Laragon: clic derecho en Laragon → PHP → php.ini).'
                );
                $r = $jsonError($msg);
                if ($r !== null) {
                    return $r;
                }

                return $this->redirect(['action' => 'generar']);
            }

            $raw = $this->request->getData();
            if (empty($raw['data']) || !is_array($raw['data'])) {
                $r = $jsonError(__('No se recibieron los datos del CFDI. Intente de nuevo.'));
                if ($r !== null) {
                    return $r;
                }

                return $this->redirect(['action' => 'generar']);
            }

            $cfdi = new Cfdi();
            try {
                $data = $raw['data'];
                $data['Certificado']['pemKEY'] = $emisor->pemKEY;
                $data['Certificado']['numero'] = $emisor->numero;
                $data['Certificado']['pemCER'] = $emisor->pemCER;
                $tenant = str_replace("&", 'Y', $data['Emisor']['Rfc']);
                $tenant = str_replace('Ñ', '', $data['Emisor']['Rfc']);
                $data['Emisor']['Nombre'] = str_replace('&quot;', '"', $data['Emisor']['Nombre']);

                if ($data['Cfdi']['TipoDeComprobante'] == 'T') {
                    $data['Cfdi']['SubTotal'] = '0';
                    $data['Cfdi']['Total'] = '0';
                    $data['Cfdi']['MetodoPago'] = '';
                    $data['Cfdi']['FormaPago'] = '';
                    if ($data['Cfdi']['Moneda'] == 'XXX') {
                        $data['Cfdi']['TipoCambio'] = '';
                    }
                }

                /* Validar ubicaciones solo si el complemento Carta Porte está activo */
                $incluyeCartaPorte = ComprobantesTable::datosIncluyenCartaPorte($data);
                $ubicacionesJson = $data['carta30']['Ubicaciones'] ?? '';
                $ubicacionesArr  = json_decode($ubicacionesJson, true) ?? [];
                Log::write('debug', sprintf(
                    '[generar] cartaPorte=%s | carta30.Ubicaciones = %s | count=%d',
                    $incluyeCartaPorte ? 'sí' : 'no',
                    $ubicacionesJson ?: '(vacío)',
                    count($ubicacionesArr)
                ));
                if ($incluyeCartaPorte && empty($ubicacionesArr)) {
                    throw new \RuntimeException(
                        'No se recibieron ubicaciones en el Carta Porte. Agrega al menos un Origen y un Destino antes de timbrar.'
                    );
                }

                $tipo = '01';
                $cfdi->setData($data);
                $xml = $cfdi->getXMLSellado($data);
                $pdfOpciones = $this->pdfOpcionesFromRequest($raw);
                
                
                if ($data['Emisor']['Rfc'] == 'EKU9003173C9') { // pruebas
                    $servicio = 'https://dev.facturaloplus.com/ws/servicio.do?wsdl';
                    $apikey = '3a9cb10b0f104cab9af319dca4dc04f2';
                } else { // produccion
                    $servicio = 'https://app.facturaloplus.com/ws/servicio.do?wsdl';
                    $apikey = '3a9cb10b0f104cab9af319dca4dc04f2';
                }
                $client = new SoapClient($servicio);
                $result = $client->timbrar($apikey, $xml);
                $response = (array)$result;
            } catch (\Throwable $e) {
                $msg = __('Error al timbrar: {0}', $e->getMessage());
                $r = $jsonError($msg);
                if ($r !== null) {
                    return $r;
                }
                $this->Flash->error($msg);

                return $this->redirect(['action' => 'generar']);
            }

                if ($response['code'] == 200) {
					$xml1 = $response['data'];						
					$xmlArray = Xml::toArray(Xml::build($xml1));
                  
                    $uuid = $xmlArray['Comprobante']['cfdi:Complemento']['tfd:TimbreFiscalDigital']['@UUID'];
                    $empresaActual = $empresasTable->get($empresa->id);
                    $folioUsado = (int)($xmlArray['Comprobante']['@Folio'] ?? 0);
                    $empresaActual->folio_cfdi = $folioUsado + 1;
                    $empresaActual->folios_disponibles = max(0, (int)($empresaActual->folios_disponibles ?? 0) - 1);
                    $empresasTable->save($empresaActual);
                    $now = FrozenTime::now();
                   
                    $ym = date('Y/m/', time());
                    $basePathXML = WWW_ROOT . 'comprobantes/xml/' . $ym . 'emitidos';
					$basePathPDF = WWW_ROOT . 'comprobantes/pdf/' . $ym . 'emitidos';

                    if (!file_exists($basePathXML)) {
						mkdir($basePathXML, 0755, true);
					}
					if (!file_exists($basePathPDF)) {
						mkdir($basePathPDF, 0755, true);
					}
					$pathXML = "$basePathXML/$uuid.xml";

                    file_put_contents($pathXML, $xml1);

					// Plantilla HTML: templates/Comprobantes/plantilla.php + Dompdf (mismo flujo que pdf/verPdf).
					$relXml = '/comprobantes/xml/' . $ym . 'emitidos/' . $uuid . '.xml';
					$relPdf = '/comprobantes/pdf/' . $ym . 'emitidos/' . $uuid . '.pdf';
					$_pdfGen = new Ppdf();
					if ($_pdfGen->generate($relXml, $relPdf, 1, null, $pdfOpciones) !== 1) {
						Log::warning('Timbrado: no se pudo generar el PDF desde plantilla. UUID: ' . $uuid);
					} elseif (!$_pdfGen->ultimoPdfIncluyoQr()) {
						Log::warning('Timbrado: PDF generado sin QR, reintentando. UUID: ' . $uuid);
						$_pdfGen->generate($relXml, $relPdf, 1, null, $pdfOpciones);
					}
					
					$fechaEmision   = str_replace('T', ' ', $xmlArray['Comprobante']['@Fecha']);

                    $comprobante = $this->Comprobantes->newEmptyEntity();
					$comprobante->rfcEmisor = $data['Emisor']['Rfc'];
					$comprobante->nombreEmisor = $data['Emisor']['Nombre'];
					$comprobante->rfcEmisor = $data['Emisor']['Rfc'];
					$comprobante->rfcReceptor = $data['Receptor']['Rfc'];
					$comprobante->nombreReceptor = $data['Receptor']['Nombre'];
					$comprobante->emailReceptor = $data['Receptor']['Correo'] ;
					$comprobante->serie = $data['Cfdi']['Serie'];
					$comprobante->folio = $data['Cfdi']['Folio'];
					$comprobante->fechaEmision = $fechaEmision;
					$comprobante->formaPago = $data['Cfdi']['FormaPago'];
					$comprobante->llevaCartaPorte = (
						ComprobantesTable::xmlTieneCartaPorte($xmlArray)
						|| ComprobantesTable::datosIncluyenCartaPorte($data)
					) ? 1 : 0;
					$comprobante->total = $xmlArray['Comprobante']['@Total'];
					$comprobante->subtotal = $xmlArray['Comprobante']['@SubTotal'];
					$comprobante->descuento = empty($data['Cfdi']['Descuento']) ? "0" : $data['Cfdi']['Descuento'];
					$comprobante->tipoComprobante = $data['Cfdi']['TipoDeComprobante'];
					$comprobante->uuid = $uuid;				
					$comprobante->fechaRegistro = Time::now();
					$comprobante->pdf = '/comprobantes/pdf/' . $ym . 'emitidos/' . $uuid . '.pdf';
					$comprobante->xml = '/comprobantes/xml/' . $ym . 'emitidos/' . $uuid . '.xml';
					$comprobante->acuseCancelacion = '';
					//$cfdi['json'] = $pathJSON ;					
					$mes  = substr($data['Cfdi']['Fecha'], 5, 2);
					$anio = substr($data['Cfdi']['Fecha'], 0, 4);					
					$comprobante->anio = $anio;
					$comprobante->mes = $mes;
                    
					/*$comprobante->xml = $xml1;
					$comprobante->pdf = $result->PDF;*/
					$comprobante->estatus = 1;
					//$comprobante->user_id = $this->Auth->user('id');
					$saved = $this->Comprobantes->save($comprobante);
					if (!$saved) {
						$errMsg = __('No se pudo guardar el comprobante en la base de datos.');
						if ($ajax) {
							return $this->response
								->withType('application/json')
								->withStringBody(json_encode([
									'success' => false,
									'message' => $errMsg,
								], JSON_UNESCAPED_UNICODE));
						}
						$this->Flash->error($errMsg);

						return $this->redirect(['controller' => 'Comprobantes', 'action' => 'index']);
					}
					$comprobante = $saved;
                    $okMsg = __('Comprobante timbrado correctamente {0}', $comprobante->uuid);
                    if ($ajax) {
                        return $this->response
                            ->withType('application/json')
                            ->withStringBody(json_encode([
                                'success' => true,
                                'message' => $okMsg,
                                'uuid' => $uuid,
								'comprobante_id' => $comprobante->id,
								'pdf_view_url' => Router::url([
									'plugin' => false,
									'prefix' => false,
									'controller' => 'Comprobantes',
									'action' => 'vistaPdf',
									$comprobante->id,
								], true),
								'pdf_download_url' => Router::url([
									'plugin' => false,
									'prefix' => false,
									'controller' => 'Comprobantes',
									'action' => 'pdf',
									$comprobante->id,
								], true),
								'xml_url' => Router::url([
									'plugin' => false,
									'prefix' => false,
									'controller' => 'Comprobantes',
									'action' => 'xml',
									$comprobante->id,
								], true),
                                'redirect' => Router::url([
									'plugin' => false,
									'prefix' => false,
									'controller' => 'Comprobantes',
									'action' => 'index',
								], true),
                            ], JSON_UNESCAPED_UNICODE));
                    }
                    $this->Flash->success($okMsg);

                    return $this->redirect(['controller' => 'Comprobantes', 'action' => 'index']);

                }else{
                    $errMsg = __('Error al timbrar el comprobante: {0}', (string)($response['message'] ?? ''));
                    if ($ajax) {
                        return $this->response
                            ->withType('application/json')
                            ->withStringBody(json_encode([
                                'success' => false,
                                'message' => $errMsg,
                            ], JSON_UNESCAPED_UNICODE));
                    }
                    $this->Flash->error($errMsg);

                    return $this->redirect(['controller' => 'Comprobantes', 'action' => 'index']);
                }
               
        }
        $reutilizarDatos = $this->cargarDatosReutilizarFactura();
        $this->set(compact('emisor', 'preparacionCp', 'permisoSictEmpresa', 'reutilizarDatos'));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function cargarDatosReutilizarFactura(): ?array
    {
        $desdeId = (int)($this->request->getQuery('desde') ?? 0);
        if ($desdeId <= 0) {
            return null;
        }

        try {
            $comprobante = $this->Comprobantes->get($desdeId, contain: []);
        } catch (\Throwable) {
            $this->Flash->warning(__('No se encontró la factura seleccionada.'));

            return null;
        }

        $pathXML = trim((string)($comprobante->xml ?? ''));
        if ($pathXML === '') {
            $this->Flash->warning(__('La factura no tiene XML asociado para reutilizar.'));

            return null;
        }

        $absXml = WWW_ROOT . ltrim(str_replace('\\', '/', $pathXML), '/');
        if (!is_file($absXml)) {
            $this->Flash->warning(__('No se encontró el archivo XML de la factura.'));

            return null;
        }

        try {
            $datos = CfdiReutilizar::desdeXmlTimbrado((string)file_get_contents($absXml));
        } catch (\Throwable $e) {
            $this->Flash->warning(__('No se pudo leer la factura para reutilizar: {0}', $e->getMessage()));

            return null;
        }

        $rfcRec = trim((string)($datos['receptor']['Rfc'] ?? ''));
        if ($rfcRec !== '') {
            try {
                $cliente = $this->fetchTable('Clientes')->find()
                    ->select(['correo', 'direccionCompleta'])
                    ->where(['Clientes.rfc' => $rfcRec, 'Clientes.eliminado' => 0])
                    ->first();
                if ($cliente) {
                    $datos['receptor']['Correo'] = trim((string)($cliente->correo ?? ''));
                    $datos['receptor']['direccionCompleta'] = trim((string)($cliente->direccionCompleta ?? ''));
                }
            } catch (\Throwable) {
                // Catálogo de clientes opcional.
            }
        }

        $serie = trim((string)($comprobante->serie ?? ''));
        $folio = trim((string)($comprobante->folio ?? ''));
        $datos['meta'] = [
            'id' => (int)$comprobante->id,
            'uuid' => (string)($comprobante->uuid ?? ''),
            'serieFolio' => $serie !== '' || $folio !== '' ? trim($serie . ($folio !== '' ? '-' . $folio : '')) : '',
        ];

        $this->Flash->success(__('Datos cargados de la factura {0}. Revise folio, fecha e IdCCP antes de timbrar.', $datos['meta']['serieFolio'] ?: $datos['meta']['uuid']));

        return $datos;
    }

    /**
     * Requisitos mínimos para emitir Carta Porte sin sorpresas en pantalla.
     *
     * @return array<string, mixed>
     */
    private function evaluarPreparacionCartaPorte(?Empresa $empresa): array
    {
        $items = [];
        $listo = true;

        $add = static function (string $key, string $label, bool $ok, ?string $url = null) use (&$items, &$listo): void {
            if (!$ok) {
                $listo = false;
            }
            $items[] = compact('key', 'label', 'ok', 'url');
        };

        $add('empresa', 'Empresa permisionaria registrada', $empresa !== null, '/empresa');

        $pemOk = $empresa !== null
            && trim((string)($empresa->pem_key ?? '')) !== ''
            && trim((string)($empresa->pem_cer ?? '')) !== '';
        $add('csd', 'Certificados CSD (.cer / .key)', $pemOk, '/empresa');

        $regimenOk = $empresa !== null && trim((string)($empresa->regimen_fiscal ?? '')) !== '';
        $add('regimen', 'Régimen fiscal del emisor', $regimenOk, '/empresa');

        $cpOk = $empresa !== null && (
            trim((string)($empresa->lugar_expedicion ?? '')) !== ''
            || trim((string)($empresa->cp ?? '')) !== ''
        );
        $add('cp_exp', 'C.P. de expedición', $cpOk, '/empresa');

        $add('soap', 'Extensión PHP SOAP', extension_loaded('soap'));

        $count = function (string $table): int {
            try {
                return (int)$this->fetchTable($table)->find()->count();
            } catch (\Throwable) {
                return 0;
            }
        };

        $nClientes = $count('Clientes');
        $add('clientes', 'Al menos un cliente (receptor)', $nClientes > 0, '/facturacion/clientes');

        $nUbic = $count('Ubicaciones');
        $add('ubicaciones', 'Ubicaciones guardadas (origen/destino)', $nUbic > 0, '/ubicaciones');

        $nMer = $count('CatalogoMercancias');
        $add('mercancias', 'Mercancías en catálogo', $nMer > 0, '/facturacion/mercancias');

        $nVeh = $count('Vehiculos');
        $add('vehiculos', 'Vehículos activos', $nVeh > 0, '/vehiculos');

        $nOp = $count('Operadores');
        $add('operadores', 'Operadores registrados', $nOp > 0, '/operadores');

        return [
            'listo' => $listo,
            'items' => $items,
            'conteos' => [
                'clientes' => $nClientes,
                'ubicaciones' => $nUbic,
                'mercancias' => $nMer,
                'vehiculos' => $nVeh,
                'operadores' => $nOp,
            ],
        ];
    }

    /**
     * Acción para descargar o visualizar el PDF de un comprobante
     * 
     * @param string|null $id ID del comprobante
     * @return \Cake\Http\Response|null
     */
    public function descargarPDF($id = null)
    {
        $comprobante = $this->Comprobantes->get($id, contain: []);
        if (!$this->comprobanteEnAlcance($comprobante)) {
            $this->Flash->error(__('No tienes acceso a este comprobante.'));
            return $this->redirect(['action' => 'index']);
        }

        $absPdf = $this->asegurarPdfDesdeXml($comprobante, $this->pdfOpcionesFromQuery());
        if ($absPdf === null || !is_file($absPdf)) {
            $this->Flash->error(__('El PDF no está disponible para este comprobante.'));
            return $this->redirect(['action' => 'index']);
        }

        return $this->response->withFile($absPdf, [
            'download' => true,
            'name' => ($comprobante->uuid ?? 'comprobante') . '.pdf',
        ]);
    }

    /**
     * Registra un CFDI ya timbrado subiendo su XML (sin volver a timbrar).
     */
    public function importarXml()
    {
        $empresa = $this->obtenerEmpresaActiva();
        if (!$empresa) {
            $this->Flash->error(__('Selecciona o registra una empresa activa.'));
            return $this->redirect(['controller' => 'Empresas', 'action' => 'index']);
        }

        if ($this->request->is('post')) {
            $upload = $this->request->getUploadedFile('archivo_xml');
            if ($upload === null || $upload->getError() !== UPLOAD_ERR_OK) {
                $this->Flash->error(__('Selecciona un archivo XML válido.'));
                return $this->redirect(['action' => 'importarXml']);
            }

            $nombre = (string)$upload->getClientFilename();
            if (!preg_match('/\.xml$/i', $nombre)) {
                $this->Flash->error(__('El archivo debe tener extensión .xml'));
                return $this->redirect(['action' => 'importarXml']);
            }

            $xmlContent = (string)$upload->getStream()->getContents();
            try {
                $parsed = ComprobantesTable::datosDesdeXmlTimbrado($xmlContent);
            } catch (\Throwable $e) {
                $this->Flash->error($e->getMessage());
                return $this->redirect(['action' => 'importarXml']);
            }

            $uuid = (string)$parsed['uuid'];
            $duplicado = $this->Comprobantes->find()
                ->where(['uuid' => $uuid])
                ->first();
            if ($duplicado !== null) {
                $this->Flash->warning(__('Este UUID ya está registrado. Puedes ver el comprobante en el listado.'));
                return $this->redirect(['action' => 'vistaPdf', $duplicado->id]);
            }

            $rfcEmpresa = trim((string)($empresa->rfc ?? ''));
            $rfcXml = trim((string)($parsed['rfcEmisor'] ?? ''));
            if ($rfcEmpresa !== '' && $rfcXml !== '' && strtoupper($rfcEmpresa) !== strtoupper($rfcXml)) {
                $this->Flash->error(__(
                    'El RFC del emisor en el XML ({0}) no coincide con la empresa registrada ({1}).',
                    $rfcXml,
                    $rfcEmpresa
                ));
                return $this->redirect(['action' => 'importarXml']);
            }

            $fechaEmision = (string)($parsed['fechaEmision'] ?? '');
            $ts = $fechaEmision !== '' ? strtotime($fechaEmision) : time();
            if ($ts === false) {
                $ts = time();
            }
            $ym = date('Y/m/', $ts);

            $basePathXML = WWW_ROOT . 'comprobantes/xml/' . $ym . 'emitidos';
            $basePathPDF = WWW_ROOT . 'comprobantes/pdf/' . $ym . 'emitidos';
            if (!is_dir($basePathXML)) {
                mkdir($basePathXML, 0755, true);
            }
            if (!is_dir($basePathPDF)) {
                mkdir($basePathPDF, 0755, true);
            }

            $pathXML = $basePathXML . DS . $uuid . '.xml';
            $relXml = '/comprobantes/xml/' . $ym . 'emitidos/' . $uuid . '.xml';
            $relPdf = '/comprobantes/pdf/' . $ym . 'emitidos/' . $uuid . '.pdf';

            file_put_contents($pathXML, $xmlContent);

            $pdfOpciones = $this->pdfOpcionesFromRequest($this->request->getData());
            $_pdfGen = new Ppdf();
            if ($_pdfGen->generate($relXml, $relPdf, 1, null, $pdfOpciones) !== 1) {
                Log::warning('importarXml: no se pudo generar el PDF. UUID: ' . $uuid);
            }

            $comprobante = $this->Comprobantes->newEmptyEntity();
            $comprobante->rfcEmisor = $parsed['rfcEmisor'];
            $comprobante->nombreEmisor = $parsed['nombreEmisor'];
            $comprobante->rfcReceptor = $parsed['rfcReceptor'];
            $comprobante->nombreReceptor = $parsed['nombreReceptor'];
            $comprobante->serie = $parsed['serie'];
            $comprobante->folio = $parsed['folio'];
            $comprobante->fechaEmision = $fechaEmision !== '' ? $fechaEmision : null;
            $comprobante->formaPago = $parsed['formaPago'];
            $comprobante->llevaCartaPorte = (int)$parsed['llevaCartaPorte'];
            $comprobante->total = $parsed['total'];
            $comprobante->subtotal = $parsed['subtotal'];
            $comprobante->descuento = $parsed['descuento'];
            $comprobante->uuid = $uuid;
            $comprobante->pdf = $relPdf;
            $comprobante->xml = $relXml;
					$comprobante->estatus = 1;

            $saved = $this->Comprobantes->save($comprobante);
            if (!$saved) {
                $this->Flash->error(__('No se pudo guardar el comprobante importado.'));
                return $this->redirect(['action' => 'importarXml']);
            }

            $this->Flash->success(__('XML timbrado importado correctamente. UUID: {0}', $uuid));
            return $this->redirect(['action' => 'vistaPdf', $saved->id]);
        }
    }

    /**
     * Descarga el XML del comprobante como adjunto.
     *
     * @param string|null $id ID del comprobante
     * @return \Cake\Http\Response|null
     */
    public function xml($id = null)
    {
        $comprobante = $this->Comprobantes->get($id, contain: []);
        $rel = ltrim((string)($comprobante->xml ?? ''), '/');
        if ($rel === '' || !is_file(WWW_ROOT . $rel)) {
            $this->Flash->error(__('El archivo XML no está disponible.'));
            return $this->redirect(['action' => 'index']);
        }

        return $this->response->withFile(WWW_ROOT . $rel, [
            'download' => true,
            'name' => ($comprobante->uuid ?? 'cfdi') . '.xml',
        ]);
    }

    /**
     * Página HTML con el XML embebido (vista previa).
     *
     * @param string|null $id ID del comprobante
     * @return void
     */
    public function vistaXml($id = null): void
    {
        $comprobante = $this->Comprobantes->get($id, contain: []);
        $this->set(compact('comprobante'));
    }

    /**
     * Sirve el XML para mostrarlo en el navegador (inline), p. ej. en iframe.
     *
     * @param string|null $id ID del comprobante
     * @return \Cake\Http\Response|null
     */
    public function verXml($id = null)
    {
        $comprobante = $this->Comprobantes->get($id, contain: []);
        $rel = ltrim((string)($comprobante->xml ?? ''), '/');
        if ($rel === '' || !is_file(WWW_ROOT . $rel)) {
            $this->Flash->error(__('El archivo XML no está disponible.'));
            return $this->redirect(['action' => 'index']);
        }
        $xml = file_get_contents(WWW_ROOT . $rel);
        if ($xml === false) {
            $this->Flash->error(__('No se pudo leer el XML.'));
            return $this->redirect(['action' => 'index']);
        }
        $name = ($comprobante->uuid ?? 'cfdi') . '.xml';

        return $this->response
            ->withType('application/xml')
            ->withCharset('UTF-8')
            ->withHeader('Content-Disposition', 'inline; filename="' . $name . '"')
            ->withStringBody($xml);
    }


    public function pdf($id = null)
    {
        $comprobante = $this->Comprobantes->get($id, contain: []);
        $pathPDF = (string)($comprobante->pdf ?? '');
        $absPdf  = $this->asegurarPdfDesdeXml($comprobante, $this->pdfOpcionesFromQuery());

        if ($absPdf === null || !is_file($absPdf)) {
            return $this->response->withStatus(500)->withStringBody('No hay XML asociado o no se pudo generar el PDF.');
        }

        return $this->response->withFile($absPdf, [
            'download' => true,
            'name' => ($comprobante->uuid ?? 'comprobante') . '.pdf',
        ]);
    }

    /**
     * Página HTML con el PDF embebido (visor dentro de la aplicación).
     *
     * @param string|null $id ID del comprobante
     * @return void
     */
    public function vistaPdf($id = null)
    {
        $comprobante = $this->Comprobantes->get($id, contain: []);

        $serie = trim((string)($comprobante->serie ?? ''));
        $folio = trim((string)($comprobante->folio ?? ''));
        $label = ($serie !== '' || $folio !== '')
            ? trim($serie . ' / ' . $folio, ' /')
            : (string)($comprobante->uuid ?? $comprobante->id);

        $pdfSrc = Router::url(['action' => 'verPdf', $comprobante->id], false);

        return $this->renderVisorPdf($pdfSrc, 'PDF — ' . $label, false);
    }

    /**
     * Sirve el PDF para mostrarlo en el navegador (inline), sin forzar descarga.
     *
     * @param string|null $id ID del comprobante
     * @return \Cake\Http\Response
     */
    public function verPdf($id = null)
    {
        $this->autoRender = false;

        $comprobante = $this->Comprobantes->get($id, contain: []);
        $filename    = ($comprobante->uuid ?? 'comprobante') . '.pdf';

        $absPdf = $this->asegurarPdfDesdeXml($comprobante, $this->pdfOpcionesFromQuery());
        if ($absPdf === null || !is_file($absPdf)) {
            return $this->response->withStatus(404)
                ->withType('text/html')
                ->withStringBody('<html><body style="font-family:sans-serif;padding:2rem"><h2>PDF no disponible</h2><p>No hay XML asociado o no se pudo generar el PDF.</p></body></html>');
        }

        $pdfData = (string)file_get_contents($absPdf);

        return $this->response
            ->withType('application/pdf')
            ->withHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->withStringBody($pdfData);
    }

    /**
     * Regenera el PDF desde el XML si falta o la plantilla/XML es más reciente.
     *
     * @return string|null Ruta absoluta del PDF listo para servir
     */
    private function asegurarPdfDesdeXml(\App\Model\Entity\Comprobante $comprobante, array $pdfOpciones = []): ?string
    {
        $pathXML = (string)($comprobante->xml ?? '');
        $pathPDF = (string)($comprobante->pdf ?? '');
        if ($pathXML === '' || $pathPDF === '') {
            return null;
        }

        $mostrarCp = !array_key_exists('mostrarCartaPortePdf', $pdfOpciones)
            || (bool)$pdfOpciones['mostrarCartaPortePdf'];
        if (!$mostrarCp) {
            $pathPDF = preg_replace('/\.pdf$/i', '-sincp.pdf', $pathPDF) ?? $pathPDF;
        }

        $absXml = WWW_ROOT . ltrim($pathXML, '/');
        $absPdf = WWW_ROOT . ltrim($pathPDF, '/');
        if (!is_file($absXml)) {
            return is_file($absPdf) ? $absPdf : null;
        }

        $plantilla = ROOT . DS . 'templates' . DS . 'Comprobantes' . DS . 'plantilla.php';
        $ppdfFile  = ROOT . DS . 'src' . DS . 'Utility' . DS . 'Ppdf.php';
        $plantillaMtime = is_file($plantilla) ? (int)filemtime($plantilla) : 0;
        $ppdfMtime      = is_file($ppdfFile) ? (int)filemtime($ppdfFile) : 0;
        $cpTpl = ROOT . DS . 'templates' . DS . 'Comprobantes' . DS . 'Complemento' . DS . 'cartaPorte.php';
        $cpTplMtime = is_file($cpTpl) ? (int)filemtime($cpTpl) : 0;
        $pdfMtime = is_file($absPdf) ? (int)filemtime($absPdf) : 0;
        $xmlMtime = (int)filemtime($absXml);

        $debeRegenerar = !is_file($absPdf)
            || $xmlMtime > $pdfMtime
            || $plantillaMtime > $pdfMtime
            || $ppdfMtime > $pdfMtime
            || $cpTplMtime > $pdfMtime;

        if ($debeRegenerar) {
            $pdfDir = dirname($absPdf);
            if (!is_dir($pdfDir) && !@mkdir($pdfDir, 0755, true)) {
                return is_file($absPdf) ? $absPdf : null;
            }
            $_pdf = new Ppdf();
            $genOpts = ['mostrarCartaPortePdf' => $mostrarCp];
            if ($_pdf->generate($pathXML, $pathPDF, 1, null, $genOpts) !== 1 || !is_file($absPdf)) {
                return is_file($absPdf) ? $absPdf : null;
            }
            if (!$_pdf->ultimoPdfIncluyoQr()) {
                Log::warning('asegurarPdfDesdeXml: PDF sin QR, reintentando. XML: ' . $pathXML);
                $_pdf->generate($pathXML, $pathPDF, 1, null, $genOpts);
            }
        }

        return is_file($absPdf) ? $absPdf : null;
    }

    /**
     * @param array<string, mixed> $raw
     * @return array{mostrarCartaPortePdf: bool}
     */
    private function pdfOpcionesFromRequest(array $raw): array
    {
        $val = $raw['data']['pdf']['mostrarCartaPorte']
            ?? $raw['mostrar_carta_porte_pdf']
            ?? '1';
        if (is_array($val)) {
            $val = end($val);
        }

        return ['mostrarCartaPortePdf' => (string)$val !== '0' && (string)$val !== ''];
    }

    /**
     * @return array{mostrarCartaPortePdf: bool}
     */
    private function pdfOpcionesFromQuery(): array
    {
        $cp = $this->request->getQuery('cp');
        if ($cp === null) {
            return ['mostrarCartaPortePdf' => true];
        }

        return ['mostrarCartaPortePdf' => (string)$cp !== '0'];
    }

    /**
     * Visor HTML unificado para vista previa y PDF timbrado.
     */
    private function renderVisorPdf(string $pdfSrc, string $pageTitle, bool $isPreview = false): \Cake\Http\Response
    {
        $this->viewBuilder()->disableAutoLayout();
        $this->set(compact('pdfSrc', 'pageTitle', 'isPreview'));

        return $this->render('pdf_viewer');
    }

    /**
     * Elimina PDFs temporales de vista previa con más de 1 hora.
     */
    private function limpiarPdfsTemporales(string $dir, int $maxAgeSeconds = 3600): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $cutoff = time() - $maxAgeSeconds;
        foreach (glob($dir . DS . '*.pdf') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }

    /**
     * Datos de emisor para pantallas CFDI y firma, tomados solo de `empresas` (una sola empresa).
     */
    private function buildEmisorFacturaDto(?Empresa $empresa): \stdClass
    {
        $o = new \stdClass();
        $o->nombre = '';
        $o->rfc = '';
        $o->lugarExpedicion = '';
        $o->serie = 'A';
        $o->folio = '1';
        $o->regimenFiscal = '';
        $o->pemKEY = '';
        $o->pemCER = '';
        $o->numero = '';
        $o->foliosDisponibles = 0;
        $o->empresaId = 0;

        if ($empresa === null) {
            return $o;
        }

        $o->empresaId = (int)$empresa->id;
        $o->nombre = (string)($empresa->nombre ?? '');
        $o->rfc = (string)($empresa->rfc ?? '');
        $cp = trim((string)($empresa->cp ?? ''));
        $lugar = trim((string)($empresa->lugar_expedicion ?? ''));
        $o->lugarExpedicion = $lugar !== '' ? $lugar : $cp;

        $serie = trim((string)($empresa->serie_cfdi ?? ''));
        $o->serie = $serie !== '' ? $serie : 'A';

        $folioVal = $empresa->folio_cfdi;
        if ($folioVal === null || $folioVal === '') {
            $o->folio = '1';
        } else {
            $o->folio = (string)(int)$folioVal;
        }

        $o->regimenFiscal = trim((string)($empresa->regimen_fiscal ?? ''));
        $o->pemKEY = (string)($empresa->pem_key ?? '');
        $o->pemCER = (string)($empresa->pem_cer ?? '');
        $o->numero = (string)($empresa->numero_certificado ?? '');
        $o->foliosDisponibles = (int)($empresa->folios_disponibles ?? 0);

        return $o;
    }
}
