import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import CountryCombobox from './CountryCombobox.vue';

enableAutoUnmount(afterEach);

const countries = [
    { code: 'AT', name: 'Austria' },
    { code: 'DE', name: 'Germany' },
    { code: 'IE', name: 'Ireland' },
    { code: 'IT', name: 'Italy' },
    { code: 'MT', name: 'Malta' },
];

function mountBox(modelValue: string | null = 'IT') {
    return mount(CountryCombobox, { props: { modelValue, countries, id: 'country' }, attachTo: document.body });
}

async function type(wrapper: ReturnType<typeof mountBox>, text: string) {
    const input = wrapper.find('input');
    await input.trigger('focus');
    await input.setValue(text);

    return input;
}

const options = (wrapper: ReturnType<typeof mountBox>) => wrapper.findAll('[role="option"]').map((option) => option.text());

describe('CountryCombobox', () => {
    it('shows the name of the current country, with the combobox roles', () => {
        const wrapper = mountBox();
        const input = wrapper.find('input');

        expect(input.element.value).toBe('Italy');
        expect(input.attributes('role')).toBe('combobox');
        expect(input.attributes('aria-autocomplete')).toBe('list');
        expect(input.attributes('aria-expanded')).toBe('false');
        expect(wrapper.find('[role="listbox"]').attributes('id')).toBe(input.attributes('aria-controls'));
    });

    it('filters by name', async () => {
        const wrapper = mountBox();
        const input = await type(wrapper, 'al');

        expect(options(wrapper)).toEqual(['ItalyIT', 'MaltaMT']);
        expect(input.attributes('aria-expanded')).toBe('true');
    });

    it('filters by exact country code', async () => {
        const wrapper = mountBox();

        await type(wrapper, 'de');
        expect(options(wrapper)).toEqual(['GermanyDE']);

        await type(wrapper, 'IE');
        expect(options(wrapper)).toEqual(['IrelandIE']);
    });

    it('shows at most 40 matches', async () => {
        const many = Array.from({ length: 60 }, (_, i) => ({ code: `A${i}`, name: `Land ${i}` }));
        const wrapper = mount(CountryCombobox, { props: { modelValue: null, countries: many, id: 'country' } });

        await wrapper.find('input').trigger('focus');
        await wrapper.find('input').setValue('land');

        expect(wrapper.findAll('[role="option"]')).toHaveLength(40);
    });

    it('moves with the arrow keys and picks with Enter', async () => {
        const wrapper = mountBox();
        const input = await type(wrapper, 'a');

        expect(wrapper.find('[aria-selected="true"]').text()).toBe('AustriaAT');

        await input.trigger('keydown', { key: 'ArrowDown' });
        await input.trigger('keydown', { key: 'ArrowDown' });
        await input.trigger('keydown', { key: 'ArrowUp' });

        const active = wrapper.find('[aria-selected="true"]');
        expect(active.text()).toBe('GermanyDE');
        expect(input.attributes('aria-activedescendant')).toBe(active.attributes('id'));

        await input.trigger('keydown', { key: 'Enter' });

        expect(wrapper.emitted('update:modelValue')).toEqual([['DE']]);
        expect(input.element.value).toBe('Germany');
        expect(input.attributes('aria-expanded')).toBe('false');
    });

    it('picks an option with the mouse', async () => {
        const wrapper = mountBox();
        await type(wrapper, 'mal');

        await wrapper.find('[role="option"]').trigger('mousedown');

        expect(wrapper.emitted('update:modelValue')).toEqual([['MT']]);
    });

    it('closes the list with Escape', async () => {
        const wrapper = mountBox();
        const input = await type(wrapper, 'a');

        await input.trigger('keydown', { key: 'Escape' });

        expect(input.attributes('aria-expanded')).toBe('false');
        expect(wrapper.find('[role="listbox"]').isVisible()).toBe(false);
        expect(wrapper.emitted('update:modelValue')).toBeUndefined();
    });

    it('restores the current country when the field loses the focus', async () => {
        const wrapper = mountBox();
        const input = await type(wrapper, 'germ');

        await input.trigger('blur');

        expect(input.element.value).toBe('Italy');
        expect(input.attributes('aria-expanded')).toBe('false');
        expect(wrapper.emitted('update:modelValue')).toBeUndefined();
    });

    it('opens the full list on focus, with the current country active and the text selected', async () => {
        const wrapper = mountBox('IT');
        const input = wrapper.find('input');
        const scrolled = vi.fn();
        Element.prototype.scrollIntoView = scrolled;

        await input.trigger('focus');
        await flushPromises();

        expect(options(wrapper)).toEqual(['AustriaAT', 'GermanyDE', 'IrelandIE', 'ItalyIT', 'MaltaMT']);
        expect(input.attributes('aria-expanded')).toBe('true');
        expect(wrapper.find('[aria-selected="true"]').text()).toBe('ItalyIT');
        expect(input.element.selectionStart).toBe(0);
        expect(input.element.selectionEnd).toBe('Italy'.length);
        expect(scrolled).toHaveBeenCalled();
        expect(scrolled.mock.contexts[0]).toBe(wrapper.find('[aria-selected="true"]').element);
        // jsdom has no scrollIntoView.
        delete (Element.prototype as Partial<Element>).scrollIntoView;
    });

    it('shows every country on focus, not only 40', async () => {
        const many = Array.from({ length: 60 }, (_, i) => ({ code: `A${i}`, name: `Land ${i}` }));
        const wrapper = mount(CountryCombobox, { props: { modelValue: 'A50', countries: many, id: 'country' } });

        await wrapper.find('input').trigger('focus');

        expect(wrapper.findAll('[role="option"]')).toHaveLength(60);
        expect(wrapper.find('[aria-selected="true"]').text()).toBe('Land 50A50');
    });

    it('filters again when the user types over the selected text', async () => {
        const wrapper = mountBox('IT');
        const input = await type(wrapper, 'ger');

        expect(options(wrapper)).toEqual(['GermanyDE']);
        expect(wrapper.find('[aria-selected="true"]').text()).toBe('GermanyDE');
    });

    it('starts at the first country on focus with no country', async () => {
        const wrapper = mountBox(null);

        await wrapper.find('input').trigger('focus');

        expect(options(wrapper)).toHaveLength(5);
        expect(wrapper.find('[aria-selected="true"]').text()).toBe('AustriaAT');
    });

    it('is empty with no country', () => {
        expect(mountBox(null).find('input').element.value).toBe('');
    });
});
