// ============================================================
// GenerateDummyData.gs — Generate 100 dummy recruitment records
// ============================================================
// INSTRUCTIONS:
// 1. Paste this file content into a NEW file in Apps Script editor
// 2. Run function generateAllDummyData() from the editor
// 3. After verification, delete this file from the project
// ============================================================

function generateAllDummyData() {
  var lock = LockService.getScriptLock();
  lock.waitLock(30000);
  try {
    var sheet = getOrCreateSheet_();
    var startRow = sheet.getLastRow() + 1;

    // Check if data already exists to prevent duplicates
    if (sheet.getLastRow() > 1) {
      var lastRecruitId = sheet.getRange(sheet.getLastRow(), 1).getValue();
      Logger.log("Existing data found. Last Recruitment ID: " + lastRecruitId);
      Logger.log("Appending new records starting at row " + startRow);
    }

    var records = buildDummyRecords_();

    // Build batch data
    var batchData = [];
    for (var i = 0; i < records.length; i++) {
      batchData.push(records[i].row);
    }

    // Write all at once for performance
    sheet
      .getRange(startRow, 1, batchData.length, batchData[0].length)
      .setValues(batchData);

    // Write audit logs for each record
    for (var j = 0; j < records.length; j++) {
      var rec = records[j];
      try {
        writeAuditLog_(
          rec.recruitmentId,
          "Created (Dummy Data)",
          "-",
          rec.status,
        );
      } catch (e) {
        Logger.log(
          "Audit log skip for " + rec.recruitmentId + ": " + e.message,
        );
      }
    }

    Logger.log(
      "SUCCESS: " +
        records.length +
        " dummy records inserted at rows " +
        startRow +
        "-" +
        (startRow + records.length - 1),
    );
    return "Sukses: " + records.length + " data dummy berhasil ditambahkan.";
  } catch (error) {
    Logger.log("ERROR: " + error.toString());
    return "Error: " + error.toString();
  } finally {
    lock.releaseLock();
  }
}

