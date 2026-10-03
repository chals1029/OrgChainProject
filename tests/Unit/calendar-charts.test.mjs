import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
import { test } from 'node:test';

// Execute the actual chart aggregation functions, without a browser or a
// duplicated implementation. No application data or live records are changed.
function readFunction(file, name) {
    const source = readFileSync(new URL(`../../resources/views/org/${file}.blade.php`, import.meta.url), 'utf8');
    const start = new RegExp(`^([ \\t]*)function ${name}\\(`, 'm').exec(source);
    assert.ok(start, `Missing ${name}`);
    const tail = source.slice(start.index);
    const end = new RegExp(`^${start[1]}\\}`, 'm').exec(tail);
    assert.ok(end, `Unclosed ${name}`);
    return tail.slice(0, end.index + end[0].length);
}
const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const context = vm.createContext({ monthNames: months, osoLiveMeta: { kpis: {}, typeLabels: [] }, window: { osoLiveRows: [] } });
for (const name of ['buildTrendAxis', 'aggOsoRows']) vm.runInContext(readFunction('dashboard', name), context);
for (const name of ['rowMatchesFilters', 'buildMonthAxis', 'buildMonthlySeries']) vm.runInContext(readFunction('analytics', name), context);
const plain = value => JSON.parse(JSON.stringify(value));

test('dashboard year axis is Jan–Dec, including empty years and all-years mode', () => {
    for (const year of ['2025', '2026', '2028', 'all']) {
        const axis = plain(context.buildTrendAxis(year, []));
        assert.deepEqual(axis.map(p => p.label), months);
        assert.equal(axis[0].key, year === 'all' ? '01' : `${year}-01`);
        assert.equal(axis[11].key, year === 'all' ? '12' : `${year}-12`);
    }
});

test('all-years dashboard counts every year instead of the last rolling window', () => {
    const rows = ['2024-01', '2026-01', '2025-12'].map(ym => ({ ym, bucket: 'approved', college: 'CICS', scope: 'in-campus' }));
    const trend = plain(context.aggOsoRows(rows, false, 'all').trend);
    assert.deepEqual(trend.labels, months);
    assert.deepEqual(trend.data, [2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1]);
});

test('analytics uses calendar year, preserves zero months and rolls cumulative totals in chronological order', () => {
    const rows = [
        {year:2025, month:'Dec', monthNumber:12, academicYearEnd:2026, allocated:999, utilized:999},
        {year:2026, month:'Jan', monthNumber:1, academicYearEnd:2026, allocated:100, utilized:20},
        {year:2026, month:'Aug', monthNumber:8, academicYearEnd:2027, allocated:200, utilized:30},
        {year:2026, month:'Dec', monthNumber:12, academicYearEnd:2027, allocated:50, utilized:10},
    ];
    const filters = {year:'2026', semester:'all', month:'all', scope:'all'};
    const matching = rows.filter(r => context.rowMatchesFilters(r, filters));
    const series = plain(context.buildMonthlySeries(matching, filters));
    assert.equal(matching.length, 3);
    assert.deepEqual(series.labels, months);
    assert.deepEqual(series.allocated, [100,0,0,0,0,0,0,200,0,0,0,50]);
    assert.equal(series.cumulativeAlloc[11], 350);
    assert.equal(series.cumulativeUtil[11], 60);
    const december = {...filters, month:'Dec'};
    const filtered = plain(context.buildMonthlySeries(rows.filter(r => context.rowMatchesFilters(r, december)), december));
    assert.deepEqual(filtered.labels, months);
    assert.deepEqual(filtered.allocated, [0,0,0,0,0,0,0,0,0,0,0,50]);
    const all = {...filters, year:'all'};
    assert.equal(context.buildMonthlySeries(rows, all).allocated[11], 1049);
});
