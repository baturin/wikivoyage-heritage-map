/*
 * L.Control.ZoomDisplay shows the current map zoom level
 Recent changes:
 2015-09-14: Container title = Zoom level
 */

L.Control.ZoomDisplay = L.Control.extend({
    options: {
        position: 'topleft'
    },

    onAdd: function (map) {
        this._map = map;
        this._container = L.DomUtil.create('div', 'leaflet-control-zoom-display leaflet-bar-part leaflet-bar');
        this.updateMapZoom(map.getZoom());
        this._container.title = L._("Zoom level");
        map.on('zoomend', this.onMapZoomEnd, this);
        return this._container;
    },

    onRemove: function (map) {
        map.off('zoomend', this.onMapZoomEnd, this);
    },

    onMapZoomEnd: function (e) {
        this.updateMapZoom(this._map.getZoom());
    },

    updateMapZoom: function (zoom) {
        this._container.innerHTML = zoom;
    }
});

L.Map.mergeOptions({
    zoomDisplayControl: true
});

L.Map.addInitHook(function () {
    if (this.options.zoomDisplayControl) {
        this.zoomDisplayControl = new L.Control.ZoomDisplay();
        this.addControl(this.zoomDisplayControl);
    }
});

L.control.zoomDisplay = function (options) {
    return new L.Control.ZoomDisplay(options);
};