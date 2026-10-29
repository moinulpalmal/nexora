#!/usr/bin/env node
/**
 * PostToolUse + Stop hook — every development change carries its documentation
 * (ARCHITECTURE.md §10, §12; owner, 2026-10-29).
 *
 * Each changed file is mapped to its module through the layer folders. Two rules:
 *
 *   Rule C — a module's code changed, but documentation/{module}.md did not.
 *
 *   Rule D — a module's screens changed (pages, module components, controllers,
 *            requests, its route file), but neither its training guides folder,
 *            its screenshots nor config/training.php did.
 *
 * PostToolUse: checks the file just edited and nudges once per module and rule per
 * session, so a feature in progress is not nagged on every edit.
 *
 * Stop: checks the whole uncommitted working tree and refuses to end the turn while a
 * rule still fires. A second stop (`stop_hook_active`) is let through, so the agent can
 * finish by stating plainly why a change is not user-visible.
 *
 * Any unexpected error exits 0 so a broken hook can never block real work.
 */

import { execFileSync } from 'node:child_process';
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import process from 'node:process';

/** Module folder name (lowercased) → its reference doc under documentation/. */
const MODULE_DOCS = {
    admin: 'admin.md',
    settings: 'settings.md',
    training: 'training.md',
    hrm: 'hrm.md',
    projectmanagement: 'projects.md',
    projects: 'projects.md',
    accounting: 'accounting.md',
};

/** Module folder name (lowercased) → its section under the training guides. */
const GUIDE_SECTIONS = {
    admin: 'admin',
    settings: 'settings',
    hrm: 'hrm',
    projectmanagement: 'projects',
    projects: 'projects',
    accounting: 'accounting',
};

/** [pattern, is a screen] — capture group 1 is the module folder. */
const LAYERS = [
    [/^app\/Http\/Controllers\/([^/]+)\/[^/]+$/, true],
    [/^app\/Http\/Requests\/([^/]+)\/[^/]+$/, true],
    [/^resources\/js\/pages\/([^/]+)\/.+$/, true],
    [/^resources\/js\/components\/([^/]+)\/.+$/, true],
    [/^routes\/([^/]+)\.php$/, true],
    [/^app\/(?:Models|Services|Enums|Listeners|Mail|Notifications|Exceptions)\/([^/]+)\/[^/]+$/, false],
];

const GUIDES_ROOT = 'resources/js/components/training/guides/';
const SHOTS_ROOT = 'public/images/training/';
const REGISTRY = 'config/training.php';

function readStdin() {
    try {
        return readFileSync(0, 'utf8');
    } catch {
        return '';
    }
}

function git(root, args) {
    return execFileSync('git', args, { cwd: root, encoding: 'utf8' });
}

/** Every uncommitted path, tracked or not, repo-relative with forward slashes. */
function changedPaths(root) {
    return git(root, ['status', '--porcelain', '--untracked-files=all'])
        .split('\n')
        .filter((line) => line.trim() !== '')
        .map((line) => line.slice(3))
        .map((entry) => (entry.includes(' -> ') ? entry.split(' -> ')[1] : entry))
        .map((entry) => entry.replace(/^"(.*)"$/, '$1'));
}

/**
 * The module a code file belongs to, and whether it is part of a screen; null for
 * documentation, the training content itself, and anything no module owns.
 */
function classify(rel) {
    if (rel.startsWith(GUIDES_ROOT) || rel.startsWith(SHOTS_ROOT) || rel === REGISTRY) {
        return null;
    }

    for (const [pattern, screen] of LAYERS) {
        const match = rel.match(pattern);

        if (!match) {
            continue;
        }

        const module = match[1].toLowerCase();

        if (!MODULE_DOCS[module]) {
            return null;
        }

        // The manual's own screens are documented in training.md, not by a guide.
        return { module, screen: screen && module !== 'training' };
    }

    return null;
}

