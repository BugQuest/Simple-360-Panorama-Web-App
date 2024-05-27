class App {
    constructor(texture_url, exif) {

        this.texture_url = texture_url
        this.exif = exif

        this.camera = null
        this.scene = null
        this.renderer = null
        this.fov = 70

        this.isUserInteracting = false
        this.onMouseDownMouseX = 0
        this.onMouseDownMouseY = 0
        this.lon = 0
        this.onMouseDownLon = 0
        this.lat = 0
        this.onMouseDownLat = 0
        this.phi = 0
        this.theta = 0

        this.textureLoader = new THREE.TextureLoader()
        this.texture = null
        this.progressElement = document.getElementById('progress')
        this.progressBarElement = document.getElementById('circular-progress')

        this.container = document.getElementById('app')
        this.mesh = null

        this.is_debug = false
        this.debugBtnElement = document.getElementById('__debug_btn')
        this.debugElement = document.getElementById('__debug')
        this.debugInputElement = document.getElementById('__debug_input')
        this.debugExifElement = document.getElementById('__debug_exif')

        this.populateExif()
        this.load()
    }

    load() {
        this.textureLoader.load(this.texture_url,
            (texture) => {

                texture.mapping = THREE.EquirectangularReflectionMapping
                texture.colorSpace = THREE.SRGBColorSpace
                texture.minFilter = THREE.LinearFilter
                this.texture = texture
                this.initialize()
            },
            (xhr) => {
                this.progressElement.innerHTML = (xhr.loaded / xhr.total * 100).toFixed(0) + '%'
                this.progressBarElement.style.setProperty('--progress', (xhr.loaded / xhr.total * 100).toString())
            },
            (xhr) => {
                this.progressElement.progress.innerHTML = 'An error happened'
            })
    }

    initialize() {
        this.camera = new THREE.PerspectiveCamera(this.fov, window.innerWidth / window.innerHeight, 1, 1000)
        this.camera.target = new THREE.Vector3(0, 0, 0)

        this.scene = new THREE.Scene()

        // Add panorama 360 sphere mesh
        let geometry = new THREE.SphereGeometry(500, 60, 40)
        geometry.scale(-1, 1, 1)

        let material = new THREE.MeshBasicMaterial({
            map: this.texture
        })

        this.mesh = new THREE.Mesh(geometry, material)

        this.scene.add(this.mesh)

        this.renderer = new THREE.WebGLRenderer({antialias: true})
        this.renderer.setPixelRatio(window.devicePixelRatio)
        this.renderer.setSize(window.innerWidth, window.innerHeight)
        this.container.appendChild(this.renderer.domElement)

        this.container.addEventListener('mousedown', (event) => this.onDocumentMouseDown(event), false)
        this.container.addEventListener('mousemove', (event) => this.onDocumentMouseMove(event), false)
        this.container.addEventListener('mouseup', (event) => this.onDocumentMouseUp(event), false)
        this.container.addEventListener('mouseleave', (event) => this.onDocumentMouseUp(event), false)
        this.container.addEventListener('wheel', (event) => this.onDocumentMouseWheel(event), false)
        this.container.addEventListener('touchstart', (event) => this.onDocumentTouchStart(event), false)
        this.container.addEventListener('touchmove', (event) => this.onDocumentTouchMove(event), false)
        this.container.addEventListener('touchend', (event) => this.onDocumentTouchEnd(event), false)

        this.debugBtnElement.addEventListener('click', (event) => this.onDebugBtnClick(event), false)

        window.addEventListener('resize', this.onWindowResized, false)
        screen.orientation.addEventListener('change', this.onWindowResized, false)
        this.onWindowResized(null)

        //add class active
        this.debugBtnElement.classList.add('active')

        this.animate()
    }

    animate() {
        requestAnimationFrame(() => this.animate())
        this.update()
    }

    update() {
        this.lat = Math.max(-85, Math.min(85, this.lat))
        this.phi = THREE.Math.degToRad(90 - this.lat)
        this.theta = THREE.Math.degToRad(this.lon)

        this.camera.target.x = 500 * Math.sin(this.phi) * Math.cos(this.theta)
        this.camera.target.y = 500 * Math.cos(this.phi)
        this.camera.target.z = 500 * Math.sin(this.phi) * Math.sin(this.theta)

        if (this.is_debug)
            this.debugInputElement.innerHTML = `
            <ul>
                <li>x: ${this.camera.target.x}</li>
                <li>y: ${this.camera.target.y}</li>
                <li>z: ${this.camera.target.z}</li>
                <li>fov: ${this.fov}</li>
            </ul>`

        this.camera.lookAt(this.camera.target)
        this.renderer.render(this.scene, this.camera)
    }

    fractionToDecimal(fraction) {
        const [numerator, denominator] = fraction.split('/').map(Number);
        return numerator / denominator;
    }

    convertDMSToDD(dmsArray) {
        const degrees = this.fractionToDecimal(dmsArray[0]);
        const minutes = this.fractionToDecimal(dmsArray[1]);
        const seconds = this.fractionToDecimal(dmsArray[2]);
        return degrees + (minutes / 60) + (seconds / 3600);
    }

    populateExif() {
        let exifData = '<ul>'

        if (typeof this.exif.FILE.FileSize !== 'undefined') {
            let size = this.exif.FILE.FileSize
            //convert to KB or MB
            if (size > 1024) {
                size = size / 1024
                if (size > 1024) {
                    size = size / 1024
                    exifData += `<li>File Size: ${size.toFixed(2)} MB</li>`
                } else
                    exifData += `<li>File Size: ${size.toFixed(2)} KB</li>`
            }
        }

        if (typeof this.exif.EXIF.DateTimeOriginal !== 'undefined')
            exifData += `<li>Date: ${this.exif.EXIF.DateTimeOriginal}</li>`

        if (typeof this.exif.EXIF.ISOSpeedRatings !== 'undefined')
            exifData += `<li>ISO: ${this.exif.EXIF.ISOSpeedRatings}</li>`

        if (typeof this.exif.EXIF.ExifImageWidth !== 'undefined' && typeof this.exif.EXIF.ExifImageLength !== 'undefined')
            exifData += `<li>Size: ${this.exif.EXIF.ExifImageWidth}x${this.exif.EXIF.ExifImageLength} px</li>`

        if (typeof this.exif.GPS.GPSLatitude !== 'undefined'
            && typeof this.exif.GPS.GPSLongitude !== 'undefined'
            && typeof this.exif.GPS.GPSAltitude !== 'undefined'
            && typeof this.exif.GPS.GPSLatitudeRef !== 'undefined') {
            //convert to lat lng

            let lat = this.convertDMSToDD(this.exif.GPS.GPSLatitude)
            let lng = this.convertDMSToDD(this.exif.GPS.GPSLongitude)
            let alt = this.fractionToDecimal(this.exif.GPS.GPSAltitude)

            let ref = this.exif.GPS.GPSLatitudeRef

            if (ref === 'W')
                lng = -lng;

            exifData += `<li>Latitude: ${lat}</li>`
            exifData += `<li>Longitude: ${lng}</li>`
            exifData += `<li>Altitude: ${alt} m</li>`
            exifData += `<li><a class="btn" href="https://www.google.com/maps/search/?api=1&query=${lat},${lng}" target="_blank">View on Google Maps</a></li>`
        }

        exifData += '</ul>'

        this.debugExifElement.innerHTML = exifData
    }

    onDocumentMouseDown(event) {
        this.isUserInteracting = true
        this.onPointerDownPointerX = event.clientX
        this.onPointerDownPointerY = event.clientY
        this.onPointerDownLon = this.lon
        this.onPointerDownLat = this.lat
    }

    onDocumentMouseMove(event) {
        if (this.isUserInteracting) {
            this.lon = (this.onPointerDownPointerX - event.clientX) * 0.1 + this.onPointerDownLon
            this.lat = (event.clientY - this.onPointerDownPointerY) * 0.1 + this.onPointerDownLat
        }
    }

    onDocumentMouseUp(event) {
        this.isUserInteracting = false
    }

    onDocumentMouseWheel(event) {
        this.fov += event.deltaY * 0.05
        if (this.fov < 20 || this.fov > 90)
            this.fov = (this.fov < 20) ? 20 : 90

        this.camera.fov = this.fov
        this.camera.updateProjectionMatrix()
    }

    onWindowResized(event) {
        this.renderer.setSize(window.innerWidth, window.innerHeight)
        this.camera.projectionMatrix.makePerspective(this.fov, 2, 1, 1000)
    }

    onDocumentTouchStart(event) {
        if (event.touches.length == 1) {
            event.preventDefault()
            this.isUserInteracting = true
            this.onPointerDownPointerX = event.touches[0].pageX
            this.onPointerDownPointerY = event.touches[0].pageY
            this.onPointerDownLon = this.lon
            this.onPointerDownLat = this.lat
        }
    }

    onDocumentTouchMove(event) {
        if (event.touches.length == 1) {
            event.preventDefault()
            this.lon = (this.onPointerDownPointerX - event.touches[0].pageX) * 0.1 + this.onPointerDownLon
            this.lat = (event.touches[0].pageY - this.onPointerDownPointerY) * -0.1 + this.onPointerDownLat
        } else if (event.touches.length == 2) {
            this.isUserInteracting = true
            let dx = event.touches[0].pageX - event.touches[1].pageX
            let dy = event.touches[0].pageY - event.touches[1].pageY
            let distance = Math.sqrt(dx * dx + dy * dy)
            let scale = distance / 100
            this.fov = this.fov * scale
            if (this.fov < 20 || this.fov > 90)
                this.fov = (this.fov < 20) ? 20 : 90

            this.camera.fov = this.fov
        }
    }

    onDocumentTouchEnd(event) {
        this.isUserInteracting = false
    }

    onDebugBtnClick(event) {
        this.is_debug = !this.is_debug
        this.debugElement.style.display = (this.is_debug) ? 'block' : 'none'
    }
}

