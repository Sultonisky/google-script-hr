// ============================================================
// GenerateDummyData.gs — Complete HRIS Demo Dataset Generator
// ============================================================
// Run: generateAllHRISDemoData() from Apps Script editor
// Sheets generated:
//   raw_kandidat      — 40 Pending candidates
//   kandidat_hold     — 15 Hold candidates
//   kandidat_accepted — 30 Accepted candidates
//   kandidat_blacklist— 15 Blacklist candidates
//   Employee          — 30 from recruitment + 20 legacy outsource
//   Offboarding       — auto from Employee status changes
//   Audit_Log         — activity trail
// Sheet Users is NOT modified.
// ============================================================

function generateAllHRISDemoData() {
  var lock = LockService.getScriptLock();
  lock.waitLock(60000);
  try {
    Logger.log('===== START: Generate HRIS Demo Data =====');
    var today = new Date();

    _gdClearSheets_();

    var pool = _gdBuildCandidatePool_(today);
    _gdWriteRawKandidat_(pool.pending);
    _gdWriteHoldSheet_(pool.hold, today);
    _gdWriteAcceptedSheet_(pool.accepted, today);
    _gdWriteBlacklistSheet_(pool.blacklist, today);
    _gdWriteEmployeeSheet_(pool.accepted, today);
    _gdWriteAuditLog_(pool.all, today);

    Logger.log('Pending:   ' + pool.pending.length);
    Logger.log('Hold:      ' + pool.hold.length);
    Logger.log('Accepted:  ' + pool.accepted.length);
    Logger.log('Blacklist: ' + pool.blacklist.length);
    Logger.log('===== DONE =====');
    return 'OK — P:' + pool.pending.length + ' H:' + pool.hold.length +
           ' A:' + pool.accepted.length + ' B:' + pool.blacklist.length;
  } catch (e) {
    Logger.log('ERROR: ' + e.toString() + '\n' + e.stack);
    return 'Error: ' + e.toString();
  } finally {
    lock.releaseLock();
  }
}

// ============================================================
// CLEAR — kosongkan semua sheet data (bukan header)
// ============================================================
function _gdClearSheets_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var targets = [
    SHEET_NAME, HOLD_SHEET_NAME, ACCEPTED_SHEET_NAME,
    BLACKLIST_SHEET_NAME, EMPLOYEE_SHEET_NAME, AUDIT_SHEET_NAME,
    OFFBOARDING_SHEET_NAME,
  ];
  targets.forEach(function(name) {
    var s = ss.getSheetByName(name);
    if (!s) return;
    var lastRow = s.getLastRow();
    var maxRows = s.getMaxRows();
    // Sheet hanya punya header atau kosong — tidak perlu apa-apa
    if (lastRow <= 1) return;
    // Hapus konten baris data (baris 2 ke bawah)
    s.getRange(2, 1, lastRow - 1, s.getLastColumn()).clearContent();
    // Hapus baris kosong yang tersisa jika ada sisa row kosong di bawah
    // supaya sheet kembali ke ukuran minimal (header + 1 baris kosong = 2 baris)
    if (maxRows > 2) {
      s.deleteRows(3, maxRows - 2);
    }
  });
}

// ============================================================
// HELPERS
// ============================================================
function _gdPick_(arr)          { return arr[Math.floor(Math.random() * arr.length)]; }
function _gdRandInt_(a, b)      { return Math.floor(Math.random() * (b - a + 1)) + a; }
function _gdPad_(n, s)          { var r = '' + n; while (r.length < (s||2)) r = '0' + r; return r; }
function _gdShuffle_(arr)       { for (var i = arr.length-1; i > 0; i--) { var j = Math.floor(Math.random()*(i+1)); var t=arr[i]; arr[i]=arr[j]; arr[j]=t; } }
function _gdRandDigits_(n)      { var s=''; for(var i=0;i<n;i++) s+=Math.floor(Math.random()*10); return s; }
function _gdRandAlphanum_(n)    { var c='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',s=''; for(var i=0;i<n;i++) s+=c.charAt(Math.floor(Math.random()*c.length)); return s; }
function _gdFmt_(d)             { return Utilities.formatDate(d,'GMT+7','yyyy-MM-dd HH:mm:ss'); }
function _gdFmtDate_(d)         { return Utilities.formatDate(d,'GMT+7','yyyy-MM-dd'); }
function _gdSubDays_(base, n)   { return new Date(base.getTime() - n * 86400000); }
function _gdAddDays_(base, n)   { return new Date(base.getTime() + n * 86400000); }
function _gdRandTime_(d)        { var c=new Date(d); c.setHours(_gdRandInt_(8,17),_gdRandInt_(0,59),_gdRandInt_(0,59)); return c; }

