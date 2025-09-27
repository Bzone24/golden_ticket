@php
    $indices = range(0, 9);
    $tq_a = $tq_b = $tq_c = 0;
    $a_amounts = $b_amounts = $c_amounts = [];
    foreach ($indices as $i) {
        $tq_a += $drawDetail->totalAqty($i,$user->id??null);
        $a_amounts[] = $drawDetail->totalAqty($i,$user->id??null);
    }
    foreach ($indices as $i) {
        $tq_b += $drawDetail->totalBqty($i,$user->id??null);
        $b_amounts[] = $drawDetail->totalBqty($i,$user->id??null);
    }
    foreach ($indices as $i) {
        $tq_c += $drawDetail->totalCqty($i,$user->id??null);
        $c_amounts[] = $drawDetail->totalCqty($i,$user->id??null);
    }

    $claim_a_amt = $drawDetail->claim_a ? $a_amounts[$drawDetail->claim_a] : 0;
    $claim_b_amt = $drawDetail->claim_b ? $b_amounts[$drawDetail->claim_b] : 0;
    $claim_c_amt = $drawDetail->claim_c ? $c_amounts[$drawDetail->claim_c] : 0;

    $a_pl = $tq_a * 11 - $claim_a_amt * 100;
    $b_pl = $tq_b * 11 - $claim_b_amt * 100;
    $c_pl = $tq_c * 11 - $claim_c_amt * 100;
@endphp

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Option</th>
            @foreach ($indices as $i)
                <th>{{ $i }}</th>
            @endforeach
            <th>TQ</th>
            <th>Claim Q</th>
            <th>P & L</th>
            <th>Result</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="option-a">A</td>
            @foreach ($indices as $i)
                <td @class([
                    'bg-danger text-white' =>
                        $drawDetail->claim_a == $i &&
                        $drawDetail->totalAqty($i,$user->id??null) == $claim_a_amt &&
                        $drawDetail->totalAqty($i,$user->id??null) != 0,
                ])>{{ $drawDetail->totalAqty($i,$user->id??null) }}</td>
            @endforeach
            <td>{{ $tq_a }}</td>
            <td>{{ $claim_a_amt }}</td>
            <td @class([
                'bg-danger text-white' => $a_pl < 0,
                'bg-success text-white' => $a_pl > 0,
            ])>{{ $a_pl }}</td>
            <td>{{ $drawDetail->claim_a ?? 'N/A' }}</td>
        </tr>

        <tr>
            <td class="option-b">B</td>
            @foreach ($indices as $i)
                <td @class([
                    'bg-danger text-white' =>
                        $drawDetail->claim_b == $i &&
                        $drawDetail->totalBqty($i,$user->id??null) == $claim_b_amt &&
                        $drawDetail->totalBqty($i,$user->id??null) != 0,
                ])>{{ $drawDetail->totalBqty($i,$user->id??null) }}</td>
            @endforeach
            <td>{{ $tq_b }}</td>
            <td>{{ $claim_b_amt }}</td>
            <td @class([
                'bg-danger text-white' => $b_pl < 0,
                'bg-success text-white' => $b_pl > 0,
            ])>{{ $b_pl }}</td>
            <td>{{ $drawDetail->claim_b ?? 'N/A' }}</td>
        </tr>

        <tr>
            <td class="option-c">C</td>
            @foreach ($indices as $i)
                <td @class([
                    'bg-danger text-white' =>
                        $drawDetail->claim_c == $i &&
                        $drawDetail->totalCqty($i,$user->id??null) == $claim_c_amt &&
                        $drawDetail->totalCqty($i,$user->id??null) != 0,
                ])>{{ $drawDetail->totalCqty($i,$user->id??null) }}</td>
            @endforeach
            <td>{{ $tq_c }}</td>
            <td>{{ $claim_c_amt }}</td>
            <td @class([
                'bg-danger text-white' => $c_pl < 0,
                'bg-success text-white' => $c_pl > 0,
            ])>{{ $c_pl }}</td>
            <td>{{ $drawDetail->claim_c ?? 'N/A' }}</td>
        </tr>

        <tr>
            <td colspan="11" style="font-size: larger; font-weight: bold; color: red">Total</td>
            <td class="text-success">{{ $tq_a + $tq_b + $tq_c }}</td>
            <td class="text-warning">{{ $claim_a_amt + $claim_b_amt + $claim_c_amt }}</td>
            <td>{{ $a_pl + $b_pl + $c_pl }}</td>
            <td></td>
        </tr>
    </tbody>
</table>
