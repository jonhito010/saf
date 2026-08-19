<?php
namespace App\Utility;
	
use App\Controller\AppController;

use Cake\Utility\Xml;
use App\Utility\Ppdf;
use Cake\I18n\Time;
use DOMDocument;
use XSLTProcessor;
use Cake\View\ViewBuilder;
use App\Utility\Funciones;
use App\Utility\NumeroALetras;
use mikehaertl\wkhtmlto\Pdf;
use Cake\View\View;
use Endroid\QrCode\QrCode;

	
	class Ppdf extends AppController  {
		/***Utilidades necesarioas para generacion de PDF****/
		
		
		
		public function getLogo(){
			
			$logo = !empty($logo)?$logo:"img/logo.png";
			$result = ['logo' => $logo, 'size'=>$sizePX];
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
			$text = file_get_contents($pathXML);			
			$xmlclean = str_replace($namespaces_remplazo ,"",$text);
			$ArrayXmlClean = simplexml_load_string($xmlclean);
			$json = json_encode($ArrayXmlClean);
			$obj = json_decode($json);
			return $obj;
		}

		public function generate($pathXML,$plantilla = 1,$pathPDF){
			$parametroslogo =  $this->getLogo();
            
			$logo = $parametroslogo['logo'];
			$size = $parametroslogo['size'];
           
			if($plantilla <= 9){
				/**plantilla html**/
				$obj = $this->trasnportTOxml($pathXML);
				$Pre = 0;
				if ($tipo == 'Pre') {
					$textQr = 'Representación de un QR para ilustracion.';
					$Pre 	= 1;
				}else{
					$textQr = ($tipo == "V")?"Representación de un QR para ilustracion.":'https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id='.$obj->{'Complemento'}->{'TimbreFiscalDigital'}->{'@attributes'}->{'UUID'}.'&re='.$obj->{'Emisor'}->{'@attributes'}->{'Rfc'}.'&rr='.$obj->{'Receptor'}->{'@attributes'}->{'Rfc'}.'&tt='.$obj->{'@attributes'}->{'Total'}.'&fe='.substr($obj->{'@attributes'}->{'Sello'}, -8).'';
				}
				$qrCode = new QrCode($textQr);
				$qrpng = $qrCode->writeDataUri();
				$view = new View($this->request); // creamos la vista a renderizas
				debug($view);
				$view->setLayout('blank');        // ponemos ajax para que tome solo el html de la vista
				$color = array( 
					"intenso" => "red",
					"borde" => "red",
					"transparente" => "#fccdd2",
					"comprobante" => "red",
					"letra" => "#ffffff"
				);
				// Convert to array
				$datosParametro = json_decode(file_get_contents($pathJson), true);
				$qrpngCP = '';
				if (isset($obj->{'Complemento'}->{'CartaPorte'})){
					if ($obj->{'Complemento'}->{'CartaPorte'}->{'@attributes'}->{'Version'} == '3.1') {
						$IdCCP 			= isset($obj->{'Complemento'}->{'CartaPorte'}->{'@attributes'}->{'IdCCP'}) ? $obj->{'Complemento'}->{'CartaPorte'}->{'@attributes'}->{'IdCCP'}:'';
						$Ubicaciones 	= $obj->{'Complemento'}->{'CartaPorte'}->{'Ubicaciones'}->{'Ubicacion'};
						foreach ( $Ubicaciones as $Ubicacion){
							if (substr($Ubicacion->{'@attributes'}->{'IDUbicacion'}, 0, 2) == 'OR') {
								$FechaHoraSalidaLlegada = $Ubicacion->{'@attributes'}->{'FechaHoraSalidaLlegada'};
							}
						}
						$FechaTimbrado 			= isset($obj->{'Complemento'}->{'TimbreFiscalDigital'}->{'@attributes'}->{'FechaTimbrado'})?$obj->{'Complemento'}->{'TimbreFiscalDigital'}->{'@attributes'}->{'FechaTimbrado'}:'';
						$QrCP = ($tipo == "V")?"Representación de un QR para ilustracion - Vista Previa":'https://verificacfdi.facturaelectronica.sat.gob.mx/verificaccp/default.aspx?IdCCP='.$IdCCP.'&FechaOrig='.$FechaHoraSalidaLlegada.'&FechaTimb='.$FechaTimbrado.'';
						$qrCodeCP = new QrCode($QrCP);
						$qrpngCP = $qrCodeCP->writeDataUri();
					}	
				}
				debug(obj);
				die;

				
				$view->set(compact("obj","logo","color","qrpng","datosParametro","ticket","plantilla","qrpngCP","size","Pre")); // podemos mandar variables al ctp
				//Pruebas para espacios en blanco en pdf..........................................................................................................................................................
				
				
					$html = $view->render("Comprobantes/plantilla.php");   // en la carpeta Template creamos Anexos/pdf.ctp para llevar un orden de los archivos, es opcional
				
		
				
				
				
				$cadena = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
				$aleatorio = str_shuffle($cadena);
				$cadenaAleatoria = substr($aleatorio,0,8);
				$rfc = str_replace('&', 'Y', $obj->{'Emisor'}->{'@attributes'}->{'Rfc'});
				$rfc = str_replace('Ñ', '', $rfc);
				$tenant = $rfc.'_'.time();
				$pathHTML = '/tmp/'.$tenant.'_'.$cadenaAleatoria.'.html';
				//$pathHTML = '/tmp/'.$tenant.'.html';
				file_put_contents($pathHTML, $html);
				
				//$cmd = "xvfb-run wkhtmltopdf --margin-top 2 --margin-bottom 2 --margin-left 2 --margin-right 2 --page-size Letter --viewport-size 35000x1024 $pathHTML $pathPDF";
				//$cmd = "xvfb-run -a wkhtmltopdf --margin-top 2 --margin-bottom 3 --margin-left 2 --margin-right 2 --footer-center 'Este documento es una representacion impresa de un CFDI 4.0' --footer-font-size 7 --footer-right 'Pagina [page]/[topage]' --page-size Letter --viewport-size 20000x1024 --footer-spacing -5 $pathHTML $pathPDF";
				$cmd = "xvfb-run -a wkhtmltopdf --margin-top 2 --margin-bottom 3 --margin-left 4 --margin-right 4 --footer-font-size 6 --footer-right 'Pagina [page]/[topage]' --page-size Letter --viewport-size 20000x1024 --footer-spacing -1 $pathHTML $pathPDF";
				//error_log(print_r($cmd, TRUE));
				//$cmd = "xvfb-run wkhtmltopdf --header-right 'Pagina [page] de [topage]' --margin-top 5 --margin-bottom 1 --margin-left 1 --margin-right 1 --page-size Letter --viewport-size 35000x1024 /tmp/wip.html $pathPDF";//Con Paginado preguntar a WIP
				shell_exec($cmd);
				if($tipo == "V"){
					header("Content-type:application/pdf"); 		
					readfile($pathPDF);	
					unlink($pathXML);
					if (file_exists($pathPDF)) {
						unlink($pathPDF);
					}
				}else{
					return 1;
				}
				unlink($pathHTML);
 			   	die;
 			  
			}
			
		}
	
		public function generaCadena(){
			$cadena = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
			$aleatorio = str_shuffle($cadena);
			$cadenaAleatoria = substr($aleatorio,0,8);
			return $cadenaAleatoria;
		}

		
		
		
	

	}