function _gdRandSalary_(pos) {
  var map = {
    'Director':[15000000,30000000],'GM':[12000000,25000000],'Manager':[8000000,20000000],
    'Supervisor':[5000000,12000000],'Team Lead':[5000000,12000000],'Senior Staff':[4000000,10000000],
    'Staff':[3500000,8000000],'Junior Staff':[3000000,6000000],'IT Support':[4000000,7000000],
    'Network Engineer':[5000000,9000000],'Software Engineer':[7000000,15000000],
    'Backend Developer':[7000000,14000000],'Frontend Developer':[6000000,13000000],
    'Fullstack Developer':[8000000,16000000],'HR Staff':[4500000,8000000],
    'HR Recruiter':[4500000,8000000],'Finance Staff':[5000000,9000000],
    'Accounting Staff':[4500000,8000000],'Digital Marketing Specialist':[4500000,10000000],
    'Graphic Designer':[4000000,8000000],'UI/UX Designer':[5000000,12000000],
    'Sales Executive':[4000000,9000000],'Purchasing Staff':[4000000,7500000],
    'Warehouse Staff':[3500000,6000000],'Admin':[3500000,6000000],
    'Customer Service':[3500000,6500000],'Quality Control Staff':[3500000,7000000],
    'Driver':[3000000,5500000],'Security':[3000000,5000000],'Office Boy':[2500000,4500000],
  };
  var r = map[pos] || [3500000,8000000];
  return Math.round(_gdRandInt_(r[0],r[1]) / 500000) * 500000;
}

