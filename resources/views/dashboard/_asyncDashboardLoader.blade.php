<div id="dashboard-status" class="alert alert-info d-flex align-items-center" role="status" aria-live="polite">
    <span class="fas fa-spinner fa-spin mr-2" aria-hidden="true"></span>
    <span>{{ __('Loading dashboard data...') }}</span>
</div>

<div id="dashboard-content" aria-busy="true"></div>

<noscript>
    <div class="alert alert-warning">{{ __('JavaScript is required to load this dashboard.') }}</div>
</noscript>

@push('scripts')
<script>
(function () {
    'use strict';

    var status = document.getElementById('dashboard-status');
    var target = document.getElementById('dashboard-content');
    var endpoint = @json($dashboardContentEndpoint);

    function executeScripts(container) {
        var originalDocumentAddEventListener = document.addEventListener;

        document.addEventListener = function (type, listener, options) {
            if (type === 'DOMContentLoaded' && document.readyState !== 'loading') {
                window.setTimeout(function () {
                    listener.call(document, new Event('DOMContentLoaded'));
                }, 0);
                return;
            }

            return originalDocumentAddEventListener.call(document, type, listener, options);
        };

        try {
            Array.prototype.slice.call(container.querySelectorAll('script')).forEach(function (oldScript) {
                var script = document.createElement('script');
                Array.prototype.slice.call(oldScript.attributes).forEach(function (attribute) {
                    script.setAttribute(attribute.name, attribute.value);
                });
                script.text = oldScript.text;
                oldScript.parentNode.replaceChild(script, oldScript);
            });
        } finally {
            document.addEventListener = originalDocumentAddEventListener;
        }
    }

    function showError(message) {
        status.className = 'alert alert-danger';
        status.innerHTML = '';

        var text = document.createElement('span');
        text.textContent = message;
        status.appendChild(text);

        var retry = document.createElement('button');
        retry.type = 'button';
        retry.className = 'btn btn-sm btn-outline-danger ml-3';
        retry.textContent = @json(__('Retry'));
        retry.addEventListener('click', loadDashboard);
        status.appendChild(retry);
    }

    function loadDashboard() {
        status.className = 'alert alert-info d-flex align-items-center';
        status.innerHTML = '<span class="fas fa-spinner fa-spin mr-2" aria-hidden="true"></span><span>' +
            @json(__('Loading dashboard data...')) + '</span>';
        target.setAttribute('aria-busy', 'true');

        fetch(endpoint, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (response) {
            if (response.status === 401 || response.status === 419) {
                window.location.href = @json(route('login.show'));
                throw new Error('SESSION_EXPIRED');
            }
            if (!response.ok) {
                throw new Error('HTTP_' + response.status);
            }
            return response.json();
        }).then(function (payload) {
            if (!payload || typeof payload.html !== 'string') {
                throw new Error('INVALID_DASHBOARD_RESPONSE');
            }

            target.innerHTML = payload.html;
            executeScripts(target);
            target.setAttribute('aria-busy', 'false');
            status.className = 'd-none';
        }).catch(function (error) {
            if (error.message !== 'SESSION_EXPIRED') {
                target.setAttribute('aria-busy', 'false');
                showError(@json(__('The dashboard could not be loaded. Please try again.')));
            }
        });
    }

    loadDashboard();
}());
</script>
@endpush
