<?php
use App\Helpers\LanguageSwitcher;
use App\Services\PiiKeyBackupService;
use App\Services\PiiAccessService;
use App\Services\ApplicationPiiAccessService;

$piiKeyBackupStatus = Auth::check()
    ? app(PiiKeyBackupService::class)->statusFor(Auth::user())
    : null;

$piiKeyBackupIconClass = 'text-success';
$piiKeyBackupTitle = __('PII key backup is current.');

if ($piiKeyBackupStatus) {
    if ($piiKeyBackupStatus['state'] === 'due_soon') {
        $piiKeyBackupIconClass = 'text-warning';
        $piiKeyBackupTitle = __('PII key backup is due soon.');
    } elseif (in_array($piiKeyBackupStatus['state'], ['never', 'overdue', 'unavailable'], true)) {
        $piiKeyBackupIconClass = 'text-danger';
        $piiKeyBackupTitle = $piiKeyBackupStatus['state'] === 'unavailable'
            ? __('PII key backup tracking is unavailable.')
            : __('PII key backup is required.');
    }
}

$isMapPage = request()->is('maps*');
$mapPiiUser = Auth::user();
$mapPiiAccess = $isMapPage && $mapPiiUser ? app(PiiAccessService::class) : null;
$mapAppPiiAccess = $isMapPage && $mapPiiUser ? app(ApplicationPiiAccessService::class) : null;
$canUnlockMapPii = $isMapPage && $mapPiiUser && (
    ($mapPiiAccess && $mapPiiAccess->canUnlock($mapPiiUser)) ||
    ($mapAppPiiAccess && $mapAppPiiAccess->canUnlock($mapPiiUser))
);
$mapPiiUnlocked = $isMapPage && $mapPiiUser && (
    ($mapPiiAccess && $mapPiiAccess->isUnlocked($mapPiiUser, 'view')) ||
    ($mapAppPiiAccess && $mapAppPiiAccess->isUnlocked($mapPiiUser, 'view'))
);
$mapPiiSecondsRemaining = $mapPiiUnlocked ? max(
    $mapPiiAccess ? $mapPiiAccess->secondsRemaining($mapPiiUser, 'view') : 0,
    $mapAppPiiAccess ? $mapAppPiiAccess->secondsRemaining($mapPiiUser, 'view') : 0
) : 0;
?>
<nav class="main-header navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        @if (request()->is('maps'))
            <a href="{{ url('/') }}" class="" >
                <span class=""> 
                    <img src="{{ asset('/img/logo-imis.png') }}" alt="Municipality Logo" id="map-logo"
                        style="line-height: .8; margin-right: 0.5rem; margin-top:8px; max-height:33px; width:70px">
                </span>
            </a>
        @endif

        @if (request()->is('maps'))
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button" onclick="hideImage()">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
        @else
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button" onclick="toggleElements()">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
        @endif
              
        <!-- Language Dropdown -->
        {!! LanguageSwitcher::language_switcher() !!}
        <!-- This div is used for aligning the user name and roles to the right -->
        <div style="flex-grow: 1;"></div> <!-- This pushes content to the right -->

        @if ($piiKeyBackupStatus)
            <li class="nav-item">
                <button id="pii-key-backup-indicator" type="button"
                    class="nav-link btn btn-link {{ $piiKeyBackupIconClass }}"
                    data-toggle="modal" data-target="#pii-key-backup-modal"
                    title="{{ $piiKeyBackupTitle }}" aria-label="{{ $piiKeyBackupTitle }}">
                    <i class="fas fa-key" aria-hidden="true"></i>
                    @if (in_array($piiKeyBackupStatus['state'], ['never', 'overdue', 'due_soon', 'unavailable'], true))
                        <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                    @endif
                </button>
            </li>
        @endif

        @if ($isMapPage && ($canUnlockMapPii || $mapPiiUnlocked))
            <li class="nav-item" id="map-pii-nav-item">
                @if ($mapPiiUnlocked)
                    <form id="map-pii-lock-form" method="POST" action="{{ route('maps.pii.lock') }}" class="d-inline m-0 p-0">
                        @csrf
                        <button id="map-pii-toggle-btn" type="submit"
                            class="nav-link btn btn-link text-success"
                            title="{{ __('Owner information is unlocked for map. Click to lock.') }}"
                            aria-label="{{ __('Owner information is unlocked for map. Click to lock.') }}"
                            data-unlocked="true"
                            data-seconds-remaining="{{ $mapPiiSecondsRemaining }}">
                            <i class="fas fa-lock-open fa-lg" aria-hidden="true" style="pointer-events: none;"></i>
                        </button>
                    </form>
                @else
                    <button id="map-pii-toggle-btn" type="button"
                        class="nav-link btn btn-link text-secondary"
                        title="{{ __('Unlock Owner Information') }}"
                        aria-label="{{ __('Unlock Owner Information') }}"
                        data-unlocked="false"
                        data-toggle="modal" data-target="#map-pii-unlock-modal">
                        <i class="fas fa-lock fa-lg" aria-hidden="true" style="pointer-events: none;"></i>
                    </button>
                @endif
            </li>
        @endif

       <li class="nav-item ml-auto dropdown position-relative">
        <a class="nav-link" href="#" id="bell" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa-regular fa-bell fa-lg"></i>
            <span id="badge" style="position:absolute;top:5px;right:5px;background:#fd0808;color:white;font-size:10px;font-weight:bold;padding:2px 6px;border-radius:50%;display:none;line-height:1;">!</span>
        </a>

          <div class="dropdown-menu dropdown-menu-right p-2 shadow-lg" aria-labelledby="bell" 
            style="width: 300px; max-height: 400px; overflow-y: auto;">

            <div class="d-flex justify-content-between align-items-center px-2 mb-2">
                <strong>Notifications</strong>
                <a href="#" onclick="clearAllNotifications()" class="text-danger small">Clear All</a>
            </div>

            <ul id="notification-list" class="list-unstyled mb-0"></ul>
        </div>
       </li>
        
        <!-- Display the user's name and roles on the right side -->
        <div style="display: flex; justify-content: flex-end; margin-top: 0.5%;">
            <small>{{__('Hi')}},{{ Auth::user()->name }}, {{ implode(', ', get_current_user_roles()) }}</small>
        </div>

        <li class="nav-item ml-auto">
            <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
                <i class="fas fa-th-large"></i>
            </a>
        </li>
    </ul>