// ============================================================
// REFERENCE DATA
// ============================================================
var _GD_SOURCES_ = [
  'JobStreet','LinkedIn','Indeed','Instagram','Website Perusahaan',
  'Referensi Karyawan','Kampus / Career Fair','Loker.id','Karir.com',
  'Glassdoor','Walk In','Other',
];
var _GD_POSITIONS_ = [
  'Director','GM','Manager','Supervisor','Team Lead','Senior Staff','Staff','Junior Staff',
  'IT Support','Network Engineer','Software Engineer','Backend Developer','Frontend Developer',
  'Fullstack Developer','HR Staff','HR Recruiter','Finance Staff','Accounting Staff',
  'Digital Marketing Specialist','Graphic Designer','UI/UX Designer','Sales Executive',
  'Purchasing Staff','Warehouse Staff','Admin','Customer Service','Quality Control Staff',
  'Driver','Security','Office Boy',
];
var _GD_DEPARTMENTS_ = [
  'Human Resources','Finance','Accounting','Marketing','Digital Marketing','Sales',
  'IT','Engineering','Operations','Legal','GA','Warehouse','Purchasing',
  'Quality Control','Customer Service','Admin',
];
var _GD_DEPT_MAP_ = {
  'Director':['Human Resources','Finance','IT','Operations','Marketing'],
  'GM':['Human Resources','Finance','IT','Operations','Marketing','Sales'],
  'IT Support':['IT','Engineering','Operations'],'Network Engineer':['IT','Engineering'],
  'Software Engineer':['IT','Engineering'],'Backend Developer':['IT','Engineering'],
  'Frontend Developer':['IT','Engineering'],'Fullstack Developer':['IT','Engineering'],
  'HR Staff':['Human Resources'],'HR Recruiter':['Human Resources'],
  'Finance Staff':['Finance','Accounting'],'Accounting Staff':['Finance','Accounting'],
  'Digital Marketing Specialist':['Marketing','Digital Marketing'],
  'Graphic Designer':['Marketing','Digital Marketing'],
  'UI/UX Designer':['Marketing','Digital Marketing','IT'],
  'Sales Executive':['Sales'],'Purchasing Staff':['Purchasing','Warehouse'],
  'Warehouse Staff':['Warehouse','Operations'],'Admin':['Admin','GA','Human Resources'],
  'Customer Service':['Customer Service'],'Quality Control Staff':['Quality Control','Operations'],
  'Driver':['GA','Operations','Warehouse'],'Security':['GA','Operations'],'Office Boy':['Admin','GA'],
};
var _GD_EDUCATION_ = [
  'Junior High School','Senior High School (SMA)','Vocational High School (SMK)',
  "Diploma (D3)","Bachelor's Degree (S1)","Master's Degree (S2)","Doctoral Degree (S3)",
];
var _GD_WORK_EXP_ = [
  'Fresh Graduate','Less than 1 Year','1-2 Years','2-3 Years',
  '3-5 Years','5-10 Years','More than 10 Years',
];
var _GD_EMP_STATUS_ = [
  'Employed Full Time','Employed Contract','Part Time','Freelance',
  'Unemployed','Resigned','Fresh Graduate',
];
var _GD_AVAILABLE_ = [
  'Immediately','1 Week','2 Weeks','1 Month','2 Months','3 Months','Negotiable',
];
var _GD_MARITAL_ = ['Single','Married','Divorced','Widowed'];
var _GD_CITIES_  = [
  'Jakarta Pusat','Jakarta Selatan','Jakarta Barat','Jakarta Utara','Jakarta Timur',
  'Bandung','Surabaya','Semarang','Yogyakarta','Medan','Makassar','Balikpapan',
  'Denpasar','Palembang','Banjarmasin','Pontianak','Padang','Lampung',
];
var _GD_STREETS_ = [
  'Jl. Sudirman','Jl. Thamrin','Jl. Gatot Subroto','Jl. Diponegoro','Jl. Ahmad Yani',
  'Jl. Imam Bonjol','Jl. Hayam Wuruk','Jl. Gajah Mada','Jl. Veteran','Jl. Pahlawan',
  'Jl. Merdeka','Jl. Asia Afrika','Jl. Pemuda','Jl. Kartini','Jl. Rasuna Said',
  'Jl. Kuningan','Jl. TB Simatupang','Jl. Cikini','Jl. Menteng','Jl. Salemba',
  'Jl. Tebet','Jl. Kemang','Jl. Fatmawati','Jl. Bangka','Jl. Margonda',
];
var _GD_COMPANIES_ = [
  'PT Telkom Indonesia','PT Bank Mandiri','PT Pertamina','PT PLN','PT Garuda Indonesia',
  'PT Astra International','PT Unilever Indonesia','PT Indofood Sukses Makmur',
  'PT BRI','PT BCA','PT Bank Negara Indonesia','PT Samsung Electronics Indonesia',
  'PT Gojek Indonesia','PT Tokopedia','PT Traveloka','PT Shopee Indonesia',
  'PT Grab Indonesia','PT Lazada Indonesia','PT Bukalapak','PT Toyota Motor Manufacturing Indonesia',
  'PT Honda Prospect Motor','PT Yamaha Motor Indonesia','PT Astra Honda Motor',
];
var _GD_MALE_NAMES_ = [
  'Ahmad','Budi','Dedi','Eko','Fajar','Gilang','Hendra','Irfan','Joko','Krisna',
  'Luthfi','Muhammad','Nanda','Oki','Prasetyo','Rizki','Satria','Taufik','Wahyu','Yoga',
  'Aditya','Bagus','Cakra','Dimas','Elang','Farhan','Guntur','Hafiz','Indra','Januar',
  'Kurniawan','Lukman','Maulana','Nugroho','Rian','Surya','Teguh','Yudi','Zainal','Andi',
  'Bambang','Dharmawan','Firman','Hery','Iwan','Jefri','Khalid','Lukas','Mardiansyah',
  'Naufal','Okta','Pranoto','Rizal','Saputra','Umar','Viktor','Wibowo','Yusuf','Zulfikar',
  'Akmal','Bima','Danang','Erik','Fahmi','Gibran','Haidar','Ilham','Kamal','Mikail',
  'Nabil','Raka','Tariq','Alif','Bara','Zidan',
];
var _GD_FEMALE_NAMES_ = [
  'Ani','Bunga','Citra','Dewi','Eka','Fitri','Gita','Hana','Indah','Juli',
  'Kartika','Lestari','Maya','Nina','Oktavia','Putri','Ratna','Sari','Tantri','Ulya',
  'Wati','Yunita','Ayu','Bening','Cempaka','Dian','Elsa','Fiona','Grace','Hani',
  'Intan','Julia','Kirana','Luna','Mega','Nabila','Pratiwi','Rina','Siti','Tika',
  'Ulfa','Vina','Wida','Yanti','Zahra','Amina','Bella','Cinta','Dara','Fatimah',
  'Hidayah','Ira','Jannah','Khalida','Laila','Mira','Nisa','Rahma','Salsabila',
  'Tia','Ummi','Vera','Wahyuni','Yani','Zahara','Aldila','Dhea','Farah','Gisel',
  'Mutiara','Nadhira','Prilly','Rahma',
];
var _GD_LAST_NAMES_ = [
  'Susanto','Wijaya','Pratama','Kurniawan','Setiawan','Saputra','Hidayat','Santoso',
  'Putra','Ardianto','Nugroho','Suryadi','Wibowo','Rahman','Firmansyah','Suhendar',
  'Gunawan','Hartono','Budiman','Siregar','Tampubolon','Manurung','Purba','Simanjuntak',
  'Limbong','Sinaga','Nainggolan','Hutapea','Panggabean','Sitorus','Ginting','Pardede',
  'Silalahi','Purnama','Prasetyo','Wahyudi','Lestari',
];
var _GD_HR_NOTES_ = [
  'Kandidat memiliki pengalaman yang relevan dengan posisi yang dilamar.',
  'CV lengkap dan menarik, perlu follow up untuk interview.',
  'Kandidat direferensikan oleh tim internal.',
  'Hasil tes technical cukup baik, perlu evaluasi lebih lanjut.',
  'Komunikasi kandidat sangat baik saat screening.',
  'Kandidat sudah berpengalaman di industri serupa.',
  'Perlu konfirmasi gaji dengan budget yang tersedia.',
  'Kandidat bersedia untuk WFH maupun on-site.',
  'Portfolio kandidat sangat impresif, layak lanjut ke tahap interview.',
  'Kandidat menunjukkan kemampuan leadership yang baik.',
  '','','',
];
var _GD_HOLD_REASONS_ = [
  'Kandidat meminta penundaan jadwal interview',
  'Posisi belum resmi dibuka, menunggu approval',
  'Budget rekrutmen belum tersedia untuk kuartal ini',
  'Menunggu hasil background check dari referensi',
  'Kandidat sedang dalam proses di perusahaan lain',
];
var _GD_BLACKLIST_REASONS_ = [
  'Tidak hadir pada jadwal interview tanpa konfirmasi',
  'Data pada CV terbukti tidak sesuai kenyataan',
  'Pelanggaran etika selama proses seleksi',
  'Pernah mengalami PHK dengan catatan buruk',
  'Memberikan informasi palsu tentang pengalaman kerja',
];
var _GD_COMPANIES_INTERNAL_ = [
  'PT Mahakarya Sukses Indonesia','PT Mahakarya Tech','PT Mahakarya Trading',
  'PT Mahakarya Logistics','PT Mahakarya Capital',
];
var _GD_EMP_TYPES_   = ['PKWTT','PKWT','Outsource','Intern','Freelance'];
var _GD_EMP_STATUSES_= ['Active','Resigned','Terminated','On Leave','Probation'];
var _GD_CONTRACT_DUR_= ['3 Months','6 Months','1 Year','2 Years','3 Years'];
var _GD_SALARY_TYPES_= ['Monthly','Daily','Project-Based','Hourly'];
var _GD_OS_VENDORS_  = [
  'PT Cipta Karya Mandiri','PT Solusi Tenaga Prima','PT Mitra Kerja Abadi',
  'PT Insan Mulia Nusantara','PT Karya Tama Sejahtera',
];

