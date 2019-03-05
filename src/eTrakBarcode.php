<?PHP

namespace parcelmonkeygroup\eTrakBarcode;

class eTrakBarcode {
  
  var $barcode;
  var $origin;
  var $destination;
  var $service;
  var $curl_timeout = 5;
  
  var $qr_character_encoding = 'UTF-8';
  var $qr_error_correction_level = 'H'; // L [7% data loss], M [15% data loss], Q [25% data loss], H [30% data loss]
  var $qr_margin = 0;
  var $qr_width = 200;
  
  var $overlay_logo = true;
  var $overlay_ref = true;
  
  function __construct($barcode) {
    $this->barcode = $barcode;
  }
  
  public function setWidth($xy) {
    if($xy < 200) {
      throw new Exception('Minimum width for generation is 200px');
    }
    $this->qr_width = $xy;
  }
  
  public function generateBody() {
    
    $content[]=$this->barcode;
    
    return implode("|", $content);
    
  }
  
  public function overlayLogo($bool) {
    $this->overlay_logo = $bool ? true : false;
  }
  
  public function overlayRef($bool) {
    $this->overlay_ref = $bool ? true : false;
  }
  
  public function getDataUri() {
    
	  $providers = ['google','qrserver'];
		
		foreach($providers as $provider) {
			
	    $data = $this->curl_get_data($this->getQrCodeUrl($provider));
			if($data['responseCode'] == 200) continue;
			
			// otherwise iterate to next provider
			
		}
		
    $out = $data['data'];
    
    if($this->overlay_logo) $out = $this->gdOverlayLogo($out);
    if($this->overlay_ref) $out = $this->gdOverlayTextRef($out);
    
    return 'data:'.$data['contentType'].';base64,'.base64_encode($out);
    
  }
  
  private function getQrCodeUrl($provider='google') {
    
		$method = 'getQrCodeUrl_'.$provider;
		return $this->$method();
    
  }
	
	private function getQrCodeUrl_google() {

    $query['chs'] = $this->qr_width.'x'.$this->qr_width;
    $query['cht'] = 'qr';
    $query['choe'] = $this->qr_character_encoding;
    $query['chld'] = $this->qr_error_correction_level.'|'.$this->qr_margin;
    $query['chl'] = $this->generateBody();
    
    $body = $this->generateBody();
    $url = 'https://chart.googleapis.com/chart?'.http_build_query($query);
    return $url;
		
	}
	
	private function getQrCodeUrl_qrserver() {

    $query['size'] = $this->qr_width.'x'.$this->qr_width;
    $query['charset-source'] = $this->qr_character_encoding;
    $query['charset-target'] = $this->qr_character_encoding;
    $query['ecc'] = $this->qr_error_correction_level;
		$query['margin'] = $this->qr_margin;
    $query['data'] = $this->generateBody();
    
    $body = $this->generateBody();
    $url = 'https://api.qrserver.com/v1/create-qr-code/?'.http_build_query($query);
		
		return $url;
		
	}
  
  private function gdOverlayLogo($imagedata) {

    $qr = imagecreatefromstring($imagedata);
    list($width, $height) = getimagesizefromstring($imagedata);
      
    $logodata = 'iVBORw0KGgoAAAANSUhEUgAAACoAAAAqCAMAAADyHTlpAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAAZQTFRFAwMD////dlg4TQAAACpJREFUeNpiYCQaMIwqHapKGTDAIFdKgrcGXOlouI7ocB0tXYaPUoAAAwDOcgXlGpQ4gQAAAABJRU5ErkJggg==';
    $logo = imagecreatefromstring(base64_decode($logodata));
    unset($logodata);

    $logo_xy = $width * (42/200);
    $logo_xy = floor($logo_xy);
    
    $logo_offset = ($width / 2) - ($logo_xy / 2);
    $logo_offset = floor($logo_offset);
    
    $out = imagecreatetruecolor($width, $height);
    imagecopyresampled($out, $qr, 0, 0, 0, 0, $width, $height, $width, $height);
    imagecopyresampled($out, $logo, $logo_offset, $logo_offset, 0, 0, $logo_xy, $logo_xy, 42, 42);
    ob_start();
    imagepng($out);
    $stringdata = ob_get_contents(); // read from buffer
    ob_end_clean(); // delete buffer
    $zdata = gzdeflate($stringdata);
    
    return $stringdata;    
    
  }
  
  private function curl_get_data($url) {
    
  	$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);                                                               
  	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->curl_timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);         
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);           
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0); 
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->curl_timeout); //timeout in seconds
		
  	$data = curl_exec($ch);
  	$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
  	$responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

  	return [ 'data'=>$data,'contentType'=>$contentType,'responseCode'=>$responseCode ];

  }
  
  private function gdOverlayTextRef($imagedata) {
    
    $original = imagecreatefromstring($imagedata);
    list($width, $height) = getimagesizefromstring($imagedata);
        
    
    $out = imagecreatetruecolor($width, $height * 1.15);
    $white = imagecolorallocate($out, 255, 255, 255);
    imagefill($out, 0, 0, $white);
    
    imagecopy($out, $original, 0, 0, 0, 0, $width, $height);
    
    $color = imagecolorallocate($out, 0, 0, 0);
    
    $fontsize = 18;
    
    imagettftext($out, $fontsize, 0, 0, $height + $fontsize + 10, $color, __DIR__.'/fonts/RobotoMono-Bold.ttf', $this->barcode);
    
    ob_start();
    imagepng($out);
    $stringdata = ob_get_contents(); // read from buffer
    ob_end_clean(); // delete buffer
    $zdata = gzdeflate($stringdata);
    
    return $stringdata;
        
  }
  
  function __toString() {
    return $this->getDataUri();
  }
  
}

?>