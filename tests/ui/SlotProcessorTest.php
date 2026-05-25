<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Compiler\SlotProcessor;

class SlotProcessorTest extends TestCase
{
    private SlotProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new SlotProcessor();
    }

    public function testExtractDefaultSlot(): void
    {
        $html = '<p>Default content</p>';

        $slots = $this->processor->extractSlots($html);

        $this->assertArrayHasKey('default', $slots);
        $this->assertEquals('<p>Default content</p>', $slots['default']);
    }

    public function testExtractNamedSlot(): void
    {
        $html = '<template slot="header"><h1>Title</h1></template>';

        $slots = $this->processor->extractSlots($html);

        $this->assertArrayHasKey('header', $slots);
        $this->assertEquals('<h1>Title</h1>', $slots['header']);
    }

    public function testExtractMultipleNamedSlots(): void
    {
        $html = <<<HTML
<template slot="header"><h2>Header</h2></template>
<template slot="footer"><p>Footer</p></template>
HTML;

        $slots = $this->processor->extractSlots($html);

        $this->assertArrayHasKey('header', $slots);
        $this->assertArrayHasKey('footer', $slots);
        $this->assertEquals('<h2>Header</h2>', $slots['header']);
        $this->assertEquals('<p>Footer</p>', $slots['footer']);
    }

    public function testDefaultSlotExcludesNamedTemplates(): void
    {
        $html = <<<HTML
<template slot="header"><h2>Header</h2></template>
<p>Body content</p>
HTML;

        $slots = $this->processor->extractSlots($html);

        $this->assertStringNotContainsString('<template', $slots['default']);
        $this->assertStringContainsString('Body content', $slots['default']);
    }

    public function testProcessDefaultSlotPlaceholder(): void
    {
        $template = '<div class="card"><slot /></div>';
        $slots = ['default' => '<p>Injected</p>'];

        $result = $this->processor->processSlotPlaceholders($template, $slots);

        $this->assertStringContainsString('<p>Injected</p>', $result);
        $this->assertStringNotContainsString('<slot />', $result);
    }

    public function testProcessNamedSlotPlaceholder(): void
    {
        $template = '<div><slot name="header" /><slot /></div>';
        $slots = [
            'header' => '<h1>Title</h1>',
            'default' => '<p>Body</p>',
        ];

        $result = $this->processor->processSlotPlaceholders($template, $slots);

        $this->assertStringContainsString('<h1>Title</h1>', $result);
        $this->assertStringContainsString('<p>Body</p>', $result);
        $this->assertStringNotContainsString('<slot', $result);
    }

    public function testMissingSlotRendersEmpty(): void
    {
        $template = '<div><slot name="missing" /></div>';
        $slots = ['default' => ''];

        $result = $this->processor->processSlotPlaceholders($template, $slots);

        $this->assertStringNotContainsString('<slot', $result);
        $this->assertStringContainsString('<div></div>', $result);
    }

    public function testExtractScopedSlotData(): void
    {
        $html = '<slot :item="currentItem" />';

        $data = $this->processor->extractScopedSlotData($html);

        $this->assertArrayHasKey('item', $data);
        $this->assertEquals('currentItem', $data['item']);
    }

    public function testExtractScopedSlotDataMultiple(): void
    {
        $html = '<slot :item="todo" :index="idx" />';

        $data = $this->processor->extractScopedSlotData($html);

        $this->assertArrayHasKey('item', $data);
        $this->assertArrayHasKey('index', $data);
        $this->assertEquals('todo', $data['item']);
        $this->assertEquals('idx', $data['index']);
    }
}
