// ============================================================
// GenerateDummyData.gs — Complete HRIS Demo Dataset Generator
// ============================================================
// Run: generateAllHRISDemoData() from Apps Script editor
// Sheets generated:
//   data_kandidat     — 40 Pending candidates
//   kandidat_hold     — 15 Hold candidates
//   kandidat_accepted — 30 Accepted candidates
//   kandidat_blacklist— 15 Blacklist candidates
//   Employee          — 30 from recruitment + 20 legacy (tipe campuran)
//   Offboarding       — auto from Employee status changes
//   Audit_Log         — activity trail
// Sheet Users is NOT modified.
// ============================================================

function generateAllHRISDemoData() {
  var lock = LockService.getScriptLock();
  lock.waitLock(60000);
  try {
    Logger.log("===== START: Generate HRIS Demo Data =====");
    var today = new Date();

    _gdClearSheets_();

    var pool = _gdBuildCandidatePool_(today);
    _gdWriteRawKandidat_(pool.pending);
    _gdWriteHoldSheet_(pool.hold, today);
    _gdWriteAcceptedSheet_(pool.accepted, today);
    _gdWriteBlacklistSheet_(pool.blacklist, today);
    var probationEmployees = _gdWriteEmployeeSheet_(pool.accepted, today);
    _gdWriteProbationSheet_(probationEmployees, today);
    _gdWriteAuditLog_(pool.all, today);

    Logger.log("Pending:   " + pool.pending.length);
    Logger.log("Hold:      " + pool.hold.length);
    Logger.log("Accepted:  " + pool.accepted.length);
    Logger.log("Blacklist: " + pool.blacklist.length);
    Logger.log("===== DONE =====");
    return (
      "OK — P:" +
      pool.pending.length +
      " H:" +
      pool.hold.length +
      " A:" +
      pool.accepted.length +
      " B:" +
      pool.blacklist.length
    );
  } catch (e) {
    Logger.log("ERROR: " + e.toString() + "\n" + e.stack);
    return "Error: " + e.toString();
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
    SHEET_NAME,
    HOLD_SHEET_NAME,
    ACCEPTED_SHEET_NAME,
    BLACKLIST_SHEET_NAME,
    EMPLOYEE_SHEET_NAME,
    AUDIT_SHEET_NAME,
    PROBATION_SHEET_NAME,
  ];
  targets.forEach(function (name) {
    var s = ss.getSheetByName(name);
    if (!s) return;
    var lastRow = s.getLastRow();
    var maxRows = s.getMaxRows();
    // Sheet hanya punya header atau kosong — tidak perlu apa-apa
    if (lastRow <= 1) return;
    // Hapus konten baris data (baris 2 ke bawah) SEMUA kolom
    s.getRange(2, 1, lastRow - 1, s.getMaxColumns()).clearContent();
    // Hapus baris kosong yang tersisa
    if (maxRows > 2) {
      s.deleteRows(3, maxRows - 2);
    }
  });
}

// ============================================================
// HELPERS
// ============================================================
function _gdPick_(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}
function _gdRandInt_(a, b) {
  return Math.floor(Math.random() * (b - a + 1)) + a;
}
function _gdPad_(n, s) {
  var r = "" + n;
  while (r.length < (s || 2)) r = "0" + r;
  return r;
}
// Selaraskan Employee ID di sheet kandidat_accepted dengan Employee ID yang
// dihasilkan sheet Employee (join date + sequence).
function _gdSyncAcceptedEmployeeId_(recruitmentId, employeeId) {
  if (!recruitmentId || !employeeId) return;
  try {
    var sheet = getOrCreateAcceptedSheet_();
    if (!sheet || sheet.getLastRow() < 2) return;
    var data = sheet.getDataRange().getValues();
    var hdr = data[0];
    var ridIdx = -1,
      empIdx = -1;
    hdr.forEach(function (h, i) {
      if (String(h).trim() === "Recruitment ID") ridIdx = i;
      if (String(h).trim() === "Employee ID") empIdx = i;
    });
    if (ridIdx === -1 || empIdx === -1) return;
    for (var r = 1; r < data.length; r++) {
      if (
        String(data[r][ridIdx] || "").trim() ===
        String(recruitmentId).trim()
      ) {
        sheet.getRange(r + 1, empIdx + 1).setValue(employeeId);
        break;
      }
    }
  } catch (e) {}
}
function _gdShuffle_(arr) {
  for (var i = arr.length - 1; i > 0; i--) {
    var j = Math.floor(Math.random() * (i + 1));
    var t = arr[i];
    arr[i] = arr[j];
    arr[j] = t;
  }
}
function _gdRandDigits_(n) {
  var s = "";
  for (var i = 0; i < n; i++) s += Math.floor(Math.random() * 10);
  return s;
}
function _gdRandAlphanum_(n) {
  var c = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789",
    s = "";
  for (var i = 0; i < n; i++)
    s += c.charAt(Math.floor(Math.random() * c.length));
  return s;
}
function _gdFmt_(d) {
  return Utilities.formatDate(d, "GMT+7", "yyyy-MM-dd HH:mm:ss");
}
function _gdFmtDate_(d) {
  return Utilities.formatDate(d, "GMT+7", "yyyy-MM-dd");
}
function _gdSubDays_(base, n) {
  return new Date(base.getTime() - n * 86400000);
}
function _gdAddDays_(base, n) {
  return new Date(base.getTime() + n * 86400000);
}
function _gdRandTime_(d) {
  var c = new Date(d);
  c.setHours(_gdRandInt_(8, 17), _gdRandInt_(0, 59), _gdRandInt_(0, 59));
  return c;
}

function _gdRandSalary_(pos) {
  var map = {
    Director: [15000000, 30000000],
    GM: [12000000, 25000000],
    Manager: [8000000, 20000000],
    Supervisor: [5000000, 12000000],
    "Team Lead": [5000000, 12000000],
    "Senior Staff": [4000000, 10000000],
    Staff: [3500000, 8000000],
    "Junior Staff": [3000000, 6000000],
    "IT Support": [4000000, 7000000],
    "Network Engineer": [5000000, 9000000],
    "Software Engineer": [7000000, 15000000],
    "Backend Developer": [7000000, 14000000],
    "Frontend Developer": [6000000, 13000000],
    "Fullstack Developer": [8000000, 16000000],
    "HR Staff": [4500000, 8000000],
    "HR Recruiter": [4500000, 8000000],
    "Finance Staff": [5000000, 9000000],
    "Accounting Staff": [4500000, 8000000],
    "Digital Marketing Specialist": [4500000, 10000000],
    "Graphic Designer": [4000000, 8000000],
    "UI/UX Designer": [5000000, 12000000],
    "Sales Executive": [4000000, 9000000],
    "Purchasing Staff": [4000000, 7500000],
    "Warehouse Staff": [3500000, 6000000],
    Admin: [3500000, 6000000],
    "Customer Service": [3500000, 6500000],
    "Quality Control Staff": [3500000, 7000000],
    Driver: [3000000, 5500000],
    Security: [3000000, 5000000],
    "Office Boy": [2500000, 4500000],
  };
  var r = map[pos] || [3500000, 8000000];
  return Math.round(_gdRandInt_(r[0], r[1]) / 500000) * 500000;
}

