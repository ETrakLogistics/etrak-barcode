<?PHP

include_once('src/eTrakBarcode.php');

$b = new \parcelmonkeygroup\eTrakBarcode\eTrakBarcode('ETYCL222200929');
//$b->overlayLogo(false);
//$b->overlayRef(false);
$b->setWidth(200);

echo '<img src="'.$b.'" />';


?>