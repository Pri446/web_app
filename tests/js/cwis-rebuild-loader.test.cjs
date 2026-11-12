const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const blade = fs.readFileSync(path.join(__dirname, '../../resources/views/cwis/generator-data/show.blade.php'), 'utf8');
const script = blade.match(/<script\b[^>]*>([\s\S]*?)<\/script\s*>/i)[1];

function setup(confirmed) {
    const events = {};
    const frames = [];
    const button = { disabled: false };
    const loader = { hidden: true, focus() {} };
    let submissions = 0;
    const confirmations = [];
    const form = {
        dataset: { confirm: 'Confirm rebuild' },
        querySelector: () => button,
        addEventListener(name, handler) { events[name] = handler; },
        setAttribute() {}, removeAttribute() {}
    };
    const context = {
        document: {
            addEventListener(name, handler) { if (name === 'DOMContentLoaded') handler(); },
            getElementById(id) { return id === 'cwis-rebuild-form' ? form : loader; }
        },
        window: {
            Swal: { fire(options) { confirmations.push(options); return Promise.resolve({ isConfirmed: confirmed }); } },
            requestAnimationFrame(handler) { frames.push(handler); },
            addEventListener(name, handler) { events[name] = handler; }
        },
        HTMLFormElement: { prototype: { submit() { submissions++; } } }
    };
    vm.runInNewContext(script, context);
    return { events, frames, button, loader, form, confirmations, submissions: () => submissions };
}

test('confirmed rebuild shows loader before submission and blocks repeated clicks', async () => {
    const state = setup(true);
    const confirmation = state.events.submit({ preventDefault() {} });
    await state.events.submit({ preventDefault() {} });
    await confirmation;
    assert.equal(state.confirmations.length, 1);
    assert.equal(state.confirmations[0].showCancelButton, true);
    assert.equal(state.loader.hidden, false);
    assert.equal(state.button.disabled, true);
    assert.equal(state.submissions(), 0);
    while (state.frames.length) state.frames.shift()();
    assert.equal(state.submissions(), 1);
    state.events.pageshow({ persisted: true });
    assert.equal(state.loader.hidden, true);
    assert.equal(state.button.disabled, false);
    assert.equal(state.form.dataset.busy, undefined);
});

test('cancelled confirmation does not show loader or submit', async () => {
    const state = setup(false);
    await state.events.submit({ preventDefault() {} });
    assert.equal(state.loader.hidden, true);
    assert.equal(state.button.disabled, false);
    assert.equal(state.frames.length, 0);
    assert.equal(state.submissions(), 0);
});

test('loading overlay is scoped to the indicator page instead of the window', () => {
    assert.match(blade, /#cwis-indicator-page\s*\{\s*position: relative;/);
    assert.match(blade, /#cwis-rebuild-loader\s*\{\s*position: absolute;/);
    assert.ok(blade.indexOf('<div id="cwis-indicator-page">') < blade.indexOf('<div id="cwis-rebuild-loader"'));
    assert.doesNotMatch(script, /window\.confirm/);
});

test('first generation without existing results does not request rebuild confirmation', async () => {
    const state = setup(true);
    delete state.form.dataset.confirm;
    await state.events.submit({ preventDefault() {} });
    assert.equal(state.confirmations.length, 0);
    assert.equal(state.loader.hidden, false);
    while (state.frames.length) state.frames.shift()();
    assert.equal(state.submissions(), 1);
});
