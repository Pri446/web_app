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
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
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
    <script src="{{ asset('js/functions.js') }}?v={{ filemtime(public_path('js/functions.js')) }}"></script>
    <script>
    $(function() {
        bsCustomFileInput.init();
    });
    </script>
    @stack('scripts')
    @include('toast-message')

</body>

</html>
