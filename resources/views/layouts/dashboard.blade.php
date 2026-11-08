<!-- Last Modified Date: 18-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
<!DOCTYPE html>
<html lang="en" style="height: auto;">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{csrf_token()}}">
    <title>@yield('title') &mdash; {{config('app.name')}}</title>

    <!-- Theme style -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="{{asset('css/app.css')}}">
    <link rel="stylesheet" href="{{asset('css/style.css')}}">
</head>

<body class="sidebar-mini sidebar-collapse layout-fixed layout-navbar-fixed layout-footer-fixed" style="height: auto;">
    <div class="wrapper">
        <!-- Header -->
        @include('includes.header')
        <!-- Sidebar -->
        @include('includes.sidebar')
        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 style="font-family: 'Roboto', sans-serif;">@yield('title', 'Page Title')</h1>
                        </div>
                    </div>
                </div><!-- /.container-fluid -->
            </section>
            <section class="content">
                <!-- Loader/Spinner for ajax requests -->
                <div class="loading-overlay">
                    <span class="fas fa-spinner fa-3x fa-spin"></span>
                    <span id="loading-content"></span>
                </div>

                <div class="container-fluid">
                    <div class="row">

                        <div class="col-12">
                            @yield('content')

                        </div>
                        <!--col -->
                    </div>
                    <!--row -->
                </div>
                <!--container-fluid-->
            </section>
        </div>
    </div>
    <!-- Footer -->
    @include('includes.footer')

    <script src="{{asset('js/app.js')}}"></script>
    <script>
    $(document).ready(function() {
        @if(Route::current() -> getName() != 'cwis-df-mne.index' && Request()->route()->uri() != 'cwis/cwis-df-mne/newsurvey')
        $('[data-toggle="collapse"]').click(function() {
            $(this).toggleClass("active");
            switch ($(this).attr('data-target')) {
                case "#collapseFilterPdf":

                    break;
                case "#kml-previewer":

                    break;
                default:
                    if ($(this).hasClass("active")) {
                        $(this).text("{{ __('Hide Filter') }}");
                    } else {
                        $(this).text("{{ __('Show Filter') }}");
                    }
                    break;
            }

        });

        @endif
        $('#create_user').on('click', function() {
            createUser();
        })
    });
    </script>
    <script src="{{ asset('js/functions.js') }}"></script>
    <script>
    $(function() {
        bsCustomFileInput.init();
    });

     function loadNotifications() {
    fetch("/notifications")
        .then(response => response.json())
        .then(data => {
            let notificationIcon = document.getElementById('bell');
            let notificationList = document.getElementById('notification-list');
            let badge = document.getElementById('badge');

            notificationList.innerHTML = '';

            // 🔔 Badge shows unread count
            if (data.unreadCount > 0) {
                badge.textContent = data.unreadCount;
                badge.style.display = 'inline';
            } else {
                badge.style.display = 'none';
            }

            if (data.notifications.length > 0) {
                data.notifications.forEach(item => {
                    const li = document.createElement('li');
                    li.textContent = item.message;
                    li.style.cursor = 'pointer';
                    li.style.padding = '10px';
                    li.style.borderBottom = '1px solid #ddd';

                    // Gray out read notifications
                    if (item.status) {
                        li.style.color = '#888';
                    } else {
                        li.style.fontWeight = 'bold';
                    }

                    li.onclick = () => markAsRead(item.id);
                    notificationList.appendChild(li);
                });
            } else {
                const emptyLi = document.createElement('li');
                emptyLi.id = 'no-notification-text';
                emptyLi.className = 'text-center text-muted py-3';
                emptyLi.innerHTML = `
                    <i class="fa fa-bell-slash fa-2x mb-2" style="color: #ccc;"></i><br>
                    No notifications
                `;
                notificationList.appendChild(emptyLi);
            }
        });
    }


    function markAsRead(id) {
        fetch('/notification/read', {
            method:'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id:id })
        }).then(() => loadNotifications());
    }
    document.getElementById("bell").addEventListener("click", function() {
    fetch('/notification/read-all', {
        method:'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    }).then(() => {
        loadNotifications(); // ✅ refresh list but keep messages
    });
});

// ✅ Clear All notifications
function clearAllNotifications() {
    fetch('/notification/clear-all', {
        method:'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    }).then(() => {
        loadNotifications();
    });}
    setInterval(loadNotifications, 120000);
    document.addEventListener("DOMContentLoaded", loadNotifications);

    </script>
      <script>
    // Global Map Configuration - Loaded once from .env
    window.mapConfig = {
        centerLng: {{ Config::get('constants.MAP_CENTER_LNG', 81.6314) }},
        centerLat: {{ Config::get('constants.MAP_CENTER_LAT', 28.5971) }},
        extentMinLng: {{ Config::get('constants.MAP_EXTENT_MIN_LNG', 81.57) }},
        extentMinLat: {{ Config::get('constants.MAP_EXTENT_MIN_LAT', 28.55) }},
        extentMaxLng: {{ Config::get('constants.MAP_EXTENT_MAX_LNG', 81.70) }},
        extentMaxLat: {{ Config::get('constants.MAP_EXTENT_MAX_LAT', 28.64) }},
        minZoom: {{ Config::get('constants.MAP_MIN_ZOOM', 12.5) }},
        maxZoom: {{ Config::get('constants.MAP_MAX_ZOOM', 19) }}
    };
    
    // Global helper function to create map view
    window.createMapView = function() {
        return new ol.View({
            center: ol.proj.transform(
                [window.mapConfig.centerLng, window.mapConfig.centerLat], 
                'EPSG:4326',
                'EPSG:3857'
            ),
            minZoom: window.mapConfig.minZoom,
            maxZoom: window.mapConfig.maxZoom,
            extent: ol.proj.transformExtent(
                [
                    window.mapConfig.extentMinLng,
                    window.mapConfig.extentMinLat,
                    window.mapConfig.extentMaxLng,
                    window.mapConfig.extentMaxLat
                ],
                'EPSG:4326',
                'EPSG:3857'
            )
        });
    };
    
    // Global helper to update existing map view
    window.updateMapView = function(map) {
        if (!map) return;
        map.getView().setCenter(ol.proj.transform(
            [window.mapConfig.centerLng, window.mapConfig.centerLat], 
            'EPSG:4326',
            'EPSG:3857'
        ));
        map.getView().setMinZoom(window.mapConfig.minZoom);
        map.getView().setMaxZoom(window.mapConfig.maxZoom);
        map.getView().setExtent(ol.proj.transformExtent(
            [
                window.mapConfig.extentMinLng,
                window.mapConfig.extentMinLat,
                window.mapConfig.extentMaxLng,
                window.mapConfig.extentMaxLat
            ],
            'EPSG:4326',
            'EPSG:3857'
        ));
    };
</script>
    @stack('scripts')
    @include('toast-message')

</body>

</html>
