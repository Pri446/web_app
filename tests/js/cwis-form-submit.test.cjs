const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/js/cwis-form-submit.js', 'utf8');

function setup(response) {
    const file = {name: 'evidence[question]', files: ['evidence.pdf'],
        classList: {add() {}}, insertAdjacentElement() {}};
    const button = {name: 'save_mode', value: 'submitted', disabled: false};
    const boxes = [];
    let handler, sent, redirected;
    const form = {
        action: '/cwis/accountability-data', elements: [file],
        querySelectorAll(selector) { return selector === 'button[type="submit"]' ? [button] : []; },
        addEventListener(event, callback) { handler = callback; },
        prepend(box) { boxes.push(box); }
    };
    const document = {
        addEventListener(event, callback) { callback(); },
        querySelectorAll() { return [form]; },
        createElement() { return {dataset: {}, children: [], setAttribute() {}, scrollIntoView() {},
            appendChild(child) { this.children.push(child); }}; }
    };
    class FormData {
        constructor(input) { this.file = input.elements[0].files[0]; this.values = {}; }
        set(name, value) { this.values[name] = value; }
    }
    vm.runInNewContext(source, {document, FormData,
        fetch: async (url, options) => { sent = options; return response; },
        window: {location: {assign(url) { redirected = url; }}}});
    return {file, button, boxes, submit: () => handler({preventDefault() {}, submitter: button}),
        sent: () => sent, redirected: () => redirected};
}

test('422 validation leaves the file selection intact and renders Laravel field errors', async () => {
    const ui = setup({status: 422, ok: false,
        json: async () => ({errors: {'evidence.question': ['Evidence is required.']}})});
    await ui.submit();
    assert.equal(ui.file.files[0], 'evidence.pdf');
    assert.equal(ui.sent().body.values.save_mode, 'submitted');
    assert.equal(ui.sent().body.file, 'evidence.pdf');
    assert.equal(ui.boxes[0].className, 'alert alert-danger');
    assert.equal(ui.boxes[0].children[1].children[0].textContent, 'Evidence is required.');
    assert.equal(ui.button.disabled, false);
    assert.equal(ui.redirected(), undefined);
});

test('successful submission navigates once to the server-selected listing', async () => {
    const ui = setup({status: 200, ok: true, json: async () => ({redirect: '/cwis/generator-data'})});
    await ui.submit();
    assert.equal(ui.redirected(), '/cwis/generator-data');
    assert.equal(ui.boxes.length, 0);
});

test('network failure leaves uploads selected and re-enables submission', async () => {
    const ui = setup({json: async () => { throw new Error('Network failed'); }});
    await ui.submit();
    assert.equal(ui.file.files[0], 'evidence.pdf');
    assert.equal(ui.button.disabled, false);
    assert.equal(ui.redirected(), undefined);
});