function _gdDept_(pos) {
  return _gdPick_(_GD_DEPT_MAP_[pos] || _GD_DEPARTMENTS_);
}
function _gdName_(isMale) {
  return _gdPick_(isMale ? _GD_MALE_NAMES_ : _GD_FEMALE_NAMES_) + ' ' + _gdPick_(_GD_LAST_NAMES_);
}
function _gdAgeForExp_(exp) {
  if (exp==='Fresh Graduate'||exp==='Less than 1 Year') return _gdRandInt_(20,25);
  if (exp==='1-2 Years') return _gdRandInt_(22,28);
  if (exp==='2-3 Years') return _gdRandInt_(24,30);
  if (exp==='3-5 Years') return _gdRandInt_(26,33);
  if (exp==='5-10 Years') return _gdRandInt_(28,38);
  return _gdRandInt_(32,50);
}

// ============================================================
// BUILD CANDIDATE POOL — generate semua data kandidat sekali
// lalu distribusikan ke pending/hold/accepted/blacklist
// ============================================================
function _gdBuildCandidatePool_(today) {
  var usedEmails = {}, usedPhones = {}, usedNiks = {};
  var counter = 0;

  function makeCandidate(overrides) {
    counter++;
    var isMale    = Math.random() > 0.45;
    var fullName  = _gdName_(isMale);
    var pos       = _gdPick_(_GD_POSITIONS_);
    var exp       = _gdPick_(_GD_WORK_EXP_);
    var age       = Math.min(50, _gdAgeForExp_(exp));
    var edu       = _gdPick_(_GD_EDUCATION_);
    var city      = _gdPick_(_GD_CITIES_);
    var marital   = _gdPick_(_GD_MARITAL_);
    var empSt     = _gdPick_(_GD_EMP_STATUS_);
    var avail     = _gdPick_(_GD_AVAILABLE_);
    var source    = _gdPick_(_GD_SOURCES_);
    var salary    = _gdRandSalary_(pos);
    var company   = (exp==='Fresh Graduate'||exp==='Less than 1 Year') ? '-' : _gdPick_(_GD_COMPANIES_);

    var phone;
    do { phone = '08' + _gdRandDigits_(10); } while (usedPhones[phone]);
    usedPhones[phone] = true;

    var nameParts  = fullName.toLowerCase().split(' ');
    var emailBase  = nameParts[0] + '.' + (nameParts[1]||'x').replace(/[^a-z]/g,'');
    var emailIdx   = _gdRandInt_(1,999);
    var email      = emailBase + emailIdx + '@gmail.com';
    while (usedEmails[email]) { emailIdx++; email = emailBase + emailIdx + '@gmail.com'; }
    usedEmails[email] = true;

    var nik;
    do { nik = _gdRandDigits_(16); } while (usedNiks[nik]);
    usedNiks[nik] = true;

    var birthYear  = new Date().getFullYear() - age;
    var birthDate  = birthYear + '-' + _gdPad_(_gdRandInt_(1,12)) + '-' + _gdPad_(_gdRandInt_(1,28));
    var address    = _gdPick_(_GD_STREETS_) + ' No. ' + _gdRandInt_(1,150) + ', ' + city;
    var cvLink     = Math.random() > 0.35 ? 'https://drive.google.com/file/d/' + _gdRandAlphanum_(15) + '/view' : '';

    var createdAt  = _gdRandTime_(_gdSubDays_(today, _gdRandInt_(5,120)));
    var updatedAt  = _gdRandTime_(new Date(Math.min(
      today.getTime(),
      createdAt.getTime() + _gdRandInt_(1,30) * 86400000
    )));

    var recId = 'REC-' + _gdFmtDate_(createdAt).replace(/-/g,'') + '-' + _gdPad_(counter, 6);

    var base = {
      recruitmentId: recId, createdDate: _gdFmt_(createdAt),
      fullName: fullName, nik: nik, birthDate: birthDate, age: age,
      gender: isMale ? 'Male' : 'Female', maritalStatus: marital,
      email: email, phone: phone, address: address, city: city,
      positionApplied: pos, education: edu, workExperience: exp,
      lastCompany: company, currentEmploymentStatus: empSt,
      availableToJoin: avail, expectedSalary: salary,
      recruitmentSource: source, cvLink: cvLink,
      status: 'Pending', hrNotes: _gdPick_(_GD_HR_NOTES_),
      createdBy: 'Demo Generator', updatedAt: _gdFmt_(updatedAt),
      holdReason: '', holdFollowUpDate: '',
      blacklistReason: '', blacklistDate: '', blacklistUpdatedBy: '',
      employeeId: '',
      // derived
      department: _gdDept_(pos),
      isMale: isMale,
    };

    // merge overrides
    for (var k in (overrides||{})) base[k] = overrides[k];
    return base;
  }

  var pending    = [], hold = [], accepted = [], blacklist = [];

  // 40 Pending
  for (var i=0; i<40; i++) pending.push(makeCandidate({ status:'Pending' }));

  // 15 Hold
  for (var i=0; i<15; i++) {
    var fuDate = _gdFmtDate_(_gdAddDays_(today, _gdRandInt_(7,30)));
    hold.push(makeCandidate({
      status: 'Hold',
      holdReason: _gdPick_(_GD_HOLD_REASONS_),
      holdFollowUpDate: fuDate,
    }));
  }

  // 30 Accepted
  for (var i=0; i<30; i++) {
    var empId = 'EMP-' + today.getFullYear() + '-' + _gdPad_(i+1, 5);
    accepted.push(makeCandidate({
      status: 'Accepted',
      employeeId: empId,
    }));
  }

  // 15 Blacklist
  var now = _gdFmt_(today);
  for (var i=0; i<15; i++) {
    blacklist.push(makeCandidate({
      status: 'Blacklist',
      blacklistReason: _gdPick_(_GD_BLACKLIST_REASONS_),
      blacklistDate: now,
      blacklistUpdatedBy: 'HR Admin',
    }));
  }

  return {
    pending: pending, hold: hold,
    accepted: accepted, blacklist: blacklist,
    all: pending.concat(hold).concat(accepted).concat(blacklist),
  };
}

