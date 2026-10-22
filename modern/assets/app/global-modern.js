import $ from 'jquery';
import * as bootstrap from 'bootstrap';
import noUiSlider from 'nouislider';

window.$ = window.jQuery = $;
window.bootstrap = bootstrap;
window.noUiSlider = noUiSlider;

const mapsAlreadyLoaded = window.__googleMapsLoaded === true;

const debounce = (callback, delay = 180) => {
  let timer;
  return (...args) => {
    window.clearTimeout(timer);
    timer = window.setTimeout(() => callback(...args), delay);
  };
};

const escapeHtml = (value) => {
  const node = document.createElement('div');
  node.textContent = value ?? '';
  return node.innerHTML;
};

function attachAutocomplete(input, provider, onSelect = (item) => {
  if (item.url) window.location.assign(item.url);
}) {
  const parent = input.parentElement;
  parent.classList.add('jsn-autocomplete');
  input.setAttribute('autocomplete', 'off');
  input.setAttribute('aria-autocomplete', 'list');

  const menu = document.createElement('div');
  menu.className = 'jsn-autocomplete-menu';
  menu.hidden = true;
  menu.setAttribute('role', 'listbox');
  parent.append(menu);

  let results = [];
  let activeIndex = -1;

  const close = () => {
    menu.hidden = true;
    activeIndex = -1;
  };

  const select = (item) => {
    close();
    input.value = item.value ?? item.description ?? '';
    onSelect(item);
  };

  const render = (groups) => {
    results = groups.flatMap((group) => group.items);
    menu.replaceChildren();

    for (const group of groups) {
      if (!group.items.length) continue;
      const heading = document.createElement('div');
      heading.className = 'jsn-autocomplete-heading';
      heading.textContent = group.label;
      menu.append(heading);

      for (const item of group.items) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'jsn-autocomplete-item border-0 w-100 text-start';
        button.setAttribute('role', 'option');
        button.innerHTML = `${escapeHtml(item.value ?? item.description)}${item.text ? `<br><small class="text-muted">${escapeHtml(item.text)}</small>` : ''}`;
        button.addEventListener('mousedown', (event) => {
          event.preventDefault();
          select(item);
        });
        menu.append(button);
      }
    }

    menu.hidden = results.length === 0;
  };

  const search = debounce(async () => {
    const query = input.value.trim();
    if (query.length < 2) return close();

    input.closest('.form-group, .jsn-searchbox')?.classList.add('loading');
    try {
      render(await provider(query));
    } catch (error) {
      console.error('Autocomplete request failed', error);
      close();
    } finally {
      input.closest('.form-group, .jsn-searchbox')?.classList.remove('loading');
    }
  });

  input.addEventListener('input', search);
  input.addEventListener('keydown', (event) => {
    const items = [...menu.querySelectorAll('.jsn-autocomplete-item')];
    if (event.key === 'Escape') return close();
    if (!items.length || !['ArrowDown', 'ArrowUp', 'Enter'].includes(event.key)) return;
    event.preventDefault();
    if (event.key === 'Enter' && activeIndex >= 0) return select(results[activeIndex]);
    activeIndex = event.key === 'ArrowDown'
      ? Math.min(activeIndex + 1, items.length - 1)
      : Math.max(activeIndex - 1, 0);
    items.forEach((item, index) => item.classList.toggle('active', index === activeIndex));
    items[activeIndex]?.focus({ preventScroll: true });
  });
  input.addEventListener('blur', () => window.setTimeout(close, 120));
}

async function fetchJson(url) {
  const response = await fetch(url, { headers: { Accept: 'application/json' } });
  if (!response.ok) throw new Error(`${response.status} ${response.statusText}`);
  return response.json();
}

let taxonomyPromise;
const loadTaxonomies = () => {
  taxonomyPromise ??= Promise.all([
    fetchJson('/subjects.json'),
    fetchJson('/occupations.json'),
    fetchJson('/regions.json'),
  ]);
  return taxonomyPromise;
};

