/**
 * Shared stationery request cart, backed by localStorage, so the catalog
 * landing page and the New Request modal can add/edit the same draft
 * without either one holding the source of truth in memory.
 */
(function (window) {
    'use strict';

    function storageKey(userId) {
        return 'stationery_request_draft_' + userId;
    }

    function getLines(userId) {
        var saved = localStorage.getItem(storageKey(userId));

        if (!saved) {
            return [];
        }

        try {
            var parsed = JSON.parse(saved);
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) {
            return [];
        }
    }

    function saveLines(userId, lines) {
        localStorage.setItem(storageKey(userId), JSON.stringify(lines));
        window.dispatchEvent(new CustomEvent('stationery-cart:updated', {
            detail: { userId: userId, lines: lines },
        }));
    }

    function addItem(userId, item, qty) {
        qty = qty || 1;

        var lines = getLines(userId);
        var existing = lines.find(function (line) { return line.item_id === item.id; });

        if (existing) {
            existing.qty += qty;
        } else {
            lines.push({
                item_id: item.id,
                name: item.name,
                unit_of_measure: item.unit_of_measure,
                unit_cost: parseFloat(item.unit_cost),
                qty: qty,
            });
        }

        saveLines(userId, lines);

        return lines;
    }

    function updateQty(userId, itemId, qty) {
        var lines = getLines(userId);
        var line = lines.find(function (line) { return line.item_id === itemId; });

        if (line) {
            line.qty = qty;
        }

        saveLines(userId, lines);

        return lines;
    }

    function removeItem(userId, itemId) {
        var lines = getLines(userId).filter(function (line) { return line.item_id !== itemId; });
        saveLines(userId, lines);

        return lines;
    }

    function clear(userId) {
        localStorage.removeItem(storageKey(userId));
        window.dispatchEvent(new CustomEvent('stationery-cart:updated', {
            detail: { userId: userId, lines: [] },
        }));
    }

    function getCount(userId) {
        return getLines(userId).reduce(function (sum, line) { return sum + line.qty; }, 0);
    }

    window.StationeryCart = {
        getLines: getLines,
        saveLines: saveLines,
        addItem: addItem,
        updateQty: updateQty,
        removeItem: removeItem,
        clear: clear,
        getCount: getCount,
    };
})(window);