/** The rules still firing for one module, given every changed path. */
function gapsFor(module, screen, changed) {
    const gaps = [];
    const doc = `documentation/${MODULE_DOCS[module]}`;

    if (!changed.includes(doc)) {
        gaps.push({
            rule: 'C',
            text: `${doc} has no pending edits`,
        });
    }

    const section = GUIDE_SECTIONS[module];

    if (screen && section) {
        const guidesTouched = changed.some(
            (rel) =>
                rel.startsWith(`${GUIDES_ROOT}${section}/`) ||
                rel.startsWith(SHOTS_ROOT) ||
                rel === REGISTRY,
        );

        if (!guidesTouched) {
            gaps.push({
                rule: 'D',
                text: `no training guide under ${GUIDES_ROOT}${section}/ (nor ${REGISTRY}) has pending edits`,
            });
        }
    }

    return gaps;
}

const INSTRUCTIONS =
    'CLAUDE.md and ARCHITECTURE.md §10 require every change to update, in the same change, the ' +
    "module's documentation/{module}.md and the training guide of every screen it touches (English " +
    'and Bangla; recapture screenshots when the picture changed; register a new screen in ' +
    `${REGISTRY}). Read the doc and the guides and fix whatever the change made untrue, or state ` +
    'plainly why this change is not user-visible.';

function block(event, reason) {
    const output = { decision: 'block', reason };

    if (event === 'PostToolUse') {
        output.hookSpecificOutput = { hookEventName: 'PostToolUse' };
    }

    process.stdout.write(JSON.stringify(output));
    process.exit(0);
}

function onStop(root, payload) {
    if (payload.stop_hook_active) {
        return;
    }

    const changed = changedPaths(root);
    const modules = new Map();

    for (const rel of changed) {
        const found = classify(rel);

        if (!found) {
            continue;
        }

        const entry = modules.get(found.module) ?? { screen: false, files: [] };
        entry.screen = entry.screen || found.screen;
        entry.files.push(rel);
        modules.set(found.module, entry);
    }

    const lines = [];

    for (const [module, { screen, files }] of modules) {
        for (const gap of gapsFor(module, screen, changed)) {
            lines.push(`- ${module} (${files.slice(0, 3).join(', ')}${files.length > 3 ? ', …' : ''}): ${gap.text}`);
        }
    }

    if (lines.length > 0) {
        block('Stop', `Documentation is out of step with this change:\n${lines.join('\n')}\n${INSTRUCTIONS}`);
    }
}

function onPostToolUse(root, payload) {
    const input = payload?.tool_input ?? {};
    const filePath = payload?.tool_response?.filePath ?? input.file_path ?? input.relative_path;

    if (!filePath) {
        return;
    }

    const rel = path.relative(root, path.resolve(root, filePath)).split(path.sep).join('/');

    if (!rel || rel.startsWith('../')) {
        return;
    }

    const found = classify(rel);

    if (!found) {
        return;
    }

    const gaps = gapsFor(found.module, found.screen, changedPaths(root));

    if (gaps.length === 0) {
        return;
    }

    // Once per module and rule per session: the Stop hook is the real gate.
    const marker = path.join(os.tmpdir(), `nexora-docs-sync-${payload.session_id ?? 'session'}.json`);
    const seen = existsSync(marker) ? JSON.parse(readFileSync(marker, 'utf8')) : [];
    const fresh = gaps.filter((gap) => !seen.includes(`${found.module}:${gap.rule}`));

    if (fresh.length === 0) {
        return;
    }

    writeFileSync(marker, JSON.stringify([...seen, ...fresh.map((gap) => `${found.module}:${gap.rule}`)]));

    block(
        'PostToolUse',
        `${rel} belongs to the ${found.module} module, but ${fresh.map((gap) => gap.text).join(', and ')}. ` +
            `${INSTRUCTIONS} The turn cannot end until this is done.`,
    );
}

function main() {
    const raw = readStdin();

    if (!raw.trim()) {
        return;
    }

    const payload = JSON.parse(raw);
    const root = git(process.cwd(), ['rev-parse', '--show-toplevel']).trim();

    if (payload.hook_event_name === 'Stop') {
        onStop(root, payload);
    } else {
        onPostToolUse(root, payload);
    }
}

try {
    main();
} catch {
    // Never let a hook failure interfere with the session.
    process.exit(0);
}