function initializePersonAutocomplete() {
  const body = document.body;
  const personPattern = body.dataset.urlAutocompletePersons;
  const placePattern = body.dataset.urlAutocompletePlaces;

  document.querySelectorAll('.js-autocomplete').forEach((input) => {
    const quick = input.classList.contains('js-quicksearch');
    attachAutocomplete(input, async (query) => {
      const personsPromise = fetchJson(personPattern.replace('%QUERY', encodeURIComponent(query)));
      if (!quick) return [{ label: 'Persons', items: (await personsPromise).slice(0, 12) }];

      const [persons, places, [subjects, occupations]] = await Promise.all([
        personsPromise,
        fetchJson(placePattern.replace('%QUERY', encodeURIComponent(query))),
        loadTaxonomies(),
      ]);
      const needle = query.toLocaleLowerCase();
      const matching = (items) => items.filter((item) => item.value.toLocaleLowerCase().includes(needle)).slice(0, 6);

      return [
        { label: 'Persons', items: persons.slice(0, 8) },
        { label: 'Places', items: places.slice(0, 6) },
        { label: 'Subjects', items: matching(subjects) },
        { label: 'Occupations', items: matching(occupations) },
      ];
    });
  });
}

function initializeWhereAutocomplete() {
  const input = document.querySelector('#where-search');
  if (!input || input.dataset.initialized) return;
  input.dataset.initialized = 'true';

  attachAutocomplete(input, async (query) => {
    const [, , regions] = await loadTaxonomies();
    const needle = query.toLocaleLowerCase();
    const local = regions.filter((item) => item.value.toLocaleLowerCase().includes(needle)).slice(0, 8);
    let cities = [];

    if (window.google?.maps?.importLibrary) {
      try {
        const { AutocompleteSuggestion } = await google.maps.importLibrary('places');
        const { suggestions } = await AutocompleteSuggestion.fetchAutocompleteSuggestions({
          input: query,
          includedPrimaryTypes: ['locality'],
          language: 'en',
        });
        cities = suggestions.map(({ placePrediction }) => ({
          placePrediction,
          value: placePrediction.text.toString(),
        }));
      } catch (error) {
        console.warn('Google Places suggestions are unavailable', error);
      }
    }

    return [
      { label: 'Regions', items: local },
      { label: 'Cities', items: cities.slice(0, 8) },
    ];
  }, async (item) => {
    if (item.url) return window.location.assign(item.url);
    if (!item.placePrediction) return;
    try {
      const place = item.placePrediction.toPlace();
      await place.fetchFields({ fields: ['location', 'displayName', 'formattedAddress'] });
      if (!place.location) return;
      document.querySelector('#where-lat').value = place.location.lat();
      document.querySelector('#where-lng').value = place.location.lng();
      document.querySelector('#where-descr').value = item.value;
      document.querySelector('#where-selection').textContent = item.value;
      document.querySelector('#where-button').disabled = false;
      document.querySelector('#where-button').classList.remove('disabled');
      document.querySelector('#where-box').classList.remove('hidden');
      input.value = '';
      document.querySelector('#where-radius')?.focus();
    } catch (error) {
      console.error('Unable to resolve the selected place', error);
    }
  });
}

function initializeSubjectTrees() {
  document.querySelectorAll('.js-tree').forEach((container) => {
    let tree;
    try {
      tree = JSON.parse(container.dataset.tree || '[]');
    } catch (error) {
      console.error('Invalid subject tree data', error);
      return;
    }

    container.classList.add('jsn-tree');
    const selected = new Set();
    const output = document.querySelector('#what-selection');
    const submit = document.querySelector('#what-button');

    const update = () => {
      if (output) output.value = [...selected].join(' ');
      if (submit) {
        submit.disabled = selected.size === 0;
        submit.classList.toggle('disabled', selected.size === 0);
      }
    };

    const build = (nodes) => {
      const list = document.createElement('ul');
      for (const node of nodes) {
        const item = document.createElement('li');
        const label = document.createElement('label');
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.value = node.id ?? '';
        label.append(checkbox, document.createTextNode(node.text ?? node.title ?? ''));
        item.append(label);

        let childList;
        if (node.nodes?.length) {
          childList = build(node.nodes);
          item.append(childList);
        }

        checkbox.addEventListener('change', () => {
          item.querySelectorAll('input[type="checkbox"]').forEach((child) => {
            child.checked = checkbox.checked;
            if (child.value) checkbox.checked ? selected.add(child.value) : selected.delete(child.value);
          });
          update();
        });
        list.append(item);
      }
      return list;
    };

    container.replaceChildren(build(tree));
    update();
  });
}

