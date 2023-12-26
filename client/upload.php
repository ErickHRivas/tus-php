<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
include 'vendor/autoload.php';

use \TusPhp\Tus\Client;

class Tus {

  private $urlUploadFile = 'http://tusd.tusdemo.net/files/';
  private $minSizeUpload = 5000000; // bytes = 5MB
  private $uploadKey = '';
  private $headers = [
    'filename'=> 'LE2060136169920231100140400021112.zip',
    'filetype'=> 'zip',
    'numRuc'=> '20601361699',
    'perTributario'=> '202311',
    'codOrigenEnvio'=> '2',
    'codProceso'=> '3',
    'codTipoCorrelativo'=> '01',
    'codLibro'=> '140000',
    'nomArchivoImportacion'=> 'LE2060136169920231100140400021112.zip',
  ];

  public function init( $file ){

    // $client = new Client($this->urlUploadFile, ['headers' => $this->headers]);
    // $client->addMetadata('filename', 'LE2060136169920231100140400021112.zip');
    // $client->addMetadata( 'filetype', 'zip' );
    // $client->addMetadata( 'numRuc', '20601361699' );
    // $client->addMetadata( 'perTributario', '202311' );
    // $client->addMetadata( 'codOrigenEnvio', '2' );
    // $client->addMetadata( 'codProceso', '3' );
    // $client->addMetadata( 'codTipoCorrelativo', '01' );
    // $client->addMetadata( 'codLibro', '140000' );
    // $client->addMetadata( 'nomArchivoImportacion', 'LE2060136169920231100140400021112.zip' );
    // print_r($client->getMetadata());
    // exit;
    $size = $file['size'];
    $this->uploadKey = hash_file('md5', $file['tmp_name']);

    $verify = $this->verify($file);
    print( json_encode($verify) );
    echo '<br>';

    if( $verify['status'] != 'error' && $verify['bytes_uploaded'] < $size ) {
      $sizeUpload = 0;
      while( $sizeUpload < $size ) {
        $this->changeSizeUpload( $sizeUpload, $size );
        $infoUpload = $this->upload($file);
        print( json_encode($infoUpload) );
        echo '<br>';
        $sizeUpload = $infoUpload['bytes_uploaded'];
      }
    }

    // $client = new Client($this->urlUploadFile, ['headers' => $this->headers]);
    // $client->setKey($this->uploadKey)->file($file['tmp_name'], $file['name']);

    // print_r( $client->sendHeadRequestMine() );

  }

  public function changeSizeUpload( $sizeUpload, $size ){
    
    $sizeNeedUpload = $size - $sizeUpload;
    if( $sizeNeedUpload < $this->minSizeUpload ) {
      $this->minSizeUpload = $sizeNeedUpload + 1;
    }

  }

  public function upload( $file ) {

    $client = new Client($this->urlUploadFile, ['headers' => $this->headers]);

    try {
        $client->setKey($this->uploadKey)->file($file['tmp_name'], $file['name']);
    
        $bytesUploaded = $client->upload($this->minSizeUpload);
    
        return [
            'status' => 'uploading',
            'bytes_uploaded' => $bytesUploaded,
            'upload_key' => $this->uploadKey
        ];
    } catch (ConnectionException | FileException | TusException $e) {
        return [
            'status' => 'error',
            'bytes_uploaded' => -1,
            'upload_key' => '',
            'error' => $e->getMessage(),
        ];
    }
  }

  public function verify( $file ) {

    $client = new \TusPhp\Tus\Client($this->urlUploadFile, ['headers' => $this->headers]);

    $this->uploadKey = hash_file('md5', $file['tmp_name']);

    try {
        $offset = $client->setKey($this->uploadKey)->getOffset();
        $status = false !== $offset ? 'resume' : 'new';
        $offset = false === $offset ? 0 : $offset;

        return [
            'status' => $status,
            'bytes_uploaded' => $offset,
            'upload_key' => $this->uploadKey,
        ];
    } catch (ConnectException $e) {
        return [
            'status' => 'error',
            'bytes_uploaded' => -1,
        ];
    } catch (FileException $e) {
        return [
            'status' => 'resume',
            'bytes_uploaded' => 0,
            'upload_key' => '',
        ];
    }

  }

}

$tus = new Tus();
$response = $tus->init($_FILES['tus_file']);
print( json_encode($response) );