// ============================================================
// REFERENCE DATA
// ============================================================
var _GD_SOURCES_ = [
  "JobStreet",
  "LinkedIn",
  "Indeed",
  "Instagram",
  "Website Perusahaan",
  "Referensi Karyawan",
  "Kampus / Career Fair",
  "Loker.id",
  "Karir.com",
  "Glassdoor",
  "Walk In",
  "Other",
];
var _GD_POSITIONS_ = [
  "Director",
  "GM",
  "Manager",
  "Supervisor",
  "Team Lead",
  "Senior Staff",
  "Staff",
  "Junior Staff",
  "IT Support",
  "Network Engineer",
  "Software Engineer",
  "Backend Developer",
  "Frontend Developer",
  "Fullstack Developer",
  "HR Staff",
  "HR Recruiter",
  "Finance Staff",
  "Accounting Staff",
  "Digital Marketing Specialist",
  "Graphic Designer",
  "UI/UX Designer",
  "Sales Executive",
  "Purchasing Staff",
  "Warehouse Staff",
  "Admin",
  "Customer Service",
  "Quality Control Staff",
  "Driver",
  "Security",
  "Office Boy",
];
var _GD_DEPARTMENTS_ = [
  "Human Resources",
  "Finance",
  "Accounting",
  "Marketing",
  "Digital Marketing",
  "Sales",
  "IT",
  "Engineering",
  "Operations",
  "Legal",
  "GA",
  "Warehouse",
  "Purchasing",
  "Quality Control",
  "Customer Service",
  "Admin",
];
var _GD_DEPT_MAP_ = {
  Director: ["Human Resources", "Finance", "IT", "Operations", "Marketing"],
  GM: ["Human Resources", "Finance", "IT", "Operations", "Marketing", "Sales"],
  "IT Support": ["IT", "Engineering", "Operations"],
  "Network Engineer": ["IT", "Engineering"],
  "Software Engineer": ["IT", "Engineering"],
  "Backend Developer": ["IT", "Engineering"],
  "Frontend Developer": ["IT", "Engineering"],
  "Fullstack Developer": ["IT", "Engineering"],
  "HR Staff": ["Human Resources"],
  "HR Recruiter": ["Human Resources"],
  "Finance Staff": ["Finance", "Accounting"],
  "Accounting Staff": ["Finance", "Accounting"],
  "Digital Marketing Specialist": ["Marketing", "Digital Marketing"],
  "Graphic Designer": ["Marketing", "Digital Marketing"],
  "UI/UX Designer": ["Marketing", "Digital Marketing", "IT"],
  "Sales Executive": ["Sales"],
  "Purchasing Staff": ["Purchasing", "Warehouse"],
  "Warehouse Staff": ["Warehouse", "Operations"],
  Admin: ["Admin", "GA", "Human Resources"],
  "Customer Service": ["Customer Service"],
  "Quality Control Staff": ["Quality Control", "Operations"],
  Driver: ["GA", "Operations", "Warehouse"],
  Security: ["GA", "Operations"],
  "Office Boy": ["Admin", "GA"],
};
var _GD_EDUCATION_ = ["SD", "SMP", "SMA/SMK", "D3", "S1", "S2", "S3"];
var _GD_WORK_EXP_ = [
  "Fresh Graduate",
  "1-2 Tahun",
  "3-5 Tahun",
  "5-10 Tahun",
  "Lebih dari 10 Tahun",
];
var _GD_EMP_STATUS_ = [
  "Employed Full Time",
  "Employed Contract",
  "Part Time",
  "Freelance",
  "Unemployed",
  "Resigned",
  "Fresh Graduate",
];
var _GD_AVAILABLE_ = [
  "Segera",
  "1 Minggu",
  "2 Minggu",
  "1 Bulan",
  "2 Bulan",
  "3 Bulan",
  "Negosiasi",
];
var _GD_MARITAL_ = ["Belum Menikah", "Menikah", "Cerai"];
var _GD_CITIES_ = [
  "Jakarta Pusat",
  "Jakarta Selatan",
  "Jakarta Barat",
  "Jakarta Utara",
  "Jakarta Timur",
  "Bandung",
  "Surabaya",
  "Semarang",
  "Yogyakarta",
  "Medan",
  "Makassar",
  "Balikpapan",
  "Denpasar",
  "Palembang",
  "Banjarmasin",
  "Pontianak",
  "Padang",
  "Lampung",
];
var _GD_STREETS_ = [
  "Jl. Sudirman",
  "Jl. Thamrin",
  "Jl. Gatot Subroto",
  "Jl. Diponegoro",
  "Jl. Ahmad Yani",
  "Jl. Imam Bonjol",
  "Jl. Hayam Wuruk",
  "Jl. Gajah Mada",
  "Jl. Veteran",
  "Jl. Pahlawan",
  "Jl. Merdeka",
  "Jl. Asia Afrika",
  "Jl. Pemuda",
  "Jl. Kartini",
  "Jl. Rasuna Said",
  "Jl. Kuningan",
  "Jl. TB Simatupang",
  "Jl. Cikini",
  "Jl. Menteng",
  "Jl. Salemba",
  "Jl. Tebet",
  "Jl. Kemang",
  "Jl. Fatmawati",
  "Jl. Bangka",
  "Jl. Margonda",
];
var _GD_COMPANIES_ = [
  "PT Telkom Indonesia",
  "PT Bank Mandiri",
  "PT Pertamina",
  "PT PLN",
  "PT Garuda Indonesia",
  "PT Astra International",
  "PT Unilever Indonesia",
  "PT Indofood Sukses Makmur",
  "PT BRI",
  "PT BCA",
  "PT Bank Negara Indonesia",
  "PT Samsung Electronics Indonesia",
  "PT Gojek Indonesia",
  "PT Tokopedia",
  "PT Traveloka",
  "PT Shopee Indonesia",
  "PT Grab Indonesia",
  "PT Lazada Indonesia",
  "PT Bukalapak",
  "PT Toyota Motor Manufacturing Indonesia",
  "PT Honda Prospect Motor",
  "PT Yamaha Motor Indonesia",
  "PT Astra Honda Motor",
];
var _GD_MALE_NAMES_ = [
  "Ahmad",
  "Budi",
  "Dedi",
  "Eko",
  "Fajar",
  "Gilang",
  "Hendra",
  "Irfan",
  "Joko",
  "Krisna",
  "Luthfi",
  "Muhammad",
  "Nanda",
  "Oki",
  "Prasetyo",
  "Rizki",
  "Satria",
  "Taufik",
  "Wahyu",
  "Yoga",
  "Aditya",
  "Bagus",
  "Cakra",
  "Dimas",
  "Elang",
  "Farhan",
  "Guntur",
  "Hafiz",
  "Indra",
  "Januar",
  "Kurniawan",
  "Lukman",
  "Maulana",
  "Nugroho",
  "Rian",
  "Surya",
  "Teguh",
  "Yudi",
  "Zainal",
  "Andi",
  "Bambang",
  "Dharmawan",
  "Firman",
  "Hery",
  "Iwan",
  "Jefri",
  "Khalid",
  "Lukas",
  "Mardiansyah",
  "Naufal",
  "Okta",
  "Pranoto",
  "Rizal",
  "Saputra",
  "Umar",
  "Viktor",
  "Wibowo",
  "Yusuf",
  "Zulfikar",
  "Akmal",
  "Bima",
  "Danang",
  "Erik",
  "Fahmi",
  "Gibran",
  "Haidar",
  "Ilham",
  "Kamal",
  "Mikail",
  "Nabil",
  "Raka",
  "Tariq",
  "Alif",
  "Bara",
  "Zidan",
];
var _GD_FEMALE_NAMES_ = [
  "Ani",
  "Bunga",
  "Citra",
  "Dewi",
  "Eka",
  "Fitri",
  "Gita",
  "Hana",
  "Indah",
  "Juli",
  "Kartika",
  "Lestari",
  "Maya",
  "Nina",
  "Oktavia",
  "Putri",
  "Ratna",
  "Sari",
  "Tantri",
  "Ulya",
  "Wati",
  "Yunita",
  "Ayu",
  "Bening",
  "Cempaka",
  "Dian",
  "Elsa",
  "Fiona",
  "Grace",
  "Hani",
  "Intan",
  "Julia",
  "Kirana",
  "Luna",
  "Mega",
  "Nabila",
  "Pratiwi",
  "Rina",
  "Siti",
  "Tika",
  "Ulfa",
  "Vina",
  "Wida",
  "Yanti",
  "Zahra",
  "Amina",
  "Bella",
  "Cinta",
  "Dara",
  "Fatimah",
  "Hidayah",
  "Ira",
  "Jannah",
  "Khalida",
  "Laila",
  "Mira",
  "Nisa",
  "Rahma",
  "Salsabila",
  "Tia",
  "Ummi",
  "Vera",
  "Wahyuni",
  "Yani",
  "Zahara",
  "Aldila",
  "Dhea",
  "Farah",
  "Gisel",
  "Mutiara",
  "Nadhira",
  "Prilly",
  "Rahma",
];
var _GD_LAST_NAMES_ = [
  "Susanto",
  "Wijaya",
  "Pratama",
  "Kurniawan",
  "Setiawan",
  "Saputra",
  "Hidayat",
  "Santoso",
  "Putra",
  "Ardianto",
  "Nugroho",
  "Suryadi",
  "Wibowo",
  "Rahman",
  "Firmansyah",
  "Suhendar",
  "Gunawan",
  "Hartono",
  "Budiman",
  "Siregar",
  "Tampubolon",
  "Manurung",
  "Purba",
  "Simanjuntak",
  "Limbong",
  "Sinaga",
  "Nainggolan",
  "Hutapea",
  "Panggabean",
  "Sitorus",
  "Ginting",
  "Pardede",
  "Silalahi",
  "Purnama",
  "Prasetyo",
  "Wahyudi",
  "Lestari",
];
var _GD_HR_NOTES_ = [
  "Kandidat memiliki pengalaman yang relevan dengan posisi yang dilamar.",
  "CV lengkap dan menarik, perlu follow up untuk interview.",
  "Kandidat direferensikan oleh tim internal.",
  "Hasil tes technical cukup baik, perlu evaluasi lebih lanjut.",
  "Komunikasi kandidat sangat baik saat screening.",
  "Kandidat sudah berpengalaman di industri serupa.",
  "Perlu konfirmasi gaji dengan budget yang tersedia.",
  "Kandidat bersedia untuk WFH maupun on-site.",
  "Portfolio kandidat sangat impresif, layak lanjut ke tahap interview.",
  "Kandidat menunjukkan kemampuan leadership yang baik.",
  "",
  "",
  "",
];
var _GD_HOLD_REASONS_ = [
  "Kandidat meminta penundaan jadwal interview",
  "Posisi belum resmi dibuka, menunggu approval",
  "Budget rekrutmen belum tersedia untuk kuartal ini",
  "Menunggu hasil background check dari referensi",
  "Kandidat sedang dalam proses di perusahaan lain",
];
var _GD_BLACKLIST_REASONS_ = [
  "Tidak hadir pada jadwal interview tanpa konfirmasi",
  "Data pada CV terbukti tidak sesuai kenyataan",
  "Pelanggaran etika selama proses seleksi",
  "Pernah mengalami PHK dengan catatan buruk",
  "Memberikan informasi palsu tentang pengalaman kerja",
];
var _GD_COMPANIES_INTERNAL_ = [
  "PT Mahakarya Sukses Indonesia",
  "PT Stein Perkasa Internasional",
  "PT Perkasa Injeksi Indonesia",
  "PT Mitra Elektro Perkasa",
];
var _GD_EMP_TYPES_ = ["Project", "PKWTT", "PKWT", "Outsource", "Intern"];
var _GD_EMP_STATUSES_ = [
  "Active",
  "Resigned",
  "Terminated",
  "On Leave",
  "Probation",
];
var _GD_CONTRACT_DUR_ = ["3 Bulan", "6 Bulan", "1 Tahun", "2 Tahun", "3 Tahun"];
var _GD_SALARY_TYPES_ = ["Bulanan", "Harian", "Per Proyek", "Per Jam"];
var _GD_OS_VENDORS_ = [
  "PT Cipta Karya Mandiri",
  "PT Solusi Tenaga Prima",
  "PT Mitra Kerja Abadi",
  "PT Insan Mulia Nusantara",
  "PT Karya Tama Sejahtera",
];
var _GD_BRANCHES_ = [
  "PT Mahakarya Sukses Indonesia",
  "PT Stein Perkasa Internasional",
  "PT Perkasa Injeksi Indonesia",
  "PT Mitra Elektro Perkasa",
];
var _GD_DIVISIONS_ = [
  "RnD & aftersales",
  "Sales",
  "FAT & GA",
  "Manufacture",
  "E-Commerce",
  "IT",
  "Digital Marketing",
  "Buyer - Import",
  "Marketing",
  "Creative",
  "HR & Legal",
];
var _GD_AREAS_ = [
  "Head Office (HO)",
  "Depo Jakarta",
  "Depo Bandung",
  "Depo Surabaya",
  "Pabrik",
];
var _GD_JOB_LEVELS_ = ["Associate", "Supervisor", "Manager"];
var _GD_PTKP_ = ["TK/0", "TK/1", "K/0", "K/1", "K/2", "K/3"];
var _GD_RELIGIONS_ = ["Islam", "Kristen", "Katolik", "Hindu", "Buddha"];
var _GD_BLOOD_TYPES_ = ["A", "B", "AB", "O"];
var _GD_SUPERIORS_ = [
  "Budi Santoso",
  "Siti Nurhaliza",
  "Ahmad Wijaya",
  "Dewi Lestari",
  "Eko Prasetyo",
  "Rina Marlina",
  "Hadi Wijanto",
];
var _GD_STATUS_EMP_ = ["Permanent", "Contract", "Probation"];
var _GD_ROTATION_TYPES_ = ["Promosi", "Mutasi", "Demosi", ""];

