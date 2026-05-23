<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AccessMatrixCommand extends Command
{
    protected $signature = 'access:matrix
                            {--markdown : Simpan salinan markdown (lihat --output)}
                            {--output= : Path file .md (default: storage/app/access-matrix.md)}';

    protected $description = 'Ringkasan ability (config/access.php) dan route yang memakai checkAccess:*';

    public function handle(): int
    {
        $map = config('access.map', []);

        $this->info('=== Peta ability → level (config/access.php) ===');
        $mapRows = [];
        foreach ($map as $ability => $levels) {
            $mapRows[] = [
                $ability,
                is_array($levels) ? implode(', ', $levels) : '(bukan array)',
            ];
        }
        $this->table(['Ability', 'Level yang diizinkan'], $mapRows);

        $routeRows = $this->collectRoutesWithCheckAccess();
        $this->newLine();
        $this->info('=== Route terdaftar yang memakai middleware checkAccess ===');
        if ($routeRows === []) {
            $this->warn('Tidak ada route terdeteksi (atau router belum termuat). Lihat bagian scan file di bawah.');
        } else {
            $this->table(['Method', 'URI', 'Name', 'Ability'], $routeRows);
        }

        $this->newLine();
        $this->info('=== Kemunculan di routes/web.php (nomor baris) ===');
        $fileRows = $this->scanWebRoutesFile();
        if ($fileRows === []) {
            $this->warn('Tidak menemukan checkAccess di routes/web.php');
        } else {
            $this->table(['Baris', 'Cuplikan'], $fileRows);
        }

        $usedAbilities = [];
        foreach ($routeRows as $r) {
            foreach (array_map('trim', explode(',', $r[3])) as $ab) {
                if ($ab !== '') {
                    $usedAbilities[] = $ab;
                }
            }
        }
        $usedAbilities = array_unique($usedAbilities);
        $definedAbilities = array_keys($map);
        $missingInConfig = array_diff($usedAbilities, $definedAbilities);
        $unusedInRoutes = array_diff($definedAbilities, $usedAbilities);

        $this->newLine();
        $this->info('=== Cek konsistensi ===');
        if ($missingInConfig !== []) {
            $this->error('Ability dipakai di route tapi tidak ada di config (akan 403): '.implode(', ', $missingInConfig));
        } else {
            $this->line('Semua ability di route ada di config.');
        }
        if ($unusedInRoutes !== []) {
            $this->comment('Ability di config tapi tidak terdeteksi di route (normal jika dipakai @can / canAccess saja): '.implode(', ', $unusedInRoutes));
        }

        if ($this->option('markdown')) {
            $target = $this->option('output') ?: storage_path('app/access-matrix.md');
            try {
                $path = $this->writeMarkdown($map, $routeRows, $fileRows, $missingInConfig, $unusedInRoutes, $target);
                if ($path !== null) {
                    $this->info('Ditulis: '.$path);
                }
            } catch (\Throwable $e) {
                $this->error('Gagal menulis markdown: '.$e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return list<array{0: string, 1: string, 2: string, 3: string}>
     */
    private function collectRoutesWithCheckAccess(): array
    {
        try {
            $routes = app('router')->getRoutes();
            $rows = [];
            foreach ($routes as $route) {
                try {
                    $abilities = [];
                    foreach ($route->gatherMiddleware() as $middleware) {
                        if (! is_string($middleware)) {
                            continue;
                        }
                        if (preg_match('/checkAccess:([\w.]+)/', $middleware, $m)) {
                            $abilities[] = $m[1];
                        }
                    }
                    if ($abilities === []) {
                        continue;
                    }
                    $methods = array_diff($route->methods(), ['HEAD']);
                    $rows[] = [
                        implode('|', $methods),
                        '/'.$route->uri(),
                        $route->getName() ?? '—',
                        implode(', ', array_unique($abilities)),
                    ];
                } catch (\Throwable) {
                    continue;
                }
            }
            usort($rows, fn ($a, $b) => strcmp($a[3], $b[3]) ?: strcmp($a[1], $b[1]));

            return $rows;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array{0: int, 1: string}>
     */
    private function scanWebRoutesFile(): array
    {
        $path = base_path('routes/web.php');
        if (! is_readable($path)) {
            return [];
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];
        $out = [];
        foreach ($lines as $i => $line) {
            if (str_contains($line, 'checkAccess:')) {
                $trim = trim($line);
                if (strlen($trim) > 100) {
                    $trim = substr($trim, 0, 97).'...';
                }
                $out[] = [$i + 1, $trim];
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $map
     * @param  list<array{0: string, 1: string, 2: string, 3: string}>  $routeRows
     * @param  list<array{0: int, 1: string}>  $fileRows
     * @param  array<int, string>  $missingInConfig
     * @param  array<int, string>  $unusedInRoutes
     */
    private function writeMarkdown(
        array $map,
        array $routeRows,
        array $fileRows,
        array $missingInConfig,
        array $unusedInRoutes,
        string $target
    ): ?string {
        $dir = dirname($target);
        if (! is_dir($dir)) {
            if (! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
                $this->error('Tidak bisa membuat folder: '.$dir);

                return null;
            }
        }

        $lines = [];
        $lines[] = '# Access matrix (otomatis)';
        $lines[] = '';
        $lines[] = 'Di-generate dengan `php artisan access:matrix --markdown` (opsi: `--output=docs/access-matrix.md`). Segarkan dengan menjalankan ulang command.';
        $lines[] = '';
        $lines[] = '## Ability → level (`config/access.php`)';
        $lines[] = '';
        $lines[] = '| Ability | Level |';
        $lines[] = '| --- | --- |';
        foreach ($map as $ability => $levels) {
            $cell = is_array($levels) ? implode(', ', $levels) : '`(invalid)`';
            $lines[] = '| `'.$ability.'` | '.$cell.' |';
        }
        $lines[] = '';
        $lines[] = '## Route dengan `checkAccess`';
        $lines[] = '';
        $lines[] = '| Method | URI | Name | Ability |';
        $lines[] = '| --- | --- | --- | --- |';
        foreach ($routeRows as $r) {
            $lines[] = '| '.$r[0].' | `'.$r[1].'` | `'.$r[2].'` | `'.$r[3].'` |';
        }
        if ($routeRows === []) {
            $lines[] = '| — | — | — | *(tidak terdeteksi)* |';
        }
        $lines[] = '';
        $lines[] = '## Referensi baris di `routes/web.php`';
        $lines[] = '';
        $lines[] = '| Baris | Cuplikan |';
        $lines[] = '| ---: | --- |';
        foreach ($fileRows as $f) {
            $lines[] = '| '.$f[0].' | `'.str_replace('|', '\\|', $f[1]).'` |';
        }
        $lines[] = '';
        $lines[] = '## Catatan';
        $lines[] = '';
        $lines[] = '- Menu/modal memakai `@can(\'ability\')` atau `accessAbility` di Blade — tidak selalu muncul di tabel route.';
        $lines[] = '- Controller bisa memakai `Auth::user()->canAccess(\'ability\')` tanpa middleware route.';
        $lines[] = '';
        if ($missingInConfig !== []) {
            $lines[] = '### Perhatian';
            $lines[] = '';
            $lines[] = '- Ability tanpa entri config: `'.implode('`, `', $missingInConfig).'`';
            $lines[] = '';
        }
        if ($unusedInRoutes !== []) {
            $lines[] = '### Ability hanya untuk UI / controller';
            $lines[] = '';
            $lines[] = '- `'.implode('`, `', $unusedInRoutes).'`';
            $lines[] = '';
        }

        $bytes = @file_put_contents($target, implode("\n", $lines)."\n");
        if ($bytes === false) {
            $this->error('Tidak bisa menulis '.$target);

            return null;
        }

        return $target;
    }
}
