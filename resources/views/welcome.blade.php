<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet" type="text/css">

    <!-- Styles -->
    <style>
        html, body {
            background-color: #fff;
            color: #636b6f;
            font-family: 'Nunito', sans-serif;
            font-weight: 200;
            height: 100vh;
            margin: 0;
        }

        .full-height {
            height: 100vh;
        }

        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .position-ref {
            position: relative;
        }

        .top-right {
            position: absolute;
            right: 10px;
            top: 18px;
        }

        .content {
            text-align: center;
        }

        .title {
            font-size: 84px;
        }

        .links > a {
            color: #636b6f;
            padding: 0 25px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .1rem;
            text-decoration: none;
            text-transform: uppercase;
        }

        .m-b-md {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
<div class="flex-center position-ref full-height">
    @if (Route::has('login'))
        <div class="top-right links">
            @auth
                <a href="{{ url('/home') }}">Home</a>
                @else
                    <a href="{{ route('login') }}">Login</a>
                    <a href="{{ route('register') }}">Register</a>
                    @endauth
        </div>
    @endif

    <div class="content">
        <div>
            <form action="#" method="POST" id="init_data_form">
                <label for="price">
                    Price
                    <input type="number" step="0.0000000001" name="price" id="price">
                </label>
                <label for="time">
                    Time
                    <input type="text" name="time" id="time">
                </label>
                <label for="go">
                    <input type="submit" id="go" value="Go">
                </label>
            </form>
        </div>
        <div id="chart-container" style="width:100%; height:400px;"></div>
    </div>
</div>
<script src="/js/jquery-3.3.1.min.js"
        integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>

<script src="/js/highstock.js"></script>
<script src="/js/socket.js"></script>
<script>

    $(document).ready(function () {
        // Create the chart
        var chart = Highcharts.stockChart('chart-container', {

            rangeSelector: {
                buttons: [{
                    count: 1,
                    type: 'minute',
                    text: '1M'
                }, {
                    count: 5,
                    type: 'minute',
                    text: '5M'
                }, {
                    type: 'all',
                    text: 'All'
                }],
                inputEnabled: false,
                selected: 0
            },

            title: {
                text: 'AAPL Stock Price'
            },

            series: [{
                name: 'AAPL Stock Price',
                data: null,
                type: 'spline',
                tooltip: {
                    valueDecimals: 8
                }
            },
                {
                    name: 'AAPL Stock Price',
                    data: null,
                    type: 'spline',
                    tooltip: {
                        valueDecimals: 8
                    }
                },
                {
                    name: 'MEDIAN',
                    data: null,
                    type: 'spline',
                    tooltip: {
                        valueDecimals: 8
                    },
                    turboThreshold: 0
                },
                {
                    name: 'MEDIAN upper 1 level',
                    data: null,
                    type: 'spline',
                    tooltip: {
                        valueDecimals: 8
                    },
                    turboThreshold: 0
                },
                {
                    name: 'MEDIAN upper 2 level',
                    data: null,
                    type: 'spline',
                    tooltip: {
                        valueDecimals: 8
                    },
                    turboThreshold: 0
                },
                {
                    name: 'MEDIAN upper 3 level',
                    data: null,
                    type: 'spline',
                    tooltip: {
                        valueDecimals: 8
                    },
                    turboThreshold: 0
                },
                {
                    name: 'MEDIAN lower 1 level',
                    data: null,
                    type: 'spline',
                    tooltip: {
                        valueDecimals: 8
                    },
                    turboThreshold: 0
                },
                {
                    name: 'MEDIAN lower 2 level',
                    data: null,
                    type: 'spline',
                    tooltip: {
                        valueDecimals: 8
                    },
                    turboThreshold: 0
                },
                {
                    name: 'MEDIAN lower 3 level',
                    data: null,
                    type: 'spline',
                    tooltip: {
                        valueDecimals: 8
                    },
                    turboThreshold: 0
                },
            ]
        });
        var init_data = false;
        var data_inited = false;
        var all_ticks = [];
        var all_changed_ticks = [];
        var median_spline = {
            upper_3: [],
            upper_2: [],
            upper_1: [],
            middle: [],
            lower_1: [],
            lower_2: [],
            lower_3: []
        };
        var init_tick;
        var prev_real_tick = null;
        var prev_fake_tick = null;
        var time_to_change = 0;
        var price_to_change = 0;
        var price_diff;
        var abs_price_diff;
        var average_change_by_sec = 0;
        $('#init_data_form').submit(function (e){
            e.preventDefault();
            if(!init_data) {
                init_data = true;
                time_to_change = parseInt($('#time').val()) * 60;
                price_to_change = parseFloat($('#price').val());
            }
        });
        setInterval(function () {
            $.ajax({
                url: 'https://quotes.instaforex.com/api/quotesTick?m=json&q=eurusd',
                type: 'GET',
                success: function (data) {
                    var tick = data[0];
                    if(init_data && !data_inited) {
                        data_inited = true;
                        init_tick = tick;
                        prev_fake_tick = init_tick;
                        all_changed_ticks.push({x: init_tick.lasttime * 1000, y: (init_tick.ask + init_tick.bid) * 0.5});
                        chart.series[1].setData(all_changed_ticks, true);
                        price_diff = ((init_tick.ask + init_tick.bid) / 2) - price_to_change;
                        abs_price_diff = Math.abs(price_diff);
                        average_change_by_sec = Math.abs(price_diff) / time_to_change;
                        var when_make_changes = [];
                        for(i = 0; i < time_to_change; i++) {
                            var current_median_point = {x: (init_tick.lasttime + i) * 1000, y: ((init_tick.ask + init_tick.bid) / 2) + (average_change_by_sec * i)};
                            median_spline.middle.push(current_median_point);
                            if(when_make_changes.length === 0) {
                                var count_of_changes = Math.round((Math.random() * (6 - 3) + 3));
                                for(j = 0; j < count_of_changes; j++) {
                                    var time_when_change = Math.round((Math.random() * ((init_tick.lasttime + i + 60) - (init_tick.lasttime + i + 1)) + (init_tick.lasttime + i + 1)));
                                    while(when_make_changes.indexOf(time_when_change) !== -1) {
                                        time_when_change = Math.round((Math.random() * ((init_tick.lasttime + i + 60) - (init_tick.lasttime + i + 1)) + (init_tick.lasttime + i + 1)));
                                    }
                                    when_make_changes.push(time_when_change);
                                }
                                when_make_changes = when_make_changes.sort();
                            } else {
                                if(when_make_changes[0] === (init_tick.lasttime + i)) {
                                    when_make_changes.shift();
                                    var last_tick = all_changed_ticks[all_changed_ticks.length - 1];
                                    var current_price_diff = current_median_point.y - last_tick.y;
                                    var new_tick = {x: (init_tick.lasttime + i) * 1000, y: last_tick.y};
                                    if(current_price_diff > 0) {
                                        var range = Math.round(Math.random() * 1000);
                                        if(range >= 0 && range <= 700) {
                                            max_distance = current_median_point.y + abs_price_diff * 0.03;
                                            min_distance = current_median_point.y + abs_price_diff * 0.01;
                                        } else if(range > 700 && range <= 900) {
                                            max_distance = current_median_point.y + abs_price_diff * 0.13;
                                            min_distance = current_median_point.y + abs_price_diff * 0.08;
                                        } else if(range > 900 && range <= 980) {
                                            max_distance = current_median_point.y + abs_price_diff * 0.08;
                                            min_distance = current_median_point.y + abs_price_diff * 0.05;
                                        } else {
                                            max_distance = current_median_point.y + abs_price_diff * 0.05;
                                            min_distance = current_median_point.y + abs_price_diff * 0.03;
                                        }
                                    } else {
                                        var range = Math.round(Math.random() * 1000);
                                        if(range >= 0 && range <= 700) {
                                            max_distance = current_median_point.y - abs_price_diff * 0.03;
                                            min_distance = current_median_point.y - abs_price_diff * 0.01;
                                        } else if(range > 700 && range <= 900) {
                                            max_distance = current_median_point.y - abs_price_diff * 0.13;
                                            min_distance = current_median_point.y - abs_price_diff * 0.08;
                                        } else if(range > 900 && range <= 980) {
                                            max_distance = current_median_point.y - abs_price_diff * 0.08;
                                            min_distance = current_median_point.y - abs_price_diff * 0.05;
                                        } else {
                                            max_distance = current_median_point.y - abs_price_diff * 0.05;
                                            min_distance = current_median_point.y - abs_price_diff * 0.03;
                                        }
                                    }
                                    new_tick.y = (Math.random() * (max_distance - min_distance) + min_distance);
                                    all_changed_ticks.push(new_tick);
                                }
                            }
                        }
                        var last_tick = all_changed_ticks[all_changed_ticks.length - 1];
                        var new_tick = {x: (init_tick.lasttime + time_to_change + 5) * 1000, y: price_to_change};
                        all_changed_ticks.push(new_tick);
                        console.log(all_changed_ticks);
                        chart.series[1].setData(all_changed_ticks, true);
                        chart.series[2].setData(median_spline.middle, true);
                    }
                    if(init_data && data_inited) {
                        if(tick.lasttime >= (init_tick.lasttime + time_to_change + 5)) {
                            var current_real_price = (tick.ask + tick.bid) * 0.5;

                        }
                    }
                    if (prev_real_tick) {
                        if (tick.lasttime === prev_real_tick.lasttime) {
                            all_ticks[all_ticks.length - 1] = {x: tick.lasttime * 1000, y: (tick.ask + tick.bid) * 0.5};
                        } else {
                            all_ticks.push({x: tick.lasttime * 1000, y: (tick.ask + tick.bid) * 0.5});
                        }
                    } else {
                        all_ticks.push({x: tick.lasttime * 1000, y: (tick.ask + tick.bid) * 0.5});
                    }
                    chart.series[0].setData(all_ticks, true);
                    prev_real_tick = tick;
                },
                error: function (data) {
                    console.log(data);
                }
            });
        }, 1000);
    });
</script>
</body>
</html>