</nav>

@if ($piiKeyBackupStatus)
    <div class="modal fade" id="pii-key-backup-modal" tabindex="-1" role="dialog"
        aria-labelledby="pii-key-backup-title" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="pii-key-backup-form" method="POST"
                    action="{{ route('pii-key-backup.download') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="pii-key-backup-title">
                            {{ __('Back Up PII Encryption Key') }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal"
                            aria-label="{{ __('Close') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        @if ($piiKeyBackupStatus['state'] === 'unavailable')
                            <div class="alert alert-danger">
                                {{ __('Backup tracking is unavailable. Apply the PII key-backup migration before using this feature.') }}
                            </div>
                        @elseif ($piiKeyBackupStatus['state'] === 'never')
                            <div class="alert alert-danger">
                                {{ __('No backup has been recorded for the active PII key.') }}
                            </div>
                        @elseif ($piiKeyBackupStatus['state'] === 'overdue')
                            <div class="alert alert-danger">
                                {{ __('The PII key backup is overdue.') }}
                            </div>
                        @elseif ($piiKeyBackupStatus['state'] === 'due_soon')
                            <div class="alert alert-warning">
                                {{ trans_choice(
                                    'The PII key backup is due in :count day.|The PII key backup is due in :count days.',
                                    $piiKeyBackupStatus['days_remaining'],
                                    ['count' => $piiKeyBackupStatus['days_remaining']]
                                ) }}
                            </div>
                        @else
                            <div class="alert alert-success">
                                {{ trans_choice(
                                    'The next PII key backup is due in :count day.|The next PII key backup is due in :count days.',
                                    $piiKeyBackupStatus['days_remaining'],
                                    ['count' => $piiKeyBackupStatus['days_remaining']]
                                ) }}
                            </div>
                        @endif

                        <dl class="row mb-3">
                            <dt class="col-sm-5">{{ __('Active key version') }}</dt>
                            <dd class="col-sm-7">{{ $piiKeyBackupStatus['key_version'] }}</dd>
                            <dt class="col-sm-5">{{ __('Backup interval') }}</dt>
                            <dd class="col-sm-7">
                                {{ $piiKeyBackupStatus['interval_days'] }} {{ __('days') }}
                            </dd>
                            <dt class="col-sm-5">{{ __('Last backup download') }}</dt>
                            <dd class="col-sm-7">
                                @if ($piiKeyBackupStatus['last_downloaded_at'])
                                    {{ $piiKeyBackupStatus['last_downloaded_at']->format('Y-m-d H:i') }}
                                    @if ($piiKeyBackupStatus['last_downloaded_by'])
                                        — {{ $piiKeyBackupStatus['last_downloaded_by'] }}
                                    @endif
                                @else
                                    {{ __('Never') }}
                                @endif
                            </dd>
                        </dl>

                        <div class="alert alert-warning">
                            <strong>{{ __('Security warning:') }}</strong>
                            {{ __('The downloaded file contains the directly usable Base64 PII key. Base64 is not encryption. Move it immediately to approved encrypted offline storage, remove it from Downloads, and never send or commit it.') }}
                        </div>

                        <div class="form-group">
                            <label for="pii_key_backup_password">{{ __('Current Password') }}</label>
                            <input id="pii_key_backup_password" name="current_password" type="password"
                                class="form-control" required maxlength="255"
                                autocomplete="current-password">
                            <small class="form-text text-muted">
                                {{ __('Your password is verified but never stored with the backup.') }}
                            </small>
                        </div>

                        <div class="custom-control custom-checkbox">
                            <input id="acknowledge_raw_key_risk" name="acknowledge_raw_key_risk"
                                type="checkbox" value="1" class="custom-control-input" required>
                            <label class="custom-control-label" for="acknowledge_raw_key_risk">
                                {{ __('I understand this is the raw encryption key and will store it securely.') }}
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            {{ __('Cancel') }}
                        </button>
                        <button id="pii-key-backup-submit" type="submit" class="btn btn-danger" disabled
                            @if ($piiKeyBackupStatus['state'] === 'unavailable') disabled @endif>
                            {{ __('Download Key Backup') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var form = $('#pii-key-backup-form');
            var submitButton = $('#pii-key-backup-submit');
            var trackingAvailable = @json($piiKeyBackupStatus['state'] !== 'unavailable');

            function updatePiiKeyBackupButton() {
                var ready = trackingAvailable
                    && $('#pii_key_backup_password').val().length > 0
                    && $('#acknowledge_raw_key_risk').is(':checked');
                var submitting = form.data('submitting') === true;

                // Always pass a real Boolean. Passing undefined makes jQuery
                // read the disabled property instead of updating it, which
                // previously left the button disabled even when the form was
                // complete.
                submitButton.prop('disabled', !ready || submitting);
            }

            $('#pii_key_backup_password, #acknowledge_raw_key_risk').on(
                'input change',
                updatePiiKeyBackupButton
            );

            form.on('submit', function(event) {
                event.preventDefault();

                if (submitButton.prop('disabled') || form.data('submitting')) {
                    return;
                }

                form.data('submitting', true);
                submitButton.prop('disabled', true).text("{{ __('Preparing...') }}");

                // FormData keeps the value required by this one request. Clear
                // the visible input immediately so the password is not left in
                // the page DOM while the key backup is being generated.
                var requestBody = new FormData(form[0]);
                $('#pii_key_backup_password').val('');

                fetch(form.attr('action'), {
                    method: 'POST',
                    body: requestBody,
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json, application/octet-stream',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(function(response) {
                    if (!response.ok) {
                        return response.json().catch(function() {
                            return { message: "{{ __('The PII key backup could not be generated.') }}" };
                        }).then(function(error) {
                            var message = error.message;

                            if (error.errors) {
                                var keys = Object.keys(error.errors);
                                if (keys.length && error.errors[keys[0]].length) {
                                    message = error.errors[keys[0]][0];
                                }
                            }

                            throw new Error(message ||
                                "{{ __('The PII key backup could not be generated.') }}");
                        });
                    }

                    var disposition = response.headers.get('Content-Disposition') || '';
                    var match = disposition.match(/filename\*?=(?:UTF-8''|["'])?([^"';]+)/i);
                    var filename = match
                        ? decodeURIComponent(match[1].replace(/["']/g, ''))
                        : 'pii-key-backup.key';

                    return response.blob().then(function(blob) {
                        return { blob: blob, filename: filename };
                    });
                }).then(function(download) {
                    var downloadUrl = window.URL.createObjectURL(download.blob);
                    var link = document.createElement('a');

                    link.href = downloadUrl;
                    link.download = download.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.setTimeout(function() {
                        window.URL.revokeObjectURL(downloadUrl);
                    }, 1000);

                    $('#pii-key-backup-modal').modal('hide');
                    form[0].reset();
                    form.data('submitting', false);
                    submitButton.text("{{ __('Download Key Backup') }}");
                    updatePiiKeyBackupButton();

                    Swal.fire({
                        title: "{{ __('PII key backup downloaded') }}",
                        text: "{{ __('Move the key file from Downloads to approved encrypted offline storage immediately.') }}",
                        icon: 'success',
                        confirmButtonColor: '#3085d6'
                    }).then(function() {
                        window.location.reload();
                    });
                }).catch(function(error) {
                    form.data('submitting', false);
                    submitButton.text("{{ __('Download Key Backup') }}");
                    updatePiiKeyBackupButton();

                    Swal.fire({
                        title: "{{ __('Backup failed') }}",
                        text: error.message,
                        icon: 'error',
                        confirmButtonColor: '#3085d6'
                    });
                });
            });

            @if (in_array($piiKeyBackupStatus['state'], ['never', 'overdue', 'due_soon'], true))
                // Show one automatic reminder per browser-tab session. The
                // persistent header icon remains visible on every page.
                try {
                    var reminderKey = 'pii-key-backup-reminder-'
                        + @json($piiKeyBackupStatus['state']) + '-'
                        + @json(optional($piiKeyBackupStatus['due_at'])->toIso8601String());

                    if (!window.sessionStorage.getItem(reminderKey)) {
                        window.sessionStorage.setItem(reminderKey, 'shown');
                        $('#pii-key-backup-modal').modal('show');
                    }
                } catch (error) {
                    // Storage may be unavailable in private/restricted browser
                    // modes. The header icon still provides the reminder.
                }
            @endif
        });
    </script>
@endif

@if ($isMapPage && ($canUnlockMapPii || $mapPiiUnlocked))
    <div class="modal fade" id="map-pii-unlock-modal" tabindex="-1" role="dialog"
        aria-labelledby="map-pii-unlock-title" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="map-pii-unlock-form" method="POST" action="{{ route('maps.pii.unlock') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="map-pii-unlock-title">
                            {{ __('Reveal Owner Information on Map') }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>
                            {{ __('This will reveal owner and customer names in map feature popups and map CSV/Excel exports.') }}
                        </p>
                        <p class="mb-0">
                            {{ __('Access lasts five minutes and will be recorded in the security audit log.') }}
                        </p>
                        <div class="form-group mt-3 mb-0">
                            <label for="map_pii_current_password">
                                {{ __('Current Password') }}
                            </label>
                            <input id="map_pii_current_password" name="current_password" type="password"
                                class="form-control"
                                maxlength="255" autocomplete="current-password"
                                aria-describedby="map-pii-current-password-help" required>
                            <small id="map-pii-current-password-help" class="form-text text-muted">
                                {{ __('Re-enter your login password to confirm access. The password is not stored.') }}
                            </small>
                            <div id="map-pii-error-feedback" class="invalid-feedback d-none"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            {{ __('Cancel') }}
                        </button>
                        <button id="map-pii-unlock-submit" type="submit" class="btn btn-info">
                            {{ __('Reveal for 5 Minutes') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        window.mapPiiUnlocked = {{ $mapPiiUnlocked ? 'true' : 'false' }};
        window.decryptedPiiCache = window.decryptedPiiCache || {};

        function handleMapPiiClick(btn, e) {
            var isUnlocked = btn.getAttribute('data-unlocked') === 'true';
            if (isUnlocked) {
                if (window.lockMapPii) {
                    if (e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    window.lockMapPii();
                }
            } else {
                if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
                    $('#map-pii-error-feedback').addClass('d-none').text('');
                    $('#map_pii_current_password').removeClass('is-invalid').val('');
                    $('#map-pii-unlock-modal').modal('show');
                }
            }
        }
    </script>

    @push('scripts')
    <script>
        (function() {
            var mapPiiCountdownTimer = null;

            // Move modal to body so it is not trapped or clipped by nav/wrapper
            if ($('#map-pii-unlock-modal').length) {
                $('#map-pii-unlock-modal').appendTo('body');
            }

            window.setMapPiiState = function(isUnlocked, secondsRemaining) {
                window.mapPiiUnlocked = !!isUnlocked;
                var $btn = $('#map-pii-toggle-btn');
                if ($btn.length) {
                    $btn.attr('data-unlocked', isUnlocked ? 'true' : 'false');
                    $btn.attr('data-seconds-remaining', secondsRemaining || 0);
                    if (isUnlocked) {
                        $btn.removeClass('text-secondary').addClass('text-success');
                        $btn.removeAttr('data-toggle').removeAttr('data-target');
                        $btn.attr('title', "{{ __('Owner information is unlocked for map. Click to lock.') }}");
                        $btn.attr('aria-label', "{{ __('Owner information is unlocked for map. Click to lock.') }}");
                        $btn.html('<i class="fas fa-lock-open fa-lg" aria-hidden="true" style="pointer-events: none;"></i>');
                    } else {
                        $btn.removeClass('text-success').addClass('text-secondary');
                        $btn.attr('data-toggle', 'modal').attr('data-target', '#map-pii-unlock-modal');
                        $btn.attr('title', "{{ __('Unlock Owner Information') }}");
                        $btn.attr('aria-label', "{{ __('Unlock Owner Information') }}");
                        $btn.html('<i class="fas fa-lock fa-lg" aria-hidden="true" style="pointer-events: none;"></i>');
                    }
                }
                if (mapPiiCountdownTimer) {
                    clearTimeout(mapPiiCountdownTimer);
                    mapPiiCountdownTimer = null;
                }
                if (isUnlocked && secondsRemaining > 0) {
                    mapPiiCountdownTimer = setTimeout(function() {
                        window.setMapPiiState(false, 0);
                        window.decryptedPiiCache = {};
                        $(document).trigger('mapPiiStateChanged', [false]);
                        window.location.reload();
                    }, secondsRemaining * 1000);
                }
            };

            window.lockMapPii = function() {
                var token = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val();
                var $btn = $('#map-pii-toggle-btn');
                $btn.prop('disabled', true);

                $.ajax({
                    url: "{{ route('maps.pii.lock') }}",
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    data: {
                        _token: token
                    },
                    success: function(res) {
                        window.setMapPiiState(false, 0);
                        window.decryptedPiiCache = {};
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: "{{ __('Locked') }}",
                                text: (res && res.message) ? res.message : "{{ __('Owner information has been locked.') }}",
                                icon: 'info',
                                timer: 1200,
                                showConfirmButton: false
                            }).then(function() {
                                window.location.reload();
                            });
                        } else {
                            window.location.reload();
                        }
                    },
                    error: function() {
                        window.location.reload();
                    }
                });
            };

            $(document).on('submit', '#map-pii-lock-form', function(e) {
                e.preventDefault();
                window.lockMapPii();
            });

            $(document).on('click', '#map-pii-toggle-btn', function(e) {
                var isUnlocked = $(this).attr('data-unlocked') === 'true';
                if (isUnlocked) {
                    e.preventDefault();
                    e.stopPropagation();
                    window.lockMapPii();
                } else {
                    $('#map-pii-error-feedback').addClass('d-none').text('');
                    $('#map_pii_current_password').removeClass('is-invalid').val('');
                    $('#map-pii-unlock-modal').modal('show');
                }
            });

            $(document).on('submit', '#map-pii-unlock-form', function(e) {
                e.preventDefault();
                var $submit = $('#map-pii-unlock-submit');
                var password = $('#map_pii_current_password').val();
                if (!password) {
                    $('#map_pii_current_password').addClass('is-invalid');
                    return;
                }
                $submit.prop('disabled', true).text("{{ __('Verifying...') }}");
                $('#map-pii-error-feedback').addClass('d-none').text('');
                $('#map_pii_current_password').removeClass('is-invalid');

                var token = $('meta[name="csrf-token"]').attr('content') || $('#map-pii-unlock-form input[name="_token"]').val();

                $.ajax({
                    url: "{{ route('maps.pii.unlock') }}",
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    data: {
                        _token: token,
                        current_password: password
                    },
                    success: function(res) {
                        $submit.prop('disabled', false).text("{{ __('Reveal for 5 Minutes') }}");
                        $('#map-pii-unlock-modal').modal('hide');
                        $('#map_pii_current_password').val('');
                        var sec = (res && res.seconds_remaining) ? res.seconds_remaining : 300;
                        window.setMapPiiState(true, sec);
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: "{{ __('Success') }}",
                                text: (res && res.message) ? res.message : "{{ __('Owner information is revealed on the map for five minutes.') }}",
                                icon: 'success',
                                timer: 1200,
                                showConfirmButton: false
                            }).then(function() {
                                window.location.reload();
                            });
                        } else {
                            window.location.reload();
                        }
                    },
                    error: function(xhr) {
                        $submit.prop('disabled', false).text("{{ __('Reveal for 5 Minutes') }}");
                        var msg = "{{ __('Password confirmation failed.') }}";
                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            } else if (xhr.responseJSON.errors && xhr.responseJSON.errors.current_password) {
                                msg = xhr.responseJSON.errors.current_password[0];
                            }
                        }
                        $('#map_pii_current_password').addClass('is-invalid');
                        $('#map-pii-error-feedback').removeClass('d-none').text(msg);
                    }
                });
            });

            $(document).ready(function() {
                window.setMapPiiState({{ $mapPiiUnlocked ? 'true' : 'false' }}, {{ $mapPiiSecondsRemaining }});
                @if ($errors->has('map_pii_password'))
                    $('#map-pii-error-feedback').removeClass('d-none').text("{{ $errors->first('map_pii_password') }}");
                    $('#map_pii_current_password').addClass('is-invalid');
                    $('#map-pii-unlock-modal').modal('show');
                @endif
            });
        })();
    </script>
    @endpush
@endif

<script>


    function hideImage() {
        var logo = document.getElementById('map-logo');
        if (logo.style.display === 'none') {
            logo.style.display = 'inline';
        } else {
            logo.style.display = 'none';
            helloText.style.display = 'inline';
        }
    }
</script>
