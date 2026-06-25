@props(['name' => 'content', 'value' => '', 'placeholder' => 'Start writing...', 'id' => null])

@php
    $editorId = $id ?? $name . '_editor';
    $hiddenId = $id ?? $name;
@endphp

<div
    x-data="{
        editor: null,
        content: @js($value),
        init() {
            import('../../js/components/editor.js').then(mod => {
                this.editor = mod.createEditor(this.$refs.editorElement, {
                    content: this.content,
                    placeholder: @js($placeholder),
                    onUpdate: (html) => {
                        this.content = html;
                    },
                });
            });
        },
        get isReady() {
            return this.editor !== null;
        },
    }"
    x-init="init()"
    class="rich-editor-wrapper"
>
    <div class="mb-1 flex flex-wrap items-center gap-1 rounded-t-lg border border-gray-300 bg-gray-50 px-2 py-1.5">
        <button
            type="button"
            class="rounded px-2 py-1 text-sm font-semibold text-gray-700 hover:bg-gray-200"
            x-on:click="if (editor) { editor.chain().focus().toggleBold().run() }"
            x-bind:class="editor?.isActive('bold') ? 'bg-gray-200 text-indigo-600' : ''"
            title="{{ __('Bold') }}"
        >
            <span class="text-base font-bold">B</span>
        </button>

        <button
            type="button"
            class="rounded px-2 py-1 text-sm font-semibold text-gray-700 hover:bg-gray-200"
            x-on:click="if (editor) { editor.chain().focus().toggleItalic().run() }"
            x-bind:class="editor?.isActive('italic') ? 'bg-gray-200 text-indigo-600' : ''"
            title="{{ __('Italic') }}"
        >
            <span class="text-base italic">I</span>
        </button>

        <span class="mx-1 h-5 w-px bg-gray-300"></span>

        <button
            type="button"
            class="rounded px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-200"
            x-on:click="if (editor) { editor.chain().focus().toggleHeading({ level: 2 }).run() }"
            x-bind:class="editor?.isActive('heading', { level: 2 }) ? 'bg-gray-200 text-indigo-600' : ''"
            title="{{ __('Heading 2') }}"
        >
            H2
        </button>

        <button
            type="button"
            class="rounded px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-200"
            x-on:click="if (editor) { editor.chain().focus().toggleHeading({ level: 3 }).run() }"
            x-bind:class="editor?.isActive('heading', { level: 3 }) ? 'bg-gray-200 text-indigo-600' : ''"
            title="{{ __('Heading 3') }}"
        >
            H3
        </button>

        <button
            type="button"
            class="rounded px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-200"
            x-on:click="if (editor) { editor.chain().focus().toggleHeading({ level: 4 }).run() }"
            x-bind:class="editor?.isActive('heading', { level: 4 }) ? 'bg-gray-200 text-indigo-600' : ''"
            title="{{ __('Heading 4') }}"
        >
            H4
        </button>

        <span class="mx-1 h-5 w-px bg-gray-300"></span>

        <button
            type="button"
            class="rounded px-2 py-1 text-sm text-gray-700 hover:bg-gray-200"
            x-on:click="if (editor) { editor.chain().focus().toggleBulletList().run() }"
            x-bind:class="editor?.isActive('bulletList') ? 'bg-gray-200 text-indigo-600' : ''"
            title="{{ __('Bullet List') }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                <path d="M3 6h.01M3 12h.01M3 18h.01M8 6h13M8 12h13M8 18h13"/>
            </svg>
        </button>

        <button
            type="button"
            class="rounded px-2 py-1 text-sm text-gray-700 hover:bg-gray-200"
            x-on:click="if (editor) { editor.chain().focus().toggleOrderedList().run() }"
            x-bind:class="editor?.isActive('orderedList') ? 'bg-gray-200 text-indigo-600' : ''"
            title="{{ __('Ordered List') }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                <path d="M3 6h.01M3 12h.01M3 18h.01M8 6h13M8 12h13M8 18h13"/>
            </svg>
        </button>

        <span class="mx-1 h-5 w-px bg-gray-300"></span>

        <button
            type="button"
            class="rounded px-2 py-1 text-sm text-gray-700 hover:bg-gray-200"
            x-on:click="if (editor) { editor.chain().focus().toggleBlockquote().run() }"
            x-bind:class="editor?.isActive('blockquote') ? 'bg-gray-200 text-indigo-600' : ''"
            title="{{ __('Blockquote') }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                <path d="M3 21c3 0 5-2 5-5V6H4v6h2c0 2-1 3-3 3m14 0c3 0 5-2 5-5V6h-4v6h2c0 2-1 3-3 3"/>
            </svg>
        </button>

        <button
            type="button"
            class="rounded px-2 py-1 text-sm text-gray-700 hover:bg-gray-200"
            x-on:click="if (editor) { editor.chain().focus().toggleCodeBlock().run() }"
            x-bind:class="editor?.isActive('codeBlock') ? 'bg-gray-200 text-indigo-600' : ''"
            title="{{ __('Code Block') }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                <path d="M16 18l6-6-6-6M8 6l-6 6 6 6"/>
            </svg>
        </button>

        <span class="mx-1 h-5 w-px bg-gray-300"></span>

        <button
            type="button"
            class="rounded px-2 py-1 text-sm text-gray-700 hover:bg-gray-200"
            x-on:click="if (editor) {
                const url = window.prompt('{{ __("Enter link URL:") }}');
                if (url) {
                    if (editor.isActive('link')) {
                        editor.chain().focus().unsetLink().run();
                    } else {
                        editor.chain().focus().setLink({ href: url }).run();
                    }
                }
            }"
            x-bind:class="editor?.isActive('link') ? 'bg-gray-200 text-indigo-600' : ''"
            title="{{ __('Link') }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                <path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/>
                <path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>
            </svg>
        </button>

        <button
            type="button"
            class="rounded px-2 py-1 text-sm text-gray-700 hover:bg-gray-200"
            x-on:click="if (editor) {
                const url = window.prompt('{{ __("Enter image URL:") }}');
                if (url) {
                    editor.chain().focus().setImage({ src: url }).run();
                }
            }"
            title="{{ __('Image') }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <path d="M21 15l-5-5L5 21"/>
            </svg>
        </button>
    </div>

    <div
        x-ref="editorElement"
        class="min-h-[400px] w-full rounded-b-lg border border-t-0 border-gray-300 bg-white px-4 py-3 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500"
    ></div>

    <input type="hidden" name="{{ $name }}" x-model="content" />
</div>
