<script setup>
import {Editor, EditorContent} from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import {onBeforeUnmount, onMounted, onUnmounted, ref} from 'vue'
import Link from '@tiptap/extension-link'
import { ListBulletIcon } from '@heroicons/vue/24/outline'
import { CodeBracketIcon } from '@heroicons/vue/24/outline'
import { FaceSmileIcon } from '@heroicons/vue/24/outline'
import { PaperClipIcon } from '@heroicons/vue/24/outline'
import { PaperAirplaneIcon } from '@heroicons/vue/24/outline'
import { Placeholder } from '@tiptap/extensions'

const editor = new Editor({
  extensions: [
    StarterKit,
    Placeholder.configure({
      placeholder: 'Write something …',
    }),
    Link.configure({
      openOnClick: false,
      validate: href => /^https?:\/\//.test(href),
    }),
  ],
  content: '',
})

const toggleBold = () => editor.chain().focus().toggleBold().run()
const toggleItalic = () => editor.chain().focus().toggleItalic().run()
const toggleBulletList = () => editor.chain().focus().toggleBulletList().run()
const toggleCodeBlock = () => editor.chain().focus().toggleCodeBlock().run()

// 3. Функція для встановлення/видалення посилання
const setLink = () => {
  // Якщо посилання вже активне, видаляємо його
  if (editor.isActive('link')) {
    editor.chain().focus().unsetLink().run()
    return
  }
  const url = window.prompt('URL посилання:')
  if (url === null || url === '') {
    return
  }
  editor.chain().focus().setLink({href: url}).run()
}

const buttonClass = (active) =>
  `px-2 py-1 rounded ${active ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-200'}`

const showEmojiPicker = ref(false)

const emojis = [
  '😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂',
  '🙂', '🙃', '😉', '😊', '😇', '🥰', '😍', '🤩',
  '😘', '😗', '😚', '😙', '😋', '😛', '😜', '🤪',
  '😝', '🤑', '🤗', '🤭', '🤫', '🤔', '🤐', '🤨',
  '😐', '😑', '😶', '😏', '😒', '🙄', '😬', '🤥',
  '😌', '😔', '😪', '🤤', '😴', '😷', '🤒', '🤕',
  '🤢', '🤮', '🤧', '🥵', '🥶', '🥴', '😵', '🤯',
  '🤠', '🥳', '😎', '🤓', '🧐', '😕', '😟', '🙁',
  '😮', '😯', '😲', '😳', '🥺', '😦', '😧', '😨',
  '😰', '😥', '😢', '😭', '😱', '😖', '😣', '😞',
  '😓', '😩', '😫', '😤', '😡', '😠', '🤬', '😈',
  '👿', '💀', '☠️', '💩', '🤡', '👹', '👺', '👻',
  '👽', '👾', '🤖', '😺', '😸', '😹', '😻', '😼',
  '😽', '🙀', '😿', '😾', '💋', '👋', '🤚', '🖐️',
  '✋', '🖖', '👌', '✌️', '🤞', '🤟', '🤘', '🤙',
  '👈', '👉', '👆', '🖕', '👇', '☝️', '👍', '👎',
  '✊', '👊', '🤛', '🤜', '👏', '🙌', '👐', '🤲',
  '🤝', '🙏', '✍️', '💅', '🤳', '💪'
]

const toggleEmojiPicker = () => {
  showEmojiPicker.value = !showEmojiPicker.value
}

const selectEmoji = async (emoji) => {
  // Отримуємо позицію курсора
  editor.chain()
    .focus()
    .insertContent(emoji)
    .run()
}

const sendMessage = () => {
  const html = editor.getHTML()
  console.log('Send message:', html)
  editor.commands.clearContent()
}
const addFiles = () => {
  alert('addFiles')
}

onBeforeUnmount(() => {
  editor.destroy()
})
const closeEmojiPicker = (event) => {
  const popup = document.querySelector('.emojiPopup2') // клас твого попапу
  const toggleButton = document.querySelector('.emojiToggle') // кнопка відкриття

  // Якщо клік **не на попапі** і **не на кнопці**
  if (popup && !popup.contains(event.target) && toggleButton && !toggleButton.contains(event.target)) {
    showEmojiPicker.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', closeEmojiPicker)
})

