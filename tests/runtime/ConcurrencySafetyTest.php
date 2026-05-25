<?php
require_once __DIR__ . '/../../autoload.php';

use Core\Core\Runtime\Runtime;
use Core\Core\Runtime\Channel;
use Core\Core\Runtime\Timer;

class ConcurrencySafetyTest {
    private $results = [];

    public function run() {
        if (!Runtime::available()) {
            echo "✗ Runtime not available\n";
            exit(1);
        }

        echo "=== Concurrency Safety Test ===\n\n";

        $this->testRaceCondition();
        $this->testAtomicOperations();
        $this->testChannelSafety();
        $this->testSharedState();
        $this->testMessageOrdering();
        $this->testConcurrentReads();
        $this->testConcurrentWrites();
        $this->testProducerConsumer();

        $this->printSummary();
    }

    private function testRaceCondition() {
        echo "Test 1: Race Condition Detection... ";
        $counter = 0;
        $expected = 1000;

        for ($i = 0; $i < $expected; $i++) {
            Runtime::spawn(function() use (&$counter) {
                $counter++;
            });
        }

        Runtime::run();

        if ($counter === $expected) {
            echo "✓ (no race condition)\n";
            $this->results['race_condition'] = 'PASS';
        } else {
            echo "✗ (race detected: expected $expected, got $counter)\n";
            $this->results['race_condition'] = 'FAIL';
        }
    }

    private function testAtomicOperations() {
        echo "Test 2: Atomic Operations... ";
        $ch = new Channel(0);
        $values = [];

        Runtime::spawn(function() use ($ch) {
            for ($i = 0; $i < 100; $i++) {
                $ch->send($i);
            }
        });

        Runtime::spawn(function() use ($ch, &$values) {
            for ($i = 0; $i < 100; $i++) {
                $values[] = $ch->receive();
            }
        });

        Runtime::run();

        $valid = count($values) === 100 && count(array_unique($values)) === 100;

        if ($valid) {
            echo "✓ (operations atomic)\n";
            $this->results['atomic_ops'] = 'PASS';
        } else {
            echo "✗ (atomicity violated)\n";
            $this->results['atomic_ops'] = 'FAIL';
        }
    }

    private function testChannelSafety() {
        echo "Test 3: Channel Safety (concurrent access)... ";
        $ch = new Channel(100);
        $sent = 0;
        $received = 0;

        for ($i = 0; $i < 10; $i++) {
            Runtime::spawn(function() use ($ch, &$sent) {
                for ($j = 0; $j < 100; $j++) {
                    $ch->send($j);
                    $sent++;
                }
            });
        }

        for ($i = 0; $i < 10; $i++) {
            Runtime::spawn(function() use ($ch, &$received) {
                for ($j = 0; $j < 100; $j++) {
                    $ch->receive();
                    $received++;
                }
            });
        }

        Runtime::run();

        if ($sent === 1000 && $received === 1000) {
            echo "✓ (channel safe)\n";
            $this->results['channel_safety'] = 'PASS';
        } else {
            echo "✗ (sent: $sent, received: $received)\n";
            $this->results['channel_safety'] = 'FAIL';
        }
    }

    private function testSharedState() {
        echo "Test 4: Shared State Access... ";
        $state = ['count' => 0];
        $ch = new Channel(0);

        Runtime::spawn(function() use (&$state, $ch) {
            for ($i = 0; $i < 100; $i++) {
                $ch->receive();
                $state['count']++;
                $ch->send(true);
            }
        });

        Runtime::spawn(function() use ($ch) {
            for ($i = 0; $i < 100; $i++) {
                $ch->send(true);
                $ch->receive();
            }
        });

        Runtime::run();

        if ($state['count'] === 100) {
            echo "✓ (state consistent)\n";
            $this->results['shared_state'] = 'PASS';
        } else {
            echo "✗ (count: " . $state['count'] . ")\n";
            $this->results['shared_state'] = 'FAIL';
        }
    }

