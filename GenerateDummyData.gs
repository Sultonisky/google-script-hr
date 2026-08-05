// ============================================================
// GenerateDummyData.gs — Complete HRIS Demo Dataset Generator
// ============================================================
// Run: generateAllHRISDemoData() from Apps Script editor
// Generates: Recruitment (100), Employee (100), Outsource (100), Audit Logs
// (Master data is derived dynamically from the Employee sheet.)
// ============================================================

function generateAllHRISDemoData() {
  var lock = LockService.getScriptLock();
  lock.waitLock(60000);
  try {
    Logger.log("===== START: Generate HRIS Demo Data =====");
    var today = new Date();

    // 2. Recruitment (100 candidates)
    var recResult = generateRecruitmentData_(today);

    // 3. Employee (50 legacy + 50 from recruitment)
    var empResult = generateEmployeeData_(recResult.accepted, today);

    // 4. Outsource (100 records)
    var osCount = generateOutsourceData_(today);

    // 5. Audit Logs
    generateAuditLogs_(
      recResult.records,
      empResult.legacyIds,
      empResult.recIds,
    );

    Logger.log("===== COMPLETE =====");
    Logger.log(
      "Recruitment: " +
        recResult.count +
        " (" +
        recResult.accepted.length +
        " accepted)",
    );
    Logger.log("Employee: " + empResult.count);
    Logger.log("Outsource: " + osCount);

    return (
      "OK — " +
      "Rec:" +
      recResult.count +
      " Emp:" +
      empResult.count +
      " OS:" +
      osCount
    );
  } catch (e) {
    Logger.log("ERROR: " + e.toString());
    return "Error: " + e.toString();
  } finally {
    lock.releaseLock();
  }
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================
function _gdPick_(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}
function _gdRandInt_(a, b) {
  return Math.floor(Math.random() * (b - a + 1)) + a;
}
function _gdPad_(n, s) {
  var r = "" + n;
  while (r.length < s) r = "0" + r;
  return r;
}
function _gdAddMultiple_(arr, val, cnt) {
  for (var i = 0; i < cnt; i++) arr.push(val);
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
  var c = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
  var s = "";
  for (var i = 0; i < n; i++)
    s += c.charAt(Math.floor(Math.random() * c.length));
  return s;
}
function _gdNow_() {
  return Utilities.formatDate(new Date(), "GMT+7", "yyyy-MM-dd HH:mm:ss");
}
function _gdDateStr_(d) {
  return Utilities.formatDate(d, "GMT+7", "yyyy-MM-dd HH:mm:ss");
}
function _gdDateOnly_(d) {
  return Utilities.formatDate(d, "GMT+7", "yyyy-MM-dd");
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
function _gdMaleNames_() {
  return [
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
    "Ujang",
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
    "Cipto",
    "Dharmawan",
    "Eko",
    "Firman",
    "Guruh",
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
    "Taufik",
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
    "Javier",
    "Kamal",
    "Luthfi",
    "Mikail",
    "Nabil",
    "Osman",
    "Putra",
    "Raka",
    "Salsa",
    "Tariq",
    "Ubay",
    "Vicky",
    "Wahid",
    "Xavier",
    "Yusuf",
    "Zidan",
    "Alif",
    "Bara",
  ];
}
function _gdFemaleNames_() {
  return [
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
    "Oktaviani",
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
    "Eris",
    "Fatimah",
    "Gita",
    "Hidayah",
    "Ira",
    "Jannah",
    "Khalida",
    "Laila",
    "Mira",
    "Nisa",
    "Oka",
    "Purnama",
    "Qanita",
    "Rahma",
    "Salsabila",
    "Tia",
    "Ummi",
    "Vera",
    "Wahyuni",
    "Xenia",
    "Yani",
    "Zahara",
    "Aldila",
    "Bianca",
    "Cassia",
    "Dhea",
    "Eldora",
    "Farah",
    "Gisel",
    "Hana",
    "Izzati",
    "Jihane",
    "Khalisah",
    "Lutfia",
    "Mutiara",
    "Nadhira",
    "Olive",
    "Prilly",
    "Quinn",
    "Rahma",
  ];
}
function _gdLastNames_() {
  return [
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
    "Togatorop",
    "Sinaga",
    "Nainggolan",
    "Hutapea",
    "Panggabean",
    "Sitorus",
    "Ginting",
    "Pardede",
    "Silalahi",
    "Rumapea",
    "Tambunan",
    "Purnama",
    "Wibowo",
    "Hartono",
    "Prasetyo",
    "Wahyudi",
    "Lestari",
    "Hidayat",
    "Saputra",
    "Wijaya",
    "Susanto",
    "Rahman",
    "Firmansyah",
    "Setiawan",
    "Suryadi",
    "Kurniawan",
    "Putra",
    "Santoso",
    "Ardianto",
    "Nugroho",
    "Hidayat",
    "Siregar",
    "Tampubolon",
    "Manurung",
    "Purba",
    "Simanjuntak",
    "Limbong",
    "Togatorop",
    "Sinaga",
    "Nainggolan",
    "Hutapea",
    "Panggabean",
    "Sitorus",
    "Ginting",
    "Pardede",
    "Silalahi",
    "Rumapea",
    "Tambunan",
    "Purnama",
    "Wibowo",
    "Hartono",
    "Prasetyo",
    "Wahyudi",
    "Lestari",
    "Hidayat",
    "Saputra",
    "Wijaya",
    "Susanto",
    "Rahman",
    "Firmansyah",
    "Setiawan",
    "Suryadi",
    "Kurniawan",
    "Putra",
    "Santoso",
    "Ardianto",
    "Nugroho",
    "Hidayat",
    "Siregar",
    "Tampubolon",
  ];
}
function _gdStreetNames_() {
  return [
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
    "Jl. Sisingamangaraja",
    "Jl. Rasuna Said",
    "Jl. Kuningan",
    "Jl. TB Simatupang",
    "Jl. HR Rasuna Said",
    "Jl. M.H. Thamrin",
    "Jl. Jenderal Soedirman",
    "Jl. Mangga Besar",
    "Jl. Kali Besar",
    "Jl. Cikini",
    "Jl. Menteng",
    "Jl. Salemba",
    "Jl. Senen",
    "Jl. Jatinegara",
    "Jl. Duren Sawit",
    "Jl. Tebet",
    "Jl. Pancoran",
    "Jl. Pasar Minggu",
    "Jl. Lenteng Agung",
    "Jl. Srengseng",
    "Jl. Kembangan",
    "Jl. Meruya",
    "Jl. Puri Kembangan",
    "Jl. Cengkareng",
    "Jl. Grogol",
    "Jl. Kemanggisan",
    "Jl. Palmerah",
    "Jl. Cipete",
    "Jl. Fatmawati",
    "Jl. Bangka",
    "Jl. Kemang",
    "Jl. Pejaten",
    "Jl. Warung Jati",
    "Jl. Margonda",
    "Jl. Merdeka",
    "Jl. Asia Afrika",
    "Jl. Naripan",
    "Jl. Braga",
    "Jl. Asia Afrika",
    "Jl. Diponegoro",
    "Jl. Supratman",
    "Jl. Pahlawan",
    "Jl. Perintis Kemerdekaan",
    "Jl. H.R. Rasuna Said",
    "Jl. Casablanca",
    "Jl. Kyai Maja",
    "Jl. Prof. Dr. Satrio",
    "Jl. Haji Nawi",
    "Jl. Gandaria",
    "Jl. Cipete Raya",
    "Jl. Pos Pengumben",
  ];
}
function _gdGetCompanies_() {
  return [
    "PT Telkom Indonesia",
    "PT Bank Mandiri",
    "PT Pertamina",
    "PT PLN",
    "PT Garuda Indonesia",
    "PT Astra International",
    "PT Unilever Indonesia",
    "PT Indofood Sukses Makmur",
    "PT Telekomunikasi Seluler",
    "PT Matahari Putra Prima",
    "PT Kalbe Farma",
    "PT BRI",
    "PT BCA",
    "PT Bank Negara Indonesia",
    "PT Wijaya Karya",
    "PT Adhi Karya",
    "PT Jasa Marga",
    "PT Angkasa Pura",
    "PT Pos Indonesia",
    "PT Kereta Api Indonesia",
    "PT Samsung Electronics Indonesia",
    "PT Xiaomi Communications",
    "PT Gojek Indonesia",
    "PT Tokopedia",
    "PT Traveloka",
    "PT Shopee Indonesia",
    "PT Grab Indonesia",
    "PT Lazada Indonesia",
    "PT Bukalapak",
    "PT Blibli.com",
    "PT Astra Otoparts",
    "PT Charoen Pokphand Indonesia",
    "PT Siam Cement Indonesia",
    "PT Sumitomo Indonesia",
    "PT Mitsui Indonesia",
    "PT Toyota Motor Manufacturing Indonesia",
    "PT Honda Prospect Motor",
    "PT Daihatsu Indonesia",
    "PT Suzuki Indomobil Motor",
    "PT Yamaha Motor Indonesia",
    "PT Astra Honda Motor",
    "PT Kawasaki Motor Indonesia",
    "PT Bimantara Citra",
    "PT Lion Air",
    "PT Sriwijaya Air",
    "PT Batik Air",
    "PT Citilink",
    "PT Wings Air",
    "PT Garuda Indonesia",
    "PT Indonesia AirAsia",
    "PT Trigana Air Service",
    "PT Aviastar",
    "PT Susi Air",
    "PT Trans Nusa",
    "PT Nam Air",
  ];
}
function _gdGetCities_() {
  return [
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
    "Bengkulu",
    "Jambi",
    "Palangka Raya",
    "Samarinda",
    "Manado",
    "Gorontalo",
    "Palu",
    "Mamuju",
    "Ambon",
    "Ternate",
    "Jayapura",
    "Manokwari",
    "Sorong",
    "Merauke",
    "Wamena",
    "Banda Aceh",
    "Padang",
  ];
}
function _gdPickForPosition_(pos, departments) {
  var map = {
    Director: ["Human Resources", "Finance", "IT", "Operations", "Marketing"],
    GM: [
      "Human Resources",
      "Finance",
      "IT",
      "Operations",
      "Marketing",
      "Sales",
    ],
    Manager: [
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
    ],
    Supervisor: departments,
    "Team Lead": departments,
    "Senior Staff": departments,
    Staff: departments,
    "Junior Staff": departments,
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
  return _gdPick_(map[pos] || departments);
}

// ============================================================
// 1. RECRUITMENT DATA GENERATION
// ============================================================
function generateRecruitmentData_(today) {
  var sheet = getOrCreateSheet_();
  var existingRows = sheet.getLastRow();
  if (existingRows > 1) {
    Logger.log(
      "raw_kandidat already has " + (existingRows - 1) + " rows. Clearing...",
    );
    sheet.deleteRows(2, existingRows - 1);
  }

  var records = [];
  var acceptedRecords = [];

  var statusList = [];
  _gdAddMultiple_(statusList, "Accepted", 50);
  _gdAddMultiple_(statusList, "Applied", 8);
  _gdAddMultiple_(statusList, "Screening", 6);
  _gdAddMultiple_(statusList, "Interview", 6);
  _gdAddMultiple_(statusList, "Psychotest", 4);
  _gdAddMultiple_(statusList, "HR Interview", 4);
  _gdAddMultiple_(statusList, "User Interview", 4);
  _gdAddMultiple_(statusList, "Offering", 4);
  _gdAddMultiple_(statusList, "Rejected", 6);
  _gdAddMultiple_(statusList, "Withdrawn", 3);
  _gdAddMultiple_(statusList, "Hold", 4);
  _gdAddMultiple_(statusList, "Blacklist", 1);
  _gdShuffle_(statusList);

  var usedEmails = {};
  var usedPhones = {};
  var usedNik = {};

  var departments = [
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
  var positions = [
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
  ];
  var educationLevels = ["SMA", "SMK", "D3", "S1", "S2"];
  var workExps = [
    "Fresh Graduate",
    "< 1 Tahun",
    "1-2 Tahun",
    "2-3 Tahun",
    "3-5 Tahun",
    "5-10 Tahun",
    "> 10 Tahun",
  ];
  var companies = _gdGetCompanies_();
  var cities = _gdGetCities_();
  var sources = [
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
  ];
  var empStatuses = [
    "Employed Full Time",
    "Employed Contract",
    "Part Time",
    "Freelance",
    "Unemployed",
    "Resigned",
    "Fresh Graduate",
  ];
  var availableJoins = [
    "Segera",
    "1 Minggu",
    "2 Minggu",
    "1 Bulan",
    "2 Bulan",
    "3 Bulan",
    "Bisa Negosiasi",
  ];
  var maritalStatuses = ["Single", "Married", "Divorced"];

  var holdReasons = [
    "Kandidat minta penundaan",
    "Posisi belum dibuka",
    "Budget belum tersedia",
    "Menunggu hasil background check",
  ];

  var blacklistReasons = [
    "Tidak hadir tanpa konfirmasi",
    "Berdusta dalam aplikasi",
    "Pelanggaran etika saat interview",
  ];

  var hrNotesPool = [
    "Kandidat memiliki pengalaman yang relevan dengan posisi yang dilamar.",
    "CV lengkap dan menarik, perlu follow up untuk interview.",
    "Kandidat direferensikan oleh tim internal.",
    "Hasil tes technical cukup baik, perlu evaluasi lebih lanjut.",
    "Tingkat komunikasi kandidat sangat baik.",
    "Kandidat sudah memiliki pengalaman di industri serupa.",
    "Perlu konfirmasi gaji yang diharapkan dengan budget yang tersedia.",
    "Kandidat bersedia untuk remote work.",
    "Jadwalkan ulang interview karena kandidat berhalangan.",
    "Portfolio kandidat sangat impresif.",
    "Kandidat menunjukkan kemampuan leadership yang baik.",
    "Butuh waktu lebih untuk evaluasi technical test.",
    "",
    "",
    "",
  ];

  for (var i = 0; i < 100; i++) {
    var status = statusList[i];
    var isMale = Math.random() > 0.45;
    var firstName = isMale
      ? _gdPick_(_gdMaleNames_())
      : _gdPick_(_gdFemaleNames_());
    var lastName = _gdPick_(_gdLastNames_());
    var fullName = firstName + " " + lastName;

    var position = _gdPick_(positions);
    var dept = _gdPickForPosition_(position, departments);
    var education = _gdPick_(educationLevels);
    var workExp = _gdPick_(workExps);

    var age;
    if (workExp === "Fresh Graduate" || workExp === "< 1 Tahun")
      age = _gdRandInt_(20, 25);
    else if (workExp === "1-2 Tahun") age = _gdRandInt_(22, 28);
    else if (workExp === "2-3 Tahun") age = _gdRandInt_(24, 30);
    else if (workExp === "3-5 Tahun") age = _gdRandInt_(26, 33);
    else if (workExp === "5-10 Tahun") age = _gdRandInt_(28, 38);
    else age = _gdRandInt_(32, 50);
    if (age > 50) age = 50;

    var birthYear = today.getFullYear() - age;
    var birthDate =
      birthYear +
      "-" +
      _gdPad_(_gdRandInt_(1, 12)) +
      "-" +
      _gdPad_(_gdRandInt_(1, 28));

    var phone;
    do {
      phone = "08" + _gdRandDigits_(10);
    } while (usedPhones[phone]);
    usedPhones[phone] = true;

    var emailBase =
      firstName.toLowerCase() +
      "." +
      lastName.toLowerCase().replace(/[^a-z]/g, "");
    var emailSuffix = _gdRandInt_(1, 999);
    var email = emailBase + emailSuffix + "@gmail.com";
    var emailAttempt = 0;
    while (usedEmails[email]) {
      emailAttempt++;
      email = emailBase + emailSuffix + emailAttempt + "@gmail.com";
    }
    usedEmails[email] = true;

    var nik;
    do {
      nik = _gdRandDigits_(16);
    } while (usedNik[nik]);
    usedNik[nik] = true;

    var city = _gdPick_(cities);
    var address =
      _gdPick_(_gdStreetNames_()) + " No. " + _gdRandInt_(1, 150) + ", " + city;

    var expectedSalary = _gdRandSalary_(position);
    var company =
      workExp === "Fresh Graduate" || workExp === "< 1 Tahun"
        ? "-"
        : _gdPick_(companies);

    var cvLink = "";
    if (Math.random() > 0.3) {
      cvLink =
        "https://drive.google.com/file/d/" + _gdRandAlphanum_(15) + "/view";
    }

    var createdDateObj = new Date(
      today.getTime() - _gdRandInt_(5, 120) * 86400000,
    );
    createdDateObj.setHours(
      _gdRandInt_(8, 17),
      _gdRandInt_(0, 59),
      _gdRandInt_(0, 59),
    );
    var createdDateStr = Utilities.formatDate(
      createdDateObj,
      "GMT+7",
      "yyyy-MM-dd HH:mm:ss",
    );

    var updatedDateObj = new Date(
      createdDateObj.getTime() +
        _gdRandInt_(
          0,
          Math.max(
            1,
            Math.floor((today.getTime() - createdDateObj.getTime()) / 86400000),
          ),
        ) *
          86400000,
    );
    updatedDateObj.setHours(
      _gdRandInt_(8, 17),
      _gdRandInt_(0, 59),
      _gdRandInt_(0, 59),
    );
    var updatedAtStr = Utilities.formatDate(
      updatedDateObj,
      "GMT+7",
      "yyyy-MM-dd HH:mm:ss",
    );

    var recDatePart = Utilities.formatDate(createdDateObj, "GMT+7", "yyyyMMdd");
    var recId = "REC-" + recDatePart + "-" + _gdPad_(i + 1, 6);

    var holdReason = "",
      holdFollowUpDate = "";
    var blacklistReason = "",
      blacklistDate = "",
      blacklistUpdatedBy = "";
    var employeeId = "";

    if (status === "Hold") {
      holdReason = _gdPick_(holdReasons);
      var fuDate = new Date(today.getTime() + _gdRandInt_(7, 30) * 86400000);
      holdFollowUpDate = Utilities.formatDate(fuDate, "GMT+7", "yyyy-MM-dd");
    }

    if (status === "Blacklist") {
      blacklistReason = _gdPick_(blacklistReasons);
      blacklistDate = updatedAtStr;
      blacklistUpdatedBy = "HR Admin";
    }

    if (status === "Accepted") {
      employeeId = "EMP-" + today.getFullYear() + "-" + _gdPad_(i + 1, 5);
    }

    var row = [
      recId, // Recruitment ID
      createdDateStr, // Created Date
      fullName, // Full Name
      "'" + nik, // NIK
      birthDate, // Birth Date
      age, // Age
      isMale ? "Male" : "Female", // Gender
      _gdPick_(maritalStatuses), // Marital Status
      email, // Email
      "'" + phone, // Phone
      address, // Address
      city, // City
      position, // Position Applied
      education, // Education
      workExp, // Work Experience
      company, // Last Company
      _gdPick_(empStatuses), // Current Employment Status
      _gdPick_(availableJoins), // Available to Join
      expectedSalary, // Expected Salary
      _gdPick_(sources), // Recruitment Source
      cvLink, // CV Link
      status, // Status
      _gdPick_(hrNotesPool), // HR Notes
      "Demo Generator", // Created By
      updatedAtStr, // Updated At
      holdReason, // Hold Reason (extra)
      holdFollowUpDate, // Hold Follow Up Date (extra)
      blacklistReason, // Blacklist Reason (extra)
      blacklistDate, // Blacklist Date (extra)
      blacklistUpdatedBy, // Blacklist Updated By (extra)
      employeeId, // Employee ID (extra)
    ];

    records.push({
      recruitmentId: recId,
      status: status,
      row: row,
      employeeId: employeeId,
      fullName: fullName,
    });

    if (status === "Accepted") {
      acceptedRecords.push({
        recruitmentId: recId,
        employeeId: employeeId,
        fullName: fullName,
        nik: nik,
        gender: isMale ? "Male" : "Female",
        email: email,
        phone: phone,
        address: address,
        city: city,
        position: position,
        department: dept,
        education: education,
        workExp: workExp,
        age: age,
        birthDate: birthDate,
        maritalStatus: _gdPick_(maritalStatuses),
        expectedSalary: expectedSalary,
        createdDate: createdDateStr,
        updatedAt: updatedAtStr,
      });
    }
  }

  var batchData = records.map(function (r) {
    return r.row;
  });
  if (batchData.length > 0) {
    var startRow = 2;
    sheet
      .getRange(startRow, 1, batchData.length, batchData[0].length)
      .setValues(batchData);
  }

  Logger.log(
    "Recruitment: " +
      records.length +
      " candidates written (" +
      acceptedRecords.length +
      " accepted)",
  );
  return { count: records.length, accepted: acceptedRecords, records: records };
}

// ============================================================
// 2. EMPLOYEE DATA GENERATION (50 legacy + 50 from recruitment)
// ============================================================
function generateEmployeeData_(acceptedCandidates, today) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var empSheet = ss.getSheetByName(EMPLOYEE_SHEET_NAME);

  var useFullHeaders = true;
  if (empSheet && empSheet.getLastRow() > 0) {
    var existingHeaders = empSheet
      .getRange(1, 1, 1, empSheet.getLastColumn())
      .getValues()[0];
    if (existingHeaders.indexOf("Nama Lengkap") !== -1) {
      useFullHeaders = false;
    }
  }

  var headers = EMPLOYEE_HEADERS;
  if (!empSheet) {
    empSheet = ss.insertSheet(EMPLOYEE_SHEET_NAME);
    empSheet.getRange(1, 1, 1, headers.length).setValues([headers]);
    empSheet
      .getRange(1, 1, 1, headers.length)
      .setBackground("#005BAC")
      .setFontColor("#ffffff")
      .setFontWeight("bold");
    empSheet.setFrozenRows(1);
  }

  if (empSheet.getLastRow() > 1) {
    empSheet.deleteRows(2, empSheet.getLastRow() - 1);
  }

  var empRows = [];
  var empIds = [];
  var legacyIds = [];
  var recIds = [];
  var empCounter = 1;

  var departments = [
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
  var positions = [
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
  ];
  var educationLevels = ["SMA", "SMK", "D3", "S1", "S2"];
  var workLocations = [
    "Jakarta Pusat",
    "Jakarta Selatan",
    "Jakarta Barat",
    "Jakarta Timur",
    "Jakarta Utara",
    "Bandung",
    "Surabaya",
    "Semarang",
    "Yogyakarta",
    "Medan",
    "Makassar",
    "Balikpapan",
  ];
  var companies = [
    "PT Mahakarya Sukses Indonesia",
    "PT Mahakarya Tech",
    "PT Mahakarya Trading",
    "PT Mahakarya Logistics",
    "PT Mahakarya Capital",
  ];
  var employeeTypes = ["PKWTT", "PKWT", "Outsource", "Intern", "Freelance"];
  var empStatuses = [
    "Active",
    "Resigned",
    "Terminated",
    "On Leave",
    "Probation",
  ];
  var contractDurations = [
    "3 Bulan",
    "6 Bulan",
    "1 Tahun",
    "2 Tahun",
    "3 Tahun",
  ];
  var salaryTypes = ["Monthly", "Daily", "Project-Based", "Hourly"];
  var maritalStatuses = ["Single", "Married", "Divorced", "Widowed"];
  var bloodTypes = ["A", "B", "AB", "O", "-"];
  var cities = _gdGetCities_();
  var streetNames = _gdStreetNames_();

  // 50 Legacy employees
  for (var i = 0; i < 50; i++) {
    var isMale = Math.random() > 0.48;
    var firstName = isMale
      ? _gdPick_(_gdMaleNames_())
      : _gdPick_(_gdFemaleNames_());
    var lastName = _gdPick_(_gdLastNames_());
    var fullName = firstName + " " + lastName;

    var position = _gdPick_(positions);
    var dept = _gdPickForPosition_(position, departments);
    var education = _gdPick_(educationLevels);
    var workLoc = _gdPick_(workLocations);
    var company = _gdPick_(companies);
    var empType = "PKWTT";
    var empStatus = _gdPick_(empStatuses);
    var salaryType = "Monthly";
    var salary = _gdRandSalary_(position);

    var age;
    if (position === "Director" || position === "GM") age = _gdRandInt_(40, 55);
    else if (position === "Manager") age = _gdRandInt_(35, 50);
    else if (position === "Supervisor" || position === "Team Lead")
      age = _gdRandInt_(30, 45);
    else if (position === "Senior Staff") age = _gdRandInt_(28, 40);
    else age = _gdRandInt_(22, 35);

    var birthYear = today.getFullYear() - age;
    var birthDate =
      birthYear +
      "-" +
      _gdPad_(_gdRandInt_(1, 12)) +
      "-" +
      _gdPad_(_gdRandInt_(1, 28));
    var marital = _gdPick_(maritalStatuses);
    var blood = _gdPick_(bloodTypes);

    var hireYear = today.getFullYear() - _gdRandInt_(0, 10);
    var hireMonth = _gdRandInt_(1, 12);
    var hireDay = _gdRandInt_(1, 28);
    var hireDate = hireYear + "-" + _gdPad_(hireMonth) + "-" + _gdPad_(hireDay);

    var probEndObj = new Date(hireYear + 1, hireMonth - 1, hireDay);
    var probEndDate = _gdDateOnly_(probEndObj);

    var contractEndDate = "";
    if (empType === "PKWT") {
      var ce = new Date(
        today.getFullYear() + 1,
        _gdRandInt_(0, 11),
        _gdRandInt_(1, 28),
      );
      contractEndDate = _gdDateOnly_(ce);
    }

    var nik;
    do {
      nik = _gdRandDigits_(16);
    } while (usedNikGlobal(nik));
    markNikUsed(nik);

    var emailBase =
      firstName.toLowerCase() +
      "." +
      lastName.toLowerCase().replace(/[^a-z]/g, "");
    var email = emailBase + _gdRandInt_(1, 999) + "@mahakarya.co.id";
    var phone = "08" + _gdRandDigits_(10);

    var address =
      _gdPick_(streetNames) +
      " No. " +
      _gdRandInt_(1, 150) +
      ", " +
      _gdPick_(cities);

    var empId = "EMP-" + hireYear + "-" + _gdPad_(empCounter, 5);
    empCounter++;

    // Baris sesuai EMPLOYEE_HEADERS (37 kolom) dari Config.gs
    var row = [
      empId, // 1. Employee ID
      "", // 2. Recruitment ID
      fullName, // 3. Full Name
      position, // 4. Position
      email, // 5. Email
      "'" + phone, // 6. Phone
      hireDate, // 7. Join Date
      empStatus, // 8. Status
      "", // 9. Notes
      _gdNow_(), // 10. Created At
      company, // 11. Company Entity
      empType, // 12. Employee Type
      "'" + nik, // 13. NIK
      birthDate, // 14. Birth Date
      age, // 15. Age
      isMale ? "Male" : "Female", // 16. Gender
      marital, // 17. Marital Status
      address, // 18. Address
      _gdPick_(cities), // 19. City
      education, // 20. Education
      "", // 21. Work Experience
      dept, // 22. Department
      "", // 23. Division
      "", // 24. Branch
      "", // 25. Contract Start
      contractEndDate, // 26. Contract End
      "", // 27. Contract Duration
      empStatus, // 28. Employment Status
      salary, // 29. Salary
      salaryType, // 30. Salary Type
      "", // 31. Outsource Vendor
      "", // 32. Contract Number
      "", // 33. District
      "", // 34. Recruitment Source
      "", // 35. HR Notes
      "Demo Generator", // 36. Created By
      _gdNow_(), // 37. Updated At
    ];

    empRows.push(row);
    empIds.push(empId);
    legacyIds.push(empId);
  }

  // 50 from recruitment (accepted)
  for (var j = 0; j < acceptedCandidates.length && j < 50; j++) {
    var c = acceptedCandidates[j];
    var isMale = c.gender === "Male";
    var age = c.age;
    var birthDate = c.birthDate;
    var marital = c.maritalStatus;

    var hireDateObj = new Date(c.createdDate);
    var hireDate = _gdDateOnly_(hireDateObj);
    var probEndObj = new Date(
      hireDateObj.getFullYear() + 1,
      hireDateObj.getMonth(),
      hireDateObj.getDate(),
    );
    var probEndDate = _gdDateOnly_(probEndObj);

    var empId = c.employeeId;
    recIds.push(empId);

    // Baris sesuai EMPLOYEE_HEADERS (37 kolom) dari Config.gs
    var row = [
      empId, // 1. Employee ID
      c.recruitmentId, // 2. Recruitment ID
      c.fullName, // 3. Full Name
      c.position, // 4. Position
      c.email, // 5. Email
      "'" + c.phone, // 6. Phone
      hireDate, // 7. Join Date
      "Active", // 8. Status
      "", // 9. Notes
      c.updatedAt, // 10. Created At
      "PT Mahakarya Sukses Indonesia", // 11. Company Entity
      "PKWTT", // 12. Employee Type
      "'" + c.nik, // 13. NIK
      birthDate, // 14. Birth Date
      age, // 15. Age
      c.gender, // 16. Gender
      marital, // 17. Marital Status
      c.address, // 18. Address
      c.city, // 19. City
      c.education, // 20. Education
      c.workExp, // 21. Work Experience
      c.department, // 22. Department
      "", // 23. Division
      "", // 24. Branch
      "", // 25. Contract Start
      "", // 26. Contract End
      "", // 27. Contract Duration
      "Probation", // 28. Employment Status
      c.expectedSalary, // 29. Salary
      "Monthly", // 30. Salary Type
      "", // 31. Outsource Vendor
      "", // 32. Contract Number
      "", // 33. District
      "", // 34. Recruitment Source
      "", // 35. HR Notes
      "Demo Generator", // 36. Created By
      c.updatedAt, // 37. Updated At
    ];

    empRows.push(row);
    empIds.push(empId);
  }

  if (empRows.length > 0) {
    empSheet.getRange(2, 1, empRows.length, headers.length).setValues(empRows);
  }

  Logger.log(
    "Employee: " +
      empRows.length +
      " employees written (" +
      legacyIds.length +
      " legacy + " +
      recIds.length +
      " from recruitment)",
  );
  return {
    count: empRows.length,
    legacyIds: legacyIds,
    recIds: recIds,
    ids: empIds,
  };
}

// NIK tracking for employee generation
var _gdUsedNik = {};
function usedNikGlobal(nik) {
  return !!_gdUsedNik[nik];
}
function markNikUsed(nik) {
  _gdUsedNik[nik] = true;
}

// ============================================================
// 3. OUTSOURCE DATA GENERATION (100 → Employee sheet)
// ============================================================
function generateOutsourceData_(today) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var empSheet = ss.getSheetByName(EMPLOYEE_SHEET_NAME);

  if (!empSheet) {
    Logger.log("Employee sheet not found. Skipping Outsource generation.");
    return 0;
  }

  var headers = EMPLOYEE_HEADERS;
  var existingRows = empSheet.getLastRow();

  var osRows = [];
  var departments = [
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
  var positions = [
    "IT Support",
    "Network Engineer",
    "Software Engineer",
    "Backend Developer",
    "Frontend Developer",
    "Fullstack Developer",
    "HR Staff",
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
  var educationLevels = ["SMA", "SMK", "D3", "S1"];
  var workLocations = [
    "Jakarta Pusat",
    "Jakarta Selatan",
    "Jakarta Barat",
    "Jakarta Timur",
    "Jakarta Utara",
    "Bandung",
    "Surabaya",
    "Semarang",
    "Yogyakarta",
    "Medan",
    "Makassar",
    "Balikpapan",
  ];
  var outsourceCompanies = [
    "PT Bhinneka Teknologi",
    "PT Jasa Mandiri",
    "PT Sumber Daya Prima",
    "PT Mitra Sejahtera",
    "PT Outsource Indonesia",
    "PT Global Staffing",
    "PT Teknologi Mandiri",
    "PT Solusi SDM",
  ];
  var empStatuses = ["Active", "On Leave", "Resigned", "Terminated"];
  var contractDurations = [
    "3 Bulan",
    "6 Bulan",
    "1 Tahun",
    "2 Tahun",
    "3 Tahun",
  ];
  var maritalStatuses = ["Single", "Married", "Divorced"];
  var bloodTypes = ["A", "B", "AB", "O", "-"];
  var cities = _gdGetCities_();
  var streetNames = _gdStreetNames_();

  for (var i = 0; i < 100; i++) {
    var isMale = Math.random() > 0.52;
    var firstName = isMale
      ? _gdPick_(_gdMaleNames_())
      : _gdPick_(_gdFemaleNames_());
    var lastName = _gdPick_(_gdLastNames_());
    var fullName = firstName + " " + lastName;

    var position = _gdPick_(positions);
    var dept = _gdPickForPosition_(position, departments);
    var education = _gdPick_(educationLevels);
    var workLoc = _gdPick_(workLocations);
    var company = _gdPick_(outsourceCompanies);
    var empType = "Outsource";
    var empStatus = _gdPick_(empStatuses);
    var salaryType = "Monthly";
    var salary = _gdRandSalary_(position);

    var age;
    if (
      position === "Driver" ||
      position === "Security" ||
      position === "Office Boy"
    )
      age = _gdRandInt_(20, 45);
    else age = _gdRandInt_(22, 40);

    var birthYear = today.getFullYear() - age;
    var birthDate =
      birthYear +
      "-" +
      _gdPad_(_gdRandInt_(1, 12)) +
      "-" +
      _gdPad_(_gdRandInt_(1, 28));
    var marital = _gdPick_(maritalStatuses);
    var blood = _gdPick_(bloodTypes);

    var hireYear = today.getFullYear() - _gdRandInt_(0, 3);
    var hireMonth = _gdRandInt_(1, 12);
    var hireDay = _gdRandInt_(1, 28);
    var hireDate = hireYear + "-" + _gdPad_(hireMonth) + "-" + _gdPad_(hireDay);

    var contractEndObj = new Date(
      today.getFullYear() + 1,
      _gdRandInt_(0, 11),
      _gdRandInt_(1, 28),
    );
    var contractEndDate = _gdDateOnly_(contractEndObj);

    var nik;
    do {
      nik = _gdRandDigits_(16);
    } while (usedNikGlobal(nik));
    markNikUsed(nik);

    var emailBase =
      firstName.toLowerCase() +
      "." +
      lastName.toLowerCase().replace(/[^a-z]/g, "");
    var email =
      emailBase +
      _gdRandInt_(1, 999) +
      "@" +
      company.toLowerCase().replace(/[^a-z]/g, "") +
      ".co.id";
    var phone = "08" + _gdRandDigits_(10);

    var address =
      _gdPick_(streetNames) +
      " No. " +
      _gdRandInt_(1, 150) +
      ", " +
      _gdPick_(cities);

    var empId = "OS-" + hireYear + "-" + _gdPad_(i + 1, 5);

    // Baris sesuai EMPLOYEE_HEADERS (37 kolom) dari Config.gs
    var row = [
      empId, // 1. Employee ID
      "", // 2. Recruitment ID
      fullName, // 3. Full Name
      position, // 4. Position
      email, // 5. Email
      "'" + phone, // 6. Phone
      hireDate, // 7. Join Date
      empStatus, // 8. Status
      "", // 9. Notes
      _gdNow_(), // 10. Created At
      "PT Mahakarya Sukses Indonesia", // 11. Company Entity
      empType, // 12. Employee Type
      "'" + nik, // 13. NIK
      birthDate, // 14. Birth Date
      age, // 15. Age
      isMale ? "Male" : "Female", // 16. Gender
      marital, // 17. Marital Status
      address, // 18. Address
      _gdPick_(cities), // 19. City
      education, // 20. Education
      "", // 21. Work Experience
      dept, // 22. Department
      "", // 23. Division
      "", // 24. Branch
      "", // 25. Contract Start
      contractEndDate, // 26. Contract End
      _gdPick_(contractDurations), // 27. Contract Duration
      empStatus, // 28. Employment Status
      salary, // 29. Salary
      salaryType, // 30. Salary Type
      company, // 31. Outsource Vendor
      "", // 32. Contract Number
      "", // 33. District
      "", // 34. Recruitment Source
      "", // 35. HR Notes
      "Demo Generator", // 36. Created By
      _gdNow_(), // 37. Updated At
    ];

    osRows.push(row);
  }

  if (osRows.length > 0) {
    empSheet
      .getRange(existingRows + 1, 1, osRows.length, headers.length)
      .setValues(osRows);
  }

  Logger.log("Outsource: " + osRows.length + " records written");
  return osRows.length;
}

// ============================================================
// 4. AUDIT LOG GENERATION
// ============================================================
function generateAuditLogs_(recruitmentRecords, legacyEmpIds, recEmpIds) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var auditSheet = ss.getSheetByName(AUDIT_LOG_SHEET_NAME);

  if (auditSheet) {
    // Clear existing data and rewrite headers to ensure correct column order
    var lastRow = auditSheet.getLastRow();
    if (lastRow > 1) {
      auditSheet
        .getRange(2, 1, lastRow - 1, AUDIT_LOG_HEADERS.length)
        .clearContent();
    }
    auditSheet
      .getRange(1, 1, 1, AUDIT_LOG_HEADERS.length)
      .setValues([AUDIT_LOG_HEADERS]);
    auditSheet
      .getRange(1, 1, 1, AUDIT_LOG_HEADERS.length)
      .setBackground("#005BAC")
      .setFontColor("#ffffff")
      .setFontWeight("bold");
    auditSheet.setFrozenRows(1);
  } else {
    auditSheet = ss.insertSheet(AUDIT_LOG_SHEET_NAME);
    auditSheet
      .getRange(1, 1, 1, AUDIT_LOG_HEADERS.length)
      .setValues([AUDIT_LOG_HEADERS]);
    auditSheet
      .getRange(1, 1, 1, AUDIT_LOG_HEADERS.length)
      .setBackground("#005BAC")
      .setFontColor("#ffffff")
      .setFontWeight("bold");
    auditSheet.setFrozenRows(1);
  }

  var auditRows = [];
  var today = new Date();
  // Column order per AUDIT_LOG_HEADERS:
  // 0=Recruitment ID, 1=Action, 2=Field, 3=Old Value, 4=New Value, 5=User, 6=Timestamp

  // Recruitment audit logs
  for (var i = 0; i < recruitmentRecords.length; i++) {
    var rec = recruitmentRecords[i];
    var recId = rec.recruitmentId;
    var status = rec.status;

    // Created log
    var recCreatedTime = rec.row[1]; // Created Date from row data
    auditRows.push([
      recId, // 1 - Recruitment ID
      "CREATE", // 2 - Action
      "Status", // 3 - Field
      "", // 4 - Old Value
      "Applied", // 5 - New Value
      "Demo Generator", // 6 - User
      recCreatedTime, // 7 - Timestamp
    ]);

    // Status progression logs
    if (status !== "Applied") {
      var stages = [
        "Applied",
        "Screening",
        "Interview",
        "Psychotest",
        "HR Interview",
        "User Interview",
        "Offering",
      ];
      var currentStageIndex = stages.indexOf(status);
      if (currentStageIndex === -1) currentStageIndex = stages.length - 1;

      var baseDate = new Date(today.getTime() - _gdRandInt_(10, 60) * 86400000);
      for (var s = 1; s <= currentStageIndex; s++) {
        var stageDate = new Date(
          baseDate.getTime() + s * _gdRandInt_(2, 10) * 86400000,
        );
        var stageTime = _gdDateStr_(stageDate);
        var prevStage = stages[s - 1];
        var currStage = stages[s];
        auditRows.push([
          recId, // 1 - Recruitment ID
          "STATUS_CHANGE", // 2 - Action
          "Status", // 3 - Field
          prevStage, // 4 - Old Value
          currStage, // 5 - New Value
          "Demo Generator", // 6 - User
          stageTime, // 7 - Timestamp
        ]);
      }
    }

    // Hold log
    if (status === "Hold") {
      auditRows.push([
        recId, // 1 - Recruitment ID
        "HOLD", // 2 - Action
        "Status", // 3 - Field
        "", // 4 - Old Value
        "On Hold", // 5 - New Value
        "Demo Generator", // 6 - User
        _gdNow_(), // 7 - Timestamp
      ]);
    }

    // Blacklist log
    if (status === "Blacklist") {
      var blTime = _gdDateStr_(
        new Date(today.getTime() - _gdRandInt_(5, 30) * 86400000),
      );
      auditRows.push([
        recId, // 1 - Recruitment ID
        "BLACKLIST", // 2 - Action
        "Status", // 3 - Field
        "", // 4 - Old Value
        "Blacklisted", // 5 - New Value
        "Demo Generator", // 6 - User
        blTime, // 7 - Timestamp
      ]);
    }

    // Accepted log
    if (status === "Accepted" && rec.employeeId) {
      var acTime = _gdDateStr_(
        new Date(today.getTime() - _gdRandInt_(1, 10) * 86400000),
      );
      auditRows.push([
        recId, // 1 - Recruitment ID
        "ACCEPT_TO_EMPLOYEE", // 2 - Action
        "Status", // 3 - Field
        "", // 4 - Old Value
        "Accepted → Employee " + rec.employeeId, // 5 - New Value
        "Demo Generator", // 6 - User
        acTime, // 7 - Timestamp
      ]);
    }

    // HR Notes log
    if (rec.row[21] && rec.row[21] !== "Demo Generator") {
      auditRows.push([
        recId, // 1 - Recruitment ID
        "HR_NOTES_UPDATE", // 2 - Action
        "HR Notes", // 3 - Field
        "", // 4 - Old Value
        rec.row[21], // 5 - New Value
        "Demo Generator", // 6 - User
        _gdNow_(), // 7 - Timestamp
      ]);
    }
  }

  // Employee audit logs (legacy)
  for (var k = 0; k < legacyEmpIds.length; k++) {
    var empId = legacyEmpIds[k];
    auditRows.push([
      empId, // 1 - Recruitment ID (using Employee ID)
      "CREATE", // 2 - Action
      "Employee", // 3 - Field
      "", // 4 - Old Value
      "Created", // 5 - New Value
      "Demo Generator", // 6 - User
      _gdNow_(), // 7 - Timestamp
    ]);
  }

  // Employee audit logs (from recruitment)
  for (var m = 0; m < recEmpIds.length; m++) {
    var empId = recEmpIds[m];
    auditRows.push([
      empId, // 1 - Recruitment ID (using Employee ID)
      "CREATE_FROM_RECRUITMENT", // 2 - Action
      "Employee", // 3 - Field
      "", // 4 - Old Value
      "Converted from recruitment", // 5 - New Value
      "Demo Generator", // 6 - User
      _gdNow_(), // 7 - Timestamp
    ]);
  }

  if (auditRows.length > 0) {
    auditSheet
      .getRange(2, 1, auditRows.length, AUDIT_LOG_HEADERS.length)
      .setValues(auditRows);
  }

  Logger.log("Audit Logs: " + auditRows.length + " entries written");
  return auditRows.length;
}

// ============================================================
// FIX: Fix existing Employee sheet headers and data column order
// Run: fixEmployeeSheet() from Apps Script editor
// Remaps rows written in the old 32-column order to the current
// 37-column EMPLOYEE_HEADERS (Config.gs) order.
// ============================================================
function fixEmployeeSheet() {
  var lock = LockService.getScriptLock();
  lock.waitLock(60000);
  try {
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var sheet = ss.getSheetByName(EMPLOYEE_SHEET_NAME);
    if (!sheet) {
      return "Employee sheet not found. Nothing to fix.";
    }

    var lastRow = sheet.getLastRow();
    var lastCol = sheet.getLastColumn();

    var currentHeaders =
      lastCol > 0 ? sheet.getRange(1, 1, 1, lastCol).getValues()[0] : [];

    var existingData = [];
    if (lastRow > 1) {
      existingData = sheet.getRange(2, 1, lastRow - 1, lastCol).getValues();
    }

    // DETECT header format
    var headersAreOld = false; // old 32-col English order
    var headersAreLegacy = false; // ancient Indonesian format ("Nama Lengkap")
    if (currentHeaders.length > 0) {
      if (String(currentHeaders[1] || "") === "Company Entity") {
        headersAreOld = true; // Old 32-col order: col 2 = Company Entity
      } else if (
        String(currentHeaders[0] || "").indexOf("Nama") !== -1 ||
        String(currentHeaders[1] || "").indexOf("Nama") !== -1
      ) {
        headersAreLegacy = true;
      }
    }

    // DETECT data format (rows under current headers)
    var dataIsOldOrder = false;
    if (existingData.length > 0 && !headersAreLegacy) {
      for (var d = 0; d < existingData.length; d++) {
        if (String(existingData[d][1] || "").indexOf("PT ") === 0) {
          dataIsOldOrder = true; // Old order: col 2 = Company Entity
          break;
        }
      }
    }

    if (!headersAreOld && !dataIsOldOrder && !headersAreLegacy) {
      return "Employee sheet is already correct. No fix needed.";
    }

    if (headersAreLegacy) {
      return (
        "Employee sheet uses legacy Indonesian headers (Nama Lengkap) " +
        "which cannot be auto-migrated safely. Please regenerate demo data " +
        "by running generateAllHRISDemoData()."
      );
    }

    // Map from OLD 32-column order -> current EMPLOYEE_HEADERS (Config.gs)
    var OLD_TO_NEW = [
      // [oldIndex, headerName]
      [0, "Employee ID"], // 1
      [27, "Recruitment ID"], // 28 -> 2
      [3, "Full Name"], // 4 -> 3
      [16, "Position"], // 17 -> 4
      [9, "Email"], // 10 -> 5
      [10, "Phone"], // 11 -> 6
      [17, "Join Date"], // 18 -> 7
      [21, "Status"], // 22 -> 8
      [-1, "Notes"], // 9 (empty)
      [31, "Created At"], // 32 -> 10
      [1, "Company Entity"], // 2 -> 11
      [2, "Employee Type"], // 3 -> 12
      [4, "NIK"], // 5 -> 13
      [5, "Birth Date"], // 6 -> 14
      [6, "Age"], // 7 -> 15
      [7, "Gender"], // 8 -> 16
      [8, "Marital Status"], // 9 -> 17
      [11, "Address"], // 12 -> 18
      [12, "City"], // 13 -> 19
      [13, "Education"], // 14 -> 20
      [14, "Work Experience"], // 15 -> 21
      [15, "Department"], // 16 -> 22
      [-1, "Division"], // 23 (empty)
      [-1, "Branch"], // 24 (empty)
      [18, "Contract Start"], // 19 -> 25
      [19, "Contract End"], // 20 -> 26
      [20, "Contract Duration"], // 21 -> 27
      [21, "Employment Status"], // 22 -> 28
      [22, "Salary"], // 23 -> 29
      [23, "Salary Type"], // 24 -> 30
      [24, "Outsource Vendor"], // 25 -> 31
      [25, "Contract Number"], // 26 -> 32
      [26, "District"], // 27 -> 33
      [28, "Recruitment Source"], // 29 -> 34
      [29, "HR Notes"], // 30 -> 35
      [30, "Created By"], // 31 -> 36
      [31, "Updated At"], // 32 -> 37
    ];

    var fixedRows = existingData.map(function (row) {
      var r = row.slice(0, 32);
      while (r.length < 32) r.push("");
      var n = new Array(EMPLOYEE_HEADERS.length).fill("");
      for (var m = 0; m < OLD_TO_NEW.length; m++) {
        var oldIdx = OLD_TO_NEW[m][0];
        var headerName = OLD_TO_NEW[m][1];
        n[EMPLOYEE_COL[headerName] - 1] = oldIdx === -1 ? "" : r[oldIdx] || "";
      }
      return n;
    });

    // Clear old data
    if (lastRow > 1) {
      sheet
        .getRange(2, 1, lastRow - 1, Math.max(lastCol, EMPLOYEE_HEADERS.length))
        .clearContent();
    }

    // Write correct headers
    sheet
      .getRange(1, 1, 1, EMPLOYEE_HEADERS.length)
      .setValues([EMPLOYEE_HEADERS]);
    sheet
      .getRange(1, 1, 1, EMPLOYEE_HEADERS.length)
      .setBackground("#005BAC")
      .setFontColor("#ffffff")
      .setFontWeight("bold");
    sheet.setFrozenRows(1);

    // Auto-resize columns
    sheet.autoResizeColumns(1, EMPLOYEE_HEADERS.length);

    // Write corrected data
    if (fixedRows.length > 0) {
      sheet
        .getRange(2, 1, fixedRows.length, EMPLOYEE_HEADERS.length)
        .setValues(fixedRows);
    }

    Logger.log(
      "Employee sheet fixed: " +
        fixedRows.length +
        " rows rewritten with correct headers.",
    );
    return (
      "Employee sheet fixed! Headers: " +
      EMPLOYEE_HEADERS.join(", ") +
      ". " +
      fixedRows.length +
      " data rows rewritten."
    );
  } catch (e) {
    Logger.log("ERROR fixEmployeeSheet: " + e.toString());
    return "Error: " + e.toString();
  } finally {
    lock.releaseLock();
  }
}
