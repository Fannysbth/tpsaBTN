@extends('layouts.app')

@section('title', 'Detail Assessment')

@section('content')
    <h1>Detail Assessment</h1>

    <p><strong>Perusahaan:</strong> {{ $assessment->company_name }}</p>
    <p><strong>Tanggal:</strong> {{ $assessment->assessment_date->format('d M Y') }}</p>
    <p><strong>Total Skor:</strong> {{ $assessment->total_score }}</p>
    <p><strong>Level Risiko:</strong> {{ strtoupper($assessment->risk_level) }}</p>

    <hr>

    <h2>Skor per Kategori</h2>

    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>Kategori</th>
                <th>Skor</th>
                <th>Bobot</th>
                <th>Skor Tertimbang</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($assessment->category_scores as $category)
                <tr>
                    <td>{{ $category['name'] }}</td>
                    <td>{{ $category['score'] }}</td>
                    <td>{{ $category['weight'] }}</td>
                    <td>{{ $category['weighted_score'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <hr>

    <h2>Catatan</h2>
    <p>{{ $assessment->notes }}</p>

    <br>

    <a href="{{ route('dashboard.index') }}">← Kembali ke Dashboard</a>
@endsection