    private function testMessageOrdering() {
        echo "Test 5: Message Ordering... ";
        $ch = new Channel(10);
        $received = [];

        Runtime::spawn(function() use ($ch) {
            for ($i = 0; $i < 100; $i++) {
                $ch->send($i);
            }
            $ch->close();
        });

        Runtime::spawn(function() use ($ch, &$received) {
            while (($val = $ch->receive()) !== null) {
                $received[] = $val;
            }
        });

        Runtime::run();

        $ordered = true;
        for ($i = 0; $i < count($received) - 1; $i++) {
            if ($received[$i] >= $received[$i + 1]) {
                $ordered = false;
                break;
            }
        }

        if ($ordered && count($received) === 100) {
            echo "✓ (messages ordered)\n";
            $this->results['message_ordering'] = 'PASS';
        } else {
            echo "✗ (ordering violated)\n";
            $this->results['message_ordering'] = 'FAIL';
        }
    }

    private function testConcurrentReads() {
        echo "Test 6: Concurrent Reads... ";
        $data = range(0, 99);
        $reads = 0;

        for ($i = 0; $i < 100; $i++) {
            Runtime::spawn(function() use ($data, &$reads) {
                $val = $data[array_rand($data)];
                $reads++;
            });
        }

        Runtime::run();

        if ($reads === 100) {
            echo "✓ (concurrent reads safe)\n";
            $this->results['concurrent_reads'] = 'PASS';
        } else {
            echo "✗ (reads: $reads)\n";
            $this->results['concurrent_reads'] = 'FAIL';
        }
    }

    private function testConcurrentWrites() {
        echo "Test 7: Concurrent Writes (channel-based)... ";
        $ch = new Channel(100);
        $writes = 0;

        for ($i = 0; $i < 100; $i++) {
            Runtime::spawn(function() use ($ch, $i, &$writes) {
                $ch->send($i);
                $writes++;
            });
        }

        Runtime::spawn(function() use ($ch) {
            for ($i = 0; $i < 100; $i++) {
                $ch->receive();
            }
        });

        Runtime::run();

        if ($writes === 100) {
            echo "✓ (concurrent writes safe)\n";
            $this->results['concurrent_writes'] = 'PASS';
        } else {
            echo "✗ (writes: $writes)\n";
            $this->results['concurrent_writes'] = 'FAIL';
        }
    }

    private function testProducerConsumer() {
        echo "Test 8: Producer-Consumer Pattern... ";
        $ch = new Channel(50);
        $produced = 0;
        $consumed = 0;

        for ($i = 0; $i < 5; $i++) {
            Runtime::spawn(function() use ($ch, &$produced) {
                for ($j = 0; $j < 100; $j++) {
                    $ch->send($j);
                    $produced++;
                }
            });
        }

        for ($i = 0; $i < 5; $i++) {
            Runtime::spawn(function() use ($ch, &$consumed) {
                for ($j = 0; $j < 100; $j++) {
                    $ch->receive();
                    $consumed++;
                }
            });
        }

        Runtime::run();

        if ($produced === 500 && $consumed === 500) {
            echo "✓ (pattern works)\n";
            $this->results['producer_consumer'] = 'PASS';
        } else {
            echo "✗ (produced: $produced, consumed: $consumed)\n";
            $this->results['producer_consumer'] = 'FAIL';
        }
    }

    private function printSummary() {
        $passed = count(array_filter($this->results, fn($r) => $r === 'PASS'));
        $total = count($this->results);

        echo "\n=== Summary ===\n";
        echo "Tests: $passed/$total passed\n";

        if ($passed === $total) {
            echo "\n✓ All concurrency safety tests passed\n";
            exit(0);
        } else {
            echo "\n✗ Some tests failed\n";
            exit(1);
        }
    }
}

$test = new ConcurrencySafetyTest();
$test->run();
