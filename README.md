# eTrak Barcode Builder

Easily build eTrak barcodes.


## Usage

```php
include_once('vendor/autoload.php');
$b = new \parcelmonkeygroup\eTrakBarcode\eTrakBarcode('ETYCL222200929');
echo '<img src="'.$b.'" />';
```