<?php

namespace Nexph\Builder;

/**
 * Analyzes build output and reports per-component size breakdown.
 */
class BundleAnalyzer
{
    public function analyze(array $components, array $jsParts, array $cssParts, array $htmlParts): array
    {
        $report = [
            'components' => [],
            'totals'     => [
                'js'    => 0,
                'css'   => 0,
                'html'  => 0,
                'total' => 0,
            ],
        ];

        foreach ($components as $i => $name) {
            $js   = $jsParts[$i]  ?? '';
            $css  = $cssParts[$i] ?? '';
            $html = $htmlParts[$i] ?? '';

            $jsSize   = strlen($js);
            $cssSize  = strlen($css);
            $htmlSize = strlen($html);
            $total    = $jsSize + $cssSize + $htmlSize;

            $report['components'][] = [
                'name'  => $name,
                'js'    => $jsSize,
                'css'   => $cssSize,
                'html'  => $htmlSize,
                'total' => $total,
            ];

            $report['totals']['js']    += $jsSize;
            $report['totals']['css']   += $cssSize;
            $report['totals']['html']  += $htmlSize;
            $report['totals']['total'] += $total;
        }

        usort($report['components'], fn($a, $b) => $b['total'] <=> $a['total']);

        return $report;
    }

    public function render(array $report): string
    {
        $lines = [];
        $lines[] = '';
        $lines[] = '  Bundle Analysis';
        $lines[] = '  ' . str_repeat('─', 52);
        $lines[] = sprintf('  %-24s %8s %8s %8s', 'Component', 'JS', 'CSS', 'Total');
        $lines[] = '  ' . str_repeat('─', 52);

        foreach ($report['components'] as $c) {
            $lines[] = sprintf(
                '  %-24s %8s %8s %8s',
                $c['name'],
                $this->fmt($c['js']),
                $this->fmt($c['css']),
                $this->fmt($c['total'])
            );
        }

        $t = $report['totals'];
        $lines[] = '  ' . str_repeat('─', 52);
        $lines[] = sprintf(
            '  %-24s %8s %8s %8s',
            'TOTAL',
            $this->fmt($t['js']),
            $this->fmt($t['css']),
            $this->fmt($t['total'])
        );
        $lines[] = '';

        return implode(PHP_EOL, $lines);
    }

    public function writeJson(array $report, string $outputDir): string
    {
        $path = rtrim($outputDir, '/') . '/bundle-report.json';
        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT));
        return $path;
    }

    private function fmt(int $bytes): string
    {
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
