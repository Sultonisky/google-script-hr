<section class="dashboard-section">
  <div class="dashboard-section__header">
    <h6>Headcount Karyawan</h6>
    <p>Total headcount <strong class="text-navy" id="headcount-total">{{ $headcountTotal ?? 0 }} karyawan</strong></p>
  </div>
<div class="row g-3">
  @foreach ($headcountCards ?? [] as $card)
    <div class="col-6 col-md-4 col-xl">
      <div class="stat-card" style="font: inherit;"
        aria-label="Lihat headcount per divisi dan departemen dari semua entitas">
        <div class="stat-icon {{ $card['icon'] }}"><i class="bi bi-building"></i></div>
        <div>
          <div class="stat-label">{{ $card['code'] }}</div>
          <div class="stat-value text-navy" id="headcount-{{ $card['code'] }}">{{ $card['count'] }}</div>
          <div class="small text-muted headcount-entity-name">{{ $card['name'] }}</div>
        </div>
      </div>
    </div>
  @endforeach
</div>
</section>

<section class="dashboard-section headcount-breakdown" aria-labelledby="headcount-breakdown-title">
  <div class="dashboard-section__header">
    <h6 id="headcount-breakdown-title">Headcount Karyawan by Divisi &amp; Departemen</h6>
    <p>Jumlah karyawan aktif dari semua entitas. Buka divisi untuk melihat departemen di dalamnya.</p>
  </div>

  <div class="headcount-breakdown__list" id="dashboardHeadcountAccordion">
    @php
      $divisionColumns = [[], []];
      foreach ($divisionDepartmentGroups ?? [] as $index => $division) {
          $divisionColumns[$index % 2][] = ['index' => $index, 'division' => $division];
      }
    @endphp
    @if (count($divisionDepartmentGroups ?? []) === 0)
      <div class="headcount-breakdown__empty">Belum ada data divisi.</div>
    @endif
    @foreach ($divisionColumns as $column)
      @if ($column === [])
        @continue
      @endif
      <div class="headcount-breakdown__column">
      @foreach ($column as $item)
      @php
        $index = $item['index'];
        $division = $item['division'];
        $collapseId = 'dashboard-headcount-collapse-' . $index;
      @endphp
      <article class="headcount-group" data-division="{{ $division['name'] }}" data-count="{{ $division['count'] }}">
        <h3 class="headcount-group__heading">
          <button class="headcount-group__toggle collapsed" type="button"
            data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}"
            aria-expanded="false" aria-controls="{{ $collapseId }}">
            <span class="headcount-group__name">{{ $division['name'] }}</span>
            <span class="headcount-group__count">{{ $division['count'] }}</span>
            <i class="bi bi-chevron-down headcount-group__chevron" aria-hidden="true"></i>
          </button>
        </h3>
        <div id="{{ $collapseId }}" class="collapse" data-bs-parent="#dashboardHeadcountAccordion">
          <div class="headcount-group__body">
            <p class="headcount-group__label">Departemen</p>
            @forelse ($division['departments'] ?? [] as $department)
              @if ($loop->first)
                <div class="headcount-dept-list">
              @endif
                <div class="headcount-dept" data-department="{{ $department['name'] }}" data-count="{{ $department['count'] }}" data-division-parent="{{ $division['name'] }}">
                  <i class="bi bi-people-fill" aria-hidden="true"></i>
                  <span class="headcount-dept__name">{{ $department['name'] }}</span>
                  <span class="headcount-dept__leader" aria-hidden="true"></span>
                  <span class="headcount-dept__count">{{ $department['count'] }}</span>
                </div>
              @if ($loop->last)
                </div>
              @endif
            @empty
              <div class="headcount-breakdown__empty">Belum ada departemen di divisi ini.</div>
            @endforelse
          </div>
        </div>
      </article>
      @endforeach
      </div>
    @endforeach
  </div>
</section>

