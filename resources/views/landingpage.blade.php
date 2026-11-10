<!-- Last Modified Date: 18-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />

    <title>IMIS-Homepage</title>
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Roboto:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i"
        rel="stylesheet" />

    <!-- Vendor CSS Files -->
    <!-- <link href="{{ asset('landingpage/vendor/aos/aos.css') }}" rel="stylesheet" /> -->
    <link rel="stylesheet" href="{{asset('css/app.css')}}">
    <link rel="stylesheet" href="{{asset('css/style.css')}}">

</head>

<body>
    <!-- ======= Top Bar ======= -->
    <section id="topbar" class="d-flex align-items-center">
        <div class="container d-flex justify-content-center justify-content-md-between">
            <div class="contact-info d-flex align-items-center">
                <div class="d-flex p-4">
                    <i class="fas fa-envelope d-flex align-items-center"></i> 
                    <span class="p-2">imis@ait.asia</span>
                </div>

                <div class="d-flex">
                    <i class="fas fa-phone d-flex align-items-center "></i>
                    <span class="p-2"> </span>
                </div>
            </div>
        </div>
    </section>

    <!-- ======= Header ======= -->
    <header id="header" class="d-flex align-items-center">
        <div class="container d-flex align-items-center justify-content-between">
            <a href="{{URL::to('/')}}" class="logo"><img src="{{ asset('img/logo-imis.png') }}" alt="IMIS LOGO" /></a>

            <nav id="navbar" class="navbar-landing">
                <ul>
                    <li><a class="nav-link scrollto active" href="#hero">Home</a></li>
                    <li><a class="nav-link scrollto" href="#about">About</a></li>
                    <li><a class="nav-link scrollto" href="#cwis">CWIS</a></li>
                    <li><a class="nav-link scrollto" href="#features">Features</a></li>
                    <li><a class="nav-link scrollto" href="#services">Functional Modules</a></li>
                    <li><a class="nav-link scrollto" href="#contact">Contact</a></li>
                    <li>
                        <button type="button" class="btn btn-get-started" data-toggle="modal" data-target="#loginModal">
                            LOG In
                        </button>
                    </li>
                </ul>
                <i class="fa fa-bars  mobile-nav-toggle"></i>
            </nav>
            <!--navbar -->
        </div>

    </header>
    <!-- End Header -->

    <!-- ======= Hero Section ======= -->
    <section id="hero" class="d-flex align-items-center">

        <div class="container">
            <div class="row">
                <div class="col-lg-6 pt-4 pt-lg-0 order-2 order-lg-1 d-flex flex-column justify-content-center"
                    data-aos="zoom-out" data-aos-delay="100">
                    <div>
                        <h1>Integrated Municipal Information System</h1>
                    </div>
                    <h2>"Empowering Local Governments to achieve SDG 6.2 through </br> CWIS Approach"</h2>
                </div>
                <div class="col-lg-6 order-1 order-lg-2 hero-img" data-aos="zoom-out" data-aos-delay="100">
                    <img src="{{ asset('/img/imislogo.svg') }}" class="img-fluid" alt="">
                </div>
            </div>
        </div>

    </section><!-- End Hero -->



    <main id="main">
         <!-- ======= About Section ======= -->
         <section id="about" class="about section-bg">
            <div class="container" data-aos="fade-up">
                <div class="section-title">
                    <!--<h2>About</h2>-->
                    <h3>About <span>IMIS</span></h3>
                </div>
               <div class="text-left">
                <p>
                IMIS is an open-source GIS-based Digital Public Infrastructure (DPI) which functions as both a municipal information system and a software solution, integrating data, processes, and services to enhance municipal governance—particularly in sanitation management with Citywide Inclusive Sanitation (CWIS) approach to achieve SDG 6.2. It offers municipalities data-driven decision-making tools to strengthen governance across various sectors. By leveraging open-source technologies and Geographic Information Systems (GIS), it facilitates:
                <ul>
                   <li>Planning, management, and monitoring of sanitation systems using the CWIS approach.</li>
                    <li>End-to-end FSM (Faecal Sludge Management) service chain oversight, including real-time data tracking.</li>
                    <li>Generation and visualization of CWIS indicators for performance assessment.</li>
                    <li>Intuitive dashboards for tracking CWIS indicators, Key Performance Indicators (KPIs), and other essential municipal governance metrics.</li>
                </ul>
                IMIS as a sub-national public data system contributes to national-level monitoring by feeding data into centralized systems, supporting CWIS indicators and other critical metrics for achieving sanitation targets.
                Beyond sanitation management, with its modular and scalable design, Base IMIS empowers local authorities by providing a unified, data-driven framework that enhances efficiency, accountability, and service delivery in municipal governance.
                </p>
                </div>
            </div>
        </section>
        <!-- End About Section -->

          <!-- ======= About Section ======= -->
          <section id="cwis" class="about">
            <div class="container" data-aos="fade-up">
                <div class="section-title">
                    <!--<h2>About</h2>-->
                    <h3>Citywide Inclusive Sanitation <span>(CWIS)</span></h3>
                </div>
                <div class="text-left">
                <p>
                CWIS is an approach to achieve SDG 6.2 for safe, equitable and financially viable sanitation systems and services. CWIS ensures everyone in a city has access to safely managed sanitation, and human waste is safely managed along the whole sanitation service chain ensuring protection of the environment and human health.
                </p>
                <img src="{{ asset('img/svg/landing-page/cwis.jpg') }}" class="cwis-img" alt="CWIS">
                <p>CWIS approach focuses on service provision and its enabling environment rather than on building infrastructure, therefore, reliable data is the key success factor for CWIS. UN Water SDG 6 global acceleration framework has also identified data and information as one of the five accelerators of SDG 6 outcomes.</p>
                </div>
            </div>
        </section>
        <!-- End About Section -->

        <!-- ======= Feature Section ======= -->
        <section id="features" class="about section-bg">
            <div class="container" data-aos="fade-up">
                <div class="section-title">
                    <!--<h2>About</h2>-->
                    <h3>Features of <span>IMIS</span></h3>
                </div>
                <div class="text-left">
                <ul>
                    <li>Spatial context for municipal data - infrastructure, services, and resources</li>