// ============================================================
// HELPER: build raw row array dari objek kandidat
// ============================================================
function _gdCandidateRow_(c, extraHeaders) {
  // core 25 + extra 6
  var row = [
    c.recruitmentId, c.createdDate, c.fullName,
    "'" + c.nik, c.birthDate, c.age, c.gender, c.maritalStatus,
    c.email, "'" + c.phone, c.address, c.city,
    c.positionApplied, c.education, c.workExperience, c.lastCompany,
    c.currentEmploymentStatus, c.availableToJoin, c.expectedSalary,
    c.recruitmentSource, c.cvLink, c.status, c.hrNotes,
    c.createdBy, c.updatedAt,
    // extra
    c.holdReason, c.holdFollowUpDate,
    c.blacklistReason, c.blacklistDate, c.blacklistUpdatedBy,
    c.employeeId,
  ];
  if (extraHeaders) {
    // Processed Date, Processed By
    row.push(c.processedDate || '');
    row.push(c.processedBy   || '');
  }
  return row;
}

// ============================================================
// WRITE: raw_kandidat — hanya Pending
// ============================================================
function _gdWriteRawKandidat_(candidates) {
  var sheet = getOrCreateSheet_();
  if (candidates.length === 0) return;
  var rows = candidates.map(function(c) { return _gdCandidateRow_(c, false); });
  sheet.getRange(2, 1, rows.length, rows[0].length).setValues(rows);
  Logger.log('raw_kandidat: ' + rows.length + ' rows written');
}

