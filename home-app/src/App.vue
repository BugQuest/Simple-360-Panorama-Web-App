<template>
  <div id="app-container">
    <div id="mobile-menu-btn" @click="mobile_menu = !mobile_menu">
      Menu
    </div>
    <div id="panoramas" class="flex flex-col" :class="{active:mobile_menu}">
      <div class="item">
        <div id="mobile-menu-close" @click="mobile_menu = !mobile_menu">
          Close
        </div>
        <div class="btn" @click="autoFit()">Fit</div>
      </div>
      <div class="item"
           :class="{focused: panorama.name === focused}"
           v-for="panorama in panoramas">
        <div class="item-date">- {{ panorama.date }}-</div>
        <div class="item-name" @click="flyTo(panorama.name)" title="Voir sur la carte">{{ panorama.name }}</div>
        <div class="item-previews">
          <div @click="panorama.selected = 'mobile'"
               class="item-preview"
               :class="{'active': panorama.selected == 'mobile'}">
            Mobile
          </div>
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
    <div id="map-container">
      <l-map ref="map"
             :options="{zoomControl: false}"
             :zoom="current_zoom"
             @update:zoom="current_zoom = $event"
             :max-zoom="20"
             :min-zoom="4"
             :use-global-leaflet="false">
        <l-tile-layer
            url='https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png'
            :minZoom="4"
            :maxZoom="20"
            attribution='&copy; OpenStreetMap France | &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            layer-type="base"
            name="OpenStreetMap"></l-tile-layer>
        <l-marker v-for="(marker, index) in markers"
                  :lat-lng="marker.latlng"
                  @click="focused = marker.name">
          <l-icon
              :icon-anchor="[20, 20]"
              :icon-size="[40,40]"
              :icon-url="null">
            <div
                class="map-marker"
                :class="{'here-marker':focused === marker.name}">
              <div class="map-marker-dot" :style="{background: marker.color}"></div>
            </div>
          </l-icon>
          <l-popup :content="marker.name"></l-popup>
        </l-marker>
      </l-map>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import {LMap, LTileLayer, LMarker, LIcon, LPopup} from "@vue-leaflet/vue-leaflet";
import L from 'leaflet'

export default {
  components: {
    LMap,
    LTileLayer,
    LMarker,
    LIcon,
    LPopup
  },
  data() {
    return {
      panoramas: [],
      current_zoom: 12,
      focused: null,
      mobile_menu: false
    }
  },
  computed: {
    markers: function () {
      return this.panoramas.map(panorama => {
        return {
          latlng: [panorama.latitude, panorama.longitude],
          color: '#0aa9c3',
          name: panorama.name
        }
      })
    }
  },
  mounted() {
    setTimeout(() => {
      this.getPanoramas()
    }, 200)
  },
  methods: {
    getPanoramas() {
      axios
          .get('/get360List.php')
          .then(response => {
            this.panoramas = response.data
            setTimeout(() => {
              this.autoFit()
            }, 200)
          })
    },
    goToViewer: function (panorama) {
      switch (panorama.selected) {
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
    },
    flyTo(name) {
      let marker = this.panoramas.find(panorama => panorama.name === name)
      this.focused = marker.name
      this.$refs.map.leafletObject.flyTo([marker.latitude, marker.longitude], 15)
    },
    autoFit() {
      this.$refs.map.leafletObject.invalidateSize()

      let bounds = []
      this.panoramas.forEach(panorama => {
        bounds.push([panorama.latitude, panorama.longitude])
      })

      this.$refs.map.leafletObject.fitBounds(bounds)
    },
  }
}
</script>

