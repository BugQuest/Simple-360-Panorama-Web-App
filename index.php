<!DOCTYPE html>
<html>
<head>
    <title>BugQuest 360</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <link rel="stylesheet" href="index.css">
    <link rel="manifest" href="manifest.json">
</head>
<body>
<div id="header">
    <h1>BugQuest 360</h1>
    <hr
    / width="50%">
    <p>Click sur un panorama pour le visualiser</p>
</div>
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
        'pc_only' => $pc_only
    ];
}

// Sort the panoramas by date
usort($panoramas, function ($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

?>
<div id="panoramas">
    <?php foreach ($panoramas as $panorama): ?>
        <div class="item" onclick="window.location.href='viewer.php?panorama=<?= $panorama['name']; ?>'">
            <div class="item-date">- <?= $panorama['date']; ?> -</div>
            <div class="item-name"><?= $panorama['name']; ?></div>
            <?php if ($panorama['pc_only']): ?>
                <div class="item-pc-only">Seulement PC</div>
            <?php endif; ?>
            <div class="item-size"><?= $panorama['size']; ?>  <?= $panorama['width'] . "x" . $panorama['height'] ?>px</div>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
