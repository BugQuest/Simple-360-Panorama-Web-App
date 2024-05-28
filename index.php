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
    <p style="color: #fd6100">Version HD et Max seulement pour pc</p>
</div>
<?php include_once 'includes/get360Views.php'; ?>

<div id="app">
    <div id="panoramas">
        <div class="item" v-for="panorama in panoramas">
            <div class="item-date">- {{ panorama.date }}-</div>
            <div class="item-name">{{ panorama.name }}</div>
            <div class="item-previews">
                <div @click="panorama.selected = 'mobile'"
                     class="item-preview"
                     :class="{'active': panorama.selected == 'mobile'}">
                    Mobile
                </div @click="panorama">
                <div v-if="Boolean(panorama.hd)"
                     @click="panorama.selected = 'hd'"
                     class="item-preview-hd"
                     :class="{'active': panorama.selected == 'hd'}">
                    HD
                </div>
                <div v-if="Boolean(panorama.max)"
                     @click="panorama.selected = 'max'"
                     class="item-preview-max"
                     :class="{'active': panorama.selected == 'max'}">
                    Max
                </div>
            </div>
            <div v-if="panorama.selected == 'mobile'" class="item-size">{{ panorama.size }} {{ panorama.width }}x{{ panorama.height }}px</div>
            <div v-if="panorama.selected == 'hd'" class="item-size">{{ panorama.hd.size }} {{ panorama.hd.width }}x{{ panorama.hd.height }}px</div>
            <div v-if="panorama.selected == 'max'" class="item-size">{{ panorama.max.size }} {{ panorama.max.width }}x{{ panorama.max.height }}px</div>

            <div class="item-go">
                <div @click="goToViewer(panorama)">Visualiser</div>
            </div>
        </div>
    </div>
</div>
</body>
<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
<script>
    const {createApp, ref} = Vue

    createApp({
        data: function () {
            return {
                panoramas: <?= json_encode($panoramas) ?>
            }
        },
        methods: {
            goToViewer: function (panorama) {
                switch(panorama.selected) {
                    case 'mobile':
                        window.location.href = 'viewer.php?panorama=' + panorama.name;
                        break;
                    case 'hd':
                        window.location.href = 'viewer.php?panorama=' + panorama.hd.name;
                        break;
                    case 'max':
                        window.location.href = 'viewer.php?panorama=' + panorama.max.name;
                        break;
                }
            }
        }
    }).mount('#app')
</script>
</html>
