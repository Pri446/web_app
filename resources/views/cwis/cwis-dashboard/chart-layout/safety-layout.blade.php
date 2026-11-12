<!-- Include Chart.js library -->
<style>
    /* Breakpoints */
    @media screen and (max-width: 540px) {
        .chart__figure {
            flex-direction: column;
            height: auto;
        }

        .chart__caption {
            margin: 15px auto auto;
            text-align: center;
            min-width: 160px;
        }

        .chart {
            width: 100%;
            margin-right: 0;
            margin-left: 0;
            /* Center-align on smaller screens */
        }

        .safety {
            width: 100%;
        }
    }

    /* Fonts (Google fonts) */
    .font--barlow {
        font-family: "Barlow Condensed", sans-serif;
    }

    .font--montserrat {
        font-family: "Montserrat", sans-serif;
    }

    .color--grey {
        color: #334466;
    }

    .color--green {
        color: #01713c;
    }

    /* Values */
    .canvas-size {
        width: 160px;
        height: 50px;
    }

    .font-weight--900 {
        font-weight: 900;
    }

    .animation-time--1400ms {
        animation-duration: 1400ms;
    }
    #sfe{
        height:900px;
        margin-left: 50px;
    }
    /* Fading animation */
    @keyframes fadein {
        0% {
            opacity: 0;
        }

        40% {
            opacity: 0;
        }

        80% {
            opacity: 1;
        }

        100% {
            opacity: 1;
        }
    }

    .main {
        display: grid;
    }

    .chart {
        position: relative;
        font-weight: 500;
        margin-right: auto;
        /* Center-align */
        margin-left: auto;
        /* Center-align */
        width: 50%;

        @media screen and (max-width: 540px) {
            width: 100%;
            margin-right: 0;
            margin-left: 0;
            /* Center-align on smaller screens */
        }

        .chart__figure {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
           
            height: 100%;
        }

        .chart__canvas {
            width: 160px;
            height: 140px;
        }

        .chart__caption {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            margin-left: auto;
            /* Center-align */
            margin-right: auto;
            /* Center-align */
            font-size: 36px;
            line-height: 56px;
            height: 100%;
            width: calc(80px + 160px);
            font-family: "Barlow Condensed", sans-serif;
            color: #01713c;
            border-bottom: 1px solid #ccc;
        }

        .chart__value {
            display: grid;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            height: calc(40px + 160px);
            width: 160px;
            animation: fadein 1400ms;
        }

        p {
            font-size: 20px;
            margin: auto;
            font-family: "Barlow Condensed", sans-serif;
        }
    }
    /* Styles for each safety container */
    .safety {
        border: 1px solid rgb(231, 227, 227);
        width: 500px;
        height: 400px;
        margin: 10px 10px;
        display: grid;
        padding: 2px;
        background-color: #F4F9F7;
    }

    .safety1,
    .safety2,
    .safety3,
    .safety4 {
        width: 100%;
        height: auto;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
       
    }

    .sf1,
    .sf2,
    .sf3,
    .sf4 {
        width: calc(33.33% - 20px);
       
        box-sizing: border-box;
       
      
    }
    
    .sf1:last-child,
    .sf2:last-child,
    .sf3:last-child,
    .sf4:last-child {
        margin-right: auto;
        margin-left: 2.5%;
    }

    .sf1 img,
    .sf2 img,
    .sf3 img,
    .sf4 img {
        width: 100%;
        height: auto;
        margin-bottom: 5px;
    }

    .safety img {
        width: 200px;
        height: 200px;
    }

    .card-header {
        width: 100%;
        text-align: left;
        margin-bottom: 10px;
        padding-bottom: 5px;
        border-bottom: 1px solid #ccc;
        height: 60px;
    }
    
    /* heading */
    .safety h2 {
        font-size: auto;
    }

    /* paragraph */
    .safety p {
        font-size: 15px;
        text-align: center;
    }

    span {
        position: absolute;
        top: 50%;
        left: 50%;
        text-align: center;
        font-size: 30px;
        margin-left: -25px;
        margin-top: -20px;
    }

    .chart-container {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
    }
 
</style>
</head>

