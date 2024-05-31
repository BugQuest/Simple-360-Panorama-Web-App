<?php
// Fonction pour convertir une fraction sous forme "numérateur/dénominateur" en décimal
function fractionToDecimal($fraction)
{
    list($numerator, $denominator) = explode('/', $fraction);
    return $numerator / $denominator;
}

// Conversion de la latitude et de la longitude en degrés décimaux
function convertDMSToDD($dmsArray)
{
    $degrees = fractionToDecimal($dmsArray[0]);
    $minutes = fractionToDecimal($dmsArray[1]);
    $seconds = fractionToDecimal($dmsArray[2]);
    return $degrees + ($minutes / 60) + ($seconds / 3600);
}

$files = glob('images/*.jpg');

$panoramas = [];
// Remove the 'images/' prefix from the panorama filenames and extension
foreach ($files as $path) {
    $name = str_replace('images/', '', $path);
    $name = str_replace('.jpg', '', $name);

    $exif = exif_read_data($path, 0, true);
    $size = $exif['FILE']['FileSize'];
    if ($size > 1024) {
        $size = $size / 1024;
        if ($size > 1024) {
            $size = $size / 1024;
            $size = round($size, 2) . 'Mo';
        } else
            $size = round($size, 2) . 'Ko';
    }

    $date = $exif['EXIF']['DateTimeOriginal'];

    //format date from YYYY:MM:DD HH:MM:SS to DD/MM/YYYY HH:MM:SS
    $date = substr($date, 8, 2) . '/' . substr($date, 5, 2) . '/' . substr($date, 0, 4) . substr($date, 10);

    $width = $exif['EXIF']['ExifImageWidth'];
    $height = $exif['EXIF']['ExifImageLength'];

    $pc_only = $width > 4096 || $height > 2048;

    $latitude = convertDMSToDD($exif['GPS']['GPSLatitude']);
    $longitude = convertDMSToDD($exif['GPS']['GPSLongitude']);
    $altitude = fractionToDecimal($exif['GPS']['GPSAltitude']);

    $latitude_ref = $exif['GPS']['GPSLatitudeRef'];

    if ($latitude_ref == 'W')
        $longitude = -$longitude;

    $panoramas[] = [
        'name' => $name,
        'path' => $path,
        'size' => $size,
        'date' => $date,
        'width' => $width,
        'height' => $height,
        'pc_only' => $pc_only,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'altitude' => $altitude,
        'hd' => false,
        'max' => false,
        'selected' => 'mobile'
    ];
}

//some files have same name with _hd or _max suffix, we need to merge them and unset the original
foreach ($panoramas as $key => $panorama) {
    $name = $panorama['name'];
    foreach ($panoramas as $key2 => $panorama2) {
        if ($key != $key2 && $panorama2['name'] == $name . '_hd') {
            $panoramas[$key]['hd'] = $panorama2;
            unset($panoramas[$key2]);
        }
        if ($key != $key2 && $panorama2['name'] == $name . '_max') {
            $panoramas[$key]['max'] = $panorama2;
            unset($panoramas[$key2]);
        }
    }
}


// Sort the panoramas by date
usort($panoramas, function ($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

//set header to json
header('Content-Type: application/json');
echo json_encode($panoramas);