// ============================================================
// WRITE: kandidat_hold
// ============================================================
function _gdWriteHoldSheet_(candidates, today) {
  var sheet = getOrCreateHoldSheet_();
  if (candidates.length === 0) return;
  var nowStr = _gdFmt_(today);
  var rows = candidates.map(function(c) {
    c.processedDate = nowStr;
    c.processedBy   = 'Demo Generator';
    return _gdCandidateRow_(c, true);
  });
  sheet.getRange(2, 1, rows.length, rows[0].length).setValues(rows);
  Logger.log('kandidat_hold: ' + rows.length + ' rows written');
}

// ============================================================
// WRITE: kandidat_accepted
// ============================================================
function _gdWriteAcceptedSheet_(candidates, today) {
  var sheet = getOrCreateAcceptedSheet_();
  if (candidates.length === 0) return;
  var nowStr = _gdFmt_(today);
  var rows = candidates.map(function(c) {
    c.processedDate = nowStr;
    c.processedBy   = 'Demo Generator';
    return _gdCandidateRow_(c, true);
  });
  sheet.getRange(2, 1, rows.length, rows[0].length).setValues(rows);
  Logger.log('kandidat_accepted: ' + rows.length + ' rows written');
}

// ============================================================
// WRITE: kandidat_blacklist
// ============================================================
function _gdWriteBlacklistSheet_(candidates, today) {
  var sheet = getOrCreateBlacklistSheet_();
  if (candidates.length === 0) return;
  var nowStr = _gdFmt_(today);
  var rows = candidates.map(function(c) {
    c.processedDate = nowStr;
    c.processedBy   = 'Demo Generator';
    return _gdCandidateRow_(c, true);
  });
  sheet.getRange(2, 1, rows.length, rows[0].length).setValues(rows);
  Logger.log('kandidat_blacklist: ' + rows.length + ' rows written');
}

