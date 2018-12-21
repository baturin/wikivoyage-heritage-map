/*
 * L.Control.ZoomFS - default Leaflet.Zoom control with added buttons
 * built to work with Leaflet version 0.5
 * https://github.com/elidupuis/leaflet.zoomfs
 * complete modificate User:Mey2008 de.Wikivoyage
 */
L.Control.Buttons = L.Control.Zoom.extend({
	includes: L.Mixin.Events,
	onAdd: function (map) {
		var zoomName = 'leaflet-control-zoom',
				barName = 'leaflet-bar',
				partName = barName + '-part',
				container = L.DomUtil.create('div', zoomName + ' ' + barName);

		this._map = map;

		this._zoomInButton = this._createButton('+', 'Zoom in',
						zoomName + '-in ' +
            partName + ' ' +
            partName + '-top',
						container, this._zoomIn,  this);

		this._zoomOutButton = this._createButton('-', 'Zoom out',
						zoomName + '-out ' +
						partName + ' ' +
						partName + ' ',
						container, this._zoomOut, this);

		this._allMarkersButton = this._createButton('','Show me all markers',
						'leaflet-control-all ' +
						partName + ' ' +
						partName + '-bottom',
						container, this.doAll, this);

		map.on('zoomend zoomlevelschange', this._updateDisabled, this);

		return container;
	},
	
doPrint: function () {
  window.print();
},

doAll: function () {
  onAll();
},

doDest: function () {
  onDest();
}

});