<li>Efficient storage and management of municipal data, including infrastructure and essential services</li>
<li>Integration of CWIS data to support planning, management, and evaluation of sanitation systems and services</li>
<li>Decision support tools for decision-making based on spatial analysis and modelling</li>
<li>Real-time dashboard for monitoring KPIs and CWIS indicators</li>
<li>User-friendly interfaces with access control features</li>
<li>Scalability to adapt to the evolving technology and information needs</li>
<li>Mainstreaming CWIS service chain into the city's business process</li>
<li>Interoperable with external data sources, including tax/revenue, public health, emergency response data and more</li>
<li>Robust security measures to safeguard sensitive data, ensuring city data privacy compliance</li>
</ul>
                </div>
                <div style="text-align: center">
                    <!-- <button class="btn-get-started"> Learn More</button> -->
                </div>

            </div>
        </section>
        <!-- End Feature Section -->

        <!-- ======= Services Section ======= -->
        <section id="services" class="service">
            <div class="container" data-aos="fade-up">
                <div class="section-title">
                    <!--<h2>Functionalities</h2>-->
                    <h3>Functional<span> Modules</span></h3>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-4 d-flex align-items-stretch" data-aos="zoom-in" data-aos-delay="100">
                        <div class="icon-box">
                           <img src="{{ asset('img/svg/landing-page/buildingIMS.svg') }}" class="icon" alt="Building Icon">
                            <div class="card-body float-right ">

                                <h5 class=" text-center ">Building Information Management System</h5>

                                <div class="card-text text-left">
                                    <ul>
                                        <li>Maintains information about all existing and new buildings with their building footprints, sanitation system, socio-economic condition, etc</li>
                                        <li>Maintains information about low-income communities with their geographic coverage and sanitation system
                                        </li>
                                    </ul>

                                </div>
                            </div>

                        </div>

                    </div>

                    <div class="col-md-4 mb-4 d-flex align-items-stretch" data-aos="zoom-in" data-aos-delay="200">

                        <div class="icon-box">
                        <img src="{{ asset('img/svg/landing-page/propertyTaxCollectionIMS.svg')}}" class="icon">

                            <!--Card content-->
                            <div class="card-body">
                                <!--Title-->
                                <h5 class=" text-center ">Property Tax Collection Support System</h5>
                                <!--Text-->
                                <div class="card-text text-left ">
                                    <ul>
                                    <li>Enables to import of property tax or other revenue data into IMIS for spatial visualization of buildings or containments with their tax or revenue collection status</li>
                                    </ul>
                                </div>
                            </div>

                        </div>
                        <!--/.Card-->

                    </div>

                    <div class="col-md-4 mb-4 d-flex align-items-stretch " data-aos="zoom-in" data-aos-delay="300">
                        <div class="icon-box">
                        <img src="{{ asset('img/svg/landing-page/urbanManagementDSS.svg')}}" class="icon" alt="Urban Management DSS">

                                <div class="card-body">
                                    <!--Title-->
                                    <h5 class=" text-center ">Urban Management Decision Support System</h5>
                                    <!--Text-->
                                    <div class="card-text text-left">
                                        <ul>
                                        <li>Dashboard for monitoring the situation of sanitation and other elements required for planning, management and monitoring and evaluation of CWIS </li>
                                        <li>Dashboards for monitoring KPIs and CWIS indicators</li>
                                        <li>Tools for real-time monitoring of the sanitation service chain</li>
                                        <li>Spatial analysis tools</li>
                                        <li>Query and attribute analysis tools</li>
                                        <li>Basic navigation tools for exploration, analysis, and visualization of spatial data within a GIS environment and tools for printing maps</li>

                                        </ul>
                                    </div>
                                </div>

                            </div>
                            <!--/.Card-->
                        </div>
                    </div>


                <div class="row">
                    <div class="col-md-4 mb-4 d-flex align-items-stretch" data-aos="zoom-in" data-aos-delay="100">
                        <div class="icon-box">
                        <img src="{{ asset('img/svg/landing-page/utilityIMS.svg')}}" class="icon" alt="Utility Icon">
                            <!--Card content-->
                            <div class="card-body">
                                <!--Title-->
                                <h5 class="text-center  ">Utility Information Management System</h5>
                                <!--Text-->
                                <div class="card-text text-left ">
                                    <ul>
                                    <li>Maintains road network information</li>
                                    <li>Maintains water supply network information</li>
                                    <li>Maintains sewerage network information</li>
                                    <li>Maintains drainage network information</li>
                                    </ul>
                                </div>
                            </div>

                        </div>
                        <!--/.Card-->
                    </div>

                    <div class="col-md-4 mb-4 d-flex align-items-stretch" data-aos="zoom-in" data-aos-delay="100">

                        <div class="icon-box">
                        <img src="{{ asset('img/svg/landing-page/swmPaymentStatus.svg')}}" class="icon">
                            <!--Card content-->
                            <div class="card-body">
                                <!--Title-->
                                <h5 class=" text-center ">Solid Waste Information Support System</h5>
                                <!--Text-->
                                <div class="card-text text-left ">
                                    <ul>
                                    <li>Enables import of solid waste management data into the system for spatial visualization of buildings with their solid waste management status</li>
                                    </ul>
                                </div>
                            </div>

                        </div>
                        <!--/.Card-->
                    </div>


                    <div class="col-md-4 mb-4 d-flex align-items-stretch" data-aos="zoom-in" data-aos-delay="300">

                        <div class="icon-box">
                        <img src="{{ asset('img/svg/landing-page/watersupplyISS.svg')}}" class="icon" alt="Water Supply ISS Icon">
                            <!--Card content-->
                            <div class="card-body">
                                <!--Title-->
                                <h5 class=" text-center ">Water Supply Information Support System</h5>
                                <!--Text-->
                                <div class="card-text text-left">
                                    <ul><li>Enables to import of water supply bill payment data into the system for spatial visualization of buildings with their bill payment status</li></ul>
                                </div>
                            </div>

                        </div>
                        <!--/.Card-->
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-4 d-flex align-items-stretch" data-aos="zoom-in" data-aos-delay="200">

                        <div class="icon-box">
                        <img src="{{ asset('img/svg/landing-page/publicHealthISS.svg')}}" class="icon" alt="Fecal Sludge Icon">
                            <!--Card content-->
                            <div class="card-body">
                                <!--Title-->
                                <h5 class=" text-center ">Public Health Information Support System</h5>
                                <!--Text-->
                                <div class="card-text text-left ">
                                    <ul><li>Maintains information about hotspot areas where waterborne diseases occurred </li></ul>
                                </div>
                            </div>

                        </div>
                        <!--/.Card-->

                    </div>
                    <div class="col-md-4 mb-4 d-flex align-items-stretch" data-aos="zoom-in" data-aos-delay="200">

                        <div class="icon-box">
                        <img src="{{ asset('img/svg/landing-page/ptctIMS.svg')}}" class="icon" alt="PTCT  Icon">

                                <!--Card content-->
                                <div class="card-body">
                                    <!--Title-->
                                    <h5 class=" text-center ">Public/Community Toilet (PT/CT) Information Management System</h5>
                                    <!--Text-->
                                    <div class="card-text text-left ">
                                        <ul>
                                            <li>
                                                Maintains information about all PTs and CTs in the city with the number of users used and their feedback
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                        </div>
                        <!--/.Card-->
                    </div>

                    <div class="col-md-4 mb-4 d-flex align-items-stretch" data-aos="zoom-in" data-aos-delay="200">

                        <div class="icon-box">
                        <img src="{{ asset('img/svg/landing-page/sewerConnectionIMS.svg')}}" class="icon" alt="PTCT  Icon">


                                <!--Card content-->
                                <div class="card-body">
                                    <!--Title-->
                                    <h5 class=" text-center ">Sewer Connection Information Management System</h5>
                                    <!--Text-->
                                    <div class="card-text text-left ">
                                        <ul>
                                            <li>
                                                Maintains information about all buildings and their corresponding sewer network
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                        </div>
                        <!--/.Card-->
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-4 d-flex align-items-stretch" data-aos="zoom-in" data-aos-delay="200">

                            <div class="icon-box">
                            <img src="{{ asset('img/svg/landing-page/fecalSludgeIMS.svg') }}" class="icon" alt="Fecal Sludge Icon">

                                    <!--Card content-->
                                    <div class="card-body p">
                                        <!--Title-->
                                        <h5 class=" text-center ">Fecal Sludge Information Management System</h5>
                                        <!--Text-->
                                        <div class="card-text text-left">
                                            <ul>
                                                <li>Maintains information about all containments with their geographic location </li>
                                                <li>Maintains information about FSM service providers and their resources</li>
                                                <li>Maintains information about the Fecal Sludge Treatment Plant and the FS disposed records</li>
                                                <li>Maintains the quality test record of treated wastewater and compost generated from the treatment plant</li>
                                                <li>Maintains records of services from containment emptying to transport, and desludging of FS in the treatment plant</li>
                                                <li>Maintains the customer feedback data</li>

                                            </ul>

                                        </div>
                                    </div>

                                </div>
                                <!--/.Card-->
                            </div>

                    </div>
                </div>
            </div>

        </section>

        <!-- ======= Contact Section ======= -->
        <section class="contact " id="contact">
            <div class="container" data-aos="fade-up">
                <div class="section-title">
                    <!--<h2>Contact</h2>-->
                    <h3>Send a <span>message</span></h3>
                </div>

                <div class="row ">

                    <div class="col-lg-4">
                        <div class="info">

                            <div class="address">
                                <i class="fas fa-map-marker-alt"></i>
                                <h4>Location:</h4>
                                <p></p>
                            </div>

                            <div class="email">
                                <i class=" icon far fa-envelope"></i>
                                <h4>Email:</h4>
                                <p>imis@ait.asia</p>
                            </div>

                            <div class="phone">
                                <i class="fas fa-mobile-alt"></i>
                                <h4>Call:</h4>
                                <p></p>
                            </div>

                        </div>

                    </div>

                    <div class="col-lg-8 mt-5 mt-lg-0">

                        <form action="{{ route('contact.send') }}" method="POST">
                            @csrf
                            @error('contact_rate_limit')
                                <div class="alert alert-warning" role="alert" data-testid="contact-rate-limit-error">{{ $message }}</div>
                            @enderror
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <input type="text" name="name" class="form-control" id="name" placeholder="Your Name" value="{{ old('name') }}" required>
                                </div>
                                <div class="col-md-6 form-group mt-3 mt-md-0">
                                    <input type="email" class="form-control" name="email" id="email" placeholder="Your Email" value="{{ old('email') }}" required>
                                </div>
                            </div>
                            <div class="form-group mt-3">
                                <input type="text" class="form-control" name="subject" id="subject" placeholder="Subject" value="{{ old('subject') }}" required>
                            </div>
                            <div class="form-group mt-3">
                                <textarea class="form-control" name="message" rows="5" placeholder="Message" required>{{ old('message') }}</textarea>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-block">Send Now!</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </section>
        <!-- End Contact Section -->
    </main>
    <!-- End #main -->

    <!-- ======= Footer ======= -->
    <footer id="footer" class="section-bg">
        <div class="container py-4">
        <div class="copyright">
        <strong> Base IMIS <i class="fa-regular fa-copyright"> </i>  2022-{{ \Carbon\Carbon::now()->format('Y') }} by <a href="http://www.innovativesolution.com.np">
            ISPL</a> & <a href="https://www.gwsc.ait.ac.th/">GWSC-AIT</a> is licensed under <a href="https://creativecommons.org/licenses/by-nc-sa/4.0/?ref=chooser-v1">CC BY-NC-SA 4.0 </a>
