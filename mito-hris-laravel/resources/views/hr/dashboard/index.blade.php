@extends('layouts.hr')

@section('title', 'Dashboard - MITO HRIS')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Ringkasan rekrutmen & data kandidat')

@section('content')
<section class="page-section active" id="pageDashboard">
  @include('hr.dashboard.partials.statistics')
  @include('hr.dashboard.partials.charts')
</section>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('chartMonthly');
    if (ctx) {
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: {!! json_encode($monthlyLabels ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']) !!},
          datasets: [{
            label: 'Jumlah Pelamar',
            data: {!! json_encode($monthlyData ?? [12, 19, 3, 5, 2, 3, 15, 22, 18, 25, 20, 30]) !!},
            borderColor: '#eb1c24',
            backgroundColor: 'rgba(235, 28, 36, 0.1)',
            fill: true,
            tension: 0.35,
            borderWidth: 2.5,
            pointRadius: 4,
            pointBackgroundColor: '#eb1c24'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false }
          },
          scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
          }
        }
      });
    }
  });
</script>
@endsection
