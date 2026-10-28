<?php

namespace Tests\Feature;

use App\Services\CompanyRegulationBankService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use UnexpectedValueException;

class CompanyRegulationBankTest extends TestCase
{
    public function test_private_company_regulation_bank_builds_cascading_options_and_resolves_citation(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('company-data/peraturan-perusahaan.json', json_encode([
            'bab' => [[
                'nomor' => 'IX',
                'judul' => 'Bab Disiplin Uji',
                'pasal' => [
                    [
                        'nomor' => '46',
                        'judul' => 'Tindakan Disiplin Uji',
                        'teks_pembuka' => 'Pembuka pasal.',
                        'ayat' => [
                            [
                                'type' => 'ayat',
                                'nomor' => '1',
                                'teks' => 'Teks ayat.',
                                'sub' => [
                                    ['type' => 'ayat', 'nomor' => 'a', 'teks' => 'Teks subhuruf.'],
                                ],
                            ],
                            ['type' => 'teks', 'teks' => 'Teks tanpa nomor.'],
                            ['type' => 'tabel', 'header' => ['Kolom A', 'Kolom B'], 'baris' => [['Isi A', 'Isi B']]],
                        ],
                    ],
                    [
                        'nomor' => '47',
                        'judul' => 'Kewajiban Uji',
                        'teks_pembuka' => [['type' => 'teks', 'teks' => 'Dianggap sebagai ayat satu.']],
                        'ayat' => [],
                    ],
                ],
            ]],
        ], JSON_THROW_ON_ERROR));

        $bank = app(CompanyRegulationBankService::class);
        $chapters = $bank->chapters();

        $this->assertSame('IX', $chapters[0]['number']);
        $this->assertSame('Bab Disiplin Uji', $chapters[0]['title']);
        $this->assertSame('46', $chapters[0]['articles'][0]['number']);
        $this->assertSame('a', $chapters[0]['articles'][0]['paragraphs'][0]['sub'][0]['number']);
        $this->assertStringContainsString('Huruf a: Teks subhuruf.', $chapters[0]['articles'][0]['paragraphs'][0]['text']);
        $this->assertStringContainsString('Teks tanpa nomor.', $chapters[0]['articles'][0]['text']);
        $this->assertStringContainsString('Kolom A | Kolom B', $chapters[0]['articles'][0]['text']);
        $this->assertSame('1', $chapters[0]['articles'][1]['paragraphs'][0]['number']);
        $this->assertSame('Dianggap sebagai ayat satu.', $chapters[0]['articles'][1]['paragraphs'][0]['text']);

        $reference = $bank->resolveReference([
            'chapter_number' => 'IX',
            'article_number' => '46',
            'paragraph_number' => '1',
            'article_letter' => 'a',
        ]);

        $this->assertSame('Bab Disiplin Uji', $reference['chapter_title']);
        $this->assertSame('Teks subhuruf.', $reference['article_text']);
        $this->assertNull($bank->resolveReference([
            'chapter_number' => 'IX',
            'article_number' => '999',
        ]));
    }

    public function test_missing_private_company_regulation_bank_fails_explicitly(): void
    {
        Storage::fake('local');

        $this->expectException(UnexpectedValueException::class);
        app(CompanyRegulationBankService::class)->chapters();
    }

    public function test_nested_numbered_subparagraphs_are_preserved_and_resolved(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('company-data/peraturan-perusahaan.json', json_encode([
            'bab' => [[
                'nomor' => 'II',
                'judul' => 'Tata Tertib',
                'pasal' => [[
                    'nomor' => '6',
                    'judul' => 'Kewajiban Karyawan',
                    'ayat' => [[
                        'type' => 'ayat',
                        'nomor' => '2',
                        'teks' => 'Karyawan wajib mematuhi ketentuan berikut:',
                        'sub' => [
                            [
                                'type' => 'ayat',
                                'nomor' => 'b',
                                'sub' => collect(range(1, 7))->map(fn (int $number) => [
                                    'type' => 'ayat',
                                    'nomor' => (string) $number,
                                    'teks' => "Ketentuan huruf b nomor {$number}.",
                                ])->all(),
                            ],
                            [
                                'type' => 'ayat',
                                'nomor' => 'c',
                                'sub' => collect(range(1, 3))->map(fn (int $number) => [
                                    'type' => 'ayat',
                                    'nomor' => (string) $number,
                                    'teks' => "Ketentuan huruf c nomor {$number}.",
                                ])->all(),
                            ],
                        ],
                    ]],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR));

        $bank = app(CompanyRegulationBankService::class);
        $paragraph = $bank->chapters()[0]['articles'][0]['paragraphs'][0];

        $this->assertSame(['b', 'c'], array_column($paragraph['sub'], 'number'));
        $this->assertSame(['1', '2', '3', '4', '5', '6', '7'], array_column($paragraph['sub'][0]['sub'], 'number'));
        $this->assertSame(['1', '2', '3'], array_column($paragraph['sub'][1]['sub'], 'number'));
        $this->assertStringContainsString('Huruf b:', $paragraph['text']);
        $this->assertStringContainsString('Nomor 7: Ketentuan huruf b nomor 7.', $paragraph['text']);
        $this->assertStringContainsString('Nomor 3: Ketentuan huruf c nomor 3.', $paragraph['text']);

        $reference = $bank->resolveReference([
            'chapter_number' => 'II',
            'article_number' => '6',
            'paragraph_number' => '2',
            'article_letter' => 'b',
            'subparagraph_number' => '7',
        ]);

        $this->assertSame('7', $reference['subparagraph_number']);
        $this->assertSame('Ketentuan huruf b nomor 7.', $reference['article_text']);
        $this->assertNull($bank->resolveReference([
            'chapter_number' => 'II',
            'article_number' => '6',
            'paragraph_number' => '2',
            'article_letter' => 'b',
            'subparagraph_number' => '8',
        ]));
    }
}
