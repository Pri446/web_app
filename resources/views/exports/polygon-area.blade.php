<table>
    <thead>
        <tr>
            <th>{{ __('Polygon Name') }}</th>
            <th>{{ __('Area (km²)') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($polygonAreas as $row)
            <tr>
                <td>{{ $row['polygon_name'] }}</td>
                <td>{{ number_format($row['area_km2'], 6) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
