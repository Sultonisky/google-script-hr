/**
 * Utility for Realtime NIK Autofill & Cascading Regions in Laravel HRIS
 */

export function setupNikAutofill(options = {}) {
    const nikInput = document.getElementById(options.nikInputId || 'nik');
    const provinceSelect = document.getElementById(options.provinceSelectId || 'province');
    const citySelect = document.getElementById(options.citySelectId || 'city');
    const genderSelect = document.getElementById(options.genderSelectId || 'jenis_kelamin');
    const birthDateInput = document.getElementById(options.birthDateInputId || 'tanggal_lahir');
    const ageInput = document.getElementById(options.ageInputId || 'usia');

    if (!nikInput) return;

    nikInput.addEventListener('input', async function () {
        const nik = this.value.trim();

        if (nik.length === 16 && /^\d+$/.test(nik)) {
            try {
                const response = await fetch('/api/v1/nik/parse', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ nik: nik }),
                });

                const data = await response.json();

                if (data.valid) {
                    // Autofill Province
                    if (provinceSelect && data.provinsi_kode) {
                        provinceSelect.value = data.provinsi_kode;
                        provinceSelect.dispatchEvent(new Event('change'));
                        
                        // Load cities and then set city
                        if (citySelect && data.kota_kode) {
                            await loadCities(data.provinsi_kode, citySelect, data.kota_kode);
                        }
                    }

                    // Autofill Gender
                    if (genderSelect && data.jenis_kelamin) {
                        genderSelect.value = data.jenis_kelamin;
                    }

                    // Autofill Birth Date
                    if (birthDateInput && data.tanggal_lahir && data.tanggal_valid) {
                        birthDateInput.value = data.tanggal_lahir;
                    }

                    // Autofill Age
                    if (ageInput && data.usia !== null) {
                        ageInput.value = data.usia;
                    }
                }
            } catch (error) {
                console.error('Gagal parsing NIK:', error);
            }
        }
    });

    if (provinceSelect && citySelect) {
        provinceSelect.addEventListener('change', async function () {
            await loadCities(this.value, citySelect);
        });
    }
}

async function loadCities(provinceCode, citySelect, selectedCityCode = null) {
    if (!provinceCode || !citySelect) return;

    try {
        const response = await fetch(`/api/v1/cities/${provinceCode}`);
        const cities = await response.json();

        citySelect.innerHTML = '<option value="">-- Pilih Kota / Kabupaten --</option>';
        for (const [code, name] of Object.entries(cities)) {
            const option = document.createElement('option');
            option.value = code;
            option.textContent = name;
            if (selectedCityCode && code === selectedCityCode) {
                option.selected = true;
            }
            citySelect.appendChild(option);
        }
    } catch (e) {
        console.error('Gagal memuat kota:', e);
    }
}

// Auto init if elements exist
document.addEventListener('DOMContentLoaded', () => {
    setupNikAutofill();
});
