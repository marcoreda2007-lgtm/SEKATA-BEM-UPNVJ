const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { runInNewContext } = require('node:vm');
const { join } = require('node:path');

const source = readFileSync(join(__dirname, '../public/js/sidebar.js'), 'utf8');

function setup(saved = null, storageBlocked = false) {
    const classes = new Set();
    const attributes = {};
    let click;
    const toggle = {
        setAttribute: (name, value) => { attributes[name] = value; },
        addEventListener: (event, callback) => { click = callback; },
    };
    const sidebar = { classList: {
        toggle: (name, enabled) => enabled ? classes.add(name) : classes.delete(name),
        contains: (name) => classes.has(name),
    } };
    const elements = {
        sidebarToggle: toggle,
        desktopSidebar: sidebar,
        sidebarToggleArrow: { setAttribute() {} },
    };
    runInNewContext(source, {
        document: { getElementById: (id) => elements[id] },
        localStorage: {
            getItem() {
                if (storageBlocked) throw new Error('Blocked');
                return saved;
            },
            setItem(key, value) {
                if (storageBlocked) throw new Error('Blocked');
                saved = value;
            },
        },
    });
    return { attributes, classes, click: () => click(), saved: () => saved };
}

test('sidebar toggles and saves its state with accessible labels', () => {
    const ui = setup();
    assert.equal(ui.attributes['aria-expanded'], 'true');
    ui.click();
    assert.equal(ui.classes.has('is-collapsed'), true);
    assert.equal(ui.attributes['aria-expanded'], 'false');
    assert.equal(ui.attributes['aria-label'], 'Buka sidebar');
    assert.equal(ui.saved(), 'true');
    ui.click();
    assert.equal(ui.attributes['aria-expanded'], 'true');
    assert.equal(ui.saved(), 'false');
});

test('saved collapsed preference is restored', () => {
    assert.equal(setup('true').classes.has('is-collapsed'), true);
});

test('blocked storage does not prevent toggling', () => {
    const ui = setup(null, true);
    ui.click();
    assert.equal(ui.attributes['aria-expanded'], 'false');
});

test('pages without the desktop sidebar do not register handlers', () => {
    assert.doesNotThrow(() => runInNewContext(source, {
        document: { getElementById: () => null },
    }));
});