function initializeSliders() {
  document.querySelectorAll('.when-slider').forEach((slider) => {
    const min = Number(slider.dataset.sliderMin ?? 1490);
    const max = Number(slider.dataset.sliderMax ?? 1870);
    const start = JSON.parse(slider.dataset.sliderValue ?? `[${min},${max}]`);
    const row = slider.closest('tr, .row, form, .modal-body');
    const from = row?.querySelector('input[name="from"], #when-from');
    const to = row?.querySelector('input[name="to"], #when-to');

    noUiSlider.create(slider, {
      start,
      connect: true,
      step: Number(slider.dataset.sliderStep ?? 1),
      range: { min, max },
      format: {
        to: (value) => String(Math.round(value)),
        from: Number,
      },
    });
    slider.noUiSlider.on('update', ([fromValue, toValue]) => {
      if (from) from.value = fromValue;
      if (to) to.value = toValue;
    });
    const sync = () => slider.noUiSlider.set([
      Math.max(min, Number(from?.value ?? min)),
      Math.min(max, Number(to?.value ?? max)),
    ]);
    from?.addEventListener('change', sync);
    to?.addEventListener('change', sync);
  });
}

function initializeBootstrap() {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
    bootstrap.Tooltip.getOrCreateInstance(element);
  });
  document.querySelectorAll('[data-bs-toggle="popover"]').forEach((element) => {
    bootstrap.Popover.getOrCreateInstance(element, {
      html: element.dataset.bsHtml === 'true',
      trigger: element.dataset.bsTrigger || 'click',
    });
  });
}

function initializeLegacyInteractions() {
  document.querySelectorAll('a[href^="#"]:not([data-bs-toggle="tab"]):not([href="#"])').forEach((anchor) => {
    anchor.addEventListener('click', (event) => {
      const target = document.querySelector(anchor.getAttribute('href'));
      if (!target) return;
      event.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      if (anchor.classList.contains('jumphighlight')) {
        target.animate([{ backgroundColor: '#fff3a0' }, { backgroundColor: 'transparent' }], { duration: 1800 });
      }
    });
  });

  document.querySelectorAll('.js-show-hiddennames').forEach((button) => {
    button.addEventListener('click', () => {
      button.closest('div')?.classList.add('hidden');
      button.closest('.panel-body')?.querySelectorAll('li.hidden').forEach((item) => item.classList.remove('hidden'));
    });
  });

  document.querySelectorAll('.js-form-nosubmit').forEach((form) => form.addEventListener('submit', (event) => event.preventDefault()));

  document.querySelectorAll('input.collector').forEach((collector) => {
    const form = collector.closest('form');
    const selected = new Set(collector.value.split(' ').filter(Boolean));
    form?.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
      checkbox.checked = selected.has(checkbox.value);
      checkbox.addEventListener('change', () => {
        collector.value = [...form.querySelectorAll('input[type="checkbox"]:checked')].map((item) => item.value).join(' ');
      });
    });
  });

  document.querySelectorAll('table.click-to-select tr').forEach((row) => {
    row.addEventListener('click', (event) => {
      if (event.target.closest('input, a, button')) return;
      row.querySelector('input')?.click();
    });
  });

  document.querySelector('#apply-sources')?.addEventListener('click', (event) => {
    event.currentTarget.closest('.modal')?.querySelector('form')?.requestSubmit();
  });

  document.querySelectorAll('.js-apply-daterange').forEach((button) => {
    button.addEventListener('click', () => {
      const params = JSON.parse(button.dataset.params || '{}');
      const scope = button.closest('.modal, form, body');
      params.from = scope.querySelector('input[name="from"], #when-from')?.value;
      params.to = scope.querySelector('input[name="to"], #when-to')?.value;
      window.location.assign(`${button.dataset.url}?${new URLSearchParams(params)}`);
    });
  });
}

window.initMap = () => {
  window.__googleMapsLoaded = true;
  window.mapIsReady = true;
  document.dispatchEvent(new CustomEvent('map:ready'));
  $(document.body).trigger('map:ready');
  initializeWhereAutocomplete();
};

window.mapIsReady = false;
document.addEventListener('map:ready', initializeWhereAutocomplete);

document.addEventListener('DOMContentLoaded', () => {
  initializeBootstrap();
  initializePersonAutocomplete();
  initializeSubjectTrees();
  initializeSliders();
  initializeLegacyInteractions();
  if (window.mapIsReady) initializeWhereAutocomplete();
});

if (mapsAlreadyLoaded) {
  window.setTimeout(window.initMap, 0);
}
