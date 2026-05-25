<?php
namespace Core\Server;

class ObjectPool {
    /** @var object[] */
    private array $items = [];
    private array $known = [];
    private array $borrowed = [];
    private int $created = 0;
    private int $reused = 0;
    private int $released = 0;
    private int $dropped = 0;
    private int $foreignRelease = 0;
    private int $doubleRelease = 0;
    private int $contamination = 0;

    public function __construct(
        private readonly \Closure $factory,
        private readonly int $maxSize = 1024,
        private readonly ?\Closure $reset = null,
        private readonly string $name = 'object',
        private readonly ?ObjectTracker $tracker = null,
        private readonly bool $safety = false,
    ) {}

    public function acquire(string $owner = '', string $context = ''): object {
        $item = array_pop($this->items);
        if ($item) {
            $this->reused++;
        } else {
            $this->created++;
            $item = ($this->factory)();
            $this->known[spl_object_id($item)] = true;
            $this->tracker?->track($item, $this->name, $owner, $context, 'borrowed');
        }

        $id = spl_object_id($item);
        if ($this->safety) {
            $this->known[$id] = true;
            $this->borrowed[$id] = true;
        }

        if ($this->safety && $item instanceof Cleanable && !$item->isClean()) {
            $this->contamination++;
            $this->reset($item);
        }

        $this->tracker?->track($item, $this->name, $owner, $context, 'borrowed');
        return $item;
    }

    public function release(object $item): void {
        $id = spl_object_id($item);
        if ($this->safety) {
            if (!isset($this->known[$id])) {
                $this->foreignRelease++;
                return;
            }

            if (!isset($this->borrowed[$id])) {
                $this->doubleRelease++;
                return;
            }

            unset($this->borrowed[$id]);
        }

        $this->reset($item);

        if (count($this->items) >= $this->maxSize) {
            $this->dropped++;
            $this->tracker?->release($item, 'dropped');
            return;
        }

        $this->items[] = $item;
        $this->released++;
        $this->tracker?->update($item, [
            'owner' => $this->name,
            'context' => '',
            'state' => 'idle',
        ]);
    }

    private function reset(object $item): void {
        if ($this->reset) {
            ($this->reset)($item);
            return;
        }

        if ($item instanceof Resettable) {
            $item->reset();
        }
    }

    public function stats(): array {
        return [
            'idle' => count($this->items),
            'borrowed' => count($this->borrowed),
            'max' => $this->maxSize,
            'created' => $this->created,
            'reused' => $this->reused,
            'released' => $this->released,
            'dropped' => $this->dropped,
            'foreign_release' => $this->foreignRelease,
            'double_release' => $this->doubleRelease,
            'contamination' => $this->contamination,
        ];
    }
}
