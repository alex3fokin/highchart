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
                    Time to change
                    <input type="text" name="time_to_change" id="time_to_change">
                </label>
                <label for="time">
                    Time to end
                    <input type="text" name="time_to_end" id="time_to_end">
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
                    },
                    turboThreshold: 0
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
        var init_second_part = false;
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
        var end_init_tick;
        var prev_real_tick = null;
        var prev_fake_tick = null;
        var time_to_change = 0;
        var time_to_end = 0;
        var price_to_change = 0;
        var price_diff;
        var abs_price_diff;
        var average_change_by_sec = 0;
        var end_changes = false;
        var max_possible_diff_to_end;
        $('#init_data_form').submit(function (e){
            e.preventDefault();
            if(!init_data) {
                init_data = true;
                time_to_change = parseInt($('#time_to_change').val()) * 60;
                time_to_end = parseInt($('#time_to_end').val()) * 60;
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
                        var is_flat = false;
                        var flat_init_point;
                        var recovery_offset = 0;
                        var flats_in_a_row = 0;
                        var when_prevent_flat = time_to_change - (time_to_change * 0.25);
                        for(i = 0; i < time_to_change; i++) {
                            if(i >= when_prevent_flat) {
                                is_flat = false;
                            } else {
                                if((init_tick.lasttime + i) % 60 === 0) {
                                    var chance_of_flat = Math.round(Math.random() * 10);
                                    if(chance_of_flat <= 4) {
                                        if(is_flat) {
                                            recovery_offset = 60 * flats_in_a_row;
                                        }
                                        is_flat = false;
                                    } else {
                                        if(!is_flat) {
                                            flat_init_point = i;
                                            flats_in_a_row = 0;
                                        }
                                        is_flat = true;
                                        flats_in_a_row++;
                                    }
                                }
                            }
                            var current_median_point;
                            var current_time_offset;
                            if(is_flat) {
                                current_time_offset = flat_init_point;
                            } else {
                                if(recovery_offset !== 0) {
                                    recovery_offset -= 1 * flats_in_a_row;
                                }
                                current_time_offset = i - recovery_offset;
                            }
                            if(price_diff < 0) {
                                current_median_point = {x: (init_tick.lasttime + i) * 1000, y: ((init_tick.ask + init_tick.bid) / 2) + (average_change_by_sec * current_time_offset)};
                            } else {
                                current_median_point = {x: (init_tick.lasttime + i) * 1000, y: ((init_tick.ask + init_tick.bid) / 2) - (average_change_by_sec * current_time_offset)};
                            }
                            median_spline.middle.push(current_median_point);

                            var last_tick = all_changed_ticks[all_changed_ticks.length - 1];
                            var current_price_diff = current_median_point.y - last_tick.y;
                            var new_tick = {x: (init_tick.lasttime + i) * 1000, y: last_tick.y};
                            if(current_price_diff > 0) {
                                var range = Math.round(Math.random() * 1000);
                                if(range >= 0 && range <= 700) {
                                    max_distance = current_median_point.y + abs_price_diff * 0.003;
                                    min_distance = current_median_point.y + abs_price_diff * 0.001;
                                } else if(range > 700 && range <= 900) {
                                    max_distance = current_median_point.y + abs_price_diff * 0.013;
                                    min_distance = current_median_point.y + abs_price_diff * 0.008;
                                } else if(range > 900 && range <= 980) {
                                    max_distance = current_median_point.y + abs_price_diff * 0.008;
                                    min_distance = current_median_point.y + abs_price_diff * 0.005;
                                } else {
                                    max_distance = current_median_point.y + abs_price_diff * 0.005;
                                    min_distance = current_median_point.y + abs_price_diff * 0.003;
                                }
                            } else {
                                var range = Math.round(Math.random() * 1000);
                                if(range >= 0 && range <= 700) {
                                    max_distance = current_median_point.y - abs_price_diff * 0.003;
                                    min_distance = current_median_point.y - abs_price_diff * 0.001;
                                } else if(range > 700 && range <= 900) {
                                    max_distance = current_median_point.y - abs_price_diff * 0.013;
                                    min_distance = current_median_point.y - abs_price_diff * 0.008;
                                } else if(range > 900 && range <= 980) {
                                    max_distance = current_median_point.y - abs_price_diff * 0.008;
                                    min_distance = current_median_point.y - abs_price_diff * 0.005;
                                } else {
                                    max_distance = current_median_point.y - abs_price_diff * 0.005;
                                    min_distance = current_median_point.y - abs_price_diff * 0.003;
                                }
                            }
                            new_tick.y = (Math.random() * (max_distance - min_distance) + min_distance);
                            all_changed_ticks.push(new_tick);
                        }
                        last_tick = all_changed_ticks[all_changed_ticks.length - 1];
                        var new_tick = {x: (init_tick.lasttime + time_to_change + 5) * 1000, y: price_to_change};
                        all_changed_ticks.push(new_tick);
                        chart.series[1].setData(all_changed_ticks, true);
                        chart.series[2].setData(median_spline.middle, true);
                    }
                    if(init_data && data_inited && !end_changes) {
                        if(tick.lasttime >= (init_tick.lasttime + time_to_change + 5)) {
                            var last_tick = all_changed_ticks[all_changed_ticks.length - 1];
                            var current_price_diff = last_tick.y - ((tick.ask + tick.bid) / 2);
                            var abs_current_price_diff = Math.abs(current_price_diff);
                            if(!init_second_part) {
                                init_second_part = true;
                                end_init_tick = last_tick;
                                max_possible_diff_to_end = Math.abs(last_tick.y - ((tick.ask + tick.bid) / 2)) * 0.10 ;
                                average_change_by_sec = abs_current_price_diff / time_to_end;
                            }
                            if(abs_current_price_diff <= max_possible_diff_to_end) {
                                end_changes = true;
                            } else {
                                var current_median_point;
                                if(current_price_diff > 0) {
                                    current_median_point = {x: tick.lasttime * 1000, y: (end_init_tick.y - (average_change_by_sec * (tick.lasttime - (end_init_tick.x / 1000))))};
                                } else {
                                    current_median_point = {x: tick.lasttime * 1000, y: (end_init_tick.y + (average_change_by_sec * (tick.lasttime - (end_init_tick.x / 1000))))};
                                }
                                var new_tick = {x: (tick.lasttime + i) * 1000, y: last_tick.y};
                                if(current_price_diff > 0) {
                                    var range = Math.round(Math.random() * 1000);
                                    if(range >= 0 && range <= 700) {
                                        max_distance = current_median_point.y + abs_current_price_diff * 0.003;
                                        min_distance = current_median_point.y + abs_current_price_diff * 0.001;
                                    } else if(range > 700 && range <= 900) {
                                        max_distance = current_median_point.y + abs_current_price_diff * 0.013;
                                        min_distance = current_median_point.y + abs_current_price_diff * 0.008;
                                    } else if(range > 900 && range <= 980) {
                                        max_distance = current_median_point.y + abs_current_price_diff * 0.008;
                                        min_distance = current_median_point.y + abs_current_price_diff * 0.005;
                                    } else {
                                        max_distance = current_median_point.y + abs_current_price_diff * 0.005;
                                        min_distance = current_median_point.y + abs_current_price_diff * 0.003;
                                    }
                                } else {
                                    var range = Math.round(Math.random() * 1000);
                                    if(range >= 0 && range <= 700) {
                                        max_distance = current_median_point.y - abs_current_price_diff * 0.003;
                                        min_distance = current_median_point.y - abs_current_price_diff * 0.001;
                                    } else if(range > 700 && range <= 900) {
                                        max_distance = current_median_point.y - abs_current_price_diff * 0.013;
                                        min_distance = current_median_point.y - abs_current_price_diff * 0.008;
                                    } else if(range > 900 && range <= 980) {
                                        max_distance = current_median_point.y - abs_current_price_diff * 0.008;
                                        min_distance = current_median_point.y - abs_current_price_diff * 0.005;
                                    } else {
                                        max_distance = current_median_point.y - abs_current_price_diff * 0.005;
                                        min_distance = current_median_point.y - abs_current_price_diff * 0.003;
                                    }
                                }
                                new_tick.y = (Math.random() * (max_distance - min_distance) + min_distance);
                                all_changed_ticks.push(new_tick);
                                median_spline.middle.push(current_median_point);
                                chart.series[1].setData(all_changed_ticks, true);
                                chart.series[2].setData(median_spline.middle, true);
                            }
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
