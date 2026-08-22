<!-- partials/ProbationModals.html — PROBATION EVALUATION MODAL (1:1 from GAS) -->
<div class="modal fade" id="probationEvalModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:16px">
      <div class="modal-header" style="background:#7c3aed;border-radius:16px 16px 0 0">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-clipboard-check text-white fs-5"></i>
          <h6 class="modal-title mb-0 text-white fw-bold">Probation Employee Evaluation</h6>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form action="" method="POST" id="probationEvalForm">
        @csrf
        <div class="modal-body p-4">
          <input type="hidden" name="employee_id" id="evalEmployeeId" />

          <!-- Step 1: Search karyawan probation (live search, 1:1 GAS) -->
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">
              <i class="bi bi-search me-1"></i>Search Employee
              <span class="text-muted fw-normal">(Status: Probation)</span>
            </label>
            <div class="position-relative">
              <input type="text" class="form-control" id="evalEmpSearch"
                placeholder="Type name or Employee ID..." autocomplete="off"
                style="font-size:13px;padding-right:36px" oninput="handleEvalEmpSearch(this.value)" />
              <i class="bi bi-x-circle-fill position-absolute" id="evalEmpSearchClear"
                style="right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:#aaa;display:none"
                onclick="clearEvalEmpSearch()"></i>
            </div>
            <div id="evalEmpDropdown" class="border rounded-3 mt-1 shadow-sm"
              style="display:none;max-height:200px;overflow-y:auto;background:#fff;z-index:9999;position:relative">
            </div>
          </div>

          <!-- Step 2: Info karyawan terpilih + form evaluasi (1:1 GAS evalEmpPreview) -->
          <div id="evalEmpPreview" style="display:none">
            <div class="p-3 rounded-3 mb-4" style="background:#f5f3ff;border:1px solid #ddd6fe">
              <div class="d-flex align-items-center gap-3">
                <div id="evalEmpAvatar"
                  style="width:44px;height:44px;font-size:16px;flex-shrink:0;background:#7c3aed;color:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800">?</div>
                <div class="flex-grow-1">
                  <div class="fw-bold" id="evalEmpName" style="font-size:15px">-</div>
                  <div class="text-muted" style="font-size:12px">
                    <span id="evalEmpPosition">-</span> &bull; <span id="evalEmpDept">-</span>
                  </div>
                </div>
                <div class="text-end flex-shrink-0" style="font-size:11.5px">
                  <div class="text-muted">Employee ID</div>
                  <div class="fw-semibold text-purple" id="evalEmpIdDisp">-</div>
                  <div class="text-muted mt-1">Contract Until</div>
                  <div class="fw-semibold" id="evalEmpContractEnd">-</div>
                </div>
              </div>
            </div>

            <!-- Penilaian 5 kriteria (1:1 GAS) -->
            <p class="fw-bold mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#7c3aed">
              <i class="bi bi-star-fill me-1"></i>Scoring (1–10 per criterion)
            </p>
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:13px">
                  Work Performance <span class="text-danger">*</span>
                  <span class="badge rounded-pill ms-1" id="badgePerformance" style="background:#7c3aed;font-size:11px">5</span>
                </label>
                <input type="range" class="form-range eval-score-range" id="evalScorePerformance" name="score_performance" min="1" max="10" step="1" value="5" oninput="updateEvalBadge('badgePerformance',this.value);recalcEvalAvg()" />
                <div class="d-flex justify-content-between" style="font-size:10px;color:#9ca3af;margin-top:-2px">
                  <span>1 Poor</span><span>5 Average</span><span>10 Excellent</span>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:13px">
                  Discipline <span class="text-danger">*</span>
                  <span class="badge rounded-pill ms-1" id="badgeDiscipline" style="background:#7c3aed;font-size:11px">5</span>
                </label>
                <input type="range" class="form-range eval-score-range" id="evalScoreDiscipline" name="score_discipline" min="1" max="10" step="1" value="5" oninput="updateEvalBadge('badgeDiscipline',this.value);recalcEvalAvg()" />
                <div class="d-flex justify-content-between" style="font-size:10px;color:#9ca3af;margin-top:-2px">
                  <span>1 Poor</span><span>5 Average</span><span>10 Excellent</span>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:13px">
                  Communication <span class="text-danger">*</span>
                  <span class="badge rounded-pill ms-1" id="badgeCommunication" style="background:#7c3aed;font-size:11px">5</span>
                </label>
                <input type="range" class="form-range eval-score-range" id="evalScoreCommunication" name="score_communication" min="1" max="10" step="1" value="5" oninput="updateEvalBadge('badgeCommunication',this.value);recalcEvalAvg()" />
                <div class="d-flex justify-content-between" style="font-size:10px;color:#9ca3af;margin-top:-2px">
                  <span>1 Poor</span><span>5 Average</span><span>10 Excellent</span>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:13px">
                  Initiative &amp; Creativity <span class="text-danger">*</span>
                  <span class="badge rounded-pill ms-1" id="badgeInitiative" style="background:#7c3aed;font-size:11px">5</span>
                </label>
                <input type="range" class="form-range eval-score-range" id="evalScoreInitiative" name="score_initiative" min="1" max="10" step="1" value="5" oninput="updateEvalBadge('badgeInitiative',this.value);recalcEvalAvg()" />
                <div class="d-flex justify-content-between" style="font-size:10px;color:#9ca3af;margin-top:-2px">
                  <span>1 Poor</span><span>5 Average</span><span>10 Excellent</span>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold" style="font-size:13px">
                  Teamwork <span class="text-danger">*</span>
                  <span class="badge rounded-pill ms-1" id="badgeTeamwork" style="background:#7c3aed;font-size:11px">5</span>
                </label>
                <input type="range" class="form-range eval-score-range" id="evalScoreTeamwork" name="score_teamwork" min="1" max="10" step="1" value="5" oninput="updateEvalBadge('badgeTeamwork',this.value);recalcEvalAvg()" />
                <div class="d-flex justify-content-between" style="font-size:10px;color:#9ca3af;margin-top:-2px">
                  <span>1 Poor</span><span>5 Average</span><span>10 Excellent</span>
                </div>
              </div>
              <!-- Average Score auto (1:1 GAS) -->
              <div class="col-12 col-md-6 d-flex align-items-end">
                <div class="w-100 p-3 rounded-3 text-center" style="background:#f5f3ff;border:2px solid #7c3aed">
                  <div class="text-muted fw-semibold" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Average Score</div>
                  <div class="fw-bold mt-1" id="evalAvgScore" style="font-size:28px;color:#7c3aed;line-height:1">5.0</div>
                  <input type="hidden" name="score" id="evalAvgScoreHidden" value="5.0" />
                  <div style="font-size:11px;color:#9ca3af">out of 10</div>
                </div>
              </div>
            </div>

            <!-- Score Guidelines (1:1 GAS) -->
            <div class="alert py-2 px-3 mb-3" style="background:#fffbeb;border:1px solid #fde68a;font-size:12px">
              <div class="d-flex align-items-start gap-2">
                <i class="bi bi-info-circle-fill text-warning" style="font-size:14px;margin-top:2px"></i>
                <div>
                  <strong>Score Guidelines:</strong>
                  <ul class="mb-0 mt-1 ps-3" style="line-height:1.6">
                    <li><strong style="color:#166534">≥ 7.0</strong> = Eligible for <strong>Pass → Permanent (PKWTT)</strong></li>
                    <li><strong style="color:#d97706">5.0 - 6.9</strong> = Can choose <strong>Extend</strong> or <strong>Terminate</strong></li>
                    <li><strong style="color:#991b1b">&lt; 5.0</strong> = Can only choose <strong>Extend</strong> or <strong>Terminate</strong></li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Decision (1:1 GAS visual radio options) -->
            <p class="fw-bold mb-2" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#7c3aed">
              <i class="bi bi-check2-circle me-1"></i>Decision <span class="text-danger">*</span>
            </p>
            <input type="hidden" name="decision" id="evalDecisionValue" value="" />
            <div class="d-flex flex-column gap-2 mb-4" id="evalDecisionOptions">
              <!-- Pass → Permanent -->
              <div class="eval-decision-opt p-3 rounded-3" data-value="Lulus → Karyawan Tetap"
                style="border:2px solid #e5e7eb;cursor:pointer;transition:border-color .15s,background .15s"
                onclick="selectEvalDecision(this)">
                <div class="d-flex align-items-center gap-3">
                  <div class="eval-dec-dot" style="width:20px;height:20px;border-radius:50%;border:2px solid #d1d5db;flex-shrink:0;display:flex;align-items:center;justify-content:center">
                    <div style="width:8px;height:8px;border-radius:50%;background:transparent"></div>
                  </div>
                  <div>
                    <div class="fw-semibold" style="font-size:13px">
                      <i class="bi bi-trophy-fill me-1 text-success"></i>Pass → Permanent Employee (PKWTT)
                    </div>
                    <div class="text-muted" style="font-size:11.5px">Employee is promoted to permanent status. <strong>SK Pengangkatan Tetap (PDF)</strong> will be generated.</div>
                  </div>
                </div>
              </div>
              <!-- Terminate → Paklaring -->
              <div class="eval-decision-opt p-3 rounded-3" data-value="Tidak Lolos → Putus Kontrak (Paklaring)"
                style="border:2px solid #e5e7eb;cursor:pointer;transition:border-color .15s,background .15s"
                onclick="selectEvalDecision(this)">
                <div class="d-flex align-items-center gap-3">
                  <div class="eval-dec-dot" style="width:20px;height:20px;border-radius:50%;border:2px solid #d1d5db;flex-shrink:0;display:flex;align-items:center;justify-content:center">
                    <div style="width:8px;height:8px;border-radius:50%;background:transparent"></div>
                  </div>
                  <div>
                    <div class="fw-semibold" style="font-size:13px">
                      <i class="bi bi-x-octagon-fill me-1 text-danger"></i>Not Pass → End Contract (Paklaring)
                    </div>
                    <div class="text-muted" style="font-size:11.5px">Employment terminated. <strong>Certificate of Employment / Paklaring (PDF)</strong> will be generated.</div>
                  </div>
                </div>
              </div>
              <!-- Extend → Re-evaluation -->
              <div class="eval-decision-opt p-3 rounded-3" data-value="Tidak Lolos → Perpanjang Probation (Evaluasi Ulang)"
                style="border:2px solid #e5e7eb;cursor:pointer;transition:border-color .15s,background .15s"
                onclick="selectEvalDecision(this)">
                <div class="d-flex align-items-center gap-3">
                  <div class="eval-dec-dot" style="width:20px;height:20px;border-radius:50%;border:2px solid #d1d5db;flex-shrink:0;display:flex;align-items:center;justify-content:center">
                    <div style="width:8px;height:8px;border-radius:50%;background:transparent"></div>
                  </div>
                  <div>
                    <div class="fw-semibold" style="font-size:13px">
                      <i class="bi bi-arrow-repeat me-1 text-warning"></i>Not Pass → Extend Probation (Re-evaluation)
                    </div>
                    <div class="text-muted" style="font-size:11.5px">Probation extended for future re-evaluation. <em>(No PDF generated)</em></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Extension detail (conditional, 1:1 GAS) -->
            <div id="evalExtendSection" style="display:none">
              <p class="fw-bold mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#d97706">
                <i class="bi bi-calendar-range me-1"></i>Extension Details
              </p>
              <div class="row g-3 mb-4">
                <div class="col-md-4">
                  <label class="form-label fw-semibold" style="font-size:13px">Extension Duration <span class="text-danger">*</span></label>
                  <select class="form-select form-select-sm" name="extension_duration" id="evalExtDuration">
                    <option value="">— Select Duration —</option>
                    <option value="1 Bulan">1 Month</option>
                    <option value="2 Bulan">2 Months</option>
                    <option value="3 Bulan">3 Months</option>
                    <option value="6 Bulan">6 Months</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-semibold" style="font-size:13px">New Contract Start <span class="text-danger">*</span></label>
                  <input type="date" class="form-control form-control-sm" name="extension_start" id="evalExtStart" />
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-semibold" style="font-size:13px">New Contract End</label>
                  <input type="date" class="form-control form-control-sm" name="extension_end" id="evalExtEnd" />
                  <div class="form-text" style="font-size:11px">Auto-calculated from duration</div>
                </div>
              </div>
            </div>

            <!-- Evaluator Notes -->
            <div class="mb-0">
              <label class="form-label fw-semibold" style="font-size:13px">Evaluator Notes <span class="text-muted fw-normal">(optional)</span></label>
              <textarea class="form-control form-control-sm" name="notes" id="evalCatatan" rows="2"
                placeholder="e.g. Performance meets high standard, recommended for permanent..."></textarea>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#7c3aed" id="btnConfirmProbationEval" disabled>
            <span id="btnEvalText"><i class="bi bi-clipboard-check me-1"></i>Save Evaluation</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  window.__allEmployeesForEval = @json($probations ?? []);

  function handleEvalEmpSearch(query) {
    const q = (query || '').toLowerCase().trim();
    const dropdown = document.getElementById('evalEmpDropdown');
    const clearBtn = document.getElementById('evalEmpSearchClear');
    clearBtn.style.display = q ? 'block' : 'none';
    if (!q) { dropdown.style.display = 'none'; return; }

    const matched = (window.__allEmployeesForEval || []).filter(e => {
      const name = (e.fullName || '').toLowerCase();
      const id = (e.employeeId || '').toLowerCase();
      const pos = (e.jobPosition || '').toLowerCase();
      return name.includes(q) || id.includes(q) || pos.includes(q);
    }).slice(0, 8);

    if (matched.length === 0) {
      dropdown.innerHTML = '<div class="p-3 text-muted text-center" style="font-size:13px">Tidak ada karyawan probation yang cocok</div>';
      dropdown.style.display = 'block';
      return;
    }
    dropdown.innerHTML = matched.map(e => `
      <div class="p-2 border-bottom d-flex align-items-center gap-2" style="cursor:pointer;" onclick='selectEvalEmployee(${JSON.stringify(e).replace(/'/g, "&#39;")})'>
        <div style="width:32px;height:32px;background:#7c3aed;color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">${(e.fullName||'E').substring(0,2).toUpperCase()}</div>
        <div style="font-size:12.5px;">
          <div class="fw-semibold">${e.fullName||'-'}</div>
          <div class="text-muted" style="font-size:11px">${e.employeeId} &bull; ${e.jobPosition||'-'}</div>
        </div>
      </div>
    `).join('');
    dropdown.style.display = 'block';
  }

  function selectEvalEmployee(emp) {
    document.getElementById('evalEmpDropdown').style.display = 'none';
    document.getElementById('evalEmpSearch').value = `${emp.fullName} (${emp.employeeId})`;
    document.getElementById('evalEmployeeId').value = emp.employeeId;
    document.getElementById('probationEvalForm').action = `/hr/probation/${emp.employeeId}/evaluate`;

    document.getElementById('evalEmpName').textContent = emp.fullName || '-';
    document.getElementById('evalEmpPosition').textContent = emp.jobPosition || '-';
    document.getElementById('evalEmpDept').textContent = emp.department || '-';
    document.getElementById('evalEmpIdDisp').textContent = emp.employeeId;
    document.getElementById('evalEmpContractEnd').textContent = emp.endDateContract || '-';
    document.getElementById('evalEmpAvatar').textContent = (emp.fullName || 'E').substring(0,2).toUpperCase();

    document.getElementById('evalEmpPreview').style.display = 'block';
    // Reset scores
    ['evalScorePerformance','evalScoreDiscipline','evalScoreCommunication','evalScoreInitiative','evalScoreTeamwork'].forEach(id => {
      const el = document.getElementById(id); if(el) el.value = 5;
    });
    ['badgePerformance','badgeDiscipline','badgeCommunication','badgeInitiative','badgeTeamwork'].forEach(id => updateEvalBadge(id, 5));
    recalcEvalAvg();
    // Reset decision
    document.querySelectorAll('.eval-decision-opt').forEach(opt => { opt.style.borderColor='#e5e7eb'; opt.style.background=''; opt.querySelector('.eval-dec-dot div').style.background='transparent'; });
    document.getElementById('evalDecisionValue').value = '';
    document.getElementById('evalExtendSection').style.display = 'none';
    document.getElementById('btnConfirmProbationEval').disabled = true;
  }

  function clearEvalEmpSearch() {
    document.getElementById('evalEmpSearch').value = '';
    document.getElementById('evalEmpSearchClear').style.display = 'none';
    document.getElementById('evalEmpDropdown').style.display = 'none';
    document.getElementById('evalEmpPreview').style.display = 'none';
    document.getElementById('btnConfirmProbationEval').disabled = true;
    document.getElementById('evalEmployeeId').value = '';
  }

  function updateEvalBadge(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  }

  function recalcEvalAvg() {
    const ids = ['evalScorePerformance','evalScoreDiscipline','evalScoreCommunication','evalScoreInitiative','evalScoreTeamwork'];
    const vals = ids.map(id => parseInt(document.getElementById(id)?.value || 5));
    const avg = (vals.reduce((a,b) => a+b, 0) / vals.length).toFixed(1);
    document.getElementById('evalAvgScore').textContent = avg;
    document.getElementById('evalAvgScoreHidden').value = avg;
  }

  function selectEvalDecision(el) {
    // Reset all
    document.querySelectorAll('.eval-decision-opt').forEach(opt => {
      opt.style.borderColor = '#e5e7eb';
      opt.style.background = '';
      const dot = opt.querySelector('.eval-dec-dot div');
      if (dot) dot.style.background = 'transparent';
    });
    // Select this
    el.style.borderColor = '#7c3aed';
    el.style.background = '#faf5ff';
    const dot = el.querySelector('.eval-dec-dot div');
    if (dot) dot.style.background = '#7c3aed';

    const val = el.getAttribute('data-value');
    document.getElementById('evalDecisionValue').value = val;

    // Show/hide extension section
    const isExtend = val.includes('Perpanjang');
    document.getElementById('evalExtendSection').style.display = isExtend ? 'block' : 'none';
    if (isExtend) {
      document.getElementById('evalExtStart').value = '{{ date("Y-m-d") }}';
    }

    document.getElementById('btnConfirmProbationEval').disabled = false;
  }

  // Auto-calc extension end date
  document.addEventListener('DOMContentLoaded', function() {
    const durationSel = document.getElementById('evalExtDuration');
    const startEl = document.getElementById('evalExtStart');
    const endEl = document.getElementById('evalExtEnd');
    if (!durationSel || !startEl || !endEl) return;
    function updateExtEnd() {
      const dur = durationSel.value;
      const start = startEl.value;
      if (!dur || !start) return;
      const months = parseInt(dur);
      if (isNaN(months)) return;
      const d = new Date(start);
      d.setMonth(d.getMonth() + months);
      endEl.value = d.toISOString().split('T')[0];
    }
    durationSel.addEventListener('change', updateExtEnd);
    startEl.addEventListener('change', updateExtEnd);
  });
</script>
