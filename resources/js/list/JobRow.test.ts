import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { row } from './fixtures';
import JobRow from './JobRow.vue';

describe('JobRow', () => {
    it('shows each line of the row', () => {
        const wrapper = mount(JobRow, { props: { row: row({ alsoOn: ['We Work Remotely'] }) } });

        expect(wrapper.find('.t').text()).toBe('Senior Laravel Developer');
        expect(wrapper.find('.meta').text()).toContain('Acme');
        expect(wrapper.find('.meta').text()).toContain('via Himalayas');
        expect(wrapper.find('.meta').text()).toContain('also on We Work Remotely');
        expect(wrapper.find('.meta').text()).toContain('2 days ago');
        expect(wrapper.find('.pay').text()).toContain('Salary not stated');
        expect(wrapper.find('.pay').text()).toContain('Full time, Senior');
        expect(wrapper.find('.stamp').attributes('aria-label')).toBe('IT Admitted');
    });

    it('shows the salary, the converted salary and the OTE badge', () => {
        const wrapper = mount(JobRow, {
            props: { row: row({ salary: { original: '$150k OTE', converted: '≈ €120k / year', unknown: false, ote: true } }) },
        });

        expect(wrapper.find('.pay').text()).toContain('$150k OTE');
        expect(wrapper.find('.pay .conv').text()).toBe('≈ €120k / year');
        expect(wrapper.find('.pay .badge').text()).toBe('OTE');
        expect(wrapper.find('.q').exists()).toBe(false);
    });

    it('marks an unseen job, and fades the stamp of a seen one', () => {
        const unseen = mount(JobRow, { props: { row: row() } });
        const seen = mount(JobRow, { props: { row: row({ seen: true }) } });

        expect(unseen.classes()).toContain('unseen');
        expect(unseen.find('.dot').exists()).toBe(true);
        expect(unseen.find('.stamp').classes()).not.toContain('seen');
        expect(seen.classes()).not.toContain('unseen');
        expect(seen.find('.stamp').classes()).toContain('seen');
    });

    it('keeps the stamp angle of the job between renders', async () => {
        const wrapper = mount(JobRow, { props: { row: row({ id: 648 }) } });
        const angle = (wrapper.find('.stamp').element as HTMLElement).style.getPropertyValue('--r');

        await wrapper.setProps({ row: row({ id: 648, seen: true }) });

        expect(angle).toBe('1.2deg');
        expect((wrapper.find('.stamp').element as HTMLElement).style.getPropertyValue('--r')).toBe(angle);
    });

    it('shows the pressed state and the actions of each tab', () => {
        const saved = mount(JobRow, { props: { row: row({ state: 'saved' }) } });
        const fresh = mount(JobRow, { props: { row: row() } });

        expect(saved.find('[aria-pressed="true"]').text()).toBe('Saved');
        expect(saved.text()).not.toContain('Not for me');
        expect(fresh.text()).toContain('Not for me');
    });

    it('gives a "Location not stated" row only Save, Not for me and Apply, and no unseen dot', () => {
        const wrapper = mount(JobRow, { props: { row: row({ stamp: { kind: 'not_stated', place: 'Location' } }), group: true } });

        expect(wrapper.findAll('.acts button').map((button) => button.text())).toEqual(['Save', 'Not for me', 'Apply']);
        expect(wrapper.find('.dot').exists()).toBe(false);
        expect(wrapper.find('.stamp').classes()).toContain('unk');
    });

    it('sends the action and the apply events', async () => {
        const job = row();
        const wrapper = mount(JobRow, { props: { row: job } });

        await wrapper.findAll('.act')[1].trigger('click');
        await wrapper.find('.btn').trigger('click');

        expect(wrapper.emitted('act')).toEqual([[job, 'hidden']]);
        expect(wrapper.emitted('apply')).toEqual([[job]]);
    });
});
