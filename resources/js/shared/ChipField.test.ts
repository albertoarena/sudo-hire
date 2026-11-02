import { enableAutoUnmount, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import ChipField from './ChipField.vue';

enableAutoUnmount(afterEach);

function mountField(modelValue: string[] = ['laravel'], lowercase = true) {
    return mount(ChipField, { props: { modelValue, label: 'Add a keyword', placeholder: 'Add a keyword', lowercase } });
}

describe('ChipField', () => {
    it('shows each value as a chip with a remove button', () => {
        const wrapper = mountField(['laravel', 'php']);

        expect(wrapper.findAll('.chip').map((chip) => chip.text())).toEqual(['laravel', 'php']);
        expect(wrapper.find('.chip button').attributes('aria-label')).toBe('Remove laravel');
        expect(wrapper.find('input').attributes('aria-label')).toBe('Add a keyword');
    });

    it('adds a trimmed, lowercase keyword with Enter and clears the field', async () => {
        const wrapper = mountField();
        const input = wrapper.find('input');

        await input.setValue('  Symfony ');
        await input.trigger('keydown', { key: 'Enter' });

        expect(wrapper.emitted('update:modelValue')).toEqual([[['laravel', 'symfony']]]);
        expect(input.element.value).toBe('');
    });

    it('keeps the spelling when lowercase is off', async () => {
        const wrapper = mountField([], false);

        await wrapper.find('input').setValue('Acme Corp');
        await wrapper.find('input').trigger('keydown', { key: 'Enter' });

        expect(wrapper.emitted('update:modelValue')).toEqual([[['Acme Corp']]]);
    });

    it('does not add a duplicate or an empty keyword', async () => {
        const wrapper = mountField();
        const input = wrapper.find('input');

        await input.setValue('Laravel');
        await input.trigger('keydown', { key: 'Enter' });
        await input.setValue('   ');
        await input.trigger('keydown', { key: 'Enter' });

        expect(wrapper.emitted('update:modelValue')).toBeUndefined();
        expect(input.element.value).toBe('');
    });

    it('removes a keyword with its button', async () => {
        const wrapper = mountField(['laravel', 'php']);

        await wrapper.findAll('.chip button')[0].trigger('click');

        expect(wrapper.emitted('update:modelValue')).toEqual([[['php']]]);
    });
});