function buildDummyRecords_() {
  var records = [];
  var today = new Date();

  // Status distribution — menyesuaikan dengan sistem: Pending, Accepted, Hold, Blacklist
  var statusList = [];
  addMultiple_(statusList, "Pending", 70); // Mayoritas kandidat baru masuk, belum ada aksi
  addMultiple_(statusList, "Accepted", 20); // Kandidat yang diterima
  addMultiple_(statusList, "Hold", 8); // Kandidat yang ditahan sementara
  addMultiple_(statusList, "Blacklist", 2); // Kandidat yang di-blacklist

  // Shuffle status list
  shuffleArray_(statusList);

  // Position list
  var positions = [
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
    "Digital Marketing",
    "Graphic Designer",
    "UI/UX Designer",
    "Sales Executive",
    "Purchasing Staff",
    "Warehouse Staff",
    "Admin",
    "Customer Service",
  ];

  // Indonesian first names
  var maleFirstNames = [
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
  ];

  var femaleFirstNames = [
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
  ];

  var lastNames = [
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
  ];

  // Cities
  var cities = [
    "Jakarta Selatan",
    "Jakarta Barat",
    "Jakarta Pusat",
    "Jakarta Utara",
    "Jakarta Timur",
    "Bandung",
    "Surabaya",
    "Yogyakarta",
    "Semarang",
    "Malang",
    "Medan",
    "Palembang",
    "Makassar",
    "Denpasar",
    "Balikpapan",
    "Tangerang",
    "Bekasi",
    "Depok",
    "Bogor",
    "Banten",
  ];

  // Education levels — must match form values in FormPendaftaran & backend validation
  var educationLevels = [
    "Senior High School",
    "Vocational High School",
    "Diploma III",
    "Bachelor Degree",
    "Master Degree",
  ];

  // Work experiences — must match form option values
  var workExperiences = [
    "No Experience",
    "Less than 1 Year",
    "1-2 Years",
    "2-3 Years",
    "3-5 Years",
    "5-10 Years",
    "More than 10 Years",
  ];

  // Companies
  var companies = [
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
  ];

  // Employment statuses — must match form option values
  var empStatuses = [
    "Employed Full Time",
    "Employed Contract",
    "Part Time",
    "Freelance",
    "Unemployed",
    "Resigned",
    "Fresh Graduate",
  ];

  // Available to join options
  var availableToJoin = [
    "Segera",
    "1 Minggu",
    "2 Minggu",
    "1 Bulan",
    "2 Bulan",
    "3 Bulan",
    "Bisa Negosiasi",
  ];

  // Recruitment sources
  var recruitmentSources = [
    "JobStreet",
    "LinkedIn",
    "Indeed",
    "Glassdoor",
    "Instagram",
    "Website Perusahaan",
    "Referensi Karyawan",
    "Kampus / Career Fair",
    "Indeed",
    "Loker.id",
    "Karir.com",
  ];

  // Salary ranges by position type
  var salaryRanges = {
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
    "Digital Marketing": [4500000, 10000000],
    "Graphic Designer": [4000000, 8000000],
    "UI/UX Designer": [5000000, 12000000],
    "Sales Executive": [4000000, 9000000],
    "Purchasing Staff": [4000000, 7500000],
    "Warehouse Staff": [3500000, 6000000],
    Admin: [3500000, 6000000],
    "Customer Service": [3500000, 6500000],
  };

  // HR Notes pool
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
    "",
    "",
    "",
  ];

  // Addresses
  var streetNames = [
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
  ];

  // Universities
  var universities = [
    "Universitas Indonesia",
    "Institut Teknologi Bandung",
    "Universitas Gadjah Mada",
    "Universitas Diponegoro",
    "Institut Teknologi Sepuluh Nopember",
    "Universitas Airlangga",
    "Universitas Padjadjaran",
    "Universitas Hasanuddin",
    "Universitas Brawijaya",
    "Universitas Sebelas Maret",
    "Universitas Negeri Jakarta",
    "Universitas Negeri Yogyakarta",
    "Universitas Muhammadiyah Jakarta",
    "Universitas Trisakti",
    "Universitas Mercu Buana",
    "Universitas Bina Nusantara",
    "Politeknik Negeri Jakarta",
    "Politeknik Negeri Bandung",
    "Universitas Pancasila",
    "Universitas Tarumanagara",
  ];

  // Majors
  var majors = [
    "Teknik Informatika",
    "Sistem Informasi",
    "Ilmu Komputer",
    "Teknik Komputer",
    "Teknik Elektro",
    "Teknik Mesin",
    "Teknik Sipil",
    "Teknik Industri",
    "Akuntansi",
    "Manajemen",
    "Ekonomi",
    "Bisnis",
    "Desain Grafis",
    "Desain Komunikasi Visual",
    "Seni Rupa",
    "Psikologi",
    "Hubungan Masyarakat",
    "Ilmu Komunikasi",
    "Hukum",
    "Sastra Inggris",
    "Pendidikan",
  ];

  // Marital statuses — must match form values
  var maritalStatuses = ["Single", "Married", "Divorced", "Widowed"];

  // Hold reasons
  var holdReasons = [
    "Kandidat minta penundaan",
    "Posisi belum dibuka",
    "Budget belum tersedia",
    "Menunggu hasil background check",
  ];

  // Blacklist reasons
  var blacklistReasons = [
    "Tidak hadir tanpa konfirmasi",
    "Berdusta dalam aplikasi",
    "Pelanggaran etika saat interview",
  ];

  var usedEmails = {};
  var usedPhones = {};
  var usedNik = {};

  for (var i = 0; i < 100; i++) {
    var status = statusList[i];
    var isMale = Math.random() > 0.45;
    var firstName = isMale
      ? pickRandom_(maleFirstNames)
      : pickRandom_(femaleFirstNames);
    var lastName = pickRandom_(lastNames);
    var fullName = firstName + " " + lastName;

    var position = pickRandom_(positions);
    var salaryRange = salaryRanges[position];
    var expectedSalary = randomInt_(salaryRange[0], salaryRange[1]);
    // Round salary to nearest 500000
    expectedSalary = Math.round(expectedSalary / 500000) * 500000;

    var education = pickRandom_(educationLevels);
    var workExp = pickRandom_(workExperiences);

    // Age based on experience
    var age;
    if (workExp === "No Experience" || workExp === "Less than 1 Year") {
      age = randomInt_(20, 25);
    } else if (workExp === "1-2 Years") {
      age = randomInt_(22, 28);
    } else if (workExp === "2-3 Years") {
      age = randomInt_(24, 30);
    } else if (workExp === "3-5 Years") {
      age = randomInt_(26, 33);
    } else if (workExp === "5-10 Years") {
      age = randomInt_(28, 38);
    } else {
      age = randomInt_(32, 50);
    }
    if (age > 50) age = 50;

    var birthYear = today.getFullYear() - age;
    var birthMonth = randomInt_(1, 12);
    var birthDay = randomInt_(1, 28);
    var birthDate = birthYear + "-" + pad_(birthMonth) + "-" + pad_(birthDay);

    // Generate unique phone
    var phone;
    do {
      phone = "08" + randomDigits_(10);
    } while (usedPhones[phone]);
    usedPhones[phone] = true;

    // Generate unique email
    var emailBase = firstName.toLowerCase() + "." + lastName.toLowerCase();
    emailBase = emailBase.replace(/[^a-z0-9.]/g, "");
    var emailSuffix = randomInt_(1, 999);
    var email = emailBase + emailSuffix + "@gmail.com";
    var emailAttempt = 0;
    while (usedEmails[email]) {
      emailAttempt++;
      email = emailBase + emailSuffix + emailAttempt + "@gmail.com";
    }
    usedEmails[email] = true;

    var city = pickRandom_(cities);
    var street = pickRandom_(streetNames) + " No. " + randomInt_(1, 150);
    var address = street + ", " + city;

    var university = pickRandom_(universities);
    var major = pickRandom_(majors);
    var educationDisplay = education;
    if (
      education === "S1" ||
      education === "S2" ||
      education === "D3" ||
      education === "D4"
    ) {
      educationDisplay = education + " " + major;
    }

    var company = workExp === "No Experience" ? "-" : pickRandom_(companies);
    var empStatus = pickRandom_(empStatuses);
    var joinDate = pickRandom_(availableToJoin);
    var source = pickRandom_(recruitmentSources);
    var maritalStatus = pickRandom_(maritalStatuses);

    var cvLink = "";
    if (Math.random() > 0.3) {
      cvLink =
        "https://drive.google.com/file/d/" + randomAlphanumeric_(15) + "/view";
    }

    var hrNotes = pickRandom_(hrNotesPool);

    // Created Date - spread across last 90 days
    var daysAgo = randomInt_(0, 90);
    var createdDateObj = new Date(today.getTime() - daysAgo * 86400000);
    createdDateObj.setHours(
      randomInt_(8, 17),
      randomInt_(0, 59),
      randomInt_(0, 59),
    );
    var createdDateStr = Utilities.formatDate(
      createdDateObj,
      "GMT+7",
      "yyyy-MM-dd HH:mm:ss",
    );

    // Updated At
    var updatedDateObj = new Date(
      createdDateObj.getTime() + randomInt_(0, daysAgo) * 86400000,
    );
    updatedDateObj.setHours(
      randomInt_(8, 17),
      randomInt_(0, 59),
      randomInt_(0, 59),
    );
    var updatedAtStr = Utilities.formatDate(
      updatedDateObj,
      "GMT+7",
      "yyyy-MM-dd HH:mm:ss",
    );

    // Recruitment ID - use timestamp-based to avoid collision
    var recDatePart = Utilities.formatDate(createdDateObj, "GMT+7", "yyyyMMdd");
    var recId = "REC-" + recDatePart + "-" + ("000000" + (i + 1)).slice(-6);

    // Extra columns
    var holdReason = "";
    var holdFollowUpDate = "";
    var blacklistReason = "";
    var blacklistDate = "";
    var blacklistUpdatedBy = "";
    var employeeId = "";

    if (status === "Hold") {
      holdReason = pickRandom_(holdReasons);
      var fuDate = new Date(today.getTime() + randomInt_(7, 30) * 86400000);
      holdFollowUpDate = Utilities.formatDate(fuDate, "GMT+7", "yyyy-MM-dd");
    }

    if (status === "Blacklist") {
      blacklistReason = pickRandom_(blacklistReasons);
      blacklistDate = updatedAtStr;
      blacklistUpdatedBy = "HR Admin";
    }

    if (status === "Accepted") {
      employeeId =
        "EMP-" + today.getFullYear() + "-" + ("0000" + (i + 1)).slice(-4);
    }

    var row = [
      recId, // Recruitment ID
      createdDateStr, // Created Date
      fullName, // Full Name
      "'" + randomDigits_(16), // NIK (16-digit Indonesian national ID)
      birthDate, // Birth Date
      age, // Age
      isMale ? "Male" : "Female", // Gender (matches backend: Male/Female)
      maritalStatus, // Marital Status
      email, // Email
      "'" + phone, // Phone
      address, // Address
      city, // City
      position, // Position Applied
      educationDisplay, // Education
      workExp, // Work Experience
      company, // Last Company
      empStatus, // Current Employment Status
      joinDate, // Available to Join
      expectedSalary, // Expected Salary
      source, // Recruitment Source
      cvLink, // CV Link
      status, // Status
      hrNotes, // HR Notes
      "System", // Created By
      updatedAtStr, // Updated At
      holdReason, // Hold Reason (extra)
      holdFollowUpDate, // Hold Follow Up Date (extra)
      blacklistReason, // Blacklist Reason (extra)
      blacklistDate, // Blacklist Date (extra)
      blacklistUpdatedBy, // Blacklist Updated By (extra)
      employeeId, // Employee ID (extra)
    ];

    records.push({ recruitmentId: recId, status: status, row: row });
  }

  return records;
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function pickRandom_(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

function randomInt_(min, max) {
  return Math.floor(Math.random() * (max - min + 1)) + min;
}

function pad_(n) {
  return n < 10 ? "0" + n : "" + n;
}

function addMultiple_(arr, value, count) {
  for (var i = 0; i < count; i++) arr.push(value);
}

function shuffleArray_(arr) {
  for (var i = arr.length - 1; i > 0; i--) {
    var j = Math.floor(Math.random() * (i + 1));
    var temp = arr[i];
    arr[i] = arr[j];
    arr[j] = temp;
  }
}

function randomDigits_(count) {
  var result = "";
  for (var i = 0; i < count; i++) {
    result += Math.floor(Math.random() * 10);
  }
  return result;
}

function randomAlphanumeric_(count) {
  var chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
  var result = "";
  for (var i = 0; i < count; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return result;
}

// ============================================================
// VALIDATION FUNCTION — Run after insertion to verify
// ============================================================
function validateInsertedData() {
  var sheet = getDashboardSheet_();
  if (!sheet || sheet.getLastRow() < 2) {
    Logger.log("ERROR: No data found in sheet");
    return "No data found";
  }

  var lastRow = sheet.getLastRow();
  var lastCol = sheet.getLastColumn();
  var values = sheet.getRange(1, 1, lastRow, lastCol).getValues();
  var headers = values[0];

  var colIndex = {};
  headers.forEach(function (h, i) {
    colIndex[String(h).trim()] = i;
  });

  var totalRows = lastRow - 1;
  var statusCounts = {};
  var idSet = {};
  var emailSet = {};
  var phoneSet = {};
  var duplicates = [];
  var positionCounts = {};
  var educationCounts = {};

  for (var r = 1; r < values.length; r++) {
    var row = values[r];
    if (!row.join("").toString().trim()) continue;

    var recId = String(row[colIndex["Recruitment ID"]] || "");
    var email = String(row[colIndex["Email"]] || "");
    var phone = String(row[colIndex["Phone"]] || "");
    var status = String(row[colIndex["Status"]] || "");
    var position = String(row[colIndex["Position Applied"]] || "");
    var education = String(row[colIndex["Education"]] || "");

    // Count statuses
    statusCounts[status] = (statusCounts[status] || 0) + 1;

    // Count positions
    positionCounts[position] = (positionCounts[position] || 0) + 1;

    // Count education
    educationCounts[education] = (educationCounts[education] || 0) + 1;

    // Check duplicates
    if (idSet[recId]) duplicates.push("Duplicate ID: " + recId);
    if (emailSet[email]) duplicates.push("Duplicate Email: " + email);
    if (phoneSet[phone]) duplicates.push("Duplicate Phone: " + phone);

    idSet[recId] = true;
    emailSet[email] = true;
    phoneSet[phone] = true;
  }

  Logger.log("=== VALIDATION RESULTS ===");
  Logger.log("Total rows (excluding header): " + totalRows);
  Logger.log("");
  Logger.log("--- Status Distribution ---");
  var statuses = ["Pending", "Accepted", "Hold", "Blacklist"];
  for (var s = 0; s < statuses.length; s++) {
    Logger.log(statuses[s] + ": " + (statusCounts[statuses[s]] || 0));
  }
  Logger.log("");
  Logger.log("--- Duplicates ---");
  if (duplicates.length === 0) {
    Logger.log("No duplicates found!");
  } else {
    for (var d = 0; d < duplicates.length; d++) {
      Logger.log(duplicates[d]);
    }
  }
  Logger.log("");
  Logger.log("--- Position Distribution ---");
  var posKeys = Object.keys(positionCounts).sort();
  for (var p = 0; p < posKeys.length; p++) {
    Logger.log(posKeys[p] + ": " + positionCounts[posKeys[p]]);
  }

  return "Validation complete. Check Execution Log for details.";
}

// ============================================================
// CLEANUP — Remove all dummy data (CAREFUL!)
// ============================================================
function removeAllDummyData() {
  var lock = LockService.getScriptLock();
  lock.waitLock(30000);
  try {
    var sheet = getDashboardSheet_();
    if (!sheet || sheet.getLastRow() < 2) return "No data to remove.";

    var lastRow = sheet.getLastRow();
    var lastCol = sheet.getLastColumn();
    var values = sheet.getRange(1, 1, lastRow, lastCol).getValues();
    var headers = values[0];

    var colIndex = {};
    headers.forEach(function (h, i) {
      colIndex[String(h).trim()] = i;
    });

    var createdByCol = colIndex["Created By"];
    var rowsToDelete = [];

    for (var r = 1; r < values.length; r++) {
      if (String(values[r][createdByCol]) === "System") {
        rowsToDelete.push(r + 1); // +1 because sheet rows are 1-indexed
      }
    }

    // Delete from bottom to top to avoid index shifting
    rowsToDelete.sort(function (a, b) {
      return b - a;
    });
    for (var i = 0; i < rowsToDelete.length; i++) {
      sheet.deleteRow(rowsToDelete[i]);
    }

    Logger.log("Removed " + rowsToDelete.length + " dummy records.");
    return "Removed " + rowsToDelete.length + " dummy records.";
  } catch (error) {
    return "Error: " + error.toString();
  } finally {
    lock.releaseLock();
  }
}
