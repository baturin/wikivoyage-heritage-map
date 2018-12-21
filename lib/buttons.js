/*
 * L.Control.ZoomFS - default Leaflet.Zoom control with added buttons
 * https://github.com/elidupuis/leaflet.zoomfs
 * 
 * Recent changes:
 * 2015-04-27: Localization
 * 2015-04-03: only zoom buttons for layer F 
 * 2014-06-15: tooltips for desti & markers buttons
 * 2013-09-16: complete modificate User:Mey2008 de.Wikivoyage
 */
L.Control.Buttons = L.Control.Zoom.extend({
  includes: L.Mixin.Events,
  onAdd: function (map) {
    var zoomName = 'leaflet-control-zoom',
      barName = 'leaflet-bar',
      partName = barName + '-part',
      container = L.DomUtil.create('div', zoomName + ' ' + barName);

    this._map = map;

    this._zoomInButton = this._createButton('+', L._('Zoom in'),
      zoomName + '-in ' +
      partName + ' ' +
      partName + '-top',
      container, this._zoomIn, this);

    this._zoomOutButton = this._createButton('-', L._('Zoom out'),
      zoomName + '-out ' +
      partName + ' ' +
      partName + ' ',
      container, this._zoomOut, this);
    /*
    this._printButton = this._createButton('', 'Print screen',
      'leaflet-control-printer ' +
      partName + ' ' +
      partName + ' ',
      container, this.doPrint, this);
    */
    if (jslayer.indexOf("E") == -1) {
      this._zoomFullScreenButton = this._createButton('', L._('POIs ⇔ destinations'),
        'leaflet-control-dest ' +
        partName + ' ' +
        partName + ' ',
        container, this.doDest, this);

      this._allMarkersButton = this._createButton('', L._('Map center ⇔ all markers'),
        'leaflet-control-all ' +
        partName + ' ' +
        partName + '-bottom',
        container, this.doAll, this);
    }

    map.on('zoomend zoomlevelschange', this._updateDisabled, this);

    return container;

  },

  /* 
  doPrint: function () {
    window.print();
  },
  */

  doAll: function () {
    onAll();
  },

  doDest: function () {
    onDest();
  }

});

