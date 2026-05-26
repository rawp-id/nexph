<?php
namespace Core\Auth;

class FileSessionDriver implements SessionDriver {
    public static function schema(): array {
        return [
            'path' => 'string|required',
        ];
    }
    private string $path;
    private int $lifetime;

    public function __construct(array $config) {
        $this->path = rtrim($config['path'] ?? '/tmp/sessions', '/');
        $this->lifetime = $config['lifetime'] ?? 7200;
        
        if (!is_dir($this->path)) {
            mkdir($this->path, 0700, true);
        }
    }

    public function read(string $id): array {
        $file = $this->getPath($id);
        
        if (!file_exists($file)) {
            return [];
        }
        
        $content = file_get_contents($file);
        if ($content === false) {
            return [];
        }
        
        $data = json_decode($content, true);
        if (!is_array($data)) {
            return [];
        }
        
        if (isset($data['_last_activity']) && (time() - $data['_last_activity']) > $this->lifetime) {
            $this->destroy($id);
            return [];
        }
        
        return $data;
    }

    public function write(string $id, array $data): bool {
        $file = $this->getPath($id);
        $content = json_encode($data);
        
        return file_put_contents($file, $content, LOCK_EX) !== false;
    }

    public function destroy(string $id): bool {
        $file = $this->getPath($id);
        
        if (file_exists($file)) {
            return unlink($file);
        }
        
        return true;
    }

    public function exists(string $id): bool {
        return file_exists($this->getPath($id));
    }

    public function gc(int $maxLifetime): void {
        $files = glob($this->path . '/sess_*');
        $now = time();
        
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > $maxLifetime) {
                @unlink($file);
            }
        }
    }

    private function getPath(string $id): string {
        return $this->path . '/sess_' . $id;
    }
}