// ============================================================
// WRITE: Employee — 30 dari accepted + 20 legacy outsource
// ============================================================
function _gdWriteEmployeeSheet_(acceptedCandidates, today) {
  var sheet = getOrCreateEmployeeSheet_();
  var rows  = [];
  var nowStr = _gdFmt_(today);

  // --- 30 karyawan dari recruitment accepted ---
  acceptedCandidates.forEach(function(c) {
    var joinDate = _gdFmtDate_(_gdSubDays_(today, _gdRandInt_(30, 365)));
    var row = new Array(EMPLOYEE_HEADERS.length).fill('');
    row[EMPLOYEE_COL['Employee ID']       - 1] = c.employeeId;
    row[EMPLOYEE_COL['Recruitment ID']    - 1] = c.recruitmentId;
    row[EMPLOYEE_COL['Full Name']         - 1] = c.fullName;
    row[EMPLOYEE_COL['Position']          - 1] = c.positionApplied;
    row[EMPLOYEE_COL['Email']             - 1] = c.email;
    row[EMPLOYEE_COL['Phone']             - 1] = "'" + c.phone;
    row[EMPLOYEE_COL['Join Date']         - 1] = joinDate;
    row[EMPLOYEE_COL['Status']            - 1] = _gdPick_(['Active','Active','Active','Probation','On Leave']);
    row[EMPLOYEE_COL['Notes']             - 1] = c.hrNotes || '';
    row[EMPLOYEE_COL['Created At']        - 1] = nowStr;
    row[EMPLOYEE_COL['Company Entity']    - 1] = _gdPick_(_GD_COMPANIES_INTERNAL_);
    row[EMPLOYEE_COL['Employee Type']     - 1] = 'PKWTT';
    row[EMPLOYEE_COL['NIK']               - 1] = "'" + c.nik;
    row[EMPLOYEE_COL['Birth Date']        - 1] = c.birthDate;
    row[EMPLOYEE_COL['Age']               - 1] = c.age;
    row[EMPLOYEE_COL['Gender']            - 1] = c.gender;
    row[EMPLOYEE_COL['Marital Status']    - 1] = c.maritalStatus;
    row[EMPLOYEE_COL['Address']           - 1] = c.address;
    row[EMPLOYEE_COL['City']              - 1] = c.city;
    row[EMPLOYEE_COL['Education']         - 1] = c.education;
    row[EMPLOYEE_COL['Work Experience']   - 1] = c.workExperience;
    row[EMPLOYEE_COL['Department']        - 1] = c.department;
    row[EMPLOYEE_COL['Division']          - 1] = '';
    row[EMPLOYEE_COL['Branch']            - 1] = _gdPick_(['Jakarta','Bandung','Surabaya','']);
    row[EMPLOYEE_COL['Contract Start']    - 1] = joinDate;
    row[EMPLOYEE_COL['Contract End']      - 1] = '';
    row[EMPLOYEE_COL['Contract Duration'] - 1] = '';
    row[EMPLOYEE_COL['Employment Status'] - 1] = 'Active';
    row[EMPLOYEE_COL['Salary']            - 1] = c.expectedSalary;
    row[EMPLOYEE_COL['Salary Type']       - 1] = 'Monthly';
    row[EMPLOYEE_COL['Outsource Vendor']  - 1] = '';
    row[EMPLOYEE_COL['Contract Number']   - 1] = '';
    row[EMPLOYEE_COL['District']          - 1] = '';
    row[EMPLOYEE_COL['Recruitment Source']- 1] = c.recruitmentSource;
    row[EMPLOYEE_COL['HR Notes']          - 1] = '';
    row[EMPLOYEE_COL['Created By']        - 1] = 'Demo Generator';
    row[EMPLOYEE_COL['Updated At']        - 1] = nowStr;
    rows.push(row);
  });

  // --- 20 legacy outsource (bukan dari recruitment) ---
  var usedNiksEmp = {}, usedPhonesEmp = {}, usedEmailsEmp = {};
  for (var i = 0; i < 20; i++) {
    var isMale  = Math.random() > 0.45;
    var fullName= _gdName_(isMale);
    var pos     = _gdPick_(_GD_POSITIONS_);
    var dept    = _gdDept_(pos);
    var age     = _gdRandInt_(22, 45);
    var edu     = _gdPick_(_GD_EDUCATION_);
    var city    = _gdPick_(_GD_CITIES_);
    var marital = _gdPick_(_GD_MARITAL_);
    var salary  = _gdRandSalary_(pos);
    var empType = 'Outsource';
    var empStat = _gdPick_(['Active','Active','Active','Active','Resigned','Terminated']);
    var vendor  = _gdPick_(_GD_OS_VENDORS_);
    var company = _gdPick_(_GD_COMPANIES_INTERNAL_);

    var nik;
    do { nik = _gdRandDigits_(16); } while (usedNiksEmp[nik]);
    usedNiksEmp[nik] = true;

    var phone;
    do { phone = '08' + _gdRandDigits_(10); } while (usedPhonesEmp[phone]);
    usedPhonesEmp[phone] = true;

    var nameParts = fullName.toLowerCase().split(' ');
    var emailBase = nameParts[0] + '.' + (nameParts[1]||'x').replace(/[^a-z]/g,'');
    var emailIdx  = _gdRandInt_(100,999);
    var email     = emailBase + emailIdx + '@gmail.com';
    while (usedEmailsEmp[email]) { emailIdx++; email = emailBase + emailIdx + '@gmail.com'; }
    usedEmailsEmp[email] = true;

    var birthYear = new Date().getFullYear() - age;
    var birthDate = birthYear + '-' + _gdPad_(_gdRandInt_(1,12)) + '-' + _gdPad_(_gdRandInt_(1,28));
    var address   = _gdPick_(_GD_STREETS_) + ' No. ' + _gdRandInt_(1,150) + ', ' + city;
    var joinDate  = _gdFmtDate_(_gdSubDays_(today, _gdRandInt_(90, 730)));
    var empId     = 'EMP-' + today.getFullYear() + '-' + _gdPad_(100 + i + 1, 5);
    var contractDur = _gdPick_(_GD_CONTRACT_DUR_);
    var contStart = joinDate;
    var contEnd   = empType === 'PKWT' || empType === 'Outsource'
      ? _gdFmtDate_(_gdAddDays_(today, _gdRandInt_(30, 365))) : '';
    var contractNo = 'CONT-' + today.getFullYear() + '-' + _gdPad_(_gdRandInt_(1,999), 4);

    var row = new Array(EMPLOYEE_HEADERS.length).fill('');
    row[EMPLOYEE_COL['Employee ID']       - 1] = empId;
    row[EMPLOYEE_COL['Recruitment ID']    - 1] = '';
    row[EMPLOYEE_COL['Full Name']         - 1] = fullName;
    row[EMPLOYEE_COL['Position']          - 1] = pos;
    row[EMPLOYEE_COL['Email']             - 1] = email;
    row[EMPLOYEE_COL['Phone']             - 1] = "'" + phone;
    row[EMPLOYEE_COL['Join Date']         - 1] = joinDate;
    row[EMPLOYEE_COL['Status']            - 1] = empStat;
    row[EMPLOYEE_COL['Notes']             - 1] = '';
    row[EMPLOYEE_COL['Created At']        - 1] = nowStr;
    row[EMPLOYEE_COL['Company Entity']    - 1] = company;
    row[EMPLOYEE_COL['Employee Type']     - 1] = empType;
    row[EMPLOYEE_COL['NIK']               - 1] = "'" + nik;
    row[EMPLOYEE_COL['Birth Date']        - 1] = birthDate;
    row[EMPLOYEE_COL['Age']               - 1] = age;
    row[EMPLOYEE_COL['Gender']            - 1] = isMale ? 'Male' : 'Female';
    row[EMPLOYEE_COL['Marital Status']    - 1] = marital;
    row[EMPLOYEE_COL['Address']           - 1] = address;
    row[EMPLOYEE_COL['City']              - 1] = city;
    row[EMPLOYEE_COL['Education']         - 1] = edu;
    row[EMPLOYEE_COL['Work Experience']   - 1] = _gdPick_(_GD_WORK_EXP_);
    row[EMPLOYEE_COL['Department']        - 1] = dept;
    row[EMPLOYEE_COL['Division']          - 1] = '';
    row[EMPLOYEE_COL['Branch']            - 1] = _gdPick_(['Jakarta','Bandung','Surabaya','']);
    row[EMPLOYEE_COL['Contract Start']    - 1] = contStart;
    row[EMPLOYEE_COL['Contract End']      - 1] = contEnd;
    row[EMPLOYEE_COL['Contract Duration'] - 1] = contractDur;
    row[EMPLOYEE_COL['Employment Status'] - 1] = empStat === 'Active' ? 'Active' : empStat;
    row[EMPLOYEE_COL['Salary']            - 1] = salary;
    row[EMPLOYEE_COL['Salary Type']       - 1] = 'Monthly';
    row[EMPLOYEE_COL['Outsource Vendor']  - 1] = vendor;
    row[EMPLOYEE_COL['Contract Number']   - 1] = contractNo;
    row[EMPLOYEE_COL['District']          - 1] = '';
    row[EMPLOYEE_COL['Recruitment Source']- 1] = _gdPick_(_GD_SOURCES_);
    row[EMPLOYEE_COL['HR Notes']          - 1] = '';
    row[EMPLOYEE_COL['Created By']        - 1] = 'Demo Generator';
    row[EMPLOYEE_COL['Updated At']        - 1] = nowStr;
    rows.push(row);
  }

  if (rows.length > 0) {
    sheet.getRange(2, 1, rows.length, EMPLOYEE_HEADERS.length).setValues(rows);
  }
  Logger.log('Employee: ' + rows.length + ' rows written (30 recruitment + 20 outsource)');
}

