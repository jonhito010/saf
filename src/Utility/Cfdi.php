<?php
namespace App\Utility;
use Cake\Utility\Xml;
use App\Utility\Complementos;
use DOMDocument;
use XSLTProcessor;
use App\Utility\NumeroALetras;
class Cfdi {
	var $xml;
	var $root;

	/** Reemplazo de utf8_encode() (deprecado en PHP 8.2+). */
	private static function cfdiUtf8Encode(string $value): string
	{
		if ($value === '') {
			return '';
		}
		if (function_exists('mb_check_encoding') && mb_check_encoding($value, 'UTF-8')) {
			return $value;
		}

		return mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
	}

	/** Reemplazo de utf8_decode() (deprecado en PHP 8.2+). */
	private static function cfdiUtf8Decode(string $value): string
	{
		if ($value === '') {
			return '';
		}
		if (function_exists('mb_check_encoding') && mb_check_encoding($value, 'UTF-8')) {
			return $value;
		}

		return mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
	}
	
	public function __construct() {
		$this->xml = new DOMdocument("1.0", "UTF-8");
	}
	
	public function setData($data, $complementos =null){
		$complementosmasiva = $complementos;
		
		$complemento_ = new Complementos();
		$complementos = $complemento_->setComplemento($data);
		$complemento = $this->xml->createElement("cfdi:Complemento");
		$this->root = $this->xml->createElement("cfdi:Comprobante");
		$this->root = $this->xml->appendChild($this->root);
		$this->setComprobante($data);
		if(isset($data['global']['uso']) && $data['global']['uso'] == 1){
			$this->setGlobal($data);	
		}
		/*if($complementos['RELACIONADOS'] == true || (isset($complementosmasiva['NOMINAMASIVA']) && $complementosmasiva['NOMINAMASIVA'] == true) || $complementosmasiva['RELACIONADOS'] == true){
			$this->setRelacion($data);
		}*/
		$this->setEmisor($data);
		$this->setReceptor($data);
		$this->setConceptos($data,$complementos);
		if ($complementos['NOMINA'] ==  true) {
			$rootNomina = $this->setNomina($data,$complemento);
			$this->setNominaEmisor($data, $rootNomina);
			$this->setNominaReceptor($data, $rootNomina);
			$this->setNominaPercepcion($data, $rootNomina);
			$this->setNominaDeduccion($data, $rootNomina);
			$this->setNominaOtrosPagos($data, $rootNomina);
			$this->setNominaIncapacidad($data, $rootNomina);
		}else{
			$this->setImpuestos($data);
		} 

		if(isset($complementos['PF']) && $complementos['PF'] == true){
			$this->setPF($data,$complemento);
		}

		if($complementos['INE'] == true){
			$this->setINE($data,$complemento);
		}
		
		if($complementos['DONATARIAS'] == true){
			$this->setDonatarias($data,$complemento);
		}

		if($complementos['CONSTRUCCION'] == true){
			$this->setContruccion($data,$complemento);
		}

		if($complementos['LEYENDAS'] == true){
			$this->setLeyendas($data,$complemento);
		}

		if($complementos['NOTARIOS'] == true){
			$this->setNotarios($data,$complemento);
		}

		if(!empty($complementos['DETALLISTA']) && $complementos['DETALLISTA'] == true){
			$this->setDetallista($data,$complemento);
		}

		if(!empty($complementos['VEHICULO']) && $complementos['VEHICULO'] == true){
			$this->setVehiculo($data,$complemento);
		}

		if(!empty($complementos['COMERCIO']) && $complementos['COMERCIO'] == true){
			$this->setComercioExterior($data,$complemento);
		}

		if(!empty($complementos['AEROLINEAS']) && $complementos['AEROLINEAS'] == true){
			$this->setAerolinea($data,$complemento);
		}	

		if(!empty($complementos['DIVISAS']) && $complementos['DIVISAS'] == true){
			$this->setDivisas($data,$complemento);
		}
		
	
		if(!empty($complementos['CARTAPORTE31']) && $complementos['CARTAPORTE31'] == true){
			$this->setCartaPorte31($data,$complemento);
		}
		
		
		if (!empty($data['json']['locales']) && $data['Cfdi']['TipoDeComprobante'] != 'N') {
			$locales = json_decode($data['json']['locales']);
			if (!empty($locales->Traslados) || !empty($locales->Retenidos)) {
				$this->setLocales($data, $locales,$complemento);
			}
		}
	}
	
	
	private function setLocales($data, $locales,$complemento) {
  
		//$complemento = $this->xml->createElement("cfdi:Complemento");
		$complemento = $this->root->appendChild($complemento);

		$complementoImplocal= [
			//"xmlns:implocal"	   =>	"http://www.sat.gob.mx/implocal",
			"version"					   =>	"1.0" ,
			"TotaldeRetenciones" =>	$this->validarCeros($locales->TotalImpuestosRetenidos),
			"TotaldeTraslados"	 => $this->validarCeros($locales->TotalImpuestosTrasladados),
			//"xsi:schemaLocation" => "http://www.sat.gob.mx/implocal http://www.sat.gob.mx/sitio_internet/cfd/implocal/implocal.xsd"
		];

		$implocal = $this->xml->createElement("implocal:ImpuestosLocales"); 
		$implocal = $complemento->appendChild($implocal);
		$this->satxmlsv33_cargaAtt($implocal, $complementoImplocal);
		
		foreach($locales->Traslados as $traslado) {
			$trasladoLocal = $this->xml->createElement("implocal:TrasladosLocales"); 
			$trasladoLocal = $implocal->appendChild($trasladoLocal);
			$this->satxmlsv33_cargaAtt($trasladoLocal, [
				'ImpLocTrasladado' => $traslado->ImpLocTrasladado,
				'TasadeTraslado'   => $traslado->TasadeTraslado,
				'Importe'          => $this->validarCeros($traslado->Importe)
			]);	
		}
		 
		foreach($locales->Retenidos as $retenido) {
			$retenidoLocal = $this->xml->createElement("implocal:RetencionesLocales");
			$retenidoLocal = $implocal->appendChild($retenidoLocal);
			$this->satxmlsv33_cargaAtt($retenidoLocal, [
				'ImpLocRetenido' 	 => $retenido->ImpLocRetenido,
				'TasadeRetencion'  => $retenido->TasadeRetencion,
				'Importe'          => $this->validarCeros($retenido->Importe)
			]);	
		}
  }
  private function sindecimales($moneda) {
		$ceroDecimales   = ['BIF', 'BYR', 'CLP', 'DJF', 'GNF', 'ISK', 'JPY', 'KMF', 'KRW', 'PYG', 'RWF', 'UGX', 'UYI', 'VND', 'VUV', 'XAF', 'XAG', 'XAU', 'XBA', 'XBB', 'XBC', 'XBD', 'XDR', 'XOF', 'XPD', 'XPF', 'XPT', 'XSU', 'XTS', 'XUA', 'XXX'];
		if (in_array($moneda, $ceroDecimales)) {
			return 0;
		}else{
			return 1;
		}
	}
  
	private function setImpuestos($data) {
		if(!isset($data['json']['impuestos'])){
			return;
		}
		$impuestos = (array)json_decode($data['json']['impuestos']);
		//debug($impuestos);
		if (empty($impuestos['Traslados']) && empty($impuestos['Retenidos'])) {
			return;
		}
		if($data['Cfdi']['TipoDeComprobante'] == 'T'){
			return;
		}
		$xImpuestos = $this->xml->createElement("cfdi:Impuestos");
		$xImpuestos = $this->root->appendChild($xImpuestos);
		if (!empty($impuestos['Retenidos'])) {
			$this->satxmlsv33_cargaAtt($xImpuestos,['TotalImpuestosRetenidos' => $this->validarCeros($data['Impuestos']['TotalImpuestosRetenidos'])]);
			$xTraslados = $this->xml->createElement("cfdi:Retenciones");
			$xTraslados = $xImpuestos->appendChild($xTraslados);
			foreach($impuestos['Retenidos'] as $traslado) {
				$traslado = (array)$traslado;
				$xTraslado = $this->xml->createElement("cfdi:Retencion");
				$xTraslado = $xTraslados->appendChild($xTraslado);
				$campos = ['Impuesto', 'Importe'];
				foreach ($campos as $k) {
					if ($k == 'Importe' ) {
						
						$traslado[$k] = $this->validarCeros($traslado[$k]);
						
					}
					$this->satxmlsv33_cargaAtt($xTraslado, [$k => self::cfdiUtf8Decode($traslado[$k])]);
				}
			}
		}
		if (!empty($impuestos['Traslados'])) {
			
			foreach($impuestos['Traslados'] as $traslado) {
				$traslado = (array)$traslado;


			if($traslado['TipoFactor'] != "Exento"){

			if(isset($_SESSION['Auth']['User']['emisor_id']) && $_SESSION['Auth']['User']['emisor_id'] == 48577) {
				
				if (isset($impuestos['TotalImpuestosTrasladados'])) {
					$this->satxmlsv33_cargaAtt($xImpuestos,['TotalImpuestosTrasladados' => $this->validarCeros($data['Impuestos']['TotalImpuestosTrasladados'])]);
					//$this->satxmlsv33_cargaAtt($xImpuestos,['TotalImpuestosTrasladados' => $data['Impuestos']['TotalImpuestosTrasladados']);
				}
			  
			
			}else{
			if (isset($impuestos['TotalImpuestosTrasladados'])) {
				if ($this->sindecimales($data['Cfdi']['Moneda']) == 1) {
					$this->satxmlsv33_cargaAtt($xImpuestos,['TotalImpuestosTrasladados' => $this->validarCeros($data['Impuestos']['TotalImpuestosTrasladados'])]);
				}else{
					$this->satxmlsv33_cargaAtt($xImpuestos,['TotalImpuestosTrasladados' => $data['Impuestos']['TotalImpuestosTrasladados']]);
				}
			  
			}
			}
			}
		}


			
			$xTraslados = $this->xml->createElement("cfdi:Traslados");
			$xTraslados = $xImpuestos->appendChild($xTraslados);
			
			foreach($impuestos['Traslados'] as $traslado) {
				$traslado = (array)$traslado;
				//debug($traslado);
				
				
				$xTraslado = $this->xml->createElement("cfdi:Traslado");
				$xTraslado = $xTraslados->appendChild($xTraslado);
						
				//$campos = ['Base','Impuesto', 'TipoFactor', 'TasaOCuota', 'Importe'];
				if($traslado['TipoFactor'] == "Exento"){
						$campos = ['Base','Impuesto', 'TipoFactor'];
				}else{
						$campos = ['Base','Impuesto', 'TipoFactor', 'TasaOCuota', 'Importe'];
				}

				foreach ($campos as $k) {
					
					if ($k == 'Importe' || $k == 'Base') {
						
						
						$traslado[$k] = $this->validarCeros($traslado[$k]);
						
						
					}
					
					
					
					$this->satxmlsv33_cargaAtt($xTraslado, [$k => self::cfdiUtf8Decode($traslado[$k])]);
				}
			}
			
		}
		
		
				
	}
	
	private function setConceptos($data,$complementos) {
		$conceptos = json_decode($data['json']['conceptos']);
			
		$xConceptos = $this->xml->createElement("cfdi:Conceptos");
		$xConceptos = $this->root->appendChild($xConceptos);

		
		
		if (2 == 3){
			$xConcepto = $this->xml->createElement("cfdi:Concepto");
			$xConcepto = $xConceptos->appendChild($xConcepto);
			
			$conceptoArr=json_decode($data['json']['conceptos']);
			
				$c=(array)$conceptoArr;
				
				
				$this->satxmlsv33_cargaAtt($xConcepto,[
				 'ClaveProdServ' => $c['ClaveProdServ'],
				 'Cantidad'      => $c['Cantidad'],
				 'ClaveUnidad'   => $c['ClaveUnidad'],
				 'Descripcion'   => substr(self::cfdiUtf8Encode(html_entity_decode("Pago de n&oacute;mina")), 0, 1000),
				 'ValorUnitario' => $c['ValorUnitario'],
				 'Importe'		 => $c['Importe'],
				 'Descuento'	 => $c['Descuento'],
				]);
				
			
		}else{
		 
		/*quitar 1 concepto; ClaveProdServ*/
		/*$quitarTcarta = false;
		if(count($conceptos) == 1 && $data["complemento"]["CartaPorte"]==1){
		$quitarTcarta = true;
		}*/
			
		foreach($conceptos as $concepto) {
			$campos = ['ClaveProdServ', 'NoIdentificacion', 'Cantidad', 'ClaveUnidad','Unidad', 'Descripcion', 'ValorUnitario', 'Importe', 'Descuento','ObjetoImp'];
			
			
			
			$arrTraslados   = [];
		    $arrRetenciones = [];
			$totalTraslados   = 0;
			$totalRetenciones = 0;
			
			$xConcepto = $this->xml->createElement("cfdi:Concepto");
			$xConcepto = $xConceptos->appendChild($xConcepto);
			$concepto  = (array)$concepto;
			$this->normalizarImpuestosConcepto02($concepto, $data);
			//debug($concepto);
			//if($quitarTcarta == false){
			$concepto['ClaveProdServ'] = trim(explode(' -', $concepto['ClaveProdServ'])[0]);
			//}
			$concepto['ClaveUnidad'] = trim(explode(' -', $concepto['ClaveUnidad'])[0]);

			//$concepto['Descripcion'] = self::cfdiUtf8Decode(html_entity_decode(substr($concepto['Descripcion'], 0, 1000)));
			$concepto['Descripcion'] = str_replace("€", "&euro;", $concepto['Descripcion']);
			$concepto['Descripcion'] = iconv("UTF-8", "iso-8859-1//TRANSLIT", substr($concepto['Descripcion'], 0, 1000));
			
			if($complementos['NOMINA'] ==  false){
					if(isset($data['global']['uso']) && $data['global']['uso'] == 1){
				}
					else{
					$unidad   = file_get_contents("http://54.214.51.205/cfdi/ws/claveUnidad/" . $concepto['ClaveUnidad']);
						if (!empty($unidad)) {
							$unidad = explode(" - ", $unidad)[1];
							$unidad = trim(self::cfdiUtf8Decode($unidad));
							$concepto['Unidad'] = substr($unidad, 0, 20);
						}
					}
			}
			
			/*if(isset($concepto['iedu'])){
			$ieduNodo= (array)$concepto['iedu'];
			}else {
			$ieduNodo = 0;
			}*/
			if(isset($concepto['iedu'])){
			$ieduNodo= (array)$concepto['iedu'];
			$vieneIEDU = 1;
			}else {
			$ieduNodo = 0;
			$vieneIEDU =0;
			}
			$concepto['Descuento'] =isset($concepto['Descuento'])?$concepto['Descuento']:'';
			if (isset($concepto['Descuento']) && $concepto['Descuento'] == 0) {
				unset($concepto['Descuento']);
			} else {
				 $validaDecimales = isset($data['json']['masDecimales'])?$data['json']['masDecimales']:0;
					
				$concepto['Descuento'] = $this->validarCeros($concepto['Descuento'],$validaDecimales);
			}

			
			foreach ($campos as $k) {

				if($k == 'Unidad' && !empty($concepto[$k])){
				
			
				if(strlen($concepto[$k])> 20){
					  $concepto[$k] = trim($concepto[$k]);
					  $nombre = explode(" ", $concepto[$k]);
					  $concepto[$k] = $nombre[0];
					}else {
					  $concepto[$k] = $concepto[$k];
					}
				}
				
				if($data['Cfdi']['TipoDeComprobante'] == 'T'){ /// permite valores en 0 tipoc comprobante T
					if (!isset($concepto[$k]) ) {
						continue;
					}
				}else{
					if (empty($concepto[$k])) { // quita los ceros de importe y valor unitario en tipo comprobante T
						continue;
					}
				}
				
				//if(isset($_SESSION['Auth']['User']['emisor_id']) && $_SESSION['Auth']['User']['emisor_id'] == 48577) {
					 //$this->validarCeros($c['Descuento'])
					  $validaDecimales = isset($data['json']['masDecimales'])?$data['json']['masDecimales']:0;
						
					 if($k == 'ValorUnitario' && !empty($concepto[$k])){
						 $concepto[$k] = $this->validarCeros($concepto[$k],$validaDecimales);
					 }
					 if($k == 'Importe' && !empty($concepto[$k])){
						 $concepto[$k] = $this->validarCeros($concepto[$k],$validaDecimales);
					 }
					 if($k == 'Descuento' && !empty($concepto[$k])){
						 $concepto[$k] = $this->validarCeros($concepto[$k],$validaDecimales);
					 }
					
				
				//}
				
				$this->satxmlsv33_cargaAtt($xConcepto,[$k => $concepto[$k]]);
			
				
			}
			
			
			
			if (empty($concepto['Impuestos']) && !empty($concepto['CuentaPredial'])) {
				$xCuentaPredial = $this->xml->createElement("cfdi:CuentaPredial");
				$xCuentaPredial = $xConcepto->appendChild($xCuentaPredial);
				$this->satxmlsv33_cargaAtt($xCuentaPredial,['Numero' => $concepto['CuentaPredial']]);
				unset($concepto['CuentaPredial']);
			}
		
			
			if($data['Cfdi']['TipoDeComprobante'] != 'T'){
				
				
				if(isset($concepto['Impuestos'])){
		
			$impuestos = $this->xml->createElement("cfdi:Impuestos");
			$impuestos = $xConcepto->appendChild($impuestos);
			 
			$concepto['Impuestos'] = (array)$concepto['Impuestos'];
			if (!empty($concepto['Impuestos']['Traslados'])) {
				
				$xTraslados = $this->xml->createElement("cfdi:Traslados");
				$xTraslados = $impuestos->appendChild($xTraslados);
				
				foreach($concepto['Impuestos']['Traslados'] as $traslado) {
					
					
					$traslado = (array)$traslado;
					//debug($traslado);
					$xTraslado = $this->xml->createElement("cfdi:Traslado");
					$xTraslado = $xTraslados->appendChild($xTraslado);

					if ($traslado['TipoFactor'] != 'Exento') {
						
						if (empty($arrTraslados[$traslado['extra']])) {
							$arrTraslados[$traslado['extra']] = $traslado['Importe'];
						} else {
							$arrTraslados[$traslado['extra']] += $traslado['Importe'];
						}
						$totalTraslados += $traslado['Importe'];
					}
					
					if($traslado['TipoFactor']=='Exento'){
						unset($traslado['Importe']);
					}
					foreach ($traslado as $k => $v) {
						
						if ($k == 'id'  || $k == 'extra' || (empty($v) && $v != 0)) {
							continue;
						}
						 
						if ($k == 'Base' || $k == 'Importe') {
							if($data['Emisor']['Rfc'] == 'EKU9003173C9' || $data['Emisor']['Rfc'] == 'CBR0303073W6'){
								$validaDecimales = isset($data['json']['masDecimales'])?$data['json']['masDecimales']:0;
								$v = $this->validarCeros($v,$validaDecimales);	
				
							}else{
							$v = $this->validarCeros($v);
							}
						}
						
						
						$this->satxmlsv33_cargaAtt($xTraslado,[$k => $v]);
					}
					
				}
				
			} // Fin traslados
			
			if (!empty($concepto['Impuestos']['Retenidos'])) {

				$xTraslados = $this->xml->createElement("cfdi:Retenciones");
				$xTraslados = $impuestos->appendChild($xTraslados);
				
				foreach($concepto['Impuestos']['Retenidos'] as $traslado) {
					
					
					$traslado = (array)$traslado;
					
			
					$xTraslado = $this->xml->createElement("cfdi:Retencion");
					$xTraslado = $xTraslados->appendChild($xTraslado);
					/*if($traslado['Impuesto'] == '001' && $traslado['TasaOCuota'] == '0.012500'){
						$traslado['TasaOCuota'] = '0.0125';
					}*/
					if ($traslado['TipoFactor'] != 'Exento') {
						if (empty($arrTraslados[$traslado['extra']])) {
							$arrTraslados[$traslado['extra']] = $traslado['Importe'];
						} else {
							$arrTraslados[$traslado['extra']] += $traslado['Importe'];
						}
						$totalTraslados += $traslado['Importe'];
						
					}
					
					
					foreach ($traslado as $k => $v) {
						if ($k == 'id'  || $k == 'extra' || (empty($v) && $v != 0)) {
							continue;
						}
						
						
						if ($k == 'Base' || $k == 'Importe') {
							 
							if($data['Emisor']['Rfc'] == 'EKU9003173C9' || $data['Emisor']['Rfc'] == 'CBR0303073W6'){
								$validaDecimales = isset($data['json']['masDecimales'])?$data['json']['masDecimales']:0;
								$v = $this->validarCeros($v,$validaDecimales);	
							}else{
								$v = $this->validarCeros($v);
							}
						}
						
						$this->satxmlsv33_cargaAtt($xTraslado,[$k => $v]);
					}
					
			 	 }
				
			   } // Fin traslados
				
			  }// fin valida nodo existe impuestos
			}
			

			/*if (empty($concepto['Impuestos'])) {
				continue;
			}*/

			

				/*inicio prueba*/
				
			if(isset($concepto['vehiculos'])){ 
				$complemento = $this->xml->createElement("cfdi:ComplementoConcepto");
				$vehiculosNodo = (array)$concepto['vehiculos'];
					
					
					
					$complemento = $xConcepto->appendChild($complemento);
					
					$complementoVehiculo = [
						"version"		 =>	"1.1" ,
						"ClaveVehicular" =>	 $data['VehiculoNUEVO']['ClaveVehicular'],
						"Niv"			 =>  $data['VehiculoNUEVO']['Niv'], 
						
					];
					
					$ventahehiculo = $this->xml->createElement("ventavehiculos:VentaVehiculos"); /*creamos nodo Contabilidad de Complemento INE*/
					$ventahehiculo = $complemento->appendChild($ventahehiculo);
					$this->satxmlsv33_cargaAtt($ventahehiculo, $complementoVehiculo);
					foreach($vehiculosNodo as $nodo){
						
					$nodo = (array)$nodo;
					$complementoVehiculoParte = [
						"cantidad"		 	=>	$nodo['cantidad'],
						"unidad" 			=>	$nodo['unidad'],
						"noIdentificacion"	=>  $nodo['noidentificacion'], 
						"descripcion"		=>  $nodo['descripcion'],
						"valorUnitario"		=>  $nodo['valorunitario'],
						"importe"			=>  $nodo['importe'],
						
					];
					$partevehiculo = $this->xml->createElement("ventavehiculos:Parte"); /*creamos nodo Contabilidad de Complemento INE*/
					$partevehiculo = $ventahehiculo->appendChild($partevehiculo);
					$this->satxmlsv33_cargaAtt($partevehiculo, $complementoVehiculoParte);
					
					if(!empty($nodo['numero']) && !empty($nodo['fecha'])){
					$infaduanaVehiculoParte = [
						"numero"	=>	$nodo['numero'],
						"fecha" 	=>	$nodo['fecha'],
						"aduana"	=>  $nodo['aduana'], 
						
						
					];
					$aduanaParteVehiculo = $this->xml->createElement("ventavehiculos:InformacionAduanera"); /*creamos nodo Contabilidad de Complemento INE*/
					$aduanaParteVehiculo = $partevehiculo->appendChild($aduanaParteVehiculo);
					$this->satxmlsv33_cargaAtt($aduanaParteVehiculo, $infaduanaVehiculoParte);
					}
					
					
					
					}
			}

			if($vieneIEDU >0){ 
				$complemento = $this->xml->createElement("cfdi:ComplementoConcepto");
				foreach($ieduNodo as $nodo){
					$nodo = (array)$nodo;
					$complemento = $xConcepto->appendChild($complemento);
					
					$complementoIedu = [
						"version"		 =>	"1.0" ,
						"nombreAlumno"	 =>	 self::cfdiUtf8Decode($nodo['nombreAlumno']),
						"CURP"			 =>  self::cfdiUtf8Decode($nodo['CURP']), 
						"nivelEducativo" =>  self::cfdiUtf8Decode($nodo['nivelEducativo']),
						"autRVOE" 		 =>  $nodo['autRVOE'],
						"rfcPago"		 =>  self::cfdiUtf8Decode($nodo['rfcPago']),
					];
					
					$iedu = $this->xml->createElement("iedu:instEducativas"); /*creamos nodo Contabilidad de Complemento INE*/
					$iedu = $complemento->appendChild($iedu);
					$this->satxmlsv33_cargaAtt($iedu, $complementoIedu);
				}
			}
				
				
			if(isset($concepto['terceros'])){ 
				$tercerosData = (array)$concepto['terceros'];
				$terceros = $this->xml->createElement("cfdi:ACuentaTerceros"); /*creamos nodo Contabilidad de Complemento INE*/
				$terceros = $xConcepto->appendChild($terceros);
				$datosTerceros = [
				"RfcACuentaTerceros" => $tercerosData['rfc'],
				"NombreACuentaTerceros" => self::cfdiUtf8Decode($tercerosData['nombre']),
				"RegimenFiscalACuentaTerceros" => $tercerosData['regimen'],
				"DomicilioFiscalACuentaTerceros" => self::cfdiUtf8Decode($tercerosData['domicilio']),
				];
				$this->satxmlsv33_cargaAtt($terceros, $datosTerceros);
			}
			if (!empty($concepto['NumeroPedimento'])) {
				$NumeroPedimento = $this->xml->createElement("cfdi:InformacionAduanera");
				$NumeroPedimento = $xConcepto->appendChild($NumeroPedimento);
				$this->satxmlsv33_cargaAtt_($NumeroPedimento,['NumeroPedimento' => $concepto['NumeroPedimento']]);
				unset($concepto['NumeroPedimento']);
			}

			if (!empty($concepto['CuentaPredial'])) {
				$xCuentaPredial = $this->xml->createElement("cfdi:CuentaPredial");
				$xCuentaPredial = $xConcepto->appendChild($xCuentaPredial);
				$this->satxmlsv33_cargaAtt($xCuentaPredial,['Numero' => $concepto['CuentaPredial']]);
				unset($concepto['CuentaPredial']);
			}
				
		}
				

			
		} // Fin for conceptos	
	}

	/**
	 * @param array<string, mixed> $concepto
	 */
	private function esFactorTruncar(array $concepto): bool
	{
		$fr = (string)($concepto['factorRedondeo'] ?? 'REDONDEAR');

		return $fr === 'TRUNCAR' || $fr === '4';
	}

	private function redondeoAplicar(float $n, int $decimales, bool $truncar): float
	{
		$f = 10 ** $decimales;
		if ($truncar) {
			return floor($n * $f + 1e-9) / $f;
		}

		return round($n * $f) / $f;
	}

	/**
	 * @param array<string, mixed> $concepto
	 */
	private function baseExactConcepto(array $concepto): float
	{
		if (isset($concepto['BaseExact']) && is_numeric($concepto['BaseExact'])) {
			return max(0.0, (float)$concepto['BaseExact']);
		}
		$base = (float)($concepto['Importe'] ?? 0);
		if (!empty($concepto['Descuento'])) {
			$base -= (float)$concepto['Descuento'];
		}

		return max(0.0, $base);
	}

	private function importeImpuestoTasa(float $baseExact, float $tasa, int $decimales, bool $truncar): float
	{
		$f = 10 ** $decimales;
		if ($truncar) {
			$base = $this->redondeoAplicar($baseExact, $decimales, true);

			return floor($base * $tasa * $f + 1e-9) / $f;
		}

		return round($baseExact * $tasa * $f) / $f;
	}

	/**
	 * CFDI 4.0: ObjetoImp 02 exige nodo Concepto.Impuestos con al menos un traslado o retención.
	 *
	 * @param array<string, mixed> $concepto
	 * @param array<string, mixed> $data
	 */
	private function normalizarImpuestosConcepto02(array &$concepto, array $data): void
	{
		$objetoImp = (string)($concepto['ObjetoImp'] ?? '');
		if ($objetoImp !== '02') {
			return;
		}

		$imp = $concepto['Impuestos'] ?? null;
		if ($imp !== null) {
			$imp = json_decode(json_encode($imp), true);
			if (!empty($imp['Traslados']) || !empty($imp['Retenidos'])) {
				return;
			}
		}

		$baseExact = $this->baseExactConcepto($concepto);
		$truncar = $this->esFactorTruncar($concepto);
		$validaDecimales = $data['json']['masDecimales'] ?? 0;
		$dec = 2;
		$baseXml = $this->redondeoAplicar($baseExact, $dec, $truncar);
		$importeIva = $this->importeImpuestoTasa($baseExact, 0.16, $dec, $truncar);

		$concepto['BaseExact'] = $baseExact;
		$concepto['Impuestos'] = [
			'Traslados' => [[
				'Base' => $this->validarCeros($baseXml, $validaDecimales),
				'Impuesto' => '002',
				'TipoFactor' => 'Tasa',
				'TasaOCuota' => '0.160000',
				'Importe' => $this->validarCeros($importeIva, $validaDecimales),
				'extra' => '1-002-Tasa-0.160000-IVA_16%',
			]],
		];
	}
	
	
	private function validarCeros($number, $masDecimales = 0) {
		$number = is_numeric($number) ? (float)$number : 0.0;
		if ($masDecimales == 0) {
			return number_format($number, 2, '.', '');
		} elseif ($masDecimales == 3) {
			return number_format($number, 3, '.', '');
		} elseif ($masDecimales == 99) {
			return $number;
		} else {
			return number_format($number, 6, '.', '');
		}
	}

	/**
	 * Fecha y hora de emisión CFDI (zona México) dentro del margen de 72 h del SAT.
	 * No concatena día y hora por separado: evita 2026-06-18T23:13 tras medianoche.
	 */
	private function construirFechaEmisionCfdi(array $data): string
	{
		$tz = new \DateTimeZone('America/Mexico_City');

		$minutosMenos = 5;
		if (!empty($data['zonaHoraria']) && in_array($data['zonaHoraria'], ['PACITICO', 'PACIFICO'], true)) {
			$minutosMenos = 115;
		} elseif (!empty($data['zonaHoraria']) && in_array($data['zonaHoraria'], ['NORTE', 'PACITICOFRONTERA', 'PACIFICOFRONTERA'], true)) {
			$minutosMenos = 176;
		}
		if (!empty($data['zonaHoraria']) && $data['zonaHoraria'] === 'NORTE') {
			$minutosMenos = 236;
		}

		$ahora = new \DateTime('now', $tz);
		$referencia = (clone $ahora)->modify('-' . $minutosMenos . ' minutes');

		$fechaInput = trim((string)($data['Cfdi']['Fecha'] ?? ''));
		if ($fechaInput === '') {
			return $referencia->format('Y-m-d\TH:i:s');
		}
		if (strpos($fechaInput, 'T') !== false) {
			$fechaInput = substr($fechaInput, 0, 10);
		}

		$hoyMexico = $ahora->format('Y-m-d');
		$fecha72horas = (new \DateTime('-3 days', $tz))->format('Y-m-d');

		if ($fechaInput === $hoyMexico) {
			return $referencia->format('Y-m-d\TH:i:s');
		}

		if ($fechaInput === $fecha72horas) {
			$dt = new \DateTime($fechaInput . ' 00:00:00', $tz);
			$dt->modify('+210 minutes');

			return $dt->format('Y-m-d\TH:i:s');
		}

		$dt = \DateTime::createFromFormat('Y-m-d H:i:s', $fechaInput . ' ' . $referencia->format('H:i:s'), $tz);
		if ($dt === false) {
			return $referencia->format('Y-m-d\TH:i:s');
		}
		if ($dt > $referencia) {
			$dt = $referencia;
		}

		return $dt->format('Y-m-d\TH:i:s');
	}
	
	private function setComprobante($data) {
		date_default_timezone_set('America/Mexico_City');
		if ($data['Cfdi']['Fecha'] != 'fecha_tmp') {
			$data['Cfdi']['Fecha'] = $this->construirFechaEmisionCfdi($data);
		}
		if ($data['Cfdi']['Descuento'] == 0) {
			unset($data['Cfdi']['Descuento']);
		} else {
			$data['Cfdi']['Descuento'] = $this->validarCeros($data['Cfdi']['Descuento']);
		}
		
		if ($this->sindecimales($data['Cfdi']['Moneda']) == 1) {
			$data['Cfdi']['SubTotal'] = $this->validarCeros($data['Cfdi']['SubTotal']);
			$data['Cfdi']['Total'] = $this->validarCeros($data['Cfdi']['Total']);
		}

		$this->satxmlsv33_cargaAtt($this->root,['Version' => '4.0']);

		foreach ($data['Cfdi'] as $k => $v) {
			if ($data['Cfdi']['TipoDeComprobante'] != 'T') {
				if (empty($v)) {
					continue;
				}
			}
			$this->satxmlsv33_cargaAtt($this->root,[$k => $v]);
		}

		$this->satxmlsv33_cargaAtt($this->root,['NoCertificado' => $data['Certificado']['numero']]);
		$this->satxmlsv33_cargaAtt($this->root,['Certificado' => $this->getCertificado($data)]);

	}
	
	private function getSello($data, $complementos ) {
		$key = ($data['Certificado']['pemKEY'] . "");
		$pkeyid = openssl_get_privatekey(file_get_contents($key));
		$cadena = $this->getCadenaOriginal($complementos);
		//debug($cadena);
		//die;
		openssl_sign($cadena,$crypttext, $pkeyid,OPENSSL_ALGO_SHA256);
		openssl_free_key($pkeyid);
		return base64_encode($crypttext);
	}
	
	private function getCertificado($data) {
		$cer = file_get_contents($data['Certificado']['pemCER'] . "");
        
		$tmp = explode("-----BEGIN CERTIFICATE-----", $cer);
		$tmp = explode("-----END CERTIFICATE-----", $tmp[1])[0];
		return str_replace("\n", '', $tmp);
	}
	
	private function setEmisor($data) {
		$emisor = $this->xml->createElement("cfdi:Emisor");
		$emisor = $this->root->appendChild($emisor);
		$this->satxmlsv33_cargaAtt($emisor,[	
			"Rfc"          => self::cfdiUtf8Decode($data['Emisor']['Rfc']),
		    "Nombre"       => self::cfdiUtf8Decode($data['Emisor']['Nombre']),
		    "RegimenFiscal"=> $data['Emisor']['RegimenFiscal'],
			//"FacAtrAdquirente"=> $data['Emisor']['FacAtrAdquirente']
		]);
	}
	
	private function setGlobal($data) {
		$globalf = $this->xml->createElement("cfdi:InformacionGlobal");
		$globalf = $this->root->appendChild($globalf);
		$this->satxmlsv33_cargaAtt($globalf,[	
			"Periodicidad"     => self::cfdiUtf8Decode($data['global']['Periodicidad']),
		    "Meses"  => self::cfdiUtf8Decode($data['global']['Mes']),
		    "Año" => self::cfdiUtf8Decode($data['global']['Anio']),
		]);
	}
	private function setReceptor($data) {
		$receptor = $this->xml->createElement("cfdi:Receptor");
		$receptor = $this->root->appendChild($receptor);
		$this->satxmlsv33_cargaAtt($receptor,[	
			"Rfc"     => self::cfdiUtf8Decode($data['Receptor']['Rfc']),
		    "Nombre"  => self::cfdiUtf8Decode(strtoupper($data['Receptor']['Nombre'])),
		    "UsoCFDI" => $data['Receptor']['UsoCFDI'],
			"DomicilioFiscalReceptor" => trim($data['Receptor']['DomicilioFiscalReceptor']),
			"RegimenFiscalReceptor" => $data['Receptor']['RegimenFiscalReceptor'],
			"ResidenciaFiscal" => empty($data['Receptor']['ResidenciaFiscal']) ? null : $data['Receptor']['ResidenciaFiscal'],
			"NumRegIdTrib" => empty($data['Receptor']['NumRegIdTrib']) ? null : $data['Receptor']['NumRegIdTrib'],
		]);
	}
	
	function getCadenaOriginal($complementos ) {
		$paso = new DOMDocument("1.0","UTF-8");
		$paso->loadXML($this->getXML($complementos));
		$xsl = new DOMDocument("1.0", "UTF-8");
		
		$ruta = "util40/cadenaoriginal_4_0.xslt";
		
		$xsl->load($ruta);		
		$proc = new XSLTProcessor; 
		@$proc->importStyleSheet($xsl); 
		$cadena_original = $proc->transformToXML($paso);
		return $cadena_original;
	}
	
	private function satxmlsv33_cargaAtt(&$nodo, $attr) {
		
		
		foreach ($attr as $key => $val) {
		
/*
    	for ($i=0;$i<strlen($val); $i++) {
        $a = substr($val,$i,1);
        if ($a > chr(127) && $a !== chr(219) && $a !== chr(211) && $a !== chr(209)) {
					$val = substr_replace($val, ".", $i, 1);
        }
	    }
*/	    
	    //$val = preg_replace('/\s\s+/', ' ', $val);   // Regla 5a y 5c
		
		
	    if($key != "NumPedimento" && $key != "Pedimento" && $key != 'Descripcion'){ 
			$val = preg_replace('/\s\s+/', ' ', $val);   // Regla 5a y 5c
			
		}
		$val = trim($val);                           // Regla 5b
	    
		
		if (strlen($val)>0) {   // Regla 6
		  	//Anterior
		  	//$val = str_replace(array('"','>','<'),"'",$val);  // &...;
			//nuevo para utlizar encoding
			
			if($key != "Nombre" || $key != "Descripcion" || $key  != "Serie"){
			 $val = str_replace("","",$val);  //
			 $val = str_replace("","",$val);  //
			}
			$val = str_replace("Sí","S&iacute;",$val);  //
			//$val = str_replace('"',"'",$val);  // &...;
			$val = str_replace('<',"&lt;",$val);  // &...;
			$val = str_replace('>',"&gt;",$val);  // &...;
			//$val = str_replace('"',"&quot;",$val);  // &...;
			if($key  == "Serie"){
				
				
			
				$nodo->setAttribute($key,$val);
				
			}else{
				$nodo->setAttribute($key, self::cfdiUtf8Encode($val));
			}
			
			
				
//	      $val = self::cfdiUtf8Encode(str_replace("\|","/",$val)); // Regla 1
		/*if(isset($_SESSION['Auth']['User']['emisor_id']) && $_SESSION['Auth']['User']['emisor_id'] == 48577) {
			debug($key);
			debug($val);
		}*/
	      
			}
		}
	}
	
	private function satxmlsv33_cargaAtt_(&$nodo, $attr) {
		
		
		foreach ($attr as $key => $val) {
/*
    	for ($i=0;$i<strlen($val); $i++) {
        $a = substr($val,$i,1);
        if ($a > chr(127) && $a !== chr(219) && $a !== chr(211) && $a !== chr(209)) {
					$val = substr_replace($val, ".", $i, 1);
        }
	    }
*/	    
	    //$val = preg_replace('/\s\s+/', ' ', $val);   // Regla 5a y 5c
	    $val = trim($val);                           // Regla 5b
		 
		 
	    if (strlen($val)>0) {   // Regla 6
		  	//Anterior
			//$val = str_replace(array('"','>','<'),"'",$val);  // &...;
	    	//nuevo para utlizar encoding
			$val = str_replace('"',"'",$val);  // &...;
			$val = str_replace('<',"&lt;",$val);  // &...;
			$val = str_replace('>',"&gt;",$val);  // &...;
//	      $val = self::cfdiUtf8Encode(str_replace("\|","/",$val)); // Regla 1
	      $nodo->setAttribute($key, self::cfdiUtf8Encode($val));
			}
		}
	}
	
	private function satxmlsv33_fix_chr($nomb) {
	    $nomb = str_replace(array(".","/")," ",$nomb);
	    return ($nomb);
	}
	
	public function getXML($complementos ) {
		
		$this->xml->formatOutput = true;
		
		/*$arregloHeaders = ["xmlns:cfdi"  => "http://www.sat.gob.mx/cfd/3",
	    "xmlns:xsi"          => "http://www.w3.org/2001/XMLSchema-instance",
	    "xsi:schemaLocation" => "http://www.sat.gob.mx/cfd/3 http://www.sat.gob.mx/sitio_internet/cfd/3/cfdv33.xsd"];*/
		


		$arregloHeaders = ["xmlns:cfdi"  => "http://www.sat.gob.mx/cfd/4",
	    "xmlns:xsi"          => "http://www.w3.org/2001/XMLSchema-instance",
	    "xsi:schemaLocation" => "http://www.sat.gob.mx/cfd/4 http://www.sat.gob.mx/sitio_internet/cfd/4/cfdv40.xsd"];
		
		if($complementos['INE'] == true){
			$arregloHeaders["xmlns:ine"] = "http://www.sat.gob.mx/ine";
			$arregloHeaders["xsi:schemaLocation"] .= " http://www.sat.gob.mx/ine http://www.sat.gob.mx/sitio_internet/cfd/ine/ine11.xsd";
		}											 
		
		if($complementos['DONATARIAS'] == true){
			
			$arregloHeaders["xmlns:donat"] = "http://www.sat.gob.mx/donat";
			$arregloHeaders["xsi:schemaLocation"] .= " http://www.sat.gob.mx/donat http://www.sat.gob.mx/sitio_internet/cfd/donat/donat11.xsd";

		}
		
		
		if(isset($complementos['PF']) && $complementos['PF'] == true){
			
			$arregloHeaders['xmlns:pfic']  ="http://www.sat.gob.mx/pfic";
			$arregloHeaders["xsi:schemaLocation"] .= " http://www.sat.gob.mx/cfd/3 http://www.sat.gob.mx/sitio_internet/cfd/3/cfdv32.xsd http://www.sat.gob.mx/pfic http://www.sat.gob.mx/sitio_internet/cfd/pfic/pfic.xsd";
		}
		
		if($complementos['IEDU'] == true){
			
			$arregloHeaders['xmlns:iedu']  ="http://www.sat.gob.mx/iedu";
			$arregloHeaders["xsi:schemaLocation"] .= " http://www.sat.gob.mx/iedu http://www.sat.gob.mx/sitio_internet/cfd/iedu/iedu.xsd";
		}
		
		if($complementos['CONSTRUCCION'] == true){
			
			$arregloHeaders['xmlns:servicioparcial']="http://www.sat.gob.mx/servicioparcialconstruccion" ;
			$arregloHeaders["xsi:schemaLocation"] .= " http://www.sat.gob.mx/servicioparcialconstruccion http://www.sat.gob.mx/sitio_internet/cfd/servicioparcialconstruccion/servicioparcialconstruccion.xsd";
		}
		
		if($complementos['LEYENDAS'] == true){
			 
			$arregloHeaders['xmlns:leyendasFisc']="http://www.sat.gob.mx/leyendasFiscales" ;
			$arregloHeaders["xsi:schemaLocation"] .= " http://www.sat.gob.mx/leyendasFiscales http://www.sat.gob.mx/sitio_internet/cfd/leyendasFiscales/leyendasFisc.xsd";

		}
		
		if($complementos['NOTARIOS'] == true){
			
			$arregloHeaders['xmlns:notariospublicos']="http://www.sat.gob.mx/notariospublicos" ;
			$arregloHeaders["xsi:schemaLocation"] .= " http://www.sat.gob.mx/notariospublicos http://www.sat.gob.mx/sitio_internet/cfd/notariospublicos/notariospublicos.xsd";

		}
		
		if(!empty($complementos['VEHICULO']) && $complementos['VEHICULO'] == true){
			
			$arregloHeaders['xmlns:vehiculousado'] = "http://www.sat.gob.mx/vehiculousado";
			$arregloHeaders["xsi:schemaLocation"]  .= " http://www.sat.gob.mx/vehiculousado http://www.sat.gob.mx/sitio_internet/cfd/vehiculousado/vehiculousado.xsd";

		}
		
		if(!empty($complementos['DETALLISTA']) && $complementos['DETALLISTA'] == true){
			$arregloHeaders['xmlns:detallista'] = "http://www.sat.gob.mx/detallista ";
			$arregloHeaders["xsi:schemaLocation"]  .= " http://www.sat.gob.mx/detallista  http://www.sat.gob.mx/sitio_internet/cfd/detallista/detallista.xsd ";
		}
		
		if(!empty($complementos['COMERCIO']) && $complementos['COMERCIO'] == true){
			//$arregloHeaders['xmlns:cce11'] = "http://www.sat.gob.mx/ComercioExterior11";
			//$arregloHeaders["xsi:schemaLocation"]  .= " http://www.sat.gob.mx/ComercioExterior11 http://www.sat.gob.mx/sitio_internet/cfd/ComercioExterior11/ComercioExterior11.xsd ";
			
			$arregloHeaders['xmlns:cce20'] = "http://www.sat.gob.mx/ComercioExterior20";
			$arregloHeaders["xsi:schemaLocation"]  .= "  http://www.sat.gob.mx/ComercioExterior20  http://www.sat.gob.mx/sitio_internet/cfd/ComercioExterior20/ComercioExterior20.xsd";
		}
		
		if(!empty($complementos['AEROLINEAS']) && $complementos['AEROLINEAS'] == true){
			$arregloHeaders['xmlns:aerolineas'] = "http://www.sat.gob.mx/aerolineas ";
			$arregloHeaders["xsi:schemaLocation"]  .= " http://www.sat.gob.mx/aerolineas  http://www.sat.gob.mx/sitio_internet/cfd/aerolineas/aerolineas.xsd ";
		}
		
		if(!empty($complementos['VEHICULOVENTA']) && $complementos['VEHICULOVENTA'] == true){
			$arregloHeaders['xmlns:ventavehiculos'] = "http://www.sat.gob.mx/ventavehiculos ";
			$arregloHeaders["xsi:schemaLocation"]  .= " http://www.sat.gob.mx/ventavehiculos 
				http://www.sat.gob.mx/sitio_internet/cfd/ventavehiculos/ventavehiculos11.xsd ";
		}
		if ($complementos['NOMINA']!= 'NOMINA') {
			if($complementos['LOCLES'] == true){
				$arregloHeaders['xmlns:implocal'] = "http://www.sat.gob.mx/implocal ";
				$arregloHeaders["xsi:schemaLocation"]  .= " http://www.sat.gob.mx/implocal http://www.sat.gob.mx/sitio_internet/cfd/implocal/implocal.xsd ";
			}
		}
		
		
		if($complementos['NOMINA'] == true){
			$arregloHeaders['xmlns:nomina12'] = "http://www.sat.gob.mx/nomina12";
			$arregloHeaders["xsi:schemaLocation"]  .= "  http://www.sat.gob.mx/nomina12 ";
			$arregloHeaders["xsi:schemaLocation"]  .= " http://www.sat.gob.mx/sitio_internet/cfd/nomina/nomina12.xsd ";
		}
		

		
		if($complementos['TERCEROS'] == true){
			$arregloHeaders['xmlns:terceros'] = "http://www.sat.gob.mx/terceros";
			$arregloHeaders["xsi:schemaLocation"]  .= " http://www.sat.gob.mx/terceros http://www.sat.gob.mx/sitio_internet/cfd/terceros/terceros11.xsd ";
			
		}
		
		if($complementos['DIVISAS'] == true){
			$arregloHeaders['xmlns:divisas'] = "http://www.sat.gob.mx/divisas";
			$arregloHeaders["xsi:schemaLocation"]  .= "  http://www.sat.gob.mx/divisas http://www.sat.gob.mx/sitio_internet/cfd/divisas/divisas.xsd ";
			
		}
		
		if(isset($complementos['CARTAPORTE']) && $complementos['CARTAPORTE'] == true){
			$arregloHeaders['xmlns:cartaporte20'] = "http://www.sat.gob.mx/CartaPorte20";
			$arregloHeaders["xsi:schemaLocation"]  .= "  http://www.sat.gob.mx/CartaPorte20 http://www.sat.gob.mx/sitio_internet/cfd/CartaPorte/CartaPorte20.xsd";
			
		}
		if(isset($complementos['CARTAPORTE']) && $complementos['CARTAPORTE30'] == true){
			$arregloHeaders['xmlns:cartaporte30'] = "http://www.sat.gob.mx/CartaPorte30";
			$arregloHeaders["xsi:schemaLocation"]  .= "  http://www.sat.gob.mx/CartaPorte30  http://www.sat.gob.mx/sitio_internet/cfd/CartaPorte/CartaPorte30.xsd";
			
		}
		
		if(isset($complementos['CARTAPORTE31']) && $complementos['CARTAPORTE31'] == true){
			$arregloHeaders['xmlns:cartaporte31'] = "http://www.sat.gob.mx/CartaPorte31";
			$arregloHeaders["xsi:schemaLocation"]  .= "  http://www.sat.gob.mx/CartaPorte31  http://www.sat.gob.mx/sitio_internet/cfd/CartaPorte/CartaPorte31.xsd";
			
		}
		
      
		$this->satxmlsv33_cargaAtt($this->root, $arregloHeaders);
		return $this->xml->saveXML();
	}
	
	
	public function getXMLSellado($data, $complementos = null) {
	    $complemento = new Complementos();
	    $complementos = $complemento->setComplemento($data); 
		$sello = $this->getSello($data, $complementos );
		$this->satxmlsv33_cargaAtt($this->root,['Sello' => $sello]);		
		return $this->xml->saveXML();
	}

	private function setNomina($data,$complemento) {
		
		$xslt = "";
		
			$nomina = $this->xml->createElement("nomina12:Nomina");
		//$complemento = $this->xml->createElement("cfdi:Complemento");
		$complemento = $this->root->appendChild($complemento);
		$nomina = $complemento->appendChild($nomina);

		$dataTotales = [
			"Version"            => '1.2',
			"TipoNomina"       	 => $data['Nomina']['tipoNomina'],
			"FechaPago"			 => $data['Nomina']['fechaPago'],
			"FechaInicialPago"	 => $data['Nomina']['fechaInicio'],
			"FechaFinalPago"	 => $data['Nomina']['fechaFin'],
			"NumDiasPagados"	 => (strpos($data['Nomina']['diasPagados'], '.') > 0) ? sprintf('%.03f', $data['Nomina']['diasPagados']) : $data['Nomina']['diasPagados'],
			"TotalPercepciones"  => $data['Nomina']['percepciones'],
			"TotalDeducciones"   => $data['Nomina']['deducciones'],
			"TotalOtrosPagos"	 => $data['Nomina']['tOtrosPagos'],
			"xsi:schemaLocation" =>	$xslt
		];

		/*if ($data['Nomina']['tOtrosPagos'] <= 0) {
			unset($dataTotales['TotalOtrosPagos']);
		}*/
		/*$bn02 = "";
		if($data['Empleado']['tipoRegimen'] == "02"){
			$bn02 = true;
		}
		
		$otroArr=json_decode($data['OtrosPagos']);
		*/
		$bn02 = "";
		if($data['Empleado']['tipoRegimen'] == "02"){
			$bn02 = true;
		}
		
		$otroArr=json_decode($data['OtrosPagos']);
		
		$otroArr=json_decode($data['OtrosPagos']);
		if (($data['Nomina']['tOtrosPagos'] < 0 && count($otroArr) == 0)) {
			unset($dataTotales['TotalOtrosPagos']);
		}

		if ($data['Nomina']['deducciones'] <= 0) {
			unset($dataTotales['TotalDeducciones']);
		}

		if($data['Nomina']['percepciones'] <= 0){
			unset($dataTotales['TotalPercepciones']);
		}
		

		$this->satxmlsv33_cargaAtt($nomina, $dataTotales);
		return $nomina;
	}

	public function setNominaEmisor($data, $rootNomina) {
		$emisor = $this->xml->createElement("nomina12:Emisor");
		$emisor = $rootNomina->appendChild($emisor);
		
		$data1 = [	
			"RegistroPatronal" => $data['Nomina']['registroPatronal'],
			"Curp"			       => $data['Nomina']['Curp']
		];
		
		
		if (strlen($data['Emisor']['Rfc']) == 12) {
			unset($data1['Curp']);
		}
        
		if($data['Nomina']['registroPatronal'] == ''){
			unset($data1['RegistroPatronal']);
		}
		if($data['Empleado']['tipoContrato'] > 8){
			unset($data1['RegistroPatronal']);
		}
		
		$this->satxmlsv33_cargaAtt($emisor, $data1);

		if($data['SNCF']['entidadSNCF'] == 1){
		$EntidadSNCF = $this->xml->createElement("nomina12:EntidadSNCF");
		$EntidadSNCF = $emisor->appendChild($EntidadSNCF);

		$datosEntidad = [	
			 "OrigenRecurso" 	=> $data['SNCF']['origenRecurso'],
			 "MontoRecursoPropio" => $data['SNCF']['montoRecursoPropio']
			
			];
		if( $data['SNCF']['origenRecurso'] =='IF' ||  $data['SNCF']['origenRecurso'] == 'IP'){
			unset($datosEntidad['MontoRecursoPropio']);
		}

		$this->satxmlsv33_cargaAtt($EntidadSNCF,$datosEntidad);
		}

		

	}

	private function datediffInWeeks($date1, $date2) {
		if($date1 > $date2) return $this->datediffInWeeks($date2, $date1);
		$first = new \DateTime($date1);
		$second = new \DateTime($date2);
		return floor($first->diff($second)->days/7);
	}

	public function setNominaReceptor($data, $rootNomina){
		$receptor = $this->xml->createElement("nomina12:Receptor");
		$receptor = $rootNomina->appendChild($receptor);
		
		$f2 = ($data['Empleado']['fechaInicioRelLaboral']);
		$f1 = ($data['Nomina']['fechaFin']);
		//$f1 = ($data['Nomina']['fechaPago']);
		
		
		$a = new \DateTime($f1);
		$b = new \DateTime($f2);
		$diff = $this->datediffInWeeks($f1, $f2);

		
		$antiguedad = "P$diff" . "W";
/*		
		$antiguedad .= $diff->y > 0 ? $diff->y . "Y" : "";
		$antiguedad .= $diff->m > 0 ? $diff->m . "M" : "";
		$antiguedad .= $diff->d > 0 ? $diff->d . "D" : "";
*/	
		if(!empty($data['Empleado']['sindicalizado'])){
			if(($data['Empleado']['sindicalizado'] == "Si") || ($data['Empleado']['sindicalizado'] == "SI")){
				$data['Empleado']['sindicalizado']="Sí";
			}else{
				$data['Empleado']['sindicalizado']="No";
			}	
		}
		$datosReceptor=[
			"Curp"          		=> $data['Empleado']['curp'],
			"NumSeguridadSocial"	=> $data['Empleado']['nss'],
			"TipoContrato"      	=> $data['Empleado']['tipoContrato'],
			"Departamento"			=> !empty($data['Empleado']['departamento'])?self::cfdiUtf8Decode($data['Empleado']['departamento']):'',
			"TipoRegimen"			=> $data['Empleado']['tipoRegimen'],
			"NumEmpleado"			=> $data['Empleado']['numeroEmpleado'],
			"TipoJornada"			=> !empty($data['Empleado']['tipoJornada'])?self::cfdiUtf8Decode($data['Empleado']['tipoJornada']):'',
			"Sindicalizado"			=> !empty($data['Empleado']['sindicalizado'])?self::cfdiUtf8Decode($data['Empleado']['sindicalizado']):'',
			"PeriodicidadPago"		=> !empty($data['Empleado']['periodicidadPago'])?$data['Empleado']['periodicidadPago']:'',
			"ClaveEntFed"			=> $data['Empleado']['claveEntFed'],			
			"Banco"					=> !empty($data['Empleado']['banco'])?$data['Empleado']['banco']:"",
			"CuentaBancaria"		=> !empty($data['Empleado']['cuentaBancaria'])?$data['Empleado']['cuentaBancaria']:"",
			"FechaInicioRelLaboral"	=> date('Y-m-d', strtotime($data['Empleado']['fechaInicioRelLaboral'])),
			"Antigüedad"			=> $antiguedad,
			"Puesto"				=> self::cfdiUtf8Decode($data['Empleado']['puesto']),
			"SalarioBaseCotApor"	=> $data['Empleado']['salarioBaseCotApor'],
			"RiesgoPuesto"			=> $data['Empleado']['riesgoPuesto'],
			"SalarioDiarioIntegrado"=> $data['Empleado']['salarioDiarioIntegrado'],

		];
		if ($data['Empleado']['cuentaBancaria'] == '') {
				unset($datosReceptor['CuentaBancaria']);
		}
		if ($data['Empleado']['departamento'] == '') {
				unset($datosReceptor['Departamento']);
		}
		if ($data['Empleado']['tipoJornada'] == 0) {
				unset($datosReceptor['TipoJornada']);
		}
		if($data['Empleado']['tipoContrato'] > 8){
			unset($datosReceptor['NumSeguridadSocial']);
			unset($datosReceptor['FechaInicioRelLaboral']);
			unset($datosReceptor['Antigüedad']);
			unset($datosReceptor['RiesgoPuesto']);
			unset($datosReceptor['SalarioDiarioIntegrado']);
		}
		if($data['Empleado']['tipoRegimen'] == '5' || $data['Empleado']['tipoRegimen'] == '10' ||$data['Empleado']['tipoRegimen'] == '11' ){
			unset($datosReceptor['SalarioBaseCotApor']);
		}
		$this->satxmlsv33_cargaAtt($receptor,$datosReceptor);
		if($data['SubContratacion']['aplicaSub'] == 1){
		$contratadosArr=json_decode($data['Contratados']);
		foreach ($contratadosArr as $ctds) {
		$ctds=(array)$ctds;
		$subcontratados = $this->xml->createElement("nomina12:SubContratacion");
		$subcontratados = $receptor->appendChild($subcontratados);

		$this->satxmlsv33_cargaAtt($subcontratados,[	
			 "RfcLabora" 			=> $ctds['rfclabora'],
			 "PorcentajeTiempo" 	=> $ctds['porcentaje'],
			
			]);
		} 
		}
	} 
	public function setNominaPercepcion($data, $rootNomina) {
		$separacionB = 0;
		$indemnizacionB = 0;
		$percepciones = $this->xml->createElement("nomina12:Percepciones");
		$percepciones = $rootNomina->appendChild($percepciones);
		$percepcionTotales = [
			"TotalSueldos"	                => $data['Nomina']['TotalSaldos'],
			"TotalSeparacionIndemnizacion"  => $data['Nomina']['tSeparacionIndemnizacion'],
			"TotalJubilacionPensionRetiro"	=> $data['Nomina']['tJubilacionPensionRetiro'],
			"TotalGravado"					=> $data['Nomina']['tPercepcionGravado'],
			"TotalExento"					=> $data['Nomina']['tPercepcionExento'],
		];
		if ($data['Nomina']['tSeparacionIndemnizacion'] <= 0) {
			unset($percepcionTotales['TotalSeparacionIndemnizacion']);
		}
		if ($data['Nomina']['tJubilacionPensionRetiro'] <= 0) {
			unset($percepcionTotales['TotalJubilacionPensionRetiro']);
		}
		$this->satxmlsv33_cargaAtt($percepciones , $percepcionTotales);
		$percepcionArr=json_decode($data['Percepcion']);
		if (count($percepcionArr) == 0) {
			$percepciones = $rootNomina->removeChild ($percepciones);
			return;
		}
		$posicion044= 0;
		foreach ($percepcionArr as $pcn) {
		$pcn=(array)$pcn;
		if($pcn['clave']== "044"){ 
			$posicion044++;
		}
		}
		$posicion044_b = 0;
		$indemnizacionM = 0;
		foreach ($percepcionArr as $pcn) {
			$pcn=(array)$pcn;
			$claveinternaP = (empty($pcn['interna'])) ? $pcn['clave'] : $pcn['interna'];
			if($pcn['clave'] == "044"){
				$posicion044_b++;
			}
			$percepcion = $this->xml->createElement("nomina12:Percepcion");
			$percepcion = $percepciones->appendChild($percepcion);
			$this->satxmlsv33_cargaAtt($percepcion,[	
			 "TipoPercepcion" 	=> $pcn['clave'],
			 "Clave"		  	=> $claveinternaP,
			 "Concepto"			=> self::cfdiUtf8Decode($pcn['concepto']),
			 "ImporteGravado"	=> $pcn['gravado'],
			 "ImporteExento"	=> $pcn['exento'],
			]);
			
			if($pcn['clave'] == '045'){
		
					$acciones = $this->xml->createElement("nomina12:AccionesOTitulos");
					$acciones = $percepcion->appendChild($acciones);
					$this->satxmlsv33_cargaAtt($acciones,[
						
						"ValorMercado" 	=> $data['acciones']['ValorMercado'],
						"PrecioAlOtorgarse" 	=> $data['acciones']['PrecioAlOtorgarse'],
						]);
				
		}
			
			
			
			if($pcn['clave'] == '019'){
					$horasArr=json_decode($data['HorasExtras']);
					foreach ($horasArr as $hra) {
					$hra=(array)$hra;	
					$horasExtra = $this->xml->createElement("nomina12:HorasExtra");
					$horasExtra = $percepcion->appendChild($horasExtra);
					$this->satxmlsv33_cargaAtt($horasExtra,[
						
						"Dias" 	=> $hra['dias'],
						"TipoHoras"		=> $hra['clave'],
						"HorasExtra"	=> $hra['horas'],
						"ImportePagado"	=> $hra['importe'],
						]);
				}
			}
			if($pcn['clave'] == '039' || $pcn['clave'] == '044' AND $separacionB == 0  && $posicion044_b == $posicion044 ){
				$separacionB ++;
				
				    $pension = $this->xml->createElement("nomina12:JubilacionPensionRetiro");
					$pension = $percepciones->appendChild($pension);
					$datosPension = [
						"TotalUnaExhibicion" 	=> $data['Jubilacion']['TotalUnaExhibicionPension'],
						"TotalParcialidad"		=> $data['Jubilacion']['TotalParcialidadPension'],
						"MontoDiario"			=> $data['Jubilacion']['MontoDiarioPension'],
						"IngresoAcumulable"		=> $data['Jubilacion']['IngresoAcumulable'],
						"IngresoNoAcumulable"	=> $data['Jubilacion']['IngresoNoAcumulable'],
					];

					if ($data['Jubilacion']['TotalUnaExhibicionPension'] > 0 ) {
						unset($datosPension['TotalParcialidad']);
						unset($datosPension['MontoDiario']);
					}else if ($data['Jubilacion']['TotalParcialidadPension'] > 0) {
						unset($datosPension['TotalUnaExhibicion']);
					}


					$this->satxmlsv33_cargaAtt($pension,$datosPension);
			}
			if ($pcn['clave'] == '022' || $pcn['clave'] == '023' || $pcn['clave'] == '025') {
				$indemnizacionM++;
			}
		}
		if($indemnizacionM > 0 ){
			$indemnizacion = $this->xml->createElement("nomina12:SeparacionIndemnizacion");
			$indemnizacion = $percepciones->appendChild($indemnizacion);
			$this->satxmlsv33_cargaAtt($indemnizacion,[
				"TotalPagado" 			=> $data['Indemnizacion']['TotalPagado'],
				"NumAñosServicio"		=> $data['Indemnizacion']['NumAnosServicio'],
				"UltimoSueldoMensOrd"	=> $data['Indemnizacion']['UltimoSueldoMensOrd'],
				"IngresoAcumulable"		=> $data['Indemnizacion']['IngresoAcumulable'],
				"IngresoNoAcumulable"   => $data['Indemnizacion']['IngresoNoAcumulable'],
				]);
		}
	}
	

	public function setNominaDeduccion($data, $rootNomina) {
		
		$deduccionArr=json_decode($data['Deduccion']);
		if (count($deduccionArr) == 0) {
			return;
		}
		
		$deducciones = $this->xml->createElement("nomina12:Deducciones");
		$deducciones = $rootNomina->appendChild($deducciones);
		$dataDeduciones = [
		 "TotalOtrasDeducciones"  	=> $data['Nomina']['totalDeducciones'],
		 "TotalImpuestosRetenidos"	=> $data['Nomina']['totalDeduccionesRetenidas'],
		];

		if($data['Nomina']['totalDeduccionesRetenidas'] <= 0){
			unset($dataDeduciones['TotalImpuestosRetenidos']);
		}

		if($data['Nomina']['totalDeducciones'] <= 0){
			unset($dataDeduciones['TotalOtrasDeducciones']);
		}

		$this->satxmlsv33_cargaAtt($deducciones, $dataDeduciones);
			
		
		foreach($deduccionArr as $dcn){
		$dcn=(array)$dcn;
		$claveinterna = (empty($dcn['interna'])) ? $dcn['clave'] : $dcn['interna'];
		$deduccion = $this->xml->createElement("nomina12:Deduccion");
		$deduccion = $deducciones->appendChild($deduccion);
		$this->satxmlsv33_cargaAtt($deduccion,[	
			 "TipoDeduccion" 	=> $dcn['clave'],
			 "Clave"		  	=> $claveinterna,
			 "Concepto"			=> self::cfdiUtf8Decode($dcn['concepto']),
			 "Importe"			=> $dcn['importe'],
			
		]);
		}
	}


	public function setNominaOtrosPagos($data, $rootNomina) {
		$otroArr=json_decode($data['OtrosPagos']);

		if (count($otroArr) == 0) {
			return;
		}

		$otrospagos = $this->xml->createElement("nomina12:OtrosPagos");
		$otrospagos = $rootNomina->appendChild($otrospagos);
		$this->satxmlsv33_cargaAtt($otrospagos,[	
		]);


		foreach($otroArr as $otp){
		$otp = (array)$otp;
		$claveinternaO = (empty($otp['interna'])) ? $otp['clave'] : $otp['interna'];
		$otropago = $this->xml->createElement("nomina12:OtroPago");
		$otropago = $otrospagos->appendChild($otropago);
		$this->satxmlsv33_cargaAtt($otropago,[	
			 "TipoOtroPago" 	=> $otp['clave'],
			 "Clave"		  	=> $claveinternaO,
			 "Concepto"			=> self::cfdiUtf8Decode(substr($otp['concepto'], 0, 99)),
			 "Importe"			=> $otp['importe'],

			
		]);
		if($otp['clave'] == '002'){
		
					$subsidio = $this->xml->createElement("nomina12:SubsidioAlEmpleo");
					$subsidio = $otropago->appendChild($subsidio);
					$this->satxmlsv33_cargaAtt($subsidio,[
						
						"SubsidioCausado" 	=> $data['SUBSIDIO']['SubsidioCausado'],
						]);
				
		}
		if($otp['clave'] == '004'){
		
					$subsidio = $this->xml->createElement("nomina12:CompensacionSaldosAFavor");
					$subsidio = $otropago->appendChild($subsidio);
					$this->satxmlsv33_cargaAtt($subsidio,[						
						"SaldoAFavor" 		=> $data['Compensacion']['SaldoAFavor'],
						"Año" 				=> $data['Compensacion']['anio'],
						"RemanenteSalFav" 	=> $data['Compensacion']['RemanenteSalFav'],
						]);
				
		}


		}
	}

	public function setNominaIncapacidad($data, $rootNomina){
		$incapacidadArr=json_decode($data['Incapacidad']);
		
		if (count($incapacidadArr) == 0) {
			return;
		}

		$incapacidades = $this->xml->createElement("nomina12:Incapacidades");
		$incapacidades = $rootNomina->appendChild($incapacidades);
		$this->satxmlsv33_cargaAtt($incapacidades,[	
		]);
		$incapacidadArr=json_decode($data['Incapacidad']);
		foreach($incapacidadArr as $otp){
		$otp = (array)$otp;
		$incapacidad = $this->xml->createElement("nomina12:Incapacidad");
		$incapacidad = $incapacidades->appendChild($incapacidad);
		$this->satxmlsv33_cargaAtt($incapacidad,[	
			 "DiasIncapacidad" 	=> $otp['dias'],
			 "TipoIncapacidad"	=> $otp['clave'],
			 "ImporteMonetario"	=> $otp['importe'],
			 
		]);
		}
	}

	
	private function setINE($data,$complemento) {		
		$ine = $this->xml->createElement("ine:INE");
		//$complemento = $this->xml->createElement("cfdi:Complemento");
		$complemento = $this->root->appendChild($complemento);
		$ine = $complemento->appendChild($ine);

		$complementoINE = [
			"Version"			=>	"1.1" ,
			"TipoProceso"		=>	self::cfdiUtf8Decode($data['INE']['TipoProceso']),
			"TipoComite"		=>  $data['INE']['TipoComite'], 
			"IdContabilidad"    =>  $data['INE']['IdContabilidad'] ,
			];

		$entidadINE = [			
			"ClaveEntidad" => $data['INE']['Entidad']['ClaveEntidad'],
			"Ambito"	   => $data['INE']['Entidad']['Ambito'],
		];

		$contabilidadINE = [
			"IdContabilidad" => $data['INE']['Entidad']['IdContabilidad'],
		];

		
		if($data['INE']['TipoProceso'] != 'Ordinario'){
		/**Campaña o precampaña**/
		unset($complementoINE['TipoComite']); /**quitamos TipoComite**/
		unset($complementoINE['IdContabilidad']); /**quitamos IdContabilidad**/
		$this->satxmlsv33_cargaAtt($ine, $complementoINE);
		$entidad = $this->xml->createElement("ine:Entidad"); /*creamos nodo entidad de Complemento INE*/
		$entidad = $ine->appendChild($entidad);
		$this->satxmlsv33_cargaAtt($entidad, $entidadINE);
		
		$contabilidad = $this->xml->createElement("ine:Contabilidad"); /*creamos nodo Contabilidad de Complemento INE*/
		$contabilidad = $entidad->appendChild($contabilidad);
		$this->satxmlsv33_cargaAtt($contabilidad, $contabilidadINE);
		
		}else {
			/***Ordinario **/
			if ($data['INE']['TipoComite'] != 'Ejecutivo Nacional') { /*Si el tipo de comprobante es diferente de Ejecutivo Nacional*/
				unset($entidadINE['Ambito']);  /*eliminamos Ambito de Nodo Entidad*/
				unset($complementoINE['IdContabilidad']); /*Eliminamos IdContabilidad del Nodo padre*/
				$this->satxmlsv33_cargaAtt($ine, $complementoINE); /*se manda a armar*/


				$entidad = $this->xml->createElement("ine:Entidad"); /* se crea el nodo entidad*/
				$entidad = $ine->appendChild($entidad);
				$this->satxmlsv33_cargaAtt($entidad, $entidadINE);/*se manda a armar*/

				$contabilidad = $this->xml->createElement("ine:Contabilidad");/*se manda a armar el nodo Contabilidad*/
				$contabilidad = $entidad->appendChild($contabilidad);
				$this->satxmlsv33_cargaAtt($contabilidad, $contabilidadINE);
					

			}else{
				/* si es Ejecutivo Nacional */

				$this->satxmlsv33_cargaAtt($ine, $complementoINE); /* mostrara solo el nodo ine con Tipo de proceso /Tipo de comité / Id Contabilidad */
				
			}
	  }
  }

  private function setDonatarias($data,$complemento) {
  
		//$complemento = $this->xml->createElement("cfdi:Complemento");
		$complemento = $this->root->appendChild($complemento);
		

		$complementoDonatarias = [
			"xmlns:donat"		=>	"http://www.sat.gob.mx/donat",
			"version"			=>	"1.1" ,
			"noAutorizacion"	=>	$data['Donat']['noAutorizacion'],
			"fechaAutorizacion"	=>  $data['Donat']['fechaAutorizacion'], 
			"leyenda"   		=>  self::cfdiUtf8Decode($data['Donat']['Leyenda']),
		];

		$donat = $this->xml->createElement("donat:Donatarias"); /*creamos nodo Contabilidad de Complemento INE*/
		$donat = $complemento->appendChild($donat);
		$this->satxmlsv33_cargaAtt($donat, $complementoDonatarias);
	
  }
  
  private function setPF($data , $complemento){
	  //$complemento = $this->xml->createElement("cfdi:Complemento");
		$complemento = $this->root->appendChild($complemento);
		

		$complementoPF = [
			"version"			=>	"1.0" ,
			"ClaveVehicular"	=>	$data['PF']['claveVehicular'],
			"Placa"	=>  $data['PF']['placa'], 
			"RFCPF"   		=>  $data['PF']['rfc'] ,
		];
		if(empty($data['PF']['rfc'])){
		unset($complementoPF['RFCPF']);
		}

		$pf = $this->xml->createElement("pfic:PFintegranteCoordinado"); /*creamos nodo Contabilidad de Complemento INE*/
		$pf = $complemento->appendChild($pf);
		$this->satxmlsv33_cargaAtt($pf, $complementoPF);
	
  }
  
  private function setContruccion($data,$complemento){
		//$complemento = $this->xml->createElement("cfdi:Complemento");
		
		
		
		$lista_nodos = json_decode($data['complemento']['info']['nodos'], true) ?? []; 
		
		foreach($lista_nodos as $ln){
		
		$complemento = $this->root->appendChild($complemento);
	  
		$construccion = $this->xml->createElement("servicioparcial:parcialesconstruccion");
		$construccion = $complemento->appendChild($construccion);
		 
		$datosContruccion= [			
			"Version"			=>	"1.0" ,
			"NumPerLicoAut"     =>  $ln['numero_permiso'],
		
		];
		
		$this->satxmlsv33_cargaAtt($construccion,$datosContruccion);
		
		$inmueble = $this->xml->createElement("servicioparcial:Inmueble");
		$inmueble = $construccion->appendChild($inmueble);
		
			 $datosInmueble=[	
			 "Estado" 		=> $ln['estado'],
			 "CodigoPostal"	=> $ln['codigo_postal'],
			 "Municipio"	=> $ln['municipio'],
			 "Calle"		=> $ln['calle'],
			 "Colonia"  	=> $ln['colonia'],
			 "Localidad"	=> $ln['localidad'],
			 "NoExterior"	=> $ln['num_exterior'],
			 "NoInterior"	=> $ln['num_interior'],
			 "Referencia"   =>$ln['referencia'],
			 ];
			 
			 
			 
			 if($ln['referencia']==''){
				unset($datosInmueble['Referencia']);
			 }
			  if($ln['num_interior'] ==''){
				unset($datosInmueble['NoInterior']);
			 }
			  if($ln['num_exterior']==''){
				unset($datosInmueble['NoExterior']);
			 }
			  if($ln['localidad']==''){
				unset($datosInmueble['Localidad']);
			 }
			  if($ln['localidad'] ==''){
				unset($datosInmueble['Colonia']);
			 }
			  if( $ln['calle']==''){
				unset($datosInmueble['Calle']);
			 }
			  if( $ln['municipio'] ==''){
				unset($datosInmueble['Municipio']);
			 }
			 
			 $this->satxmlsv33_cargaAtt($inmueble,$datosInmueble);
			 
		}
  }
  
  private function setLeyendas($data,$complemento){
		//$complemento = $this->xml->createElement("cfdi:Complemento");
		$complemento = $this->root->appendChild($complemento);
		$leyends=json_decode($data['json']['leyendas']);
		
		if (count($leyends) == 0) {
			return;
		}
		
		$leyendas = $this->xml->createElement("leyendasFisc:LeyendasFiscales");
		$leyendas = $complemento->appendChild($leyendas);
		 
		$datosLeyenda= [			
			"version"			=>	"1.0" ,
		];
		
		$this->satxmlsv33_cargaAtt($leyendas,$datosLeyenda);
		
		foreach($leyends as $leyendsa){
		$leyendsa = (array)$leyendsa;
		$leyenda = $this->xml->createElement("leyendasFisc:Leyenda");
		$leyenda = $leyendas->appendChild($leyenda);
		
			 $datosLey=[	
			 "disposicionFiscal" => trim($leyendsa['disposicion']),
			 "norma"			 => trim($leyendsa['norma']),
			 "textoLeyenda"		 => trim($leyendsa['texto']),
			 ];
			 
			 if($leyendsa['disposicion'] ==''){
				unset($datosLey['disposicionFiscal']);
			 }
			  if($leyendsa['norma'] ==''){
				unset($datosLey['norma']);
			 }
			  
			 
			 $this->satxmlsv33_cargaAtt($leyenda,$datosLey);
		}	 
			 
  }
  
  function setNotarios($data,$complemento){
	  //$complemento = $this->xml->createElement("cfdi:Complemento");
	  $complemento = $this->root->appendChild($complemento);
	  
	  $notarios = $this->xml->createElement("notariospublicos:NotariosPublicos");
	  $notarios = $complemento->appendChild($notarios);
		 
		$datosNotarios= [			
			"Version"			=>	"1.0" ,
		];
		
		$this->satxmlsv33_cargaAtt($notarios,$datosNotarios);
		
		$inmuebles = $this->xml->createElement("notariospublicos:DescInmuebles");
		$inmuebles = $notarios->appendChild($inmuebles);
		$this->satxmlsv33_cargaAtt($inmuebles,[]);
		//debug($data['notarios']);
		$inmueblesa = json_decode($data['notarios']['inmuebles']);

		/*echo "<pre>";
		var_dump($data);
		echo "<pre>";*/
		
		foreach($inmueblesa as $inmueble){
			$inmuebl = $this->xml->createElement("notariospublicos:DescInmueble");
			$inmuebl = $inmuebles->appendChild($inmuebl);
			
			$inmueble = (array) $inmueble;
			$datoInmueble=[	
				"TipoInmueble" => $inmueble['tipoInmueble'],
				"Calle"		=> self::cfdiUtf8Decode($inmueble['calle']),
				"NoExterior"	=> $inmueble['numeroExt'],
				"NoInterior" 	=> $inmueble['numeroInt'],
				"Colonia"		=> self::cfdiUtf8Decode($inmueble['colonia']),
				"Localidad"	=> $inmueble['localidad'],
				"Referencia" 	=> self::cfdiUtf8Decode($inmueble['referencia']),
				"Municipio"	=> $inmueble['municipio'],
				"Estado"		=> $inmueble['estado'],
				"Pais"		 	=> $inmueble['pais'],
				"CodigoPostal"	=> $inmueble['codigoPostal']
			 ];
			 
			 if($inmueble['numeroExt'] ==''){
				unset($datoInmueble['NoExterior']);
			 }
			 if($inmueble['numeroInt'] ==''){
				unset($datoInmueble['NoInterior']);
			 }			
			 if($inmueble['colonia'] ==''){
				unset($datoInmueble['Colonia']);
			 }
			 if($inmueble['localidad'] ==''){
				unset($datoInmueble['Localidad']);
			 }
			 if($inmueble['referencia'] ==''){
				unset($datoInmueble['Referencia']);
			 }
			 
			 if($inmueble['pais']=='' || !isset($inmueble['pais'])){
				 $datoInmueble['Pais']='MEX';
			 }
			  
			 
			 $this->satxmlsv33_cargaAtt($inmuebl,$datoInmueble);
			$datoInmueble = [];
			
			
		}
		
		$operacion = $this->xml->createElement("notariospublicos:DatosOperacion");
		$operacion = $notarios->appendChild($operacion);
        $datosOperacion =[
			"NumInstrumentoNotarial" => $data['notarios']['NumInstrumentoNotarial'],
			"FechaInstNotarial"		 => $data['notarios']['FechaInstNotarial'],
			"MontoOperacion"		 => $data['notarios']['MontoOperacion'],
			"Subtotal" 		 		 => $data['notarios']['Subtotal'],
			"IVA"					 => $data['notarios']['IVA'],		
		];
		
		$this->satxmlsv33_cargaAtt($operacion,$datosOperacion);
		$notari = $this->xml->createElement("notariospublicos:DatosNotario");
		$notari = $notarios->appendChild($notari);
        $datosOperacion =[
			"CURP" 				 => $data['notarios']['CURP'],
			"NumNotaria"		 => $data['notarios']['NumNotaria'],
			"EntidadFederativa"	 => $data['notarios']['EntidadFederativa'],
			"Adscripcion" 		 => self::cfdiUtf8Decode($data['notarios']['Adscripcion']),
		];
  
		$this->satxmlsv33_cargaAtt($notari,$datosOperacion);
		
		/****** enajenantes *******/
				
		$arregloajenantes = json_decode($data['notarios']['enajenantes']);
		
		$numeroEnajenantes=count($arregloajenantes);
		
		 if($numeroEnajenantes == 1){
		 $dataCon = ["CoproSocConyugalE" => "No",];
		 }else{
		 $dataCon = ["CoproSocConyugalE" => "Si",];
		 }
		 
		 
		$enajenant = $this->xml->createElement("notariospublicos:DatosEnajenante");
		$enajenant = $notarios->appendChild($enajenant);
		$this->satxmlsv33_cargaAtt($enajenant, $dataCon);
		
		
		if($numeroEnajenantes == 1){
			$unenajenanteNodo = $this->xml->createElement("notariospublicos:DatosUnEnajenante");
			$unenajenanteNodo = $enajenant->appendChild($unenajenanteNodo);
			
			
			foreach($arregloajenantes as $unenajenante){
			$unenajenante = (array) $unenajenante;
			$datoUnEnajenante = [ 	
			 "Nombre" 			=> self::cfdiUtf8Decode($unenajenante['nombre']),
			 "ApellidoPaterno"	=> self::cfdiUtf8Decode($unenajenante['paterno']),
			 "ApellidoMaterno"	=> self::cfdiUtf8Decode($unenajenante['materno']),
			 "RFC" 				=> $unenajenante['rfc'],
			 "CURP"				=> $unenajenante['curp'],
			 ];
			 
			 if($unenajenante['materno'] ==''){
				unset($datoUnEnajenante['ApellidoMaterno']);
			 }
			 
			 $this->satxmlsv33_cargaAtt($unenajenanteNodo,$datoUnEnajenante);
			}
			
		}else{
			
		$EnajenantesCopPdre = $this->xml->createElement("notariospublicos:DatosEnajenantesCopSC");
		$EnajenantesCopPdre = $enajenant->appendChild($EnajenantesCopPdre);
		
		foreach($arregloajenantes as $enajenantesn){
			$EnajenantesCop = $this->xml->createElement("notariospublicos:DatosEnajenanteCopSC");
			$EnajenantesCop = $EnajenantesCopPdre->appendChild($EnajenantesCop);
			
			$enajenantesn = (array) $enajenantesn;
			$datoVariosEnajenante = [ 	
			 "Nombre" 			=> self::cfdiUtf8Decode($enajenantesn['nombre']),
			 "ApellidoPaterno"	=> self::cfdiUtf8Decode($enajenantesn['paterno']),
			 "ApellidoMaterno"	=> self::cfdiUtf8Decode($enajenantesn['materno']),
			 "RFC" 				=> $enajenantesn['rfc'],
			 "CURP"				=> $enajenantesn['curp'],
			 "Porcentaje"		=> $enajenantesn['porcentaje'],
		   ];
			 
			 if($enajenantesn['materno'] ==''){
				unset($datoVariosEnajenante['ApellidoMaterno']);
			 }
			 
			 if($enajenantesn['paterno'] ==''){
				unset($datoVariosEnajenante['ApellidoPaterno']);
			 }
			 
			 if($enajenantesn['curp'] ==''){
				unset($datoVariosEnajenante['CURP']);
			 }
			 
			 $this->satxmlsv33_cargaAtt($EnajenantesCop,$datoVariosEnajenante);
		  }
		}
		
		/****datos adquirientes***/
		
				
		$arregloadquirientes = json_decode($data['notarios']['adquirientes']);
		
		$numeroAdquirientes=count($arregloadquirientes);
		
		
		
		 if($numeroAdquirientes == 1){
		 $dataConE = ["CoproSocConyugalE" => "No",];
		 }else{
		 $dataConE = ["CoproSocConyugalE" => "Si",];
		 }
		 
		 $adquirient = $this->xml->createElement("notariospublicos:DatosAdquiriente");
		 $adquirient = $notarios->appendChild($adquirient);
		 $this->satxmlsv33_cargaAtt($adquirient, $dataConE);
		 
		
		
		
		if($numeroAdquirientes == 1){
			$unAdquirienteNodo = $this->xml->createElement("notariospublicos:DatosUnAdquiriente");
			$unAdquirienteNodo = $adquirient->appendChild($unAdquirienteNodo);
			
			
			foreach($arregloadquirientes as $unadquiriente){
			$unadquiriente = (array) $unadquiriente;
			$datoUnAdquiriente = [ 	
			 "Nombre" 			=> self::cfdiUtf8Decode($unadquiriente['nombre']),
			 "ApellidoPaterno"	=> self::cfdiUtf8Decode($unadquiriente['paterno']),
			 "ApellidoMaterno"	=> self::cfdiUtf8Decode($unadquiriente['materno']),
			 "RFC" 				=> $unadquiriente['rfc'],
			 "CURP"				=> $unadquiriente['curp'],
			 ];
			 
			 if($unadquiriente['materno'] == ''){
				unset($datoUnAdquiriente['materno']);
			 }
			 
			 $this->satxmlsv33_cargaAtt($unAdquirienteNodo,$datoUnAdquiriente);
			}
			
		}else{
			$AdquirienteCopPadre = $this->xml->createElement("notariospublicos:DatosAdquirientesCopSC");
			$AdquirienteCopPadre = $adquirient->appendChild($AdquirienteCopPadre);
		
		foreach($arregloadquirientes as $adquirienten){
			$AdquirienteCop = $this->xml->createElement("notariospublicos:DatosAdquirienteCopSC");
			$AdquirienteCop = $AdquirienteCopPadre->appendChild($AdquirienteCop);
			
			$adquirienten = (array) $adquirienten;
			$datoVariosAdquirientes = [ 	
			 "Nombre" 			=> self::cfdiUtf8Decode($adquirienten['nombre']),
			 "ApellidoPaterno"	=> self::cfdiUtf8Decode($adquirienten['paterno']),
			 "ApellidoMaterno"	=> self::cfdiUtf8Decode($adquirienten['materno']),
			 "RFC" 				=> $adquirienten['rfc'],
			 "CURP"				=> $adquirienten['curp'],
			 "Porcentaje"		=> $adquirienten['porcentaje'],
		   ];
			 
			 if($adquirienten['materno'] ==''){
				unset($datoVariosAdquirientes['ApellidoMaterno']);
			 }
			 
			 if($adquirienten['paterno'] ==''){
				unset($datoVariosAdquirientes['ApellidoPaterno']);
			 }
			 
			 if($adquirienten['curp'] ==''){
				unset($datoVariosAdquirientes['CURP']);
			 }
			 
			 $this->satxmlsv33_cargaAtt($AdquirienteCop,$datoVariosAdquirientes);
		  }
		}
		
		
		 
	  
  }
  	//Funcion que arma el fracmento CfdiRelacionados dentro del xml donde agrega tipo de relacion y uuid
  	//Obtiene los datos del data donde lee los datos por objeto
  	public function setRelacion($data){
		
		if(isset($data['relacion']['tipoRelacion']) && $data['relacion']['tipoRelacion'] != ""){
			$relacionados = $this->xml->createElement("cfdi:CfdiRelacionados");
			$relacionados = $this->root->appendChild($relacionados);
			$datosRel= ["TipoRelacion" => $data['relacion']['tipoRelacion']];
			$this->satxmlsv33_cargaAtt($relacionados,$datosRel);
			$uuids = json_decode($data['Relaciones']['relacion']);
				foreach($uuids  as $uuid){
					$uuid = (array) $uuid; 
					$complementoRelacion = ["UUID" => $uuid['uuid']];
					$relacionado = $this->xml->createElement("cfdi:CfdiRelacionado"); /*creamos nodo CfdiRelacionado*/
					$relacionado = $relacionados->appendChild($relacionado);
					$this->satxmlsv33_cargaAtt($relacionado, $complementoRelacion);
				}
				
		}else{
		
			$relacionados_lista = json_decode($data['Relaciones']['relacion'], true) ?? [];
			foreach( $relacionados_lista as $rl){
				
				
			$relacionados = $this->xml->createElement("cfdi:CfdiRelacionados");
				$relacionados = $this->root->appendChild($relacionados);
				$datosRel= ["TipoRelacion" => $rl['tiporelacion']];
				$this->satxmlsv33_cargaAtt($relacionados,$datosRel);
				
					foreach($rl['uuids']  as $uuid){
						
						$complementoRelacion = ["UUID" => $uuid['uuid']];
						$relacionado = $this->xml->createElement("cfdi:CfdiRelacionado"); /*creamos nodo CfdiRelacionado*/
						$relacionado = $relacionados->appendChild($relacionado);
						$this->satxmlsv33_cargaAtt($relacionado, $complementoRelacion);
					}
			}
		}
	  }
	  
	   public function setDetallista($data,$complemento){
		$complemento = $this->root->appendChild($complemento);
		

		$atributosDetallista = [
			"type"  => "SimpleInvoiceType",
			"contentVersion" => "1.3.1",
			"documentStructureVersion" => "AMC8.1",
			"documentStatus" => $data['detallista']['documentStatus'],
				
		];
			
		
		$detallista = $this->xml->createElement("detallista:detallista"); 
		$detallista = $complemento->appendChild($detallista);
		$this->satxmlsv33_cargaAtt($detallista, $atributosDetallista);
		/*****************/		
		
			
		
		$requestPayment = $this->xml->createElement("detallista:requestForPaymentIdentification"); 
		$requestPayment = $detallista->appendChild($requestPayment);
		$this->satxmlsv33_cargaAtt($requestPayment, []);		
		
		$entityTipe = $this->xml->createElement("detallista:entityType",$data['detallista']['requestForPaymentIdentification']['entityType']); 
		$entityTipe = $requestPayment->appendChild($entityTipe);
		$this->satxmlsv33_cargaAtt($entityTipe,[]);	
		
		if($data['detallista']['requestForPaymentIdentification']['specialInstruction']['code'] != ""){
		$atributoSpecial = [
			"code"  => $data['detallista']['requestForPaymentIdentification']['specialInstruction']['code'],
		];
		$atributoSpe = $this->xml->createElement("detallista:specialInstruction"); 
		$atributoSpe = $detallista->appendChild($atributoSpe);
		$this->satxmlsv33_cargaAtt($atributoSpe, $atributoSpecial);
		
		include('NumeroALetras.php');
		$numLetra = new NumeroALetras();
		$cantidadConLetra = $numLetra->convertir($data['Cfdi']['Total'], $data['Cfdi']['Moneda']);
		
		$atributoText = $this->xml->createElement("detallista:text",$cantidadConLetra); 
		$atributoText = $atributoSpe->appendChild($atributoText);
	    $this->satxmlsv33_cargaAtt($atributoText,[]);
		}
		
		$orderIdentification = $this->xml->createElement("detallista:orderIdentification"); 
		$orderIdentification = $detallista->appendChild($orderIdentification);
		$this->satxmlsv33_cargaAtt($orderIdentification, []);
		
		$atributoreferenceIdentification = [
			"type"  => "ON", //***duda
		];
		$referenceIdentification = $this->xml->createElement("detallista:referenceIdentification",$data['detallista']['orderIdentification']['referenceIdentification']['ordenDeCompra']); 
		$referenceIdentification = $orderIdentification->appendChild($referenceIdentification);
		$this->satxmlsv33_cargaAtt($referenceIdentification,$atributoreferenceIdentification);
		
		$referenceIdentificationReferenceDate = $this->xml->createElement("detallista:ReferenceDate",$data['detallista']['orderIdentification']['ReferenceDate']); 
		$referenceIdentificationReferenceDate = $orderIdentification->appendChild($referenceIdentificationReferenceDate);
		$this->satxmlsv33_cargaAtt($referenceIdentificationReferenceDate,[]);
		
		$AdditionalInformationNodo = $this->xml->createElement("detallista:AdditionalInformation"); 
		$AdditionalInformationNodo = $detallista->appendChild($AdditionalInformationNodo);
		$this->satxmlsv33_cargaAtt($AdditionalInformationNodo,[]);
		
		$atrinutosReferenceIdentification = ["type" => $data['detallista']['AditionalInformation']['type']];
		
		$referenceIdentificationN = $this->xml->createElement("detallista:referenceIdentification",$data['detallista']['AditionalInformation']['referenceIdentification']); 
		$referenceIdentificationN = $AdditionalInformationNodo->appendChild($referenceIdentificationN);
		$this->satxmlsv33_cargaAtt($referenceIdentificationN,$atrinutosReferenceIdentification);
		
		$DeliveryNote = $this->xml->createElement("detallista:DeliveryNote"); 
		$DeliveryNote = $detallista->appendChild($DeliveryNote);
		$this->satxmlsv33_cargaAtt($DeliveryNote,[]);
		
		$referenceIdentification = $this->xml->createElement("detallista:referenceIdentification",$data['detallista']['DeliveryNote']['referenceIdentification']); 
		$referenceIdentification = $DeliveryNote->appendChild($referenceIdentification);
		$this->satxmlsv33_cargaAtt($referenceIdentification,[]);
		
		$ReferenceDateDely = $this->xml->createElement("detallista:ReferenceDate",$data['detallista']['DeliveryNote']['ReferenceDate']); 
		$ReferenceDateDely = $DeliveryNote->appendChild($ReferenceDateDely);
		$this->satxmlsv33_cargaAtt($ReferenceDateDely,[]);
		
		$buyer = $this->xml->createElement("detallista:buyer"); 
		$buyer = $detallista->appendChild($buyer);
		$this->satxmlsv33_cargaAtt($buyer,[]);
		
		$glnbuyer = $this->xml->createElement("detallista:gln",$data['detallista']['buyer']['gln']); 
		$glnbuyer = $buyer->appendChild($glnbuyer);
		$this->satxmlsv33_cargaAtt($glnbuyer,[]);
		
		$contactInformation = $this->xml->createElement("detallista:contactInformation"); 
		$contactInformation = $buyer->appendChild($contactInformation);
		$this->satxmlsv33_cargaAtt($contactInformation,[]);
		
		$personOrDepartmentName = $this->xml->createElement("detallista:personOrDepartmentName"); 
		$personOrDepartmentName = $contactInformation->appendChild($personOrDepartmentName);
		$this->satxmlsv33_cargaAtt($personOrDepartmentName,[]);
		
		
		
		$contactInformationText = $this->xml->createElement("detallista:text",$data['detallista']['buyer']['personOrDepartmentName']); 
		$contactInformationText = $personOrDepartmentName->appendChild($contactInformationText);
		$this->satxmlsv33_cargaAtt($contactInformationText,[]);
		
		$seller = $this->xml->createElement("detallista:seller"); 
		$seller = $detallista->appendChild($seller);
		$this->satxmlsv33_cargaAtt($seller,[]);
		
		$glnSeller = $this->xml->createElement("detallista:gln",$data['detallista']['seller']['gln']); 
		$glnSeller = $seller->appendChild($glnSeller);
		$this->satxmlsv33_cargaAtt($glnSeller,[]);
		
		
		$atributoPartyIdentificationSeller = [
		"type" => $data['detallista']['seller']['type'],
 		];
		
		$alternatePartyIdentificationSeller = $this->xml->createElement("detallista:alternatePartyIdentification",$data['detallista']['seller']['Numero']); 
		$alternatePartyIdentificationSeller = $seller->appendChild($alternatePartyIdentificationSeller);
		$this->satxmlsv33_cargaAtt($alternatePartyIdentificationSeller,$atributoPartyIdentificationSeller);
		
		if($data['detallista']['shipTo']['gln'] != '' || $data['detallista']['shipTo']['name'] != '' || $data['detallista']['shipTo']['streetAddressOne']!= '' || $data['detallista']['shipTo']['city'] != ''|| $data['detallista']['shipTo']['postalCode'] != ''){
		
		$shipTo = $this->xml->createElement("detallista:shipTo"); 
		$shipTo = $detallista->appendChild($shipTo);
		$this->satxmlsv33_cargaAtt($shipTo,[]);
		
		if($data['detallista']['shipTo']['gln'] !=  ''){
		
		$glnShipTo = $this->xml->createElement("detallista:gln",$data['detallista']['shipTo']['gln']); 
		$glnShipTo = $shipTo->appendChild($glnShipTo);
		$this->satxmlsv33_cargaAtt($glnShipTo,[]);
		
		}
		
		if($data['detallista']['shipTo']['name'] != '' || $data['detallista']['shipTo']['streetAddressOne']!= '' || $data['detallista']['shipTo']['city'] != ''|| $data['detallista']['shipTo']['postalCode'] != ''){
		
		$nameAndAddress = $this->xml->createElement("detallista:nameAndAddress"); 
		$nameAndAddress = $shipTo->appendChild($nameAndAddress);
		$this->satxmlsv33_cargaAtt($nameAndAddress,[]);
		if($data['detallista']['shipTo']['name']!=''){
		$nameAndAddressName = $this->xml->createElement("detallista:name",$data['detallista']['shipTo']['name']); 
		$nameAndAddressName = $nameAndAddress->appendChild($nameAndAddressName);
		$this->satxmlsv33_cargaAtt($nameAndAddressName,[]);
		}
		if($data['detallista']['shipTo']['streetAddressOne']){
		$streetAddressOne = $this->xml->createElement("detallista:streetAddressOne",$data['detallista']['shipTo']['streetAddressOne']); 
		$streetAddressOne = $nameAndAddress->appendChild($streetAddressOne);
		$this->satxmlsv33_cargaAtt($streetAddressOne,[]);
		}
		if($data['detallista']['shipTo']['city'] !=''){
		$city = $this->xml->createElement("detallista:city",$data['detallista']['shipTo']['city']); 
		$city = $nameAndAddress->appendChild($city);
		$this->satxmlsv33_cargaAtt($city,[]);
		}
		if($data['detallista']['shipTo']['postalCode'] != ''){
		$postalCode = $this->xml->createElement("detallista:postalCode",$data['detallista']['shipTo']['postalCode']); 
		$postalCode = $nameAndAddress->appendChild($postalCode);
		$this->satxmlsv33_cargaAtt($postalCode,[]);
		}
		}
		
	  }
		
	 if($data['detallista']['InvoiceCreator']['gln'] != '' || $data['detallista']['InvoiceCreator']['alternatePartyIdentification'] != '' || $data['detallista']['InvoiceCreator']['name'] != '' || $data['detallista']['InvoiceCreator']['streetAddressOne'] != '' || $data['detallista']['InvoiceCreator']['city']!= '' || $data['detallista']['InvoiceCreator']['postalCode'] != '' ){		
		
		$InvoiceCreator = $this->xml->createElement("detallista:InvoiceCreator"); 
		$InvoiceCreator = $detallista->appendChild($InvoiceCreator);
		$this->satxmlsv33_cargaAtt($InvoiceCreator,[]);
		
		if($data['detallista']['InvoiceCreator']['gln'] != ''){
		$glnInvoiceCreator = $this->xml->createElement("detallista:gln",$data['detallista']['InvoiceCreator']['gln']); 
		$glnInvoiceCreator = $InvoiceCreator->appendChild($glnInvoiceCreator);
		$this->satxmlsv33_cargaAtt($glnInvoiceCreator,[]);		
		}
		
		if( $data['detallista']['InvoiceCreator']['type'] != '' AND $data['detallista']['InvoiceCreator']['alternatePartyIdentification'] != ''){
		$alternatePartyIdentificationAtri= [
		"type" => $data['detallista']['InvoiceCreator']['type']
		];
		$alternatePartyIdentification = $this->xml->createElement("detallista:alternatePartyIdentification",$data['detallista']['InvoiceCreator']['alternatePartyIdentification']); 
		$alternatePartyIdentification = $InvoiceCreator->appendChild($alternatePartyIdentification);
		$this->satxmlsv33_cargaAtt($alternatePartyIdentification,$alternatePartyIdentificationAtri);
		}
		
		if($data['detallista']['InvoiceCreator']['name']!= '' || $data['detallista']['InvoiceCreator']['streetAddressOne'] != '' || $data['detallista']['InvoiceCreator']['city']!= '' || $data['detallista']['InvoiceCreator']['postalCode'] != ''){
		$nameAndAddressCreator = $this->xml->createElement("detallista:nameAndAddress"); 
		$nameAndAddressCreator = $InvoiceCreator->appendChild($nameAndAddressCreator);
		$this->satxmlsv33_cargaAtt($nameAndAddressCreator,[]);
		if($data['detallista']['InvoiceCreator']['name'] != ''){
		$nameAndAddressName = $this->xml->createElement("detallista:name",$data['detallista']['InvoiceCreator']['name']); 
		$nameAndAddressName = $nameAndAddressCreator->appendChild($nameAndAddressName);
		$this->satxmlsv33_cargaAtt($nameAndAddressName,[]);
		}
		
		if($data['detallista']['InvoiceCreator']['streetAddressOne'] != ''){
		$streetAddressOne = $this->xml->createElement("detallista:streetAddressOne",$data['detallista']['InvoiceCreator']['streetAddressOne']); 
		$streetAddressOne = $nameAndAddressCreator->appendChild($streetAddressOne);
		$this->satxmlsv33_cargaAtt($streetAddressOne,[]);
		}
		if($data['detallista']['InvoiceCreator']['city'] != ''){
		$city = $this->xml->createElement("detallista:city",$data['detallista']['InvoiceCreator']['city']); 
		$city = $nameAndAddressCreator->appendChild($city);
		$this->satxmlsv33_cargaAtt($city,[]);
		}
		if($data['detallista']['InvoiceCreator']['postalCode'] != ''){
		$postalCode = $this->xml->createElement("detallista:postalCode",$data['detallista']['InvoiceCreator']['postalCode']); 
		$postalCode = $nameAndAddressCreator->appendChild($postalCode);
		$this->satxmlsv33_cargaAtt($postalCode,[]);
		}
		}
		}
		if($data['detallista']['Customs']['gln'] != ''){
		$Customs = $this->xml->createElement("detallista:Customs"); 
		$Customs = $detallista->appendChild($Customs);
		$this->satxmlsv33_cargaAtt($Customs,[]);
		
		$glnShipTo = $this->xml->createElement("detallista:gln",$data['detallista']['Customs']['gln']); 
		$glnShipTo = $Customs->appendChild($glnShipTo);
		$this->satxmlsv33_cargaAtt($glnShipTo,[]);
		}
		
		
		if($data['detallista']['Currency']['currencyISOCode'] !=''  AND $data['detallista']['Currency']['currencyFunction'] != ''){
		$currencyAtributos = [
		"currencyISOCode" => $data['detallista']['Currency']['currencyISOCode']
		];
		
		$currency = $this->xml->createElement("detallista:currency"); 
		$currency = $detallista->appendChild($currency);
		$this->satxmlsv33_cargaAtt($currency,$currencyAtributos);
		
		$currencyFunction = $this->xml->createElement("detallista:currencyFunction",$data['detallista']['Currency']['currencyFunction']); 
		$currencyFunction = $currency->appendChild($currencyFunction);
		$this->satxmlsv33_cargaAtt($currencyFunction,[]);
		if($data['detallista']['Currency']['rateOfChange'] != ''){
		$rateOfChange = $this->xml->createElement("detallista:rateOfChange",$data['detallista']['Currency']['rateOfChange']); 
		$rateOfChange = $currency->appendChild($rateOfChange);
		$this->satxmlsv33_cargaAtt($rateOfChange,[]);
		}
		
	   }
		
		$paymentTermsAtributo = [
			"paymentTermsEvent" 		=> $data['detallista']['paymentTerms']['PaymentTermsEvent'],
			"PaymentTermsRelationTime"	=> $data['detallista']['paymentTerms']['PaymentTermsRelationTime']
		
		];
		
		if($data['detallista']['paymentTerms']['PaymentTermsEvent'] ==''){
			unset($paymentTermsAtributo["paymentTermsEvent"]);
		}
		if($data['detallista']['paymentTerms']['PaymentTermsRelationTime'] ==''){
			unset($paymentTermsAtributo["PaymentTermsRelationTime"]);
		}
		if(($data['detallista']['paymentTerms']['NetPaymentTermsType'] != '' AND  $data['detallista']['paymentTerms']['timePeriod'] != '' AND $data['detallista']['paymentTerms']['paymentTimePeriod']!= '' AND $data['detallista']['paymentTerms']['Percentaje'] !='') || ($data['detallista']['paymentTerms']['discountPaymentType'] != '' AND $data['detallista']['paymentTerms']['Percentaje'] !='' )){
		$paymentTerms = $this->xml->createElement("detallista:paymentTerms"); 
		$paymentTerms = $detallista->appendChild($paymentTerms);
		$this->satxmlsv33_cargaAtt($paymentTerms,$paymentTermsAtributo);
		
		if($data['detallista']['paymentTerms']['NetPaymentTermsType'] != '' AND  $data['detallista']['paymentTerms']['timePeriod'] != '' AND $data['detallista']['paymentTerms']['paymentTimePeriod']!= '' AND $data['detallista']['paymentTerms']['Percentaje'] !=''){
		$netPaymentAtributo = [
			"netPaymentTermsType" 		=> $data['detallista']['paymentTerms']['NetPaymentTermsType'],
		
		];
		$netPayment = $this->xml->createElement("detallista:netPayment"); 
		$netPayment = $paymentTerms->appendChild($netPayment);
		$this->satxmlsv33_cargaAtt($netPayment,$netPaymentAtributo);
		
		$paymentTimePeriod = $this->xml->createElement("detallista:paymentTimePeriod"); 
		$paymentTimePeriod = $netPayment->appendChild($paymentTimePeriod);
		$this->satxmlsv33_cargaAtt($paymentTimePeriodpaymentTimePeriod,[]);
		
		$timePeriodDueAtributo = ["timePeriod" => $data['detallista']['paymentTerms']['timePeriod']];
		
		$timePeriodDue = $this->xml->createElement("detallista:timePeriodDue"); 
		$timePeriodDue = $paymentTimePeriod->appendChild($timePeriodDue);
		$this->satxmlsv33_cargaAtt($timePeriodDue,$timePeriodDueAtributo);
		
		$valueDue = $this->xml->createElement("detallista:value",$data['detallista']['paymentTerms']['paymentTimePeriod']); 
		$valueDue = $timePeriodDue->appendChild($valueDue);
		$this->satxmlsv33_cargaAtt($valueDue,[]);
		}
		
		if($data['detallista']['paymentTerms']['discountPaymentType'] != '' AND $data['detallista']['paymentTerms']['Percentaje'] !='' ){
			$discountPaymentAtributo = ["discountType" => $data['detallista']['paymentTerms']['discountPaymentType']];
			$discountPayment = $this->xml->createElement("detallista:discountPayment"); 
			$discountPayment = $paymentTerms->appendChild($discountPayment);
			$this->satxmlsv33_cargaAtt($discountPayment,$discountPaymentAtributo);
			
			$discountPaymentPercentage = $this->xml->createElement("detallista:percentage",$data['detallista']['paymentTerms']['Percentaje']); 
			$discountPaymentPercentage = $discountPayment->appendChild($discountPaymentPercentage);
			$this->satxmlsv33_cargaAtt($discountPaymentPercentage,[]);
		}
		
		}
		/*$shipmentDetail = $this->xml->createElement("detallista:shipmentDetail"); 
		$shipmentDetail = $paymentTerms->appendChild($shipmentDetail);
		$this->satxmlsv33_cargaAtt($shipmentDetail,[]);*/
		
		if(isset($data["detallista"]["LineItem"]["cargos_globales"]) && $data["detallista"]["LineItem"]["cargos_globales"] == 1){
		
		$allowanceChargeAtributo = [
			"allowanceChargeType"	=>	$data['detallista']['allowanceCharge']['allowanceChargeType'], 
			"settlementType"		=>	$data['detallista']['allowanceCharge']['settlementType'],
			"sequenceNumber"		=>	$data['detallista']['allowanceCharge']['SequenceNumber'],
		];
		
		if($data['detallista']['allowanceCharge']['SequenceNumber'] == ''){
			unset($allowanceChargeAtributo['sequenceNumber']);
		}
		
		$allowanceCharge = $this->xml->createElement("detallista:allowanceCharge"); 
		$allowanceCharge = $detallista->appendChild($allowanceCharge);
		$this->satxmlsv33_cargaAtt($allowanceCharge,$allowanceChargeAtributo);
		
		$monetaryAmountOrPercentage = $this->xml->createElement("detallista:monetaryAmountOrPercentage"); 
		$monetaryAmountOrPercentage = $allowanceCharge->appendChild($monetaryAmountOrPercentage);
		$this->satxmlsv33_cargaAtt($monetaryAmountOrPercentage,[]);
		
		$amountRate = [
			"base"	=>	"INVOICE_VALUE",
		];
		
		$rateAmount = $this->xml->createElement("detallista:rate"); 
		$rateAmount = $monetaryAmountOrPercentage->appendChild($rateAmount);
		$this->satxmlsv33_cargaAtt($rateAmount,$amountRate);
		
		$percentageRate = $this->xml->createElement("detallista:percentage",$data['detallista']['allowanceCharge']['porcentage']); 
		$percentageRate = $rateAmount->appendChild($percentageRate);
		$this->satxmlsv33_cargaAtt($percentageRate,[]);
	   }
		//Elemento: specialServicesType nodo opcional olvido
		
		/*****Line Item******/
		$productosItem = json_decode($data['json']['conceptos']);
		
		$i=1;
		foreach($productosItem as $producto){
		$producto = (array)$producto;
		$ClaveProdServ = $producto['ClaveProdServ'];
		$NoIdentificacion = $producto['NoIdentificacion'];
		$Cantidad = $producto['Cantidad'];
		$ClaveUnidad = $producto['ClaveUnidad'];
		$Descripcion = $producto['Descripcion'];
		$ValorUnitario = $producto['ValorUnitario'];
		$Importe = $producto['Importe'];
		$Descuento = $producto['Descuento'];
		//$unidad   = file_get_contents("http://sistema.factura-lo.com.mx/puebas/ws/claveUnidad/" . $ClaveUnidad);
		
		$unidad   = file_get_contents("https://sistema.factura-lo.com.mx/pruebas/ws/claveUnidad/" . $ClaveUnidad);
		
				if (!empty($unidad)) {
			$unidad = explode(" - ", $unidad)[1];
		    $unidad = trim($unidad);
			 
		}
		
		
		$atributoLineItem = [
		"type" 		=> "SimpleInvoiceLineItemType",
		"number"	=>	$i,
		];
		
		$lineItem = $this->xml->createElement("detallista:lineItem"); 
		$lineItem = $detallista->appendChild($lineItem);
		$this->satxmlsv33_cargaAtt($lineItem,$atributoLineItem);
		
		$tradeItemIdentification = $this->xml->createElement("detallista:tradeItemIdentification"); 
		$tradeItemIdentification = $lineItem->appendChild($tradeItemIdentification);
		$this->satxmlsv33_cargaAtt($tradeItemIdentification,[]);
		
		$gtin = $this->xml->createElement("detallista:gtin", $NoIdentificacion); 
		$gtin = $tradeItemIdentification->appendChild($gtin);
		$this->satxmlsv33_cargaAtt($gtin,[]);
		
		$atributoAlternateTradeItemIdentification = [
		"type"	=>	"SUPPLIER_ASSIGNED",
		];
		
		$alternateTradeItemIdentification = $this->xml->createElement("detallista:alternateTradeItemIdentification",$NoIdentificacion); 
		$alternateTradeItemIdentification = $lineItem->appendChild($alternateTradeItemIdentification);
		$this->satxmlsv33_cargaAtt($alternateTradeItemIdentification,$atributoAlternateTradeItemIdentification);
		
		if($data['detallista']['LineItem']['language'] != ''){
			$atributoTradeItemDescriptionInformation = [
				"language" => $data['detallista']['LineItem']['language']
			];
			 
			$tradeItemDescriptionInformation = $this->xml->createElement("detallista:tradeItemDescriptionInformation"); 
			$tradeItemDescriptionInformation = $lineItem->appendChild($tradeItemDescriptionInformation);
			$this->satxmlsv33_cargaAtt($tradeItemDescriptionInformation,$atributoTradeItemDescriptionInformation);
		$Descripcion = substr($Descripcion, 0, 35);
		$longText = $this->xml->createElement("detallista:longText",$Descripcion); 
		$longText = $tradeItemDescriptionInformation->appendChild($longText);
		$this->satxmlsv33_cargaAtt($longText,[]);
		
		}
		
		$invoicedQuantityAtributos = [
		"unitOfMeasure" => $Cantidad /**obde conde para ver que es*/
		];		
		
		$invoicedQuantity = $this->xml->createElement("detallista:invoicedQuantity",$Cantidad); 
		$invoicedQuantity = $lineItem->appendChild($invoicedQuantity);
		$this->satxmlsv33_cargaAtt($invoicedQuantity,$invoicedQuantityAtributos);
		
		
		$aditionalQuantityAtributos = [
		"QuantityType" => 'NUM_CONSUMER_UNITS'
		];		
		$aditionalQuantity = $this->xml->createElement("detallista:aditionalQuantity",$Cantidad);  //opcional quantity
		$aditionalQuantity = $lineItem->appendChild($aditionalQuantity);
		$this->satxmlsv33_cargaAtt($aditionalQuantity,$aditionalQuantityAtributos);
		
		$grossPrice = $this->xml->createElement("detallista:grossPrice"); 
		$grossPrice = $lineItem->appendChild($grossPrice);
		$this->satxmlsv33_cargaAtt($grossPrice,[]);
		
		$grossPriceAmount = $this->xml->createElement("detallista:Amount",$ValorUnitario); 
		$grossPriceAmount = $grossPrice->appendChild($grossPriceAmount);
		$this->satxmlsv33_cargaAtt($grossPriceAmount,[]);
		
		$netPrice = $this->xml->createElement("detallista:netPrice"); 
		$netPrice = $lineItem->appendChild($netPrice);
		$this->satxmlsv33_cargaAtt($netPrice,[]);
		
		$netPriceAmount = $this->xml->createElement("detallista:Amount",$ValorUnitario); 
		$netPriceAmount = $netPrice->appendChild($netPriceAmount);
		$this->satxmlsv33_cargaAtt($netPriceAmount,[]);
	  
		$lineItemAdditionalInformation = $this->xml->createElement("detallista:AdditionalInformation"); 
		$lineItemAdditionalInformation = $lineItem->appendChild($lineItemAdditionalInformation);
		$this->satxmlsv33_cargaAtt($lineItemAdditionalInformation,[]);
		
		$lineReferenceIdentificationAtributo = [
		"type" => "ON"
		];
		$lineReferenceIdentification   = $this->xml->createElement("detallista:referenceIdentification",$ClaveProdServ); 
		$lineReferenceIdentification  = $lineItemAdditionalInformation->appendChild($lineReferenceIdentification );
		$this->satxmlsv33_cargaAtt($lineReferenceIdentification ,$lineReferenceIdentificationAtributo);
		//$data['detallista']['LineItem']['Customs'] != '')||
		if(($data['detallista']['LineItem']['alternatePartyIdentification'] != '' AND $data['detallista']['LineItem']['ReferenceDate']!='' AND $data['detallista']['LineItem']['Name'] != '')){
		$lineItemCustoms = $this->xml->createElement("detallista:Customs"); 
		$lineItemCustoms = $lineItem->appendChild($lineItemCustoms);
		$this->satxmlsv33_cargaAtt($lineItemCustoms,[]);
		
		if($data['detallista']['LineItem']['Customs'] != '' ){
			$glnCustoms = $this->xml->createElement("detallista:gln",$data['detallista']['LineItem']['Customs']); 
			$glnCustoms = $lineItemCustoms->appendChild($glnCustoms);
			$this->satxmlsv33_cargaAtt($glnCustoms,[]);
		}
		if($data['detallista']['LineItem']['alternatePartyIdentification'] != '' AND $data['detallista']['LineItem']['ReferenceDate']!='' AND $data['detallista']['LineItem']['Name'] != ''){
		
		$atributosPartyId =[
		"type"  => 'TN'
		];		
		$alternatePartyIdentificationCustoms = $this->xml->createElement("detallista:alternatePartyIdentification",$data['detallista']['LineItem']['alternatePartyIdentification']); 
		$alternatePartyIdentificationCustoms = $lineItemCustoms->appendChild($alternatePartyIdentificationCustoms);
		$this->satxmlsv33_cargaAtt($alternatePartyIdentificationCustoms,$atributosPartyId);
		
		
		$ReferenceDateCustoms = $this->xml->createElement("detallista:ReferenceDate",$data['detallista']['LineItem']['ReferenceDate']); 
		$ReferenceDateCustoms = $lineItemCustoms->appendChild($ReferenceDateCustoms);
		$this->satxmlsv33_cargaAtt($ReferenceDateCustoms,[]);
		
		$nameAndAddressCustoms = $this->xml->createElement("detallista:nameAndAddress"); 
		$nameAndAddressCustoms = $lineItemCustoms->appendChild($nameAndAddressCustoms);
		$this->satxmlsv33_cargaAtt($nameAndAddressCustoms,[]);
		
		
		
		$nameAndAddressNameCustoms = $this->xml->createElement("detallista:name",$data['detallista']['LineItem']['Name']); 
		$nameAndAddressNameCustoms = $nameAndAddressCustoms->appendChild($nameAndAddressNameCustoms);
		$this->satxmlsv33_cargaAtt($nameAndAddressNameCustoms,[]);
		}
		}
		
		if(isset($data["detallista"]["LineItem"]["unidad_logistica"]) && $data["detallista"]["LineItem"]["unidad_logistica"] == 1){
		$LogisticUnits = $this->xml->createElement("detallista:LogisticUnits"); 
		$LogisticUnits = $lineItem->appendChild($LogisticUnits);
		$this->satxmlsv33_cargaAtt($LogisticUnits,[]);
		
		$atributosSerialShippingContainerCode = [
		"type" => $data['detallista']['LineItem']['serialShippingContainerCodeType']
		];
		$SerialShippingContainerCode = $this->xml->createElement("detallista:serialShippingContainerCode",$data['detallista']['LineItem']['serialShippingContainerCode']); 
		$SerialShippingContainerCode = $LogisticUnits->appendChild($SerialShippingContainerCode);
		$this->satxmlsv33_cargaAtt($SerialShippingContainerCode,$atributosSerialShippingContainerCode);
		}
		if($data['detallista']['LineItem']['palletQuantity'] != '' AND $data['detallista']['LineItem']['description']['Type'] != '' AND $data['detallista']['LineItem']['transport'] != ''){
		$palletInformation = $this->xml->createElement("detallista:palletInformation"); 
		$palletInformation = $lineItem->appendChild($palletInformation);
		$this->satxmlsv33_cargaAtt($palletInformation,[]);
		
		$palletQuantity = $this->xml->createElement("detallista:palletQuantity",$data['detallista']['LineItem']['palletQuantity']); 
		$palletQuantity = $palletInformation->appendChild($palletQuantity);
		$this->satxmlsv33_cargaAtt($palletQuantity,[]);
		$atributospalletQuantityDescription = [
		"type" => $data['detallista']['LineItem']['description']['Type']
		];
		//
		//debug($data['detallista']['LineItem']['description']['Type']);
		$palletQuantityDescription = $this->xml->createElement("detallista:description",$data['detallista']['LineItem']['description']['Type']); 
		$palletQuantityDescription = $palletInformation->appendChild($palletQuantityDescription);
		$this->satxmlsv33_cargaAtt($palletQuantityDescription,$atributospalletQuantityDescription);
		
		$palletTransport = $this->xml->createElement("detallista:transport"); 
		$palletTransport = $palletInformation->appendChild($palletTransport);
		$this->satxmlsv33_cargaAtt($palletTransport,[]);
		
		$palletMethodOfPayment = $this->xml->createElement("detallista:methodOfPayment",$data['detallista']['LineItem']['transport']); 
		$palletMethodOfPayment = $palletTransport->appendChild($palletMethodOfPayment);
		$this->satxmlsv33_cargaAtt($palletMethodOfPayment,[]);
		}
		
		if($data['detallista']['LineItem']['productionDate'] != '' AND $data['detallista']['LineItem']['LotNumber'] !=''){
			
		$extendedAttributes = $this->xml->createElement("detallista:extendedAttributes"); 
		$extendedAttributes = $lineItem->appendChild($extendedAttributes);
		$this->satxmlsv33_cargaAtt($extendedAttributes,[]);
		
		$atributoLotNumber =[
		"productionDate" => $data['detallista']['LineItem']['productionDate']
		];			
		$lotNumber  = $this->xml->createElement("detallista:lotNumber",$data['detallista']['LineItem']['LotNumber']); 
		$lotNumber  = $extendedAttributes->appendChild($lotNumber);
		$this->satxmlsv33_cargaAtt($lotNumber ,$atributoLotNumber);		
		}
		
		if(isset($data["detallista"]["LineItem"]["cargos_globales"]) && $data["detallista"]["LineItem"]["cargos_globales"] == 1){
		
		$lineItenAllowanceChargeAtrib =[
		"allowanceChargeType" 	=> 	$data['detallista']['allowanceCharge']['allowanceChargeType'],
		"settlementType"		=>	$data['detallista']['allowanceCharge']['settlementType'],
		"sequenceNumber"		=> 	$data['detallista']['allowanceCharge']['SequenceNumber'],
		];		
		
		if($data['detallista']['allowanceCharge']['settlementType'] == ''){
			unset($lineItenAllowanceChargeAtrib['settlementType']);
		}
		
		if($data['detallista']['allowanceCharge']['SequenceNumber'] == ''){
			unset($lineItenAllowanceChargeAtrib['SequenceNumber']);
		}
			
		$lineItenAllowanceCharge  = $this->xml->createElement("detallista:allowanceCharge"); 
		$lineItenAllowanceCharge  = $lineItem->appendChild($lineItenAllowanceCharge);
		$this->satxmlsv33_cargaAtt($lineItenAllowanceCharge,$lineItenAllowanceChargeAtrib);
		
		if($data['detallista']['allowanceCharge']['specialServicesType'] != ''){
		$specialServicesType  = $this->xml->createElement("detallista:specialServicesType",$data['detallista']['allowanceCharge']['specialServicesType']); 
		$specialServicesType  = $lineItenAllowanceCharge->appendChild($specialServicesType);
		$this->satxmlsv33_cargaAtt($specialServicesType,[]);
		}
		
		
		
		$monetaryAmountOrPercentage  = $this->xml->createElement("detallista:monetaryAmountOrPercentage"); 
		$monetaryAmountOrPercentage  = $lineItenAllowanceCharge->appendChild($monetaryAmountOrPercentage);
		$this->satxmlsv33_cargaAtt($monetaryAmountOrPercentage,[]);
		
		$percentagePerUnit  = $this->xml->createElement("detallista:percentagePerUnit",$data['detallista']['allowanceCharge']['porcentage']); 
		$percentagePerUnit  = $monetaryAmountOrPercentage->appendChild($percentagePerUnit);
		$this->satxmlsv33_cargaAtt($percentagePerUnit,[]);
			
		$ratePerUnit  = $this->xml->createElement("detallista:ratePerUnit"); 
		$ratePerUnit  = $monetaryAmountOrPercentage->appendChild($ratePerUnit);
		$this->satxmlsv33_cargaAtt($ratePerUnit,[]);
		
		$amountPerUnit  = $this->xml->createElement("detallista:amountPerUnit",$Descuento); 
		$amountPerUnit  = $ratePerUnit->appendChild($amountPerUnit);
		$this->satxmlsv33_cargaAtt($amountPerUnit,[]);
		}
		/*
		$tradeItemTaxInformation  = $this->xml->createElement("detallista:tradeItemTaxInformation"); 
		$tradeItemTaxInformation  = $lineItem->appendChild($tradeItemTaxInformation);
		$this->satxmlsv33_cargaAtt($tradeItemTaxInformation,[]);
		
		$taxTypeDescription  = $this->xml->createElement("detallista:taxTypeDescription",$data['detallista']['lineItem']['taxTypeDescription']); 
		$taxTypeDescription  = $tradeItemTaxInformation->appendChild($taxTypeDescription);
		$this->satxmlsv33_cargaAtt($taxTypeDescription,[]);
		
		$referenceNumber  = $this->xml->createElement("detallista:referenceNumber",$data['detallista']['LineItem']['referenceNumber']); 
		$referenceNumber  = $tradeItemTaxInformation->appendChild($referenceNumber);
		$this->satxmlsv33_cargaAtt($referenceNumber,[]);
		
		$taxCategory  = $this->xml->createElement("detallista:taxCategory"); 
		$taxCategory  = $tradeItemTaxInformation->appendChild($taxCategory);
		$this->satxmlsv33_cargaAtt($taxCategory,[]);
		
		$tradeItemTaxAmount  = $this->xml->createElement("detallista:tradeItemTaxAmount"); 
		$tradeItemTaxAmount  = $tradeItemTaxInformation->appendChild($tradeItemTaxAmount);
		$this->satxmlsv33_cargaAtt($tradeItemTaxAmount,[]);
		
		$taxPercentage  = $this->xml->createElement("detallista:taxPercentage",$data['detallista']['LineItem']['taxPercentage']); 
		$taxPercentage  = $tradeItemTaxAmount->appendChild($taxPercentage);
		$this->satxmlsv33_cargaAtt($taxPercentage,[]);
		
		$taxAmount  = $this->xml->createElement("detallista:taxAmount",'23443'); 
		$taxAmount  = $tradeItemTaxAmount->appendChild($taxAmount);
		$this->satxmlsv33_cargaAtt($taxAmount,[]);*/
				
		$totalLineAmount  = $this->xml->createElement("detallista:totalLineAmount"); 
		$totalLineAmount  = $lineItem->appendChild($totalLineAmount);
		$this->satxmlsv33_cargaAtt($totalLineAmount,[]);
		
		$grossAmountLine  = $this->xml->createElement("detallista:grossAmount"); 
		$grossAmountLine  = $totalLineAmount->appendChild($grossAmountLine);
		$this->satxmlsv33_cargaAtt($grossAmountLine,[]);
		
		$AmountLineGross  = $this->xml->createElement("detallista:Amount",$ValorUnitario); 
		$AmountLineGross  = $grossAmountLine->appendChild($AmountLineGross);
		$this->satxmlsv33_cargaAtt($AmountLineGross,[]);
		
		$netAmounttLine  = $this->xml->createElement("detallista:netAmount"); 
		$netAmounttLine  = $totalLineAmount->appendChild($netAmounttLine);
		$this->satxmlsv33_cargaAtt($netAmounttLine,[]);
		
		$netAmountLin  = $this->xml->createElement("detallista:Amount",$ValorUnitario); 
		$netAmountLin  = $netAmounttLine->appendChild($netAmountLin);
		$this->satxmlsv33_cargaAtt($netAmountLine,[]);
		
		$i++;
		
		}
		/**Fin line item***/
		
		$totalAmount = $this->xml->createElement("detallista:totalAmount"); 
		$totalAmount = $detallista->appendChild($totalAmount);
		$this->satxmlsv33_cargaAtt($totalAmount,[]);
		
		$Amount = $this->xml->createElement("detallista:Amount",$data['Cfdi']['SubTotal']); 
		$Amount = $totalAmount->appendChild($Amount);
		$this->satxmlsv33_cargaAtt($Amount,[]);
		
		if(isset($data["detallista"]["LineItem"]["cargos_globales"]) && $data["detallista"]["LineItem"]["cargos_globales"] == 1){
			
		$artibutoTotalAllowanceCharge = [ 
			"allowanceOrChargeType"	=>	"ALLOWANCE",
		];
			
		$TotalAllowanceCharge = $this->xml->createElement("detallista:TotalAllowanceCharge"); 
		$TotalAllowanceCharge = $detallista->appendChild($TotalAllowanceCharge);
		$this->satxmlsv33_cargaAtt($TotalAllowanceCharge,$artibutoTotalAllowanceCharge);
		
		
		$specialServicesType = $this->xml->createElement("detallista:specialServicesType",$data['detallista']['allowanceCharge']['specialServicesType']); 
		$specialServicesType = $TotalAllowanceCharge->appendChild($specialServicesType);
		$this->satxmlsv33_cargaAtt($specialServicesType,[]);		
		$descuento = empty($data['Cfdi']['Descuento']) ? '0.00' :$data['Cfdi']['Descuento'];

		$specialServicesTypeAmount = $this->xml->createElement("detallista:Amount",$descuento); 
		$specialServicesTypeAmount = $TotalAllowanceCharge->appendChild($specialServicesTypeAmount);
		$this->satxmlsv33_cargaAtt($specialServicesTypeAmount,[]);
		}
		}
		
		function setVehiculo($data,$complemento){
		
		$complemento = $this->root->appendChild($complemento);
	  
		$vehiculo = $this->xml->createElement("vehiculousado:VehiculoUsado");
		$vehiculo = $complemento->appendChild($vehiculo);
		 
		$datosUsado= [			
			"Version"			=>	"1.0" ,
			"montoAdquisicion"  => $data['VEHICULO']['montoAdquisicion'],
			"montoEnajenacion"  => $data['VEHICULO']['montoEnajenacion'],
			"claveVehicular"  => $data['VEHICULO']['claveVehicular'],
			"marca"  => $data['VEHICULO']['marca'],
			"tipo"  => $data['VEHICULO']['tipo'],
			"modelo"  => $data['VEHICULO']['modelo'],
			"numeroMotor"  => $data['VEHICULO']['numeroMotor'],
			"numeroSerie"  => $data['VEHICULO']['numeroSerie'],
			"NIV"  => $data['VEHICULO']['NIV'],
			"valor"  => $data['VEHICULO']['valor'],
		];
		
		if($data['VEHICULO']['montoAdquisicion'] == ''){
			unset($datosUsado['numeroMotor']);				
		}
		
		if($data['VEHICULO']['montoAdquisicion'] == ''){
			unset($datosUsado['numeroSerie']);			
		}		
		if($data['VEHICULO']['montoAdquisicion'] == ''){
			unset($datosUsado['NIV']);			
		}
		
		
		$this->satxmlsv33_cargaAtt($vehiculo,$datosUsado);
		
		$infAdaunera = $this->xml->createElement("vehiculousado:InformacionAduanera");
		$infAdaunera = $vehiculo->appendChild($infAdaunera);
		
			 $datosAduana=[	
			 "numero" 	=> $data['VEHICULO']['Aduana']['numero'],
			 "fecha"	=> $data['VEHICULO']['Aduana']['fecha'],
			 "aduana"	=> $data['VEHICULO']['Aduana']['aduana'],
			 ];
			 
			 
			 
			 if($data['VEHICULO']['Aduana']['aduana'] ==''){
				unset($datosAduana['aduana']);
			 }
			 $this->satxmlsv33_cargaAtt($infAdaunera,$datosAduana);
  }
  
   public function setComercioExterior($data,$complemento){
		$complemento = $this->root->appendChild($complemento);
		

		$comercioDatos = [
			"Version"				=>	"2.0" ,
			"MotivoTraslado"		=>	$data['comercio']['MotivoTraslado'],
			//"TipoOperacion"			=>  $data['comercio']['TipoOperacion'], 
			"ClaveDePedimento"   	=>  $data['comercio']['ClaveDePedimento'],
			"CertificadoOrigen" 	=>  $data['comercio']['CertificadoOrigen'],
			"NumCertificadoOrigen" 	=>  $data['comercio']['NumCertificadoOrigen'],			
			"NumeroExportadorConfiable"  =>  $data['comercio']['NumeroExportadorConfiable'],
			"Incoterm" 				=>  $data['comercio']['Incoterm'],
			//"Subdivision"			=>  $data['comercio']['Subdivision'],
			"Observaciones"  		=>  $data['comercio']['Observaciones'],
			"TipoCambioUSD" 		=>  $data['comercio']['TipoCambioUSD'],	
			"TotalUSD" 				=>  $data['comercio']['TotalUSD'],				
			
			
		];
		
		if($data['Cfdi']['TipoDeComprobante'] != 'T' && $data['comercio']['MotivoTraslado'] != 05 || $data['comercio']['MotivoTraslado'] == ''){
			unset($comercioDatos['MotivoTraslado']);	
		}		
				
		
		/*if($data['comercio']['TipoOperacion'] == ''){
			unset($comercioDatos['TipoOperacion']);
		}**/
		if($data['comercio']['ClaveDePedimento'] == ''){
	    	unset($comercioDatos['ClaveDePedimento']);
		}
		if($data['comercio']['CertificadoOrigen']==''){
			unset($comercioDatos['CertificadoOrigen']);	
		}
		if($data['comercio']['CertificadoOrigen'] == 0){
			unset($comercioDatos['NumCertificadoOrigen']);
		}			
		if($data['comercio']['NumeroExportadorConfiable']==''){
			unset($comercioDatos['NumeroExportadorConfiable']);	
		}
		
	

		$paises = ['DEU','AUT','BEL','BGR','CYP','HRV','DNK','SVK','SVN','ESP','EST','FIN','FRA','GRC','HUN','IRL','ITA','LVA','LTU','LUX','MLT','NLD','POL','PRT','GBR','CZE','ROU','SWE'];
		$paisReceptor = $data['comercio']['Receptor']['Domicilio']['Pais'];
		
		if (!in_array($paisReceptor, $paises)) {
			unset($comercioDatos['NumeroExportadorConfiable']);	
		}
		
		if($data['comercio']['Incoterm'] == ''){
		unset($comercioDatos['Incoterm']);
		}
		/*if($data['comercio']['Subdivision'] ==''){
		unset($comercioDatos['Subdivision']);	
		}*/
		if($data['comercio']['Observaciones'] == '' ){
		unset($comercioDatos['Observaciones']);	
		}
		if($data['comercio']['TipoCambioUSD'] == ''){
		unset($comercioDatos['TipoCambioUSD']);
		}
		if($data['comercio']['TotalUSD'] ==''){
		unset($comercioDatos['TotalUSD']);	
		}
		
		
		$comercioExt = $this->xml->createElement("cce20:ComercioExterior"); 
		$comercioExt = $complemento->appendChild($comercioExt);
		$this->satxmlsv33_cargaAtt($comercioExt, $comercioDatos);
		
		/******Nodo Emisor*******/
		if(isset($data['comercio']['Emisor']['Curp'])){
		$datosEmisor =[
		"Curp"           =>$data['comercio']['Emisor']['Curp'],
		];
		
		if($data['comercio']['Emisor']['Curp'] == ''){
		unset($datosEmisor['Curp']);
		}
		}else{
			$datosEmisor = [];
		}
		
		$comercioExtEmisor = $this->xml->createElement("cce20:Emisor"); 
		$comercioExtEmisor = $comercioExt->appendChild($comercioExtEmisor);
		$this->satxmlsv33_cargaAtt($comercioExtEmisor, $datosEmisor);
		
		/******Nodo Emisor Domicilio *******/
		
		$datosEmisorDomicilio= [
		"Calle" 		 => $data['comercio']['Emisor']['Domicilio']['Calle'],
		"NumeroExterior" => $data['comercio']['Emisor']['Domicilio']['NumeroExterior'],
		"NumeroInterior" => $data['comercio']['Emisor']['Domicilio']['NumeroInterior'],
		"Colonia"		 => $this->getClaveCat($data['comercio']['Emisor']['Domicilio']['Colonia']),
		"Localidad" 	 => $this->getClaveCat($data['comercio']['Emisor']['Domicilio']['Localidad']),
		"Referencia" 	 => $data['comercio']['Emisor']['Domicilio']['Referencia'],
		"Municipio" 	 => $this->getClaveCat($data['comercio']['Emisor']['Domicilio']['Municipio']),
		"Estado" 		 => $this->getClaveCat($data['comercio']['Emisor']['Domicilio']['Estado']),
		"Pais" 			 => $this->getClaveCat($data['comercio']['Emisor']['Domicilio']['Pais']),
		"CodigoPostal"   => $data['comercio']['Emisor']['Domicilio']['CodigoPostal'],
		];
		
		
		
		if($data['comercio']['Emisor']['Domicilio']['NumeroExterior'] == ''){
		unset($datosEmisorDomicilio['NumeroExterior']);
		}
		if($data['comercio']['Emisor']['Domicilio']['NumeroInterior'] == ''){
		unset($datosEmisorDomicilio['NumeroInterior']);	
		}
		if($data['comercio']['Emisor']['Domicilio']['Colonia'] ==''){
		unset($datosEmisorDomicilio['Colonia']);
		}
		if($data['comercio']['Emisor']['Domicilio']['Localidad'] == ''){
		unset($datosEmisorDomicilio['Localidad']);	
		}
		if($data['comercio']['Emisor']['Domicilio']['Referencia'] == ''){
		unset($datosEmisorDomicilio['Referencia']);	
		}
		if($data['comercio']['Emisor']['Domicilio']['Municipio'] == ''){
		unset($datosEmisorDomicilio['Municipio']);	
		}
		
		
		
		$comercioExtEmisorDomicilio = $this->xml->createElement("cce20:Domicilio"); 
		$comercioExtEmisorDomicilio = $comercioExtEmisor->appendChild($comercioExtEmisorDomicilio);
		$this->satxmlsv33_cargaAtt($comercioExtEmisorDomicilio, $datosEmisorDomicilio);
		
		////****propiestarios**//
		if($data['Cfdi']['TipoDeComprobante'] == 'T' && $comercioDatos['MotivoTraslado'] == 05 ){
			$propietarios = json_decode($data['comercio']['Propietarios']);
			foreach($propietarios   as $propietario){
			$propietario = (array) $propietario; 
			$datoPropietario = [
			"NumRegIdTrib"	   => $propietario['NumRegIdTrib'],
			"ResidenciaFiscal" => $propietario['Residencia'],
			];

			$comercioExtPropietario= $this->xml->createElement("cce20:Propietario"); 
			$comercioExtPropietario = $comercioExt->appendChild($comercioExtPropietario);
			$this->satxmlsv33_cargaAtt($comercioExtPropietario, $datoPropietario);
			}
		}
		
		/******Nodo Receptor*******/
		//$datosReceptor =[
		//	"NumRegIdTrib" => 'NumRegIdTrib',
		//];

		$datosReceptor =[
			"NumRegIdTrib" => $data['comercio']['Receptor']['Domicilio']['NumRegIdTrib']
		];

		if($data['comercio']['Receptor']['Domicilio']['NumRegIdTrib'] == ''){
			unset($datosReceptor['NumRegIdTrib']);
		}
		if($data['Cfdi']['TipoDeComprobante'] =='T'){	//richie solicitud		
			unset($datosReceptor['NumRegIdTrib']);
		}
		//if(isset($data['comercio']['Receptor']['usar']) && $data['comercio']['Receptor']['usar'] ==1){
		
		$comercioExtReceptor = $this->xml->createElement("cce20:Receptor"); 
		$comercioExtReceptor = $comercioExt->appendChild($comercioExtReceptor);
		$this->satxmlsv33_cargaAtt($comercioExtReceptor, $datosReceptor);
		//}
		/******Nodo Receptor Domicilio *******/
		
		$datosReceptorDomicilio= [
			"Calle" 		 => $data['comercio']['Receptor']['Domicilio']['Calle'],
			"NumeroExterior" => $data['comercio']['Receptor']['Domicilio']['NumeroExterior'],
			"NumeroInterior" => $data['comercio']['Receptor']['Domicilio']['NumeroInterior'],
			"Colonia"		 => $this->getClaveCat($data['comercio']['Receptor']['Domicilio']['Colonia']),
			"Localidad" 	 => $this->getClaveCat($data['comercio']['Receptor']['Domicilio']['Localidad']),
			"Referencia" 	 => $data['comercio']['Receptor']['Domicilio']['Referencia'],
			"Municipio" 	 => $this->getClaveCat($data['comercio']['Receptor']['Domicilio']['Municipio']),
			"Estado" 		 => $this->getClaveCat($data['comercio']['Receptor']['Domicilio']['Estado']),
			"Pais" 			 => $this->getClaveCat($data['comercio']['Receptor']['Domicilio']['Pais']),
			"CodigoPostal"   => $data['comercio']['Receptor']['Domicilio']['CodigoPostal'],
		];
		
		
		
		if($data['comercio']['Receptor']['Domicilio']['NumeroExterior'] == ''){
		unset($datosReceptorDomicilio['NumeroExterior']);
		}
		if($data['comercio']['Receptor']['Domicilio']['NumeroInterior'] == ''){
		unset($datosReceptorDomicilio['NumeroInterior']);	
		}
		if($data['comercio']['Receptor']['Domicilio']['Colonia'] ==''){
		unset($datosReceptorDomicilio['Colonia']);
		}
		if($data['comercio']['Receptor']['Domicilio']['Localidad'] == ''){
		unset($datosReceptorDomicilio['Localidad']);	
		}
		if($data['comercio']['Receptor']['Domicilio']['Referencia'] == ''){
		unset($datosReceptorDomicilio['Referencia']);	
		}
		if($data['comercio']['Receptor']['Domicilio']['Municipio'] == ''){
		unset($datosReceptorDomicilio['Municipio']);	
		}
		//if(isset($data['comercio']['Receptor']['usar']) && $data['comercio']['Receptor']['usar'] ==1){
		$comercioExtReceptorDomicilio = $this->xml->createElement("cce20:Domicilio"); 
		$comercioExtReceptorDomicilio = $comercioExtReceptor->appendChild($comercioExtReceptorDomicilio);
		$this->satxmlsv33_cargaAtt($comercioExtReceptorDomicilio, $datosReceptorDomicilio);
		//}
		
		/******Nodo Destinatario*******/
		
		$destinatarios = json_decode($data['comercio']['Destinatarios']);
		if(!empty($destinatarios)){
		foreach($destinatarios as $d){
		$d =(array)$d;
		$datosDestinatario =[
		"NumRegIdTrib" => $d['NumRegIdTrib'],
		"Nombre"	   => $d['Nombre'],
		];
		$comercioExtDestinatario = $this->xml->createElement("cce20:Destinatario"); 
		$comercioExtDestinatario = $comercioExt->appendChild($comercioExtDestinatario);
		$this->satxmlsv33_cargaAtt($comercioExtDestinatario, $datosDestinatario);
		
		/******Nodo Destinatario Domicilio *******/
		
		$datosDestinatarioDomicilio= [
		"Calle" 		 => $d['Calle'],
		"NumeroExterior" => $d['NumeroExterior'],
		"NumeroInterior" => $d['NumeroInterior'],
		"Colonia"		 => $this->getClaveCat($d['Colonia']),
		"Localidad" 	 => $this->getClaveCat($d['Localidad']),
		"Referencia" 	 => $d['Referencia'],
		"Municipio" 	 => $this->getClaveCat($d['Municipio']),
		"Estado" 		 => $this->getClaveCat($d['Estado']),
		"Pais" 			 => $this->getClaveCat($d['Pais']),
		"CodigoPostal"   => $d['CodigoPostal'],
		];
		$paises = ['DEU','AUT','BEL','BGR','CYP','HRV','DNK','SVK','SVN','ESP','EST','FIN','FRA','GRC','HUN','IRL','ITA','LVA','LTU','LUX','MLT','NLD','POL','PRT','GBR','CZE','ROU','SWE'];
		
		
		if (!in_array($d['Pais'], $paises)) {
			unset($comercioDatos['NumeroExportadorConfiable']);	
		}
		
		
		if($d['NumeroExterior'] == ''){
		unset($datosDestinatarioDomicilio['NumeroExterior']);
		}
		if($d['NumeroInterior'] == ''){
		unset($datosDestinatarioDomicilio['NumeroInterior']);	
		}
		if($d['Colonia'] ==''){
		unset($datosDestinatarioDomicilio['Colonia']);
		}
		if($d['Localidad'] == ''){
		unset($datosDestinatarioDomicilio['Localidad']);	
		}
		if($d['Referencia'] == ''){
		unset($datosDestinatarioDomicilio['Referencia']);	
		}
		if($d['Municipio'] == ''){
		unset($datosDestinatarioDomicilio['Municipio']);	
		}
		
		$comercioExtDestinatarioDomicilio = $this->xml->createElement("cce20:Domicilio"); 
		$comercioExtDestinatarioDomicilio = $comercioExtDestinatario->appendChild($comercioExtDestinatarioDomicilio);
		$this->satxmlsv33_cargaAtt($comercioExtDestinatarioDomicilio, $datosDestinatarioDomicilio);
		
		}
		}
		////****Merncanias**//
		
		$comercioExtMercancias= $this->xml->createElement("cce20:Mercancias"); 
		$comercioExtMercancias = $comercioExt->appendChild($comercioExtMercancias);
		$this->satxmlsv33_cargaAtt($comercioExtMercancias, []);
		$mercancias = json_decode($data['json']['mercancias']);
		if (count($mercancias) > 0) {
						
		
		foreach($mercancias   as $m){
		$m = (array)$m;
		$arancelariaFraccion = explode("-", $m['FraccionArancelaria']);

		$mercancia = [
		"NoIdentificacion"	  => $m['NoIdentificacion'],
		"FraccionArancelaria" => trim($arancelariaFraccion['0']),
		//"FraccionArancelaria" => '07039001',
		"CantidadAduana"	  => $m['CantidadAduana'],
		"UnidadAduana"		  => $m['UnidadAduana'],
		"ValorUnitarioAduana" => $m['ValorUnitarioAduana'],
		//"ValorUnitarioAduana" => '200.00',
		"ValorDolares"		  => $m['ValorDolares'],
		//"ValorDolares"		  => '100.00',
		];
		
		if($m['FraccionArancelaria']==''){
			unset($mercancia['FraccionArancelaria']);
		}
		if($m['CantidadAduana']==''){
			unset($mercancia['CantidadAduana']);
		}
		if($m['UnidadAduana']==''){
			unset($mercancia['UnidadAduana']);
		}
		if($m['ValorUnitarioAduana']==''){
			unset($mercancia['ValorUnitarioAduana']);
		}
		
		$comercioExtMercancia= $this->xml->createElement("cce20:Mercancia"); 
		$comercioExtMercancia = $comercioExtMercancias->appendChild($comercioExtMercancia);
		$this->satxmlsv33_cargaAtt($comercioExtMercancia, $mercancia);

		if(!empty($m['Especificaciones'])){
		$especificaciones = $m['Especificaciones'];
		$especificaciones = (array)$especificaciones;
		foreach($especificaciones   as $especificacion){
		$especificacion = (array)$especificacion;	
		$espeMercancia = [
		"Marca"	 		 => $especificacion['Marca'],
		"Modelo"		 => $especificacion['Modelo'],
		"SubModelo"	 	 => $especificacion['SubModelo'],
		"NumeroSerie"	 => $especificacion['NumeroSerie'],
		];
	
		if($especificacion['Marca'] == ''){
			unset($espeMercancia['Marca']);
		}
		if($especificacion['Modelo'] == ''){
			unset($espeMercancia['Modelo']);
		}
		if($especificacion['SubModelo'] == ''){
			unset($espeMercancia['SubModelo']);
		}
		if($especificacion['NumeroSerie'] == ''){
			unset($espeMercancia['NumeroSerie']);
		}
		
		$especificacionMercancia= $this->xml->createElement("cce20:DescripcionesEspecificas"); 
		$especificacionMercancia = $comercioExtMercancia->appendChild($especificacionMercancia);
		$this->satxmlsv33_cargaAtt($especificacionMercancia, $espeMercancia);
		
		}
		}
	  }
	 }
	}
	
	function setAerolinea($data,$complemento){
		
		$complemento = $this->root->appendChild($complemento);
	  
		$aerolinea = $this->xml->createElement("aerolineas:Aerolineas");
		$aerolinea = $complemento->appendChild($aerolinea);
		 
		$aerolineas= [			
			"Version"	=>	"1.0" ,
			"TUA"		=> $data['Aerolineas']['TUA'],
		];
		
		$this->satxmlsv33_cargaAtt($aerolinea,$aerolineas);
		
		$oCargos = $this->xml->createElement("aerolineas:OtrosCargos");
		
		
		$oDatosCargos=[	
		"TotalCargos" => $data['Aerolineas']['TotalCargos'],
		];
		
		$oCargos= $aerolinea->appendChild($oCargos);
		
		$this->satxmlsv33_cargaAtt($oCargos,$oDatosCargos);
		$cargos = json_decode($data['json']['cargos']);

		foreach($cargos as $c) {
			
			$c = (array)$c;
			$cargoss = $this->xml->createElement("aerolineas:Cargo");
			$cargoss = $oCargos->appendChild($cargoss);
		
			$dataCargo = [
			"CodigoCargo" => $c['codigoCargo'],
			"Importe"     => $this->validarCeros($c['importe']),
			];			
			
			$this->satxmlsv33_cargaAtt($cargoss,$dataCargo);	
		}	 
  }
  
  
  
  function setDivisas($data,$complemento){
	  $complemento = $this->root->appendChild($complemento);
	  
		$divisas = $this->xml->createElement("divisas:Divisas");
		$divisas = $complemento->appendChild($divisas);
		 
		$d_divisas= [			
			"version"	=>	"1.0" ,
			"tipoOperacion"		=> $data['DIVISAS']['tipo'],
		];
		
		$this->satxmlsv33_cargaAtt($divisas,$d_divisas);
		
		
	  
  }
	
  public function getClaveCat($nombre){
		$clave = explode("-", $nombre);
		return $clave[0];		
  }
	
 function setCartaPorte10($data,$complemento){
		
		$complemento = $this->root->appendChild($complemento);
		$cartaporte = $this->xml->createElement("cartaporte:CartaPorte");
		$cartaporte = $complemento->appendChild($cartaporte);
		$carta= [			
			"Version"	=>	"1.0" ,
			"TranspInternac"		=> self::cfdiUtf8Decode($data['carta']['TranspInternac']),
			"EntradaSalidaMerc"		=> $data['carta']['EntradaSalidaMerc'],
			"ViaEntradaSalida"		=> $data['carta']['ViaEntradaSalida'],
			"TotalDistRec"		=> $data['carta']['TotalDistRec'],
		
		];
		
		if($data['carta']['TranspInternac'] == "No"){
			unset($carta['EntradaSalidaMerc']);
			unset($carta['ViaEntradaSalida']);
			
		}
		if(!isset($data['carta']['mercancias']['federal']['uso']) && !isset($data["carta"]["mercancias"]["ferroviario"]["uso"])){
			unset($carta['TotalDistRec']);
		}
		
		$this->satxmlsv33_cargaAtt($cartaporte,$carta);
		
		$ubicaciones = $this->xml->createElement("cartaporte:Ubicaciones");
		$ubicaciones = $cartaporte->appendChild($ubicaciones);
		$u_bicaciones = json_decode($data['carta']['inCartaUbicaciones'], true) ?? []; 
		
	
		if(count($u_bicaciones)> 0 ){ 
		foreach($u_bicaciones as $u) {

			$ubicacion = $this->xml->createElement("cartaporte:Ubicacion"); 
			$ubicacion = $ubicaciones->appendChild($ubicacion);
		
			$data_ubicacion = [
			"TipoEstacion" => trim(explode("-",$u['tipoEstacion'])[0]),
			"DistanciaRecorrida" => $u['distanciaRecorrida'],
			];			
		if(!isset($data['carta']['mercancias']['federal']['uso']) && !isset($data["carta"]["mercancias"]["ferroviario"]["uso"])){
	
		unset($data_ubicacion['DistanciaRecorrida']);
		}
			$this->satxmlsv33_cargaAtt($ubicacion,$data_ubicacion);	
				
				if(!empty($u['origen'])){
				
				$origen = $this->xml->createElement("cartaporte:Origen");
				$origen = $ubicacion->appendChild($origen);
				$data_origen = [
				"IDOrigen" => isset($u['origen']['idOrigen'])?$u['origen']['idOrigen']:"",
				"RFCRemitente"=> isset($u['origen']['rfc'])?$u['origen']['rfc']:"",
				"NombreRemitente"=> isset($u['origen']['nombre'])?$u['origen']['nombre']:"",
				"NumRegIdTrib"=> isset($u['origen']['numRegIdTrib'])?$u['origen']['numRegIdTrib']:"",
				"ResidenciaFiscal"=> isset($u['origen']['residencia'])?$u['origen']['residencia']:"",
				"NumEstacion"=> isset($u['origen']['numeroEstacion'])?trim(explode("-",$u['origen']['numeroEstacion'])[0]):"",
				"NombreEstacion"=> $u['origen']['numeroEstacion'] != ""?self::cfdiUtf8Decode(trim(explode("-",$u['origen']['numeroEstacion'])[1])):"",
				"NavegacionTrafico"=> isset($u['origen']['navegacionTrafico'])?$u['origen']['navegacionTrafico']:"",
				//"FechaHoraSalida"=> isset($u['origen']['fechaHoraSalida'])? str_replace(" ","T",$u['origen']['fechaHoraSalida']):"",
				"FechaHoraSalida"=> isset($u['origen']['fechaHoraSalida'])?$u['origen']['fechaHoraSalida']."T".date("H:m:i"):"",
				];
				
				//debug($data_origen);
				if(isset($u['origen']['rfc'])){
					unset($data_origen['NumRegIdTrib']);
					unset($data_origen['ResidenciaFiscal']);
					
					//unset($data_origen['NavegacionTrafico']);
				}
				
				if(isset($data['carta']['mercancias']['federal']['uso'])){
					unset($data_origen['NumEstacion']);
					unset($data_origen['NombreEstacion']); 
					unset($data_origen['NavegacionTrafico']);
				}
				if(!isset($data['carta']['mercancias']['maritimo']['uso'])){
					unset($data_origen['NavegacionTrafico']);
				}
				
				
				$this->satxmlsv33_cargaAtt($origen,$data_origen);	
				}
				if(!empty($u['destino'])){
				
				$destino = $this->xml->createElement("cartaporte:Destino");
				$destino = $ubicacion->appendChild($destino);
				
				$data_destino = [
				"IDDestino" => isset($u['destino']['idDestino'])?str_replace(" ","",$u['destino']['idDestino']):"",
				"RFCDestinatario"=> isset($u['destino']['rfc'])?$u['destino']['rfc']:"",
				"NombreDestinatario"=> isset($u['destino']['nombre'])?$u['destino']['nombre']:"",
				"NumRegIdTrib"=> isset($u['destino']['numRegIdTrib'])?$u['destino']['numRegIdTrib']:"",
				"ResidenciaFiscal"=> isset($u['destino']['residencia'])?$u['destino']['residencia']:"",
				"NumEstacion"=> isset($u['destino']['numeroEstacion'])?trim(explode("-",$u['destino']['numeroEstacion'])[0]):"",
				"NombreEstacion"=> ($u['destino']['numeroEstacion']!= "")?self::cfdiUtf8Decode(trim(explode("-",$u['destino']['numeroEstacion'])[1])):"",
				"NavegacionTrafico"=> isset($u['destino']['navegacionTrafico'])?$u['destino']['navegacionTrafico']:"",
				"FechaHoraProgLlegada"=> isset($u['destino']['fechaHoraLlegada'])?$u['destino']['fechaHoraLlegada']."T".date("H:m:i"):"",
				];
				
				
				
				if(isset($u['destino']['rfc'])){
					unset($data_destino['NumRegIdTrib']);
					unset($data_destino['ResidenciaFiscal']);
					
					
				}
				
				if(isset($data['carta']['mercancias']['federal']['uso'])){
					unset($data_destino['NumEstacion']);
					unset($data_destino['NombreEstacion']);
					
					
				}
				if(!isset($data['carta']['mercancias']['maritimo']['uso'])){
					unset($data_destino['NavegacionTrafico']);
				}
				
				$this->satxmlsv33_cargaAtt($destino,$data_destino);	
				}
				debug($u['Domicilio']);
				if(!empty($u['Domicilio'])){
				
				$domicilio = $this->xml->createElement("cartaporte:Domicilio");
				$domicilio = $ubicacion->appendChild($domicilio);
		
				$data_domicilio = [
				"Calle" => isset($u['Domicilio']['calle'])?$u['Domicilio']['calle']:"",
				"NumeroExterior"=> isset($u['Domicilio']['numeroExterior'])?$u['Domicilio']['numeroExterior']:"",
				"NumeroInterior"=> isset($u['Domicilio']['numeroInterior'])?$u['Domicilio']['numeroInterior']:"",
				"Colonia"=> isset($u['Domicilio']['colonia'])?$u['Domicilio']['colonia']:"",
				"Localidad"=> isset($u['Domicilio']['localidad'])?$u['Domicilio']['localidad']:"",
				"Referencia"=> isset($u['Domicilio']['referencia'])?$u['Domicilio']['referencia']:"",
				"Municipio"=> isset($u['Domicilio']['municipio'])?$u['Domicilio']['municipio']:"",
				"Estado"=> isset($u['Domicilio']['estado'])?$u['Domicilio']['estado']:"",
				"Pais"=> isset($u['Domicilio']['pais'])?$u['Domicilio']['pais']:"",
				"CodigoPostal"=> isset($u['Domicilio']['codigopostal'])?$u['Domicilio']['codigopostal']:"",
				];
				/*if($data["carta"]["TranspInternac"]=="Sí"){
					unset($data_domicilio['Pais']);
				}*/
				
				$this->satxmlsv33_cargaAtt($domicilio,$data_domicilio);	
				}
		}
	}
		
		$mercancias = $this->xml->createElement("cartaporte:Mercancias");
		$mercancias = $cartaporte->appendChild($mercancias);
		$data_mercancia= [			
			"PesoBrutoTotal"	=>	isset($data['carta']['Mercancias']['PesoBrutoTotal'])?$data['carta']['Mercancias']['PesoBrutoTotal']:"",
			"UnidadPeso"		=> isset($data['carta']['Mercancias']['UnidadPeso'])?trim(explode("-",$data['carta']['Mercancias']['UnidadPeso'])[0]):"",
			"PesoNetoTotal"		=> isset($data['carta']['Mercancias']['PesoNetoTotal'])?$data['carta']['Mercancias']['PesoNetoTotal']:"",
			"NumTotalMercancias"		=> isset($data['carta']['Mercancias']['NumTotalMercancias'])?$data['carta']['Mercancias']['NumTotalMercancias']:"",
			"CargoPorTasacion"		=> isset($data['carta']['Mercancias']['CargoPorTasacion'])?$data['carta']['Mercancias']['CargoPorTasacion']:"",
			];
			
			
		$this->satxmlsv33_cargaAtt($mercancias,$data_mercancia);
		$m_ercancias = json_decode($data['carta']['inCartaMercancias'], true) ?? []; 
		$quitarCTCT =false;
		if(count($m_ercancias)==1 ){
		$quitarCTCT =true;
		}
		foreach($m_ercancias as $m) {
			$mercancia = $this->xml->createElement("cartaporte:Mercancia"); 
			$mercancia = $mercancias->appendChild($mercancia);
			$data_mercancia = [
			"BienesTransp" => $m['BienesTransp'],
			"ClaveSTCC"     => trim(explode("-",$m['ClaveSTCC'])[0]),
			"Descripcion"     => $m['descripcion'],
			"Cantidad"     => $m['Cantidad'],
			"ClaveUnidad"     => trim(explode("-",$m['ClaveUnidad'])[0]),
			"Unidad"     => trim(explode("-",$m['Unidad'])[0]),
			"Dimensiones"     => $m['dimensiones'],
			"MaterialPeligroso"     => self::cfdiUtf8Decode($m['materialPeligroso']),
			"CveMaterialPeligroso"     => trim(self::cfdiUtf8Decode(explode("-",$m['claveMaterialPeligroso'])[0])),
			"Embalaje"     => trim(explode("-",$m['embalaje'])[0]),
			"DescripEmbalaje"     => $m['descripcionEmbalaje'],
			"PesoEnKg"     => $m['pesoKg'],
			"ValorMercancia"     => $m['valorMercancia'],
			"Moneda"     => $m['Moneda'],
			"FraccionArancelaria"     => explode("-",$m['FraccionArancelaria'])[0],
			"UUIDComercioExt"     => $m['UuidComercio'],
			];	
			$this->satxmlsv33_cargaAtt($mercancia,$data_mercancia);	
			if($m['transporta'] == 1){
			
			foreach($m['cantidad_transporta'] as $ct){
			$cantidad_transporta = $this->xml->createElement("cartaporte:CantidadTransporta");
			$cantidad_transporta = $mercancia->appendChild($cantidad_transporta);
			$data_mercancia = [
				"Cantidad" => $ct['cantidad'],
				"IDOrigen" => $ct['idOrigen'],
				"IDDestino"     => $ct['idDestino'],
				"CvesTransporte"     => $ct['claveTransporte'],
			];	
			
			if($quitarCTCT == true){
			unset($data_mercancia['CvesTransporte']);
			}
			$this->satxmlsv33_cargaAtt($cantidad_transporta,$data_mercancia);
			}
			}
		
			if($m['detalles']==1 && $m["detalle_mercancia"]["peso_bruto"]!=""){
			$detalle_mercancia = $this->xml->createElement("cartaporte20:DetalleMercancia");
			$detalle_mercancia = $mercancia->appendChild($detalle_mercancia);
			
			$data_detalle_mercancia = [
				"UnidadPeso" => trim(explode("-",$m["detalle_mercancia"]["unidad_peso"])[0]),
				"PesoBruto" => $m["detalle_mercancia"]["peso_bruto"],
				"PesoNeto"     => $m["detalle_mercancia"]["peso_neto"],
				"PesoTara"     => $m["detalle_mercancia"]["peso_tara"],
				"NumPiezas"     => $m["detalle_mercancia"]["num_piezas"],
				];	
			 
			/*if(!isset($data['carta']['mercancias']['ferroviario']['uso']) || !isset($data['carta']['mercancias']['aereo']['uso']) ){
				unset($data_detalle_mercancia['UnidadPeso']);
				unset($data_detalle_mercancia['PesoBruto']);
				
			}*/
			
			//debug($data_detalle_mercancia);
			
			//unset($data_detalle_mercancia['BienesTransp']);
			
			
			$this->satxmlsv33_cargaAtt($detalle_mercancia,$data_detalle_mercancia);
			}
		}
		
		if(isset($data["carta"]["mercancias"]["federal"]["uso"]) && $data["carta"]["mercancias"]["federal"]["uso"] == 1){
			$federal = $this->xml->createElement("cartaporte:AutotransporteFederal");
			$federal = $mercancias->appendChild($federal);
			
			
			$data_federal = [
			"PermSCT" => trim(explode("-",$data['carta']['mercancias']['federal']['PermSCT'])[0]),
			"NumPermisoSCT"     => $data['carta']['mercancias']['federal']['NumPermisoSCT'],
			"NombreAseg"     => $data['carta']['mercancias']['federal']['NombreAseg'],
			"NumPolizaSeguro"     => $data['carta']['mercancias']['federal']['NumPolizaSeguro'],
			];	
			$this->satxmlsv33_cargaAtt($federal,$data_federal);	
			
			$identificacion = $this->xml->createElement("cartaporte:IdentificacionVehicular");
			$identificacion = $federal->appendChild($identificacion);			
			$data_identificacion = [
			"ConfigVehicular" => trim(explode("-",$data['carta']['mercancias']['federal']['identificacion']['ConfigVehicular'])[0]),
			"PlacaVM"     => $data['carta']['mercancias']['federal']['identificacion']['PlacaVM'],
			"AnioModeloVM"     => $data['carta']['mercancias']['federal']['identificacion']['AnioModeloVM'],
			];	
			$this->satxmlsv33_cargaAtt($identificacion,$data_identificacion);	
		
			if($data['carta']['mercancias']['federal']['remolque'][1]['SubTipoRem'] != ""){
			if(count($data['carta']['mercancias']['federal']['remolque'])>0){
			
			$remolques = $this->xml->createElement("cartaporte:Remolques");
			$remolques = $federal->appendChild($remolques);			
				if(isset($data['carta']['mercancias']['federal']['remolque'][1]['placa'])){
						$remolque1 = $this->xml->createElement("cartaporte:Remolque");
						$remolque1 = $remolques->appendChild($remolque1);			
						$data_remolque1 = [
						"SubTipoRem" => trim(explode("-",$data['carta']['mercancias']['federal']['remolque'][1]['SubTipoRem'])[0]),
						"Placa" => $data['carta']['mercancias']['federal']['remolque'][1]['placa'],
						];	
						$this->satxmlsv33_cargaAtt($remolque1,$data_remolque1);	
				}
				
				if(isset($data['carta']['mercancias']['federal']['remolque'][2]['placa'])){
						$remolque2 = $this->xml->createElement("cartaporte:Remolque");
						$remolque2 = $remolques->appendChild($remolque2);			
						$data_remolque2 = [
						"SubTipoRem" => trim(explode("-",$data['carta']['mercancias']['federal']['remolque'][2]['SubTipoRem'])[0]),
						"Placa" => $data['carta']['mercancias']['federal']['remolque'][2]['placa'],
						];	
						$this->satxmlsv33_cargaAtt($remolque2,$data_remolque2);	
				}
			}
			}
			}
			
			if(isset($data["carta"]["mercancias"]["maritimo"]["uso"]) && $data["carta"]["mercancias"]["maritimo"]["uso"] == 1){
			$maritimo = $this->xml->createElement("cartaporte:TransporteMaritimo");
			$maritimo = $mercancias->appendChild($maritimo);
			
			
			$data_maritimo = [
			"PermSCT"=> trim(explode("-",$data['carta']['mercancias']['maritimo']["PermSCT"])[0]),
			"NumPermisoSCT"=>$data['carta']['mercancias']['maritimo']["NumPermisoSCT"],
			"NombreAseg"=>$data['carta']['mercancias']['maritimo']["NombreAseg"],
			"NumPolizaSeguro"=> $data['carta']['mercancias']['maritimo']["NumPolizaSeguro"],
			"TipoEmbarcacion"=>trim(explode("-",$data['carta']['mercancias']['maritimo']["TipoEmbarcacion"])[0]),
			"Matricula"=>$data['carta']['mercancias']['maritimo']["Matricula"],
			"NumeroOMI"=>$data['carta']['mercancias']['maritimo']["NumeroOMI"],
			"AnioEmbarcacion"=>$data['carta']['mercancias']['maritimo']["AnioEmbarcacion"],
			"NombreEmbarc"=>$data['carta']['mercancias']['maritimo']["NombreEmbarc"],
			"NacionalidadEmbarc"=>$data['carta']['mercancias']['maritimo']["NacionalidadEmbarc"],
			"UnidadesDeArqBruto"=>$data['carta']['mercancias']['maritimo']["UnidadesDeArqBruto"],
			"TipoCarga"=>trim(explode("-",$data['carta']['mercancias']['maritimo']["TipoCarga"])[0]),
			"NumCertITC"=>$data['carta']['mercancias']['maritimo']["NumCertITC"],
			"Eslora"=>$data['carta']['mercancias']['maritimo']["Eslora"],
			"Manga"=>$data['carta']['mercancias']['maritimo']["Manga"], 
			"Calado"=>$data['carta']['mercancias']['maritimo']["Calado"],
			"LineaNaviera"=>$data['carta']['mercancias']['maritimo']["LineaNaviera"],
			"NombreAgenteNaviero"=>$data['carta']['mercancias']['maritimo']["NombreAgenteNaviero"],
			"NumAutorizacionNaviero"=>$data['carta']['mercancias']['maritimo']["NumAutorizacionNaviero"],
			//"NumAutorizacionNaviero"=> "SCT418/007/2021",
			"NumViaje"=>$data['carta']['mercancias']['maritimo']["NumViaje"],
			"NumConocEmbarc"=>$data['carta']['mercancias']['maritimo']["NumConocEmbarc"],
			];	
			$this->satxmlsv33_cargaAtt($maritimo,$data_maritimo);
			
			
			
			$m_contenedor = json_decode($data['carta']['inCartaContenendores'], true) ?? [];
			if(count($m_contenedor)> 0){
			foreach($m_contenedor as $c) {
			$contenedor = $this->xml->createElement("cartaporte:Contenedor");
			$contenedor = $maritimo->appendChild($contenedor);
		
			$data_contenedor = [
			"MatriculaContenedor" => $c['matricula'],
			"TipoContenedor"     => trim(explode("-",$c['tipo'])[0]),
			"NumPrecinto"     => $c['numero'],
			];	
			
			$this->satxmlsv33_cargaAtt($contenedor,$data_contenedor);	
			
			}
			
			}
			}
			
			if(isset($data["carta"]["mercancias"]["aereo"]["uso"]) && $data["carta"]["mercancias"]["aereo"]["uso"] == 1){
			$aereo = $this->xml->createElement("cartaporte:TransporteAereo");
			$aereo = $mercancias->appendChild($aereo);
			$data_aereo = [
			"PermSCT"=> isset($data['carta']['mercancias']['aereo']["PermSCT"])?trim(explode("-",$data['carta']['mercancias']['aereo']["PermSCT"])[0]):"",
			"NumPermisoSCT"=>isset($data['carta']['mercancias']['aereo']["NumPermisoSCT"])?$data['carta']['mercancias']['aereo']["NumPermisoSCT"]:"",
			"MatriculaAeronave"=>isset($data['carta']['mercancias']['aereo']["MatriculaAeronave"])?$data['carta']['mercancias']['aereo']["MatriculaAeronave"]:"",
			//"MatriculaAeronave"=>"UP-20101",
			"NombreAseg"=>isset($data['carta']['mercancias']['aereo']["NombreAseg"])?$data['carta']['mercancias']['aereo']["NombreAseg"]:"",
			"NumPolizaSeguro"=> isset($data['carta']['mercancias']['aereo']["NumPolizaSeguro"])?$data['carta']['mercancias']['aereo']["NumPolizaSeguro"]:"",
			"NumeroGuia"=>isset($data['carta']['mercancias']['aereo']["NumeroGuia"])?$data['carta']['mercancias']['aereo']["NumeroGuia"]:"",
			//"NumeroGuia"=>"1234567890123",
			"LugarContrato"=>isset($data['carta']['mercancias']['aereo']["LugarContrato"])?$data['carta']['mercancias']['aereo']["LugarContrato"]:"",
			"RFCTransportista"=> isset($data['carta']['mercancias']['aereo']["RFCTransportista"])?$data['carta']['mercancias']['aereo']["RFCTransportista"]:"",
			//"CodigoTransportista"=>"CA001",
			"CodigoTransportista"=>isset($data['carta']['mercancias']['aereo']["CodigoTransportista"])?trim(explode("-",$data['carta']['mercancias']['aereo']["CodigoTransportista"])[0]):"",
			"NumRegIdTribTranspor"=>isset($data['carta']['mercancias']['aereo']["NumRegIdTribTranspor"])?$data['carta']['mercancias']['aereo']["NumRegIdTribTranspor"]:"",
			"ResidenciaFiscalTranspor"=>isset($data['carta']['mercancias']['aereo']["ResidenciaFiscalTransportista"])?$data['carta']['mercancias']['aereo']["ResidenciaFiscalTransportista"]:"",
			"NombreTransportista"=>isset($data['carta']['mercancias']['aereo']["NombreTransportista"])?$data['carta']['mercancias']['aereo']["NombreTransportista"]:"",
			"RFCEmbarcador"=>isset($data['carta']['mercancias']['aereo']["RFCEmbarcador"])?$data['carta']['mercancias']['aereo']["RFCEmbarcador"]:"",
			"NumRegIdTribEmbarc"=>isset($data['carta']['mercancias']['aereo']["NumRegIdTribEmbarc"])?$data['carta']['mercancias']['aereo']["NumRegIdTribEmbarc"]:"",
			"ResidenciaFiscalEmbarc"=>isset($data['carta']['mercancias']['aereo']["ResidenciaFiscalEmbarc"])?$data['carta']['mercancias']['aereo']["ResidenciaFiscalEmbarc"]:"",
			"NombreEmbarcador"=>isset($data['carta']['mercancias']['aereo']["NombreEmbarcador"])?$data['carta']['mercancias']['aereo']["NombreEmbarcador"]:"",
			];	
			$this->satxmlsv33_cargaAtt($aereo,$data_aereo);
			
			
			}
			
			if(isset($data["carta"]["mercancias"]["ferroviario"]["uso"]) && $data["carta"]["mercancias"]["ferroviario"]["uso"] ==1){
			$ferroveario = $this->xml->createElement("cartaporte:TransporteFerroviario");
			$ferroveario = $mercancias->appendChild($ferroveario);
			$data_ferroveario = [
			"TipoDeServicio"=> trim(explode("-",$data['carta']['mercancias']['ferroviario']["TipoDeServicio"])[0]),
			"NombreAseg"=>$data['carta']['mercancias']['ferroviario']["NombreAseg"],
			"NumPolizaSeguro"=>$data['carta']['mercancias']['ferroviario']["NumPolizaSeguro"],
			"Concesionario"=>$data['carta']['mercancias']['ferroviario']["Concesionario"],
			//"Concesionario"=>"AAA010101AAA",
			];	
			$this->satxmlsv33_cargaAtt($ferroveario,$data_ferroveario);
			
			$d_paso = json_decode($data['carta']['inCartaDerechoPaso'], true) ?? []; 
			if(count($d_paso) >0){
			foreach($d_paso as $cp) {
			$paso = $this->xml->createElement("cartaporte:DerechosDePaso");
			$paso = $ferroveario->appendChild($paso);
		
			$data_paso = [
			"TipoDerechoDePaso" => trim(explode("-",$cp['derecho_de_paso'])[0]),
			"KilometrajePagado"     => $cp['kilometro_pagado'],
			];	
			
			$this->satxmlsv33_cargaAtt($paso,$data_paso);	
			
			}
			}
			
			$carro = $this->xml->createElement("cartaporte:Carro");
			$carro = $ferroveario->appendChild($carro);
		
			$data_carro = [
			"TipoCarro" => trim(explode("-",$data["carta"]["mercancias"]["ferroviario"]["carro"]["TipoCarro"])[0]),
			"MatriculaCarro"     => $data["carta"]["mercancias"]["ferroviario"]["carro"]["MatriculaCarro"],
			"GuiaCarro"     => $data["carta"]["mercancias"]["ferroviario"]["carro"]["GuiaCarro"],
			"ToneladasNetasCarro"     => $data["carta"]["mercancias"]["ferroviario"]["carro"]["ToneladasNetasCarro"],
			];	
			
			$this->satxmlsv33_cargaAtt($carro,$data_carro);	
			
			$c_contenedor = json_decode($data['carta']['inCartaContenedoresCarro'], true) ?? []; 
			if(count($c_contenedor) > 0){
			foreach($c_contenedor as $cc) {
			$carro_contenedor = $this->xml->createElement("cartaporte:Contenedor");
			$carro_contenedor = $carro->appendChild($carro_contenedor);
  		
			$data_contenedor_carro = [
			"TipoContenedor" => trim(explode("-",$cc['tipo'])[0]),
			"PesoContenedorVacio" => $cc['peso'],
			"PesoNetoMercancia" => $cc['peso_neto'],
			];	
			
			$this->satxmlsv33_cargaAtt($carro_contenedor,$data_contenedor_carro);	
			
			}
			}
			
			}
			
			
			if(isset($data["carta"]["figura_transporte"]["aplica"]) && $data["carta"]["figura_transporte"]["aplica"] == 1){
				
			$figura = $this->xml->createElement("cartaporte:FiguraTransporte");
			$figura = $cartaporte->appendChild($figura);
			
			$data_figura = [
			"CveTransporte" => $data['carta']['figura_transporte']['CveTransporte'],
			];				
			$this->satxmlsv33_cargaAtt($figura,$data_figura);	
			}
			
			$d_operadores = json_decode($data['carta']['inCartaOperadores'], true) ?? []; 
			if(count($d_operadores) > 0){
			$operadores = $this->xml->createElement("cartaporte:Operadores");
			$operadores = $figura->appendChild($operadores);
				foreach($d_operadores as $op) {
					$operador = $this->xml->createElement("cartaporte:Operador");
					$operador = $operadores->appendChild($operador);
					//debug($op);
					$data_operador = [
					"RFCOperador" => $op['rfc'],
					
					"NumLicencia" => $op['numLicencia'],
					"NombreOperador" => $op['nombre'],
					"ResidenciaFiscalOperador" => $op['ResidenciaFiscal'],
					];	
				
					$this->satxmlsv33_cargaAtt($operador,$data_operador);
					if(isset($op['direccion']['calle'])){
					$operador_calle = $this->xml->createElement("cartaporte:Domicilio");
					$operador_calle = $operador->appendChild($operador_calle);
					$data_domicilio = [
					"Calle" => $op['direccion']['calle'],
					"NumeroExterior" => $op['direccion']['numero_exterior'],
					"NumeroInterior" => $op['direccion']['numero_interior'],
					"Colonia" => $op['direccion']['colonia'],
					"Localidad" => $op['direccion']['localidad'],
					"Referencia" => $op['direccion']['referencia'],
					"Municipio" => $op['direccion']['municipio'],
					"Estado" => $op['direccion']['estado'],
					"CodigoPostal" => $op['direccion']['codigo_postal'],
					"Pais" => $op['direccion']['pais'],
					];	
					$this->satxmlsv33_cargaAtt($operador_calle,$data_domicilio);
					} 
				
				}
			}
			
			$d_propietarios = json_decode($data['carta']['inCartaPropietarios'], true) ?? []; 
			if(count($d_propietarios) > 0){
				foreach($d_propietarios as $p) {
			
					$propietario = $this->xml->createElement("cartaporte:Propietario");
					$propietario = $figura->appendChild($propietario);
					
					$data_propietario = [
					"RFCPropietario" => $p['rfc'],
					"NombrePropietario" => $p['nombre'],
					"NumRegIdTribPropietario" => $p['NumRegIdTrib'],
					"ResidenciaFiscalPropietario" => $p['ResidenciaFiscal'],
					];	
				
					$this->satxmlsv33_cargaAtt($propietario,$data_propietario);
					if(isset($p['direccion']['calle'])){
					$propietario_calle = $this->xml->createElement("cartaporte:Domicilio");
					$propietario_calle = $propietario->appendChild($propietario_calle);
					
					$data_domicilio = [
					"Calle" => $p['direccion']['calle'],
					"NumeroExterior" => $p['direccion']['numero_exterior'],
					"NumeroInterior" => $p['direccion']['numero_interior'],
					"Colonia" => $p['direccion']['colonia'],
					"Localidad" => $p['direccion']['localidad'],
					"Referencia" => $p['direccion']['referencia'],
					"Municipio" => $p['direccion']['municipio'],
					"Estado" => $p['direccion']['estado'],
					"Pais" => $p['direccion']['pais'],
					"CodigoPostal" => $p['direccion']['codigo_postal'],
					];	
				
					$this->satxmlsv33_cargaAtt($propietario_calle,$data_domicilio);
					} 
				}
			}
					  
			
			$d_arrendatarios = json_decode($data['carta']['inCartaArrendatarios'], true) ?? []; 
			if(count($d_arrendatarios) > 0){
			/*$arrendatarios = $this->xml->createElement("cartaporte:Arrendatarios");
			$arrendatarios = $figura->appendChild($arrendatarios);*/
				foreach($d_arrendatarios as $a) {
			
					$arrendatario = $this->xml->createElement("cartaporte:Arrendatario");
					$arrendatario = $figura->appendChild($arrendatario);
					
					$data_arrendatario = [
					"RFCArrendatario" => $a['rfc'],
					"NombreArrendatario" => $a['nombre'],
					"NumRegIdTribArrendatario" => $a['NumRegIdTrib'],
					"ResidenciaFiscalArrendatario" => $a['ResidenciaFiscal'],
					];	
				
					$this->satxmlsv33_cargaAtt($arrendatario,$data_arrendatario);
					if(isset($a['direccion']['calle'])){
					$arrendador_calle = $this->xml->createElement("cartaporte:Domicilio");
					$arrendador_calle = $arrendatario->appendChild($arrendador_calle);
					
					$data_domicilio = [
					"Calle" => $a['direccion']['calle'],
					"NumeroExterior" => $a['direccion']['numero_exterior'],
					"NumeroInterior" => $a['direccion']['numero_interior'],
					"Colonia" => $a['direccion']['colonia'],
					"Localidad" => $a['direccion']['localidad'],
					"Referencia" => $a['direccion']['referencia'],
					"Municipio" => $a['direccion']['municipio'],
					"Estado" => $a['direccion']['estado'],
					"Pais" => $a['direccion']['pais'],
					"CodigoPostal" => $a['direccion']['codigo_postal'],
					
					];	
				
					$this->satxmlsv33_cargaAtt($arrendador_calle,$data_domicilio);
					} 
				
				}
			}
			
			$d_notificados = json_decode($data['carta']['inCartaNotificados'], true) ?? []; 
			if(count($d_notificados) > 0){
			/*$notificados = $this->xml->createElement("cartaporte:Notificados");
			$notificados = $figura->appendChild($notificados);*/
				foreach($d_notificados as $n) {
			
					$notificado = $this->xml->createElement("cartaporte:Notificado");
					$notificado = $figura->appendChild($notificado);
					
					$data_notificado = [
					"RFCNotificado" => $n['rfc'],
					"NombreNotificado" => $n['nombre'],
					"NumRegIdTribNotificado" => $n['NumRegIdTrib'], 
					"ResidenciaFiscalNotificado" => $n['ResidenciaFiscal'],
					];	
				
					$this->satxmlsv33_cargaAtt($notificado,$data_notificado);
					if(isset($n['direccion']['calle'])){
					$notificado_calle = $this->xml->createElement("cartaporte:Domicilio");
					$notificado_calle = $notificado->appendChild($notificado_calle);
					
					$data_domicilio = [
					"Calle" => $n['direccion']['calle'],
					"NumeroExterior" => $n['direccion']['numero_exterior'],
					"NumeroInterior" => $n['direccion']['numero_interior'],
					"Colonia" => $n['direccion']['colonia'],
					"Localidad" => $n['direccion']['localidad'],
					"Referencia" => $n['direccion']['referencia'],
					"Municipio" => $n['direccion']['municipio'],
					"Estado" => $n['direccion']['estado'],
					"Pais" => $a['direccion']['pais'],
					"CodigoPostal" => $a['direccion']['codigo_postal'],
					];	
				
					$this->satxmlsv33_cargaAtt($notificado_calle,$data_domicilio);
					} 
				}
			}
   } 	
	
	function setCartaPorteLast($data,$complemento){
		
		$complemento = $this->root->appendChild($complemento);
		$cartaporte = $this->xml->createElement("cartaporte:CartaPorte");
		$cartaporte = $complemento->appendChild($cartaporte);
		$carta= [			
			"Version"	=>	"1.0" ,
			"TranspInternac"		=> self::cfdiUtf8Decode($data['carta']['TranspInternac']),
			"EntradaSalidaMerc"		=> $data['carta']['EntradaSalidaMerc'],
			"PaisOrigenDestino"		=> $data['carta']['PaisOrigenDestino'],
			"ViaEntradaSalida"		=> $data['carta']['ViaEntradaSalida'],
			"TotalDistRec"		=> $data['carta']['TotalDistRec'],
		
		];
		
		if($data['carta']['TranspInternac'] == "No"){
			unset($carta['EntradaSalidaMerc']);
			unset($carta['ViaEntradaSalida']);
			
		}
		if(!isset($data['carta']['mercancias']['federal']['uso']) && !isset($data["carta"]["mercancias"]["ferroviario"]["uso"])){
			unset($carta['TotalDistRec']);
		}
		
		$this->satxmlsv33_cargaAtt($cartaporte,$carta);
		
		$ubicaciones = $this->xml->createElement("cartaporte:Ubicaciones");
		$ubicaciones = $cartaporte->appendChild($ubicaciones);
		$u_bicaciones = json_decode($data['carta']['inCartaUbicaciones'], true) ?? []; 
		
	
		if(count($u_bicaciones)> 0 ){ 
		foreach($u_bicaciones as $u) {

			$ubicacion = $this->xml->createElement("cartaporte:Ubicacion"); 
			$ubicacion = $ubicaciones->appendChild($ubicacion);
		
			$data_ubicacion = [
			"TipoEstacion" => trim(explode("-",$u['tipoEstacion'])[0]),
			"DistanciaRecorrida" => $u['distanciaRecorrida'],
			];			
		if(!isset($data['carta']['mercancias']['federal']['uso']) && !isset($data["carta"]["mercancias"]["ferroviario"]["uso"])){
	
		unset($data_ubicacion['DistanciaRecorrida']);
		}
			$this->satxmlsv33_cargaAtt($ubicacion,$data_ubicacion);	
				
				if(!empty($u['origen'])){
				
				$origen = $this->xml->createElement("cartaporte:Origen");
				$origen = $ubicacion->appendChild($origen);
				$data_origen = [
				"IDOrigen" => isset($u['origen']['idOrigen'])?$u['origen']['idOrigen']:"",
				"RFCRemitente"=> isset($u['origen']['rfc'])?$u['origen']['rfc']:"",
				"NombreRemitente"=> isset($u['origen']['nombre'])?$u['origen']['nombre']:"",
				"NumRegIdTrib"=> isset($u['origen']['numRegIdTrib'])?$u['origen']['numRegIdTrib']:"",
				"ResidenciaFiscal"=> isset($u['origen']['residencia'])?$u['origen']['residencia']:"",
				"NumEstacion"=> isset($u['origen']['numeroEstacion'])?trim(explode("-",$u['origen']['numeroEstacion'])[0]):"",
				"NombreEstacion"=> $u['origen']['numeroEstacion'] != ""?self::cfdiUtf8Decode(trim(explode("-",$u['origen']['numeroEstacion'])[1])):"",
				"NavegacionTrafico"=> isset($u['origen']['navegacionTrafico'])?$u['origen']['navegacionTrafico']:"",
				//"FechaHoraSalida"=> isset($u['origen']['fechaHoraSalida'])? str_replace(" ","T",$u['origen']['fechaHoraSalida']):"",
				"FechaHoraSalida"=> isset($u['origen']['fechaHoraSalida'])?$u['origen']['fechaHoraSalida']."T".date("H:m:i"):"",
				];
				
				//debug($data_origen);
				if(isset($u['origen']['rfc'])){
					unset($data_origen['NumRegIdTrib']);
					unset($data_origen['ResidenciaFiscal']);
					
					//unset($data_origen['NavegacionTrafico']);
				}
				
				if(isset($data['carta']['mercancias']['federal']['uso'])){
					unset($data_origen['NumEstacion']);
					unset($data_origen['NombreEstacion']); 
					unset($data_origen['NavegacionTrafico']);
				}
				if(!isset($data['carta']['mercancias']['maritimo']['uso'])){
					unset($data_origen['NavegacionTrafico']);
				}
				
				
				$this->satxmlsv33_cargaAtt($origen,$data_origen);	
				}
				if(!empty($u['destino'])){
				
				$destino = $this->xml->createElement("cartaporte:Destino");
				$destino = $ubicacion->appendChild($destino);
				
				$data_destino = [
				"IDDestino" => isset($u['destino']['idDestino'])?str_replace(" ","",$u['destino']['idDestino']):"",
				"RFCDestinatario"=> isset($u['destino']['rfc'])?$u['destino']['rfc']:"",
				"NombreDestinatario"=> isset($u['destino']['nombre'])?$u['destino']['nombre']:"",
				"NumRegIdTrib"=> isset($u['destino']['numRegIdTrib'])?$u['destino']['numRegIdTrib']:"",
				"ResidenciaFiscal"=> isset($u['destino']['residencia'])?$u['destino']['residencia']:"",
				"NumEstacion"=> isset($u['destino']['numeroEstacion'])?trim(explode("-",$u['destino']['numeroEstacion'])[0]):"",
				"NombreEstacion"=> ($u['destino']['numeroEstacion']!= "")?self::cfdiUtf8Decode(trim(explode("-",$u['destino']['numeroEstacion'])[1])):"",
				"NavegacionTrafico"=> isset($u['destino']['navegacionTrafico'])?$u['destino']['navegacionTrafico']:"",
				"FechaHoraProgLlegada"=> isset($u['destino']['fechaHoraLlegada'])?$u['destino']['fechaHoraLlegada']."T".date("H:m:i"):"",
				];
				
				
				
				if(isset($u['destino']['rfc'])){
					unset($data_destino['NumRegIdTrib']);
					unset($data_destino['ResidenciaFiscal']);
					
					
				}
				
				if(isset($data['carta']['mercancias']['federal']['uso'])){
					unset($data_destino['NumEstacion']);
					unset($data_destino['NombreEstacion']);
					
					
				}
				if(!isset($data['carta']['mercancias']['maritimo']['uso'])){
					unset($data_destino['NavegacionTrafico']);
				}
				
				$this->satxmlsv33_cargaAtt($destino,$data_destino);	
				}
					
				if(!empty($u['domicilio'])){
				
				$domicilio = $this->xml->createElement("cartaporte:Domicilio");
				$domicilio = $ubicacion->appendChild($domicilio);
		
				$data_domicilio = [
				"Calle" => isset($u['domicilio']['calle'])?$u['domicilio']['calle']:"",
				"NumeroExterior"=> isset($u['domicilio']['numeroExterior'])?$u['domicilio']['numeroExterior']:"",
				"NumeroInterior"=> isset($u['domicilio']['numeroInterior'])?$u['domicilio']['numeroInterior']:"",
				"Colonia"=> isset($u['domicilio']['colonia'])?$u['domicilio']['colonia']:"",
				"Localidad"=> isset($u['domicilio']['localidad'])?$u['domicilio']['localidad']:"",
				"Referencia"=> isset($u['domicilio']['referencia'])?$u['domicilio']['referencia']:"",
				"Municipio"=> isset($u['domicilio']['municipio'])?$u['domicilio']['municipio']:"",
				"Estado"=> isset($u['domicilio']['estado'])?$u['domicilio']['estado']:"",
				"Pais"=> isset($u['domicilio']['pais'])?$u['domicilio']['pais']:"",
				"CodigoPostal"=> isset($u['domicilio']['codigopostal'])?$u['domicilio']['codigopostal']:"",
				];
				/*if($data["carta"]["TranspInternac"]=="Sí"){
					unset($data_domicilio['Pais']);
				}*/
				
				$this->satxmlsv33_cargaAtt($domicilio,$data_domicilio);	
				}
		}
	}
		
		$mercancias = $this->xml->createElement("cartaporte:Mercancias");
		$mercancias = $cartaporte->appendChild($mercancias);
		$data_mercancia= [			
			"PesoBrutoTotal"	=>	isset($data['carta']['Mercancias']['PesoBrutoTotal'])?$data['carta']['Mercancias']['PesoBrutoTotal']:"",
			"UnidadPeso"		=> isset($data['carta']['Mercancias']['UnidadPeso'])?trim(explode("-",$data['carta']['Mercancias']['UnidadPeso'])[0]):"",
			"PesoNetoTotal"		=> isset($data['carta']['Mercancias']['PesoNetoTotal'])?$data['carta']['Mercancias']['PesoNetoTotal']:"",
			"NumTotalMercancias"		=> isset($data['carta']['Mercancias']['NumTotalMercancias'])?$data['carta']['Mercancias']['NumTotalMercancias']:"",
			"CargoPorTasacion"		=> isset($data['carta']['Mercancias']['CargoPorTasacion'])?$data['carta']['Mercancias']['CargoPorTasacion']:"",
			];
			
			
		$this->satxmlsv33_cargaAtt($mercancias,$data_mercancia);
		$m_ercancias = json_decode($data['carta']['inCartaMercancias'], true) ?? []; 
		$quitarCTCT =false;
		if(count($m_ercancias)==1 ){
		$quitarCTCT =true;
		}
		foreach($m_ercancias as $m) {
			$mercancia = $this->xml->createElement("cartaporte:Mercancia"); 
			$mercancia = $mercancias->appendChild($mercancia);
			$data_mercancia = [
			"BienesTransp" => $m['BienesTransp'],
			"ClaveSTCC"     => trim(explode("-",$m['ClaveSTCC'])[0]),
			"Descripcion"     => $m['descripcion'],
			"Cantidad"     => $m['Cantidad'],
			"ClaveUnidad"     => trim(explode("-",$m['ClaveUnidad'])[0]),
			"Unidad"     => trim(explode("-",$m['Unidad'])[0]),
			"Dimensiones"     => $m['dimensiones'],
			"MaterialPeligroso"     => $m['materialPeligroso'],
			"CveMaterialPeligroso"     => trim(explode("-",$m['claveMaterialPeligroso'])[0]),
			"Embalaje"     => $m['embalaje'],
			"DescripEmbalaje"     => $m['descripcionEmbalaje'],
			"PesoEnKg"     => $m['PesoEnKg'],
			"ValorMercancia"     => $m['valorMercancia'],
			"Moneda"     => $m['Moneda'],
			"FraccionArancelaria"     => explode("-",$m['FraccionArancelaria'])[0],
			"UUIDComercioExt"     => $m['UuidComercio'],
			];	
			$this->satxmlsv33_cargaAtt($mercancia,$data_mercancia);	
			if($m['transporta'] == 1){
			
			foreach($m['cantidad_transporta'] as $ct){
			$cantidad_transporta = $this->xml->createElement("cartaporte:CantidadTransporta");
			$cantidad_transporta = $mercancia->appendChild($cantidad_transporta);
			$data_mercancia = [
				"Cantidad" => $ct['cantidad'],
				"IDOrigen" => $ct['idOrigen'],
				"IDDestino"     => $ct['idDestino'],
				"CvesTransporte"     => $ct['claveTransporte'],
			];	
			
			if($quitarCTCT == true){
			unset($data_mercancia['CvesTransporte']);
			}
			$this->satxmlsv33_cargaAtt($cantidad_transporta,$data_mercancia);
			}
			}
		
			if($m['detalles']==1 && $m["detalle_mercancia"]["peso_bruto"]!=""){
			$detalle_mercancia = $this->xml->createElement("cartaporte20:DetalleMercancia");
			$detalle_mercancia = $mercancia->appendChild($detalle_mercancia);
			
			$data_detalle_mercancia = [
				"UnidadPeso" => trim(explode("-",$m["detalle_mercancia"]["unidad_peso"])[0]),
				"PesoBruto" => $m["detalle_mercancia"]["peso_bruto"],
				"PesoNeto"     => $m["detalle_mercancia"]["peso_neto"],
				"PesoTara"     => $m["detalle_mercancia"]["peso_tara"],
				"NumPiezas"     => $m["detalle_mercancia"]["num_piezas"],
				];	
			 
			/*if(!isset($data['carta']['mercancias']['ferroviario']['uso']) || !isset($data['carta']['mercancias']['aereo']['uso']) ){
				unset($data_detalle_mercancia['UnidadPeso']);
				unset($data_detalle_mercancia['PesoBruto']);
				
			}*/
			
			//debug($data_detalle_mercancia);
			
			//unset($data_detalle_mercancia['BienesTransp']);
			
			
			$this->satxmlsv33_cargaAtt($detalle_mercancia,$data_detalle_mercancia);
			}
		}
		
		if(isset($data["carta"]["mercancias"]["federal"]["uso"]) && $data["carta"]["mercancias"]["federal"]["uso"] == 1){
			$federal = $this->xml->createElement("cartaporte:AutotransporteFederal");
			$federal = $mercancias->appendChild($federal);
			
			
			$data_federal = [
			"PermSCT" => trim(explode("-",$data['carta']['mercancias']['federal']['PermSCT'])[0]),
			"NumPermisoSCT"     => $data['carta']['mercancias']['federal']['NumPermisoSCT'],
			"NombreAseg"     => $data['carta']['mercancias']['federal']['NombreAseg'],
			"NumPolizaSeguro"     => $data['carta']['mercancias']['federal']['NumPolizaSeguro'],
			];	
			$this->satxmlsv33_cargaAtt($federal,$data_federal);	
			
			$identificacion = $this->xml->createElement("cartaporte:IdentificacionVehicular");
			$identificacion = $federal->appendChild($identificacion);			
			$data_identificacion = [
			"ConfigVehicular" => trim(explode("-",$data['carta']['mercancias']['federal']['identificacion']['ConfigVehicular'])[0]),
			"PlacaVM"     => $data['carta']['mercancias']['federal']['identificacion']['PlacaVM'],
			"AnioModeloVM"     => $data['carta']['mercancias']['federal']['identificacion']['AnioModeloVM'],
			];	
			$this->satxmlsv33_cargaAtt($identificacion,$data_identificacion);	
		
			if($data['carta']['mercancias']['federal']['remolque'][1]['SubTipoRem'] != "" && $data['carta']['mercancias']['federal']['remolque'][1]['placa'] !=""){
			if(count($data['carta']['mercancias']['federal']['remolque'])>0){
			
			$remolques = $this->xml->createElement("cartaporte:Remolques");
			$remolques = $federal->appendChild($remolques);			
				if(isset($data['carta']['mercancias']['federal']['remolque'][1]['placa']) && $data['carta']['mercancias']['federal']['remolque'][1]['SubTipoRem'] != ""){
						$remolque1 = $this->xml->createElement("cartaporte:Remolque");
						$remolque1 = $remolques->appendChild($remolque1);			
						$data_remolque1 = [
						"SubTipoRem" => trim(explode("-",$data['carta']['mercancias']['federal']['remolque'][1]['SubTipoRem'])[0]),
						"Placa" => $data['carta']['mercancias']['federal']['remolque'][1]['placa'],
						];	
						$this->satxmlsv33_cargaAtt($remolque1,$data_remolque1);	
				}
				
				if(isset($data['carta']['mercancias']['federal']['remolque'][2]['placa']) && $data['carta']['mercancias']['federal']['remolque'][2]['placa'] != "" && isset($data['carta']['mercancias']['federal']['remolque'][2]['SubTipoRem']) && $data['carta']['mercancias']['federal']['remolque'][2]['SubTipoRem']!= ""){
						$remolque2 = $this->xml->createElement("cartaporte:Remolque");
						$remolque2 = $remolques->appendChild($remolque2);			
						$data_remolque2 = [
						"SubTipoRem" => trim(explode("-",$data['carta']['mercancias']['federal']['remolque'][2]['SubTipoRem'])[0]),
						"Placa" => $data['carta']['mercancias']['federal']['remolque'][2]['placa'],
						];	
						$this->satxmlsv33_cargaAtt($remolque2,$data_remolque2);	
				}
			}
			}
			}
			
			if(isset($data["carta"]["mercancias"]["maritimo"]["uso"]) && $data["carta"]["mercancias"]["maritimo"]["uso"] == 1){
			$maritimo = $this->xml->createElement("cartaporte:TransporteMaritimo");
			$maritimo = $mercancias->appendChild($maritimo);
			
			
			$data_maritimo = [
			"PermSCT"=> trim(explode("-",$data['carta']['mercancias']['maritimo']["PermSCT"])[0]),
			"NumPermisoSCT"=>$data['carta']['mercancias']['maritimo']["NumPermisoSCT"],
			"NombreAseg"=>$data['carta']['mercancias']['maritimo']["NombreAseg"],
			"NumPolizaSeguro"=> $data['carta']['mercancias']['maritimo']["NumPolizaSeguro"],
			"TipoEmbarcacion"=>trim(explode("-",$data['carta']['mercancias']['maritimo']["TipoEmbarcacion"])[0]),
			"Matricula"=>$data['carta']['mercancias']['maritimo']["Matricula"],
			"NumeroOMI"=>$data['carta']['mercancias']['maritimo']["NumeroOMI"],
			"AnioEmbarcacion"=>$data['carta']['mercancias']['maritimo']["AnioEmbarcacion"],
			"NombreEmbarc"=>$data['carta']['mercancias']['maritimo']["NombreEmbarc"],
			"NacionalidadEmbarc"=>$data['carta']['mercancias']['maritimo']["NacionalidadEmbarc"],
			"UnidadesDeArqBruto"=>$data['carta']['mercancias']['maritimo']["UnidadesDeArqBruto"],
			"TipoCarga"=>trim(explode("-",$data['carta']['mercancias']['maritimo']["TipoCarga"])[0]),
			"NumCertITC"=>$data['carta']['mercancias']['maritimo']["NumCertITC"],
			"Eslora"=>$data['carta']['mercancias']['maritimo']["Eslora"],
			"Manga"=>$data['carta']['mercancias']['maritimo']["Manga"], 
			"Calado"=>$data['carta']['mercancias']['maritimo']["Calado"],
			"LineaNaviera"=>$data['carta']['mercancias']['maritimo']["LineaNaviera"],
			"NombreAgenteNaviero"=>$data['carta']['mercancias']['maritimo']["NombreAgenteNaviero"],
			"NumAutorizacionNaviero"=>$data['carta']['mercancias']['maritimo']["NumAutorizacionNaviero"],
			//"NumAutorizacionNaviero"=> "SCT418/007/2021",
			"NumViaje"=>$data['carta']['mercancias']['maritimo']["NumViaje"],
			"NumConocEmbarc"=>$data['carta']['mercancias']['maritimo']["NumConocEmbarc"],
			];	
			$this->satxmlsv33_cargaAtt($maritimo,$data_maritimo);
			
			
			
			$m_contenedor = json_decode($data['carta']['inCartaContenendores'], true) ?? [];
			if(count($m_contenedor)> 0){
			foreach($m_contenedor as $c) {
			$contenedor = $this->xml->createElement("cartaporte:Contenedor");
			$contenedor = $maritimo->appendChild($contenedor);
		
			$data_contenedor = [
			"MatriculaContenedor" => $c['matricula'],
			"TipoContenedor"     => trim(explode("-",$c['tipo'])[0]),
			"NumPrecinto"     => $c['numero'],
			];	
			
			$this->satxmlsv33_cargaAtt($contenedor,$data_contenedor);	
			
			}
			
			}
			}
			
			if(isset($data["carta"]["mercancias"]["aereo"]["uso"]) && $data["carta"]["mercancias"]["aereo"]["uso"] == 1){
			$aereo = $this->xml->createElement("cartaporte:TransporteAereo");
			$aereo = $mercancias->appendChild($aereo);
			$data_aereo = [
			"PermSCT"=> isset($data['carta']['mercancias']['aereo']["PermSCT"])?trim(explode("-",$data['carta']['mercancias']['aereo']["PermSCT"])[0]):"",
			"NumPermisoSCT"=>isset($data['carta']['mercancias']['aereo']["NumPermisoSCT"])?$data['carta']['mercancias']['aereo']["NumPermisoSCT"]:"",
			"MatriculaAeronave"=>isset($data['carta']['mercancias']['aereo']["MatriculaAeronave"])?$data['carta']['mercancias']['aereo']["MatriculaAeronave"]:"",
			//"MatriculaAeronave"=>"UP-20101",
			"NombreAseg"=>isset($data['carta']['mercancias']['aereo']["NombreAseg"])?$data['carta']['mercancias']['aereo']["NombreAseg"]:"",
			"NumPolizaSeguro"=> isset($data['carta']['mercancias']['aereo']["NumPolizaSeguro"])?$data['carta']['mercancias']['aereo']["NumPolizaSeguro"]:"",
			"NumeroGuia"=>isset($data['carta']['mercancias']['aereo']["NumeroGuia"])?$data['carta']['mercancias']['aereo']["NumeroGuia"]:"",
			//"NumeroGuia"=>"1234567890123",
			"LugarContrato"=>isset($data['carta']['mercancias']['aereo']["LugarContrato"])?$data['carta']['mercancias']['aereo']["LugarContrato"]:"",
			"RFCTransportista"=> isset($data['carta']['mercancias']['aereo']["RFCTransportista"])?$data['carta']['mercancias']['aereo']["RFCTransportista"]:"",
			//"CodigoTransportista"=>"CA001",
			"CodigoTransportista"=>isset($data['carta']['mercancias']['aereo']["CodigoTransportista"])?trim(explode("-",$data['carta']['mercancias']['aereo']["CodigoTransportista"])[0]):"",
			"NumRegIdTribTranspor"=>isset($data['carta']['mercancias']['aereo']["NumRegIdTribTranspor"])?$data['carta']['mercancias']['aereo']["NumRegIdTribTranspor"]:"",
			"ResidenciaFiscalTranspor"=>isset($data['carta']['mercancias']['aereo']["ResidenciaFiscalTransportista"])?$data['carta']['mercancias']['aereo']["ResidenciaFiscalTransportista"]:"",
			"NombreTransportista"=>isset($data['carta']['mercancias']['aereo']["NombreTransportista"])?$data['carta']['mercancias']['aereo']["NombreTransportista"]:"",
			"RFCEmbarcador"=>isset($data['carta']['mercancias']['aereo']["RFCEmbarcador"])?$data['carta']['mercancias']['aereo']["RFCEmbarcador"]:"",
			"NumRegIdTribEmbarc"=>isset($data['carta']['mercancias']['aereo']["NumRegIdTribEmbarc"])?$data['carta']['mercancias']['aereo']["NumRegIdTribEmbarc"]:"",
			"ResidenciaFiscalEmbarc"=>isset($data['carta']['mercancias']['aereo']["ResidenciaFiscalEmbarc"])?$data['carta']['mercancias']['aereo']["ResidenciaFiscalEmbarc"]:"",
			"NombreEmbarcador"=>isset($data['carta']['mercancias']['aereo']["NombreEmbarcador"])?$data['carta']['mercancias']['aereo']["NombreEmbarcador"]:"",
			];	
			$this->satxmlsv33_cargaAtt($aereo,$data_aereo);
			
			
			}
			
			if(isset($data["carta"]["mercancias"]["ferroviario"]["uso"]) && $data["carta"]["mercancias"]["ferroviario"]["uso"] ==1){
			$ferroveario = $this->xml->createElement("cartaporte:TransporteFerroviario");
			$ferroveario = $mercancias->appendChild($ferroveario);
			$data_ferroveario = [
			"TipoDeServicio"=> trim(explode("-",$data['carta']['mercancias']['ferroviario']["TipoDeServicio"])[0]),
			"NombreAseg"=>$data['carta']['mercancias']['ferroviario']["NombreAseg"],
			"NumPolizaSeguro"=>$data['carta']['mercancias']['ferroviario']["NumPolizaSeguro"],
			"Concesionario"=>$data['carta']['mercancias']['ferroviario']["Concesionario"],
			//"Concesionario"=>"AAA010101AAA",
			];	
			$this->satxmlsv33_cargaAtt($ferroveario,$data_ferroveario);
			
			$d_paso = json_decode($data['carta']['inCartaDerechoPaso'], true) ?? []; 
			if(count($d_paso) >0){
			foreach($d_paso as $cp) {
			$paso = $this->xml->createElement("cartaporte:DerechosDePaso");
			$paso = $ferroveario->appendChild($paso);
		
			$data_paso = [
			"TipoDerechoDePaso" => trim(explode("-",$cp['derecho_de_paso'])[0]),
			"KilometrajePagado"     => $cp['kilometro_pagado'],
			];	
			
			$this->satxmlsv33_cargaAtt($paso,$data_paso);	
			
			}
			}
			
			$carro = $this->xml->createElement("cartaporte:Carro");
			$carro = $ferroveario->appendChild($carro);
		
			$data_carro = [
			"TipoCarro" => trim(explode("-",$data["carta"]["mercancias"]["ferroviario"]["carro"]["TipoCarro"])[0]),
			"MatriculaCarro"     => $data["carta"]["mercancias"]["ferroviario"]["carro"]["MatriculaCarro"],
			"GuiaCarro"     => $data["carta"]["mercancias"]["ferroviario"]["carro"]["GuiaCarro"],
			"ToneladasNetasCarro"     => $data["carta"]["mercancias"]["ferroviario"]["carro"]["ToneladasNetasCarro"],
			];	
			
			$this->satxmlsv33_cargaAtt($carro,$data_carro);	
			
			$c_contenedor = json_decode($data['carta']['inCartaContenedoresCarro'], true) ?? []; 
			if(count($c_contenedor) > 0){
			foreach($c_contenedor as $cc) {
			$carro_contenedor = $this->xml->createElement("cartaporte:Contenedor");
			$carro_contenedor = $carro->appendChild($carro_contenedor);
  		
			$data_contenedor_carro = [
			"TipoContenedor" => trim(explode("-",$cc['tipo'])[0]),
			"PesoContenedorVacio" => $cc['peso'],
			"PesoNetoMercancia" => $cc['peso_neto'],
			];	
			
			$this->satxmlsv33_cargaAtt($carro_contenedor,$data_contenedor_carro);	
			
			}
			}
			
			}
			
			
			if(isset($data["carta"]["figura_transporte"]["aplica"]) && $data["carta"]["figura_transporte"]["aplica"] == 1){
				
			$figura = $this->xml->createElement("cartaporte:FiguraTransporte");
			$figura = $cartaporte->appendChild($figura);
			
			$data_figura = [
			"CveTransporte" => $data['carta']['figura_transporte']['CveTransporte'],
			];				
			$this->satxmlsv33_cargaAtt($figura,$data_figura);	
			}
			
			$d_operadores = json_decode($data['carta']['inCartaOperadores'], true) ?? []; 
			if(count($d_operadores) > 0){
			$operadores = $this->xml->createElement("cartaporte:Operadores");
			$operadores = $figura->appendChild($operadores);
				foreach($d_operadores as $op) {
					$operador = $this->xml->createElement("cartaporte:Operador");
					$operador = $operadores->appendChild($operador);
					//debug($op);
					$data_operador = [
					"RFCOperador" => $op['rfc'],
					
					"NumLicencia" => $op['numLicencia'],
					"NombreOperador" => $op['nombre'],
					"ResidenciaFiscalOperador" => $op['ResidenciaFiscal'],
					];	
				
					$this->satxmlsv33_cargaAtt($operador,$data_operador);
					if(isset($op['direccion']['calle'])){
					$operador_calle = $this->xml->createElement("cartaporte:Domicilio");
					$operador_calle = $operador->appendChild($operador_calle);
					$data_domicilio = [
					"Calle" => $op['direccion']['calle'],
					"NumeroExterior" => $op['direccion']['numero_exterior'],
					"NumeroInterior" => $op['direccion']['numero_interior'],
					"Colonia" => $op['direccion']['colonia'],
					"Localidad" => $op['direccion']['localidad'],
					"Referencia" => $op['direccion']['referencia'],
					"Municipio" => $op['direccion']['municipio'],
					"Estado" => $op['direccion']['estado'],
					"CodigoPostal" => $op['direccion']['codigo_postal'],
					"Pais" => $op['direccion']['pais'],
					];	
					$this->satxmlsv33_cargaAtt($operador_calle,$data_domicilio);
					} 
				
				}
			}
			
			$d_propietarios = json_decode($data['carta']['inCartaPropietarios'], true) ?? []; 
			if(count($d_propietarios) > 0){
				foreach($d_propietarios as $p) {
			
					$propietario = $this->xml->createElement("cartaporte:Propietario");
					$propietario = $figura->appendChild($propietario);
					
					$data_propietario = [
					"RFCPropietario" => $p['rfc'],
					"NombrePropietario" => $p['nombre'],
					"NumRegIdTribPropietario" => $p['NumRegIdTrib'],
					"ResidenciaFiscalPropietario" => $p['ResidenciaFiscal'],
					];	
				
					$this->satxmlsv33_cargaAtt($propietario,$data_propietario);
					if(isset($p['direccion']['calle'])){
					$propietario_calle = $this->xml->createElement("cartaporte:Domicilio");
					$propietario_calle = $propietario->appendChild($propietario_calle);
					
					$data_domicilio = [
					"Calle" => $p['direccion']['calle'],
					"NumeroExterior" => $p['direccion']['numero_exterior'],
					"NumeroInterior" => $p['direccion']['numero_interior'],
					"Colonia" => $p['direccion']['colonia'],
					"Localidad" => $p['direccion']['localidad'],
					"Referencia" => $p['direccion']['referencia'],
					"Municipio" => $p['direccion']['municipio'],
					"Estado" => $p['direccion']['estado'],
					"Pais" => $p['direccion']['pais'],
					"CodigoPostal" => $p['direccion']['codigo_postal'],
					];	
				
					$this->satxmlsv33_cargaAtt($propietario_calle,$data_domicilio);
					} 
				}
			}
					  
			
			$d_arrendatarios = json_decode($data['carta']['inCartaArrendatarios'], true) ?? []; 
			if(count($d_arrendatarios) > 0){
			/*$arrendatarios = $this->xml->createElement("cartaporte:Arrendatarios");
			$arrendatarios = $figura->appendChild($arrendatarios);*/
				foreach($d_arrendatarios as $a) {
			
					$arrendatario = $this->xml->createElement("cartaporte:Arrendatario");
					$arrendatario = $figura->appendChild($arrendatario);
					
					$data_arrendatario = [
					"RFCArrendatario" => $a['rfc'],
					"NombreArrendatario" => $a['nombre'],
					"NumRegIdTribArrendatario" => $a['NumRegIdTrib'],
					"ResidenciaFiscalArrendatario" => $a['ResidenciaFiscal'],
					];	
				
					$this->satxmlsv33_cargaAtt($arrendatario,$data_arrendatario);
					if(isset($a['direccion']['calle'])){
					$arrendador_calle = $this->xml->createElement("cartaporte:Domicilio");
					$arrendador_calle = $arrendatario->appendChild($arrendador_calle);
					
					$data_domicilio = [
					"Calle" => $a['direccion']['calle'],
					"NumeroExterior" => $a['direccion']['numero_exterior'],
					"NumeroInterior" => $a['direccion']['numero_interior'],
					"Colonia" => $a['direccion']['colonia'],
					"Localidad" => $a['direccion']['localidad'],
					"Referencia" => $a['direccion']['referencia'],
					"Municipio" => $a['direccion']['municipio'],
					"Estado" => $a['direccion']['estado'],
					"Pais" => $a['direccion']['pais'],
					"CodigoPostal" => $a['direccion']['codigo_postal'],
					
					];	
				
					$this->satxmlsv33_cargaAtt($arrendador_calle,$data_domicilio);
					} 
				
				}
			}
			
			$d_notificados = json_decode($data['carta']['inCartaNotificados'], true) ?? []; 
			if(count($d_notificados) > 0){
			/*$notificados = $this->xml->createElement("cartaporte:Notificados");
			$notificados = $figura->appendChild($notificados);*/
				foreach($d_notificados as $n) {
			
					$notificado = $this->xml->createElement("cartaporte:Notificado");
					$notificado = $figura->appendChild($notificado);
					
					$data_notificado = [
					"RFCNotificado" => $n['rfc'],
					"NombreNotificado" => $n['nombre'],
					"NumRegIdTribNotificado" => $n['NumRegIdTrib'], 
					"ResidenciaFiscalNotificado" => $n['ResidenciaFiscal'],
					];	
				
					$this->satxmlsv33_cargaAtt($notificado,$data_notificado);
					if(isset($n['direccion']['calle'])){
					$notificado_calle = $this->xml->createElement("cartaporte:Domicilio");
					$notificado_calle = $notificado->appendChild($notificado_calle);
					
					$data_domicilio = [
					"Calle" => $n['direccion']['calle'],
					"NumeroExterior" => $n['direccion']['numero_exterior'],
					"NumeroInterior" => $n['direccion']['numero_interior'],
					"Colonia" => $n['direccion']['colonia'],
					"Localidad" => $n['direccion']['localidad'],
					"Referencia" => $n['direccion']['referencia'],
					"Municipio" => $n['direccion']['municipio'],
					"Estado" => $n['direccion']['estado'],
					"Pais" => $a['direccion']['pais'],
					"CodigoPostal" => $a['direccion']['codigo_postal'],
					];	
				
					$this->satxmlsv33_cargaAtt($notificado_calle,$data_domicilio);
					} 
				}
			}
   }
   
   
   function setCartaPorte9000($data,$complemento){
		
		$complemento = $this->root->appendChild($complemento);
		$cartaporte = $this->xml->createElement("cartaporte20:CartaPorte");
		$cartaporte = $complemento->appendChild($cartaporte);
		$carta= [			
			"Version"	=>	"2.0" ,
			"TranspInternac"		=> self::cfdiUtf8Decode($data['carta']['TranspInternac']),
			"EntradaSalidaMerc"		=> $data['carta']['EntradaSalidaMerc'],
			"PaisOrigenDestino"		=> $data['carta']['PaisOrigenDestino'],
			"ViaEntradaSalida"		=> $data['carta']['ViaEntradaSalida'],
			"TotalDistRec"		=> $data['carta']['TotalDistRec'],
		
		];
		
		if($data['carta']['TranspInternac'] == "No"){
			unset($carta['EntradaSalidaMerc']);
			unset($carta['ViaEntradaSalida']);
			unset($carta['PaisOrigenDestino']);
			
		}
		if(!isset($data['carta']['mercancias']['federal']['uso']) && !isset($data["carta"]["mercancias"]["ferroviario"]["uso"])){
			unset($carta['TotalDistRec']);
		}
		
		$this->satxmlsv33_cargaAtt($cartaporte,$carta);
		
		$ubicaciones = $this->xml->createElement("cartaporte20:Ubicaciones");
		$ubicaciones = $cartaporte->appendChild($ubicaciones);
		$u_bicaciones = json_decode($data['carta']['inCartaUbicaciones'], true) ?? []; 
		
	
		if(count($u_bicaciones)> 0 ){ 
		foreach($u_bicaciones as $u) {
			$ubicacion = $this->xml->createElement("cartaporte20:Ubicacion"); 
			$ubicacion = $ubicaciones->appendChild($ubicacion);
		
		$tmpnombre_estacion = ($u['NumEstacion'] !="")?self::cfdiUtf8Decode(trim(explode("-",$u['NumEstacion'])[1])):"";
			$data_ubicacion = [
				"TipoUbicacion"=>$u["TipoUbicacion"],
				"IDUbicacion"=>$u["IDUbicacion"],
				"RFCRemitenteDestinatario"=>$u["RFCRemitenteDestinatario"],
				"NombreRemitenteDestinatario"=>$u["NombreRemitenteDestinatario"],
				"NumRegIdTrib"=>$u["NumRegIdTrib"],
				"ResidenciaFiscal"=>$u["ResidenciaFiscal"],
				//"NumEstacion"=>"V0102",
				//"NombreEstacion"=>$u["NavegacionTrafico"],
				"NumEstacion"=> isset($u['NumEstacion']) ? trim(explode("-", $u['NumEstacion'])[0]) : "",
				//"NombreEstacion"=> "SAN LORENZO",
				"NombreEstacion"=> isset($u['NombreEstacion']) && $u['NombreEstacion']!= ""?trim($u["NombreEstacion"]):$tmpnombre_estacion,
				"NavegacionTrafico"=>$u["NavegacionTrafico"],
				"FechaHoraSalidaLlegada"=>$u["FechaHoraSalidaLlegada"]."T".date("H:m:i"),
				"TipoEstacion"=>isset($u['TipoEstacion'])?trim(explode("-",$u['TipoEstacion'])[0]):"",
				//"TipoEstacion"=>$u["TipoEstacion"],
				"DistanciaRecorrida"=>$u["DistanciaRecorrida"],
			];			
			
			if($u["RFCRemitenteDestinatario"] != "" && $u["RFCRemitenteDestinatario"]!="XEXX010101000"){
			unset($data_ubicacion['NumRegIdTrib']);	
			unset($data_ubicacion['ResidenciaFiscal']);
			}
			
			if($u["TipoUbicacion"] == "Origen"){
				unset($data_ubicacion['DistanciaRecorrida']);
			}
			if(!isset($data['carta']['mercancias']['federal']['uso']) && !isset($data["carta"]["mercancias"]["ferroviario"]["uso"])){
				unset($data_ubicacion['DistanciaRecorrida']);
			}
			$this->satxmlsv33_cargaAtt($ubicacion,$data_ubicacion);	
					
				if(!empty($u['domicilio'])){
				
				$domicilio = $this->xml->createElement("cartaporte20:Domicilio");
				$domicilio = $ubicacion->appendChild($domicilio);
		
				$data_domicilio = [
				"Calle" => isset($u['domicilio']['calle'])?$u['domicilio']['calle']:"",
				"NumeroExterior"=> isset($u['domicilio']['numeroExterior'])?$u['domicilio']['numeroExterior']:"",
				"NumeroInterior"=> isset($u['domicilio']['numeroInterior'])?$u['domicilio']['numeroInterior']:"",
				"Colonia"=> isset($u['domicilio']['colonia'])?$u['domicilio']['colonia']:"",
				"Localidad"=> isset($u['domicilio']['localidad'])?$u['domicilio']['localidad']:"",
				"Referencia"=> isset($u['domicilio']['referencia'])?$u['domicilio']['referencia']:"",
				"Municipio"=> isset($u['domicilio']['municipio'])?$u['domicilio']['municipio']:"",
				"Estado"=> isset($u['domicilio']['estado'])?$u['domicilio']['estado']:"",
				"Pais"=> isset($u['domicilio']['pais'])?$u['domicilio']['pais']:"",
				"CodigoPostal"=> isset($u['domicilio']['codigopostal'])?$u['domicilio']['codigopostal']:"",
				];
				/*if($data["carta"]["TranspInternac"]=="Sí"){
					unset($data_domicilio['Pais']);
				}*/
				if($data_domicilio['Calle'] =="STREET"){
				$data_domicilio['Colonia'] = "akhsl demoa";
				}
				
				$this->satxmlsv33_cargaAtt($domicilio,$data_domicilio);	
				}
		}
	}
	
	
	/****valicacion*****/
	$tiposClvs = 0;
		if(isset($data["carta"]["mercancias"]["federal"]["uso"])){
		$tiposClvs++;
		}
		if(isset($data["carta"]["mercancias"]["maritimo"]["uso"])){
		$tiposClvs++;
		}
		
		if(isset($data["carta"]["mercancias"]["aereo"]["uso"])){
		$tiposClvs++;
		}
		
		if(isset($data["carta"]["mercancias"]["ferroviario"]["uso"])){
		$tiposClvs++;
		}
		
		
		
		$mercancias = $this->xml->createElement("cartaporte20:Mercancias");
		$mercancias = $cartaporte->appendChild($mercancias);
		$data_mercancia= [			
			"PesoBrutoTotal"	=>	isset($data['carta']['Mercancias']['PesoBrutoTotal'])?$data['carta']['Mercancias']['PesoBrutoTotal']:"",
			"UnidadPeso"		=> isset($data['carta']['Mercancias']['UnidadPeso'])?trim(explode("-",$data['carta']['Mercancias']['UnidadPeso'])[0]):"",
			"PesoNetoTotal"		=> isset($data['carta']['Mercancias']['PesoNetoTotal'])?$data['carta']['Mercancias']['PesoNetoTotal']:"",
			"NumTotalMercancias"		=> isset($data['carta']['Mercancias']['NumTotalMercancias'])?$data['carta']['Mercancias']['NumTotalMercancias']:"",
			"CargoPorTasacion"		=> isset($data['carta']['Mercancias']['CargoPorTasacion'])?$data['carta']['Mercancias']['CargoPorTasacion']:"",
			];
			
			
		$this->satxmlsv33_cargaAtt($mercancias,$data_mercancia);
		$m_ercancias = json_decode($data['carta']['inCartaMercancias'], true) ?? []; 
		$quitarCTCT =false;
		if(count($m_ercancias)==1 ){
		$quitarCTCT =true;
		}
		foreach($m_ercancias as $m) {
			
			
			$mercancia = $this->xml->createElement("cartaporte20:Mercancia"); 
			$mercancia = $mercancias->appendChild($mercancia);
			$data_mercancia = [
			"BienesTransp" => $m['BienesTransp'],
			"ClaveSTCC"     => trim(explode("-",$m['ClaveSTCC'])[0]),
			"Descripcion"     => $m['descripcion'],
			"Cantidad"     => $m['Cantidad'],
			"ClaveUnidad"     => trim(explode("-",$m['ClaveUnidad'])[0]),
			"Unidad"     => trim(explode("-",$m['Unidad'])[0]),
			"Dimensiones"     => $m['dimensiones'],
			"MaterialPeligroso"     => self::cfdiUtf8Decode($m['materialPeligroso']),
			"CveMaterialPeligroso"     => trim(explode("-",$m['claveMaterialPeligroso'])[0]),
			"Embalaje"     => trim(explode("-",$m['embalaje'])[0]),
			"DescripEmbalaje"     => $m['descripcionEmbalaje'],
			"PesoEnKg"     => $m['pesoKg'],
			"ValorMercancia"     => $m['valorMercancia'],
			"Moneda"     => $m['Moneda'],
			"FraccionArancelaria"     => explode("-",$m['FraccionArancelaria'])[0],
			"UUIDComercioExt"     => $m['UuidComercio'],
			];	
			
			if($data_mercancia["MaterialPeligroso"] =="" && $data_mercancia["CveMaterialPeligroso"]==""){
				unset($data_mercancia["MaterialPeligroso"]);
				unset($data_mercancia["CveMaterialPeligroso"]);
			}
			$this->satxmlsv33_cargaAtt($mercancia,$data_mercancia);	
			
			if(isset($m['pedimentos'])){
			foreach($m['pedimentos'] as $p){
			$pedimento = $this->xml->createElement("cartaporte20:Pedimentos");
			$pedimento = $mercancia->appendChild($pedimento);			
			$data_pedimento = [
			"Pedimento" => $p["pedimento"],
			];	
			$this->satxmlsv33_cargaAtt($pedimento,$data_pedimento);
			}
			}
			
			if(isset($m['guias'])){
			
			foreach($m['guias'] as $g){
			$guia = $this->xml->createElement("cartaporte20:GuiasIdentificacion");
			$guia = $mercancia->appendChild($guia);			
			$data_guia = [
			"NumeroGuiaIdentificacion"=>$g["numero"],
			"DescripGuiaIdentificacion"=>$g["descripcion"],
			"PesoGuiaIdentificacion"=>$g["peso"],
			];	
			$this->satxmlsv33_cargaAtt($guia,$data_guia);
			}
			}
			
			
			
			if($m['transporta'] == 1){
			
			foreach($m['cantidad_transporta'] as $ct){
			$cantidad_transporta = $this->xml->createElement("cartaporte20:CantidadTransporta");
			$cantidad_transporta = $mercancia->appendChild($cantidad_transporta);
			$data_mercancia = [
				"Cantidad" => $ct['cantidad'],
				"IDOrigen" => $ct['idOrigen'],
				"IDDestino"     => $ct['idDestino'],
				"CvesTransporte"     => $ct['claveTransporte'],
			];	
			
			if($quitarCTCT == true || $tiposClvs <= 1){
			unset($data_mercancia['CvesTransporte']);
			}
			$this->satxmlsv33_cargaAtt($cantidad_transporta,$data_mercancia);
			}
			}
		
			if($m['detalles']==1 && $m["detalle_mercancia"]["peso_bruto"]!=""){
			$detalle_mercancia = $this->xml->createElement("cartaporte20:DetalleMercancia");
			$detalle_mercancia = $mercancia->appendChild($detalle_mercancia);
			
			$data_detalle_mercancia = [
				"UnidadPesoMerc" => trim(explode("-",$m["detalle_mercancia"]["unidad_peso"])[0]),
				"PesoBruto" => $m["detalle_mercancia"]["peso_bruto"],
				"PesoNeto"     => $m["detalle_mercancia"]["peso_neto"],
				"PesoTara"     => $m["detalle_mercancia"]["peso_tara"],
				"NumPiezas"     => $m["detalle_mercancia"]["num_piezas"],
				];	
			 
			/*if(!isset($data['carta']['mercancias']['ferroviario']['uso']) || !isset($data['carta']['mercancias']['aereo']['uso']) ){
				unset($data_detalle_mercancia['UnidadPeso']);
				unset($data_detalle_mercancia['PesoBruto']);
				
			}*/
			
			//debug($data_detalle_mercancia);
			
			//unset($data_detalle_mercancia['BienesTransp']);
			
			
			$this->satxmlsv33_cargaAtt($detalle_mercancia,$data_detalle_mercancia);
			}
		}
		
		if(isset($data["carta"]["mercancias"]["federal"]["uso"]) && $data["carta"]["mercancias"]["federal"]["uso"] == 1){
			$federal = $this->xml->createElement("cartaporte20:Autotransporte");
			$federal = $mercancias->appendChild($federal);
			
			
			$data_federal = [
			"PermSCT" => trim(explode("-",$data['carta']['mercancias']['federal']['PermSCT'])[0]),
			"NumPermisoSCT"     => $data['carta']['mercancias']['federal']['NumPermisoSCT'],
			//"NombreAseg"     => $data['carta']['mercancias']['federal']['NombreAseg'],
			//"NumPolizaSeguro"     => $data['carta']['mercancias']['federal']['NumPolizaSeguro'],
			];	
			$this->satxmlsv33_cargaAtt($federal,$data_federal);	
			
			$identificacion = $this->xml->createElement("cartaporte20:IdentificacionVehicular");
			$identificacion = $federal->appendChild($identificacion);			
			$data_identificacion = [
			"ConfigVehicular" => trim(explode("-",$data['carta']['mercancias']['federal']['identificacion']['ConfigVehicular'])[0]),
			"PlacaVM"     => $data['carta']['mercancias']['federal']['identificacion']['PlacaVM'],
			"AnioModeloVM"     => $data['carta']['mercancias']['federal']['identificacion']['AnioModeloVM'],
			];	
			$this->satxmlsv33_cargaAtt($identificacion,$data_identificacion);
			
			
			/*$pedimento = $this->xml->createElement("cartaporte20:Pedimentos");
			$pedimento = $federal->appendChild($pedimento);			
			$data_pedimento = [
			"Pedimento" => "asas",
			];	
			$this->satxmlsv33_cargaAtt($pedimento,$data_pedimento);
			*/
			$seguros = $this->xml->createElement("cartaporte20:Seguros");
			$seguros = $federal->appendChild($seguros);			
			$data_seguro = [
			"AseguraRespCivil"=>$data['carta']['mercancias']['federal']['seguros']['AseguraRespCivil'],
			"PolizaRespCivil"=>$data['carta']['mercancias']['federal']['seguros']['PolizaRespCivil'],
			"AseguraMedAmbiente"=>$data['carta']['mercancias']['federal']['seguros']['AseguraMedAmbiente'],
			"PolizaMedAmbiente"=>$data['carta']['mercancias']['federal']['seguros']['PolizaMedAmbiente'],
			"AseguraCarga"=>$data['carta']['mercancias']['federal']['seguros']['AseguraCarga'],
			"PolizaCarga"=>$data['carta']['mercancias']['federal']['seguros']['PolizaCarga'],
			"PrimaSeguro"=>$data['carta']['mercancias']['federal']['seguros']['PrimaSeguro'],
			
			];	
			$this->satxmlsv33_cargaAtt($seguros,$data_seguro);
			
			
			if($data['carta']['mercancias']['federal']['remolque'][1]['SubTipoRem'] != ""){
			if(count($data['carta']['mercancias']['federal']['remolque'])>0){
			
			$remolques = $this->xml->createElement("cartaporte20:Remolques");
			$remolques = $federal->appendChild($remolques);			
				if(isset($data['carta']['mercancias']['federal']['remolque'][1]['placa'])){
						$remolque1 = $this->xml->createElement("cartaporte20:Remolque");
						$remolque1 = $remolques->appendChild($remolque1);			
						$data_remolque1 = [
						"SubTipoRem" => trim(explode("-",$data['carta']['mercancias']['federal']['remolque'][1]['SubTipoRem'])[0]),
						"Placa" => $data['carta']['mercancias']['federal']['remolque'][1]['placa'],
						];	
						$this->satxmlsv33_cargaAtt($remolque1,$data_remolque1);	
				}
				
				if(isset($data['carta']['mercancias']['federal']['remolque'][2]['placa']) && $data['carta']['mercancias']['federal']['remolque'][2]['placa'] != ""){
						$remolque2 = $this->xml->createElement("cartaporte20:Remolque");
						$remolque2 = $remolques->appendChild($remolque2);			
						$data_remolque2 = [
						"SubTipoRem" => trim(explode("-",$data['carta']['mercancias']['federal']['remolque'][2]['SubTipoRem'])[0]),
						"Placa" => $data['carta']['mercancias']['federal']['remolque'][2]['placa'],
						];	
						$this->satxmlsv33_cargaAtt($remolque2,$data_remolque2);	
				}
			}
			}
			}
			
			if(isset($data["carta"]["mercancias"]["maritimo"]["uso"]) && $data["carta"]["mercancias"]["maritimo"]["uso"] == 1){
			$maritimo = $this->xml->createElement("cartaporte20:TransporteMaritimo");
			$maritimo = $mercancias->appendChild($maritimo);
			
			
			$data_maritimo = [
			"PermSCT"=> trim(explode("-",$data['carta']['mercancias']['maritimo']["PermSCT"])[0]),
			"NumPermisoSCT"=>$data['carta']['mercancias']['maritimo']["NumPermisoSCT"],
			"NombreAseg"=>$data['carta']['mercancias']['maritimo']["NombreAseg"],
			"NumPolizaSeguro"=> $data['carta']['mercancias']['maritimo']["NumPolizaSeguro"],
			"TipoEmbarcacion"=>trim(explode("-",$data['carta']['mercancias']['maritimo']["TipoEmbarcacion"])[0]),
			"Matricula"=>$data['carta']['mercancias']['maritimo']["Matricula"],
			"NumeroOMI"=>$data['carta']['mercancias']['maritimo']["NumeroOMI"],
			"AnioEmbarcacion"=>$data['carta']['mercancias']['maritimo']["AnioEmbarcacion"],
			"NombreEmbarc"=>$data['carta']['mercancias']['maritimo']["NombreEmbarc"],
			"NacionalidadEmbarc"=>$data['carta']['mercancias']['maritimo']["NacionalidadEmbarc"],
			"UnidadesDeArqBruto"=>$data['carta']['mercancias']['maritimo']["UnidadesDeArqBruto"],
			"TipoCarga"=>trim(explode("-",$data['carta']['mercancias']['maritimo']["TipoCarga"])[0]),
			"NumCertITC"=>$data['carta']['mercancias']['maritimo']["NumCertITC"],
			"Eslora"=>$data['carta']['mercancias']['maritimo']["Eslora"],
			"Manga"=>$data['carta']['mercancias']['maritimo']["Manga"], 
			"Calado"=>$data['carta']['mercancias']['maritimo']["Calado"],
			"LineaNaviera"=>$data['carta']['mercancias']['maritimo']["LineaNaviera"],
			"NombreAgenteNaviero"=>$data['carta']['mercancias']['maritimo']["NombreAgenteNaviero"],
			"NumAutorizacionNaviero"=>$data['carta']['mercancias']['maritimo']["NumAutorizacionNaviero"],
			//"NumAutorizacionNaviero"=> "SCT418/007/2021",
			"NumViaje"=>$data['carta']['mercancias']['maritimo']["NumViaje"],
			"NumConocEmbarc"=>$data['carta']['mercancias']['maritimo']["NumConocEmbarc"],
			];	
			$this->satxmlsv33_cargaAtt($maritimo,$data_maritimo);
			
			
			
			$m_contenedor = json_decode($data['carta']['inCartaContenendores'], true) ?? [];
			if(count($m_contenedor)> 0){
			foreach($m_contenedor as $c) {
			$contenedor = $this->xml->createElement("cartaporte20:Contenedor");
			$contenedor = $maritimo->appendChild($contenedor);
		
			$data_contenedor = [
			"MatriculaContenedor" => $c['matricula'],
			"TipoContenedor"     => trim(explode("-",$c['tipo'])[0]),
			"NumPrecinto"     => $c['numero'],
			];	
			
			$this->satxmlsv33_cargaAtt($contenedor,$data_contenedor);	
			
			}
			
			}
			}
			
			if(isset($data["carta"]["mercancias"]["aereo"]["uso"]) && $data["carta"]["mercancias"]["aereo"]["uso"] == 1){
			$aereo = $this->xml->createElement("cartaporte20:TransporteAereo");
			$aereo = $mercancias->appendChild($aereo);
			$data_aereo = [
			"PermSCT"=> isset($data['carta']['mercancias']['aereo']["PermSCT"])?trim(explode("-",$data['carta']['mercancias']['aereo']["PermSCT"])[0]):"",
			"NumPermisoSCT"=>isset($data['carta']['mercancias']['aereo']["NumPermisoSCT"])?$data['carta']['mercancias']['aereo']["NumPermisoSCT"]:"",
			"MatriculaAeronave"=>isset($data['carta']['mercancias']['aereo']["MatriculaAeronave"])?$data['carta']['mercancias']['aereo']["MatriculaAeronave"]:"",
			//"MatriculaAeronave"=>"UP-20101",
			"NombreAseg"=>isset($data['carta']['mercancias']['aereo']["NombreAseg"])?$data['carta']['mercancias']['aereo']["NombreAseg"]:"",
			"NumPolizaSeguro"=> isset($data['carta']['mercancias']['aereo']["NumPolizaSeguro"])?$data['carta']['mercancias']['aereo']["NumPolizaSeguro"]:"",
			"NumeroGuia"=>isset($data['carta']['mercancias']['aereo']["NumeroGuia"])?$data['carta']['mercancias']['aereo']["NumeroGuia"]:"",
			//"NumeroGuia"=>"1234567890123",
			"LugarContrato"=>isset($data['carta']['mercancias']['aereo']["LugarContrato"])?$data['carta']['mercancias']['aereo']["LugarContrato"]:"",
			"RFCTransportista"=> isset($data['carta']['mercancias']['aereo']["RFCTransportista"])?$data['carta']['mercancias']['aereo']["RFCTransportista"]:"",
			//"CodigoTransportista"=>"CA001",
			"CodigoTransportista"=>isset($data['carta']['mercancias']['aereo']["CodigoTransportista"])?trim(explode("-",$data['carta']['mercancias']['aereo']["CodigoTransportista"])[0]):"",
			"NumRegIdTribTranspor"=>isset($data['carta']['mercancias']['aereo']["NumRegIdTribTranspor"])?$data['carta']['mercancias']['aereo']["NumRegIdTribTranspor"]:"",
			"ResidenciaFiscalTranspor"=>isset($data['carta']['mercancias']['aereo']["ResidenciaFiscalTransportista"])?$data['carta']['mercancias']['aereo']["ResidenciaFiscalTransportista"]:"",
			"NombreTransportista"=>isset($data['carta']['mercancias']['aereo']["NombreTransportista"])?$data['carta']['mercancias']['aereo']["NombreTransportista"]:"",
			"RFCEmbarcador"=>isset($data['carta']['mercancias']['aereo']["RFCEmbarcador"])?$data['carta']['mercancias']['aereo']["RFCEmbarcador"]:"",
			"NumRegIdTribEmbarc"=>isset($data['carta']['mercancias']['aereo']["NumRegIdTribEmbarc"])?$data['carta']['mercancias']['aereo']["NumRegIdTribEmbarc"]:"",
			"ResidenciaFiscalEmbarc"=>isset($data['carta']['mercancias']['aereo']["ResidenciaFiscalEmbarc"])?$data['carta']['mercancias']['aereo']["ResidenciaFiscalEmbarc"]:"",
			"NombreEmbarcador"=>isset($data['carta']['mercancias']['aereo']["NombreEmbarcador"])?$data['carta']['mercancias']['aereo']["NombreEmbarcador"]:"",
			];	
			$this->satxmlsv33_cargaAtt($aereo,$data_aereo);
			
			
			}
			
			if(isset($data["carta"]["mercancias"]["ferroviario"]["uso"]) && $data["carta"]["mercancias"]["ferroviario"]["uso"] ==1){
			$ferroveario = $this->xml->createElement("cartaporte20:TransporteFerroviario");
			$ferroveario = $mercancias->appendChild($ferroveario);
			$data_ferroveario = [
			"TipoDeServicio"=> trim(explode("-",$data['carta']['mercancias']['ferroviario']["TipoDeServicio"])[0]),
			//"TipoDeServicio"=> "TS02",
			"TipoDeTrafico"=>trim(explode("-",$data["carta"]["mercancias"]["ferroviario"]["TipoDeTrafico"])[0]),
			"NombreAseg"=>$data['carta']['mercancias']['ferroviario']["NombreAseg"],
			"NumPolizaSeguro"=>$data['carta']['mercancias']['ferroviario']["NumPolizaSeguro"],
			//"Concesionario"=>"AAA010101AAA",
			];	
			$this->satxmlsv33_cargaAtt($ferroveario,$data_ferroveario);
			
			$d_paso = json_decode($data['carta']['inCartaDerechoPaso'], true) ?? []; 
			if(count($d_paso) >0){
			foreach($d_paso as $cp) {
			$paso = $this->xml->createElement("cartaporte20:DerechosDePaso");
			$paso = $ferroveario->appendChild($paso);
		
			$data_paso = [
			"TipoDerechoDePaso" => trim(explode("-",$cp['derecho_de_paso'])[0]),
			"KilometrajePagado"     => $cp['kilometro_pagado'],
			];	
			
			$this->satxmlsv33_cargaAtt($paso,$data_paso);	
			
			}
			}
			
			$carro = $this->xml->createElement("cartaporte20:Carro");
			$carro = $ferroveario->appendChild($carro);
		
			$data_carro = [
			"TipoCarro" => trim(explode("-",$data["carta"]["mercancias"]["ferroviario"]["carro"]["TipoCarro"])[0]),
			"MatriculaCarro"     => $data["carta"]["mercancias"]["ferroviario"]["carro"]["MatriculaCarro"],
			"GuiaCarro"     => $data["carta"]["mercancias"]["ferroviario"]["carro"]["GuiaCarro"],
			"ToneladasNetasCarro"     => $data["carta"]["mercancias"]["ferroviario"]["carro"]["ToneladasNetasCarro"],
			];	
			
			$this->satxmlsv33_cargaAtt($carro,$data_carro);	
			
			$c_contenedor = json_decode($data['carta']['inCartaContenedoresCarro'], true) ?? []; 
			if(count($c_contenedor) > 0){
			foreach($c_contenedor as $cc) {
			$carro_contenedor = $this->xml->createElement("cartaporte20:Contenedor");
			$carro_contenedor = $carro->appendChild($carro_contenedor);
  		
			$data_contenedor_carro = [
			"TipoContenedor" => trim(explode("-",$cc['tipo'])[0]),
			"PesoContenedorVacio" => $cc['peso'],
			"PesoNetoMercancia" => $cc['peso_neto'],
			];	
			
			$this->satxmlsv33_cargaAtt($carro_contenedor,$data_contenedor_carro);	
			
			}
			}
			
			}
			
			//debug($data);
			$array_fugura_transporte = json_decode($data["carta"]["inFiguraTransporte"], true) ?? [];
			
			//debug($array_fugura_transporte);
			
			if(isset($data["carta"]["figura_transporte"]["aplica"]) && $data["carta"]["figura_transporte"]["aplica"] == 1 && count($array_fugura_transporte) > 0){
				$figura = $this->xml->createElement("cartaporte20:FiguraTransporte");
				$figura = $cartaporte->appendChild($figura);
				foreach($array_fugura_transporte as $figura_d){
					$tiposFiguraTransporte = $this->xml->createElement("cartaporte20:TiposFigura");
					$tiposFiguraTransporte = $figura->appendChild($tiposFiguraTransporte);
					$data_figura = [
						"TipoFigura"  =>$figura_d["tipo_figura"],
						"RFCFigura"=>$figura_d["rfc"],
						"NumLicencia"=>$figura_d["licencia"],
						"NombreFigura"=>$figura_d["nombre"],
						"NumRegIdTrib"=>$figura_d["tipo_figura"],
						"ResidenciaFiscalFigura"=>$figura_d["tipo_figura"]
					];	
					if($figura_d["tipo_figura"]!= "01"){
						unset($data_figura["NumLicencia"]);	
					}
					if($figura_d["rfc"] !=""){
						unset($data_figura['NumRegIdTrib']);
						unset($data_figura['ResidenciaFiscalFigura']);
					}
					$this->satxmlsv33_cargaAtt($tiposFiguraTransporte,$data_figura);
					if($figura_d["tipo_figura"] == "02" || $figura_d["tipo_figura"] == "03" && isset($figura_d['partes_transporte'])){
						foreach($figura_d['partes_transporte'] as $pt){
							$partes = $this->xml->createElement("cartaporte20:PartesTransporte");
							$partes = $tiposFiguraTransporte->appendChild($partes);
							$data_parte = [
								"ParteTransporte"=> explode("-",$pt["parte_transporte"])[0]
							];			
							$this->satxmlsv33_cargaAtt($partes,$data_parte);
						}
					}
					if(isset($figura_d["direccion"]) && $figura_d["direccion"] != ""){
						$domicilio = $this->xml->createElement("cartaporte20:Domicilio");
						$domicilio = $tiposFiguraTransporte->appendChild($domicilio);			
						$data_domicilio_figura = [
							"Calle" =>$figura_d["direccion"]["calle"],
							"NumeroExterior"  =>$figura_d["direccion"]["numero_exterior"],
							"NumeroInterior"=>$figura_d["direccion"]["numero_interior"],
							"Colonia"=>$figura_d["direccion"]["colonia"],
							"Localidad"=>$figura_d["direccion"]["localidad"],
							"Referencia"=>$figura_d["direccion"]["referencia"],
							"Municipio"=>$figura_d["direccion"]["municipio"],
							"Estado"=>$figura_d["direccion"]["estado"],
							"Pais"=>$figura_d["direccion"]["pais"],
							"CodigoPostal"=>$figura_d["direccion"]["codigo_postal"],
						];				
						$this->satxmlsv33_cargaAtt($domicilio,$data_domicilio_figura);
					}
				}
			}
			
			
   }
   
   function setCartaPorte($data,$complemento){
		
		$complemento = $this->root->appendChild($complemento);
		$cartaporte = $this->xml->createElement("cartaporte20:CartaPorte");
		$cartaporte = $complemento->appendChild($cartaporte);
		$carta= [			
			"Version"	=>	"2.0" ,
			"TranspInternac"		=> self::cfdiUtf8Decode($data['carta']['TranspInternac']),
			"EntradaSalidaMerc"		=> $data['carta']['EntradaSalidaMerc'],
			"PaisOrigenDestino"		=> $data['carta']['PaisOrigenDestino'],
			"ViaEntradaSalida"		=> $data['carta']['ViaEntradaSalida'],
			"TotalDistRec"		=> $data['carta']['TotalDistRec'],
		
		];
		
		if($data['carta']['TranspInternac'] == "No"){
			unset($carta['EntradaSalidaMerc']);
			unset($carta['ViaEntradaSalida']);
			unset($carta['PaisOrigenDestino']);
			
		}
		if(!isset($data['carta']['mercancias']['federal']['uso']) && !isset($data["carta"]["mercancias"]["ferroviario"]["uso"])){
			unset($carta['TotalDistRec']);
		}
		
		$this->satxmlsv33_cargaAtt($cartaporte,$carta);
		
		$ubicaciones = $this->xml->createElement("cartaporte20:Ubicaciones");
		$ubicaciones = $cartaporte->appendChild($ubicaciones);
		$u_bicaciones = json_decode($data['carta']['inCartaUbicaciones'], true) ?? []; 
		
	
		if(count($u_bicaciones)> 0 ){ 
		foreach($u_bicaciones as $u) {
			$ubicacion = $this->xml->createElement("cartaporte20:Ubicacion"); 
			$ubicacion = $ubicaciones->appendChild($ubicacion);
		
		$tmpnombre_estacion = ($u['NumEstacion'] !="")?self::cfdiUtf8Decode(trim(explode("-",$u['NumEstacion'])[1])):"";
			$data_ubicacion = [
				"TipoUbicacion"=>$u["TipoUbicacion"],
				"IDUbicacion"=>$u["IDUbicacion"],
				"RFCRemitenteDestinatario"=>$u["RFCRemitenteDestinatario"],
				"NombreRemitenteDestinatario"=> self::cfdiUtf8Decode($u["NombreRemitenteDestinatario"]),
				"NumRegIdTrib"=>$u["NumRegIdTrib"],
				"ResidenciaFiscal"=>$u["ResidenciaFiscal"],
				//"NumEstacion"=>"V0102",
				//"NombreEstacion"=>$u["NavegacionTrafico"],
				"NumEstacion"=> isset($u['NumEstacion']) ? trim(explode("-", $u['NumEstacion'])[0]) : "",
				//"NombreEstacion"=> "SAN LORENZO",
				"NombreEstacion"=> isset($u['NombreEstacion']) && $u['NombreEstacion']!= ""?trim($u["NombreEstacion"]):$tmpnombre_estacion,
				"NavegacionTrafico"=>$u["NavegacionTrafico"],
				"FechaHoraSalidaLlegada"=> strpos($u["FechaHoraSalidaLlegada"], 'T') ? $u["FechaHoraSalidaLlegada"] :  $u["FechaHoraSalidaLlegada"]."T".date("H:m:i"),
				"TipoEstacion"=>isset($u['TipoEstacion'])?trim(explode("-",$u['TipoEstacion'])[0]):"",
				//"TipoEstacion"=>$u["TipoEstacion"],
				"DistanciaRecorrida"=>$u["DistanciaRecorrida"],
			];			
			
			if($u["RFCRemitenteDestinatario"] != "" && $u["RFCRemitenteDestinatario"]!="XEXX010101000"){
			unset($data_ubicacion['NumRegIdTrib']);	
			unset($data_ubicacion['ResidenciaFiscal']);
			}
			
			if($u["TipoUbicacion"] == "Origen"){
				unset($data_ubicacion['DistanciaRecorrida']);
			}
			if(!isset($data['carta']['mercancias']['federal']['uso']) && !isset($data["carta"]["mercancias"]["ferroviario"]["uso"])){
				unset($data_ubicacion['DistanciaRecorrida']);
			}
			$this->satxmlsv33_cargaAtt($ubicacion,$data_ubicacion);	
					
				if(!empty($u['domicilio'])){
				
				$domicilio = $this->xml->createElement("cartaporte20:Domicilio");
				$domicilio = $ubicacion->appendChild($domicilio);
		
				$data_domicilio = [
				"Calle" => isset($u['domicilio']['calle'])?$u['domicilio']['calle']:"",
				"NumeroExterior"=> isset($u['domicilio']['numeroExterior'])?$u['domicilio']['numeroExterior']:"",
				"NumeroInterior"=> isset($u['domicilio']['numeroInterior'])?$u['domicilio']['numeroInterior']:"",
				"Colonia"=> isset($u['domicilio']['colonia'])?$u['domicilio']['colonia']:"",
				"Localidad"=> isset($u['domicilio']['localidad'])?$u['domicilio']['localidad']:"",
				"Referencia"=> isset($u['domicilio']['referencia'])?$u['domicilio']['referencia']:"",
				"Municipio"=> isset($u['domicilio']['municipio'])?$u['domicilio']['municipio']:"",
				"Estado"=> isset($u['domicilio']['estado'])?$u['domicilio']['estado']:"",
				"Pais"=> isset($u['domicilio']['pais'])?$u['domicilio']['pais']:"",
				"CodigoPostal"=> isset($u['domicilio']['codigopostal'])?$u['domicilio']['codigopostal']:"",
				];
				/*if($data["carta"]["TranspInternac"]=="Sí"){
					unset($data_domicilio['Pais']);
				}*/
				if($data_domicilio['Calle'] =="STREET"){
				$data_domicilio['Colonia'] = "akhsl demoa";
				}
				
				$this->satxmlsv33_cargaAtt($domicilio,$data_domicilio);	
				}
		}
	}
	
	
	/****valicacion*****/
	$tiposClvs = 0;
		if(isset($data["carta"]["mercancias"]["federal"]["uso"])){
		$tiposClvs++;
		}
		if(isset($data["carta"]["mercancias"]["maritimo"]["uso"])){
		$tiposClvs++;
		}
		
		if(isset($data["carta"]["mercancias"]["aereo"]["uso"])){
		$tiposClvs++;
		}
		
		if(isset($data["carta"]["mercancias"]["ferroviario"]["uso"])){
		$tiposClvs++;
		}
		
		
		
		$mercancias = $this->xml->createElement("cartaporte20:Mercancias");
		$mercancias = $cartaporte->appendChild($mercancias);
		$data_mercancia= [			
			"PesoBrutoTotal"	=>	isset($data['carta']['Mercancias']['PesoBrutoTotal'])?$data['carta']['Mercancias']['PesoBrutoTotal']:"",
			"UnidadPeso"		=> isset($data['carta']['Mercancias']['UnidadPeso'])?trim(explode("-",$data['carta']['Mercancias']['UnidadPeso'])[0]):"",
			"PesoNetoTotal"		=> isset($data['carta']['Mercancias']['PesoNetoTotal'])?$data['carta']['Mercancias']['PesoNetoTotal']:"",
			"NumTotalMercancias"		=> isset($data['carta']['Mercancias']['NumTotalMercancias'])?$data['carta']['Mercancias']['NumTotalMercancias']:"",
			"CargoPorTasacion"		=> isset($data['carta']['Mercancias']['CargoPorTasacion'])?$data['carta']['Mercancias']['CargoPorTasacion']:"",
			];
			
			
		$this->satxmlsv33_cargaAtt($mercancias,$data_mercancia);
		$m_ercancias = json_decode($data['carta']['inCartaMercancias'], true) ?? []; 
		$quitarCTCT =false;
		if(count($m_ercancias)==1 ){
		$quitarCTCT =true;
		}
		//Pedro
		if ($m_ercancias != '') {
			foreach($m_ercancias as $m) {
				
				if(isset(explode("-",$m['ClaveUnidad'])[1])){
				$unidad_nombre = self::cfdiUtf8Decode(trim(explode("-",$m['ClaveUnidad'])[1]));
				$unidad_nombre = substr($unidad_nombre, 0, 20);
				}else{
				$unidad_nombre = "";
				}
				$mercancia = $this->xml->createElement("cartaporte20:Mercancia"); 
				$mercancia = $mercancias->appendChild($mercancia);
				$data_mercancia = [
				"BienesTransp" => $m['BienesTransp'],
				"ClaveSTCC"     => trim(explode("-",$m['ClaveSTCC'])[0]),
				"Descripcion"     => $m['descripcion'],
				"Cantidad"     => $m['Cantidad'],
				"ClaveUnidad"     => trim(explode("-",$m['ClaveUnidad'])[0]),
				"Unidad"     => $unidad_nombre,
				"Dimensiones"     => $m['dimensiones'],
				"MaterialPeligroso"     => self::cfdiUtf8Decode($m['materialPeligroso']),
				"CveMaterialPeligroso"     => trim(explode("-",$m['claveMaterialPeligroso'])[0]),
				"Embalaje"     => trim(explode("-",$m['embalaje'])[0]),
				"DescripEmbalaje"     => $m['descripcionEmbalaje'],
				"PesoEnKg"     => $m['pesoKg'],
				"ValorMercancia"     => $m['valorMercancia'],
				"Moneda"     => $m['Moneda'],
				"FraccionArancelaria"     => explode("-",$m['FraccionArancelaria'])[0],
				"UUIDComercioExt"     => $m['UuidComercio'],
				];	
				
				if($data_mercancia["MaterialPeligroso"] =="" && $data_mercancia["CveMaterialPeligroso"]==""){
					unset($data_mercancia["MaterialPeligroso"]);
					unset($data_mercancia["CveMaterialPeligroso"]);
				}
				$this->satxmlsv33_cargaAtt($mercancia,$data_mercancia);	
				
				if(isset($m['pedimentos'])){
					error_log('Si trae pedimento------------------------------------');
					foreach($m['pedimentos'] as $p){
						$pedimento = $this->xml->createElement("cartaporte20:Pedimentos");
						$pedimento = $mercancia->appendChild($pedimento);			
						$data_pedimento = [
							"Pedimento" => $p["pedimento"],
						];	
						$this->satxmlsv33_cargaAtt($pedimento,$data_pedimento);
					}
				}
				
				if(isset($m['guias'])){
				
				foreach($m['guias'] as $g){
				$guia = $this->xml->createElement("cartaporte20:GuiasIdentificacion");
				$guia = $mercancia->appendChild($guia);			
				$data_guia = [
				"NumeroGuiaIdentificacion"=>$g["numero"],
				"DescripGuiaIdentificacion"=>$g["descripcion"],
				"PesoGuiaIdentificacion"=>$g["peso"],
				];	
				$this->satxmlsv33_cargaAtt($guia,$data_guia);
				}
				}
				
				
				
				if($m['transporta'] == 1){
				
				foreach($m['cantidad_transporta'] as $ct){
				$cantidad_transporta = $this->xml->createElement("cartaporte20:CantidadTransporta");
				$cantidad_transporta = $mercancia->appendChild($cantidad_transporta);
				$data_mercancia = [
					"Cantidad" => $ct['cantidad'],
					"IDOrigen" => $ct['idOrigen'],
					"IDDestino"     => $ct['idDestino'],
					"CvesTransporte"     => $ct['claveTransporte'],
				];	
				
				if($quitarCTCT == true || $tiposClvs <= 1){
				unset($data_mercancia['CvesTransporte']);
				}
				$this->satxmlsv33_cargaAtt($cantidad_transporta,$data_mercancia);
				}
				}
			
				if($m['detalles']==1 && $m["detalle_mercancia"]["peso_bruto"]!=""){
				$detalle_mercancia = $this->xml->createElement("cartaporte20:DetalleMercancia");
				$detalle_mercancia = $mercancia->appendChild($detalle_mercancia);
				
				$data_detalle_mercancia = [
					"UnidadPesoMerc" => trim(explode("-",$m["detalle_mercancia"]["unidad_peso"])[0]),
					"PesoBruto" => $m["detalle_mercancia"]["peso_bruto"],
					"PesoNeto"     => $m["detalle_mercancia"]["peso_neto"],
					"PesoTara"     => $m["detalle_mercancia"]["peso_tara"],
					"NumPiezas"     => $m["detalle_mercancia"]["num_piezas"],
					];	
				 
				/*if(!isset($data['carta']['mercancias']['ferroviario']['uso']) || !isset($data['carta']['mercancias']['aereo']['uso']) ){
					unset($data_detalle_mercancia['UnidadPeso']);
					unset($data_detalle_mercancia['PesoBruto']);
					
				}*/
				
				//debug($data_detalle_mercancia);
				
				//unset($data_detalle_mercancia['BienesTransp']);
				
				
				$this->satxmlsv33_cargaAtt($detalle_mercancia,$data_detalle_mercancia);
				}
			}
		}

		if(isset($data["carta"]["mercancias"]["federal"]["uso"]) && $data["carta"]["mercancias"]["federal"]["uso"] == 1){
			$federal = $this->xml->createElement("cartaporte20:Autotransporte");
			$federal = $mercancias->appendChild($federal);
			
			
			$data_federal = [
			"PermSCT" => trim(explode("-",$data['carta']['mercancias']['federal']['PermSCT'])[0]),
			"NumPermisoSCT"     => $data['carta']['mercancias']['federal']['NumPermisoSCT'],
			//"NombreAseg"     => $data['carta']['mercancias']['federal']['NombreAseg'],
			//"NumPolizaSeguro"     => $data['carta']['mercancias']['federal']['NumPolizaSeguro'],
			];	
			$this->satxmlsv33_cargaAtt($federal,$data_federal);	
			
			$identificacion = $this->xml->createElement("cartaporte20:IdentificacionVehicular");
			$identificacion = $federal->appendChild($identificacion);			
			$data_identificacion = [
			"ConfigVehicular" => trim(explode("-",$data['carta']['mercancias']['federal']['identificacion']['ConfigVehicular'])[0]),
			"PlacaVM"     => $data['carta']['mercancias']['federal']['identificacion']['PlacaVM'],
			"AnioModeloVM"     => $data['carta']['mercancias']['federal']['identificacion']['AnioModeloVM'],
			];	
			$this->satxmlsv33_cargaAtt($identificacion,$data_identificacion);
			
			
			/*$pedimento = $this->xml->createElement("cartaporte20:Pedimentos");
			$pedimento = $federal->appendChild($pedimento);			
			$data_pedimento = [
			"Pedimento" => "asas",
			];	
			$this->satxmlsv33_cargaAtt($pedimento,$data_pedimento);
			*/
			$seguros = $this->xml->createElement("cartaporte20:Seguros");
			$seguros = $federal->appendChild($seguros);			
			$data_seguro = [
			"AseguraRespCivil"=>$data['carta']['mercancias']['federal']['seguros']['AseguraRespCivil'],
			"PolizaRespCivil"=>$data['carta']['mercancias']['federal']['seguros']['PolizaRespCivil'],
			"AseguraMedAmbiente"=>$data['carta']['mercancias']['federal']['seguros']['AseguraMedAmbiente'],
			"PolizaMedAmbiente"=>$data['carta']['mercancias']['federal']['seguros']['PolizaMedAmbiente'],
			"AseguraCarga"=>$data['carta']['mercancias']['federal']['seguros']['AseguraCarga'],
			"PolizaCarga"=>$data['carta']['mercancias']['federal']['seguros']['PolizaCarga'],
			"PrimaSeguro"=>$data['carta']['mercancias']['federal']['seguros']['PrimaSeguro'],
			
			];	
			$this->satxmlsv33_cargaAtt($seguros,$data_seguro);
			
			
			if($data['carta']['mercancias']['federal']['remolque'][1]['SubTipoRem'] != ""){
			if(count($data['carta']['mercancias']['federal']['remolque'])>0){
			
			$remolques = $this->xml->createElement("cartaporte20:Remolques");
			$remolques = $federal->appendChild($remolques);			
				if(isset($data['carta']['mercancias']['federal']['remolque'][1]['placa'])){
						$remolque1 = $this->xml->createElement("cartaporte20:Remolque");
						$remolque1 = $remolques->appendChild($remolque1);			
						$data_remolque1 = [
						"SubTipoRem" => trim(explode("-",$data['carta']['mercancias']['federal']['remolque'][1]['SubTipoRem'])[0]),
						"Placa" => $data['carta']['mercancias']['federal']['remolque'][1]['placa'],
						];	
						$this->satxmlsv33_cargaAtt($remolque1,$data_remolque1);	
				}
				
				if(isset($data['carta']['mercancias']['federal']['remolque'][2]['placa']) && $data['carta']['mercancias']['federal']['remolque'][2]['placa'] != ""){
						$remolque2 = $this->xml->createElement("cartaporte20:Remolque");
						$remolque2 = $remolques->appendChild($remolque2);			
						$data_remolque2 = [
						"SubTipoRem" => trim(explode("-",$data['carta']['mercancias']['federal']['remolque'][2]['SubTipoRem'])[0]),
						"Placa" => $data['carta']['mercancias']['federal']['remolque'][2]['placa'],
						];	
						$this->satxmlsv33_cargaAtt($remolque2,$data_remolque2);	
				}
			}
			}
			}
			
			if(isset($data["carta"]["mercancias"]["maritimo"]["uso"]) && $data["carta"]["mercancias"]["maritimo"]["uso"] == 1){
			$maritimo = $this->xml->createElement("cartaporte20:TransporteMaritimo");
			$maritimo = $mercancias->appendChild($maritimo);
			
			
			$data_maritimo = [
			"PermSCT"=> trim(explode("-",$data['carta']['mercancias']['maritimo']["PermSCT"])[0]),
			"NumPermisoSCT"=>$data['carta']['mercancias']['maritimo']["NumPermisoSCT"],
			"NombreAseg"=>$data['carta']['mercancias']['maritimo']["NombreAseg"],
			"NumPolizaSeguro"=> $data['carta']['mercancias']['maritimo']["NumPolizaSeguro"],
			"TipoEmbarcacion"=>trim(explode("-",$data['carta']['mercancias']['maritimo']["TipoEmbarcacion"])[0]),
			"Matricula"=>$data['carta']['mercancias']['maritimo']["Matricula"],
			"NumeroOMI"=>$data['carta']['mercancias']['maritimo']["NumeroOMI"],
			"AnioEmbarcacion"=>$data['carta']['mercancias']['maritimo']["AnioEmbarcacion"],
			"NombreEmbarc"=>$data['carta']['mercancias']['maritimo']["NombreEmbarc"],
			"NacionalidadEmbarc"=>$data['carta']['mercancias']['maritimo']["NacionalidadEmbarc"],
			"UnidadesDeArqBruto"=>$data['carta']['mercancias']['maritimo']["UnidadesDeArqBruto"],
			"TipoCarga"=>trim(explode("-",$data['carta']['mercancias']['maritimo']["TipoCarga"])[0]),
			"NumCertITC"=>$data['carta']['mercancias']['maritimo']["NumCertITC"],
			"Eslora"=>$data['carta']['mercancias']['maritimo']["Eslora"],
			"Manga"=>$data['carta']['mercancias']['maritimo']["Manga"], 
			"Calado"=>$data['carta']['mercancias']['maritimo']["Calado"],
			"LineaNaviera"=>$data['carta']['mercancias']['maritimo']["LineaNaviera"],
			"NombreAgenteNaviero"=>$data['carta']['mercancias']['maritimo']["NombreAgenteNaviero"],
			"NumAutorizacionNaviero"=>$data['carta']['mercancias']['maritimo']["NumAutorizacionNaviero"],
			//"NumAutorizacionNaviero"=> "SCT418/007/2021",
			"NumViaje"=>$data['carta']['mercancias']['maritimo']["NumViaje"],
			"NumConocEmbarc"=>$data['carta']['mercancias']['maritimo']["NumConocEmbarc"],
			];	
			$this->satxmlsv33_cargaAtt($maritimo,$data_maritimo);
			
			
			
			$m_contenedor = json_decode($data['carta']['inCartaContenendores'], true) ?? [];
			if(count($m_contenedor)> 0){
			foreach($m_contenedor as $c) {
			$contenedor = $this->xml->createElement("cartaporte20:Contenedor");
			$contenedor = $maritimo->appendChild($contenedor);
		
			$data_contenedor = [
			"MatriculaContenedor" => $c['matricula'],
			"TipoContenedor"     => trim(explode("-",$c['tipo'])[0]),
			"NumPrecinto"     => $c['numero'],
			];	
			
			$this->satxmlsv33_cargaAtt($contenedor,$data_contenedor);	
			
			}
			
			}
			}
			
			if(isset($data["carta"]["mercancias"]["aereo"]["uso"]) && $data["carta"]["mercancias"]["aereo"]["uso"] == 1){
			$aereo = $this->xml->createElement("cartaporte20:TransporteAereo");
			$aereo = $mercancias->appendChild($aereo);
			$data_aereo = [
			"PermSCT"=> isset($data['carta']['mercancias']['aereo']["PermSCT"])?trim(explode("-",$data['carta']['mercancias']['aereo']["PermSCT"])[0]):"",
			"NumPermisoSCT"=>isset($data['carta']['mercancias']['aereo']["NumPermisoSCT"])?$data['carta']['mercancias']['aereo']["NumPermisoSCT"]:"",
			"MatriculaAeronave"=>isset($data['carta']['mercancias']['aereo']["MatriculaAeronave"])?$data['carta']['mercancias']['aereo']["MatriculaAeronave"]:"",
			//"MatriculaAeronave"=>"UP-20101",
			"NombreAseg"=>isset($data['carta']['mercancias']['aereo']["NombreAseg"])?$data['carta']['mercancias']['aereo']["NombreAseg"]:"",
			"NumPolizaSeguro"=> isset($data['carta']['mercancias']['aereo']["NumPolizaSeguro"])?$data['carta']['mercancias']['aereo']["NumPolizaSeguro"]:"",
			"NumeroGuia"=>isset($data['carta']['mercancias']['aereo']["NumeroGuia"])?$data['carta']['mercancias']['aereo']["NumeroGuia"]:"",
			//"NumeroGuia"=>"1234567890123",
			"LugarContrato"=>isset($data['carta']['mercancias']['aereo']["LugarContrato"])?$data['carta']['mercancias']['aereo']["LugarContrato"]:"",
			"RFCTransportista"=> isset($data['carta']['mercancias']['aereo']["RFCTransportista"])?$data['carta']['mercancias']['aereo']["RFCTransportista"]:"",
			//"CodigoTransportista"=>"CA001",
			"CodigoTransportista"=>isset($data['carta']['mercancias']['aereo']["CodigoTransportista"])?trim(explode("-",$data['carta']['mercancias']['aereo']["CodigoTransportista"])[0]):"",
			"NumRegIdTribTranspor"=>isset($data['carta']['mercancias']['aereo']["NumRegIdTribTranspor"])?$data['carta']['mercancias']['aereo']["NumRegIdTribTranspor"]:"",
			"ResidenciaFiscalTranspor"=>isset($data['carta']['mercancias']['aereo']["ResidenciaFiscalTransportista"])?$data['carta']['mercancias']['aereo']["ResidenciaFiscalTransportista"]:"",
			"NombreTransportista"=>isset($data['carta']['mercancias']['aereo']["NombreTransportista"])?$data['carta']['mercancias']['aereo']["NombreTransportista"]:"",
			"RFCEmbarcador"=>isset($data['carta']['mercancias']['aereo']["RFCEmbarcador"])?$data['carta']['mercancias']['aereo']["RFCEmbarcador"]:"",
			"NumRegIdTribEmbarc"=>isset($data['carta']['mercancias']['aereo']["NumRegIdTribEmbarc"])?$data['carta']['mercancias']['aereo']["NumRegIdTribEmbarc"]:"",
			"ResidenciaFiscalEmbarc"=>isset($data['carta']['mercancias']['aereo']["ResidenciaFiscalEmbarc"])?$data['carta']['mercancias']['aereo']["ResidenciaFiscalEmbarc"]:"",
			"NombreEmbarcador"=>isset($data['carta']['mercancias']['aereo']["NombreEmbarcador"])?$data['carta']['mercancias']['aereo']["NombreEmbarcador"]:"",
			];	
			$this->satxmlsv33_cargaAtt($aereo,$data_aereo);
			
			
			}
			
			if(isset($data["carta"]["mercancias"]["ferroviario"]["uso"]) && $data["carta"]["mercancias"]["ferroviario"]["uso"] ==1){
			$ferroveario = $this->xml->createElement("cartaporte20:TransporteFerroviario");
			$ferroveario = $mercancias->appendChild($ferroveario);
			$data_ferroveario = [
			"TipoDeServicio"=> trim(explode("-",$data['carta']['mercancias']['ferroviario']["TipoDeServicio"])[0]),
			//"TipoDeServicio"=> "TS02",
			"TipoDeTrafico"=>trim(explode("-",$data["carta"]["mercancias"]["ferroviario"]["TipoDeTrafico"])[0]),
			"NombreAseg"=>$data['carta']['mercancias']['ferroviario']["NombreAseg"],
			"NumPolizaSeguro"=>$data['carta']['mercancias']['ferroviario']["NumPolizaSeguro"],
			//"Concesionario"=>"AAA010101AAA",
			];	
			$this->satxmlsv33_cargaAtt($ferroveario,$data_ferroveario);
			
			$d_paso = json_decode($data['carta']['inCartaDerechoPaso'], true) ?? []; 
			if(count($d_paso) >0){
			foreach($d_paso as $cp) {
			$paso = $this->xml->createElement("cartaporte20:DerechosDePaso");
			$paso = $ferroveario->appendChild($paso);
		
			$data_paso = [
			"TipoDerechoDePaso" => trim(explode("-",$cp['derecho_de_paso'])[0]),
			"KilometrajePagado"     => $cp['kilometro_pagado'],
			];	
			
			$this->satxmlsv33_cargaAtt($paso,$data_paso);	
			
			}
			}
			
			$carro = $this->xml->createElement("cartaporte20:Carro");
			$carro = $ferroveario->appendChild($carro);
		
			$data_carro = [
			"TipoCarro" => trim(explode("-",$data["carta"]["mercancias"]["ferroviario"]["carro"]["TipoCarro"])[0]),
			"MatriculaCarro"     => $data["carta"]["mercancias"]["ferroviario"]["carro"]["MatriculaCarro"],
			"GuiaCarro"     => $data["carta"]["mercancias"]["ferroviario"]["carro"]["GuiaCarro"],
			"ToneladasNetasCarro"     => $data["carta"]["mercancias"]["ferroviario"]["carro"]["ToneladasNetasCarro"],
			];	
			
			$this->satxmlsv33_cargaAtt($carro,$data_carro);	
			
			$c_contenedor = json_decode($data['carta']['inCartaContenedoresCarro'], true) ?? []; 
			if(count($c_contenedor) > 0){
			foreach($c_contenedor as $cc) {
			$carro_contenedor = $this->xml->createElement("cartaporte20:Contenedor");
			$carro_contenedor = $carro->appendChild($carro_contenedor);
  		
			$data_contenedor_carro = [
			"TipoContenedor" => trim(explode("-",$cc['tipo'])[0]),
			"PesoContenedorVacio" => $cc['peso'],
			"PesoNetoMercancia" => $cc['peso_neto'],
			];	
			
			$this->satxmlsv33_cargaAtt($carro_contenedor,$data_contenedor_carro);	
			
			}
			}
			
			}
			
			//debug($data);
			$array_fugura_transporte = json_decode($data["carta"]["inFiguraTransporte"], true) ?? [];
			
			//debug($array_fugura_transporte);
			
			if(isset($data["carta"]["figura_transporte"]["aplica"]) && $data["carta"]["figura_transporte"]["aplica"] == 1 && count($array_fugura_transporte) > 0){
			
			$figura = $this->xml->createElement("cartaporte20:FiguraTransporte");
			$figura = $cartaporte->appendChild($figura);
			foreach($array_fugura_transporte as $figura_d){
			
			
			$tiposFiguraTransporte = $this->xml->createElement("cartaporte20:TiposFigura");
			$tiposFiguraTransporte = $figura->appendChild($tiposFiguraTransporte);
			
			$data_figura = [
			"TipoFigura"  =>$figura_d["tipo_figura"],
			"RFCFigura"=>$figura_d["rfc"],
			"NumLicencia"=>$figura_d["licencia"],
			"NombreFigura"=>self::cfdiUtf8Decode($figura_d["nombre"]),
			"NumRegIdTrib"=>$figura_d["tipo_figura"],
			"ResidenciaFiscalFigura"=>$figura_d["tipo_figura"]

			];		
			
			if($figura_d["tipo_figura"]!= "01"){
			unset($data_figura["NumLicencia"]);	
			}
			
			if($figura_d["rfc"] !=""){
				unset($data_figura['NumRegIdTrib']);
				unset($data_figura['ResidenciaFiscalFigura']);
			}
			$this->satxmlsv33_cargaAtt($tiposFiguraTransporte,$data_figura);
			
			
			if($figura_d["tipo_figura"] == "02" || $figura_d["tipo_figura"] == "03" && isset($figura_d['partes_transporte'])){
			foreach($figura_d['partes_transporte'] as $pt){
			$partes = $this->xml->createElement("cartaporte20:PartesTransporte");
			$partes = $tiposFiguraTransporte->appendChild($partes);
			$data_parte = [
			"ParteTransporte"=> explode("-",$pt["parte_transporte"])[0]
			];			
			$this->satxmlsv33_cargaAtt($partes,$data_parte);
			}
			}
			
			
			if(isset($figura_d["direccion"]) && $figura_d["direccion"] != ""){
			$domicilio = $this->xml->createElement("cartaporte20:Domicilio");
			$domicilio = $tiposFiguraTransporte->appendChild($domicilio);			
			$data_domicilio_figura = [
			"Calle" =>$figura_d["direccion"]["calle"],
			"NumeroExterior"  =>$figura_d["direccion"]["numero_exterior"],
			"NumeroInterior"=>$figura_d["direccion"]["numero_interior"],
			"Colonia"=>$figura_d["direccion"]["colonia"],
			"Localidad"=>$figura_d["direccion"]["localidad"],
			"Referencia"=>$figura_d["direccion"]["referencia"],
			"Municipio"=>$figura_d["direccion"]["municipio"],
			"Estado"=>$figura_d["direccion"]["estado"],
			"Pais"=>$figura_d["direccion"]["pais"],
			"CodigoPostal"=>$figura_d["direccion"]["codigo_postal"],
			];				
			
			$this->satxmlsv33_cargaAtt($domicilio,$data_domicilio_figura);
			}
			}
			
			}
			
			
   }
   
    function setCartaPorte30($data,$complemento){

		$complemento = $this->root->appendChild($complemento);
		$cartaporte = $this->xml->createElement("cartaporte30:CartaPorte");
		$cartaporte = $complemento->appendChild($cartaporte);

		/* Calcular TotalDistRec desde ubicaciones si el campo viene vacío */
		$_ubs30 = json_decode($data['carta30']['Ubicaciones'] ?? '[]', true) ?? [];
		$_totalDist30 = array_sum(array_map(function($u) {
			return ($u['TipoUbicacion'] !== 'Origen' && is_numeric($u['DistanciaRecorrida'] ?? ''))
				? (float)$u['DistanciaRecorrida'] : 0;
		}, $_ubs30));
		$_distFormVal = $data['carta30']['TotalDistRec'] ?? '';
		$_totalDistRec30 = (isset($_distFormVal) && $_distFormVal !== '' && $_distFormVal !== 'NaN')
			? $this->validarCeros($_distFormVal)
			: ($_totalDist30 > 0 ? $this->validarCeros($_totalDist30) : '');

		$carta= [
			"Version"	=>	"3.0" ,
			"IdCCP"		=> self::cfdiUtf8Decode($data['carta30']['IdCCP']),
			"TranspInternac"		=> self::cfdiUtf8Decode($data['carta30']['TranspInternac']),
			"RegimenAduanero"		=> isset($data['carta30']['RegimenAduanero'])?$data['carta30']['RegimenAduanero']:'',
			"EntradaSalidaMerc"		=> $data['carta30']['EntradaSalidaMerc'],
			"PaisOrigenDestino"		=> $data['carta30']['PaisOrigenDestino'],
			"ViaEntradaSalida"		=> $data['carta30']['ViaEntradaSalida'],
			"TotalDistRec"		=> $_totalDistRec30,
			"RegistroISTMO"		=> $data['carta30']['RegistroISTMO30'],
			"UbicacionPoloOrigen"		=> $data['carta30']['UbicacionPoloOrigen'],
			"UbicacionPoloDestino"		=> $data['carta30']['UbicacionPoloDestino'],
			
		
		];
		
		if($data['carta30']['TranspInternac'] == "No"){
			unset($carta['RegimenAduanero']);
			unset($carta['EntradaSalidaMerc']);
			unset($carta['PaisOrigenDestino']);
			unset($carta['ViaEntradaSalida']);
			//unset($carta['TranspInternac']);
			
		}
		
		if($data['carta30']['RegistroISTMO30'] == ''){
			unset($carta['RegistroISTMO']);
			unset($carta['UbicacionPoloOrigen']);
			unset($carta['UbicacionPoloDestino']);
		}
		
		if(!isset($data['carta30']['autotransporte']['uso']) && !isset($data["carta"]["ferroviario"]["uso"])){
			unset($carta['TotalDistRec']);
		}
		
		$this->satxmlsv33_cargaAtt($cartaporte,$carta);
		
		$ubicaciones = $this->xml->createElement("cartaporte30:Ubicaciones");
		$ubicaciones = $cartaporte->appendChild($ubicaciones);
		$u_bicaciones = json_decode($data['carta30']['Ubicaciones'], true) ?? [];
		usort($u_bicaciones, fn($a,$b) => ($a['TipoUbicacion'] === 'Origen' ? 0 : 1) - ($b['TipoUbicacion'] === 'Origen' ? 0 : 1));

		if(count($u_bicaciones)> 0 ){
		foreach($u_bicaciones as $u) {

			$ubicacion = $this->xml->createElement("cartaporte30:Ubicacion"); 
			$ubicacion = $ubicaciones->appendChild($ubicacion);
		
		//$tmpnombre_estacion = ($u['NumEstacion'] !="")?self::cfdiUtf8Decode(trim(explode("-",$u['NumEstacion'])[1])):"";
			$data_ubicacion = [
				"TipoUbicacion"=>$u["TipoUbicacion"],
				"IDUbicacion"=>$u["IDUbicacion"],
				"RFCRemitenteDestinatario"=>$u["RFCRemitenteDestinatario"],
				"NombreRemitenteDestinatario"=> self::cfdiUtf8Decode($u["NombreRemitenteDestinatario"]),
				"NumRegIdTrib"=>$u["NumRegIdTrib"] ?? '',
				"ResidenciaFiscal"=>$u["ResidenciaFiscal"] ?? '',
				//"NumEstacion"=>"V0102",
				//"NombreEstacion"=>$u["NavegacionTrafico"],
				"NumEstacion"=> isset($u['NumEstacion']) ? trim(explode("-", $u['NumEstacion'])[0]) : "",
				//"NombreEstacion"=> "SAN LORENZO",
				"NombreEstacion"=> isset($u['NombreEstacion']) && $u['NombreEstacion']!= ""?trim(self::cfdiUtf8Decode($u["NombreEstacion"])):'',
				"NavegacionTrafico"=>$u["NavegacionTrafico"] ?? '',
				"FechaHoraSalidaLlegada"=> strpos($u["FechaHoraSalidaLlegada"], 'T') ? $u["FechaHoraSalidaLlegada"] :  $u["FechaHoraSalidaLlegada"]."T".date("H:m:i"),
				"TipoEstacion"=>isset($u['TipoEstacion'])?trim(explode("-",$u['TipoEstacion'])[0]):"",
				//"TipoEstacion"=>$u["TipoEstacion"],
				"DistanciaRecorrida"=>$u["DistanciaRecorrida"] ?? '',
			];			
			
			if($u["RFCRemitenteDestinatario"] != "" && $u["RFCRemitenteDestinatario"]!="XEXX010101000"){
			unset($data_ubicacion['NumRegIdTrib']);	
			unset($data_ubicacion['ResidenciaFiscal']);
			}
			
			if($u["TipoUbicacion"] == "Origen"){
				unset($data_ubicacion['DistanciaRecorrida']);
			}
			/*if(!isset($data['carta']['mercancias']['federal']['uso']) && !isset($data["carta"]["mercancias"]["ferroviario"]["uso"])){
				unset($data_ubicacion['DistanciaRecorrida']);
			}*/
			
			if(isset($data['carta30']['aereo']['uso']) && $data['carta30']['aereo']['uso'] == 1){
			unset($data_ubicacion['DistanciaRecorrida']);
			
			}
			
			if(isset($data['carta30']['maritimo']['uso']) && $data['carta30']['maritimo']['uso'] == 1){
			unset($data_ubicacion['DistanciaRecorrida']);
			
			}			
			
			$this->satxmlsv33_cargaAtt($ubicacion,$data_ubicacion);	
					
				if(!empty($u['Domicilio'])){
				
				$domicilio = $this->xml->createElement("cartaporte30:Domicilio");
				$domicilio = $ubicacion->appendChild($domicilio);
		
				$data_domicilio = [
				"Calle" => isset($u['Domicilio']['Calle'])?self::cfdiUtf8Decode($u['Domicilio']['Calle']):"",
				"NumeroExterior"=> isset($u['Domicilio']['NumeroExterior'])?$u['Domicilio']['NumeroExterior']:"",
				"NumeroInterior"=> isset($u['Domicilio']['NumeroInterior'])?$u['Domicilio']['NumeroInterior']:"",
				"Colonia"=> isset($u['Domicilio']['Colonia'])?$u['Domicilio']['Colonia']:"",
				"Localidad"=> isset($u['Domicilio']['Localidad'])?$u['Domicilio']['Localidad']:"",
				"Referencia"=> isset($u['Domicilio']['Referencia'])?$u['Domicilio']['Referencia']:"",
				"Municipio"=> isset($u['Domicilio']['Municipio'])?$u['Domicilio']['Municipio']:"",
				"Estado"=> isset($u['Domicilio']['Estado'])?$u['Domicilio']['Estado']:"",
				"Pais"=> isset($u['Domicilio']['Pais'])?$u['Domicilio']['Pais']:"",
				"CodigoPostal"=> isset($u['Domicilio']['CodigoPostal'])?$u['Domicilio']['CodigoPostal']:"",
				];
				
				$this->satxmlsv33_cargaAtt($domicilio,$data_domicilio);	
				}
		}
	}
	
	
	/****valicacion*****/
	$tiposClvs = 0;
		if(isset($data["carta"]["mercancias"]["federal"]["uso"])){
		$tiposClvs++;
		}
		if(isset($data["carta"]["mercancias"]["maritimo"]["uso"])){
		$tiposClvs++;
		}
		
		if(isset($data["carta"]["mercancias"]["aereo"]["uso"])){
		$tiposClvs++;
		}
		
		if(isset($data["carta"]["mercancias"]["ferroviario"]["uso"])){
		$tiposClvs++;
		}
		
		
		
		$mercancias = $this->xml->createElement("cartaporte30:Mercancias");
		$mercancias = $cartaporte->appendChild($mercancias);
		$data_mercancia= [			
			"PesoBrutoTotal"	=>	isset($data['carta30']['mercancias']['PesoBrutoTotal'])?$data['carta30']['mercancias']['PesoBrutoTotal']:"",
			"UnidadPeso"		=> isset($data['carta30']['mercancias']['UnidadPeso'])?trim(explode("-",$data['carta30']['mercancias']['UnidadPeso'])[0]):"",
			"PesoNetoTotal"		=> isset($data['carta30']['mercancias']['PesoNetoTotal'])?$data['carta30']['mercancias']['PesoNetoTotal']:"",
			"NumTotalMercancias"		=> isset($data['carta30']['mercancias']['NumTotalMercancias'])?$data['carta30']['mercancias']['NumTotalMercancias']:"",
			"CargoPorTasacion"		=> isset($data['carta30']['mercancias']['CargoPorTasacion'])?$data['carta30']['mercancias']['CargoPorTasacion']:"",
			"LogisticaInversaRecoleccionDevolucion"		=> isset($data['carta30']['mercancias']['LogisticaInversaRecoleccionDevolucion'])?self::cfdiUtf8Decode($data['carta30']['mercancias']['LogisticaInversaRecoleccionDevolucion']):"",
			
			];
			
			
		$this->satxmlsv33_cargaAtt($mercancias,$data_mercancia);
		$m_ercancias = json_decode($data['carta30']['mercancias']['mercancia'], true) ?? []; 
		$quitarCTCT =false;
		if(count($m_ercancias)==1 ){
		$quitarCTCT =true;
		}
		//Pedro
		if ($m_ercancias != '') {
			foreach($m_ercancias as $m) {
				//debug($m['PesoEnKg']);
				if(isset(explode("-",$m['ClaveUnidad'])[1])){
				$unidad_nombre = self::cfdiUtf8Decode(trim(explode("-",$m['ClaveUnidad'])[1]));
				$unidad_nombre = substr($unidad_nombre, 0, 20);
				}else{
				$unidad_nombre = "";
				}
				$mercancia = $this->xml->createElement("cartaporte30:Mercancia"); 
				$mercancia = $mercancias->appendChild($mercancia);
				$data_mercancia = [
				"BienesTransp" => isset($m['BienesTransp'])?trim(explode("-",$m['BienesTransp'])[0]):'',
				"ClaveSTCC"     => isset($m['ClaveSTCC'])?trim(explode("-",$m['ClaveSTCC'])[0]):'',
				"Descripcion"     => isset($m['Descripcion'])?self::cfdiUtf8Decode($m['Descripcion']):'',
				"Cantidad"     => isset($m['Cantidad'])?$m['Cantidad']:'',
				"ClaveUnidad"     => isset($m['ClaveUnidad'])?trim(explode("-",$m['ClaveUnidad'])[0]):'',
				"Unidad"     => isset($m['Unidad'])?substr($m['Unidad'], 0, 20):'',
				"Dimensiones"     => isset($m['Dimensiones'])?$m['Dimensiones']:'',
				"MaterialPeligroso"     => isset($m['MaterialPeligroso'])?self::cfdiUtf8Decode($m['MaterialPeligroso']):'',
				"CveMaterialPeligroso"     => isset($m['CveMaterialPeligroso'])?trim(explode("-",$m['CveMaterialPeligroso'])[0]):'',
				"Embalaje"     => isset($m['Embalaje'])?trim(explode("-",$m['Embalaje'])[0]):'',
				"DescripEmbalaje"     => isset($m['DescripEmbalaje'])?$m['DescripEmbalaje']:'', 
				"SectorCOFEPRIS"     => isset($m['SectorCOFEPRIS'])?trim(explode("-",$m['SectorCOFEPRIS'])[0]):'',
				"NombreIngredienteActivo"  => isset($m['NombreIngredienteActivo'])?$m['NombreIngredienteActivo']:'',
				"NomQuimico"  => isset($m['NomQuimico'])?$m['NomQuimico']:'',
				"DenominacionGenericaProd"  => isset($m['DenominacionGenericaProd'])?$m['DenominacionGenericaProd']:'',
				"DenominacionDistintivaProd"  => isset($m['DenominacionDistintivaProd'])?$m['DenominacionDistintivaProd']:'',
				"Fabricante"  => isset($m['Fabricante'])?$m['Fabricante']:'',
				"FechaCaducidad"  => isset($m['FechaCaducidad'])?$m['FechaCaducidad']:'',
				"LoteMedicamento"  => isset($m['LoteMedicamento'])?$m['LoteMedicamento']:'',
				"FormaFarmaceutica"  => isset($m['FormaFarmaceutica'])?trim(explode("-",$m['FormaFarmaceutica'])[0]):'',
				"CondicionesEspTransp"  => isset($m['CondicionesEspTransp'])?trim(explode("-",$m['CondicionesEspTransp'])[0]):'',
				"RegistroSanitarioFolioAutorizacion"  => isset($m['RegistroSanitarioFolioAutorizacion'])?$m['RegistroSanitarioFolioAutorizacion']:'',
				"PermisoImportacion" => isset($m['PermisoImportacion'])?$m['PermisoImportacion']:'',
				"FolioImpoVUCEM"  => isset($m['FolioImpoVUCEM'])?$m['FolioImpoVUCEM']:'',
				"NumCAS" => isset($m['NumCAS'])?$m['NumCAS']:'',
				"RazonSocialEmpImp" => isset($m['RazonSocialEmpImp'])?$m['RazonSocialEmpImp']:'',
				"NumRegSanPlagCOFEPRIS" => isset($m['NumRegSanPlagCOFEPRIS'])?$m['NumRegSanPlagCOFEPRIS']:'',
				"DatosFabricante" => isset($m['DatosFabricante'])? $m['DatosFabricante']:'',
				"DatosFormulador" => isset($m['DatosFormulador'])?$m['DatosFormulador']:'',
				"DatosMaquilador" => isset($m['DatosMaquilador'])?$m['DatosMaquilador']:'',
				"UsoAutorizado" => isset($m['UsoAutorizado'])?$m['UsoAutorizado']:'',
				"PesoEnKg"  => isset($m['PesoEnKg'])?$m['PesoEnKg']:'',
				"ValorMercancia"  => isset($m['ValorMercancia'])?$m['ValorMercancia']:'',
				"Moneda"  => isset($m['Moneda'])?$m['Moneda']:'',
				"FraccionArancelaria"   => isset($m['FraccionArancelaria'])?explode("-",$m['FraccionArancelaria'])[0]:'',
				"UUIDComercioExt"  => isset($m['UUIDComercioExt'])?$m['UUIDComercioExt']:'',
				"TipoMateria"  =>isset($m['TipoMateria'])?explode("-",$m['TipoMateria'])[0] :'',
				"DescripcionMateria"  => isset($m['DescripcionMateria'])?$m['DescripcionMateria']:'',
				];	
				
				if($data_mercancia["MaterialPeligroso"] =="" && $data_mercancia["CveMaterialPeligroso"]=="" ){
					unset($data_mercancia["MaterialPeligroso"]);
					unset($data_mercancia["CveMaterialPeligroso"]);
				}
				
				if(($m['estadoMaterialPeligroso'] ?? '1') == "0"){
					unset($data_mercancia["MaterialPeligroso"]);
					unset($data_mercancia["CveMaterialPeligroso"]);
				}
			
				
				$this->satxmlsv33_cargaAtt($mercancia,$data_mercancia);	
				
				if(isset($m['documentosAduaneros'])){
					
					foreach($m['documentosAduaneros'] as $p){
						$pedimento = $this->xml->createElement("cartaporte30:DocumentacionAduanera");
						$pedimento = $mercancia->appendChild($pedimento);			
						$data_pedimento = [
							"TipoDocumento" =>  explode("-",$p["TipoDocumento"])[0],
							"NumPedimento" => $p["NumPedimento"],
							"IdentDocAduanero" => $p["IdentDocAduanero"],
							"RFCImpo" => $p["RFCImpo"],
						];	
						$this->satxmlsv33_cargaAtt($pedimento,$data_pedimento);
					}
				}
				
				if(isset($m['guiasIdentificacion'])){
				
				foreach($m['guiasIdentificacion'] as $g){
				$guia = $this->xml->createElement("cartaporte30:GuiasIdentificacion");
				$guia = $mercancia->appendChild($guia);			
				$data_guia = [
				"NumeroGuiaIdentificacion"=>$g["NumeroGuiaIdentificacion"],
				"DescripGuiaIdentificacion"=>$g["DescripGuiaIdentificacion"],
				"PesoGuiaIdentificacion"=>$g["PesoGuiaIdentificacion"],
				];	
				$this->satxmlsv33_cargaAtt($guia,$data_guia);
				}
				}
								
				if(!empty($m['CantidadTransporta'])){
				
				foreach($m['CantidadTransporta'] as $ct){
				$cantidad_transporta = $this->xml->createElement("cartaporte30:CantidadTransporta");
				$cantidad_transporta = $mercancia->appendChild($cantidad_transporta);
				$data_mercancia = [
					"Cantidad" => $ct['Cantidad'],
					"IDOrigen" => $ct['IDOrigen'],
					"IDDestino"     => $ct['IDDestino'],
					"CvesTransporte"     => $ct['CvesTransporte'],
				];	
				
				if($quitarCTCT == true || $tiposClvs <= 1){
				unset($data_mercancia['CvesTransporte']);
				}
				$this->satxmlsv33_cargaAtt($cantidad_transporta,$data_mercancia);
				}
				}
			
				if(isset($m['detalle']) && $m["detalle"]["PesoBruto"]!=""){
				$detalle_mercancia = $this->xml->createElement("cartaporte30:DetalleMercancia");
				$detalle_mercancia = $mercancia->appendChild($detalle_mercancia);
				
				$data_detalle_mercancia = [
					"UnidadPesoMerc" => trim(explode("-",$m["detalle"]["UnidadPesoMerc"])[0]),
					"PesoBruto" => isset($m["detalle"]["PesoBruto"])?$m["detalle"]["PesoBruto"]:'',
					"PesoNeto"     => isset($m["detalle"]["PesoNeto"])?$m["detalle"]["PesoNeto"]:'',
					"PesoTara"     => isset($m["detalle"]["PesoTara"])?$m["detalle"]["PesoTara"]:'',
					"NumPiezas"     => isset($m["detalle"]["NumPiezas"])?$m["detalle"]["NumPiezas"]:'',
					];					 
				$this->satxmlsv33_cargaAtt($detalle_mercancia,$data_detalle_mercancia);
				}
			}
		}

		if(isset($data["carta30"]["autotransporte"]["uso"]) && $data["carta30"]["autotransporte"]["uso"] == 1){
			$federal = $this->xml->createElement("cartaporte30:Autotransporte");
			$federal = $mercancias->appendChild($federal);
			
			
			$data_federal = [
			"PermSCT" => trim(explode("-",$data['carta30']['autotransporte']['PermSCT'])[0]),
			"NumPermisoSCT"     => $data['carta30']['autotransporte']['NumeroPermisoSCT'],
			
			];	
			$this->satxmlsv33_cargaAtt($federal,$data_federal);	
			
			$identificacion = $this->xml->createElement("cartaporte30:IdentificacionVehicular");
			$identificacion = $federal->appendChild($identificacion);			
			$data_identificacion = [
			"ConfigVehicular" => trim(explode("-",$data['carta30']['autotransporte']['IdentificacionVehicular']['ConfigVehicular'])[0]),
			"PesoBrutoVehicular"     => $data['carta30']['autotransporte']['IdentificacionVehicular']['PesoBrutoVehicular'],
			"PlacaVM"     => $data['carta30']['autotransporte']['IdentificacionVehicular']['PlacaVM'],
			"AnioModeloVM"     => $data['carta30']['autotransporte']['IdentificacionVehicular']['AnioModeloVM'],
			];	
			$this->satxmlsv33_cargaAtt($identificacion,$data_identificacion);
			
			
			
			$seguros = $this->xml->createElement("cartaporte30:Seguros");
			$seguros = $federal->appendChild($seguros);			
			$data_seguro = [
			"AseguraRespCivil"=>isset($data['carta30']['autotransporte']['seguro']['AseguraRespCivil'])?self::cfdiUtf8Decode($data['carta30']['autotransporte']['seguro']['AseguraRespCivil']):'',
			"PolizaRespCivil"=>isset($data['carta30']['autotransporte']['seguro']['PolizaRespCivil'])?$data['carta30']['autotransporte']['seguro']['PolizaRespCivil']:'',
			"AseguraMedAmbiente"=>isset($data['carta30']['autotransporte']['seguro']['AseguraMedAmbiente'])?self::cfdiUtf8Decode($data['carta30']['autotransporte']['seguro']['AseguraMedAmbiente']):'',
			"PolizaMedAmbiente"=>isset($data['carta30']['autotransporte']['seguro']['PolizaMedAmbiente'])?$data['carta30']['autotransporte']['seguro']['PolizaMedAmbiente']:'',
			"AseguraCarga"=>isset($data['carta30']['autotransporte']['seguro']['AseguraCarga'])?$data['carta30']['autotransporte']['seguro']['AseguraCarga']:'',
			"PolizaCarga"=>isset($data['carta30']['autotransporte']['seguro']['PolizaCarga'])?$data['carta30']['autotransporte']['seguro']['PolizaCarga']:'',
			"PrimaSeguro"=>isset($data['carta30']['autotransporte']['seguro']['PrimaSeguro'])?$data['carta30']['autotransporte']['seguro']['PrimaSeguro']:'',			
			];	
			$this->satxmlsv33_cargaAtt($seguros,$data_seguro);
			
			
			//if($data['carta30']['autotransporte']['Remolque'][0]['SubTipoRem'] != ""){
			
			if(!empty($data['carta30']['autotransporte']['Remolque']) && isset($data['carta30']['autotransporte']['Remolque'][0]['SubTipoRem']) && $data['carta30']['autotransporte']['Remolque'][0]['SubTipoRem'] != ''){
			
			$remolques = $this->xml->createElement("cartaporte30:Remolques");
			$remolques = $federal->appendChild($remolques);			
				if(isset($data['carta30']['autotransporte']['Remolque'][0]['SubTipoRem']) && $data['carta30']['autotransporte']['Remolque'][0]['SubTipoRem'] != ''){
						$remolque1 = $this->xml->createElement("cartaporte30:Remolque");
						$remolque1 = $remolques->appendChild($remolque1);			
						$data_remolque1 = [
						"SubTipoRem" => trim(explode("-",$data['carta30']['autotransporte']['Remolque'][0]['SubTipoRem'])[0]),
						"Placa" => $data['carta30']['autotransporte']['Remolque'][0]['Placa'],
						];	
						$this->satxmlsv33_cargaAtt($remolque1,$data_remolque1);	
				}
				
				if(isset($data['carta30']['autotransporte']['Remolque'][1]['SubTipoRem']) && $data['carta30']['autotransporte']['Remolque'][1]['SubTipoRem']!= ""){
						$remolque2 = $this->xml->createElement("cartaporte30:Remolque");
						$remolque2 = $remolques->appendChild($remolque2);			
						$data_remolque2 = [
						"SubTipoRem" => trim(explode("-",$data['carta30']['autotransporte']['Remolque'][1]['SubTipoRem'])[0]),
						"Placa" => $data['carta30']['autotransporte']['Remolque'][1]['Placa'],
						];	
						$this->satxmlsv33_cargaAtt($remolque2,$data_remolque2);	
				}
			}
			//}
			}
			
			if(isset($data["carta30"]["maritimo"]["uso"]) && $data["carta30"]["maritimo"]["uso"] == 1){
			$maritimo = $this->xml->createElement("cartaporte30:TransporteMaritimo");
			$maritimo = $mercancias->appendChild($maritimo);
			
			
			$data_maritimo = [
			"PermSCT"=> trim(explode("-",$data['carta30']['maritimo']["PermSCT"])[0]),
			"NumPermisoSCT"=>$data['carta30']['maritimo']["NumPermisoSCT"],
			"NombreAseg"=>self::cfdiUtf8Decode($data['carta30']['maritimo']["NombreAseg"]),
			"NumPolizaSeguro"=> $data['carta30']['maritimo']["NumPolizaSeguro"],
			"TipoEmbarcacion"=>trim(explode("-",$data['carta30']['maritimo']["TipoEmbarcacion"])[0]),
			"Matricula"=>$data['carta30']['maritimo']["Matricula"],
			"NumeroOMI"=>$data['carta30']['maritimo']["NumeroOMI"],
			"AnioEmbarcacion"=>$data['carta30']['maritimo']["AnioEmbarcacion"],
			"NombreEmbarc"=>$data['carta30']['maritimo']["NombreEmbarc"],
			"NacionalidadEmbarc"=>$data['carta30']['maritimo']["NacionalidadEmbarc"],
			"UnidadesDeArqBruto"=>$data['carta30']['maritimo']["UnidadesDeArqBruto"],
			"TipoCarga"=>trim(explode("-",$data['carta30']['maritimo']["TipoCarga"])[0]),
			//"NumCertITC"=>$data['carta30']['maritimo']["NumCertITC"],
			"Eslora"=>$data['carta30']['maritimo']["Eslora"],
			"Manga"=>$data['carta30']['maritimo']["Manga"], 
			"Calado"=>$data['carta30']['maritimo']["Calado"],
			"Puntal"=>$data['carta30']['maritimo']["Puntal"],
			"LineaNaviera"=>$data['carta30']['maritimo']["LineaNaviera"],
			"NombreAgenteNaviero"=>$data['carta30']['maritimo']["NombreAgenteNaviero"],
			"NumAutorizacionNaviero"=>$data['carta30']['maritimo']["NumAutorizacionNaviero"],
			"NumViaje"=>$data['carta30']['maritimo']["NumViaje"],
			"NumConocEmbarc"=>$data['carta30']['maritimo']["NumConocEmbarc"],
			"PermisoTempNavegacion"=>$data['carta30']['maritimo']["PermisoTempNavegacion"],
			];	
			$this->satxmlsv33_cargaAtt($maritimo,$data_maritimo);
			
			
			
			$m_contenedor = json_decode($data['carta30']['maritimo']['contenedores'], true) ?? [];
			if(count($m_contenedor)> 0){
			foreach($m_contenedor as $c) {
			$contenedor = $this->xml->createElement("cartaporte30:Contenedor");
			$contenedor = $maritimo->appendChild($contenedor);
		
			$data_contenedor = [
			"TipoContenedor"     => trim(explode("-",$c['TipoContenedor'])[0]),
			"MatriculaContenedor" => $c['MatriculaContenedor'],
			"NumPrecinto" => $c['NumPrecinto'],
			"IdCCPRelacionado" => $c['IdCCPRelacionado'],
			"PlacaVMCCP" => $c['PlacaVMCCP'],			
			"FechaCertificacionCCP"     => $c['FechaCertificacionCCP'],
			];	
			if($data_contenedor['TipoContenedor'] == 'CM011'){
				unset($data_contenedor['MatriculaContenedor']);
				unset($data_contenedor['NumPrecinto']);
			}
			
			$this->satxmlsv33_cargaAtt($contenedor,$data_contenedor);	
			
			}
			
			}
			if(isset($data['carta30']['maritimo']['remolque'][0]['SubTipoRemCCP']) && $data['carta30']['maritimo']['remolque'][0]['SubTipoRemCCP'] != ''){
				
			$remolques = $this->xml->createElement("cartaporte30:RemolquesCCP");
			$remolques = $maritimo->appendChild($remolques);			
						$remolque1 = $this->xml->createElement("cartaporte30:RemolqueCCP");
						$remolque1 = $remolques->appendChild($remolque1);			
						$data_remolque1 = [
						"SubTipoRem" => trim(explode("-",$data['carta30']['maritimo']['remolque'][0]['SubTipoRemCCP'])[0]),
						"PlacaCCP" => $data['carta30']['maritimo']['remolque'][0]['PlacaCCP'],
						];	
						$this->satxmlsv33_cargaAtt($remolque1,$data_remolque1);	
				
			}
			
			}
			
			if(isset($data["carta30"]["aereo"]["uso"]) && $data["carta30"]["aereo"]["uso"] == 1){
			$aereo = $this->xml->createElement("cartaporte30:TransporteAereo");
			$aereo = $mercancias->appendChild($aereo);
			$data_aereo = [
			"PermSCT"=> isset($data['carta30']['aereo']["PermSCT"])?trim(explode("-",$data['carta30']['aereo']["PermSCT"])[0]):"",
			"NumPermisoSCT"=>isset($data['carta30']['aereo']["NumPermisoSCT"])?$data['carta30']['aereo']["NumPermisoSCT"]:"",
			"MatriculaAeronave"=>isset($data['carta30']['aereo']["MatriculaAeronave"])?$data['carta30']['aereo']["MatriculaAeronave"]:"",
			"NombreAseg"=>isset($data['carta30']['aereo']["NombreAseg"])?$data['carta30']['aereo']["NombreAseg"]:"",
			"NumPolizaSeguro"=> isset($data['carta30']['aereo']["NumPolizaSeguro"])?$data['carta30']['aereo']["NumPolizaSeguro"]:"",
			"NumeroGuia"=>isset($data['carta30']['aereo']["NumeroGuia"])?$data['carta30']['aereo']["NumeroGuia"]:"",
			"LugarContrato"=>isset($data['carta30']['aereo']["LugarContrato"])?$data['carta30']['aereo']["LugarContrato"]:"",
			"CodigoTransportista"=>isset($data['carta30']['aereo']["CodigoTransportista"])?trim(explode("-",$data['carta30']['aereo']["CodigoTransportista"])[0]):"",
			"RFCEmbarcador"=>isset($data['carta30']['aereo']["RFCEmbarcador"])?$data['carta30']['aereo']["RFCEmbarcador"]:"",
			"NumRegIdTribEmbarc"=>isset($data['carta30']['aereo']["NumRegIdTribEmbarc"])?$data['carta30']['aereo']["NumRegIdTribEmbarc"]:"",
			"ResidenciaFiscalEmbarc"=>isset($data['carta30']['aereo']["ResidenciaFiscalEmbarc"])?$data['carta30']['aereo']["ResidenciaFiscalEmbarc"]:"",
			"NombreEmbarcador"=>isset($data['carta30']['aereo']["NombreEmbarcador"])?$data['carta30']['aereo']["NombreEmbarcador"]:"",
			];	
			$this->satxmlsv33_cargaAtt($aereo,$data_aereo);
			 
			
			}
			
			if(isset($data["carta30"]["ferroviario"]["uso"]) && $data["carta30"]["ferroviario"]["uso"] ==1){
			$ferroveario = $this->xml->createElement("cartaporte30:TransporteFerroviario");
			$ferroveario = $mercancias->appendChild($ferroveario);
			$data_ferroveario = [
			"TipoDeServicio"=> trim(explode("-",$data["carta30"]["ferroviario"]["TipoDeServicio"])[0]),
			"TipoDeTrafico"=>trim(explode("-",$data["carta30"]["ferroviario"]["TipoDeTrafico"])[0]),
			"NombreAseg"=>self::cfdiUtf8Decode($data["carta30"]["ferroviario"]["NombreAseg"]),
			"NumPolizaSeguro"=>$data["carta30"]["ferroviario"]["NumPolizaSeguro"],
			
			];	
			$this->satxmlsv33_cargaAtt($ferroveario,$data_ferroveario);
			
			$d_paso = json_decode($data["carta30"]["ferroviario"]['DerechoDePaso']['Lista'], true) ?? []; 
			if(count($d_paso) >0){
			foreach($d_paso as $cp) {
			$paso = $this->xml->createElement("cartaporte30:DerechosDePaso");
			$paso = $ferroveario->appendChild($paso);
		
			$data_paso = [
			"TipoDerechoDePaso" => trim(explode("-",$cp['TipoDerechoDePaso'])[0]),
			"KilometrajePagado"     => $cp['KilometrajePagado'],
			];	
			
			$this->satxmlsv33_cargaAtt($paso,$data_paso);	
			
			}
			}
			
			$carro = $this->xml->createElement("cartaporte30:Carro");
			$carro = $ferroveario->appendChild($carro);
		
			$data_carro = [
			"TipoCarro" => trim(explode("-",$data["carta30"]["ferroviario"]["Carro"]["TipoCarro"])[0]),
			"MatriculaCarro"     => $data["carta30"]["ferroviario"]["Carro"]["MatriculaCarro"],
			"GuiaCarro"     => $data["carta30"]["ferroviario"]["Carro"]["GuiaCarro"],
			"ToneladasNetasCarro"     => $data["carta30"]["ferroviario"]["Carro"]["ToneladasNetasCarro"],
			];	
			
			$this->satxmlsv33_cargaAtt($carro,$data_carro);	
			
			$c_contenedor = json_decode($data["carta30"]["ferroviario"]["Carro"]["contenedores"], true) ?? []; 
			if(count($c_contenedor) > 0){
			foreach($c_contenedor as $cc) {
			$carro_contenedor = $this->xml->createElement("cartaporte30:Contenedor");
			$carro_contenedor = $carro->appendChild($carro_contenedor);
  		
			$data_contenedor_carro = [
			"TipoContenedor" => trim(explode("-",$cc['TipoContenedor'])[0]),
			"PesoContenedorVacio" => $cc['PesoContenedorVacio'],
			"PesoNetoMercancia" => $cc['PesoNetoMercancia'],
			];	
			
			$this->satxmlsv33_cargaAtt($carro_contenedor,$data_contenedor_carro);	
			
			}
			}
			
			}
			
			$array_fugura_transporte = json_decode($data["carta30"]["FiguraTransporte"], true) ?? [];
			
			
			
			if(count($array_fugura_transporte) > 0){
			
			$figura = $this->xml->createElement("cartaporte30:FiguraTransporte");
			$figura = $cartaporte->appendChild($figura);
			foreach($array_fugura_transporte as $figura_d){
			
			
			$tiposFiguraTransporte = $this->xml->createElement("cartaporte30:TiposFigura");
			$tiposFiguraTransporte = $figura->appendChild($tiposFiguraTransporte);
			
			$data_figura = [
			"TipoFigura"  =>explode("-",$figura_d["TipoFigura"])[0],
			"RFCFigura"=>$figura_d["RFCfigura"],
			"NumLicencia"=>isset($figura_d["NumLicencia"])?$figura_d["NumLicencia"]:'',
			"NombreFigura"=>self::cfdiUtf8Decode($figura_d["NombreFigura"]),
			"NumRegIdTribFigura"=>$figura_d["NumRegIdTribFigura"],
			"ResidenciaFiscalFigura"=>$figura_d["ResidenciaFiscalFigura"],
			];		
			
			if($data_figura["TipoFigura"]!= "01"){
			unset($data_figura["NumLicencia"]);	
			}
			
			if($data_figura["RFCFigura"] !=""){
				unset($data_figura['NumRegIdTrib']);
				unset($data_figura['ResidenciaFiscalFigura']);
			}
			$this->satxmlsv33_cargaAtt($tiposFiguraTransporte,$data_figura);
			
			//debug($figura_d['PartesTransporte']);
			//debug($figura_d["TipoFigura"]);
			if($figura_d["TipoFigura"] == "02-Propietario" && isset($figura_d['PartesTransporte']) || $figura_d["TipoFigura"] == "03-Arrendador" && isset($figura_d['PartesTransporte'])){
			//debug($figura_d['PartesTransporte']);
			foreach($figura_d['PartesTransporte'] as $pt){
			$partes = $this->xml->createElement("cartaporte30:PartesTransporte");
			$partes = $tiposFiguraTransporte->appendChild($partes);
			$data_parte = [
			"ParteTransporte"=> explode("-",$pt)[0]
			];			
			$this->satxmlsv33_cargaAtt($partes,$data_parte);
			}
			}
			
			//debug($figura_d);
			if(isset($figura_d["direccion"]) && $figura_d["direccion"] == 1){
			$domicilio = $this->xml->createElement("cartaporte30:Domicilio");
			$domicilio = $tiposFiguraTransporte->appendChild($domicilio);			
			$data_domicilio_figura = [
			"Calle" =>self::cfdiUtf8Decode($figura_d["figuraCalle"]),
			"NumeroExterior"  =>$figura_d["figuraNumeroExterior"],
			"NumeroInterior"=>$figura_d["figuraNumeroInterior"],
			"Colonia"=>$figura_d["figuraColonia"],
			"Localidad"=>$figura_d["figuraLocalidad"],
			"Referencia"=>$figura_d["figuraReferencia"],
			"Municipio"=>$figura_d["figuraMunicipio"],
			"Estado"=>$figura_d["figuraEstado"],
			"Pais"=>$figura_d["figuraPais"],
			"CodigoPostal"=>$figura_d["figuraCodigoPostal"],
			];				
			
			$this->satxmlsv33_cargaAtt($domicilio,$data_domicilio_figura);
			}
			}
			
			}
			
			
   }
   
   
   
  function setCartaPorte31($data,$complemento){

		$complemento = $this->root->appendChild($complemento);
		$cartaporte = $this->xml->createElement("cartaporte31:CartaPorte");
		$cartaporte = $complemento->appendChild($cartaporte);

		/* Calcular TotalDistRec desde ubicaciones si el campo viene vacío */
		$_ubs31 = json_decode($data['carta30']['Ubicaciones'] ?? '[]', true) ?? [];
		$_totalDist31 = array_sum(array_map(function($u) {
			return ($u['TipoUbicacion'] !== 'Origen' && is_numeric($u['DistanciaRecorrida'] ?? ''))
				? (float)$u['DistanciaRecorrida'] : 0;
		}, $_ubs31));
		$_distFormVal31 = $data['carta30']['TotalDistRec'] ?? '';
		$_totalDistRec31 = (isset($_distFormVal31) && $_distFormVal31 !== '' && $_distFormVal31 !== 'NaN')
			? $this->validarCeros($_distFormVal31)
			: ($_totalDist31 > 0 ? $this->validarCeros($_totalDist31) : '');

		$carta= [
			"Version"	=>	"3.1" ,
			"IdCCP"		=> self::cfdiUtf8Decode($data['carta30']['IdCCP']),
			"TranspInternac"		=> self::cfdiUtf8Decode($data['carta30']['TranspInternac']),
			//"RegimenAduanero"		=> isset($data['carta30']['RegimenAduanero'])?$data['carta30']['RegimenAduanero']:'',
			"EntradaSalidaMerc"		=> $data['carta30']['EntradaSalidaMerc'],
			"PaisOrigenDestino"		=> $data['carta30']['PaisOrigenDestino'],
			"ViaEntradaSalida"		=> $data['carta30']['ViaEntradaSalida'],
			"TotalDistRec"		=> $_totalDistRec31,
			"RegistroISTMO"		=> $data['carta30']['RegistroISTMO30'],
			"UbicacionPoloOrigen"		=> $data['carta30']['UbicacionPoloOrigen'],
			"UbicacionPoloDestino"		=> $data['carta30']['UbicacionPoloDestino'],
			
		
		];
		
		if($data['carta30']['TranspInternac'] == "No"){
			unset($carta['RegimenAduanero']);
			unset($carta['EntradaSalidaMerc']);
			unset($carta['PaisOrigenDestino']);
			unset($carta['ViaEntradaSalida']);
			//unset($carta['TranspInternac']);
			
		}
		
		if($data['carta30']['RegistroISTMO30'] == ''){
			unset($carta['RegistroISTMO']);
			unset($carta['UbicacionPoloOrigen']);
			unset($carta['UbicacionPoloDestino']);
		}
		
		if(!isset($data['carta30']['autotransporte']['uso']) && !isset($data["carta"]["ferroviario"]["uso"])){
			unset($carta['TotalDistRec']);
		}
		
		$this->satxmlsv33_cargaAtt($cartaporte,$carta);
		
		
		
		if($data['carta30']['TranspInternac'] == "Sí"){
			$regimenes = json_decode($data['carta30']['Regimenes'], true) ?? []; 
			
			$regimen_s = $this->xml->createElement("cartaporte31:RegimenesAduaneros");
			$regimen_s = $cartaporte->appendChild($regimen_s);
			foreach($regimenes as $re) {
				
			
			
			$regimen = $this->xml->createElement("cartaporte31:RegimenAduaneroCCP"); 
			$regimen = $regimen_s->appendChild($regimen);
			
			$this->satxmlsv33_cargaAtt($regimen,["RegimenAduanero"=>$re['regimen']]);	
			}
		}
		
		$ubicaciones = $this->xml->createElement("cartaporte31:Ubicaciones");
		$ubicaciones = $cartaporte->appendChild($ubicaciones);
		$u_bicaciones = json_decode($data['carta30']['Ubicaciones'], true) ?? [];
		usort($u_bicaciones, fn($a,$b) => ($a['TipoUbicacion'] === 'Origen' ? 0 : 1) - ($b['TipoUbicacion'] === 'Origen' ? 0 : 1));

		if(count($u_bicaciones)> 0 ){
		foreach($u_bicaciones as $u) {
			
			$ubicacion = $this->xml->createElement("cartaporte31:Ubicacion"); 
			$ubicacion = $ubicaciones->appendChild($ubicacion);
		
		//$tmpnombre_estacion = ($u['NumEstacion'] !="")?self::cfdiUtf8Decode(trim(explode("-",$u['NumEstacion'])[1])):"";
			$data_ubicacion = [
				"TipoUbicacion"=>$u["TipoUbicacion"],
				"IDUbicacion"=>$u["IDUbicacion"],
				"RFCRemitenteDestinatario"=>$u["RFCRemitenteDestinatario"],
				"NombreRemitenteDestinatario"=> self::cfdiUtf8Decode($u["NombreRemitenteDestinatario"]),
				"NumRegIdTrib"=>$u["NumRegIdTrib"] ?? '',
				"ResidenciaFiscal"=>$u["ResidenciaFiscal"] ?? '',
				//"NumEstacion"=>"V0102",
				//"NombreEstacion"=>$u["NavegacionTrafico"],
				"NumEstacion"=> isset($u['NumEstacion']) ? trim(explode("-", $u['NumEstacion'])[0]) : "",
				//"NombreEstacion"=> "SAN LORENZO",
				"NombreEstacion"=> isset($u['NombreEstacion']) && $u['NombreEstacion']!= ""?trim(self::cfdiUtf8Decode($u["NombreEstacion"])):'',
				"NavegacionTrafico"=>$u["NavegacionTrafico"] ?? '',
				"FechaHoraSalidaLlegada"=> strpos($u["FechaHoraSalidaLlegada"], 'T') ? $u["FechaHoraSalidaLlegada"] :  $u["FechaHoraSalidaLlegada"]."T".date("H:m:i"),
				"TipoEstacion"=>isset($u['TipoEstacion'])?trim(explode("-",$u['TipoEstacion'])[0]):"",
				//"TipoEstacion"=>$u["TipoEstacion"],
				"DistanciaRecorrida"=>$u["DistanciaRecorrida"] ?? '',
			];			
			
			if($u["RFCRemitenteDestinatario"] != "" && $u["RFCRemitenteDestinatario"]!="XEXX010101000"){
			unset($data_ubicacion['NumRegIdTrib']);	
			unset($data_ubicacion['ResidenciaFiscal']);
			}
			
			if($u["TipoUbicacion"] == "Origen"){
				unset($data_ubicacion['DistanciaRecorrida']);
			}
			/*if(!isset($data['carta']['mercancias']['federal']['uso']) && !isset($data["carta"]["mercancias"]["ferroviario"]["uso"])){
				unset($data_ubicacion['DistanciaRecorrida']);
			}*/
			
			if(isset($data['carta30']['aereo']['uso']) && $data['carta30']['aereo']['uso'] == 1){
			unset($data_ubicacion['DistanciaRecorrida']);
			
			}
			
			if(isset($data['carta30']['maritimo']['uso']) && $data['carta30']['maritimo']['uso'] == 1){
			unset($data_ubicacion['DistanciaRecorrida']);
			
			}			
			
			$this->satxmlsv33_cargaAtt($ubicacion,$data_ubicacion);	
					
				if(!empty($u['Domicilio'])){
				
				$domicilio = $this->xml->createElement("cartaporte31:Domicilio");
				$domicilio = $ubicacion->appendChild($domicilio);
		
				$data_domicilio = [
				"Calle" => isset($u['Domicilio']['Calle'])?self::cfdiUtf8Decode($u['Domicilio']['Calle']):"",
				"NumeroExterior"=> isset($u['Domicilio']['NumeroExterior'])?$u['Domicilio']['NumeroExterior']:"",
				"NumeroInterior"=> isset($u['Domicilio']['NumeroInterior'])?$u['Domicilio']['NumeroInterior']:"",
				"Colonia"=> isset($u['Domicilio']['Colonia'])?$u['Domicilio']['Colonia']:"",
				"Localidad"=> isset($u['Domicilio']['Localidad'])?$u['Domicilio']['Localidad']:"",
				"Referencia"=> isset($u['Domicilio']['Referencia'])?$u['Domicilio']['Referencia']:"",
				"Municipio"=> isset($u['Domicilio']['Municipio'])?$u['Domicilio']['Municipio']:"",
				"Estado"=> isset($u['Domicilio']['Estado'])?$u['Domicilio']['Estado']:"",
				"Pais"=> isset($u['Domicilio']['Pais'])?$u['Domicilio']['Pais']:"",
				"CodigoPostal"=> isset($u['Domicilio']['CodigoPostal'])?$u['Domicilio']['CodigoPostal']:"",
				];
				
				$this->satxmlsv33_cargaAtt($domicilio,$data_domicilio);	
				}
		}
	}
	
	
	/****valicacion*****/
	$tiposClvs = 0;
		if(isset($data["carta"]["mercancias"]["federal"]["uso"])){
		$tiposClvs++;
		}
		if(isset($data["carta"]["mercancias"]["maritimo"]["uso"])){
		$tiposClvs++;
		}
		
		if(isset($data["carta"]["mercancias"]["aereo"]["uso"])){
		$tiposClvs++;
		}
		
		if(isset($data["carta"]["mercancias"]["ferroviario"]["uso"])){
		$tiposClvs++;
		}
		
		
		
		$mercancias = $this->xml->createElement("cartaporte31:Mercancias");
		$mercancias = $cartaporte->appendChild($mercancias);
		$data_mercancia= [			
			"PesoBrutoTotal"	=>	isset($data['carta30']['mercancias']['PesoBrutoTotal'])?$data['carta30']['mercancias']['PesoBrutoTotal']:"",
			"UnidadPeso"		=> isset($data['carta30']['mercancias']['UnidadPeso'])?trim(explode("-",$data['carta30']['mercancias']['UnidadPeso'])[0]):"",
			"PesoNetoTotal"		=> isset($data['carta30']['mercancias']['PesoNetoTotal'])?$data['carta30']['mercancias']['PesoNetoTotal']:"",
			"NumTotalMercancias"		=> isset($data['carta30']['mercancias']['NumTotalMercancias'])?$data['carta30']['mercancias']['NumTotalMercancias']:"",
			"CargoPorTasacion"		=> isset($data['carta30']['mercancias']['CargoPorTasacion'])?$data['carta30']['mercancias']['CargoPorTasacion']:"",
			"LogisticaInversaRecoleccionDevolucion"		=> isset($data['carta30']['mercancias']['LogisticaInversaRecoleccionDevolucion'])?self::cfdiUtf8Decode($data['carta30']['mercancias']['LogisticaInversaRecoleccionDevolucion']):"",
			
			];
			
			
		$this->satxmlsv33_cargaAtt($mercancias,$data_mercancia);
		$m_ercancias = json_decode($data['carta30']['mercancias']['mercancia'], true) ?? []; 
		$quitarCTCT =false;
		if(count($m_ercancias)==1 ){
		$quitarCTCT =true;
		}
		//Pedro
		if ($m_ercancias != '') {
			foreach($m_ercancias as $m) {
				//debug($m['PesoEnKg']);
				if(isset(explode("-",$m['ClaveUnidad'])[1])){
				$unidad_nombre = self::cfdiUtf8Decode(trim(explode("-",$m['ClaveUnidad'])[1]));
				$unidad_nombre = substr($unidad_nombre, 0, 20);
				}else{
				$unidad_nombre = "";
				}
				$mercancia = $this->xml->createElement("cartaporte31:Mercancia"); 
				$mercancia = $mercancias->appendChild($mercancia);
				$data_mercancia = [
				"BienesTransp" => isset($m['BienesTransp'])?trim(explode("-",$m['BienesTransp'])[0]):'',
				"ClaveSTCC"     => isset($m['ClaveSTCC'])?trim(explode("-",$m['ClaveSTCC'])[0]):'',
				"Descripcion"     => isset($m['Descripcion'])?self::cfdiUtf8Decode($m['Descripcion']):'',
				"Cantidad"     => isset($m['Cantidad'])?$m['Cantidad']:'',
				"ClaveUnidad"     => isset($m['ClaveUnidad'])?trim(explode("-",$m['ClaveUnidad'])[0]):'',
				"Unidad"     => isset($m['Unidad'])?substr(self::cfdiUtf8Decode($m['Unidad']), 0, 20):'',
				"Dimensiones"     => isset($m['Dimensiones'])?$m['Dimensiones']:'',
				"MaterialPeligroso"     => isset($m['MaterialPeligroso'])?self::cfdiUtf8Decode($m['MaterialPeligroso']):'',
				"CveMaterialPeligroso"     => isset($m['CveMaterialPeligroso'])?trim(explode("-",$m['CveMaterialPeligroso'])[0]):'',
				"Embalaje"     => isset($m['Embalaje'])?trim(explode("-",$m['Embalaje'])[0]):'',
				"DescripEmbalaje"     => isset($m['DescripEmbalaje'])?$m['DescripEmbalaje']:'', 
				"SectorCOFEPRIS"     => isset($m['SectorCOFEPRIS'])?trim(explode("-",$m['SectorCOFEPRIS'])[0]):'',
				"NombreIngredienteActivo"  => isset($m['NombreIngredienteActivo'])?$m['NombreIngredienteActivo']:'',
				"NomQuimico"  => isset($m['NomQuimico'])?$m['NomQuimico']:'',
				"DenominacionGenericaProd"  => isset($m['DenominacionGenericaProd'])?$m['DenominacionGenericaProd']:'',
				"DenominacionDistintivaProd"  => isset($m['DenominacionDistintivaProd'])?$m['DenominacionDistintivaProd']:'',
				"Fabricante"  => isset($m['Fabricante'])?$m['Fabricante']:'',
				"FechaCaducidad"  => isset($m['FechaCaducidad'])?$m['FechaCaducidad']:'',
				"LoteMedicamento"  => isset($m['LoteMedicamento'])?$m['LoteMedicamento']:'',
				"FormaFarmaceutica"  => isset($m['FormaFarmaceutica'])?trim(explode("-",$m['FormaFarmaceutica'])[0]):'',
				"CondicionesEspTransp"  => isset($m['CondicionesEspTransp'])?trim(explode("-",$m['CondicionesEspTransp'])[0]):'',
				"RegistroSanitarioFolioAutorizacion"  => isset($m['RegistroSanitarioFolioAutorizacion'])?$m['RegistroSanitarioFolioAutorizacion']:'',
				"PermisoImportacion" => isset($m['PermisoImportacion'])?$m['PermisoImportacion']:'',
				"FolioImpoVUCEM"  => isset($m['FolioImpoVUCEM'])?$m['FolioImpoVUCEM']:'',
				"NumCAS" => isset($m['NumCAS'])?$m['NumCAS']:'',
				"RazonSocialEmpImp" => isset($m['RazonSocialEmpImp'])?$m['RazonSocialEmpImp']:'',
				"NumRegSanPlagCOFEPRIS" => isset($m['NumRegSanPlagCOFEPRIS'])?$m['NumRegSanPlagCOFEPRIS']:'',
				"DatosFabricante" => isset($m['DatosFabricante'])? $m['DatosFabricante']:'',
				"DatosFormulador" => isset($m['DatosFormulador'])?$m['DatosFormulador']:'',
				"DatosMaquilador" => isset($m['DatosMaquilador'])?$m['DatosMaquilador']:'',
				"UsoAutorizado" => isset($m['UsoAutorizado'])?$m['UsoAutorizado']:'',
				"PesoEnKg"  => isset($m['PesoEnKg'])?$m['PesoEnKg']:'',
				"ValorMercancia"  => isset($m['ValorMercancia'])?$m['ValorMercancia']:'',
				"Moneda"  => isset($m['Moneda'])?$m['Moneda']:'',
				"FraccionArancelaria"   => isset($m['FraccionArancelaria'])?explode("-",$m['FraccionArancelaria'])[0]:'',
				"UUIDComercioExt"  => isset($m['UUIDComercioExt'])?$m['UUIDComercioExt']:'',
				"TipoMateria"  =>isset($m['TipoMateria'])?explode("-",$m['TipoMateria'])[0] :'',
				"DescripcionMateria"  => isset($m['DescripcionMateria'])?$m['DescripcionMateria']:'',
				];	
				
				if($data_mercancia["MaterialPeligroso"] =="" && $data_mercancia["CveMaterialPeligroso"]=="" ){
					unset($data_mercancia["MaterialPeligroso"]);
					unset($data_mercancia["CveMaterialPeligroso"]);
				}
				
				/*if(($m['estadoMaterialPeligroso'] ?? '1') == "0"){
					unset($data_mercancia["MaterialPeligroso"]);
					unset($data_mercancia["CveMaterialPeligroso"]);
				}*/
				
				unset($data_mercancia["MaterialPeligroso"]); ///prueba

				$this->satxmlsv33_cargaAtt($mercancia,$data_mercancia);

				if(isset($m['documentosAduaneros'])){

					foreach($m['documentosAduaneros'] as $p){
						$pedimento = $this->xml->createElement("cartaporte31:DocumentacionAduanera");
						$pedimento = $mercancia->appendChild($pedimento);			
						$data_pedimento = [
							"TipoDocumento" =>  explode("-",$p["TipoDocumento"])[0],
							"NumPedimento" => $p["NumPedimento"],
							"IdentDocAduanero" => $p["IdentDocAduanero"],
							"RFCImpo" => $p["RFCImpo"],
						];	
						$this->satxmlsv33_cargaAtt($pedimento,$data_pedimento);
					}
				}
				
				if(isset($m['guiasIdentificacion'])){
				
				foreach($m['guiasIdentificacion'] as $g){
				$guia = $this->xml->createElement("cartaporte31:GuiasIdentificacion");
				$guia = $mercancia->appendChild($guia);			
				$data_guia = [
				"NumeroGuiaIdentificacion"=>$g["NumeroGuiaIdentificacion"],
				"DescripGuiaIdentificacion"=>$g["DescripGuiaIdentificacion"],
				"PesoGuiaIdentificacion"=>$g["PesoGuiaIdentificacion"],
				];	
				$this->satxmlsv33_cargaAtt($guia,$data_guia);
				}
				}
								
				if(!empty($m['CantidadTransporta'])){
				
				foreach($m['CantidadTransporta'] as $ct){
				$cantidad_transporta = $this->xml->createElement("cartaporte31:CantidadTransporta");
				$cantidad_transporta = $mercancia->appendChild($cantidad_transporta);
				$data_mercancia = [
					"Cantidad" => $ct['Cantidad'],
					"IDOrigen" => $ct['IDOrigen'],
					"IDDestino"     => $ct['IDDestino'],
					"CvesTransporte"     => $ct['CvesTransporte'],
				];	
				
				if($quitarCTCT == true || $tiposClvs <= 1){
				unset($data_mercancia['CvesTransporte']);
				}
				$this->satxmlsv33_cargaAtt($cantidad_transporta,$data_mercancia);
				}
				}
			
				if(isset($m['detalle']) && $m["detalle"]["PesoBruto"]!=""){
				$detalle_mercancia = $this->xml->createElement("cartaporte31:DetalleMercancia");
				$detalle_mercancia = $mercancia->appendChild($detalle_mercancia);
				
				$data_detalle_mercancia = [
					"UnidadPesoMerc" => trim(explode("-",$m["detalle"]["UnidadPesoMerc"])[0]),
					"PesoBruto" => isset($m["detalle"]["PesoBruto"])?$m["detalle"]["PesoBruto"]:'',
					"PesoNeto"     => isset($m["detalle"]["PesoNeto"])?$m["detalle"]["PesoNeto"]:'',
					"PesoTara"     => isset($m["detalle"]["PesoTara"])?$m["detalle"]["PesoTara"]:'',
					"NumPiezas"     => isset($m["detalle"]["NumPiezas"])?$m["detalle"]["NumPiezas"]:'',
					];					 
				$this->satxmlsv33_cargaAtt($detalle_mercancia,$data_detalle_mercancia);
				}
			}
		}

		if(isset($data["carta30"]["autotransporte"]["uso"]) && $data["carta30"]["autotransporte"]["uso"] == 1){
			$federal = $this->xml->createElement("cartaporte31:Autotransporte");
			$federal = $mercancias->appendChild($federal);
			
			
			$data_federal = [
			"PermSCT" => trim(explode("-",$data['carta30']['autotransporte']['PermSCT'])[0]),
			"NumPermisoSCT"     => $data['carta30']['autotransporte']['NumeroPermisoSCT'],
			
			];	
			$this->satxmlsv33_cargaAtt($federal,$data_federal);	
			
			$identificacion = $this->xml->createElement("cartaporte31:IdentificacionVehicular");
			$identificacion = $federal->appendChild($identificacion);			
			$data_identificacion = [
			"ConfigVehicular" => trim(explode("-",$data['carta30']['autotransporte']['IdentificacionVehicular']['ConfigVehicular'])[0]),
			"PesoBrutoVehicular"     => $data['carta30']['autotransporte']['IdentificacionVehicular']['PesoBrutoVehicular'],
			"PlacaVM"     => $data['carta30']['autotransporte']['IdentificacionVehicular']['PlacaVM'],
			"AnioModeloVM"     => $data['carta30']['autotransporte']['IdentificacionVehicular']['AnioModeloVM'],
			];	
			$this->satxmlsv33_cargaAtt($identificacion,$data_identificacion);
			
			
			
			$seguros = $this->xml->createElement("cartaporte31:Seguros");
			$seguros = $federal->appendChild($seguros);			
			$data_seguro = [
			"AseguraRespCivil"=>isset($data['carta30']['autotransporte']['seguro']['AseguraRespCivil'])?self::cfdiUtf8Decode($data['carta30']['autotransporte']['seguro']['AseguraRespCivil']):'',
			"PolizaRespCivil"=>isset($data['carta30']['autotransporte']['seguro']['PolizaRespCivil'])?$data['carta30']['autotransporte']['seguro']['PolizaRespCivil']:'',
			"AseguraMedAmbiente"=>isset($data['carta30']['autotransporte']['seguro']['AseguraMedAmbiente'])?self::cfdiUtf8Decode($data['carta30']['autotransporte']['seguro']['AseguraMedAmbiente']):'',
			"PolizaMedAmbiente"=>isset($data['carta30']['autotransporte']['seguro']['PolizaMedAmbiente'])?$data['carta30']['autotransporte']['seguro']['PolizaMedAmbiente']:'',
			"AseguraCarga"=>isset($data['carta30']['autotransporte']['seguro']['AseguraCarga'])?$data['carta30']['autotransporte']['seguro']['AseguraCarga']:'',
			"PolizaCarga"=>isset($data['carta30']['autotransporte']['seguro']['PolizaCarga'])?$data['carta30']['autotransporte']['seguro']['PolizaCarga']:'',
			"PrimaSeguro"=>isset($data['carta30']['autotransporte']['seguro']['PrimaSeguro'])?$data['carta30']['autotransporte']['seguro']['PrimaSeguro']:'',			
			];	
			$this->satxmlsv33_cargaAtt($seguros,$data_seguro);
			
			
			//if($data['carta30']['autotransporte']['Remolque'][0]['SubTipoRem'] != ""){
			
			if(!empty($data['carta30']['autotransporte']['Remolque']) && isset($data['carta30']['autotransporte']['Remolque'][0]['SubTipoRem']) && $data['carta30']['autotransporte']['Remolque'][0]['SubTipoRem'] != ''){
			
			$remolques = $this->xml->createElement("cartaporte31:Remolques");
			$remolques = $federal->appendChild($remolques);			
				if(isset($data['carta30']['autotransporte']['Remolque'][0]['SubTipoRem']) && $data['carta30']['autotransporte']['Remolque'][0]['SubTipoRem'] != ''){
						$remolque1 = $this->xml->createElement("cartaporte31:Remolque");
						$remolque1 = $remolques->appendChild($remolque1);			
						$data_remolque1 = [
						"SubTipoRem" => trim(explode("-",$data['carta30']['autotransporte']['Remolque'][0]['SubTipoRem'])[0]),
						"Placa" => $data['carta30']['autotransporte']['Remolque'][0]['Placa'],
						];	
						$this->satxmlsv33_cargaAtt($remolque1,$data_remolque1);	
				}
				
				if(isset($data['carta30']['autotransporte']['Remolque'][1]['SubTipoRem']) && $data['carta30']['autotransporte']['Remolque'][1]['SubTipoRem']!= ""){
						$remolque2 = $this->xml->createElement("cartaporte31:Remolque");
						$remolque2 = $remolques->appendChild($remolque2);			
						$data_remolque2 = [
						"SubTipoRem" => trim(explode("-",$data['carta30']['autotransporte']['Remolque'][1]['SubTipoRem'])[0]),
						"Placa" => $data['carta30']['autotransporte']['Remolque'][1]['Placa'],
						];	
						$this->satxmlsv33_cargaAtt($remolque2,$data_remolque2);	
				}
			}
			//}
			}
			
			if(isset($data["carta30"]["maritimo"]["uso"]) && $data["carta30"]["maritimo"]["uso"] == 1){
			$maritimo = $this->xml->createElement("cartaporte31:TransporteMaritimo");
			$maritimo = $mercancias->appendChild($maritimo);
			
			
			$data_maritimo = [
			"PermSCT"=> trim(explode("-",$data['carta30']['maritimo']["PermSCT"])[0]),
			"NumPermisoSCT"=>$data['carta30']['maritimo']["NumPermisoSCT"],
			"NombreAseg"=>self::cfdiUtf8Decode($data['carta30']['maritimo']["NombreAseg"]),
			"NumPolizaSeguro"=> $data['carta30']['maritimo']["NumPolizaSeguro"],
			"TipoEmbarcacion"=>trim(explode("-",$data['carta30']['maritimo']["TipoEmbarcacion"])[0]),
			"Matricula"=>$data['carta30']['maritimo']["Matricula"],
			"NumeroOMI"=>$data['carta30']['maritimo']["NumeroOMI"],
			"AnioEmbarcacion"=>$data['carta30']['maritimo']["AnioEmbarcacion"],
			"NombreEmbarc"=>$data['carta30']['maritimo']["NombreEmbarc"],
			"NacionalidadEmbarc"=>$data['carta30']['maritimo']["NacionalidadEmbarc"],
			"UnidadesDeArqBruto"=>$data['carta30']['maritimo']["UnidadesDeArqBruto"],
			"TipoCarga"=>trim(explode("-",$data['carta30']['maritimo']["TipoCarga"])[0]),
			//"NumCertITC"=>$data['carta30']['maritimo']["NumCertITC"],
			"Eslora"=>$data['carta30']['maritimo']["Eslora"],
			"Manga"=>$data['carta30']['maritimo']["Manga"], 
			"Calado"=>$data['carta30']['maritimo']["Calado"],
			"Puntal"=>$data['carta30']['maritimo']["Puntal"],
			"LineaNaviera"=>$data['carta30']['maritimo']["LineaNaviera"],
			"NombreAgenteNaviero"=>$data['carta30']['maritimo']["NombreAgenteNaviero"],
			"NumAutorizacionNaviero"=>$data['carta30']['maritimo']["NumAutorizacionNaviero"],
			"NumViaje"=>$data['carta30']['maritimo']["NumViaje"],
			"NumConocEmbarc"=>$data['carta30']['maritimo']["NumConocEmbarc"],
			"PermisoTempNavegacion"=>$data['carta30']['maritimo']["PermisoTempNavegacion"],
			];	
			$this->satxmlsv33_cargaAtt($maritimo,$data_maritimo);
			
			
			
			$m_contenedor = json_decode($data['carta30']['maritimo']['contenedores'], true) ?? [];
			if(count($m_contenedor)> 0){
			foreach($m_contenedor as $c) {
			$contenedor = $this->xml->createElement("cartaporte31:Contenedor");
			$contenedor = $maritimo->appendChild($contenedor);
		
			$data_contenedor = [
			"TipoContenedor"     => trim(explode("-",$c['TipoContenedor'])[0]),
			"MatriculaContenedor" => $c['MatriculaContenedor'],
			"NumPrecinto" => $c['NumPrecinto'],
			"IdCCPRelacionado" => $c['IdCCPRelacionado'],
			"PlacaVMCCP" => $c['PlacaVMCCP'],			
			"FechaCertificacionCCP"     => $c['FechaCertificacionCCP'],
			];	
			if($data_contenedor['TipoContenedor'] == 'CM011'){
				unset($data_contenedor['MatriculaContenedor']);
				unset($data_contenedor['NumPrecinto']);
			}
			
			$this->satxmlsv33_cargaAtt($contenedor,$data_contenedor);	
			
			}
			
			}
			if(isset($data['carta30']['maritimo']['remolque'][0]['SubTipoRemCCP']) && $data['carta30']['maritimo']['remolque'][0]['SubTipoRemCCP'] != ''){
				
			$remolques = $this->xml->createElement("cartaporte31:RemolquesCCP");
			$remolques = $maritimo->appendChild($remolques);			
						$remolque1 = $this->xml->createElement("cartaporte31:RemolqueCCP");
						$remolque1 = $remolques->appendChild($remolque1);			
						$data_remolque1 = [
						"SubTipoRemCCP" => trim(explode("-",$data['carta30']['maritimo']['remolque'][0]['SubTipoRemCCP'])[0]),
						"PlacaCCP" => $data['carta30']['maritimo']['remolque'][0]['PlacaCCP'],
						];	
						$this->satxmlsv33_cargaAtt($remolque1,$data_remolque1);	
				
			}
			
			}
			
			if(isset($data["carta30"]["aereo"]["uso"]) && $data["carta30"]["aereo"]["uso"] == 1){
			$aereo = $this->xml->createElement("cartaporte31:TransporteAereo");
			$aereo = $mercancias->appendChild($aereo);
			$data_aereo = [
			"PermSCT"=> isset($data['carta30']['aereo']["PermSCT"])?trim(explode("-",$data['carta30']['aereo']["PermSCT"])[0]):"",
			"NumPermisoSCT"=>isset($data['carta30']['aereo']["NumPermisoSCT"])?$data['carta30']['aereo']["NumPermisoSCT"]:"",
			"MatriculaAeronave"=>isset($data['carta30']['aereo']["MatriculaAeronave"])?$data['carta30']['aereo']["MatriculaAeronave"]:"",
			"NombreAseg"=>isset($data['carta30']['aereo']["NombreAseg"])?$data['carta30']['aereo']["NombreAseg"]:"",
			"NumPolizaSeguro"=> isset($data['carta30']['aereo']["NumPolizaSeguro"])?$data['carta30']['aereo']["NumPolizaSeguro"]:"",
			"NumeroGuia"=>isset($data['carta30']['aereo']["NumeroGuia"])?$data['carta30']['aereo']["NumeroGuia"]:"",
			"LugarContrato"=>isset($data['carta30']['aereo']["LugarContrato"])?$data['carta30']['aereo']["LugarContrato"]:"",
			"CodigoTransportista"=>isset($data['carta30']['aereo']["CodigoTransportista"])?trim(explode("-",$data['carta30']['aereo']["CodigoTransportista"])[0]):"",
			"RFCEmbarcador"=>isset($data['carta30']['aereo']["RFCEmbarcador"])?$data['carta30']['aereo']["RFCEmbarcador"]:"",
			"NumRegIdTribEmbarc"=>isset($data['carta30']['aereo']["NumRegIdTribEmbarc"])?$data['carta30']['aereo']["NumRegIdTribEmbarc"]:"",
			"ResidenciaFiscalEmbarc"=>isset($data['carta30']['aereo']["ResidenciaFiscalEmbarc"])?$data['carta30']['aereo']["ResidenciaFiscalEmbarc"]:"",
			"NombreEmbarcador"=>isset($data['carta30']['aereo']["NombreEmbarcador"])?$data['carta30']['aereo']["NombreEmbarcador"]:"",
			];	
			$this->satxmlsv33_cargaAtt($aereo,$data_aereo);
			 
			
			}
			
			if(isset($data["carta30"]["ferroviario"]["uso"]) && $data["carta30"]["ferroviario"]["uso"] ==1){
			$ferroveario = $this->xml->createElement("cartaporte31:TransporteFerroviario");
			$ferroveario = $mercancias->appendChild($ferroveario);
			$data_ferroveario = [
			"TipoDeServicio"=> trim(explode("-",$data["carta30"]["ferroviario"]["TipoDeServicio"])[0]),
			"TipoDeTrafico"=>trim(explode("-",$data["carta30"]["ferroviario"]["TipoDeTrafico"])[0]),
			"NombreAseg"=>self::cfdiUtf8Decode($data["carta30"]["ferroviario"]["NombreAseg"]),
			"NumPolizaSeguro"=>$data["carta30"]["ferroviario"]["NumPolizaSeguro"],
			
			];	
			$this->satxmlsv33_cargaAtt($ferroveario,$data_ferroveario);
			
			$d_paso = json_decode($data["carta30"]["ferroviario"]['DerechoDePaso']['Lista'], true) ?? []; 
			if(count($d_paso) >0){
			foreach($d_paso as $cp) {
			$paso = $this->xml->createElement("cartaporte31:DerechosDePaso");
			$paso = $ferroveario->appendChild($paso);
		
			$data_paso = [
			"TipoDerechoDePaso" => trim(explode("-",$cp['TipoDerechoDePaso'])[0]),
			"KilometrajePagado"     => $cp['KilometrajePagado'],
			];	
			
			$this->satxmlsv33_cargaAtt($paso,$data_paso);	
			
			}
			}
			
			$carro = $this->xml->createElement("cartaporte31:Carro");
			$carro = $ferroveario->appendChild($carro);
		
			$data_carro = [
			"TipoCarro" => trim(explode("-",$data["carta30"]["ferroviario"]["Carro"]["TipoCarro"])[0]),
			"MatriculaCarro"     => $data["carta30"]["ferroviario"]["Carro"]["MatriculaCarro"],
			"GuiaCarro"     => $data["carta30"]["ferroviario"]["Carro"]["GuiaCarro"],
			"ToneladasNetasCarro"     => $data["carta30"]["ferroviario"]["Carro"]["ToneladasNetasCarro"],
			];	
			
			$this->satxmlsv33_cargaAtt($carro,$data_carro);	
			
			$c_contenedor = json_decode($data["carta30"]["ferroviario"]["Carro"]["contenedores"], true) ?? []; 
			if(count($c_contenedor) > 0){
			foreach($c_contenedor as $cc) {
			$carro_contenedor = $this->xml->createElement("cartaporte31:Contenedor");
			$carro_contenedor = $carro->appendChild($carro_contenedor);
  		
			$data_contenedor_carro = [
			"TipoContenedor" => trim(explode("-",$cc['TipoContenedor'])[0]),
			"PesoContenedorVacio" => $cc['PesoContenedorVacio'],
			"PesoNetoMercancia" => $cc['PesoNetoMercancia'],
			];	
			
			$this->satxmlsv33_cargaAtt($carro_contenedor,$data_contenedor_carro);	
			
			}
			}
			
			}
			
			$array_fugura_transporte = json_decode($data["carta30"]["FiguraTransporte"], true) ?? [];
			
			
			
			if(count($array_fugura_transporte) > 0){
			
			$figura = $this->xml->createElement("cartaporte31:FiguraTransporte");
			$figura = $cartaporte->appendChild($figura);
			foreach($array_fugura_transporte as $figura_d){
			
			
			$tiposFiguraTransporte = $this->xml->createElement("cartaporte31:TiposFigura");
			$tiposFiguraTransporte = $figura->appendChild($tiposFiguraTransporte);
			
			$data_figura = [
			"TipoFigura"  =>explode("-",$figura_d["TipoFigura"])[0],
			"RFCFigura"=>$figura_d["RFCfigura"],
			"NumLicencia"=>isset($figura_d["NumLicencia"])?$figura_d["NumLicencia"]:'',
			"NombreFigura"=>self::cfdiUtf8Decode($figura_d["NombreFigura"]),
			"NumRegIdTribFigura"=>$figura_d["NumRegIdTribFigura"],
			"ResidenciaFiscalFigura"=>$figura_d["ResidenciaFiscalFigura"],
			];		
			
			if($data_figura["TipoFigura"]!= "01"){
			unset($data_figura["NumLicencia"]);	
			}
			
			if($data_figura["RFCFigura"] !=""){
				unset($data_figura['NumRegIdTrib']);
				unset($data_figura['ResidenciaFiscalFigura']);
			}
			$this->satxmlsv33_cargaAtt($tiposFiguraTransporte,$data_figura);
			
			//debug($figura_d['PartesTransporte']);
			//debug($figura_d["TipoFigura"]);
			if($figura_d["TipoFigura"] == "02-Propietario" && isset($figura_d['PartesTransporte']) || $figura_d["TipoFigura"] == "03-Arrendador" && isset($figura_d['PartesTransporte'])){
			//debug($figura_d['PartesTransporte']);
			foreach($figura_d['PartesTransporte'] as $pt){
			$partes = $this->xml->createElement("cartaporte31:PartesTransporte");
			$partes = $tiposFiguraTransporte->appendChild($partes);
			$data_parte = [
			"ParteTransporte"=> explode("-",$pt)[0]
			];			
			$this->satxmlsv33_cargaAtt($partes,$data_parte);
			}
			}
			
			//debug($figura_d);
			if(isset($figura_d["direccion"]) && $figura_d["direccion"] == 1){
			$domicilio = $this->xml->createElement("cartaporte31:Domicilio");
			$domicilio = $tiposFiguraTransporte->appendChild($domicilio);			
			$data_domicilio_figura = [
			"Calle" =>self::cfdiUtf8Decode($figura_d["figuraCalle"]),
			"NumeroExterior"  =>$figura_d["figuraNumeroExterior"],
			"NumeroInterior"=>$figura_d["figuraNumeroInterior"],
			"Colonia"=>$figura_d["figuraColonia"],
			"Localidad"=>$figura_d["figuraLocalidad"],
			"Referencia"=>$figura_d["figuraReferencia"],
			"Municipio"=>$figura_d["figuraMunicipio"],
			"Estado"=>$figura_d["figuraEstado"],
			"Pais"=>$figura_d["figuraPais"],
			"CodigoPostal"=>$figura_d["figuraCodigoPostal"],
			];				
			
			$this->satxmlsv33_cargaAtt($domicilio,$data_domicilio_figura);
			}
			}
			
			}
			
			
   }
  
  
  
}