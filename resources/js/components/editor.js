import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Image from '@tiptap/extension-image'
import CodeBlockLowlight from '@tiptap/extension-code-block-lowlight'
import Placeholder from '@tiptap/extension-placeholder'
import { common, createLowlight } from 'lowlight'

const lowlight = createLowlight(common)

export function createEditor(element, options = {}) {
    const {
        content = '',
        placeholder = 'Start writing...',
        onUpdate = null,
    } = options

    const editor = new Editor({
        element,
        extensions: [
            StarterKit.configure({
                codeBlock: false,
                heading: {
                    levels: [2, 3, 4],
                },
            }),
            Link.configure({
                openOnClick: true,
                HTMLAttributes: {
                    rel: 'noopener noreferrer',
                    target: '_blank',
                },
            }),
            Image.configure({
                inline: false,
                allowBase64: true,
            }),
            CodeBlockLowlight.configure({
                lowlight,
            }),
            Placeholder.configure({
                placeholder,
            }),
        ],
        content,
        onUpdate: onUpdate ? ({ editor }) => onUpdate(editor.getHTML()) : null,
    })

    return editor
}

export function getEditorContent(editor) {
    return editor.getHTML()
}

export function setEditorContent(editor, content) {
    editor.commands.setContent(content)
}

export function clearEditorContent(editor) {
    editor.commands.clearContent()
}

export function isEditorActive(editor, name, attributes = {}) {
    return editor.isActive(name, attributes)
}

export function toggleBold(editor) {
    editor.chain().focus().toggleBold().run()
}

export function toggleItalic(editor) {
    editor.chain().focus().toggleItalic().run()
}

export function toggleHeading(editor, level) {
    editor.chain().focus().toggleHeading({ level }).run()
}

export function toggleBulletList(editor) {
    editor.chain().focus().toggleBulletList().run()
}

export function toggleOrderedList(editor) {
    editor.chain().focus().toggleOrderedList().run()
}

export function toggleBlockquote(editor) {
    editor.chain().focus().toggleBlockquote().run()
}

export function toggleCodeBlock(editor) {
    editor.chain().focus().toggleCodeBlock().run()
}

export function setLink(editor, url) {
    if (editor.isActive('link')) {
        editor.chain().focus().unsetLink().run()
    } else {
        editor.chain().focus().setLink({ href: url }).run()
    }
}

export function addImage(editor, src) {
    editor.chain().focus().setImage({ src }).run()
}
