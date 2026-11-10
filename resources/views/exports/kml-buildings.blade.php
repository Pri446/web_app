@foreach($allPolygonData as $block)
    <h2>{{ __('Polygon Name:') }} {{ $block['polygon_name'] }}</h2>
    <table border="1">
    <thead>
    <tr>
        <th align="right" width="20"><h1><strong>{{__('Structure Type')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Buildings')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Sewer Network')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Drain Network')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Septic Tank')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Pit/Holding Tank')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Onsite Treatment')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Composting Toilet')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Water Body')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Open Ground')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Community Toilet')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Open Defecation')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Household Served')}}</strong></h1></th>
        <th align="right" width="20"><h1><strong>{{__('Population Served')}}</strong></h1></th>

    </tr>
    </thead>
    <tbody>
     @foreach($block['rows'] as $row)
        <tr>
            <td>{{ $row['StructureType'] }}</td>
            <td>{{ $row['Buildings'] }}</td>
            <td>{{ $row['Sewer Network'] }}</td>
            <td>{{ $row['Drain Network'] }}</td>
            <td>{{ $row['Septic Tank'] }}</td>
            <td>{{ $row['Pit / Holding Tank'] }}</td>
            <td>{{ $row['Onsite Treatment'] }}</td>
            <td>{{ $row['Composting Toilet'] }}</td>
            <td>{{ $row['Water Body'] }}</td>
            <td>{{ $row['Open Ground'] }}</td>
            <td>{{ $row['Community Toilet'] }}</td>
            <td>{{ $row['Open Defecation'] }}</td>
            <td>{{ $row['Household Served'] }}</td>
            <td>{{ $row['Population Served'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
 <br><br>
@endforeach
