<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use JsonException;
use UnexpectedValueException;

class CompanyRegulationBankService
{
    private const FILE_PATH = 'company-data/peraturan-perusahaan.json';

    private ?array $chapterCache = null;

    /**
     * @return array<int, array{number:string, title:string, articles:array<int, array{number:string, title:string, text:string, paragraphs:array<int, array{number:string, text:string, sub:array<int, array{number:string, text:string, sub:array}>}>}>}>
     */
    public function chapters(): array
    {
        if ($this->chapterCache !== null) {
            return $this->chapterCache;
        }

        $disk = Storage::disk('local');
        if (! $disk->exists(self::FILE_PATH)) {
            throw new UnexpectedValueException('Bank Peraturan Perusahaan tidak ditemukan di penyimpanan privat.');
        }

        $contents = $disk->get(self::FILE_PATH);
        if (! is_string($contents)) {
            throw new UnexpectedValueException('Bank Peraturan Perusahaan tidak dapat dibaca dari penyimpanan privat.');
        }

        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedValueException('Bank Peraturan Perusahaan bukan JSON yang valid.', previous: $exception);
        }

        if (! is_array($data) || ! is_array($data['bab'] ?? null)) {
            throw new UnexpectedValueException('Struktur Bank Peraturan Perusahaan tidak valid.');
        }

        $chapters = collect($data['bab'])
            ->filter(fn ($chapter) => is_array($chapter))
            ->map(function (array $chapter): array {
                $articles = collect(is_array($chapter['pasal'] ?? null) ? $chapter['pasal'] : [])
                    ->filter(fn ($article) => is_array($article))
                    ->map(fn (array $article): array => $this->formatArticle($article))
                    ->filter(fn (array $article) => $article['number'] !== '')
                    ->values()
                    ->all();

                return [
                    'number' => $this->scalarText($chapter['nomor'] ?? ''),
                    'title' => $this->scalarText($chapter['judul'] ?? ''),
                    'articles' => $articles,
                ];
            })
            ->filter(fn (array $chapter) => $chapter['number'] !== '' && $chapter['articles'] !== [])
            ->values()
            ->all();

        if ($chapters === []) {
            throw new UnexpectedValueException('Bank Peraturan Perusahaan tidak memiliki pilihan Bab dan Pasal yang valid.');
        }

        $this->chapterCache = $chapters;