<body>
<div class="hi" >
    @include('cwis.cwis-dashboard.chart-layout.safety-count-cards')
    <div class="safety safety1" >
        @include('cwis.cwis-dashboard.chart-layout.safety-main-card', ['chartId' => 'sf1Main'])
       

@if($allowedIndicatorCodes->contains('SF-1a'))
<div class="sf1">
            <div class="chart" id="sf1aContainer">
                <figure class="chart__figure">
                    <canvas class="chart__canvas" id="sf1aCanvas" width="160" height="140" aria-label="Example doughnut chart showing data as a percentage" role="img"></canvas>
                </figure>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    createDoughnutChart(
                        {{ isset($sf1a[0]) && $sf1a[0] !== null ? html_entity_decode($sf1a[0]->data_value) : 0  }},
                        '#29ab87', 
                        'sf1aCanvas', 
                        'sf1aContainer'
                    );
                });
            </script>
            <p>{{__('Population with access to safe individual toilets')}}</p>
        </div>
@endif
@if($allowedIndicatorCodes->contains('SF-1b'))
<div class="sf1" >
            <div class="chart" id="sf1bContainer">
                <figure class="chart__figure">
                    <canvas class="chart__canvas" id="sf1bCanvas" width="160" height="140" aria-label="Example doughnut chart showing data as a percentage" role="img"></canvas>
                </figure>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    createDoughnutChart(
                        {{ isset($sf1b[0]) && $sf1b[0] !== null ? html_entity_decode($sf1b[0]->data_value) : 0 }},
                        '#29ab87', 
                        'sf1bCanvas', 
                        'sf1bContainer'
                    );
                });
            </script>
            <p>{{__('Percentage of on-site sanitation that have been desludged')}}</p>
        </div>
@endif
@if($allowedIndicatorCodes->contains('SF-1c'))
<div class="sf1" >
            <div class="chart" id="sf1cContainer">
                <figure class="chart__figure">
                    <canvas class="chart__canvas" id="sf1cCanvas" width="160" height="140" aria-label="Example doughnut chart showing data as a percentage" role="img"></canvas>
                </figure>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    createDoughnutChart(
                        {{ isset($sf1c[0]) && $sf1c[0] !== null ? html_entity_decode($sf1c[0]->data_value) : 0 }},
                        '#29ab87', 
                        'sf1cCanvas', 
                        'sf1cContainer'
                    );
                });
            </script>
            <p>{{__('Percentage of collected FS disposed at a treatment plant or at designated disposal site')}}</p>
        </div>
@endif
     <h1 style="border-bottom: 1px solid 	#B2BEB5; margin-bottom:1%;"></h1>

@if($allowedIndicatorCodes->contains('SF-1d'))
<div class="sf1">
            <div class="chart" id="sf1dContainer">
                <figure class="chart__figure">
                    <canvas class="chart__canvas" id="sf1dCanvas" width="160" height="140" aria-label="Example doughnut chart showing data as a percentage" role="img"></canvas>
                </figure>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    createDoughnutChart(
                        {{ isset($sf1d[0]) && $sf1d[0] !== null ? html_entity_decode($sf1d[0]->data_value) : 0 }},
                        '#29ab87', 
                        'sf1dCanvas', 
                        'sf1dContainer'
                    );
                });
            </script>
            <p>{{__('FS treatment capacity as a percentage of total FS generated from NSS connections (excluding safely disposed in situ)')}}</p>
        </div>
@endif
@if($allowedIndicatorCodes->contains('SF-1e'))
<div class="sf1">
            <div class="chart" id="sf1eContainer">
                <figure class="chart__figure">
                    <canvas class="chart__canvas" id="sf1eCanvas" width="160" height="140" aria-label="Example doughnut chart showing data as a percentage" role="img"></canvas>
                </figure>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    createDoughnutChart(
                        {{ isset($sf1e[0]) && $sf1e[0] !== null ? html_entity_decode($sf1e[0]->data_value) : '0' }},
                        '#29ab87', 
                        'sf1eCanvas', 
                        'sf1eContainer'
                    );
                });
            </script>
            <p>{{__('FS treatment capacity as a percentage of total FS collected from NSS connections')}}</p>
        </div>
