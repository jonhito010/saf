<?php 
namespace App\Utility;
	
class Complementos{
		public function setComplemento($data){
			$complementos = [];
			
			if(isset($data['complemento']['PF']) && $data['complemento']['PF'] == 1){
				$complementos['PF'] = true;
				}else{ 
				$complementos['PF'] = false;
			}
			
			if(isset($data['complemento']['INE']) && $data['complemento']['INE'] == 1){
				$complementos['INE'] = true;
				}else{ 
				$complementos['INE'] = false;
			}
			
			if(isset($data['complemento']['DONATARIAS']) && $data['complemento']['DONATARIAS'] == 1){
				$complementos['DONATARIAS'] = true;
				}else{ 
				$complementos['DONATARIAS'] = false;
			}
			
			if(isset($data['complemento']['existeIEDU']) && $data['complemento']['existeIEDU'] >0){
				$complementos['IEDU'] = true;
				}else{ 
				$complementos['IEDU'] = false;
			}
			
			if(isset($data['complemento']['CONSTRUCCION']) && $data['complemento']['CONSTRUCCION'] >0){
				$complementos['CONSTRUCCION'] = true;
				}else{ 
				$complementos['CONSTRUCCION'] = false;
			}
			
			if(isset($data['complemento']['LEYENDAS']) && $data['complemento']['LEYENDAS'] >0){
				$complementos['LEYENDAS'] = true;
				}else{ 
				$complementos['LEYENDAS'] = false;
			}
			
			if(isset($data['complemento']['NOTARIOS']) && $data['complemento']['NOTARIOS'] >0){
				$complementos['NOTARIOS'] = true;
				}else{ 
				$complementos['NOTARIOS'] = false;
			}
			if(isset($data['complemento']['RELACIONADOS']) && $data['complemento']['RELACIONADOS'] >0){
				$complementos['RELACIONADOS'] = true;
				}else{ 
				$complementos['RELACIONADOS'] = false;
			}
			
			if(isset($data['complemento']['DETALLISTA']) && $data['complemento']['DETALLISTA'] >0){
				$complementos['DETALLISTA'] = true;
				}else{ 
				$complementos['DETALLISTA'] = false;
			}
			
			if(isset($data['complemento']['VEHICULO']) && $data['complemento']['VEHICULO'] >0){
				$complementos['VEHICULO'] = true;
				}else{ 
				$complementos['VEHICULO'] = false;
			}
			
			if(isset($data['complemento']['COMERCIO']) && $data['complemento']['COMERCIO'] >0){
				$complementos['COMERCIO'] = true;
				}else{ 
				$complementos['COMERCIO'] = false;
			}
			
			if(isset($data['complemento']['AEROLINEAS']) && $data['complemento']['AEROLINEAS'] >0){
				$complementos['AEROLINEAS'] = true;
				}else{ 
				$complementos['AEROLINEAS'] = false;
			}
			
			if(isset($data['complemento']['existeVEHICULO']) && $data['complemento']['existeVEHICULO'] >0){
				$complementos['VEHICULOVENTA'] = true;
				}else{ 
				$complementos['VEHICULOVENTA'] = false;
			}
			
			if(isset($data['complemento']['TERCEROS']) && $data['complemento']['TERCEROS'] >0){
				$complementos['TERCEROS'] = true;
				}else{ 
				$complementos['TERCEROS'] = false;
			}
			
			if(isset($data['complemento']['DIVISAS']) && $data['complemento']['DIVISAS'] >0){
				$complementos['DIVISAS'] = true;
				}else{ 
				$complementos['DIVISAS'] = false;
			}
			
			if(isset($data['complemento']['CartaPorte']) && $data['complemento']['CartaPorte'] >0){
				$complementos['CARTAPORTE'] = true;
				}else{ 
				$complementos['CARTAPORTE'] = false;
			}
			
			if(isset($data['complemento']['CartaPorte30']) && $data['complemento']['CartaPorte30'] >0){
				$complementos['CARTAPORTE30'] = true;
				}else{ 
				$complementos['CARTAPORTE30'] = false;
			}
			
			if(isset($data['complemento']['CartaPorte31']) && $data['complemento']['CartaPorte31'] >0){
				$complementos['CARTAPORTE31'] = true;
				}else{ 
				$complementos['CARTAPORTE31'] = false;
			}
			
			
			
			
			
			if(isset($data['json']['locales']) && $data['json']['locales'] != ''){
				$imps_local = json_decode($data['json']['locales'],true); 
				
				
				
				if(count($imps_local['Traslados']) || count($imps_local['Retenidos'])){
					$complementos['LOCLES'] = true;
					}else{
					$complementos['LOCLES'] = false;
				}
			}else{
			
					$complementos['LOCLES'] = false;
			}
			
			
			if(isset($data['Nomina']['tipoDeComprobante']) && $data['Nomina']['tipoDeComprobante']=="N"){
			  $complementos['NOMINA'] = true;
			
			}else{
			  $complementos['NOMINA'] = false;
			
			}
			
			return $complementos;
		}	
	}	