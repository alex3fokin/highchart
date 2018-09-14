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
        <div id="chart-container" style="width:100%; height:400px;"></div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.3.1.min.js"
        integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>

<script src="https://code.highcharts.com/stock/highstock.js"></script>
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
                }
            ]
        });
        var all_ticks = [];
        var all_changed_ticks = [];
        var prev_tick = null;
        setInterval(function() {
            $.ajax({
                url: 'https://quotes.instaforex.com/api/quotesTick?m=json&q=eurusd',
                type: 'GET',
                success:function(data) {
                    console.log(data[0]);
                    var tick = data[0];
                    if(prev_tick) {
                        if(tick.lasttime === prev_tick.lasttime) {
                            all_ticks[all_ticks.length-1] = {x:tick.lasttime * 1000, y:(tick.ask + tick.bid) * 0.5};
                            all_changed_ticks[all_changed_ticks.length - 1] = {x:tick.lasttime * 1000, y:(tick.ask + tick.bid) * 0.5 + 0.001};
                        } else {
                            all_ticks.push({x:tick.lasttime * 1000, y:(tick.ask + tick.bid) * 0.5});
                            all_changed_ticks.push({x:tick.lasttime * 1000, y:(tick.ask + tick.bid) * 0.5 + 0.001});
                        }
                    } else {
                        all_ticks.push({x:tick.lasttime * 1000, y:(tick.ask + tick.bid) * 0.5});
                        all_changed_ticks.push({x:tick.lasttime * 1000, y:(tick.ask + tick.bid) * 0.5 + 0.001});
                    }
                    console.log(tick.lasttime);
                    console.log((tick.ask + tick.bid) * 0.5);
                    chart.series[0].setData(all_ticks, true);
                    chart.series[1].setData(all_changed_ticks, true);
                    prev_tick = tick;
                },
                error:function(data){
                    console.log(data);
                }
            });
        },1000);
    });
</script>
</body>
</html>