@endif
@if($allowedIndicatorCodes->contains('SF-1f'))
<div class="sf1">
            <div class="chart" id="sf1fContainer">
                <figure class="chart__figure">
                    <canvas class="chart__canvas" id="sf1fCanvas" width="160" height="140" aria-label="Example doughnut chart showing data as a percentage" role="img"></canvas>
                </figure>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    createDoughnutChart(
                        {{ isset($sf1f[0]) && $sf1f[0] !== null ? html_entity_decode($sf1f[0]->data_value) : 0 }},
                        '#29ab87', 
                        'sf1fCanvas', 
                        'sf1fContainer'
                    );
                });
            </script>
            <p>{{ __('Wastewater treatment capacity as a percentage of total wastewater generated from sewered connections and greywater generated from non-sewered connections')}}</p>
        </div>
@endif
        <h1 style="border-bottom: 1px solid 	#B2BEB5; margin-bottom:1%;"></h1>
@if($allowedIndicatorCodes->contains('SF-1g'))
<div class="sf1">
            <div class="chart" id="sf1gContainer">
                <figure class="chart__figure">
                    <canvas class="chart__canvas" id="sf1gCanvas" width="160" height="140" aria-label="Example doughnut chart showing data as a percentage" role="img"></canvas>
                </figure>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    createDoughnutChart(
                        {{ isset($sf1g[0]) && $sf1g[0] !== null ? html_entity_decode($sf1g[0]->data_value) : 0 }},
                        '#29ab87', 
                        'sf1gCanvas', 
                        'sf1gContainer'
                    );
                });
            </script>
            <p>{{__('Effectiveness of FS/WW treatment in meeting prescribed standards for effluent discharge and biosolids disposal')}}</p>
        </div>
@endif
@if($allowedIndicatorCodes->contains('SF-2a'))
<div class="sf2">
            <div class="chart" id="sf2aContainer">
                <figure class="chart__figure">
                    <canvas class="chart__canvas" id="sf2aCanvas" width="160" height="140" aria-label="Example doughnut chart showing data as a percentage" role="img"></canvas>
                </figure>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    createDoughnutChart(
                        {{ isset($sf2a[0]) && $sf2a[0] !== null ? html_entity_decode($sf2a[0]->data_value) : 0 }},
                        '#29ab87', 
                        'sf2aCanvas', 
                        'sf2aContainer'
                    );
                });
            </script>
            <p>{{__('Percentage LIC population with access to safe individual toilets')}}</p>
        </div>
@endif
@if($allowedIndicatorCodes->contains('SF-2b'))
<div class="sf2">
            <div class="chart" id="sf2bContainer">
                <figure class="chart__figure">
                    <canvas class="chart__canvas" id="sf2bCanvas" width="160" height="140" aria-label="Example doughnut chart showing data as a percentage" role="img"></canvas>
                </figure>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    createDoughnutChart(
                        {{ isset($sf2b[0]) && $sf2b[0] !== null ? html_entity_decode($sf2b[0]->data_value) : 0 }},
                        '#29ab87', 
                        'sf2bCanvas', 
                        'sf2bContainer'
                    );
                });
            </script>
            <p>{{__('Percentage of LIC, NSS, IHHLs that have been desludged')}}</p>
        </div>
@endif
        <h1 style="border-bottom: 1px solid 	#B2BEB5; margin-bottom:1%;"></h1>
@if($allowedIndicatorCodes->contains('SF-2c'))
<div class="sf2">
            <div class="chart" id="sf2cContainer">
                <figure class="chart__figure">
                    <canvas class="chart__canvas" id="sf2cCanvas" width="160" height="140" aria-label="Example doughnut chart showing data as a percentage" role="img"></canvas>
                </figure>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    createDoughnutChart(
                        {{ isset($sf2c[0]) && $sf2c[0] !== null ? html_entity_decode($sf2c[0]->data_value) : 0 }},
                        '#29ab87', 
                        'sf2cCanvas', 
                        'sf2cContainer'
                    );
                });
            </script>
            <p>{{__('Percentage of collected FS (collected from LIC) disposed at treatment plant or designated disposal sites')}}</p>
        </div>