function _gdDept_(pos) {
  return _gdPick_(_GD_DEPT_MAP_[pos] || _GD_DEPARTMENTS_);
}
function _gdName_(isMale) {
  return (
    _gdPick_(isMale ? _GD_MALE_NAMES_ : _GD_FEMALE_NAMES_) +
    " " +
    _gdPick_(_GD_LAST_NAMES_)
  );
}
function _gdAgeForExp_(exp) {
  if (exp === "Fresh Graduate") return _gdRandInt_(20, 25);
  if (exp === "1-2 Tahun") return _gdRandInt_(22, 28);
  if (exp === "3-5 Tahun") return _gdRandInt_(26, 33);
  if (exp === "5-10 Tahun") return _gdRandInt_(28, 38);
  return _gdRandInt_(32, 50);
}

// ============================================================
// BUILD CANDIDATE POOL — generate semua data kandidat sekali
// lalu distribusikan ke pending/hold/accepted/blacklist
// ============================================================
function _gdBuildCandidatePool_(today) {
  var usedEmails = {},
    usedPhones = {},
    usedNiks = {};
  var counter = 0;

  function makeCandidate(overrides) {
    counter++;
    var isMale = Math.random() > 0.45;
    var fullName = _gdName_(isMale);
    var pos = _gdPick_(_GD_POSITIONS_);
    var exp = _gdPick_(_GD_WORK_EXP_);
    var age = Math.min(50, _gdAgeForExp_(exp));
    var edu = _gdPick_(_GD_EDUCATION_);
    var city = _gdPick_(_GD_CITIES_);
    var marital = _gdPick_(_GD_MARITAL_);
    var empSt = _gdPick_(_GD_EMP_STATUS_);
    var avail = _gdPick_(_GD_AVAILABLE_);
    var source = _gdPick_(_GD_SOURCES_);
    var salary = _gdRandSalary_(pos);
    var company = exp === "Fresh Graduate" ? "-" : _gdPick_(_GD_COMPANIES_);

    var phone;
    do {
      phone = "08" + _gdRandDigits_(10);
    } while (usedPhones[phone]);
    usedPhones[phone] = true;

    var nameParts = fullName.toLowerCase().split(" ");
    var emailBase =
      nameParts[0] + "." + (nameParts[1] || "x").replace(/[^a-z]/g, "");
    var emailIdx = _gdRandInt_(1, 999);
    var email = emailBase + emailIdx + "@gmail.com";
    while (usedEmails[email]) {
      emailIdx++;
      email = emailBase + emailIdx + "@gmail.com";
    }
    usedEmails[email] = true;

    var nik;
    do {
      nik = _gdRandDigits_(16);
    } while (usedNiks[nik]);
    usedNiks[nik] = true;

    var birthYear = new Date().getFullYear() - age;
    var birthDate =
      birthYear +
      "-" +
      _gdPad_(_gdRandInt_(1, 12)) +
      "-" +
      _gdPad_(_gdRandInt_(1, 28));
    var address =
      _gdPick_(_GD_STREETS_) + " No. " + _gdRandInt_(1, 150) + ", " + city;
    var cvLink =
      Math.random() > 0.35
        ? "https://drive.google.com/file/d/" + _gdRandAlphanum_(15) + "/view"
        : "";

    var createdAt = _gdRandTime_(_gdSubDays_(today, _gdRandInt_(5, 120)));
    var updatedAt = _gdRandTime_(
      new Date(
        Math.min(
          today.getTime(),
          createdAt.getTime() + _gdRandInt_(1, 30) * 86400000,
        ),
      ),
    );

    var recId =
      "REC-" +
      _gdFmtDate_(createdAt).replace(/-/g, "") +
      "-" +
      _gdPad_(counter, 6);

    var base = {
      recruitmentId: recId,
      createdDate: _gdFmt_(createdAt),
      fullName: fullName,
      nik: nik,
      birthDate: birthDate,
      age: age,
      gender: isMale ? "Laki-laki" : "Perempuan",
      maritalStatus: marital,
      email: email,
      phone: phone,
      address: address,
      city: city,
      positionApplied: pos,
      education: edu,
      workExperience: exp,
      lastCompany: company,
      currentEmploymentStatus: empSt,
      availableToJoin: avail,
      expectedSalary: salary,
      recruitmentSource: source,
      cvLink: cvLink,
      status: "Pending",
      hrNotes: _gdPick_(_GD_HR_NOTES_),
      createdBy: "Demo Generator",
      updatedAt: _gdFmt_(updatedAt),
      holdReason: "",
      holdFollowUpDate: "",
      blacklistReason: "",
      blacklistDate: "",
      blacklistUpdatedBy: "",
      employeeId: "",
      // derived
      department: _gdDept_(pos),
      isMale: isMale,
    };

    // merge overrides
    for (var k in overrides || {}) base[k] = overrides[k];
    return base;
  }

  var pending = [],
    hold = [],
    accepted = [],
    blacklist = [];

  // 40 Pending
  for (var i = 0; i < 40; i++)
    pending.push(makeCandidate({ status: "Pending" }));

  // 15 Hold
  for (var i = 0; i < 15; i++) {
    var fuDate = _gdFmtDate_(_gdAddDays_(today, _gdRandInt_(7, 30)));
    hold.push(
      makeCandidate({
        status: "Hold",
        holdReason: _gdPick_(_GD_HOLD_REASONS_),
        holdFollowUpDate: fuDate,
      }),
    );
  }

  // 30 Accepted — Employee ID diisi saat _gdWriteEmployeeSheet_ (join date + sequence)
  for (var i = 0; i < 30; i++) {
    accepted.push(
      makeCandidate({
        status: "Accepted",
        employeeId: "",
      }),
    );
  }

  // 15 Blacklist
  var now = _gdFmt_(today);
  for (var i = 0; i < 15; i++) {
    blacklist.push(
      makeCandidate({
        status: "Blacklist",
        blacklistReason: _gdPick_(_GD_BLACKLIST_REASONS_),
        blacklistDate: now,
        blacklistUpdatedBy: "HR Admin",
      }),
    );
  }

  return {
    pending: pending,
    hold: hold,
    accepted: accepted,
    blacklist: blacklist,
    all: pending.concat(hold).concat(accepted).concat(blacklist),
  };
}