        return $this->chapterCache;
    }

    /**
     * @return array{chapter_number:string, chapter_title:string, article_number:string, article_title:string, paragraph_number:string, article_letter:string, subparagraph_number:string, article_text:string}|null
     */
    public function resolveReference(array $reference): ?array
    {
        $chapterNumber = $this->scalarText($reference['chapter_number'] ?? '');
        $articleNumber = $this->scalarText($reference['article_number'] ?? '');
        $paragraphNumber = $this->scalarText($reference['paragraph_number'] ?? '');
        $articleLetter = $this->scalarText($reference['article_letter'] ?? '');
        $subparagraphNumber = $this->scalarText($reference['subparagraph_number'] ?? '');

        $chapter = collect($this->chapters())->firstWhere('number', $chapterNumber);
        $article = collect($chapter['articles'] ?? [])->firstWhere('number', $articleNumber);
        if (! $chapter || ! $article) {
            return null;
        }

        $paragraph = null;
        if ($paragraphNumber !== '') {
            $paragraph = collect($article['paragraphs'])->firstWhere('number', $paragraphNumber);
            if (! $paragraph) {
                return null;
            }
        }

        $subparagraph = null;
        if ($articleLetter !== '') {
            $subparagraph = collect($paragraph['sub'] ?? [])->firstWhere('number', $articleLetter);
            if (! $subparagraph) {
                return null;
            }
        }
        $nestedSubparagraph = null;
        if ($subparagraphNumber !== '') {
            $nestedSubparagraph = collect($subparagraph['sub'] ?? [])->firstWhere('number', $subparagraphNumber);
            if (! $nestedSubparagraph) {
                return null;
            }
        }

        return [
            'chapter_number' => $chapter['number'],
            'chapter_title' => $chapter['title'],
            'article_number' => $article['number'],
            'article_title' => $article['title'],
            'paragraph_number' => $paragraph['number'] ?? '',
            'article_letter' => $subparagraph['number'] ?? '',
            'subparagraph_number' => $nestedSubparagraph['number'] ?? '',
            'article_text' => $nestedSubparagraph['text'] ?? $subparagraph['text'] ?? $paragraph['text'] ?? $article['text'],
        ];
    }

    /**
     * @return array{number:string, title:string, text:string, paragraphs:array<int, array{number:string, text:string, sub:array<int, array{number:string, text:string, sub:array}>>}
     */
    private function formatArticle(array $article): array
    {
        $paragraphs = collect(is_array($article['ayat'] ?? null) ? $article['ayat'] : [])
            ->filter(fn ($paragraph) => is_array($paragraph))
            ->map(function (array $paragraph): array {
                $subparagraphs = $this->formatSubparagraphs($paragraph['sub'] ?? []);
                $paragraphText = $this->nodeText($paragraph).$this->formatChildText($subparagraphs);

                return [
                    'number' => $this->scalarText($paragraph['nomor'] ?? ''),
                    'text' => $paragraphText,
                    'sub' => $subparagraphs,
                ];
            })
            ->values()
            ->all();

        $openingText = $this->openingText($article['teks_pembuka'] ?? null);
        if ($paragraphs === [] && $openingText !== '') {
            $paragraphs[] = [
                'number' => '1',
                'text' => $openingText,
                'sub' => [],
            ];
        }

        $articleText = [];
        if ($paragraphs === [] && $openingText !== '') {
            $articleText[] = $openingText;
        }

        foreach ($paragraphs as $paragraph) {
            $text = $paragraph['text'];
            $number = $paragraph['number'];
            if ($text !== '') {
                $articleText[] = $number !== '' ? $this->paragraphLabel($number).': '.$text : $text;
            }
        }

        return [
            'number' => $this->scalarText($article['nomor'] ?? ''),
            'title' => $this->scalarText($article['judul'] ?? ''),
            'text' => implode("\n", $articleText),
            'paragraphs' => $paragraphs,
        ];
    }

    private function paragraphLabel(string $number): string
    {
        return ctype_alpha($number) ? 'Huruf '.$number : 'Ayat ('.$number.')';
    }

    /**
     * @return array<int, array{number:string, text:string, sub:array}>
     */
    private function formatSubparagraphs(mixed $nodes): array
    {
        return collect(is_array($nodes) ? $nodes : [])
            ->filter(fn ($node) => is_array($node))
            ->map(function (array $node): array {
                $children = $this->formatSubparagraphs($node['sub'] ?? []);

                return [
                    'number' => $this->scalarText($node['nomor'] ?? ''),
                    'text' => $this->nodeText($node).$this->formatChildText($children),
                    'sub' => $children,
                ];
            })
            ->filter(fn (array $node) => $node['number'] !== '' && ($node['text'] !== '' || $node['sub'] !== []))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{number:string, text:string, sub:array}>  $nodes
     */
    private function formatChildText(array $nodes): string
    {
        $lines = [];
        foreach ($nodes as $node) {
            $label = ctype_alpha($node['number']) ? 'Huruf '.$node['number'] : 'Nomor '.$node['number'];
            if ($node['text'] !== '') {
                $lines[] = $label.': '.$node['text'];
            }
        }

        return $lines === [] ? '' : "\n".implode("\n", $lines);
    }

    private function openingText(mixed $opening): string
    {
        if (is_array($opening)) {
            return implode("\n", array_filter(array_map(
                fn ($node) => is_array($node) ? $this->nodeText($node) : $this->scalarText($node)
            , $opening)));
        }

        return $this->scalarText($opening);
    }

    private function nodeText(array $node): string
    {
        $type = $this->scalarText($node['type'] ?? '');
        if ($type === 'tabel') {
            $rows = [];
            if (is_array($node['header'] ?? null)) {
                $rows[] = $node['header'];
            }
            if (is_array($node['baris'] ?? null)) {
                array_push($rows, ...$node['baris']);
            }

            return implode("\n", array_map(
                fn ($row) => implode(' | ', array_map(fn ($cell) => $this->tableCellText($cell), is_array($row) ? $row : [$row])),
                $rows
            ));
        }

        return $this->scalarText($node['teks'] ?? '');
    }

    private function tableCellText(mixed $value): string
    {
        if (is_scalar($value)) {
            return trim((string) $value);
        }
        if (! is_array($value)) {
            return '';
        }

        return implode(' ', array_filter(array_map(fn ($cell) => $this->tableCellText($cell))));
    }

    private function scalarText(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
