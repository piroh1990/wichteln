/**
 * Filter für /wichtel-ideen.
 * Ohne dieses Script bleiben alle Ideen sichtbar.
 * Preis meint «bis zu dieser Stufe», also die gewählte Stufe und alles Günstigere.
 */
(function () {
    var AREAS = ['spiele', 'wellness', 'buero', 'kreativ', 'lesen', 'alltag', 'kalender'];
    var PRICES = ['10', '20', '30', '50'];

    function start() {
        var root = document.querySelector('[data-ideas-filter]');
        var catalog = document.querySelector('[data-ideas-catalog]');
        if (!root || !catalog) {
            return;
        }

        var state = { bereich: '', preis: '' };

        function chips(name) {
            return root.querySelectorAll('[data-filter="' + name + '"]');
        }

        function allowed(name, value) {
            if (!value) {
                return '';
            }
            var list = name === 'bereich' ? AREAS : PRICES;
            for (var i = 0; i < list.length; i++) {
                if (list[i] === value) {
                    return value;
                }
            }
            return '';
        }

        function readUrl() {
            var params = new URLSearchParams(window.location.search);
            state.bereich = allowed('bereich', params.get('bereich') || '');
            state.preis = allowed('preis', params.get('preis') || '');
        }

        function writeUrl() {
            var url = new URL(window.location.href);
            if (state.bereich) {
                url.searchParams.set('bereich', state.bereich);
            } else {
                url.searchParams.delete('bereich');
            }
            if (state.preis) {
                url.searchParams.set('preis', state.preis);
            } else {
                url.searchParams.delete('preis');
            }
            if (url.href !== window.location.href) {
                window.history.replaceState(null, '', url);
            }
        }

        function mark(name, value) {
            var list = chips(name);
            for (var i = 0; i < list.length; i++) {
                var on = (list[i].getAttribute('data-value') || '') === value;
                list[i].setAttribute('aria-checked', on ? 'true' : 'false');
                list[i].tabIndex = on ? 0 : -1;
            }
        }

        function apply() {
            var max = state.preis === '' ? 100 : parseInt(state.preis, 10);
            var cards = catalog.querySelectorAll('.idea-card');
            var shown = 0;
            for (var i = 0; i < cards.length; i++) {
                var card = cards[i];
                var area = card.getAttribute('data-bereich') || '';
                var price = parseInt(card.getAttribute('data-preis') || '0', 10);
                var match = (state.bereich === '' || area === state.bereich) && price > 0 && price <= max;
                card.hidden = !match;
                if (match) {
                    shown += 1;
                }
            }
            var bands = catalog.querySelectorAll('.ideas-band');
            for (var b = 0; b < bands.length; b++) {
                bands[b].hidden = bands[b].querySelectorAll('.idea-card:not([hidden])').length === 0;
            }
            var count = root.querySelector('[data-ideas-count]');
            if (count) {
                var template = shown === 1 ? count.getAttribute('data-one') : count.getAttribute('data-many');
                count.textContent = String(template || '').replace('{n}', String(shown));
            }
            var empty = root.querySelector('[data-ideas-empty]');
            if (empty) {
                empty.hidden = shown !== 0;
            }
            mark('bereich', state.bereich);
            mark('preis', state.preis);
        }

        function select(name, value) {
            if (name !== 'bereich' && name !== 'preis') {
                return;
            }
            state[name] = allowed(name, value);
            apply();
            writeUrl();
        }

        function fromEvent(event) {
            var node = event.target;
            if (!node || node.nodeType !== 1) {
                node = node ? node.parentElement : null;
            }
            return node;
        }

        root.addEventListener('click', function (event) {
            var node = fromEvent(event);
            if (!node) {
                return;
            }
            var button = node.closest('[data-filter]');
            if (button && root.contains(button)) {
                select(button.getAttribute('data-filter'), button.getAttribute('data-value') || '');
                return;
            }
            var reset = node.closest('[data-ideas-reset]');
            if (reset && root.contains(reset)) {
                event.preventDefault();
                state.bereich = '';
                state.preis = '';
                apply();
                writeUrl();
                var first = root.querySelector('[data-filter="bereich"]');
                if (first) {
                    first.focus();
                }
            }
        });

        root.addEventListener('keydown', function (event) {
            var node = fromEvent(event);
            if (!node) {
                return;
            }
            var button = node.closest('[data-filter]');
            if (!button || !root.contains(button)) {
                return;
            }
            var key = event.key;
            var forward = key === 'ArrowRight' || key === 'ArrowDown';
            var back = key === 'ArrowLeft' || key === 'ArrowUp';
            if (!forward && !back && key !== 'Home' && key !== 'End') {
                return;
            }
            var list = Array.prototype.slice.call(chips(button.getAttribute('data-filter')));
            var index = list.indexOf(button);
            if (index < 0 || list.length === 0) {
                return;
            }
            event.preventDefault();
            var next = index;
            if (key === 'Home') {
                next = 0;
            } else if (key === 'End') {
                next = list.length - 1;
            } else if (forward) {
                next = (index + 1) % list.length;
            } else {
                next = (index - 1 + list.length) % list.length;
            }
            select(button.getAttribute('data-filter'), list[next].getAttribute('data-value') || '');
            list[next].focus();
        });

        readUrl();
        apply();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
