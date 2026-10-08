@if (strtoupper((string) ($company['code'] ?? '')) === 'SPI')
    @php
        $steinLogo = 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('assets/stein-pdf.png')));
    @endphp
    <table class="letterhead">
        <tr>
            <td class="logo-cell">
                <img class="logo" src="{{ $steinLogo }}" alt="Stein Cookware">
            </td>
            <td class="company">
                <div class="company-name">{{ $company['name'] }}</div>
                <div class="company-address">{{ $company['address'] }}</div>
            </td>
        </tr>
    </table>
@else
    @include('pdf.components.kop-surat')
@endif