// ============================================================
// HELPER: build raw row array dari objek kandidat
// ============================================================
function _gdCandidateRow_(c, extraHeaders) {
  // core 25 + extra 6
  var row = [
    c.recruitmentId,
    c.createdDate,
    c.fullName,
    "'" + c.nik,
    c.birthDate,
    c.age,
    c.gender,
    c.maritalStatus,
    c.email,
    "'" + c.phone,
    c.address,
    c.city,
    c.positionApplied,
    c.education,
    c.workExperience,
    c.lastCompany,
    c.currentEmploymentStatus,
    c.availableToJoin,
    c.expectedSalary,
    c.recruitmentSource,
    c.cvLink,
    c.status,
    c.hrNotes,
    c.createdBy,
    c.updatedAt,
    // extra
    c.holdReason,
    c.holdFollowUpDate,
    c.blacklistReason,
    c.blacklistDate,
    c.blacklistUpdatedBy,
    c.employeeId,
  ];
  if (extraHeaders) {
    // Processed Date, Processed By
    row.push(c.processedDate || "");
    row.push(c.processedBy || "");
  }
  return row;
}

// ============================================================
// WRITE: data_kandidat — hanya Pending
// ============================================================
function _gdWriteRawKandidat_(candidates) {
  var sheet = getOrCreateSheet_();
  if (candidates.length === 0) return;
  var rows = candidates.map(function (c) {
    return _gdCandidateRow_(c, false);
  });
  sheet.getRange(2, 1, rows.length, rows[0].length).setValues(rows);
  Logger.log("data_kandidat: " + rows.length + " rows written");
}

