<div class="card mt-4" style="border-radius:12px;">
    <div class="card-header fw-bold">
        TPSA Risk Heatmap
    </div>

    <div class="card-body">
        <table class="table table-bordered text-center align-middle">
            <thead>
                <tr>
                    <th rowspan="2">Inherent Risk</th>
                    <th colspan="3">Security Control Effectiveness</th>
                </tr>
                <tr>
                    <th>Sangat Memadai</th>
                    <th>Cukup Memadai</th>
                    <th>Kurang Memadai</th>
                </tr>
            </thead>
            <tbody>
                @foreach(['high','medium','low'] as $risk)
                    <tr>
                        <th class="text-capitalize">{{ $risk }}</th>

                        @foreach(['Sangat Memadai','Cukup Memadai','Kurang Memadai'] as $label)
                            @php $items = $matrix[$risk][$label] ?? []; @endphp
                            <td>
                                <strong>{{ count($items) }}</strong>
                                <div class="small text-muted">
                                    @foreach($items as $item)
                                        {{ $item['company'] }}<br>
                                    @endforeach
                                </div>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
