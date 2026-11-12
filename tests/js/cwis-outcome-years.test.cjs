const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const blade = fs.readFileSync(path.join(__dirname, '../../resources/views/cwis/generator-data/index.blade.php'), 'utf8');
const refresh = blade.match(/function refreshInputYears\(\) \{[\s\S]*?\n        \}/)[0]
    .replace("@json(__('No reporting years available'))", "'No reporting years available'");

function setup(mapping) {
    const state = { outcome: 'equity', filter: '', disabled: false };
    const yearGroup = { hidden: true };
    const select = {
        options: [], value: '', disabled: false,
        replaceChildren() { this.options = []; this.value = ''; },
        add(option) {
            this.options.push(option);
            if (this.options.length === 1 || option.selected) this.value = option.value;
        }
    };
    const context = {
        inputYearsByOutcome: mapping,
        document: { getElementById: id => id === 'add-indicator-year-group' ? yearGroup : select },
        Option: function (text, value, defaultSelected, selected) {
            return { text: String(text), value: String(value), selected: !!selected };
        },
        $(selector) {
            return {
                val: () => selector.includes('input:checked') ? state.outcome : state.filter,
                prop: (name, value) => { state.disabled = value; }
            };
        }
    };
    vm.createContext(context);
    vm.runInContext(refresh, context);
    return { state, select, yearGroup, refresh: () => vm.runInContext('refreshInputYears()', context) };
}

test('radio choice shows only the available years for its outcome', () => {
    const ui = setup({ equity: [2025, 2024], sustainability: [2026, 2023] });
    ui.refresh();
    assert.deepEqual(ui.select.options.map(option => option.value), ['2025', '2024']);
    ui.state.outcome = 'sustainability';
    ui.refresh();
    assert.deepEqual(ui.select.options.map(option => option.value), ['2026', '2023']);
    assert.equal(ui.select.value, '2026');
    ui.state.outcome = 'equity';
    ui.refresh();
    assert.deepEqual(ui.select.options.map(option => option.value), ['2025', '2024']);
});

test('empty outcomes disable the year and Continue until another outcome is chosen', () => {
    const ui = setup({ equity: [], sustainability: [2026] });
    ui.refresh();
    assert.equal(ui.select.value, '');
    assert.equal(ui.select.disabled, true);
    assert.equal(ui.state.disabled, true);
    ui.state.outcome = 'sustainability';
    ui.refresh();
    assert.equal(ui.select.disabled, false);
    assert.equal(ui.state.disabled, false);
});

test('switching outcome resets an old year to the latest available year', () => {
    const ui = setup({ equity: [2026, 2025], sustainability: [2024, 2026, 2025] });
    ui.select.value = '2025';
    ui.state.outcome = 'sustainability';
    ui.refresh();
    assert.equal(ui.select.value, '2026');
    assert.deepEqual(ui.select.options.map(option => option.value), ['2026', '2025', '2024']);
});

test('year remains hidden and Continue disabled until an outcome is selected', () => {
    const ui = setup({ equity: [2025], sustainability: [2026] });
    ui.state.outcome = undefined;
    ui.refresh();
    assert.equal(ui.yearGroup.hidden, true);
    assert.equal(ui.select.disabled, true);
    assert.equal(ui.state.disabled, true);
    ui.state.outcome = 'sustainability';
    ui.refresh();
    assert.equal(ui.yearGroup.hidden, false);
    assert.equal(ui.select.value, '2026');
    assert.equal(ui.state.disabled, false);
});
