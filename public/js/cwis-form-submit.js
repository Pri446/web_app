(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('#indicator-data-form, .sustainability-form, .responsibility-form, .accountability-form, .rpm-form, .safety-form').forEach(function (form) {
            var busy = false;
            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                if (busy) return;
                var data = new FormData(form);
                if (event.submitter && event.submitter.name) data.set(event.submitter.name, event.submitter.value);
                var buttons = Array.from(form.querySelectorAll('button[type="submit"]'));
                var states = buttons.map(function (button) { return button.disabled; });
                busy = true;
                buttons.forEach(function (button) { button.disabled = true; });
                form.querySelectorAll('[data-cwis-errors]').forEach(function (node) { node.remove(); });
                form.querySelectorAll('.is-invalid').forEach(function (node) { node.classList.remove('is-invalid'); });
                function errors(messages) {
                    var box = document.createElement('div');
                    box.className = 'alert alert-danger';
                    box.dataset.cwisErrors = 'true';
                    box.setAttribute('role', 'alert');
                    var heading = document.createElement('strong');
                    heading.textContent = 'Error!';
                    box.appendChild(heading);
                    var list = document.createElement('ul');
                    Object.keys(messages).forEach(function (key) {
                        var name = key.split('.').reduce(function (value, part, index) { return index ? value + '[' + part + ']' : part; }, '');
                        var control = Array.from(form.elements).find(function (element) { return element.name === name; });
                        messages[key].forEach(function (message) {
                            var item = document.createElement('li');
                            item.textContent = message;
                            list.appendChild(item);
                            if (control) {
                                control.classList.add('is-invalid');
                                var feedback = document.createElement('div');
                                feedback.className = 'invalid-feedback d-block';
                                feedback.dataset.cwisErrors = 'true';
                                feedback.textContent = message;
                                control.insertAdjacentElement('afterend', feedback);
                            }
                        });
                    });
                    box.appendChild(list);
                    form.prepend(box);
                    box.scrollIntoView({behavior: 'smooth', block: 'start'});
                }
                try {
                    var response = await fetch(form.action, {method: 'POST', body: data, credentials: 'same-origin',
                        headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
                    var result = await response.json();
                    if (response.status === 422) { errors(result.errors || {form: [result.message]}); return; }
                    if (!response.ok || !result.redirect) throw new Error('Unable to save. Please try again. Your selected files have been retained.');
                    window.location.assign(result.redirect);
                } catch (error) {
                    errors({form: ['Unable to save. Please try again. Your selected files have been retained.']});
                } finally {
                    busy = false;
                    buttons.forEach(function (button, index) { button.disabled = states[index]; });
                }
            });
        });
    });
}());
