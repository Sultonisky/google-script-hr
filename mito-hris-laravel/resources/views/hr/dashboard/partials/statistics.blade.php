<!-- partials/Statistics.html — STAT CARDS (Dashboard page) (1:1 from GAS) -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-4 col-xl-2">
    <a href="{{ route('hr.recruitment.index') }}" class="stat-card text-decoration-none">
      <div class="stat-icon bg-blue"><i class="bi bi-people-fill"></i></div>
      <div>
        <div class="stat-label">Total Kandidat</div>
        <div class="stat-value text-navy" id="statTotal">{{ $stats['total'] ?? 0 }}</div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <a href="{{ route('hr.recruitment.index', ['status' => 'New']) }}" class="stat-card text-decoration-none">
      <div class="stat-icon bg-gold"><i class="bi bi-hourglass-split"></i></div>
      <div>
        <div class="stat-label">Pending</div>
        <div class="stat-value text-navy" id="statPending">{{ $stats['pending'] ?? 0 }}</div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <a href="{{ route('hr.recruitment.accepted') }}" class="stat-card text-decoration-none">
      <div class="stat-icon bg-green">
        <i class="bi bi-check-circle-fill"></i>
      </div>
      <div>
        <div class="stat-label">Accepted</div>
        <div class="stat-value text-navy" id="statAccepted">{{ $stats['accepted'] ?? 0 }}</div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <a href="{{ route('hr.recruitment.hold') }}" class="stat-card text-decoration-none">
      <div class="stat-icon bg-navy">
        <i class="bi bi-pause-circle-fill"></i>
      </div>
      <div>
        <div class="stat-label">Hold</div>
        <div class="stat-value text-navy" id="statHold">{{ $stats['hold'] ?? 0 }}</div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <a href="{{ route('hr.recruitment.blacklist') }}" class="stat-card text-decoration-none">
      <div class="stat-icon bg-red">
        <i class="bi bi-slash-circle-fill"></i>
      </div>
      <div>
        <div class="stat-label">Blacklist</div>
        <div class="stat-value text-navy" id="statBlacklist">{{ $stats['blacklist'] ?? 0 }}</div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <a href="{{ route('hr.probation.index') }}" class="stat-card text-decoration-none">
      <div class="stat-icon bg-navy">
        <i class="bi bi-hourglass-split"></i>
      </div>
      <div>
        <div class="stat-label">Probation</div>
        <div class="stat-value text-navy" id="statProbation">{{ $stats['probation'] ?? 0 }}</div>
      </div>
    </a>
  </div>
</div>
