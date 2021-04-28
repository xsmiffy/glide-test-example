<!DOCTYPE html>
<html lang="en">
<head>
    <title>Gas Calorific Values</title>
    <link rel="stylesheet" text="text/css" href="{{ asset('css/app.css') }}">
</head>
<body>

    <div class="container">
        <h1 class="text-4xl my-8">Calorific Values</h1>

        <form method="GET" action="/" class="my-8">
            @csrf
            <div>
                <label for="areas">Select an area:</label>
                <select name="areas" id="areas">
                    <option value="all">All</option>
                    @foreach($areas as $area)
                    <option value="{{ $area['id'] }}"
                            @if ($selectedArea && $selectedArea == $area['id'])
                            selected
                            @endif
                    >{{ $area['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="start-date">Start date:</label>
                <input type="date" id="start-date" name="start-date" value="{{ $selectedStart ?? '' }}">
            </div>
            <div>
                <label for="end-date">End date:</label>
                <input type="date" id="end-date" name="end-date" value="{{ $selectedEnd ?? '' }}">
            </div>
            <div>
                <input type="submit" class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded">
            </div>
        </form>

        <h2 class="text-2xl">Average Calorific Value: {{ $averageCV }}</h2>

        <table>
            <thead>
                <tr>
                    <th>Applicable For</th>
                    <th>Area</th>
                    <th>Value</th>
                </tr>
            </thead>

            <tbody>
                @foreach($calorificValues as $value)
                <tr>
                    <td>{{$value->applicable_for}}</td>
                    <td>{{$value->name}}</td>
                    <td>{{$value->value}}</td>
                </tr>
                @endforeach
            </tbody>

        </table>

        {{ $calorificValues->links() }}
    </div>
</body>
</html>