@endif
@if($allowedIndicatorCodes->contains('SF-3'))
<div class="sf3">
            <div class="chart" id="sf3aContainer">
                <figure class="chart__figure">
                    <canvas class="chart__canvas" id="sf3aCanvas" width="160" height="140" aria-label="Example doughnut chart showing data as a percentage" role="img"></canvas>

                </figure>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    createDoughnutChart(
                        {{ isset($sf3[0]) && $sf3[0] !== null ? html_entity_decode($sf3[0]->data_value) : 0 }},
                        '#29ab87', 
                        'sf3aCanvas', 'sf3aContainer'
                    );
                });
            </script>
            <p>{{__('Percentage of dependent population (those without access to a private toilet/latrine) with access to safe shared facilities (CT/PT)')}}</p>
        </div>
@endif
@if($allowedIndicatorCodes->contains('SF-3b'))
<div class="sf3">
            <div class="chart" id="sf3bContainer">
                <figure class="chart__figure">
                    <canvas class="chart__canvas" id="sf3bCanvas" width="160" height="140" aria-label="Example doughnut chart showing data as a percentage" role="img"></canvas>

                </figure>
            </div>
            <script>
              document.addEventListener('DOMContentLoaded', function () {
    createDoughnutChart(
        {{ isset($sf3b[0]) && $sf3b[0] !== null ? html_entity_decode($sf3b[0]->data_value) : 0 }},
        '#29ab87', 'sf3bCanvas', 'sf3bContainer'
    );
});
            </script>
            <p>{{__('Percentage of CTs that adhere to principles of universal design')}}</p>
        </div>
@endif
        <h1 style="border-bottom: 1px solid 	#B2BEB5; margin-bottom:1%;"></h1>
@if($allowedIndicatorCodes->contains('SF-3c'))
<div class="sf3">

<div class="chart" id="sf3cContainer">
    <figure class="chart__figure">
        <canvas class="chart__canvas" id="sf3cCanvas" width="160" height="140" aria-label="Example doughnut chart showing data as a percentage" role="img"></canvas>

    </figure>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
createDoughnutChart(
{{ isset($sf3c[0]) && $sf3c[0] !== null ? html_entity_decode($sf3c[0]->data_value) : 0 }},
'#29ab87', 'sf3cCanvas', 'sf3cContainer'
);
});
</script>
<p>{{__('Percentage of users of CTs that are women')}}</p>
</div>
@endif
@if($allowedIndicatorCodes->contains('SF-3e'))
<div class="sf3" id="sf3e">
    
 @include("cwis.cwis-dashboard.charts.safety.sf-3.sf-3e-chart")
 <br>


 <p>{{__('Average distance from the house to the closest CT (in meters)')}}</p>
</div>
@endif
@if($allowedIndicatorCodes->contains('SF-4a'))
<div class="sf4">
            <div class="chart" id="sf4aContainer">
                <figure class="chart__figure">
                    <canvas class="chart__canvas" id="sf4aCanvas" width="160" height="140" aria-label="Example doughnut chart showing data as a percentage" role="img"></canvas>

                </figure>
            </div>
            <script>
              document.addEventListener('DOMContentLoaded', function () {
    createDoughnutChart(
        {{ isset($sf4a[0]) && $sf4a[0] !== null ? html_entity_decode($sf4a[0]->data_value) : 0 }},
        '#29ab87', 'sf4aCanvas', 'sf4aContainer'
    );
});
            </script>
            <p>{{__('Percentage of PTs where FS and WW generated is safely transported to TP or safely disposed in situ')}}</p>
        </div>
@endif
        <h1 style="border-bottom: 1px solid 	#B2BEB5; margin-bottom:1%;"></h1>
@if($allowedIndicatorCodes->contains('SF-4b'))
<div class="sf4">
            <div class="chart" id="sf4bContainer">
                <figure class="chart__figure">
                    <canvas class="chart__canvas" id="sf4bCanvas" width="160" height="140" aria-label="Example doughnut chart showing data as a percentage" role="img"></canvas>

                </figure>
            </div>
            <script>
               document.addEventListener('DOMContentLoaded', function () {
    createDoughnutChart(
        {{ isset($sf4b[0]) && $sf4b[0] !== null ? html_entity_decode($sf4b[0]->data_value) : 0 }},
        '#29ab87', 'sf4bCanvas', 'sf4bContainer'
    );
});
            </script>
            <p>{{__('Percentage of PTs that adhere to principles of universal design')}}</p>
        </div>