</strong>
    </div>
            <div class="credits">
                Developed by
                <a href="https://innovativesolution.com.np/">Innovative Solution Pvt. Ltd.</a>
            </div>
        </div>
    </footer>
    <!-- End Footer -->

    <div id="preloader"></div>
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
        <i class="fa fa-arrow-circle-up"></i></a>
    <div class="modal" id="loginModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header d-block">
                    <h5 class=" text-center "> Integrated Municipal Information System</h5>
                </div>
                <div class="modal-body">
                    @include('auth.login')
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor JS Files -->
    <script src="{{asset('js/app.js')}}"></script>
    <!-- Template Main JS File -->
    <script src="{{ asset('js/main.js')}}"></script>
    <script>
        function myFunction() {
  var x = document.getElementById("password");
  if (x.type === "password") {
    x.type = "text";
  } else {
    x.type = "password";
  }
}
    $(document).ready(function() {

        // var myModal = document.getElementById('loginModal');
        var error = <?php echo ($errors); ?>;

        if (error.length > 0) {
        $('#loginModal').modal('show');
    }
    })
    </script>
     <script>
        // JavaScript code to get and display the current year
        var currentYear = new Date().getFullYear();
        document.getElementById("currentYear").textContent = currentYear;
    </script>
</body>

</html>
