<?php
$panorama = filter_input(INPUT_GET, 'panorama', FILTER_SANITIZE_STRING);
$path = 'images/' . $panorama . '.jpg';
if (!file_exists($path))
    header('Location: index.php');

$exif = exif_read_data($path, 0, true);

$isMobile = preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
if($isMobile) {
    $width = $exif['EXIF']['ExifImageWidth'];
    $height = $exif['EXIF']['ExifImageLength'];

    //if width is greater than 4096 or height is greater than 2048, mobile can't handle it, redirect to index
    if($width > 4096 || $height > 2048)
        header('Location: index.php');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>BugQuest 360 - <?= $panorama ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <link rel="stylesheet" href="app.css">
</head>
<body>
<div class="loading">
    <div id="progress"></div>
    <svg width="250" height="250" viewBox="0 0 250 250" id="circular-progress">
        <circle class="bg"></circle>
        <circle class="fg"></circle>
    </svg>
</div>
<div class="btn-back" onclick="window.location.href='/'">Retour</div>
<div class="debug-button" id="__debug_btn"></div>
<div class="debug" id="__debug">
    <div id="__debug_input"></div>
    <div id="__debug_exif"></div>
</div>
<div id="app">
</div>
<!-- <script src="three.min.js"></script> -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r79/three.min.js"></script>
<script src="app.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function (event) {
        new App('<?= $path ?>', <?= json_encode($exif) ?>);
    })
</script>

</body>
</html>