// ============================================================
// WRITE: kandidat_hold
// ============================================================
function _gdWriteHoldSheet_(candidates, today) {
  var sheet = getOrCreateHoldSheet_();
  if (candidates.length === 0) return;
  var nowStr = _gdFmt_(today);
  var rows = candidates.map(function (c) {
    c.processedDate = nowStr;
    c.processedBy = "Demo Generator";
    return _gdCandidateRow_(c, true);
  });
  sheet.getRange(2, 1, rows.length, rows[0].length).setValues(rows);
  Logger.log("kandidat_hold: " + rows.length + " rows written");
}

// ============================================================
// WRITE: kandidat_accepted
// Mengisi semua kolom termasuk Offering Letter dan Onboarding
// sesuai ACCEPTED_HEADERS (Config.gs)
// ============================================================
function _gdWriteAcceptedSheet_(candidates, today) {
  var sheet = getOrCreateAcceptedSheet_();
  if (candidates.length === 0) return;

  var nowStr = _gdFmt_(today);

  // Distribusi status offering:
  // ~40% sudah offering diterima + onboarding, ~30% offering menunggu, ~30% belum offering
  var rows = candidates.map(function (c, idx) {
    c.processedDate = nowStr;
    c.processedBy = 'Demo Generator';

    // Base row: core + extra + processed
    var base = _gdCandidateRow_(c, true);

    // Tambah kolom Offering dan Onboarding sesuai ACCEPTED_HEADERS
    var offeringCreated    = '';
    var offeringUpdated    = '';
    var offeringCreatedBy  = '';
    var offeringUpdatedBy  = '';
    var offerCompany       = '';
    var offerPosition      = '';
    var offerDept          = '';
    var offerSalary        = '';
    var offerJoinDate      = '';
    var offerBenefit       = '';
    var offerNotes         = '';
    var offerDivision      = '';
    var offerJobLevel      = '';
    var offerAreaKerja     = '';
    var offerLokasiKerja   = '';
    var offerDirectSup     = '';
    var offerGrade         = '';
    var offerResponse      = '';
    var offerRespNotes     = '';
    var offerRespDate      = '';
    var offerRespBy        = '';
    var onboardingStatus   = '';
    var onboardingDate     = '';
    var onboardingBy       = '';

    var segment = idx % 3; // 0=belum offering, 1=menunggu, 2=diterima+onboarding

    if (segment >= 1) {
      // Punya offering letter
      var offeringTs = _gdFmt_(_gdSubDays_(today, _gdRandInt_(5, 30)));
      offeringCreated   = offeringTs;
      offeringCreatedBy = 'Demo Generator';
      offerCompany      = _gdPick_(_GD_COMPANIES_INTERNAL_);
      offerPosition     = c.positionApplied;
      offerDept         = _gdDept_(c.positionApplied);
      offerDivision     = _gdPick_(_GD_DIVISIONS_);
      offerJobLevel     = _gdPick_(_GD_JOB_LEVELS_);
      offerAreaKerja    = _gdPick_(_GD_AREAS_);
      offerLokasiKerja  = c.city || _gdPick_(_GD_CITIES_);
      offerDirectSup    = _gdPick_(_GD_SUPERIORS_);
      offerGrade        = '';
      offerSalary       = String(_gdRandSalary_(c.positionApplied));
      offerJoinDate     = _gdFmtDate_(_gdSubDays_(today, _gdRandInt_(1, 20)));
      offerBenefit      = 'BPJS Kesehatan & Ketenagakerjaan, THR Tahunan';
      offerNotes        = '';
      offerResponse     = 'Menunggu';
    }

    if (segment >= 2) {
      // Offering diterima
      var respTs = _gdFmt_(_gdSubDays_(today, _gdRandInt_(1, 15)));
      offerResponse     = 'Diterima';
      offerRespNotes    = 'Kandidat menyetujui semua syarat dan kondisi.';
      offerRespDate     = respTs;
      offerRespBy       = 'Demo Generator';
      // Onboarding sudah dilakukan
      onboardingStatus  = 'Probation';
      onboardingDate    = respTs;
      onboardingBy      = 'Demo Generator';
      // Flag ke candidate agar Employee sheet ikut di-set Probation
      c._onboardingDone = true;
    }

    return base.concat([
      offeringCreated,
      offeringUpdated,
      offeringCreatedBy,
      offeringUpdatedBy,
      offerCompany,
      offerPosition,
      offerDept,
      offerSalary,
      offerJoinDate,
      offerBenefit,
      offerNotes,
      offerResponse,
      offerRespNotes,
      offerRespDate,
      offerRespBy,
      onboardingStatus,
      onboardingDate,
      onboardingBy,
      offerDivision,
      offerJobLevel,
      offerAreaKerja,
      offerLokasiKerja,
      offerDirectSup,
      offerGrade,
    ]);
  });

  sheet.getRange(2, 1, rows.length, rows[0].length).setValues(rows);
  Logger.log('kandidat_accepted: ' + rows.length + ' rows written (with offering + onboarding data)');
}