exit;
/*
// http://tusd.tusdemo.net/files/60be9ed734e9867b7b08426a66bbc00f/get

$client = new \TusPhp\Tus\Client('http://tusd.tusdemo.net/files/');
$fileMeta  = $_FILES['tus_file'];
echo $fileMeta['size'];
// echo filesize($fileMeta['tmp_name'].$fileMeta['name']);
exit;

$uploadKey = substr( hash_file('sha512', $fileMeta['tmp_name']).hash_file('sha256', $fileMeta['tmp_name']), 0, 161);

// $client->setKey($uploadKey)->file($fileMeta['tmp_name'],  time() . '_' . $fileMeta['name']);

//   // Upload 50MB starting from 10MB
//   $bytesUploaded = $client->seek(10000000)->upload(50000000);
//   echo 'entro';
// exit;
//   $partialKey1   = $client->getKey();
//   $checksum      = $client->getChecksum();

//   // Upload first 10MB
//   $bytesUploaded = $client->setFileName('chunk_b')->seek(0)->upload(10000000);
//   $partialKey2   = $client->getKey();

//   // Upload remaining bytes starting from 60,000,000 bytes (60MB => 50000000 + 10000000)
//   $bytesUploaded = $client->setFileName('chunk_c')->seek(60000000)->upload();
//   $partialKey3   = $client->getKey();

//   $client->setFileName($fileMeta['name'])->concat($uploadKey, $partialKey2, $partialKey1, $partialKey3);

//   echo 'enviado';
// exit;

// $offset = $client->setKey($uploadKey)->getOffset();
// $status = false !== $offset ? 'resume' : 'new';
// $offset = false === $offset ? 0 : $offset;

// echo json_encode([
//     'status' => $status,
//     'bytes_uploaded' => $offset,
//     'upload_key' => $uploadKey,
// ]);
// exit;
// $client->setKey($uploadKey)->file($fileMeta['tmp_name'], time() . '_' . $fileMeta['name']);

// $bytesUploaded = $client->seek(1000)->upload(10000);
// $chunkAkey     = $client->getKey();
// exit;

// concatenacion
    $client->setKey($uploadKey)->file($fileMeta['tmp_name'], time() . '_' . $fileMeta['name']);

    $bytesUploaded = $client->seek(0)->upload(10);
    $chunkAkey     = $client->getKey();

    $bytesUploaded = $client->setFileName('chunk_b.ext')->seek(0)->upload(1000);
    $chunkBkey     = $client->getKey();

    $client->setFileName($fileMeta['name'])->concat($uploadKey, $chunkBkey, $chunkAkey);
    exit;

// delete partial
// $client->setKey($uploadKey)->file($fileMeta['tmp_name'], time() . '_' . $fileMeta['name']);
// $client->delete($uploadKey);exit;

// ver cuantos partial se subieron
// $offset = $client->setKey($uploadKey)->file($_FILES['tus_file']['tmp_name'])->getOffset();

//   echo json_encode([
//     'bytes_uploaded' => $offset,
//     'upload_key' => $uploadKey,
// ]);
// exit;

// subir por fracciones
try {
    $client->setKey($uploadKey)->file($fileMeta['tmp_name'], time() . '_' . $fileMeta['name']);

    $bytesUploaded = $client->upload(5000); // Chunk of 5 mb

    echo json_encode([
        'status' => 'uploading',
        'bytes_uploaded' => $bytesUploaded,
        'upload_key' => $uploadKey
    ]);
} catch (ConnectionException | FileException | TusException $e) {
    echo json_encode([
        'status' => 'error',
        'bytes_uploaded' => -1,
        'upload_key' => '',
        'error' => $e->getMessage(),
    ]);
}


exit;











// $client->file($_FILES['tus_file']['tmp_name'], 'hola');
// $uploadKey = $client->getKey();
// echo $uploadKey;
// exit;
// $uploadKey = hash_file('md5', $_FILES['tus_file']['tmp_name']);

// verify
// $offset = $client->setKey($uploadKey)->file($_FILES['tus_file']['tmp_name'])->getOffset();

//   echo json_encode([
//     'bytes_uploaded' => $offset,
//     'upload_key' => $uploadKey,
// ]);
// exit;
// // upload 

// $client->setKey($uploadKey)
//     ->file($_FILES['tus_file']['tmp_name'], time().'_'. $_FILES['tus_file']['name']);

// // $client->setChunkSize(512000);

// $bytesUploaded = $client->upload(5000000);

// echo json_encode([
//   'status' => 'uploading',
//   'bytes_uploaded' => $bytesUploaded,
//   'upload_key' => $uploadKey
// ]);

*/