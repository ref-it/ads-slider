<!DOCTYPE html>
<html lang="en-US">

<head>
    <meta charset="UTF-8" />
    <meta name="author" content="Francesco Bedini" />
    <meta name="google" content="notranslate" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="description" content="This page shows slides." />

    <meta name="csrf-token" content="{{ csrf_token() }}" />

    @include('layouts.favicon')
    <title>NEW Ads</title>
    @vite(['resources/js/slider.ts'])
</head>

<body>
    <div id="main-container">
        <div id="loading-container">
            <div id="loading-content">
                <div class="monitor-name">{{ $data['m']['name'] }}</div>
                <i class="loading fa fa-tv animate__animated animate__swing animate__slow animate__repeat-3"></i>
                <div id="loading-message">{{__('is working hard…')}}</div>
                <div class="version"><span>{{__('Version')}}: </span><span>{{ config('app.version') }}</span></div>
            </div>
        </div>

        <div id="fatal-error-container" style="display:none">
            <div id="fatal-error-content">
                <div class="monitor-name">{{ $data['m']['name'] }}</div>
                <i
                    class="loading fa fa-heart-crack animate__animated animate__swing animate__slow animate__repeat-3"></i>
                <div class="fatal-error">⚠️ {{__('Fatal error!')}} ⚠️</div>
                <div id="fatal-error-message"></div>
                <div class="version"><span>{{__('Version')}}: </span><span>{{ config('app.version') }}</span></div>
            </div>
        </div>

        <div id="final-round-container" style="display:none">
            <div id="final-round-band">
                <div class="countdown"><span class="minutes">&nbsp;</span>:<span class="seconds">&nbsp;</span></div>
                <div id="final-round-band-label-wrap">
                    <span id="final-round-band-label">{{__('Final round!')}}</span>
                </div>
            </div>
            <div id="final-round-countdown-track">
                <div id="final-round-countdown-bar"></div>
            </div>
            <div id="final-round-content">
                <div class="event-name"></div>
            </div>
        </div>

        <div id="orderslist-container" style="display:none">
            <div class="ordersslide animate__animated animate__fadeIn">
                <div id="ol-pickup" class="ordersbox">
                    <h2 class="title">{{ __('Ready') }}</h2>
                    <div class="orderslist">
                    </div>
                </div>
                    <div id="ol-fulfilled" class="ordersbox">
                    <h2 class="title">{{ __('Fulfilled') }}</h2>
                    <div class="orderslist">
                    </div>
                </div>
                <div id="ol-pending" class="ordersbox">
                    <h2 class="title">{{ __('Pending') }}</h2>
                    <div class="orderslist">
                    </div>
                </div>
                <div id="ol-counters">

                </div>
            </div>
        </div>


        <div id="menu-container" style="display:none">
            <div class="menuPageTitle"><span class="menu-name">
                    <!-- -->
                </span><span class="menu-icon">
                    <!-- -->
                </span><!-- -->
            </div>
        </div>
        <template id="menu-row">
            <div class='menu_item animate__animated animate__fadeIn'>
            </div>
        </template>

        <div id="final-15-minutes" style="display:none">
            <i class="fas fa-unlock-alt animate__animated animate__pulse animate__infinite animate__slow">
                <!-- -->
            </i>
            <div id="wir-schliessen">{{__('We will close shortly')}}&nbsp;😢
                @if(app()->getLocale() != 'en')
                    <hr />
                    <span class="english">{{__('We will close shortly', [], 'en')}}</span>
                @endif
            </div>
        </div>

        <div id="thirty-minutes-after" style="display:none">
            <div class="table">
                <i class="fas fa-hourglass-end animate__animated animate__swing animate__slow">
                    <!-- -->
                </i>
                <div id="wir-haben-geschlossen">{{__("Sorry, we're closed!")}}&nbsp;😭
                    @if(app()->getLocale() != 'en')
                    <br><span class="english">{{__("Sorry, we're closed!",[],'en')}}</span>
                    @endif
                </div>
            </div>
            <div id="mitgliederWerbung">
            </div>
        </div>

        <div id="preparations" style="display:none">
            <div id="preparations-band">
                <div class="countdown"><span class="minutes">&nbsp;</span>:<span class="seconds">&nbsp;</span></div>
                <i id="preparations-icon"></i>
            </div>
            <div id="preparations-countdown-track">
                <div id="preparations-countdown-bar"></div>
            </div>
            <div id="preparations-content">
                <div class="event-name"></div>
            </div>
        </div>

        <div id="happy-hour-container" style="display:none">
            <div id="happy-hour-band">
                <div class="countdown">
                    <span class="hours">&nbsp;</span><span class="minutes">&nbsp;</span>:<span class="seconds">&nbsp;</span>
                </div>
                <div id="happy-hour-band-label-wrap">
                    <span id="happy-hour-band-label">{{__('Happy Hour!')}}</span>
                </div>
            </div>
            <div id="happy-hour-countdown-track">
                <div id="happy-hour-countdown-bar"></div>
            </div>
            <div id="happy-hour-content">
                <div id="happy-hour-drink"></div>
                <div id="happy-hour-priceline"><span id="price"></span></div>
                <div id="happy-hour-extra">&nbsp;</div>
            </div>
        </div>

        <div id="event-slide-small" style="display:none">
            <h1 id="event-name-small" class="event-name"></h1>
            <div>
                <i id="main-icon-small"></i>
                <div id="event-details-small" class="event-details">
                    <div class="start-small"><i class="fas fa-calendar">
                            <!-- -->
                        </i>&nbsp;<span id="start-small"></span></div>
                    <div><i class="fas fa-clock">
                            <!-- -->
                        </i>&nbsp;<span id="start-time-small"></div>
                </div>
            </div>
        </div>


        <div id="event-slide" style="display:none">
            <div id="progress-bar"></div>
            <div id="left-part-container">
                <i id="main-icon"></i>
                <canvas id="qr-code"></canvas>
            </div>
            <div class="event-details">
                <div class="start"><i class="fas fa-calendar">
                        <!-- -->
                    </i>&nbsp;<span id="start"></span></div>
                <div class="start-time"><i class="fas fa-clock">
                        <!-- -->
                    </i>&nbsp;<span id="start-time"></span></div>
                <h1 class="event-name">&nbsp;</h1>
                <div class="section"><i class="fas fa-map-marked-alt"></i>&nbsp;<span id="section"></span></div>
            </div>
            <div id="bar">
                <p id="marqueeText"></p>
            </div>
        </div>
        <div id="karaoke" style="display:none">
            <h1>{{__('Karaoke')}} - {{__('Coming next…')}}</h1>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Song') }}</th>
                        <th>{{ __('Singer') }}</th>
                    </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
        <div id="weather" style="display:none">
            <div>
                <div id="weatherTitle" class="weatherPageTitle">
                    <div>{{ __('The weather in') }}&nbsp;</div>
                    <div id="placeName" class="weatherPlaceName">Ilmenau</div>
                </div>
                <div id="weatherColumns" class="weatherColumnsRow">
                    <div id="sunTimes">
                        <div id="sunRise" class="dailyColumn sunTimeColumn">
                            <div class="sunTimeIcon"><i class="fas fa-sun">&nbsp;</i><i class="fas fa-arrow-circle-up">&nbsp;</i></div>
                            <div class="dailyDate"><time>6:07</time></div>
                        </div>
                        <div id="sunSet" class="dailyColumn sunTimeColumn">
                            <div class="sunTimeIcon"><i class="fas fa-sun">&nbsp;</i><i class="fas fa-arrow-circle-down">&nbsp;</i></div>
                            <div class="dailyDate"><time>18:09</time></div>
                        </div>
                    </div>
                </div>
                <div id="templateWeatherColumn" class="dailyColumn hourColumn" style="display: none">
                    <div class="dailyDate">12:00</div>
                    <div class="dailyIcon">
                        <img src="https://openweathermap.org/img/wn/01n@2x.png" alt="?" data-icon="01n" />
                    </div>
                    <div class="dailyTemps">
                        <span class="weatherTemp">23</span><span class="superscript">°C</span>
                        <div class="temp_feels_row">
                            <span class="temp_feels dailyMin">25</span><span class="superscript">°C</span>
                        </div>
                    </div>
                    <div class="weatherCloud weatherMetric">
                        <i class="fas fa-cloud">&nbsp;</i><span class="weatherCloudValue">55</span><span class="superscript">%</span>
                    </div>
                    <div class="weatherWind weatherMetric">
                        <i class="fas fa-wind">&nbsp;</i><span class="weatherWindValue">12</span><span class="superscript">km/h</span>
                    </div>
                </div>
            </div>
            <div id="noWarranty">{{ __('Information without guarantee') }}</div>
            <div id="weatherSource"></div>
        </div>

        <div id="weather-daily" style="display:none">
            <div>
                <div id="weatherDailyTitle" class="weatherPageTitle">
                    <div>{{ __('The weather in') }}&nbsp;</div>
                    <div id="dailyPlaceName" class="weatherPlaceName">Ilmenau</div>
                </div>
                <div id="weatherDailyColumns" class="weatherColumnsRow">

                </div>
                <div id="templateWeatherDailyColumn" class="dailyColumn" style="display: none">
                    <div class="dailyDate">Mon</div>
                    <div class="dailyIcon">
                        <img src="https://openweathermap.org/img/wn/01d@2x.png" alt="?" data-icon="01d" />
                    </div>
                    <div class="dailyTemps">
                        <span class="dailyMax">23</span><span class="superscript">°C</span><br>
                        <span class="dailyMin">12</span><span class="superscript">°C</span>
                    </div>
                    <div class="dailySunshine weatherMetric">
                        <i class="fas fa-sun">&nbsp;</i><span class="dailySunshineValue">6.8</span><span class="superscript">h</span>
                    </div>
                    <div class="dailyWind weatherMetric">
                        <i class="fas fa-wind">&nbsp;</i><span class="dailyWindValue">12</span><span class="superscript">km/h</span>
                    </div>
                </div>
            </div>
            <div id="noWarrantyDaily">{{ __('Information without guarantee') }}</div>
            <div id="weatherSourceDaily">{{ __('Source') }}: {{ __('German Weather Service (DWD)') }}</div>
        </div>

        <div id="canteen" style="display:none" data-students-label="{{ __('Students') }}" data-employees-label="{{ __('Employees') }}" data-guests-label="{{ __('Guests') }}">
            <div id="canteenTitle" class="weatherPageTitle"></div>
            <div id="canteenItems" class="canteenItemsColumn"></div>
        </div>

        <div id="pics-container" style="display:none">
            <!-- img id="current-pic" src="#" alt="" /-->
        </div>

        <div id="videos-container" style="display:none">
            <!-- img id="current-pic" src="#" alt="" /-->
            <video id="mainVideo" autoplay muted
                poster="{{ Vite::asset('resources/img/videos/VideoIsLoading.gif') }}">
                <source id="mainVideoSource">
                </source>
                <!--track src="example.vtt" default kind="subtitles" srclang="en" label="English"-->
                {{__('This browser does not support the HTML5 video tag')}}
            </video>
        </div>
    </div>
    <div id="clock"></div>
    <div id="alert" style="display:none">
        <i id="alert-icon" class="fas fa-circle-info"></i>
        <div class="row">
            <div class="col">
                <h4 id="alert-title">
                    <!-- Alert title -->
                </h4>
            </div>
            <div class="col-auto">
                <canvas id="alert-qr"></canvas>
            </div>
        </div>
        <div>
            <div id="alert-message">
                <!--Alert message-->
            </div>
        </div>
    </div>
    <script @cspNonce>
        window.getData = () => {
            return {!! json_encode($data, JSON_HEX_TAG) !!}
        };
    </script>
</body>

</html>