// ============================================================
// WRITE: kandidat_blacklist
// ============================================================
function _gdWriteBlacklistSheet_(candidates, today) {
  var sheet = getOrCreateBlacklistSheet_();
  if (candidates.length === 0) return;
  var nowStr = _gdFmt_(today);
  var rows = candidates.map(function (c) {
    c.processedDate = nowStr;
    c.processedBy = "Demo Generator";
    return _gdCandidateRow_(c, true);
  });
  sheet.getRange(2, 1, rows.length, rows[0].length).setValues(rows);
  Logger.log("kandidat_blacklist: " + rows.length + " rows written");
}

// ============================================================
// WRITE: Employee — 30 dari accepted + 20 legacy (54 kolom)
// Schema v3 (2026-08-14) sesuai EMPLOYEE_HEADERS
// ============================================================
function _gdWriteEmployeeSheet_(acceptedCandidates, today) {
  var sheet = getOrCreateEmployeeSheet_();
  var rows = [];
  var nowStr = _gdFmt_(today);

  var usedNiksEmp = {};
  var usedPhonesEmp = {};
  var usedEmailsEmp = {};
  var empSeqByDate = {};

  function nextEmpId(joinDate) {
    var key = joinDate.replace(/-/g, "");
    if (!empSeqByDate[key]) empSeqByDate[key] = 0;
    empSeqByDate[key]++;
    return key + _gdPad_(empSeqByDate[key], 2);
  }

  function fillRow(row, opts) {
    var joinDate = opts.joinDate;
    var empId = opts.empId || nextEmpId(joinDate);
    var empType = opts.empType;
    var isContract =
      empType === "PKWT" || empType === "Outsource" || empType === "Intern";
    var statusEmp = opts.statusEmp;
    var jobLevel = _gdPick_(_GD_JOB_LEVELS_);
    var area = _gdPick_(_GD_AREAS_);
    var division = _gdPick_(_GD_DIVISIONS_);
    var department = division;
    var location = opts.city;
    var workingEmail =
      opts.fullName.toLowerCase().replace(/\s+/g, ".") + "@mito.co.id";
    var npwp = _gdRandDigits_(15);
    var bpjsTk = _gdRandDigits_(11);
    var bpjsKes = _gdRandDigits_(13);
    var bankAcc = _gdRandDigits_(10);
    var directSuperior = _gdPick_(_GD_SUPERIORS_);
    var indirectSuperior = _gdPick_(_GD_SUPERIORS_);
    var rotationType = _gdPick_(_GD_ROTATION_TYPES_);

    var costCenter = area;
    if (area === "Head Office (HO)")
      costCenter = _gdPick_(["Accounting", "Finance", "HRD", "GA", "IT"]);
    else if (area === "Pabrik") costCenter = "Manufacture " + location;
    else if (area.indexOf("Depo") !== -1) costCenter = area;

    var jobTitle = opts.position || _gdPick_(_GD_POSITIONS_);
    var jobPosCurrent = jobTitle + " " + jobLevel + " (" + location + ")";
    var jobPosNoLoc = jobTitle + " " + jobLevel;

    var contStart = isContract ? joinDate : "";
    var contEnd = isContract
      ? _gdFmtDate_(_gdAddDays_(today, _gdRandInt_(180, 365)))
      : "";
    var contractDur = isContract ? _gdPick_(_GD_CONTRACT_DUR_) : "";
    var contractNo = isContract
      ? "MITO/PKT/" +
        today.getFullYear() +
        "/" +
        _gdPad_(_gdRandInt_(1, 999), 3)
      : "";
    var vendor = empType === "Outsource" ? _gdPick_(_GD_OS_VENDORS_) : "";

    row[EMPLOYEE_COL["Employee ID"] - 1] = empId;
    row[EMPLOYEE_COL["Full Name"] - 1] = opts.fullName;
    row[EMPLOYEE_COL["Branch Name"] - 1] =
      opts.branch || _gdPick_(_GD_BRANCHES_);
    row[EMPLOYEE_COL["Division"] - 1] = division;
    row[EMPLOYEE_COL["Department"] - 1] = department;
    row[EMPLOYEE_COL["Job Position (Location)"] - 1] = jobPosCurrent;
    row[EMPLOYEE_COL["Job Position"] - 1] = jobPosNoLoc;
    row[EMPLOYEE_COL["Area Kerja"] - 1] = area;
    row[EMPLOYEE_COL["Lokasi Kerja"] - 1] = location;
    row[EMPLOYEE_COL["Job Level"] - 1] = jobLevel;
    row[EMPLOYEE_COL["Grade"] - 1] = "";
    row[EMPLOYEE_COL["Join Date"] - 1] = joinDate;
    row[EMPLOYEE_COL["Status Employee"] - 1] = statusEmp;
    row[EMPLOYEE_COL["Direct Superior"] - 1] = directSuperior;
    row[EMPLOYEE_COL["Indirect Superior"] - 1] = indirectSuperior;
    row[EMPLOYEE_COL["Personal Email"] - 1] = opts.personalEmail;
    row[EMPLOYEE_COL["Working Email"] - 1] = workingEmail;
    row[EMPLOYEE_COL["End Date (Contract)"] - 1] = contEnd;
    row[EMPLOYEE_COL["Birth Place"] - 1] = opts.birthPlace || location;
    row[EMPLOYEE_COL["Birth Date"] - 1] = opts.birthDate;
    row[EMPLOYEE_COL["Citizen ID Address"] - 1] = opts.address;
    row[EMPLOYEE_COL["Residential Address"] - 1] = opts.address;
    row[EMPLOYEE_COL["NIK - NPWP 16 digit"] - 1] = "'" + opts.nik;
    row[EMPLOYEE_COL["NPWP"] - 1] = "'" + npwp;
    row[EMPLOYEE_COL["PTKP Status"] - 1] = _gdPick_(_GD_PTKP_);
    row[EMPLOYEE_COL["Bank Name"] - 1] = "BCA";
    row[EMPLOYEE_COL["Bank Account"] - 1] = "'" + bankAcc;
    row[EMPLOYEE_COL["Bank Account Holder"] - 1] = opts.fullName;
    row[EMPLOYEE_COL["BPJS Ketenagakerjaan"] - 1] = "'" + bpjsTk;
    row[EMPLOYEE_COL["BPJS Kesehatan"] - 1] = "'" + bpjsKes;
    row[EMPLOYEE_COL["Mobile Phone"] - 1] = "'" + opts.phone;
    row[EMPLOYEE_COL["Religion"] - 1] = _gdPick_(_GD_RELIGIONS_);
    row[EMPLOYEE_COL["Gender"] - 1] = opts.gender;
    row[EMPLOYEE_COL["Marital Status"] - 1] = opts.marital;
    row[EMPLOYEE_COL["Blood Type"] - 1] = _gdPick_(_GD_BLOOD_TYPES_);
    row[EMPLOYEE_COL["Cost Center"] - 1] = costCenter;
    row[EMPLOYEE_COL["Job Position (Former)"] - 1] = "";
    row[EMPLOYEE_COL["Type of Rotation"] - 1] = rotationType;
    row[EMPLOYEE_COL["Tanggal Mutasi/Demosi/Promosi"] - 1] = rotationType
      ? _gdFmtDate_(_gdSubDays_(today, _gdRandInt_(30, 365)))
      : "";
    row[EMPLOYEE_COL["Nomor SK"] - 1] =
      "SK." +
      _gdPad_(_gdRandInt_(1, 999), 3) +
      "/MITO/" +
      today.getFullYear();
    row[EMPLOYEE_COL["Resign Date"] - 1] = "";
    row[EMPLOYEE_COL["Outsource Vendor"] - 1] = vendor;
    row[EMPLOYEE_COL["Created By"] - 1] = "Demo Generator";
    row[EMPLOYEE_COL["Created At"] - 1] = nowStr;
    row[EMPLOYEE_COL["Updated At"] - 1] = nowStr;
    return row;
  }

  // --- 30 karyawan dari recruitment accepted ---
  var probationList = []; // track untuk ditulis ke kandidat_probation

  acceptedCandidates.forEach(function (c) {
    var joinDate = _gdFmtDate_(_gdSubDays_(today, _gdRandInt_(30, 365)));
    var empType = _gdPick_(_GD_EMP_TYPES_);
    // Jika onboarding sudah dilakukan (segment 2), status HARUS Probation
    var statusEmp = c._onboardingDone ? 'Probation' : _gdPick_(_GD_STATUS_EMP_);
    var genderMap = { "Laki-laki": "Male", Perempuan: "Female" };
    var row = new Array(EMPLOYEE_HEADERS.length).fill("");
    fillRow(row, {
      fullName: c.fullName,
      nik: c.nik,
      birthDate: c.birthDate,
      birthPlace: c.city,
      age: c.age,
      gender: genderMap[c.gender] || c.gender,
      marital:
        c.maritalStatus === "Belum Menikah"
          ? "Single"
          : c.maritalStatus === "Menikah"
            ? "Married"
            : "Widow",
      personalEmail: c.email,
      phone: c.phone,
      address: c.address,
      city: c.city,
      position: c.positionApplied,
      branch: _gdPick_(_GD_BRANCHES_),
      joinDate: joinDate,
      empType: empType,
      statusEmp: statusEmp,
      recruitmentId: c.recruitmentId,
      notes: c.hrNotes || "",
    });
    // Employee ID = join date + sequence (nextEmpId). Selaraskan ke sheet
    // kandidat_accepted dan objek candidate agar semua referensi konsisten.
    var generatedEmpId = String(row[EMPLOYEE_COL["Employee ID"] - 1] || "").replace(/^'/, "");
    c.employeeId = generatedEmpId;
    _gdSyncAcceptedEmployeeId_(c.recruitmentId, generatedEmpId);
    rows.push(row);
    // Track untuk kandidat_probation
    if (statusEmp === 'Probation') {
      var empId = generatedEmpId;
      var contractStart = String(row[EMPLOYEE_COL["Start Date (Contract)"] - 1] || joinDate);
      var contractEnd   = String(row[EMPLOYEE_COL["End Date (Contract)"] - 1]   || "");
      var contractNo    = String(row[EMPLOYEE_COL["Contract Number"] - 1]        || "");
      var contractDur   = String(row[EMPLOYEE_COL["Contract Duration"] - 1]      || "3 Bulan");
      probationList.push({
        employeeId:       empId,
        recruitmentId:    c.recruitmentId,
        contractNumber:   contractNo,
        contractDuration: contractDur,
        contractStart:    contractStart,
        contractEnd:      contractEnd,
        joinDate:         joinDate,
      });
    }
  });

  // --- 20 legacy (bukan dari recruitment, tipe campuran) ---
  for (var i = 0; i < 20; i++) {
    var isMale = Math.random() > 0.45;
    var fullName = _gdName_(isMale);
    var pos = _gdPick_(_GD_POSITIONS_);
    var age = _gdRandInt_(22, 45);
    var city = _gdPick_(_GD_CITIES_);
    var maritalRaw = _gdPick_(_GD_MARITAL_);
    var maritalMap = {
      "Belum Menikah": "Single",
      Menikah: "Married",
      Cerai: "Widow",
    };
    var marital = maritalMap[maritalRaw] || "Single";
    var empType = _gdPick_(_GD_EMP_TYPES_);
    var statusEmp = _gdPick_(_GD_STATUS_EMP_);

    var nik;
    do {
      nik = _gdRandDigits_(16);
    } while (usedNiksEmp[nik]);
    usedNiksEmp[nik] = true;

    var phone;
    do {
      phone = "08" + _gdRandDigits_(10);
    } while (usedPhonesEmp[phone]);
    usedPhonesEmp[phone] = true;

    var nameParts = fullName.toLowerCase().split(" ");
    var emailBase =
      nameParts[0] + "." + (nameParts[1] || "x").replace(/[^a-z]/g, "");
    var emailIdx = _gdRandInt_(100, 999);
    var personalEmail = emailBase + emailIdx + "@gmail.com";
    while (usedEmailsEmp[personalEmail]) {
      emailIdx++;
      personalEmail = emailBase + emailIdx + "@gmail.com";
    }
    usedEmailsEmp[personalEmail] = true;

    var birthYear = new Date().getFullYear() - age;
    var birthDate =
      birthYear +
      "-" +
      _gdPad_(_gdRandInt_(1, 12)) +
      "-" +
      _gdPad_(_gdRandInt_(1, 28));
    var address =
      _gdPick_(_GD_STREETS_) + " No. " + _gdRandInt_(1, 150) + ", " + city;
    var joinDate = _gdFmtDate_(_gdSubDays_(today, _gdRandInt_(90, 730)));

    var row = new Array(EMPLOYEE_HEADERS.length).fill("");
    fillRow(row, {
      fullName: fullName,
      nik: nik,
      birthDate: birthDate,
      birthPlace: city,
      age: age,
      gender: isMale ? "Male" : "Female",
      marital: marital,
      personalEmail: personalEmail,
      phone: phone,
      address: address,
      city: city,
      position: pos,
      branch: _gdPick_(_GD_BRANCHES_),
      joinDate: joinDate,
      empType: empType,
      statusEmp: statusEmp,
      recruitmentId: "",
      notes: "",
    });
    rows.push(row);
  }

  if (rows.length > 0) {
    sheet.getRange(2, 1, rows.length, EMPLOYEE_HEADERS.length).setValues(rows);
  }
  Logger.log(
    "Employee: " +
      rows.length +
      " rows written (30 recruitment + 20 legacy, 54 cols)",
  );
  return probationList; // kembalikan list untuk dipakai _gdWriteProbationSheet_
}

// ============================================================
// WRITE: kandidat_probation — satu record per employee Probation
// Eval fields dikosongkan (belum dievaluasi saat generate)
// ============================================================
function _gdWriteProbationSheet_(probationList, today) {
  if (!probationList || probationList.length === 0) {
    Logger.log("kandidat_probation: 0 rows (tidak ada employee Probation)");
    return;
  }

  var sheet  = getOrCreateProbationSheet_();
  var nowStr = _gdFmt_(today);
  var seqMap = {};

  function nextProbId() {
    var key = _gdFmtDate_(today).replace(/-/g, "");
    if (!seqMap[key]) seqMap[key] = 0;
    seqMap[key]++;
    return "PROB-" + key + "-" + _gdPad_(seqMap[key], 4);
  }

  var rows = probationList.map(function (p) {
    var row = new Array(PROBATION_HEADERS.length).fill("");
    row[PROBATION_COL["Probation ID"] - 1]      = nextProbId();
    row[PROBATION_COL["Employee ID"] - 1]        = p.employeeId;
    row[PROBATION_COL["Recruitment ID"] - 1]     = p.recruitmentId;
    row[PROBATION_COL["Contract Number"] - 1]    = p.contractNumber;
    row[PROBATION_COL["Contract Duration"] - 1]  = p.contractDuration;
    row[PROBATION_COL["Contract Start"] - 1]     = p.contractStart;
    row[PROBATION_COL["Contract End"] - 1]       = p.contractEnd;
    row[PROBATION_COL["Join Date"] - 1]          = p.joinDate;
    row[PROBATION_COL["Status"] - 1]             = "Probation";
    row[PROBATION_COL["Onboarding Date"] - 1]    = nowStr;
    row[PROBATION_COL["Onboarding By"] - 1]      = "Demo Generator";
    row[PROBATION_COL["SK Status"] - 1]          = "Pending";
    row[PROBATION_COL["Created At"] - 1]         = nowStr;
    row[PROBATION_COL["Updated At"] - 1]         = nowStr;
    return row;
  });

  sheet.getRange(2, 1, rows.length, PROBATION_HEADERS.length).setValues(rows);
  Logger.log("kandidat_probation: " + rows.length + " rows written");
}

// ============================================================
// WRITE: Audit_Log — trail aktivitas untuk semua kandidat
// ============================================================
function _gdWriteAuditLog_(allCandidates, today) {
  var sheet = getOrCreateAuditLogSheet_();
  var rows = [];
  var user = "Demo Generator";

  allCandidates.forEach(function (c) {
    var createdTs = c.createdDate;

    // Baris 1: Created
    rows.push([
      c.recruitmentId,
      "Created",
      "Status",
      "-",
      "Pending",
      user,
      createdTs,
    ]);

    // Baris 2+: status change jika bukan Pending
    if (c.status === "Hold") {
      var ts =
        c.processedDate ||
        _gdFmt_(_gdRandTime_(_gdSubDays_(today, _gdRandInt_(1, 10))));
      rows.push([
        c.recruitmentId,
        "Hold",
        "Status",
        "Pending",
        "Hold (" + c.holdReason + ")",
        user,
        ts,
      ]);
    } else if (c.status === "Accepted") {
      var ts =
        c.processedDate ||
        _gdFmt_(_gdRandTime_(_gdSubDays_(today, _gdRandInt_(1, 30))));
      rows.push([
        c.recruitmentId,
        "Accepted",
        "Status",
        "Pending",
        "Accepted -> Employee " + c.employeeId,
        user,
        ts,
      ]);
    } else if (c.status === "Blacklist") {
      var ts =
        c.processedDate ||
        _gdFmt_(_gdRandTime_(_gdSubDays_(today, _gdRandInt_(1, 20))));
      rows.push([
        c.recruitmentId,
        "Blacklist",
        "Status",
        "Pending",
        "Blacklist (" + c.blacklistReason + ")",
        user,
        ts,
      ]);
    }

    // HR Notes update jika ada catatan
    if (c.hrNotes && c.hrNotes.length > 0) {
      rows.push([
        c.recruitmentId,
        "HR Notes Update",
        "HR Notes",
        "",
        c.hrNotes.substring(0, 60),
        user,
        c.updatedAt,
      ]);
    }
  });

  if (rows.length > 0) {
    sheet.getRange(2, 1, rows.length, AUDIT_LOG_HEADERS.length).setValues(rows);
  }
  Logger.log("Audit_Log: " + rows.length + " rows written");
}

// ============================================================
// UTILITY: hapus semua data di semua sheet (reset bersih)
// Sheet header tetap dipertahankan.
// ============================================================
function clearAllHRISData() {
  _gdClearSheets_();
  Logger.log("All HRIS data cleared (headers preserved).");
  return "OK — semua data dihapus, header tetap.";
}
