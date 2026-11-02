<?php

use App\Enums\ProjectManagement\ProjectStage;

test('every project starts upcoming', function () {
    expect(ProjectStage::initial())->toBe(ProjectStage::Upcoming);
});

test('only completed and cancelled are closed', function () {
    $closed = array_values(array_filter(ProjectStage::cases(), fn (ProjectStage $stage) => $stage->isClosed()));

    expect($closed)->toBe([ProjectStage::Completed, ProjectStage::Cancelled]);
});

test('the stages are stored as kebab-case slugs in lifecycle order', function () {
    expect(array_map(fn (ProjectStage $stage) => $stage->value, ProjectStage::cases()))
        ->toBe(['upcoming', 'evaluation', 'running', 'on-hold', 'completed', 'cancelled']);
});

test('each stage has a readable label', function () {
    expect(ProjectStage::OnHold->label())->toBe('On Hold')
        ->and(ProjectStage::Upcoming->label())->toBe('Upcoming');
});
