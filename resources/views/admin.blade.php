<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
{{-- <meta name="csrf-token" content="{{ csrf_token() }}"> --}}

        <title>{{ env('APP_NAME') ?? __("Load") }}</title>

        <link rel="stylesheet" href="{{ mix('css/app.css') }}">
        {{--  <link rel="stylesheet" type="text/css" href="{{ url('css/app.css') }}">  --}}
    </head>
    <body>
        <div id="load-app"></div>
        <script src="{{ mix('/js/app.js') }}"></script>
        {{--  <script type="text/javascript" src="{{ url('js/app.js') }}"></script>  --}}
    </body>
</html>
