{{--
    Halaman lampiran gambar untuk surat. Variabel:
    - $attachments: list of ['label', 'src', 'width', 'height']
    - $withLetterhead: tampilkan kop surat di setiap halaman lampiran (jika kop tidak fixed)
--}}
@foreach ($attachments ?? [] as $attachment)
    @php
        $scale = min(690 / max(1, $attachment['width']), 850 / max(1, $attachment['height']));
        $imageWidth = (int) floor($attachment['width'] * $scale);
        $imageHeight = (int) floor($attachment['height'] * $scale);
        $caption = 'Lampiran ' . $loop->iteration . (filled($attachment['label'] ?? null) ? ' - ' . $attachment['label'] : '');
    @endphp
    <div class="attachment-page" style="page-break-before: always; page-break-inside: avoid;">
        @if (! empty($withLetterhead))
            @include('pdf.components.kop-surat-mangkir')
        @endif
        <div style="margin: 0 0 8px; text-align: center; font-weight: bold;">{{ $caption }}</div>
        <div style="text-align: center;">
            <img src="{{ $attachment['src'] }}" alt="{{ $caption }}"
                style="width: {{ $imageWidth }}px; height: {{ $imageHeight }}px; padding: 4px; border: 1px solid #9ca3af;">
        </div>
    </div>
@endforeach