// ============================================================
// WRITE: Audit_Log — trail aktivitas untuk semua kandidat
// ============================================================
function _gdWriteAuditLog_(allCandidates, today) {
  var sheet = getOrCreateAuditLogSheet_();
  var rows  = [];
  var user  = 'Demo Generator';

  allCandidates.forEach(function(c) {
    var createdTs = c.createdDate;

    // Baris 1: Created
    rows.push([c.recruitmentId, 'Created', 'Status', '-', 'Pending', user, createdTs]);

    // Baris 2+: status change jika bukan Pending
    if (c.status === 'Hold') {
      var ts = c.processedDate || _gdFmt_(_gdRandTime_(_gdSubDays_(today, _gdRandInt_(1,10))));
      rows.push([c.recruitmentId, 'Hold', 'Status', 'Pending', 'Hold (' + c.holdReason + ')', user, ts]);
    } else if (c.status === 'Accepted') {
      var ts = c.processedDate || _gdFmt_(_gdRandTime_(_gdSubDays_(today, _gdRandInt_(1,30))));
      rows.push([c.recruitmentId, 'Accepted', 'Status', 'Pending', 'Accepted -> Employee ' + c.employeeId, user, ts]);
    } else if (c.status === 'Blacklist') {
      var ts = c.processedDate || _gdFmt_(_gdRandTime_(_gdSubDays_(today, _gdRandInt_(1,20))));
      rows.push([c.recruitmentId, 'Blacklist', 'Status', 'Pending', 'Blacklist (' + c.blacklistReason + ')', user, ts]);
    }

    // HR Notes update jika ada catatan
    if (c.hrNotes && c.hrNotes.length > 0) {
      rows.push([c.recruitmentId, 'HR Notes Update', 'HR Notes', '', c.hrNotes.substring(0,60), user, c.updatedAt]);
    }
  });

  if (rows.length > 0) {
    sheet.getRange(2, 1, rows.length, AUDIT_LOG_HEADERS.length).setValues(rows);
  }
  Logger.log('Audit_Log: ' + rows.length + ' rows written');
}

// ============================================================
// UTILITY: hapus semua data di semua sheet (reset bersih)
// Sheet header tetap dipertahankan.
// ============================================================
function clearAllHRISData() {
  _gdClearSheets_();
  Logger.log('All HRIS data cleared (headers preserved).');
  return 'OK — semua data dihapus, header tetap.';
}
