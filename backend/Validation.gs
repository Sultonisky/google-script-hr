// ============================================================
// backend/Validation.gs — VALIDASI DATA FORM SERVER-SIDE
// ============================================================

function validateFormData_(f) {
  if (!f.full_name || f.full_name.trim().length < 3)
    return 'Nama lengkap wajib diisi (minimal 3 karakter).';
  if (!f.nik || !/^[0-9]{16}$/.test(f.nik))
    return 'NIK harus tepat 16 digit angka.';
  if (!f.birth_date)
    return 'Tanggal lahir wajib diisi.';
  if (!f.age || Number(f.age) < 17)
    return 'Usia minimal 17 tahun untuk mendaftar.';
  if (!f.gender)
    return 'Jenis kelamin wajib dipilih.';
  if (!f.marital_status)
    return 'Status pernikahan wajib dipilih.';
  if (!f.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.email))
    return 'Format alamat email tidak valid.';
  if (!f.phone || !/^62[0-9]{8,12}$/.test(f.phone))
    return 'Nomor HP tidak valid.';
  if (!f.address || f.address.trim() === '')
    return 'Alamat wajib diisi.';
  if (!f.city || f.city.trim() === '')
    return 'Kota/Kabupaten wajib diisi.';
  if (!f.position_applied)
    return 'Posisi yang dilamar wajib dipilih.';
  if (!f.education)
    return 'Pendidikan terakhir wajib dipilih.';
  if (!f.work_experience)
    return 'Pengalaman kerja wajib dipilih.';
  if (!f.current_employment_status)
    return 'Status bekerja saat ini wajib dipilih.';
  if (!f.available_to_join)
    return 'Kesediaan bergabung wajib dipilih.';
  if (f.expected_salary === undefined || f.expected_salary === '' || isNaN(Number(f.expected_salary)))
    return 'Ekspektasi gaji wajib diisi dengan angka.';
  if (!f.recruitment_source)
    return 'Sumber informasi lowongan wajib dipilih.';
  return null;
}