@endif
@if($allowedIndicatorCodes->contains('SF-4d'))
<div class="sf4">

            <div class="chart" id="sf4dContainer">
                <figure class="chart__figure">
                    <canvas class="chart__canvas" id="sf4dCanvas" width="160" height="140" aria-label="Example doughnut chart showing data as a percentage" role="img"></canvas>

                </figure>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
    createDoughnutChart(
        {{ isset($sf4d[0]) && $sf4d[0] !== null ? html_entity_decode($sf4d[0]->data_value) : 0 }},
        '#29ab87', 'sf4dCanvas', 'sf4dContainer'
    );
});
            </script>
            <p>{{__('Percentage of users of PTs that are women')}}</p>
        </div>
@endif
@if($allowedIndicatorCodes->contains('SF-5'))
<div class="sf4">
        <div class="chart" id="sf5Container">
            <figure class="chart__figure">
                <canvas class="chart__canvas" id="sf5Canvas" width="160" height="140" aria-label="Example doughnut chart showing data as a percentage" role="img"></canvas>

            </figure>
        </div>
        <script>
         document.addEventListener('DOMContentLoaded', function () {
    createDoughnutChart(
        {{ isset($sf5[0]) && $sf5[0] !== null ? html_entity_decode($sf5[0]->data_value) : 0 }},
        '#29ab87', 'sf5Canvas', 'sf5Container'
    );
});
        </script>
        <P>{{__('Percentage of educational institutions where FS/WW generated is safely transported to TP or safely disposed in situ')}} </P>
    </div>
@endif
    <h1 style="border-bottom: 1px solid 	#B2BEB5; margin-bottom:1%;"></h1>
@if($allowedIndicatorCodes->contains('SF-6'))
<div class="sf4">
        <div class="chart" id="sf6Container">
            <figure class="chart__figure">
                <canvas class="chart__canvas" id="sf6Canvas" width="160" height="140" aria-label="Example doughnut chart showing data as a percentage" role="img"></canvas>

            </figure>
        </div>
        <script>
          document.addEventListener('DOMContentLoaded', function () {
    createDoughnutChart(
        {{ isset($sf6[0]) && $sf6[0] !== null ? html_entity_decode($sf6[0]->data_value) : 0 }},
        '#29ab87', 'sf6Canvas', 'sf6Container'
    );
});
        </script>
        <P>{{__('Percentage of healthcare facilities where FS/WW generated is safely transported to TP or safely disposed in situ')}}</P>
        </div>
@endif
@if($allowedIndicatorCodes->contains('SF-7'))
<div class="sf4">
        <div class="chart" id="sf7Container">
            <figure class="chart__figure">
                <canvas class="chart__canvas" id="sf7Canvas" width="160" height="140" aria-label="Example doughnut chart showing data as a percentage" role="img"></canvas>

            </figure>
        </div>
        <script>
           document.addEventListener('DOMContentLoaded', function () {
    createDoughnutChart(
        {{ isset($sf7[0]) && $sf7[0] !== null ? html_entity_decode($sf7[0]->data_value) : 0 }},
        '#29ab87', 'sf7Canvas', 'sf7Container'
    );
});
        </script>
<p>{{__('Percentage of desludging services completed mechanically or semi-mechanically (by a gulper)')}}</p>
    </div>
@endif
@if($allowedIndicatorCodes->contains('SF-9'))
<div class="sf4">
        <div class="chart" id="sf9Container">
            <figure class="chart__figure">
                <canvas class="chart__canvas" id="sf9Canvas" width="160" height="140" aria-label="Example doughnut chart showing data as a percentage" role="img"></canvas>

            </figure>
        </div>
        <script>
           document.addEventListener('DOMContentLoaded', function () {
    createDoughnutChart(
        {{ isset($sf9[0]) && $sf9[0] !== null ? html_entity_decode($sf9[0]->data_value) : 0 }},
        '#29ab87', 'sf9Canvas', 'sf9Container'
    );
});
        </script>
<P>{{__('Percentage of tests which are in compliance with water quality standards for fecal coliform')}}</P>
    </div>
@endif
    </div>
</div>
