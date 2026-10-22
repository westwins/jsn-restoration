import $ from 'jquery';
import * as bootstrap from 'bootstrap';
import { MarkerClusterer as GoogleMarkerClusterer } from '@googlemaps/markerclusterer';
import noUiSlider from 'nouislider';
import './global-modern.js';

import 'bootstrap/dist/css/bootstrap.min.css';
import '@fortawesome/fontawesome-free/css/all.min.css';
import 'nouislider/dist/nouislider.css';
import 'vis-network/styles/vis-network.min.css';
import '../styles/app.less';

window.$ = window.jQuery = $;
window.bootstrap = bootstrap;
window.noUiSlider = noUiSlider;

// Preserve the small API surface used by the historical inline map script
// while delegating clustering to the maintained Google package.
window.MarkerClusterer = class MarkerClusterer extends GoogleMarkerClusterer {
  constructor(map, markers = [], options = {}) {
    super({ map, markers, algorithmOptions: options });
  }

  repaint() {
    this.render();
  }
};

// Bootstrap 5 no longer registers jQuery plugins. These adapters cover the few
// legacy inline calls until those page-local scripts are fully extracted.
$.fn.tooltip = function tooltip(options = {}) {
  return this.each((_, element) => bootstrap.Tooltip.getOrCreateInstance(element, options));
};
$.fn.popover = function popover(options = {}) {
  return this.each((_, element) => bootstrap.Popover.getOrCreateInstance(element, options));
};
$.fn.modal = function modal(action) {
  return this.each((_, element) => {
    const instance = bootstrap.Modal.getOrCreateInstance(element);
    if (typeof instance[action] === 'function') instance[action]();
  });
};
