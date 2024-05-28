<?php
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

    $panoramas[] = [
        'name' => $name,
        'path' => $path,
        'size' => $size,
        'date' => $date,
        'width' => $width,
        'height' => $height,
        'pc_only' => $pc_only,
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