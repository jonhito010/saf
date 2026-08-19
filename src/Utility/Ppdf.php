<?php
namespace App\Utility;
	
use Cake\Utility\Xml;
use App\Utility\Ppdf;
use Cake\I18n\Time;
use DOMDocument;
use XSLTProcessor;
use Cake\View\ViewBuilder;
use App\Utility\CfdiQrSat;
use App\Utility\Funciones;
use App\Utility\NumeroALetras;
use Cake\Http\ServerRequestFactory;
use Cake\Routing\Router;
use Cake\Log\Log;
use Cake\View\View;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Encoding\Encoding;

	
	class Ppdf {
		/***Utilidades necesarioas para generacion de PDF****/

		/** Si la última llamada a generate() incluyó imagen QR en la plantilla. */
		private bool $ultimoPdfIncluyoQr = false;

		public function ultimoPdfIncluyoQr(): bool
		{
			return $this->ultimoPdfIncluyoQr;
		}
		
		
		
		public function getLogo(){
			$rel  = 'img/logo.PNG';
			$abs  = WWW_ROOT . $rel;
			// Devuelve data URI para que Dompdf embeba la imagen sin depender de URLs.
			if (is_file($abs)) {
				$mime = mime_content_type($abs) ?: 'image/png';
				$b64  = base64_encode(file_get_contents($abs));
				$logo = 'data:' . $mime . ';base64,' . $b64;
			} else {
				$logo = '';
			}
			$result = ['logo' => $logo, 'size' => 100];
			return $result;
		}

	
		
	
		
		public function trasnportTOxml($pathXML){
			$namespaces_remplazo = array(
			'cfdi:','xmlns:','xsi:','oxml:', 'ns3:', 'ns9:', 'ns8:',
			'dig:', 'res:', 'wsu:', 'wsse:','ns3:', 'wsu:', 'soapenv:', 'env:',
			'oxml:', '<![CDATA[', ']]>','tfd:','divisas:','ine:','iedu:' ,'detallista:','ventavehiculos:', 'cce11:' , 'leyendasFisc:',
			'aerolineas:', 'donat:','notariospublicos:' ,'servicioparcial:','vehiculousado:' ,'detallista:' , 'cartaporte20:','terceros:',
			'implocal:','pfic:', 'nomina12:','retenciones:','enajenaciondeacciones:','dividendos:','intereses:','arrendamientoenfideicomiso:',
			'pagosaextranjeros:','premios:','fideicomisonoempresarial:', 'planesderetiro:','intereseshipotecarios:','operacionesconderivados:',
			'sectorfinanciero:','pago20:','pago10:','cartaporte30:','cce20:','cartaporte31:','plataformasTecnologicas:');   
			//Se elimina del array -->  'S:' por que en las descripciones no aparece cuando escriben -->  ESCUELAS:  Esta
			$text = file_get_contents(WWW_ROOT . ltrim($pathXML, '/')); 
			//$text = file_get_contents($pathXML);			
			$xmlclean = str_replace($namespaces_remplazo ,"",$text);
			$ArrayXmlClean = simplexml_load_string($xmlclean);
			$json = json_encode($ArrayXmlClean);
			$obj = json_decode($json);
			return $obj;
		}

		public function generate($pathXML, $pathPDF, $plantilla = 1, $tipo = null, array $opciones = [])
		{
			$parametroslogo = $this->getLogo();
			$logo = $parametroslogo['logo'];
			$size = $parametroslogo['size'];
			$mostrarCartaPortePdf = !array_key_exists('mostrarCartaPortePdf', $opciones)
				|| (bool)$opciones['mostrarCartaPortePdf'];

			if ($plantilla <= 9) {
				$obj = $this->trasnportTOxml($pathXML);
				$Pre = ($tipo === 'V') ? 1 : 0;
				$qrs = $this->prepararQrsDesdeObjeto($obj, $tipo, $mostrarCartaPortePdf);
				$textQr = $qrs['textQr'];
				$qrpng = $qrs['qrpng'];
				$textQrCP = $qrs['textQrCP'];
				$qrpngCP = $qrs['qrpngCP'];
				$uuidCfdi = (string)($obj->{'Complemento'}->{'TimbreFiscalDigital'}->{'@attributes'}->{'UUID'} ?? '');
				$this->ultimoPdfIncluyoQr = ($tipo === 'V') || ($uuidCfdi === '') || ($qrpng !== '');

				$request = Router::getRequest();
				if ($request === null) {
					$request = ServerRequestFactory::fromGlobals();
				}
				$view = new View($request);
				$view->disableAutoLayout();
				$view->setTemplatePath('Comprobantes');
				$color = [];
				$datosParametro = [];
				$direccion = null;
				$mx = null;

				$view->loadHelper('Url');
				$view->loadHelper('Html');
				$view->set(compact(
					'obj',
					'logo',
					'color',
					'qrpng',
					'textQr',
					'datosParametro',
					'plantilla',
					'qrpngCP',
					'textQrCP',
					'size',
					'Pre',
					'direccion',
					'mx',
					'mostrarCartaPortePdf'
				));

				try {
					$html = $view->render('plantilla');
					$html = $this->dompdfPrepareHtml($html);

					return $this->writePdfWithDompdf($html, $pathPDF) ? 1 : 0;
				} catch (\Throwable $e) {
					Log::error('Ppdf::generate: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());

					return 0;
				}
			}

			return 0;
		}
	
		/**
		 * Igual que generate() pero devuelve el HTML en lugar de escribir el PDF.
		 * Útil para depurar la plantilla desde el navegador.
		 *
		 * @return string|null  HTML listo para Dompdf, o null si falla.
		 */
		/**
		 * @return array{html: string}|array{error: string}
		 */
		public function generateHtml(string $pathXML): array
		{
			try {
				$parametroslogo = $this->getLogo();
				$logo = $parametroslogo['logo'];
				$size = $parametroslogo['size'];

				$obj = $this->trasnportTOxml($pathXML);
				$Pre = 0;
				$qrs = $this->prepararQrsDesdeObjeto($obj, null);
				$textQr = $qrs['textQr'];
				$qrpng = $qrs['qrpng'];
				$textQrCP = $qrs['textQrCP'];
				$qrpngCP = $qrs['qrpngCP'];
				$plantilla = 1;

				$request = Router::getRequest();
				if ($request === null) {
					$request = ServerRequestFactory::fromGlobals();
				}
				$view = new View($request);
				$view->disableAutoLayout();
				$view->setTemplatePath('Comprobantes');
				$color = [];
				$datosParametro = [];
				$direccion = null;
				$mx = null;

				$view->loadHelper('Url');
				$view->loadHelper('Html');
				$view->set(compact(
					'obj', 'logo', 'color', 'qrpng', 'textQr', 'datosParametro',
					'plantilla', 'qrpngCP', 'textQrCP', 'size', 'Pre', 'direccion', 'mx',
					'mostrarCartaPortePdf',
				));

				$html = $view->render('plantilla');
				return ['html' => $this->dompdfPrepareHtml($html)];
			} catch (\Throwable $e) {
				$msg = $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine();
				Log::error('Ppdf::generateHtml: ' . $msg);
				return ['error' => $msg];
			}
		}

		public function generaCadena(){
			$cadena = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
			$aleatorio = str_shuffle($cadena);
			$cadenaAleatoria = substr($aleatorio,0,8);
			return $cadenaAleatoria;
		}

		public function personaliza($data,$pathJson,$pathXML,$plantilla,$pathPDF,$tipo = null,$emisor_id = null ,$color= null){
			$parametroslogo = $this->getLogo();
			$logo = $parametroslogo['logo'];
			$size = $parametroslogo['size'];
			
			if($plantilla <= 9 || $plantilla ==9000){
				
				$plantilla = 10000;
				
				/**plantilla html**/
				$obj = $this->trasnportTOxml($pathXML);
				$qrs = $this->prepararQrsDesdeObjeto($obj, $tipo);
				$textQr = $qrs['textQr'];
				$qrpng = $qrs['qrpng'];
				$textQrCP = $qrs['textQrCP'];
				$qrpngCP = $qrs['qrpngCP'];
				$request = Router::getRequest();
				if ($request === null) {
					$request = ServerRequestFactory::fromGlobals();
				}
				$view = new View($request);
				$view->disableAutoLayout();
				$view->loadHelper('Url');
				$view->loadHelper('Html');
				if(isset($color)){
					
				}else{
					$color = array(
					"intenso" => "#0478cb",
					"borde" => "#0478cb",
					"transparente" => "#eef2fd",
					"comprobante" => "#419c9c",
					"letra" => "#ffffff"
				);
				}
				// Convert to array
				$datosParametro = json_decode(file_get_contents($pathJson), true);
				
				
				$view->set(compact("obj","logo","color","qrpng","textQr","qrpngCP","textQrCP","datosParametro")); // podemos mandar variables al ctp
				//Pruebas para espacios en blanco en pdf..........................................................................................................................................................
			
				if ($datosParametro['tipoComprobante'] != 'Nomina') {
					$html = $view->render("Facturas/{$plantilla}");   // en la carpeta Template creamos Anexos/pdf.ctp para llevar un orden de los archivos, es opcional
					error_log('plantilla cfdi: '.$plantilla);
				}else{
					error_log('Nomina:'.$plantilla);
					$html = $view->render("Nomina/{$plantilla}");   // en la carpeta Template creamos Anexos/pdf.ctp para llevar un orden de los archivos, es opcional
				}
				
				$html = $this->dompdfPrepareHtmlGeneric($html);
				$ok = $this->writePdfWithDompdf($html, $pathPDF);
				if ($tipo == 'V') {
					$absPdf = $this->resolveAbsolutePdfPath($pathPDF);
					if ($ok && is_file($absPdf)) {
						header('Content-Type: application/pdf');
						readfile($absPdf);
					}
					die;
				}

				return $ok ? 1 : 0;
			}else{
					error_log('aqui');
					/**JAR **/
					$pathPlan = '/home/cfdi/plantillas';
					$jasper = $pathPlan.'/' . $plantilla . '/plantilla33.jasper';
					$jar = $this->getJar();		
					$cmd = "java -jar $jar \"$pathXML\" $jasper \"$pathPDF\" \"$pathJson\" '/var/www/html/cfdi4/webroot/" . $logo . "'";
					$this->log($cmd);
					$ret = shell_exec($cmd);
					
					if($tipo == "V"){
					
					$time = time();
					$fileTMP = "/tmp/" . $data['Emisor']['Rfc'] . "-$time.pdf";
					file_put_contents($fileTMP, base64_decode($ret));
					header("Content-type:application/pdf");
					header("Content-Length: ".filesize($fileTMP));
						@readfile($fileTMP);
					unlink($pathJson);
					unlink($pathXML);
					if (file_exists($pathPDF)) {
						unlink($pathPDF);
					}
					die;	
					}else{
					 return !empty($ret)?1:0;
					}
				}
				if($tipo == "V"){
					error_log('aqui esta................');
					if($plantilla>9){
						@readfile($fileTMP);
				}
			}else{
			return 1; 
			}		
			die;	
		}
		
		
		/**
		 * Ruta absoluta del PDF (ruta relativa al webroot o absoluta del SO).
		 */
		private function resolveAbsolutePdfPath(string $pathPDF): string
		{
			$pathPDF = trim((string)$pathPDF);
			if ($pathPDF === '') {
				return '';
			}
			// Ruta Windows tipo C:\...
			if (preg_match('#^[A-Za-z]:[/\\\\]#', $pathPDF)) {
				return $pathPDF;
			}
			// Ruta absoluta real que ya existe (p. ej. /var/www/... en Linux)
			if ($pathPDF[0] === '/' && is_file($pathPDF)) {
				return $pathPDF;
			}
			// Rutas de BD tipo /comprobantes/pdf/... (URL bajo webroot, no raíz del disco)
			return WWW_ROOT . str_replace('\\', '/', ltrim($pathPDF, '/'));
		}

		/**
		 * Escribe HTML a PDF con Dompdf (no requiere wkhtmltopdf ni xvfb).
		 */
		private function writePdfWithDompdf(string $html, string $pathPDF): bool
		{
			if (!class_exists(\Dompdf\Dompdf::class) || !class_exists(\Dompdf\Options::class)) {
				Log::error('Ppdf::writePdfWithDompdf: Dompdf no instalado (faltan clases Dompdf\\Dompdf/Options).');
				return false;
			}

			$absPdf = $this->resolveAbsolutePdfPath($pathPDF);
			if ($absPdf === '') {
				Log::warning('Ppdf::writePdfWithDompdf: ruta PDF vacía.');

				return false;
			}
			$pdfDir = dirname($absPdf);
			if (!is_dir($pdfDir) && !@mkdir($pdfDir, 0755, true)) {
				Log::error('Ppdf::writePdfWithDompdf: no se pudo crear el directorio: ' . $pdfDir);

				return false;
			}
			$webroot = realpath(WWW_ROOT);
			if ($webroot === false) {
				$webroot = WWW_ROOT;
			}
			try {
				$options = new Options();
				$options->set([
					'isRemoteEnabled'      => false,
					'isHtml5ParserEnabled' => true,
					'defaultFont'          => 'DejaVu Sans',
					'chroot'               => $webroot,
				]);
				$dompdf = new Dompdf($options);
				$dompdf->setBasePath($webroot);
				$dompdf->loadHtml($html, 'UTF-8');
				$dompdf->setPaper('letter', 'portrait');
				$dompdf->render();
				$out = $dompdf->output();
				$bytes = @file_put_contents($absPdf, $out);
				if ($bytes === false || !is_file($absPdf)) {
					Log::error('Ppdf::writePdfWithDompdf: no se pudo escribir el archivo: ' . $absPdf);

					return false;
				}

				return true;
			} catch (\Throwable $e) {
				Log::error('Ppdf::writePdfWithDompdf: ' . $e->getMessage());

				return false;
			}
		}

		/**
		 * HTML genérico (Facturas/Nómina) para Dompdf: sin JS, sin Bootstrap CDN, rejilla float.
		 */
		private function dompdfPrepareHtmlGeneric(string $html): string
		{
			$html = preg_replace('#<link[^>]*bootstrap[^>]*>#i', '', $html);
			$html = preg_replace('#<base\b[^>]*>#i', '', $html);
			$html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
			$block = '<style type="text/css">' . $this->dompdfGridCss() . '</style>';
			if (stripos($html, '</head>') !== false) {
				return preg_replace('#</head>#i', $block . '</head>', $html, 1);
			}
			return $block . $html;
		}

		/**
		 * Ajusta el HTML de la plantilla CFDI para Dompdf (sin wkhtmltopdf).
		 * Orden: grid CSS primero → 1.css encima (sus reglas específicas tienen prioridad).
		 */
		private function dompdfPrepareHtml(string $html): string
		{
			// Eliminar tags incompatibles con Dompdf
			$html = preg_replace('#<link[^>]*plantillas/1\.css[^>]*>#i', '', $html, 1);
			$html = preg_replace('#<link[^>]*bootstrap[^>]*>#i', '', $html);
			$html = preg_replace('#<base\b[^>]*>#i', '', $html);
			$html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);

			// CSS base mínimo (la plantilla ya lleva sus estilos inline en el <style> del <head>)
			$block = '<style type="text/css">' . $this->dompdfGridCss() . '</style>';

			if (stripos($html, '</head>') !== false) {
				return preg_replace('#</head>#i', $block . '</head>', $html, 1);
			}

			return $block . $html;
		}

		/**
		 * Rejilla Bootstrap 12 columnas con floats para Dompdf.
		 * Dompdf no soporta flexbox: se usa float + clearfix en .row.
		 * No se definen paddings aquí — los hereda de 1.css que viene después.
		 */
		private function dompdfGridCss(): string
		{
			// La nueva plantilla usa layout 100% <table>, no necesita rejilla Bootstrap.
			// Este CSS mínimo garantiza base limpia y compatibilidad con complementos legacy.
			return <<<'CSS'
*, *::before, *::after { box-sizing: border-box; }
body { margin: 0; padding: 0; font-family: DejaVu Sans, Arial, sans-serif; }
table { border-collapse: collapse; }
p { margin: 0; }
img { max-width: 100%; }
img.qr-sat { max-width: none !important; width: 2.75cm !important; height: 2.75cm !important; }
* { box-shadow: none !important; }
CSS;
		}

		/**
		 * @return array{textQr: string, qrpng: string, textQrCP: string|null, qrpngCP: string}
		 */
		private function prepararQrsDesdeObjeto(object $obj, ?string $tipo, bool $mostrarCartaPortePdf = true): array
		{
			$esPreview = ($tipo === 'V');
			$textQr = CfdiQrSat::urlCfdi($obj, $esPreview);
			$qrpng = $this->generarQrSrc($textQr);
			$textQrCP = $mostrarCartaPortePdf ? CfdiQrSat::urlCartaPorte31($obj, $esPreview) : null;
			$qrpngCP = $textQrCP !== null ? $this->generarQrSrc($textQrCP) : '';

			$uuid = (string)($obj->{'Complemento'}->{'TimbreFiscalDigital'}->{'@attributes'}->{'UUID'} ?? '');
			if ($uuid !== '' && !$esPreview && $qrpng === '') {
				Log::error('Ppdf::prepararQrsDesdeObjeto: CFDI timbrado sin QR. UUID=' . $uuid);
			}

			return [
				'textQr' => $textQr,
				'qrpng' => $qrpng,
				'textQrCP' => $textQrCP,
				'qrpngCP' => $qrpngCP,
			];
		}

		/**
		 * Ruta relativa al webroot (Dompdf chroot) o data URI para incrustar el QR.
		 * Archivo en disco es más fiable que data URI largos bajo Apache/Dompdf.
		 */
		private function generarQrSrc(string $texto): string
		{
			if (!class_exists(QrCode::class) || trim($texto) === '') {
				return '';
			}

			$dirRel = 'tmp/qr';
			$dirAbs = WWW_ROOT . str_replace('/', DS, $dirRel);
			if (!is_dir($dirAbs) && !@mkdir($dirAbs, 0755, true)) {
				Log::error('Ppdf::generarQrSrc: no se pudo crear el directorio ' . $dirAbs);

				return $this->generarQrDataUriRespaldo($texto);
			}

			$hash = md5($texto);

			try {
				$qrCode = CfdiQrSat::crearQrCode($texto);

				if (extension_loaded('gd')) {
					$rel = $dirRel . '/' . $hash . '.png';
					$abs = $dirAbs . DS . $hash . '.png';
					if (!is_file($abs)) {
						$pngData = (new PngWriter())->write($qrCode)->getString();
						if (@file_put_contents($abs, $pngData) === false) {
							return $this->generarQrDataUriRespaldo($texto);
						}
					}

					return $rel;
				}

				$rel = $dirRel . '/' . $hash . '.svg';
				$abs = $dirAbs . DS . $hash . '.svg';
				if (!is_file($abs)) {
					$svgData = (new SvgWriter())->write(
						$qrCode,
						null,
						null,
						[SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => true]
					)->getString();
					if (@file_put_contents($abs, $svgData) === false) {
						return $this->generarQrDataUriRespaldo($texto);
					}
				}

				return $rel;
			} catch (\Throwable $e) {
				Log::error('Ppdf::generarQrSrc: ' . $e->getMessage());

				return $this->generarQrDataUriRespaldo($texto);
			}
		}

		private function generarQrDataUriRespaldo(string $texto): string
		{
			if (!class_exists(QrCode::class) || trim($texto) === '') {
				return '';
			}

			try {
				$qrCode = CfdiQrSat::crearQrCode($texto);

				if (extension_loaded('gd')) {
					$pngData = (new PngWriter())->write($qrCode)->getString();

					return 'data:image/png;base64,' . base64_encode($pngData);
				}

				$svgData = (new SvgWriter())->write(
					$qrCode,
					null,
					null,
					[SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => true]
				)->getString();

				return 'data:image/svg+xml;base64,' . base64_encode($svgData);
			} catch (\Throwable $e) {
				Log::error('Ppdf::generarQrDataUriRespaldo: ' . $e->getMessage());

				return '';
			}
		}
		
		private function construirRutaTemporal(string $nombreArchivo): string
		{
			$base = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
			if (!is_dir($base)) {
				mkdir($base, 0777, true);
			}
			return $base . DIRECTORY_SEPARATOR . ltrim($nombreArchivo, DIRECTORY_SEPARATOR);
		}
		
	}