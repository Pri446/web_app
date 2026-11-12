const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');
const script = fs.readFileSync(path.join(__dirname, '../../resources/views/cwis/sustainability/rebuild-script.blade.php'), 'utf8').match(/<script\b[^>]*>([\s\S]*?)<\/script\b[^>]*>/i)[1];

function setup(confirmed) {
    const events = {}, frames = [];
    const button = { disabled: false }, loader = { hidden: true, focus() {} };
    let submissions = 0;
    const form = { dataset: { confirm: 'Rebuild?' }, querySelector: () => button,
        addEventListener: (name, fn) => { events[name] = fn; }, setAttribute() {}, removeAttribute() {} };
    vm.runInNewContext(script, {
        document: { addEventListener: (name, fn) => fn(), getElementById: id => id === 'cwis-rebuild-form' ? form : loader },
        window: { Swal: { fire: () => Promise.resolve({ isConfirmed: confirmed }) }, requestAnimationFrame: fn => frames.push(fn), addEventListener: (name, fn) => { events[name] = fn; } },
        HTMLFormElement: { prototype: { submit() { submissions++; } } }
    });
    return { events, frames, form, button, loader, submissions: () => submissions };
}

test('confirmed Sustainability rebuild shows a page loader and blocks duplicate submission', async () => {
    const ui = setup(true);
    const pending = ui.events.submit({ preventDefault() {} });
    await ui.events.submit({ preventDefault() {} });
    await pending;
    assert.equal(ui.loader.hidden, false);
    assert.equal(ui.button.disabled, true);
    while (ui.frames.length) ui.frames.shift()();
    assert.equal(ui.submissions(), 1);
    ui.events.pageshow({ persisted: true });
    assert.equal(ui.loader.hidden, true);
    assert.equal(ui.button.disabled, false);
});

test('cancelled Sustainability rebuild leaves the form unchanged', async () => {
    const ui = setup(false);
    await ui.events.submit({ preventDefault() {} });
    assert.equal(ui.loader.hidden, true);
    assert.equal(ui.button.disabled, false);
    assert.equal(ui.submissions(), 0);
});