onUnmounted(() => {
  document.removeEventListener('click', closeEmojiPicker)
})
</script>

<template>
  <div class="border rounded-lg bg-gray-50 border-gray-300 p-2 flex flex-col gap-2">
    <div class="flex gap-2 text-gray-600">
      <button @click="toggleBold" :class="buttonClass(editor.isActive('bold'))">B</button>
      <button @click="toggleItalic" :class="buttonClass(editor.isActive('italic'))"><i>I</i></button>
      <button @click="setLink" :class="buttonClass(editor.isActive('link'))">🔗 </button>
      <button @click="toggleBulletList" :class="buttonClass(editor.isActive('bulletList'))">
        <ListBulletIcon class="pointer-events-none col-start-1 row-start-1 size-5 self-center"/>
      </button>
      <button @click="toggleCodeBlock" :class="buttonClass(editor.isActive('codeBlock'))">
        <CodeBracketIcon class="pointer-events-none col-start-1 row-start-1 size-5 self-center"/>
      </button>
    </div>

    <div class="flex items-end gap-2">
      <div class="flex-1 bg-white rounded-lg p-2 min-h-[40px] max-h-[150px] overflow-auto">
        <EditorContent :editor="editor"/>
      </div>
      <button @click="toggleEmojiPicker"
              class="emojiToggle mb-2 px-1 py-1 rounded text-gray-600 hover:bg-blue-100 relative">
        <FaceSmileIcon class="cursor-pointer size-5 self-center hover:text-blue-600"/>
              <!-- Emoji Popup -->
        <div v-show="showEmojiPicker"
             class="emojiPopup2 absolute bottom-full right-0 mb-2 z-50 bg-white border rounded shadow-lg p-3
                    w-80 max-h-96 overflow-auto">
          <div class="emojiPopupContent flex flex-wrap gap-1">
            <span v-for="emoji in emojis"
                  :key="emoji"
                  class="emoji cursor-pointer text-lg"
                  @click="selectEmoji(emoji)">
              {{ emoji }}
            </span>
          </div>
        </div>
      </button>
      <button @click="addFiles" class="mb-2 px-1 py-1 rounded text-gray-600 hover:bg-blue-100">
        <PaperClipIcon class="cursor-pointer col-start-1 row-start-1 size-5 self-center hover:text-blue-600"/>
      </button>
      <button @click="sendMessage" class="mb-2 px-1 py-1 rounded text-gray-600 hover:bg-blue-100">
        <PaperAirplaneIcon class="cursor-pointer col-start-1 row-start-1 size-5 self-center text-blue-600 hover:text-blue-800"/>
      </button>
    </div>
  </div>


</template>

<style scoped>
::v-deep(.ProseMirror:focus) {
  outline: none;
}

::v-deep(.tiptap p.is-editor-empty:first-child::before) {
  color: #adb5bd;
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
}

::v-deep(.tiptap ul),
::v-deep(.tiptap ol) {
  padding-left: 1.5rem;
  margin-left: 1.25rem;
  list-style-position: outside;
}

::v-deep(.tiptap ul li),
::v-deep(.tiptap ol li) {
  position: relative;
  margin-left: 0.25em;
  display: block;
}

::v-deep(.tiptap ul li p)::before {
  content: "•";
  position: absolute;
  left: 0;
  color: black;
}

::v-deep(.tiptap ul li p) {
  display: block;
  margin-left: 1.2em;
}

::v-deep(.tiptap a) {
  color: blue;
  text-decoration: underline;
  cursor: pointer;
}

.emojiToggle {
  position: relative;
  cursor: pointer;
}

.emojiPopup2 {
  position: absolute;
  bottom: 35px;
  right: 0;
  z-index: 100;
}

.emojiPopupContent {
  padding: 10px;
  max-height: 200px;
  max-width: 500px;
  overflow-y: auto;
  display: flex;
  flex-wrap: wrap;
  gap: 0;
}

.emoji {
  font-size: 20px;
  cursor: pointer;
  padding: 5px;
  border-radius: 4px;
  transition: background-color 0.2s;

  &:hover {
    background-color: #f0f0f0;
  }
}
</style>