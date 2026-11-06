<!-- Last Modified Date: 18-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   --><!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title') &mdash; IMIS - Base</title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link type="image/vnd.microsoft.icon" rel="shortcut icon" href="favicon.ico" />
    <link rel="stylesheet" href="{{ asset('/css/ol.css') }}" type="text/css">
    <link href="https://code.ionicframework.com/ionicons/2.0.0/css/ionicons.min.css" rel="stylesheet" type="text/css" />
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->

    {{--ol-ext--}}
  <!-- ol-ext -->
    <link rel="stylesheet" href="https://cdn.rawgit.com/Viglino/ol-ext/master/dist/ol-ext.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol/ol.css" type="text/css">

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/maps.css') }}?v={{ file_exists(public_path('css/maps.css')) ? filemtime(public_path('css/maps.css')) : time() }}">
  </head>
  <body class="hold-transition skin-blue sidebar-mini sidebar-collapse layout-fixed">
    <!-- Site wrapper -->
    <div class="wrapper">

      @include('includes/header')

      <!-- Left side column. contains the sidebar -->
      @include('includes/sidebar')
      <!-- =============================================== -->
        @yield('content')
    </div><!-- ./wrapper -->
        <aside class="control-sidebar control-sidebar-dark" >
          <div class="p-3">
          <h5>{{ Auth::user()->name }}</h5>
          <hr class="mb-2">
          <div class="mb-4">
                      <p>
                          {{implode(', ', get_current_user_roles())}}<br>
                          <small>Added at {{ Carbon\Carbon::parse(Auth::user()->created_at)->format('d F Y') }} </small>
                      </p>
                      <hr/>
                  <div class="row">
                  <div class="col-sm-6">
                    {{--@if(Auth::user()->id != 1)
                        <a href="{{ route('users.show', ['user' => Auth::user()->id]) }}" class="btn btn-block btn-dark">Profile</a>
                        @endif--}}
                    </div>
                  <div class="col-sm-6"><a href="{{ route('logout.perform') }}" class="btn btn-block btn-dark" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a></div>  
                  <form id="logout-form" action="{{ route('logout.perform') }}" method="POST" style="display: none;">
                        {{ csrf_field() }}
                    </form>
                  </div>
                                
            </div>
            </div>
      </aside>
 
    <!-- REQUIRED JS SCRIPTS -->
    <script async defer src="https://maps.google.com/maps/api/js?key={{ Config::get('constants.API_KEY_GOOGLE') }}"></script>
    
    
    <script src="{{ asset('/js/ol.js') }}" type="text/javascript"></script>
    <script src="{{asset('js/app.js')}}"></script>
    <script type="text/javascript" src="{{ asset ('/js/map_layout.js') }}"></script>
    <!--<script src="{{ asset('/old/js/Chart.min.js') }}"></script>-->
    <script type="text/javascript" src="{{ asset ('/js/ol-ext.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script type="text/javascript">
      $(document).ready(function() {
        
         $('body').Layout('fixLayoutHeight')
          $(document).on('collapsed.lte.pushmenu', handleExpandedEvent);
          function handleExpandedEvent(){
            $('.logo').css('display', 'block');
          }
           $(document).on('shown.lte.pushmenu', handleCollapsedEvent);
          function handleCollapsedEvent(){
              $('.logo').css('display', 'none');
          }

          const position = { x: 0, y: 0 }

          interact('.draggable').draggable({
            listeners: {
              start (event) {
              },
              move (event) {
                position.x += event.dx
                position.y += event.dy

                event.target.style.transform =
                  `translate(${position.x}px, ${position.y}px)`
              },
            }
          });

      });
    </script>
    <script>
     $(document).ready(function() {
        var sidebarOpen = false;
        $('.nav-link[data-widget="control-sidebar"]').click(function(e) {
          e.preventDefault(); 
          var $controlSidebar = $('.control-sidebar');
          if (sidebarOpen) {
            $controlSidebar.css('transform', 'translateX(100%)');
          } else {
            $controlSidebar.css('transform', 'translateX(0)');
          }
          sidebarOpen = !sidebarOpen;
        });
      });

    </script>

    @stack('scripts')
    </body>
</html>
