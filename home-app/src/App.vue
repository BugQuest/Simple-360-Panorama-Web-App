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
      <div class="flex items-center justify-center mt-8 p-4 border">
        <input class="block text-lg w-40 text-center text-sm text-gray-900 border border-gray-300 cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" type="text" placeholder="Rechercher" v-model="search">
      </div>
      <div class="item"
           :class="{focused: panorama.name === focused}"
           v-for="panorama in sortedPanoramas">
        <div class="item-date">- {{ panorama.date }} -</div>
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
                  @click="focused = marker.panorama.name">
          <l-icon
              :icon-anchor="[20, 20]"
              :icon-size="[40,40]"
              :icon-url="null">
            <div
                class="map-marker"
                :class="{'here-marker':focused === marker.panorama.name}">
              <div class="map-marker-dot" :style="{background: marker.color}"></div>
            </div>
          </l-icon>
          <l-popup>
            <div>
              {{ marker.panorama.name }}
              <div class="item-previews">
                <div @click="marker.panorama.selected = 'mobile'"
                     class="item-preview"
                     :class="{'active': marker.panorama.selected == 'mobile'}">
                  Mobile
                </div>
                <div v-if="Boolean(marker.panorama.hd)"
                     @click="marker.panorama.selected = 'hd'"
                     class="item-preview-hd"
                     :class="{'active': marker.panorama.selected == 'hd'}">
                  HD
                </div>
                <div v-if="Boolean(marker.panorama.max)"
                     @click="marker.panorama.selected = 'max'"
                     class="item-preview-max"
                     :class="{'active': marker.panorama.selected == 'max'}">
                  Max
                </div>
              </div>
              <div v-if="marker.panorama.selected == 'mobile'" class="item-size">{{ marker.panorama.size }} {{ marker.panorama.width }}x{{ marker.panorama.height }}px</div>
              <div v-if="marker.panorama.selected == 'hd'" class="item-size">{{ marker.panorama.hd.size }} {{ marker.panorama.hd.width }}x{{ marker.panorama.hd.height }}px</div>
              <div v-if="marker.panorama.selected == 'max'" class="item-size">{{ marker.panorama.max.size }} {{ marker.panorama.max.width }}x{{ marker.panorama.max.height }}px</div>

              <div class="item-go">
                <div @click="goToViewer(marker.panorama)">Visualiser</div>
              </div>
            </div>
          </l-popup>
        </l-marker>
      </l-map>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import {LMap, LTileLayer, LMarker, LIcon, LPopup} from "@vue-leaflet/vue-leaflet";
import moment from 'moment'

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
      search: '',
      panoramas: [],
      current_zoom: 12,
      focused: null,
      mobile_menu: false
    }
  },
  computed: {
    sortedPanoramas: function () {
      //order by date with moment
      let result = this.panoramas.sort((a, b) => {
        return moment(b.date, 'DD/MM/YYYY HH:mm:ss').diff(moment(a.date, 'DD/MM/YYYY HH:mm:ss'))
      })

      //search
      if (this.search) {
        result = result.filter(panorama => {
          return panorama.name.toLowerCase().includes(this.search.toLowerCase())
        })
      }

      return result
    },
    markers: function () {
      return this.panoramas.map(panorama => {
        return {
          latlng: [panorama.latitude, panorama.longitude],
          color: '#0aa9c3',
          panorama: panorama
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
          .get('get360List.php')
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
      this.mobile_menu = false
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

